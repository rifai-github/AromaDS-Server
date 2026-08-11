<?php

namespace App\Services\Finance;

use App\Models\Building;
use App\Models\Contract;
use App\Models\ContractFile;
use App\Models\Finance\BillingGroup;
use App\Models\Invoice;
use App\Models\InvoiceFile;
use App\Models\JobSchedule;
use App\Models\JobScheduleBaFile;
use App\Models\JobScheduleRoom;
use App\Services\DocumentNumberService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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

            $contract = Contract::with(['customer', 'contractRentals', 'billingGroup', 'billingGroups.buildings', 'contractRooms.room', 'quotation'])
                ->whereKey($contractId)
                ->lockForUpdate()
                ->firstOrFail();

            // NEW: Check if invoice generation is on hold for this contract
            if ($contract->hold_invoice) {
                DB::rollBack();

                return [
                    'success' => false,
                    'message' => 'Invoice generation is on hold for this contract (Hold Invoice is active).',
                    'hold_invoice' => true,
                ];
            }

            // Check if the invoice trigger jobs for this rental period are completed.
            // Before Service: IR/install BA triggers invoice.
            // After Service: CSR/service BA triggers invoice.
            $allJobsCompleted = $this->checkInvoiceTriggerJobsCompletedInPeriod($contract, $periodStart, $periodEnd);

            if (! $allJobsCompleted) {
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
                if (! $this->checkAllJobsHaveVerifiedBaFiles($contract, $periodStart, $periodEnd)) {
                    DB::rollBack();

                    return [
                        'success' => false,
                        'message' => 'Invoice cannot be generated: BA Files are required (BA Files Supported is ON) but some jobs do not have verified BA files.',
                    ];
                }
            }

            // A contract can legitimately have more than one active BillingGroup (each
            // scoped to different buildings/rooms). When that's the case, split into
            // one invoice per billing group instead of a single contract-wide invoice.
            // Zero or exactly one active billing group keeps the original single-invoice
            // behavior below untouched.
            $activeBillingGroups = $contract->billingGroups->where('is_active', true)->values();

            if ($activeBillingGroups->count() > 1) {
                $result = $this->generateInvoicesPerBillingGroup($contract, $activeBillingGroups, $rentalPeriod, $periodStart, $periodEnd);

                DB::commit();

                return $result;
            }

            // Check if invoice already exists for this period
            $existingInvoice = $this->checkExistingInvoice($contractId, $contract->contract_number, $rentalPeriod, $periodStart, $periodEnd);
            if ($existingInvoice) {
                DB::rollBack();

                return [
                    'success' => false,
                    'message' => 'Invoice already exists for this rental period',
                    'existing_invoice' => $existingInvoice,
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
                'period_end' => $periodEnd->toDateString(),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Auto invoice generation failed: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to auto-generate invoice: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check if invoice trigger jobs in the rental period are completed.
     */
    private function checkInvoiceTriggerJobsCompletedInPeriod(Contract $contract, Carbon $periodStart, Carbon $periodEnd): bool
    {
        $totalJobs = $this->getInvoiceTriggerJobsInPeriod($contract, $periodStart, $periodEnd)->count();
        $completedJobs = $this->getReadyInvoiceTriggerJobsInPeriod($contract, $periodStart, $periodEnd)->count();

        return $totalJobs > 0 && $totalJobs === $completedJobs;
    }

    /**
     * Get count of completed jobs in period
     */
    private function getCompletedJobsCount(Contract $contract, Carbon $periodStart, Carbon $periodEnd): int
    {
        return $this->getReadyInvoiceTriggerJobsInPeriod($contract, $periodStart, $periodEnd)->count();
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
        $completedJobs = $this->getReadyInvoiceTriggerJobsInPeriod($contract, $periodStart, $periodEnd);

        foreach ($completedJobs as $job) {
            // Check if job has at least one verified BA file
            $hasVerifiedBa = JobScheduleBaFile::where('job_schedule_id', $job->id)
                ->where('verification_status', 'verified')
                ->exists();

            if (! $hasVerifiedBa) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if invoice already exists for this period.
     *
     * When $scopeByBillingGroup is true, the lookup is additionally scoped to
     * $billingGroupId (including explicitly matching NULL for the "default"/leftover
     * bucket) so that generating one billing group's invoice for a period does not
     * block another billing group's invoice for the same period, and so that
     * per-billing-group generation stays idempotent on re-run. Existing callers that
     * don't pass these two params keep the original contract+period-only behavior.
     */
    private function checkExistingInvoice(int $contractId, string $contractNumber, string $rentalPeriod, Carbon $periodStart, Carbon $periodEnd, ?int $billingGroupId = null, bool $scopeByBillingGroup = false): ?Invoice
    {
        $query = Invoice::where(function ($query) use ($contractId, $contractNumber) {
            $query->where('contract_id', $contractId)
                ->orWhere('contract_number', $contractNumber);
        })
            ->where('invoice_status', '!=', Invoice::STATUS_CANCELLED)
            ->where('period_invoice', $rentalPeriod);

        if ($scopeByBillingGroup) {
            $query->where(function ($query) use ($billingGroupId) {
                if ($billingGroupId === null) {
                    $query->whereNull('billing_group_id');
                } else {
                    $query->where('billing_group_id', $billingGroupId);
                }
            });
        }

        return $query->first();
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
            ->whereNull('catalyst_backfill_at')
            ->whereNotIn('status', self::NON_BILLABLE_JOB_STATUSES)
            ->whereRaw("LOWER(COALESCE(type, '')) NOT IN (?, ?, ?, ?)", self::NON_BILLABLE_JOB_TYPES)
            ->orderBy('schedule_date')
            ->orderBy('id');
    }

    private function getInvoiceTriggerJobsInPeriod(Contract $contract, Carbon $periodStart, Carbon $periodEnd): \Illuminate\Support\Collection
    {
        $candidates = $this->getCandidateInvoiceJobsQuery($contract, $periodStart, $periodEnd)->get();

        // Whether this period contains an actual Install (IR) job. When a free-trial install
        // (IF) continues straight into the contract with the unit still on the wall, no IR job
        // is generated for the first period, so the first CSR/service job must become the
        // Before Service invoice trigger instead (see jobTypeCanInvoiceRentalType()).
        $periodHasInstallTrigger = $candidates->contains(
            fn (JobSchedule $jobSchedule) => in_array(
                $this->normalizeJobType($jobSchedule->type),
                ['install', 'installation', 'installation_report', 'ir'],
                true
            )
        );

        return $candidates
            ->filter(fn (JobSchedule $jobSchedule) => $this->jobMatchesRentalInvoiceTrigger($contract, $jobSchedule, $periodHasInstallTrigger))
            ->values();
    }

    private function getCompletedInvoiceTriggerJobsInPeriod(Contract $contract, Carbon $periodStart, Carbon $periodEnd): \Illuminate\Support\Collection
    {
        return $this->getInvoiceTriggerJobsInPeriod($contract, $periodStart, $periodEnd)
            ->filter(function (JobSchedule $jobSchedule) {
                return in_array($jobSchedule->status, self::BILLABLE_COMPLETED_STATUSES, true)
                    && ! empty($jobSchedule->ba_date);
            })
            ->values();
    }

    /**
     * Jobs that make an invoice period ready and provide its rental-detail scope.
     *
     * A first CSR created by ContractOnWallCsrService is a special Before Service
     * continuation: that service only creates the CSR after finding a completed
     * Install Free/installation source for the same quotation and room. The CSR is
     * therefore intentionally invoice-ready while it is still unstarted.
     */
    private function getReadyInvoiceTriggerJobsInPeriod(Contract $contract, Carbon $periodStart, Carbon $periodEnd): \Illuminate\Support\Collection
    {
        return $this->getInvoiceTriggerJobsInPeriod($contract, $periodStart, $periodEnd)
            ->filter(function (JobSchedule $jobSchedule) use ($contract) {
                $isCompletedWithBa = in_array($jobSchedule->status, self::BILLABLE_COMPLETED_STATUSES, true)
                    && ! empty($jobSchedule->ba_date);

                return $isCompletedWithBa
                    || $this->isPendingBeforeServiceTrialContinuation($contract, $jobSchedule);
            })
            ->values();
    }

    private function isPendingBeforeServiceTrialContinuation(Contract $contract, JobSchedule $jobSchedule): bool
    {
        $contractStatus = strtolower(trim((string) ($contract->contract_status ?? $contract->status ?? '')));
        if ($contractStatus !== '' && $contractStatus !== 'active') {
            return false;
        }

        return $this->isBeforeServiceTrialContinuation($contract, $jobSchedule)
            && in_array(strtolower(trim((string) $jobSchedule->status)), ['scheduled', 'new_job'], true);
    }

    private function isBeforeServiceTrialContinuation(Contract $contract, JobSchedule $jobSchedule): bool
    {
        if ($this->getInvoiceTiming($contract) !== 'before_service') {
            return false;
        }

        if (! in_array($this->normalizeJobType($jobSchedule->type), [
            'service',
            'service_first',
            'service_routine',
            'csr',
            'customer_service_report',
        ], true)) {
            return false;
        }

        if ((int) ($jobSchedule->period ?? 0) !== 1) {
            return false;
        }

        return str_contains(
            strtolower((string) $jobSchedule->internal_notes),
            'auto-generated first csr from contract activation'
        );
    }

    private function jobMatchesRentalInvoiceTrigger(Contract $contract, JobSchedule $jobSchedule, bool $periodHasInstallTrigger = true): bool
    {
        $jobType = $this->normalizeJobType($jobSchedule->type);
        $rentalTypes = $this->getRentalTypesForJob($contract, $jobSchedule);

        if ($rentalTypes->isEmpty()) {
            $legacyTriggerTypes = $this->getInvoiceTriggerJobTypes($contract);

            return $legacyTriggerTypes === null || in_array($jobType, $legacyTriggerTypes, true);
        }

        return $rentalTypes->contains(fn (string $rentalType) => $this->jobTypeCanInvoiceRentalType($jobType, $rentalType, $contract, $jobSchedule, $periodHasInstallTrigger));
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

    private function jobTypeCanInvoiceRentalType(string $jobType, string $rentalType, Contract $contract, ?JobSchedule $jobSchedule = null, bool $periodHasInstallTrigger = true): bool
    {
        $installTypes = ['install', 'installation', 'installation_report', 'ir'];
        $serviceTypes = ['service', 'service_first', 'service_routine', 'servis', 'csr', 'customer_service_report', 'customer service report'];

        $timing = $this->getInvoiceTiming($contract);
        $isUnitOnly = $rentalType === 'unit_only';
        $isUnitRefillBeforeService = $rentalType === 'unit_refill'
            && $timing === 'before_service';

        if ($isUnitOnly || $isUnitRefillBeforeService) {
            if (in_array($jobType, $installTypes, true)) {
                return true;
            }

            if (! in_array($jobType, $serviceTypes, true) || ! $jobSchedule) {
                return false;
            }

            $period = is_numeric($jobSchedule->period) ? (int) $jobSchedule->period : null;

            // Subsequent periods are always billed off their own service/CSR job.
            if ($period !== null && $period > 1) {
                return true;
            }

            // First period is normally billed off the Install (IR) job. But when a free-trial
            // install (IF) continued into the contract and the unit stayed on the wall, no IR
            // job exists in this period — so the first CSR/service job becomes the Before
            // Service trigger. Gated on Before Service + no IR job to avoid double invoicing.
            return $timing === 'before_service' && ! $periodHasInstallTrigger;
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
        if (! $rental) {
            return '';
        }

        if (is_string($rental)) {
            return strtolower(trim(str_replace('-', '_', $rental)));
        }

        return strtolower(trim(str_replace('-', '_', (string) ($rental->rental_type ?? ''))));
    }

    /**
     * Create invoice for rental period.
     *
     * $billingGroupOverride is used by the per-billing-group split
     * (generateInvoicesPerBillingGroup()) to stamp a specific billing group's
     * PIC/tax/address/billing_group_id on the invoice. When omitted, this falls
     * back to $contract->billingGroup (latest), which is the original single-invoice
     * behavior — unchanged for contracts with zero or one billing group.
     */
    private function createInvoiceForRentalPeriod(Contract $contract, string $rentalPeriod, Carbon $periodStart, Carbon $periodEnd, ?BillingGroup $billingGroupOverride = null): Invoice
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
        $firstJob = $this->getReadyInvoiceTriggerJobsInPeriod($contract, $periodStart, $periodEnd)
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
        $billingGroup = $billingGroupOverride ?? $contract->billingGroup;

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
            // Seed only — updateInvoiceTotals() resolves the authoritative value
            // from the customer's tax code via InvoiceTaxResolver.
            'tax_obligation' => false,
            'tax_code' => $contract->ppn_code,
            'npwp_number' => $billingGroup->npwp_number ?? null,
            'tax_number' => $billingGroup->tax_number ?? null,
            'tax_address' => $billingGroup->npwp_address ?? null,
            'kirim' => $billingGroup->invoice_type ?? 'manual',
            'gedung' => $this->getBuildingInfo($contract),
            'alamat_1' => $contract->customer->address ?? '',
            'alamat_2' => $contract->customer->city ?? '',
            'catatan_internal' => "Auto-generated for rental period: {$rentalPeriod}. ".($contract->internal_notes ?? ''),
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
            'created_by' => auth()->id(),
        ]);

        $invoice->logActivity(
            'created',
            'Invoice auto-generated for rental period '.$rentalPeriod,
            auth()->id()
        );

        return $invoice;
    }

    /**
     * Create invoice details from completed jobs
     */
    private function createInvoiceDetailsFromJobs(Invoice $invoice, Contract $contract, Carbon $periodStart, Carbon $periodEnd, array $billedRentals = []): \Illuminate\Database\Eloquent\Collection
    {
        $pairs = $this->computeBillableRentalPairs($contract, $periodStart, $periodEnd, $billedRentals);

        foreach ($pairs as $pair) {
            $this->createInvoiceDetail($invoice, $pair['job'], $pair['rental']);
        }

        // Unfiltered (legacy/single-invoice) callers keep returning every ready job in the
        // period, regardless of whether it actually contributed a rental line — this matches
        // the original behavior relied on by attachApprovedBaFiles() for the single-invoice path.
        return $this->getReadyInvoiceTriggerJobsInPeriod($contract, $periodStart, $periodEnd);
    }

    /**
     * Compute the (job, rental-detail) pairs that should be billed for this contract/period.
     *
     * $billedRentals is a list of billing keys (see invoiceRentalBillingKey()) to treat as
     * already billed and therefore skip — used both for cross-invoice dedup within a single
     * generation pass and for backfilling an already-drafted invoice.
     *
     * $allowedContractRoomIds, when not null, restricts results to rentals whose
     * contract_room_id is in the given list — this is how per-billing-group invoice
     * splitting scopes rental lines to only the rooms that belong to that billing group.
     * Passing null (the default) bills every eligible room, i.e. the original behavior.
     *
     * @return array<int, array{job: JobSchedule, rental: array}>
     */
    private function computeBillableRentalPairs(Contract $contract, Carbon $periodStart, Carbon $periodEnd, array $billedRentals = [], ?array $allowedContractRoomIds = null): array
    {
        $completedJobs = $this->getReadyInvoiceTriggerJobsInPeriod($contract, $periodStart, $periodEnd);
        $pairs = [];

        foreach ($completedJobs as $jobSchedule) {
            $rentalDetails = $this->getRentalDetailsForJob($jobSchedule);

            foreach ($rentalDetails as $rental) {
                if ($allowedContractRoomIds !== null && ! in_array($rental['contract_room_id'] ?? null, $allowedContractRoomIds, true)) {
                    continue;
                }

                // Create a unique key for this rental: room + rental product
                $billingKey = $this->invoiceRentalBillingKey($rental);

                if (in_array($billingKey, $billedRentals, true)) {
                    Log::debug('Skipping duplicate billing for rental unit', [
                        'job_no' => $jobSchedule->job_number,
                        'room' => $rental['room_name'],
                        'rental' => $rental['rental_name'],
                    ]);

                    continue;
                }

                $billedRentals[] = $billingKey;
                $pairs[] = ['job' => $jobSchedule, 'rental' => $rental];
            }
        }

        return $pairs;
    }

    /**
     * Resolve which active billing group (if any) each of the contract's rooms belongs to.
     *
     * Mirrors the precedence BillingGroupService already uses elsewhere in the app:
     * 1. An explicit ContractRoom::billing_group_id wins when it points at one of the
     *    contract's currently-active billing groups.
     * 2. Otherwise, fall back to the billing group whose BillingGroup::buildings() list
     *    includes the room's building.
     * Rooms matching neither are left out of the map entirely — callers treat those as the
     * "leftover"/default bucket (constraint: rooms not covered by any billing group must
     * still be billed).
     *
     * @return array<int, int> contract_room_id => billing_group_id
     */
    private function resolveContractRoomBillingGroupMap(Contract $contract, \Illuminate\Support\Collection $activeBillingGroups): array
    {
        $contract->loadMissing('contractRooms.room');

        $buildingToGroup = [];
        foreach ($activeBillingGroups as $billingGroup) {
            foreach ($billingGroup->buildings as $building) {
                // First active billing group claiming a building wins if a building was
                // (incorrectly) assigned to more than one active billing group.
                if (! isset($buildingToGroup[$building->id])) {
                    $buildingToGroup[$building->id] = $billingGroup->id;
                }
            }
        }

        $activeBillingGroupIds = $activeBillingGroups->pluck('id')->all();
        $map = [];

        foreach ($contract->contractRooms as $contractRoom) {
            $billingGroupId = null;

            if ($contractRoom->billing_group_id && in_array((int) $contractRoom->billing_group_id, $activeBillingGroupIds, true)) {
                $billingGroupId = (int) $contractRoom->billing_group_id;
            } elseif ($contractRoom->room?->building_id && isset($buildingToGroup[$contractRoom->room->building_id])) {
                $billingGroupId = $buildingToGroup[$contractRoom->room->building_id];
            }

            if ($billingGroupId !== null) {
                $map[$contractRoom->id] = $billingGroupId;
            }
        }

        return $map;
    }

    /**
     * Generate one invoice per active billing group for this rental period (plus a "default"
     * invoice, without a billing_group_id, for any rooms not covered by any active billing
     * group). Each invoice only contains rental line items for rooms belonging to its bucket.
     *
     * Called from within autoGenerateInvoiceForRentalPeriod()'s existing transaction, so all
     * billing groups for this period either all succeed or all roll back together — matching
     * the current all-or-nothing contract-level semantics.
     */
    private function generateInvoicesPerBillingGroup(Contract $contract, \Illuminate\Support\Collection $activeBillingGroups, string $rentalPeriod, Carbon $periodStart, Carbon $periodEnd): array
    {
        $roomBucketMap = $this->resolveContractRoomBillingGroupMap($contract, $activeBillingGroups);
        $allRoomIds = $contract->contractRooms->pluck('id')->all();
        $leftoverRoomIds = array_values(array_diff($allRoomIds, array_keys($roomBucketMap)));

        $buckets = $activeBillingGroups
            ->map(fn (BillingGroup $billingGroup) => [
                'billing_group' => $billingGroup,
                'room_ids' => array_keys(array_filter($roomBucketMap, fn ($bgId) => $bgId === $billingGroup->id)),
            ])
            ->values()
            ->all();

        // Default/leftover bucket: rooms not covered by any active billing group must still
        // be billed somehow, using the same "no billing_group_id" behavior as before this fix.
        $buckets[] = ['billing_group' => null, 'room_ids' => $leftoverRoomIds];

        $generatedInvoices = [];
        $existingInvoices = [];

        foreach ($buckets as $bucket) {
            $billingGroup = $bucket['billing_group'];
            $billingGroupId = $billingGroup?->id;

            // Compute (without persisting) whether this bucket has anything to bill before
            // creating an invoice header/number for it, so an empty bucket doesn't burn a
            // document number or leave a zero-amount invoice behind.
            $pairs = $this->computeBillableRentalPairs($contract, $periodStart, $periodEnd, [], $bucket['room_ids']);
            if (empty($pairs)) {
                continue;
            }

            $existingInvoice = $this->checkExistingInvoice(
                $contract->id,
                $contract->contract_number,
                $rentalPeriod,
                $periodStart,
                $periodEnd,
                $billingGroupId,
                true
            );

            if ($existingInvoice) {
                $existingInvoices[] = $existingInvoice;

                continue;
            }

            $invoice = $this->createInvoiceForRentalPeriod($contract, $rentalPeriod, $periodStart, $periodEnd, $billingGroup);

            $jobIds = [];
            foreach ($pairs as $pair) {
                $this->createInvoiceDetail($invoice, $pair['job'], $pair['rental']);
                $jobIds[] = $pair['job']->id;
            }

            // Scope BA-file/contract-file attachment to only the jobs that actually
            // contributed a line to THIS invoice, so the same BA file isn't attached to
            // more than one billing group's invoice for the same period.
            $contributingJobs = $this->getReadyInvoiceTriggerJobsInPeriod($contract, $periodStart, $periodEnd)
                ->whereIn('id', array_unique($jobIds))
                ->values();

            $this->attachApprovedBaFiles($invoice, $contributingJobs);
            $this->attachVerifiedContractFiles($invoice, $contract);
            $this->updateInvoiceTotals($invoice);

            $generatedInvoices[] = $invoice;

            Log::info('Auto-generated invoice for contract '.$contract->contract_number
                .' billing group '.($billingGroupId ?? 'default')
                ." rental period {$rentalPeriod}: {$invoice->invoice_number}");
        }

        if (empty($generatedInvoices)) {
            if (! empty($existingInvoices)) {
                return [
                    'success' => false,
                    'message' => 'Invoice already exists for this rental period',
                    'existing_invoice' => $existingInvoices[0],
                    'existing_invoices' => $existingInvoices,
                ];
            }

            return [
                'success' => false,
                'message' => 'No completed billable rooms found for this billing period',
            ];
        }

        return [
            'success' => true,
            'invoice' => $generatedInvoices[0],
            'invoices' => $generatedInvoices,
            'message' => 'Invoice(s) auto-generated successfully for rental period',
            'rental_period' => $rentalPeriod,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
        ];
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
                'billingGroups.buildings',
            ])
            ->first();

        if (! $contract || ! $invoice->invoice_date) {
            return [];
        }

        $periodStart = Carbon::parse($invoice->invoice_date)->startOfMonth();
        $periodEnd = Carbon::parse($invoice->invoice_date)->endOfMonth();

        // If the contract has more than one active billing group, restrict the preview to
        // just this invoice's own bucket (its billing group's rooms, or the leftover/default
        // bucket when billing_group_id is null) — otherwise a repair run against one billing
        // group's invoice would suggest rows that actually belong to a sibling invoice.
        $allowedContractRoomIds = null;
        $activeBillingGroups = $contract->billingGroups->where('is_active', true)->values();

        if ($activeBillingGroups->count() > 1) {
            $roomBucketMap = $this->resolveContractRoomBillingGroupMap($contract, $activeBillingGroups);

            if ($invoice->billing_group_id) {
                $allowedContractRoomIds = array_keys(array_filter(
                    $roomBucketMap,
                    fn ($billingGroupId) => $billingGroupId === (int) $invoice->billing_group_id
                ));
            } else {
                $allRoomIds = $contract->contractRooms->pluck('id')->all();
                $allowedContractRoomIds = array_values(array_diff($allRoomIds, array_keys($roomBucketMap)));
            }
        }

        $completedJobs = $this->getReadyInvoiceTriggerJobsInPeriod($contract, $periodStart, $periodEnd);
        $expected = [];
        $billedRentals = [];

        foreach ($completedJobs as $jobSchedule) {
            foreach ($this->getRentalDetailsForJob($jobSchedule) as $rental) {
                if ($allowedContractRoomIds !== null && ! in_array($rental['contract_room_id'] ?? null, $allowedContractRoomIds, true)) {
                    continue;
                }

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

                if (! $exists) {
                    InvoiceFile::create([
                        'invoice_id' => $invoice->id,
                        'file_type' => 'attachment',
                        'file_name' => 'BA File - '.($baFile->room_name ?: 'Unknown Room').' - '.$baFile->file_name,
                        'file_path' => str_replace('uploads/', '', $baFile->file_path),
                        'description' => "BA File dari Job #{$jobSchedule->job_number} Ruangan {$baFile->room_name}",
                        'created_by' => auth()->id(),
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
        if (! $contract) {
            return [];
        }

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
                Log::debug('Skipping non-billable suspended/cancelled room for invoice rental detail', [
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
                Log::debug('Skipping non-billable suspended/cancelled schedule room for invoice rental detail', [
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
            Log::debug('Skipping non-billable suspended/cancelled JA room for invoice rental detail', [
                'job_no' => $jobSchedule->job_number,
                'job_advice_room_id' => $jobAdviceRoom->id,
                'contract_room_id' => $jobAdviceRoom->contract_room_id,
            ]);

            return null;
        }

        $masterRental = $jobAdviceRoom->rentalProduct ?? $jobAdviceRoom->contractRoom?->rental_product;
        if (! $masterRental) {
            return null;
        }

        if (! $this->jobTypeCanInvoiceRentalType(
            $this->normalizeJobType($jobSchedule->type),
            $this->normalizeRentalType($masterRental),
            $contract,
            $jobSchedule,
            ! $this->isBeforeServiceTrialContinuation($contract, $jobSchedule)
        )) {
            Log::debug('Skipping JA room rental detail because job type is not the invoice trigger for this rental type', [
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
        if (! $contractRental) {
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
        if (! $masterRental) {
            return null;
        }

        if (! $this->jobTypeCanInvoiceRentalType(
            $this->normalizeJobType($jobSchedule->type),
            $this->normalizeRentalType($masterRental),
            $contract,
            $jobSchedule,
            ! $this->isBeforeServiceTrialContinuation($contract, $jobSchedule)
        )) {
            Log::debug('Skipping rental detail because job type is not the invoice trigger for this rental type', [
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
        if (! $contractRental) {
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
        if (! empty($jobAdviceRoom->contract_rental_id)) {
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
            'created_by' => auth()->id(),
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

            if (! $exists) {
                InvoiceFile::create([
                    'invoice_id' => $invoice->id,
                    'file_type' => 'attachment',
                    'file_name' => 'Contract File - '.$contractFile->file_name,
                    'file_path' => $cleanPath,
                    'description' => "File Kontrak #{$contract->contract_number}: {$contractFile->file_type}",
                    'created_by' => auth()->id(),
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

        $resolver = app(InvoiceTaxResolver::class);
        $invoice->loadMissing('customer');
        $context = $resolver->resolve($invoice->customer, $invoice->tax_code, $invoice->invoice_date);
        $taxAmount = $resolver->taxAmount((float) $subtotal, $context);
        $totalAmount = round($subtotal + $taxAmount, 2);

        $invoice->forceFill([
            'subtotal' => $subtotal,
            'tax_setting_id' => $context['default_vat_setting']?->id,
            'tax_code' => $context['tax_code'],
            'tax_obligation' => $context['applies_ppn'],
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'grand_total' => $totalAmount,
            'outstanding' => $totalAmount,
            'total_paid' => 0,
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
            'successful_generations' => collect($results)->where('success', true)->count(),
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
                    'status' => $this->getPeriodStatus($contract, $currentDate, $periodEnd),
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
                    'status' => $this->getPeriodStatus($contract, $currentDate, $periodEnd),
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
            $contract = Contract::with(['billingGroups.buildings', 'contractRooms.room'])->find($contractId);
            if (! $contract) {
                return;
            }

            $activeBillingGroups = $contract->billingGroups->where('is_active', true)->values();

            // Get all rental periods for this contract
            $periods = $this->getRentalPeriodsForContract($contractId);

            foreach ($periods as $period) {
                // We only care about periods that are fully COMPLETED
                if ($period['status'] !== 'completed') {
                    continue;
                }

                $periodStart = Carbon::parse($period['period_start']);
                $periodEnd = Carbon::parse($period['period_end']);

                if ($activeBillingGroups->count() > 1) {
                    $this->attemptAutoInvoiceForMultiBillingGroupPeriod($contract, $activeBillingGroups, $period, $periodStart, $periodEnd);

                    continue;
                }

                // Check if invoice already exists to avoid redundant processing
                $exists = $this->checkExistingInvoice(
                    $contractId,
                    $contract->contract_number ?? '',
                    $period['rental_period'],
                    $periodStart,
                    $periodEnd
                );

                if (! $exists) {
                    Log::info("Real-time Invoice Trigger: Attempting to generate invoice for Contract {$contractId}, Period {$period['rental_period']}");

                    $result = $this->autoGenerateInvoiceForRentalPeriod(
                        $contractId,
                        $period['rental_period'],
                        $periodStart,
                        $periodEnd
                    );

                    if ($result['success']) {
                        Log::info("Real-time Invoice Trigger: [SUCCESS] Generated Invoice {$result['invoice']->invoice_number}");
                    } else {
                        Log::warning('Real-time Invoice Trigger: [FAILED] '.$result['message']);
                    }
                } elseif ($exists->invoice_status === Invoice::STATUS_DRAFT) {
                    // A room/rental whose job completed after this period's invoice was
                    // first drafted would otherwise never get billed (the existence check
                    // above used to just skip). Backfill any missing rental lines while
                    // the invoice is still a draft.
                    $this->refreshDraftInvoiceRentalDetails($exists, $periodStart, $periodEnd);
                }
            }

        } catch (\Exception $e) {
            Log::error('Real-time Invoice Trigger Error: '.$e->getMessage());
        }
    }

    /**
     * Real-time trigger handling for a completed period on a contract with more than one
     * active billing group. Checks each billing-group bucket (plus the default/leftover
     * bucket) independently: if any bucket is missing its invoice, delegates to
     * autoGenerateInvoiceForRentalPeriod() (which internally creates whichever buckets are
     * still missing for the period in one pass); buckets that already have a draft invoice
     * get backfilled with any newly-completed room's rental lines, scoped to that bucket's
     * own rooms only.
     */
    private function attemptAutoInvoiceForMultiBillingGroupPeriod(Contract $contract, \Illuminate\Support\Collection $activeBillingGroups, array $period, Carbon $periodStart, Carbon $periodEnd): void
    {
        $roomBucketMap = $this->resolveContractRoomBillingGroupMap($contract, $activeBillingGroups);
        $allRoomIds = $contract->contractRooms->pluck('id')->all();
        $leftoverRoomIds = array_values(array_diff($allRoomIds, array_keys($roomBucketMap)));

        $buckets = $activeBillingGroups
            ->map(fn (BillingGroup $billingGroup) => [
                'billing_group_id' => $billingGroup->id,
                'room_ids' => array_keys(array_filter($roomBucketMap, fn ($bgId) => $bgId === $billingGroup->id)),
            ])
            ->values()
            ->all();
        $buckets[] = ['billing_group_id' => null, 'room_ids' => $leftoverRoomIds];

        $anyMissing = false;

        foreach ($buckets as $bucket) {
            $existing = $this->checkExistingInvoice(
                $contract->id,
                $contract->contract_number ?? '',
                $period['rental_period'],
                $periodStart,
                $periodEnd,
                $bucket['billing_group_id'],
                true
            );

            if (! $existing) {
                $anyMissing = true;
            } elseif ($existing->invoice_status === Invoice::STATUS_DRAFT) {
                $this->refreshDraftInvoiceRentalDetails($existing, $periodStart, $periodEnd, $bucket['room_ids']);
            }
        }

        if (! $anyMissing) {
            return;
        }

        Log::info("Real-time Invoice Trigger: Attempting to generate invoice(s) for Contract {$contract->id}, Period {$period['rental_period']} (multi billing group)");

        $result = $this->autoGenerateInvoiceForRentalPeriod(
            $contract->id,
            $period['rental_period'],
            $periodStart,
            $periodEnd
        );

        if ($result['success']) {
            Log::info('Real-time Invoice Trigger: [SUCCESS] Generated invoice(s): '.
                collect($result['invoices'] ?? [$result['invoice']])->pluck('invoice_number')->implode(', '));
        } else {
            Log::warning('Real-time Invoice Trigger: [FAILED] '.($result['message'] ?? ''));
        }
    }

    /**
     * Add any rental lines for completed jobs in the period that are missing from an
     * already-drafted invoice (e.g. a second room's job completed after the invoice for
     * this period was first generated from the first room's job).
     *
     * $allowedContractRoomIds scopes the backfill to a specific billing-group bucket's rooms
     * (see attemptAutoInvoiceForMultiBillingGroupPeriod()); null bills every eligible room,
     * which is the original single-invoice behavior.
     */
    private function refreshDraftInvoiceRentalDetails(Invoice $invoice, Carbon $periodStart, Carbon $periodEnd, ?array $allowedContractRoomIds = null): void
    {
        $contract = $invoice->contract_id ? Contract::find($invoice->contract_id) : null;
        if (! $contract) {
            return;
        }

        $invoice->loadMissing('invoiceRentalDetails');
        $billedRentals = $invoice->invoiceRentalDetails
            ->map(fn ($detail) => $this->invoiceRentalBillingKey([
                'room_name' => $detail->room_name,
                'master_rental_id' => $detail->master_rental_id,
            ]))
            ->all();

        DB::transaction(function () use ($invoice, $contract, $periodStart, $periodEnd, $billedRentals, $allowedContractRoomIds) {
            $existingCount = $invoice->invoiceRentalDetails()->count();

            $pairs = $this->computeBillableRentalPairs($contract, $periodStart, $periodEnd, $billedRentals, $allowedContractRoomIds);
            foreach ($pairs as $pair) {
                $this->createInvoiceDetail($invoice, $pair['job'], $pair['rental']);
            }

            $invoice->refresh();
            if ($invoice->invoiceRentalDetails()->count() > $existingCount) {
                $this->updateInvoiceTotals($invoice);
                Log::info("Real-time Invoice Trigger: backfilled missing rental line(s) into existing draft invoice {$invoice->invoice_number}");
            }
        });
    }
}
