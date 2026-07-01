<?php

namespace App\Services\Finance;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\JobReport;
use App\Models\JobSchedule;
use App\Models\JobScheduleRoom;
use App\Models\Customer;
use App\Models\Building;
use App\Models\RoomRentalUnit;
use App\Models\InvoiceFile;
use App\Models\JobScheduleBaFile;
use App\Models\ContractFile;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Services\DocumentNumberService;

class InvoiceGenerationService
{
    private const BILLABLE_COMPLETED_STATUSES = ['completed', 'done_job', 'dpf'];
    private const NON_BILLABLE_JOB_STATUSES = ['cancelled', 'suspend', 'meninggalkan_lokasi', 'undone'];
    private const NON_BILLABLE_ROOM_STATUSES = ['cancelled', 'suspend', 'undone'];
    private const NON_BILLABLE_JOB_TYPES = ['install_free', 'install free', 'remove_free', 'remove free'];

    protected $documentNumberService;

    public function __construct(DocumentNumberService $documentNumberService)
    {
        $this->documentNumberService = $documentNumberService;
    }

    /**
     * Auto-generate invoice for completed jobs in rental period
     */
    public function autoGenerateInvoiceForRentalPeriod(int $contractId, string $rentalPeriod, Carbon $periodStart, Carbon $periodEnd): array
    {
        try {
            DB::beginTransaction();

            $contract = Contract::with(['customer', 'contractRentals', 'billingGroup', 'quotation'])
                ->whereKey($contractId)
                ->lockForUpdate()
                ->firstOrFail();

            // NEW: Check if invoice generation is on hold for this contract
            if ($contract->hold_invoice) {
                DB::rollBack();

                return [
                    'success' => false,
                    'message' => 'Invoice generation is on hold for this contract (Hold Invoice is active).',
                    'hold_invoice' => true
                ];
            }

            // Check if the invoice trigger jobs for this rental period are completed.
            // Before Service: IR/install BA triggers invoice.
            // After Service: CSR/service BA triggers invoice.
            $allJobsCompleted = $this->checkInvoiceTriggerJobsCompletedInPeriod($contract, $periodStart, $periodEnd);
            
            if (!$allJobsCompleted) {
                DB::rollBack();

                return [
                    'success' => false,
                    'message' => 'Invoice trigger jobs in the rental period are not completed with BA date yet',
                    'completed_jobs' => $this->getCompletedJobsCount($contract, $periodStart, $periodEnd),
                    'total_jobs' => $this->getTotalJobsCount($contract, $periodStart, $periodEnd),
                    'invoice_timing' => $this->getInvoiceTiming($contract),
                ];
            }

            // NEW: Check BA Files Supported
            if ($contract->ba_files_supported) {
                if (!$this->checkAllJobsHaveVerifiedBaFiles($contract, $periodStart, $periodEnd)) {
                    DB::rollBack();

                    return [
                        'success' => false,
                        'message' => 'Invoice cannot be generated: BA Files are required (BA Files Supported is ON) but some jobs do not have verified BA files.',
                    ];
                }
            }

            // Check if invoice already exists for this period
            $existingInvoice = $this->checkExistingInvoice($contractId, $contract->contract_number, $rentalPeriod, $periodStart, $periodEnd);
            if ($existingInvoice) {
                DB::rollBack();

                return [
                    'success' => false,
                    'message' => 'Invoice already exists for this rental period',
                    'existing_invoice' => $existingInvoice
                ];
            }

            // Generate invoice
            $invoice = $this->createInvoiceForRentalPeriod($contract, $rentalPeriod, $periodStart, $periodEnd);

            // Create invoice details based on completed jobs
            $completedJobs = $this->createInvoiceDetailsFromJobs($invoice, $contract, $periodStart, $periodEnd);

            // Attach approved BA files as supporting documents
            $this->attachApprovedBaFiles($invoice, $completedJobs);

            // Attach verified contract files as supporting documents
            $this->attachVerifiedContractFiles($invoice, $contract);

            // Update invoice totals
            $this->updateInvoiceTotals($invoice);

            DB::commit();

            Log::info("Auto-generated invoice for contract {$contract->contract_number} rental period {$rentalPeriod}: {$invoice->invoice_number}");

            return [
                'success' => true,
                'invoice' => $invoice,
                'message' => 'Invoice auto-generated successfully for rental period',
                'rental_period' => $rentalPeriod,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString()
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Auto invoice generation failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to auto-generate invoice: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if invoice trigger jobs in the rental period are completed.
     */
    private function checkInvoiceTriggerJobsCompletedInPeriod(Contract $contract, Carbon $periodStart, Carbon $periodEnd): bool
    {
        $totalJobs = $this->getInvoiceTriggerJobsInPeriod($contract, $periodStart, $periodEnd)->count();
        $completedJobs = $this->getCompletedInvoiceTriggerJobsInPeriod($contract, $periodStart, $periodEnd)->count();

        return $totalJobs > 0 && $totalJobs === $completedJobs;
    }

    /**
     * Get count of completed jobs in period
     */
    private function getCompletedJobsCount(Contract $contract, Carbon $periodStart, Carbon $periodEnd): int
    {
        return $this->getCompletedInvoiceTriggerJobsInPeriod($contract, $periodStart, $periodEnd)->count();
    }

    /**
     * Get total jobs count in period
     */
    private function getTotalJobsCount(Contract $contract, Carbon $periodStart, Carbon $periodEnd): int
    {
        return $this->getInvoiceTriggerJobsInPeriod($contract, $periodStart, $periodEnd)->count();
    }

    /**
     * Check if all completed jobs in period have verified BA files
     */
    private function checkAllJobsHaveVerifiedBaFiles(Contract $contract, Carbon $periodStart, Carbon $periodEnd): bool
    {
        $completedJobs = $this->getCompletedInvoiceTriggerJobsInPeriod($contract, $periodStart, $periodEnd);

        foreach ($completedJobs as $job) {
            // Check if job has at least one verified BA file
            $hasVerifiedBa = JobScheduleBaFile::where('job_schedule_id', $job->id)
                ->where('verification_status', 'verified')
                ->exists();
            
            if (!$hasVerifiedBa) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if invoice already exists for this period
     */
    private function checkExistingInvoice(int $contractId, string $contractNumber, string $rentalPeriod, Carbon $periodStart, Carbon $periodEnd): ?Invoice
    {
        return Invoice::where(function ($query) use ($contractId, $contractNumber) {
                $query->where('contract_id', $contractId)
                    ->orWhere('contract_number', $contractNumber);
            })
            ->where('invoice_status', '!=', Invoice::STATUS_CANCELLED)
            ->where('period_invoice', $rentalPeriod)
            ->first();
    }

    private function getInvoiceTiming(Contract $contract): string
    {
        $paymentMethod = strtolower(trim((string) (
            $contract->quotation?->payment_method
            ?? $contract->quotation?->billing_methods
            ?? ''
        )));

        if (str_contains($paymentMethod, 'before')) {
            return 'before_service';
        }

        if (str_contains($paymentMethod, 'after')) {
            return 'after_service';
        }

        return 'legacy_all_jobs';
    }

    private function getInvoiceTriggerJobTypes(Contract $contract): ?array
    {
        return match ($this->getInvoiceTiming($contract)) {
            'before_service' => ['install', 'installation', 'installation_report'],
            'after_service' => ['service', 'service_first', 'service_routine', 'csr', 'customer_service_report'],
            default => null,
        };
    }

    private function getCandidateInvoiceJobsQuery(Contract $contract, Carbon $periodStart, Carbon $periodEnd)
    {
        return JobSchedule::with([
                'jobAdvice.rooms.rentalProduct',
                'jobAdvice.rooms.contractRoom.room',
                'jobAdvice.contract.contractRentals.masterRental',
                'jobAdvice.contract.contractRooms.room',
                'jobScheduleRooms.jobAdviceRoom.rentalProduct',
                'jobScheduleRooms.jobAdviceRoom.contractRoom.room',
                'jobScheduleRooms.jobAdviceRoom.quotationRoom.room',
                'jobScheduleRooms.room',
            ])
            ->whereHas('jobAdvice', function ($query) use ($contract) {
                $query->where('contract_id', $contract->id);
            })
            ->whereBetween('schedule_date', [$periodStart, $periodEnd])
            ->whereNotIn('status', self::NON_BILLABLE_JOB_STATUSES)
            ->whereRaw("LOWER(COALESCE(type, '')) NOT IN (?, ?, ?, ?)", self::NON_BILLABLE_JOB_TYPES)
            ->orderBy('schedule_date')
            ->orderBy('id');
    }

    private function getInvoiceTriggerJobsInPeriod(Contract $contract, Carbon $periodStart, Carbon $periodEnd): \Illuminate\Support\Collection
    {
        return $this->getCandidateInvoiceJobsQuery($contract, $periodStart, $periodEnd)
            ->get()
            ->filter(fn (JobSchedule $jobSchedule) => $this->jobMatchesRentalInvoiceTrigger($contract, $jobSchedule))
            ->values();
    }

    private function getCompletedInvoiceTriggerJobsInPeriod(Contract $contract, Carbon $periodStart, Carbon $periodEnd): \Illuminate\Support\Collection
    {
        return $this->getInvoiceTriggerJobsInPeriod($contract, $periodStart, $periodEnd)
            ->filter(function (JobSchedule $jobSchedule) {
                return in_array($jobSchedule->status, self::BILLABLE_COMPLETED_STATUSES, true)
                    && !empty($jobSchedule->ba_date);
            })
            ->values();
    }

    private function jobMatchesRentalInvoiceTrigger(Contract $contract, JobSchedule $jobSchedule): bool
    {
        $jobType = $this->normalizeJobType($jobSchedule->type);
        $rentalTypes = $this->getRentalTypesForJob($contract, $jobSchedule);

        if ($rentalTypes->isEmpty()) {
            $legacyTriggerTypes = $this->getInvoiceTriggerJobTypes($contract);
            return $legacyTriggerTypes === null || in_array($jobType, $legacyTriggerTypes, true);
        }

        return $rentalTypes->contains(fn (string $rentalType) => $this->jobTypeCanInvoiceRentalType($jobType, $rentalType, $contract));
    }

    private function getRentalTypesForJob(Contract $contract, JobSchedule $jobSchedule): \Illuminate\Support\Collection
    {
        $jobAdviceRooms = $jobSchedule->jobAdvice?->rooms ?? collect();

        $matchedJobAdviceRooms = $jobAdviceRooms->filter(function ($jobAdviceRoom) use ($jobSchedule) {
            $jobScheduleId = (int) $jobSchedule->id;

            return in_array($jobScheduleId, [
                (int) $jobAdviceRoom->install_job_schedule_id,
                (int) $jobAdviceRoom->service_job_schedule_id,
                (int) $jobAdviceRoom->remove_job_schedule_id,
            ], true);
        });

        $rentalTypes = $matchedJobAdviceRooms
            ->map(fn ($jobAdviceRoom) => $this->normalizeRentalType($jobAdviceRoom->rentalProduct ?? $jobAdviceRoom->contractRoom?->rental_product))
            ->filter();

        if ($rentalTypes->isNotEmpty()) {
            return $rentalTypes->unique()->values();
        }

        $scheduleRoomRentalTypes = $this->getJobAdviceRoomsFromScheduleRooms($jobSchedule)
            ->map(fn ($jobAdviceRoom) => $this->normalizeRentalType($jobAdviceRoom->rentalProduct ?? $jobAdviceRoom->contractRoom?->rental_product))
            ->filter();

        if ($scheduleRoomRentalTypes->isNotEmpty()) {
            return $scheduleRoomRentalTypes->unique()->values();
        }

        if ($jobSchedule->room_id) {
            return $contract->contractRooms
                ->where('room_id', $jobSchedule->room_id)
                ->map(fn ($contractRoom) => $this->normalizeRentalType($contractRoom->rental_product))
                ->filter()
                ->unique()
                ->values();
        }

        return $contract->contractRentals
            ->map(fn ($contractRental) => $this->normalizeRentalType($contractRental->masterRental))
            ->filter()
            ->unique()
            ->values();
    }

    private function jobTypeCanInvoiceRentalType(string $jobType, string $rentalType, Contract $contract): bool
    {
        $installTypes = ['install', 'installation', 'installation_report', 'ir'];
        $serviceTypes = ['service', 'service_first', 'service_routine', 'servis', 'csr', 'customer_service_report', 'customer service report'];

        if ($rentalType === 'unit_only') {
            return in_array($jobType, $installTypes, true);
        }

        if (in_array($rentalType, ['unit_refill', 'refill_only'], true)) {
            return in_array($jobType, $serviceTypes, true);
        }

        $legacyTriggerTypes = $this->getInvoiceTriggerJobTypes($contract);

        return $legacyTriggerTypes === null || in_array($jobType, $legacyTriggerTypes, true);
    }

    private function normalizeJobType(?string $type): string
    {
        return strtolower(trim(str_replace('-', '_', (string) $type)));
    }

    private function normalizeRentalType($rental): string
    {
        if (!$rental) {
            return '';
        }

        if (is_string($rental)) {
            return strtolower(trim(str_replace('-', '_', $rental)));
        }

        return strtolower(trim(str_replace('-', '_', (string) ($rental->rental_type ?? ''))));
    }

    /**
     * Create invoice for rental period
     */
    private function createInvoiceForRentalPeriod(Contract $contract, string $rentalPeriod, Carbon $periodStart, Carbon $periodEnd): Invoice
    {
        $invoiceNumber = $this->documentNumberService->generate(
            'invoice',
            null, // branchCode will be auto-detected
            null, // buildingId
            $contract->id // Context for branch detection
        );
        
        // Rule 46: TOP pertama manual (dari contract), selanjutnya mengikuti TOP terakhir
        $previousInvoice = Invoice::where('contract_number', $contract->contract_number)
            ->orderBy('id', 'desc')
            ->first();

        // Determine Payment Terms (TOP in days)
        $rawTerms = $contract->term_of_payment ?? $contract->payment_terms;
        $paymentTerms = 0; 
        
        if (is_numeric($rawTerms)) {
            $paymentTerms = (int) $rawTerms;
        } else {
            // Use accessor from Contract model which handles "X bulan 1x" etc.
            // and convert to days (X * 30)
            $intervalMonths = $contract->top_interval_months ?? 1;
            $paymentTerms = $intervalMonths * 30;

            // Optional: Special case for cash/tunai to keep it 0 days
            $termLower = strtolower(trim($rawTerms ?? ''));
            if (strpos($termLower, 'cash') !== false || strpos($termLower, 'tunai') !== false || strpos($termLower, 'transfer') !== false) {
                $paymentTerms = 0;
            }
        }

        if ($previousInvoice) {
            // Smart TOP: follow last invoice's actual behavior
            $paymentTerms = $previousInvoice->due_date->diffInDays($previousInvoice->invoice_date);
        }


        // Pull ba_date from the job that triggers invoice generation for this contract timing.
        $firstJob = $this->getCompletedInvoiceTriggerJobsInPeriod($contract, $periodStart, $periodEnd)
            ->sortBy([
                ['schedule_date', 'asc'],
                ['id', 'asc'],
            ])
            ->first();

        // Determine Invoice Date
        $invoiceDate = $periodEnd; // Default: End of period
        
        if ($firstJob && $firstJob->ba_date) {
            $invoiceDate = Carbon::parse($firstJob->ba_date);
        }

        // Smart Invoice Date Preference (Overrides above if set and not manual)
        if ($contract->invoice_date_preference && $contract->invoice_date_preference !== 'manual') {
            $smartDate = $contract->calculateInvoiceDate($contract->invoice_date_preference, null, $periodEnd);
            if ($smartDate) {
                $invoiceDate = $smartDate;
            }
        }

        // Recalculate Due Date based on final Invoice Date
        $dueDate = $this->calculateDueDate($invoiceDate, $paymentTerms);

        // Helper to get Billing Group
        $billingGroup = $contract->billingGroup;

        $invoice = Invoice::create([
            'invoice_number' => $invoiceNumber,
            'contract_number' => $contract->contract_number,
            'contract_id' => $contract->id,
            'po_number' => $contract->po_number,
            'customer_id' => $contract->customer_id,
            'billing_address' => $billingGroup->pic_address ?? $contract->customer->address ?? '',
            'period_invoice' => $rentalPeriod,
            'invoice_status' => Invoice::STATUS_DRAFT,
            'status' => Invoice::STATUS_DRAFT,
            'invoice_date' => $invoiceDate->toDateString(),
            'due_date' => $dueDate->toDateString(),
            'ba_date' => $firstJob ? $firstJob->ba_date : null,
            'tax_obligation' => $contract->customer->tax_obligation ?? false,
            'tax_code' => $contract->ppn_code,
            'npwp_number' => $billingGroup->npwp_number ?? null,
            'tax_number' => $billingGroup->tax_number ?? null,
            'tax_address' => $billingGroup->npwp_address ?? null,
            'kirim' => $billingGroup->invoice_type ?? 'manual',
            'gedung' => $this->getBuildingInfo($contract),
            'alamat_1' => $contract->customer->address ?? '',
            'alamat_2' => $contract->customer->city ?? '',
            'catatan_internal' => "Auto-generated for rental period: {$rentalPeriod}. " . ($contract->internal_notes ?? ''),
            'catatan_customer' => "Invoice for services completed in period {$periodStart->format('d/m/Y')} - {$periodEnd->format('d/m/Y')}",
            'pic_finance' => $billingGroup->pic_name ?? $contract->billingGroup->pic_name ?? '',
            'email' => $billingGroup->pic_email ?? $contract->billingGroup->pic_email ?? $contract->customer->email ?? '',
            'billing_group_id' => $billingGroup->id ?? $contract->billing_group_id ?? null,
            'umur_invoice' => 0,
            'outstanding' => 0,
            'total_amount' => 0,
            'grand_total' => 0,
            'subtotal' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_paid' => 0,
            'notes' => "Auto-generated invoice for rental period {$rentalPeriod}",
            'terms_conditions' => $contract->terms_conditions ?? '',
            'created_by' => auth()->id()
        ]);

        $invoice->logActivity(
            'created',
            'Invoice auto-generated for rental period ' . $rentalPeriod,
            auth()->id()
        );

        return $invoice;
    }

    /**
     * Create invoice details from completed jobs
     */
    private function createInvoiceDetailsFromJobs(Invoice $invoice, Contract $contract, Carbon $periodStart, Carbon $periodEnd, array $billedRentals = []): \Illuminate\Database\Eloquent\Collection
    {
        $completedJobs = $this->getCompletedInvoiceTriggerJobsInPeriod($contract, $periodStart, $periodEnd);

        foreach ($completedJobs as $jobSchedule) {
            // Get rental details for this job
            $rentalDetails = $this->getRentalDetailsForJob($jobSchedule);

            foreach ($rentalDetails as $rental) {
                // Create a unique key for this rental: room + rental product
                $billingKey = $this->invoiceRentalBillingKey($rental);

                if (!in_array($billingKey, $billedRentals)) {
                    $this->createInvoiceDetail($invoice, $jobSchedule, $rental);
                    $billedRentals[] = $billingKey;
                } else {
                    Log::debug("Skipping duplicate billing for rental unit in invoice {$invoice->invoice_number}", [
                        'job_no' => $jobSchedule->job_number,
                        'room' => $rental['room_name'],
                        'rental' => $rental['rental_name']
                    ]);
                }
            }
        }

        return $completedJobs;
    }

    /**
     * Build the rental rows that should exist on an already generated monthly invoice.
     * This is intentionally read-only so repair commands can preview before applying.
     */
    public function expectedRentalDetailsForInvoice(Invoice $invoice): array
    {
        $contract = $invoice->contract()
            ->with([
                'contractRentals.masterRental',
                'contractRooms.room',
                'customer',
                'quotation',
            ])
            ->first();

        if (! $contract || ! $invoice->invoice_date) {
            return [];
        }

        $periodStart = Carbon::parse($invoice->invoice_date)->startOfMonth();
        $periodEnd = Carbon::parse($invoice->invoice_date)->endOfMonth();
        $completedJobs = $this->getCompletedInvoiceTriggerJobsInPeriod($contract, $periodStart, $periodEnd);
        $expected = [];
        $billedRentals = [];

        foreach ($completedJobs as $jobSchedule) {
            foreach ($this->getRentalDetailsForJob($jobSchedule) as $rental) {
                $billingKey = $this->invoiceRentalBillingKey($rental);

                if (isset($billedRentals[$billingKey])) {
                    continue;
                }

                $expected[] = [
                    'master_rental_id' => $rental['master_rental_id'],
                    'job_no' => $jobSchedule->job_number,
                    'building_name' => $jobSchedule->building?->building_name ?? $jobSchedule->building_name ?? '',
                    'room_name' => $rental['room_name'] ?: ($jobSchedule->room?->room_name ?? $jobSchedule->room_name ?? ''),
                    'rental_name' => $rental['rental_name'] ?? 'Service',
                    'quantity' => $rental['quantity'],
                    'qty_free' => $rental['qty_free'] ?? 0,
                    'unit_price' => $rental['unit_price'],
                    'total_price' => $rental['total_price'],
                ];
                $billedRentals[$billingKey] = true;
            }
        }

        return $expected;
    }

    public function refreshInvoiceTotals(Invoice $invoice): void
    {
        $this->updateInvoiceTotals($invoice);
    }

    /**
     * Attach approved BA files from jobs to the invoice
     */
    private function attachApprovedBaFiles(Invoice $invoice, $completedJobs): void
    {
        foreach ($completedJobs as $jobSchedule) {
            $baFiles = JobScheduleBaFile::where('job_schedule_id', $jobSchedule->id)
                ->where('verification_status', 'verified')
                ->get();

            foreach ($baFiles as $baFile) {
                // Ensure no double attachment
                $exists = InvoiceFile::where('invoice_id', $invoice->id)
                    ->where('file_path', str_replace('uploads/', '', $baFile->file_path))
                    ->exists();

                if (!$exists) {
                    InvoiceFile::create([
                        'invoice_id' => $invoice->id,
                        'file_type' => 'attachment',
                        'file_name' => 'BA File - ' . ($baFile->room_name ?: 'Unknown Room') . ' - ' . $baFile->file_name,
                        'file_path' => str_replace('uploads/', '', $baFile->file_path),
                        'description' => "BA File dari Job #{$jobSchedule->job_number} Ruangan {$baFile->room_name}",
                        'created_by' => auth()->id()
                    ]);
                }
            }
        }
    }


    /**
     * Get rental details for a specific job
     */
    private function getRentalDetailsForJob(JobSchedule $jobSchedule): array
    {
        $rentalDetails = [];
        
        // Get contract rentals related to this job
        $contract = $jobSchedule->jobAdvice->contract ?? null;
        if (!$contract) return [];

        $detailsFromScheduleRooms = $this->getRentalDetailsFromJobScheduleRooms($jobSchedule, $contract);
        if ($detailsFromScheduleRooms !== null) {
            return $detailsFromScheduleRooms;
        }

        $matchedJobAdviceRooms = $this->getMatchedJobAdviceRoomsForJob($jobSchedule);
        if ($matchedJobAdviceRooms->isNotEmpty()) {
            foreach ($matchedJobAdviceRooms as $jobAdviceRoom) {
                $detail = $this->buildRentalDetailFromJobAdviceRoom($jobSchedule, $contract, $jobAdviceRoom);
                if ($detail) {
                    $rentalDetails[] = $detail;
                }
            }

            return $rentalDetails;
        }

        // Determine which rooms to bill for this job
        // If job has specific room, only use that. Otherwise use all rooms in contract.
        $contractRooms = $jobSchedule->room_id 
            ? $contract->contractRooms()->where('room_id', $jobSchedule->room_id)->with(['room'])->get()
            : $contract->contractRooms()->with(['room'])->get();
        
        foreach ($contractRooms as $contractRoom) {
            if ($this->isContractRoomNonBillableForJob($jobSchedule, $contractRoom)) {
                Log::debug("Skipping non-billable suspended/cancelled room for invoice rental detail", [
                    'job_no' => $jobSchedule->job_number,
                    'contract_room_id' => $contractRoom->id,
                    'room_id' => $contractRoom->room_id,
                ]);
                continue;
            }

            // Use accessor from ContractRoom model to get associated rental unit
            $detail = $this->buildRentalDetailFromContractRoom($jobSchedule, $contract, $contractRoom);
            if ($detail) {
                $rentalDetails[] = $detail;
            }
        }

        return $rentalDetails;
    }

    private function getRentalDetailsFromJobScheduleRooms(JobSchedule $jobSchedule, Contract $contract): ?array
    {
        $jobSchedule->loadMissing([
            'jobScheduleRooms.jobAdviceRoom.rentalProduct',
            'jobScheduleRooms.jobAdviceRoom.contractRoom.room',
            'jobScheduleRooms.jobAdviceRoom.quotationRoom.room',
            'jobScheduleRooms.room',
        ]);

        $scheduleRooms = $jobSchedule->jobScheduleRooms ?? collect();
        if ($scheduleRooms->isEmpty()) {
            return null;
        }

        $rentalDetails = [];

        foreach ($scheduleRooms as $scheduleRoom) {
            if ($this->isScheduleRoomNonBillable($scheduleRoom)) {
                Log::debug("Skipping non-billable suspended/cancelled schedule room for invoice rental detail", [
                    'job_no' => $jobSchedule->job_number,
                    'job_schedule_room_id' => $scheduleRoom->id,
                    'room_id' => $scheduleRoom->room_id,
                ]);
                continue;
            }

            $adviceRooms = $this->getJobAdviceRoomsForScheduleRoom($scheduleRoom);
            if ($adviceRooms->isNotEmpty()) {
                foreach ($adviceRooms as $jobAdviceRoom) {
                    $detail = $this->buildRentalDetailFromJobAdviceRoom($jobSchedule, $contract, $jobAdviceRoom, $scheduleRoom);
                    if ($detail) {
                        $rentalDetails[] = $detail;
                    }
                }

                continue;
            }

            foreach ($this->getContractRoomsForScheduleRoom($contract, $scheduleRoom) as $contractRoom) {
                $detail = $this->buildRentalDetailFromContractRoom($jobSchedule, $contract, $contractRoom, $scheduleRoom);
                if ($detail) {
                    $rentalDetails[] = $detail;
                }
            }
        }

        return $rentalDetails;
    }

    private function getJobAdviceRoomsFromScheduleRooms(JobSchedule $jobSchedule): \Illuminate\Support\Collection
    {
        $jobSchedule->loadMissing([
            'jobScheduleRooms.jobAdviceRoom.rentalProduct',
            'jobScheduleRooms.jobAdviceRoom.contractRoom.room',
            'jobScheduleRooms.jobAdviceRoom.quotationRoom.room',
        ]);

        return ($jobSchedule->jobScheduleRooms ?? collect())
            ->flatMap(fn (JobScheduleRoom $scheduleRoom) => $this->getJobAdviceRoomsForScheduleRoom($scheduleRoom))
            ->unique('id')
            ->values();
    }

    private function getJobAdviceRoomsForScheduleRoom(JobScheduleRoom $scheduleRoom): \Illuminate\Support\Collection
    {
        $adviceRooms = collect();

        if ($scheduleRoom->jobAdviceRoom) {
            $adviceRooms->push($scheduleRoom->jobAdviceRoom);
        }

        if (Schema::hasTable('job_schedule_room_rentals')) {
            $scheduleRoom->loadMissing([
                'rentals.jobAdviceRoom.rentalProduct',
                'rentals.jobAdviceRoom.contractRoom.room',
                'rentals.jobAdviceRoom.quotationRoom.room',
            ]);

            $adviceRooms = $adviceRooms->merge(
                $scheduleRoom->rentals
                    ->pluck('jobAdviceRoom')
                    ->filter()
            );
        }

        return $adviceRooms
            ->filter()
            ->unique('id')
            ->values();
    }

    private function getContractRoomsForScheduleRoom(Contract $contract, JobScheduleRoom $scheduleRoom): \Illuminate\Support\Collection
    {
        if ($scheduleRoom->room_id) {
            return $contract->contractRooms()
                ->where('room_id', $scheduleRoom->room_id)
                ->with('room')
                ->get();
        }

        $roomName = strtolower(trim((string) $scheduleRoom->room_name));
        if ($roomName === '') {
            return collect();
        }

        return $contract->contractRooms()
            ->with('room')
            ->get()
            ->filter(fn ($contractRoom) => strtolower(trim((string) $contractRoom->room?->room_name)) === $roomName)
            ->values();
    }

    private function buildRentalDetailFromJobAdviceRoom(JobSchedule $jobSchedule, Contract $contract, $jobAdviceRoom, ?JobScheduleRoom $scheduleRoom = null): ?array
    {
        if ($this->isJobAdviceRoomNonBillableForJob($jobSchedule, $jobAdviceRoom)) {
            Log::debug("Skipping non-billable suspended/cancelled JA room for invoice rental detail", [
                'job_no' => $jobSchedule->job_number,
                'job_advice_room_id' => $jobAdviceRoom->id,
                'contract_room_id' => $jobAdviceRoom->contract_room_id,
            ]);
            return null;
        }

        $masterRental = $jobAdviceRoom->rentalProduct ?? $jobAdviceRoom->contractRoom?->rental_product;
        if (!$masterRental) {
            return null;
        }

        if (!$this->jobTypeCanInvoiceRentalType(
            $this->normalizeJobType($jobSchedule->type),
            $this->normalizeRentalType($masterRental),
            $contract
        )) {
            Log::debug("Skipping JA room rental detail because job type is not the invoice trigger for this rental type", [
                'job_no' => $jobSchedule->job_number,
                'job_type' => $jobSchedule->type,
                'job_advice_room_id' => $jobAdviceRoom->id,
                'rental' => $masterRental->rental_name ?? null,
                'rental_type' => $masterRental->rental_type ?? null,
            ]);
            return null;
        }

        $roomId = $scheduleRoom?->room_id
            ?? $jobAdviceRoom->contractRoom?->room_id
            ?? $jobAdviceRoom->quotationRoom?->room_id
            ?? null;

        $contractRental = $this->findContractRentalForJobAdviceRoom($contract, $jobAdviceRoom, $masterRental->id, $roomId);
        if (!$contractRental) {
            return null;
        }

        return [
            'contract_rental_id' => $contractRental->id,
            'contract_room_id' => $jobAdviceRoom->contract_room_id,
            'room_id' => $roomId,
            'master_rental_id' => $masterRental->id,
            'rental_name' => $masterRental->rental_name ?? $jobAdviceRoom->rental_name ?? 'Service',
            'room_name' => $scheduleRoom?->room_name
                ?? $jobAdviceRoom->contractRoom?->room?->room_name
                ?? $jobAdviceRoom->quotationRoom?->room?->room_name
                ?? $jobAdviceRoom->room_name
                ?? '',
            'quantity' => $contractRental->quantity ?? 1,
            'qty_free' => $contractRental->qty_free ?? 0,
            'unit_price' => $contractRental->unit_price ?? 0,
            'total_price' => $contractRental->total_price ?? (($contractRental->quantity ?? 1) * ($contractRental->unit_price ?? 0)),
        ];
    }

    private function buildRentalDetailFromContractRoom(JobSchedule $jobSchedule, Contract $contract, $contractRoom, ?JobScheduleRoom $scheduleRoom = null): ?array
    {
        $masterRental = $contractRoom->rental_product;
        if (!$masterRental) {
            return null;
        }

        if (!$this->jobTypeCanInvoiceRentalType(
            $this->normalizeJobType($jobSchedule->type),
            $this->normalizeRentalType($masterRental),
            $contract
        )) {
            Log::debug("Skipping rental detail because job type is not the invoice trigger for this rental type", [
                'job_no' => $jobSchedule->job_number,
                'job_type' => $jobSchedule->type,
                'contract_room_id' => $contractRoom->id,
                'room_id' => $contractRoom->room_id,
                'rental' => $masterRental->rental_name ?? null,
                'rental_type' => $masterRental->rental_type ?? null,
            ]);
            return null;
        }

        $contractRental = $this->findContractRentalForRoomAndRental($contract, $masterRental->id, $contractRoom->room_id);
        if (!$contractRental) {
            return null;
        }

        return [
            'contract_rental_id' => $contractRental->id,
            'contract_room_id' => $contractRoom->id,
            'room_id' => $contractRoom->room_id,
            'master_rental_id' => $masterRental->id,
            'rental_name' => $masterRental->rental_name ?? 'Service',
            'room_name' => $scheduleRoom?->room_name ?? $contractRoom->room->room_name ?? '',
            'quantity' => $contractRental->quantity ?? 1,
            'qty_free' => $contractRental->qty_free ?? 0,
            'unit_price' => $contractRental->unit_price ?? 0,
            'total_price' => $contractRental->total_price ?? (($contractRental->quantity ?? 1) * ($contractRental->unit_price ?? 0)),
        ];
    }

    private function getMatchedJobAdviceRoomsForJob(JobSchedule $jobSchedule): \Illuminate\Support\Collection
    {
        $jobAdviceRooms = $jobSchedule->jobAdvice?->rooms ?? collect();
        $jobScheduleId = (int) $jobSchedule->id;

        return $jobAdviceRooms
            ->filter(function ($jobAdviceRoom) use ($jobScheduleId) {
                return in_array($jobScheduleId, [
                    (int) $jobAdviceRoom->install_job_schedule_id,
                    (int) $jobAdviceRoom->service_job_schedule_id,
                    (int) $jobAdviceRoom->remove_job_schedule_id,
                ], true);
            })
            ->values();
    }

    private function invoiceRentalBillingKey(array $rental): string
    {
        // Keyed on room_name + master_rental_id (not contract_rental_id/room_id) because
        // that is all invoice_rental_details persists — a key built any other way here
        // would not match what refreshDraftInvoiceRentalDetails() derives from saved rows,
        // breaking dedup when backfilling a room into an already-drafted invoice.
        return 'room-name:'.strtolower(trim((string) ($rental['room_name'] ?? ''))).'|rental:'.($rental['master_rental_id'] ?? '');
    }

    private function findContractRentalForJobAdviceRoom(Contract $contract, $jobAdviceRoom, int $masterRentalId, ?int $roomId)
    {
        if (!empty($jobAdviceRoom->contract_rental_id)) {
            $contractRental = $contract->contractRentals()
                ->whereKey($jobAdviceRoom->contract_rental_id)
                ->first();

            if ($contractRental) {
                return $contractRental;
            }
        }

        return $this->findContractRentalForRoomAndRental($contract, $masterRentalId, $roomId);
    }

    private function findContractRentalForRoomAndRental(Contract $contract, int $masterRentalId, ?int $roomId)
    {
        return $contract->contractRentals()
            ->where('master_rental_id', $masterRentalId)
            ->where(function ($query) use ($roomId) {
                if ($roomId) {
                    $query->where('room_id', $roomId);
                }

                $query->orWhereNull('room_id');
            })
            ->orderByRaw($roomId ? 'CASE WHEN room_id = ? THEN 0 ELSE 1 END' : 'CASE WHEN room_id IS NULL THEN 0 ELSE 1 END', $roomId ? [$roomId] : [])
            ->first();
    }

    private function isJobAdviceRoomNonBillableForJob(JobSchedule $jobSchedule, $jobAdviceRoom): bool
    {
        if (in_array(strtolower((string) $jobAdviceRoom->status), self::NON_BILLABLE_ROOM_STATUSES, true)) {
            return true;
        }

        $roomQuery = JobScheduleRoom::query()
            ->where('job_schedule_id', $jobSchedule->id)
            ->where(function ($query) use ($jobAdviceRoom) {
                $query->orWhere('job_advice_room_id', $jobAdviceRoom->id);

                $roomId = $jobAdviceRoom->contractRoom?->room_id ?? $jobAdviceRoom->quotationRoom?->room_id ?? null;
                if ($roomId) {
                    $query->orWhere('room_id', $roomId);
                }

                $roomName = $jobAdviceRoom->contractRoom?->room?->room_name
                    ?? $jobAdviceRoom->quotationRoom?->room?->room_name
                    ?? $jobAdviceRoom->room_name
                    ?? null;
                if ($roomName) {
                    $query->orWhere('room_name', $roomName);
                }
            });

        return $roomQuery
            ->get()
            ->contains(function (JobScheduleRoom $room) {
                return $this->isScheduleRoomNonBillable($room);
            });
    }

    private function isScheduleRoomNonBillable(JobScheduleRoom $room): bool
    {
        $status = strtolower((string) $room->status);
        $notes = strtolower((string) $room->notes);

        return in_array($status, self::NON_BILLABLE_ROOM_STATUSES, true)
            || str_contains($notes, '[suspend]');
    }

    private function isContractRoomNonBillableForJob(JobSchedule $jobSchedule, $contractRoom): bool
    {
        $matchingJobAdviceRooms = $jobSchedule->jobAdvice?->rooms
            ?->filter(function ($jobAdviceRoom) use ($jobSchedule, $contractRoom) {
                if ((int) $jobAdviceRoom->contract_room_id !== (int) $contractRoom->id) {
                    return false;
                }

                return in_array((int) $jobSchedule->id, [
                    (int) $jobAdviceRoom->install_job_schedule_id,
                    (int) $jobAdviceRoom->service_job_schedule_id,
                    (int) $jobAdviceRoom->remove_job_schedule_id,
                ], true);
            }) ?? collect();

        if ($matchingJobAdviceRooms->contains(function ($jobAdviceRoom) {
            return in_array(strtolower((string) $jobAdviceRoom->status), self::NON_BILLABLE_ROOM_STATUSES, true);
        })) {
            return true;
        }

        $matchingJobAdviceRoomIds = $matchingJobAdviceRooms->pluck('id')->filter()->values();

        $roomQuery = JobScheduleRoom::query()
            ->where('job_schedule_id', $jobSchedule->id)
            ->where(function ($query) use ($contractRoom, $matchingJobAdviceRoomIds) {
                if ($contractRoom->room_id) {
                    $query->orWhere('room_id', $contractRoom->room_id);
                }

                if ($matchingJobAdviceRoomIds->isNotEmpty()) {
                    $query->orWhereIn('job_advice_room_id', $matchingJobAdviceRoomIds->all());
                }

                $roomName = $contractRoom->room?->room_name;
                if ($roomName) {
                    $query->orWhere('room_name', $roomName);
                }
            });

        return $roomQuery
            ->get()
            ->contains(function (JobScheduleRoom $room) {
                $status = strtolower((string) $room->status);
                $notes = strtolower((string) $room->notes);

                return in_array($status, self::NON_BILLABLE_ROOM_STATUSES, true)
                    || str_contains($notes, '[suspend]');
            });
    }

    /**
     * Create invoice detail for a job and rental
     */
    private function createInvoiceDetail(Invoice $invoice, JobSchedule $jobSchedule, array $rental): void
    {
        // NEW: We only create rental-specific detail to avoid duplication in printing.
        // invoiceRentalDetails represents the breakdown.
        // Standard invoiceDetails is for generic items (non-job etc.)

        // Price on the SQ/contract is per TOP installment, not per calendar month —
        // it is billed as-is on each invoice regardless of how many months the TOP
        // period spans (client rule, 1 Jul 2026: "harga di SQ adalah harga per TOP,
        // tidak terpengaruh dengan frekuensi rental").
        $quantity = $rental['quantity'] ?? 1;
        $unitPrice = $rental['unit_price'] ?? 0;
        $totalPrice = $rental['total_price'] ?? ($quantity * $unitPrice);

        $rentalName = $rental['rental_name'] ?? 'Service';

        $payload = [
            'master_rental_id' => $rental['master_rental_id'],
            'job_no' => $jobSchedule->job_number,
            'building_name' => $jobSchedule->building->building_name ?? $jobSchedule->building_name ?? '',
            'room_name' => $rental['room_name'] ?: ($jobSchedule->room->room_name ?? $jobSchedule->room_name ?? ''),
            'rental_name' => $rentalName,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'created_by' => auth()->id()
        ];

        if (\Illuminate\Support\Facades\Schema::hasColumn('invoice_rental_details', 'qty_free')) {
            $payload['qty_free'] = $rental['qty_free'] ?? 0;
        }

        $invoice->invoiceRentalDetails()->create($payload);
    }

    /**
     * Attach verified contract files to the invoice
     */
    private function attachVerifiedContractFiles(Invoice $invoice, Contract $contract): void
    {
        $contractFiles = ContractFile::where('contract_id', $contract->id)
            ->where('verification_status', 'verified')
            ->get();

        foreach ($contractFiles as $contractFile) {
            $cleanPath = str_replace('uploads/', '', $contractFile->file_path);
            
            // Ensure no double attachment
            $exists = InvoiceFile::where('invoice_id', $invoice->id)
                ->where('file_path', $cleanPath)
                ->exists();

            if (!$exists) {
                InvoiceFile::create([
                    'invoice_id' => $invoice->id,
                    'file_type' => 'attachment',
                    'file_name' => 'Contract File - ' . $contractFile->file_name,
                    'file_path' => $cleanPath,
                    'description' => "File Kontrak #{$contract->contract_number}: {$contractFile->file_type}",
                    'created_by' => auth()->id()
                ]);
            }
        }
    }

    /**
     * Update invoice totals
     */
    private function updateInvoiceTotals(Invoice $invoice): void
    {
        $subtotal = $invoice->invoiceDetails()->sum('total_price') + $invoice->invoiceRentalDetails()->sum('total_price');
        $taxRate = $invoice->tax_obligation ? 0.11 : 0; // 11% tax if tax obligation
        $taxAmount = $subtotal * $taxRate;
        $totalAmount = $subtotal + $taxAmount;

        $invoice->forceFill([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'grand_total' => $totalAmount,
            'outstanding' => $totalAmount,
            'total_paid' => 0
        ])->save();
    }

    /**
     * Calculate due date
     */
    private function calculateDueDate(Carbon $invoiceDate, int $paymentTerms): Carbon
    {
        return $invoiceDate->copy()->addDays($paymentTerms);
    }

    /**
     * Get building information for invoice
     */
    private function getBuildingInfo(Contract $contract): string
    {
        $buildings = $contract->contractRooms->pluck('roomRentalUnit.building.building_name')->filter()->unique();
        return $buildings->implode(', ');
    }

    /**
     * Generate invoice for multiple rental periods
     */
    public function generateInvoicesForMultiplePeriods(int $contractId, array $rentalPeriods): array
    {
        $results = [];
        
        foreach ($rentalPeriods as $period) {
            $result = $this->autoGenerateInvoiceForRentalPeriod(
                $contractId,
                $period['rental_period'],
                Carbon::parse($period['period_start']),
                Carbon::parse($period['period_end'])
            );
            
            $results[] = $result;
        }
        
        return [
            'success' => true,
            'results' => $results,
            'total_periods' => count($rentalPeriods),
            'successful_generations' => collect($results)->where('success', true)->count()
        ];
    }

    /**
     * Get rental periods for contract
     */
    public function getRentalPeriodsForContract(int $contractId): array
    {
        $contract = Contract::with('quotation')->findOrFail($contractId);
        $periods = [];
        
        $currentDate = $contract->start_date;
        $endDate = $contract->end_date;
        $periodCounter = 1;
        $periodType = $contract->invoice_period_type ?? 'contract_date';
        $topIntervalMonths = max(1, (int) ($contract->top_interval_months ?? 1));
        
        // Handle "contract_date" & "service" logic (iterate by TOP interval from Start Date)
        // Note: For "service", the period calculation is same as contract_date, 
        // but the invoice DATE logic in createInvoiceForRentalPeriod differs.
        if ($periodType === 'contract_date' || $periodType === 'service') {
            while ($currentDate <= $endDate) {
                $periodEnd = $currentDate->copy()->addMonths($topIntervalMonths)->subDay();
                if ($periodEnd > $endDate) {
                    $periodEnd = $endDate;
                }
                
                $periods[] = [
                    'rental_period' => "Period {$periodCounter}",
                    'period_start' => $currentDate->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                    'status' => $this->getPeriodStatus($contract, $currentDate, $periodEnd)
                ];
                
                $currentDate = $currentDate->copy()->addMonths($topIntervalMonths);
                $periodCounter++;
            }
        } 
        // Handle "monthly" logic (Calendar Month: 1st to End of Month)
        elseif ($periodType === 'monthly') {
            while ($currentDate <= $endDate) {
                // Determine end of this calendar month
                $endOfMonth = $currentDate->copy()->endOfMonth();
                $periodEnd = ($endOfMonth > $endDate) ? $endDate : $endOfMonth;
                
                $periods[] = [
                    'rental_period' => "Period {$periodCounter}",
                    'period_start' => $currentDate->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                    'status' => $this->getPeriodStatus($contract, $currentDate, $periodEnd)
                ];
                
                // Next period starts at the beginning of the next month
                $currentDate = $currentDate->copy()->addMonth()->startOfMonth();
                $periodCounter++;
            }
        }
        
        return $periods;
    }

    /**
     * Get status of rental period
     */
    private function getPeriodStatus(Contract $contract, Carbon $periodStart, Carbon $periodEnd): string
    {
        $totalJobs = $this->getTotalJobsCount($contract, $periodStart, $periodEnd);
        $completedJobs = $this->getCompletedJobsCount($contract, $periodStart, $periodEnd);
        
        if ($totalJobs === 0) {
            return 'no_jobs';
        } elseif ($completedJobs === $totalJobs) {
            return 'completed';
        } elseif ($completedJobs > 0) {
            return 'partial';
        } else {
            return 'pending';
        }
    }
    /**
     * Attempt to auto-generate invoice for a contract if conditions are met
     * (Real-time trigger for Job Completion)
     */
    public function attemptAutoInvoiceForContract(int $contractId): void
    {
        try {
            // Get all rental periods for this contract
            $periods = $this->getRentalPeriodsForContract($contractId);
            
            foreach ($periods as $period) {
                // We only care about periods that are fully COMPLETED
                if ($period['status'] === 'completed') {
                    // Check if invoice already exists to avoid redundant processing
                    $exists = $this->checkExistingInvoice(
                        $contractId,
                        Contract::whereKey($contractId)->value('contract_number') ?? '',
                        $period['rental_period'],
                        Carbon::parse($period['period_start']),
                        Carbon::parse($period['period_end'])
                    );

                    if (!$exists) {
                        Log::info("Real-time Invoice Trigger: Attempting to generate invoice for Contract {$contractId}, Period {$period['rental_period']}");

                        $result = $this->autoGenerateInvoiceForRentalPeriod(
                            $contractId,
                            $period['rental_period'],
                            Carbon::parse($period['period_start']),
                            Carbon::parse($period['period_end'])
                        );

                        if ($result['success']) {
                            Log::info("Real-time Invoice Trigger: [SUCCESS] Generated Invoice {$result['invoice']->invoice_number}");
                        } else {
                            Log::warning("Real-time Invoice Trigger: [FAILED] " . $result['message']);
                        }
                    } elseif ($exists->invoice_status === Invoice::STATUS_DRAFT) {
                        // A room/rental whose job completed after this period's invoice was
                        // first drafted would otherwise never get billed (the existence check
                        // above used to just skip). Backfill any missing rental lines while
                        // the invoice is still a draft.
                        $this->refreshDraftInvoiceRentalDetails(
                            $exists,
                            Carbon::parse($period['period_start']),
                            Carbon::parse($period['period_end'])
                        );
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error("Real-time Invoice Trigger Error: " . $e->getMessage());
        }
    }

    /**
     * Add any rental lines for completed jobs in the period that are missing from an
     * already-drafted invoice (e.g. a second room's job completed after the invoice for
     * this period was first generated from the first room's job).
     */
    private function refreshDraftInvoiceRentalDetails(Invoice $invoice, Carbon $periodStart, Carbon $periodEnd): void
    {
        $contract = $invoice->contract_id ? Contract::find($invoice->contract_id) : null;
        if (!$contract) {
            return;
        }

        $invoice->loadMissing('invoiceRentalDetails');
        $billedRentals = $invoice->invoiceRentalDetails
            ->map(fn ($detail) => $this->invoiceRentalBillingKey([
                'room_name' => $detail->room_name,
                'master_rental_id' => $detail->master_rental_id,
            ]))
            ->all();

        DB::transaction(function () use ($invoice, $contract, $periodStart, $periodEnd, $billedRentals) {
            $existingCount = $invoice->invoiceRentalDetails()->count();

            $this->createInvoiceDetailsFromJobs($invoice, $contract, $periodStart, $periodEnd, $billedRentals);

            $invoice->refresh();
            if ($invoice->invoiceRentalDetails()->count() > $existingCount) {
                $this->updateInvoiceTotals($invoice);
                Log::info("Real-time Invoice Trigger: backfilled missing rental line(s) into existing draft invoice {$invoice->invoice_number}");
            }
        });
    }
}
