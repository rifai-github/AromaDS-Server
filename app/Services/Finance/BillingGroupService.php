<?php

namespace App\Services\Finance;

use App\Models\Finance\BillingGroup;
use App\Models\Contract;
use App\Models\Finance\Invoice;
use App\Models\JobSchedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Services\DocumentNumberService;

class BillingGroupService
{
    private const BILLABLE_COMPLETED_STATUSES = ['completed', 'done_job', 'dpf'];
    private const NON_BILLABLE_JOB_STATUSES = ['cancelled', 'suspend', 'meninggalkan_lokasi', 'undone'];
    private const NON_BILLABLE_JOB_TYPES = ['install_free', 'install free', 'remove_free', 'remove free'];

    protected $documentNumberService;

    public function __construct(DocumentNumberService $documentNumberService)
    {
        $this->documentNumberService = $documentNumberService;
    }

    /**
     * Create billing group for a contract
     */
    public function createBillingGroup(array $data): array
    {
        try {
            DB::beginTransaction();

            $contract = Contract::findOrFail($data['contract_id']);
            
            // Validate billing frequency and dates
            $this->validateBillingData($data);

            $billingGroup = BillingGroup::create([
                'billing_group_name' => $data['billing_group_name'],
                'contract_id' => $data['contract_id'],
                'billing_frequency' => $data['billing_frequency'],
                'billing_start_date' => $data['billing_start_date'],
                'billing_end_date' => $data['billing_end_date'] ?? null,
                'billing_amount' => $data['billing_amount'],
                'is_active' => $data['is_active'] ?? true,
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            return [
                'success' => true,
                'billing_group' => $billingGroup,
                'message' => 'Billing group created successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Billing group creation failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to create billing group: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate invoices for billing group
     */
    public function generateInvoices(int $billingGroupId): array
    {
        try {
            DB::beginTransaction();

            $billingGroup = BillingGroup::with('contract')->findOrFail($billingGroupId);
            
            if (!$billingGroup->is_active) {
                throw new \Exception('Billing group is not active');
            }

            $invoices = [];
            $currentDate = Carbon::parse($billingGroup->billing_start_date);
            $endDate = $billingGroup->billing_end_date ? Carbon::parse($billingGroup->billing_end_date) : Carbon::now();

            while ($currentDate->lte($endDate)) {
                $invoice = $this->createInvoiceForBillingGroup($billingGroup, $currentDate);
                $this->createDetailsForInvoice($invoice, $billingGroup);
                $this->syncInvoiceTotalsFromDetails($invoice);
                $invoices[] = $invoice;
                
                $currentDate = $this->getNextBillingDate($currentDate, $billingGroup->billing_frequency);
            }

            DB::commit();

            return [
                'success' => true,
                'invoices' => $invoices,
                'count' => count($invoices),
                'message' => 'Invoices generated successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Invoice generation failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to generate invoices: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create invoice for billing group
     */
    private function createInvoiceForBillingGroup(BillingGroup $billingGroup, Carbon $billingDate): Invoice
    {
        $contract = $billingGroup->contract;
        // Handle payment_terms: if it's a string (like 'cash'), use default 30 days; if it's int, use it directly
        $paymentTerms = is_numeric($contract->payment_terms) ? (int)$contract->payment_terms : 30;
        $dueDate = $this->calculateDueDate($billingDate, $paymentTerms);

        // Calculate Tax based on Customer Tax Obligation
        $taxObligation = $contract->customer->tax_obligation ?? false;
        $taxAmount = $taxObligation ? ($billingGroup->billing_amount * 0.11) : 0;
        $totalAmount = $billingGroup->billing_amount + $taxAmount;

        return Invoice::create([
            'invoice_number' => $this->documentNumberService->generate(
                'invoice',
                null,
                null,
                $contract->id
            ),
            'contract_id' => $billingGroup->contract_id,
            'contract_number' => $contract->contract_number,
            'po_number' => $contract->po_number, // Mapped from Contract
            'customer_id' => $contract->customer_id,
            'billing_address' => $billingGroup->pic_address ?? $contract->customer->address ?? '',
            'pic_finance' => $billingGroup->pic_name ?? '',
            'email' => $billingGroup->pic_email ?? $contract->customer->email ?? '',
            'invoice_date' => $billingDate->toDateString(),
            'due_date' => $dueDate->toDateString(),
            'subtotal' => $billingGroup->billing_amount,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'grand_total' => $totalAmount,
            'tax_code' => $contract->ppn_code,
            'kirim' => $billingGroup->invoice_type ?? 'manual',
            'tax_obligation' => $taxObligation,
            'invoice_status' => Invoice::STATUS_DRAFT,
            'status' => Invoice::STATUS_DRAFT,
            'billing_group_id' => $billingGroup->id,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Get next billing date based on frequency
     */
    private function getNextBillingDate(Carbon $currentDate, string $frequency): Carbon
    {
        switch ($frequency) {
            case 'monthly':
                return $currentDate->addMonth();
            case 'quarterly':
                return $currentDate->addMonths(3);
            case 'yearly':
                return $currentDate->addYear();
            case 'one_time':
                return $currentDate->addYear(); // For one-time, just add a year to break the loop
            default:
                return $currentDate->addMonth();
        }
    }

    /**
     * Calculate due date based on payment terms
     */
    private function calculateDueDate(Carbon $invoiceDate, int $paymentTerms): Carbon
    {
        return $invoiceDate->copy()->addDays($paymentTerms);
    }

    /**
     * Auto-generate invoice when all jobs in billing group are completed (Berdasarkan BRD)
     */
    public function autoGenerateInvoiceWhenJobsCompleted(int $billingGroupId, ?Carbon $billingDate = null): array
    {
        try {
            DB::beginTransaction();

            $billingGroup = BillingGroup::with(['contract', 'contract.customer'])->findOrFail($billingGroupId);
            $targetInvoiceDate = $billingDate?->copy() ?? Carbon::now();
            
            if (!$billingGroup->is_active) {
                throw new \Exception('Billing group is not active');
            }

            // Check if all jobs in current billing period are completed
            $allJobsCompleted = $this->checkAllJobsCompletedInBillingGroup($billingGroup, $targetInvoiceDate);
            
            if (!$allJobsCompleted) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Not all jobs in billing group are completed yet'
                ];
            }

            // Check if invoice for current month and billing group already exists
            $existingInvoice = Invoice::where('billing_group_id', $billingGroupId)
                ->where('invoice_status', '!=', Invoice::STATUS_CANCELLED)
                ->whereMonth('invoice_date', $targetInvoiceDate->month)
                ->whereYear('invoice_date', $targetInvoiceDate->year)
                ->exists();

            if ($existingInvoice) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Invoice for current period already exists'
                ];
            }

            // Generate invoice for current billing period
            $invoice = $this->createInvoiceForBillingGroup($billingGroup, $targetInvoiceDate);

            // Create rental details for the invoice
            $this->createDetailsForInvoice($invoice, $billingGroup, true, $targetInvoiceDate);

            if (
                !$invoice->invoiceRentalDetails()->exists() &&
                !$invoice->invoiceDetails()->exists()
            ) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'No completed billable rooms found for this billing period'
                ];
            }

            $this->syncInvoiceTotalsFromDetails($invoice);

            DB::commit();

            Log::info("Auto-generated invoice for billing group {$billingGroup->billing_group_name}: {$invoice->invoice_number}");

            return [
                'success' => true,
                'invoice' => $invoice,
                'message' => 'Invoice auto-generated successfully after all jobs completed'
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
     * Check if all jobs in billing group are completed
     */
    private function checkAllJobsCompletedInBillingGroup(BillingGroup $billingGroup, ?Carbon $billingDate = null): bool
    {
        $contractId = $billingGroup->contract_id;
        [$billingStartDate, $billingEndDate] = $this->getBillingDateRange($billingGroup, $billingDate);

        // Count billable jobs in the invoice period only. Partial/cancel/admin-correction jobs
        // must not block regeneration for an already cancelled invoice period.
        $totalJobs = DB::table('job_schedules')
            ->join('job_advices', 'job_schedules.job_advice_id', '=', 'job_advices.id')
            ->where('job_advices.contract_id', $contractId)
            ->whereBetween('job_schedules.schedule_date', [$billingStartDate, $billingEndDate])
            ->whereNotIn('job_schedules.status', self::NON_BILLABLE_JOB_STATUSES)
            ->whereRaw("LOWER(COALESCE(job_schedules.type, '')) NOT IN (?, ?, ?, ?)", self::NON_BILLABLE_JOB_TYPES)
            ->distinct()
            ->count('job_schedules.id');

        $completedJobs = DB::table('job_schedules')
            ->join('job_advices', 'job_schedules.job_advice_id', '=', 'job_advices.id')
            ->where('job_advices.contract_id', $contractId)
            ->whereBetween('job_schedules.schedule_date', [$billingStartDate, $billingEndDate])
            ->whereNotIn('job_schedules.status', self::NON_BILLABLE_JOB_STATUSES)
            ->whereRaw("LOWER(COALESCE(job_schedules.type, '')) NOT IN (?, ?, ?, ?)", self::NON_BILLABLE_JOB_TYPES)
            ->whereIn('job_schedules.status', self::BILLABLE_COMPLETED_STATUSES)
            ->distinct()
            ->count('job_schedules.id');

        return $totalJobs > 0 && $totalJobs === $completedJobs;
    }

    /**
     * Create invoice rental details based on contract rooms and rentals
     */
    private function createDetailsForInvoice(Invoice $invoice, BillingGroup $billingGroup, bool $onlyCompletedRooms = false, ?Carbon $billingDate = null): void
    {
        $contract = $billingGroup->contract;
        if (!$contract) return;

        $contractRooms = $onlyCompletedRooms
            ? $this->getBillableContractRoomsForBillingGroup($billingGroup, $contract, $billingDate)
            : $this->getEligibleContractRoomsForBillingGroup($billingGroup, $contract);

        if ($contractRooms->isEmpty() && !$onlyCompletedRooms) {
            $contractRooms = $this->getEligibleContractRoomsForBillingGroup($billingGroup, $contract, true);
        }
        
        $hasDetails = false;
        $createdKeys = [];

        foreach ($contractRooms as $contractRoom) {
            $masterRental = $contractRoom->rental_product;
            
            if ($masterRental) {
                // Find pricing detail for this rental and room (or null room for general contract rental)
                $contractRental = $contract->contractRentals()
                    ->where('master_rental_id', $masterRental->id)
                    ->where(function($q) use ($contractRoom) {
                        $q->where('room_id', $contractRoom->room_id)->orWhereNull('room_id');
                    })
                    ->first();

                if ($contractRental) {
                    $detailKey = $contractRoom->id . '_' . $masterRental->id;

                    if (isset($createdKeys[$detailKey])) {
                        continue;
                    }

                    $quantity = $contractRental->quantity ?? 1;
                    $unitPrice = $contractRental->unit_price ?? 0;
                    $totalPrice = $contractRental->total_price ?? ($quantity * $unitPrice);

                    $invoice->invoiceRentalDetails()->create([
                        'master_rental_id' => $masterRental->id,
                        'job_no' => '-', // Tidak menggunakan nomor job khusus untuk billing group bulanan yang fix
                        'building_name' => $contractRoom->building->building_name ?? '',
                        'room_name' => $contractRoom->room->room_name ?? '',
                        'rental_name' => $masterRental->rental_name ?? 'Service',
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                        'created_by' => auth()->id() ?? \App\Models\User::first()->id ?? null // Fallback if run via CLI script
                    ]);
                    $hasDetails = true;
                    $createdKeys[$detailKey] = true;
                }
            }
        }

        // Jika karena alasan tertentu tidak ada rental (misalnya contract lama), buatlah baris default di invoiceDetails
        if (!$hasDetails && !$onlyCompletedRooms) {
            $periodStr = Carbon::parse($invoice->invoice_date)->format('F Y');
            $invoice->invoiceDetails()->create([
                'description' => "Tagihan Layanan Periode " . $periodStr,
                'quantity' => 1,
                'unit_price' => $invoice->subtotal,
                'total_price' => $invoice->subtotal,
                'created_by' => auth()->id() ?? \App\Models\User::first()->id ?? null
            ]);
        }
    }

    /**
     * Get contract rooms that belong to the billing group scope.
     */
    private function getEligibleContractRoomsForBillingGroup(BillingGroup $billingGroup, Contract $contract, bool $ignoreBillingGroupScope = false): \Illuminate\Support\Collection
    {
        $query = $contract->contractRooms()->with(['room.building']);

        if (!$ignoreBillingGroupScope) {
            $scopedRoomIds = $contract->contractRooms()
                ->where('billing_group_id', $billingGroup->id)
                ->pluck('id');

            if ($scopedRoomIds && $scopedRoomIds->isNotEmpty()) {
                $query->whereIn('id', $scopedRoomIds);
            } else {
                $buildingIds = $billingGroup->buildings()->pluck('buildings.id');

                if ($buildingIds->isNotEmpty()) {
                    $query->whereHas('room', function ($roomQuery) use ($buildingIds) {
                        $roomQuery->whereIn('building_id', $buildingIds);
                    });
                }
            }
        }

        return $query->get();
    }

    /**
     * Get billable contract rooms based on jobs that were actually completed in the billing period.
     */
    private function getBillableContractRoomsForBillingGroup(BillingGroup $billingGroup, Contract $contract, ?Carbon $billingDate = null): \Illuminate\Support\Collection
    {
        $eligibleRooms = $this->getEligibleContractRoomsForBillingGroup($billingGroup, $contract);

        if ($eligibleRooms->isEmpty()) {
            return $eligibleRooms;
        }

        [$billingStartDate, $billingEndDate] = $this->getBillingDateRange($billingGroup, $billingDate);

        $completedJobs = JobSchedule::with(['jobAdvice.rooms'])
            ->whereHas('jobAdvice', function ($query) use ($contract) {
                $query->where('contract_id', $contract->id)
                    ->whereRaw("LOWER(COALESCE(type, '')) NOT IN (?, ?)", ['install free', 'install_free']);
            })
            ->whereBetween('schedule_date', [$billingStartDate, $billingEndDate])
            ->whereIn('status', self::BILLABLE_COMPLETED_STATUSES)
            ->whereRaw("LOWER(COALESCE(type, '')) NOT IN (?, ?, ?)", ['remove', 'remove free', 'remove_free'])
            ->get();

        if ($completedJobs->isEmpty()) {
            return collect();
        }

        $eligibleRoomsById = $eligibleRooms->keyBy('id');
        $eligibleRoomsByRoomId = $eligibleRooms->groupBy('room_id');
        $billableRooms = collect();

        foreach ($completedJobs as $jobSchedule) {
            $matchedJobAdviceRooms = $jobSchedule->jobAdvice?->rooms
                ?->filter(function ($jobAdviceRoom) use ($jobSchedule) {
                    return in_array($jobSchedule->id, [
                        $jobAdviceRoom->install_job_schedule_id,
                        $jobAdviceRoom->service_job_schedule_id,
                        $jobAdviceRoom->remove_job_schedule_id,
                    ], true);
                }) ?? collect();

            $linkedContractRoomIds = $matchedJobAdviceRooms
                ->reject(function ($jobAdviceRoom) {
                    return (bool) $jobAdviceRoom->is_trial;
                })
                ->pluck('contract_room_id')
                ->filter()
                ->unique()
                ->values();

            foreach ($linkedContractRoomIds as $contractRoomId) {
                if ($eligibleRoomsById->has($contractRoomId)) {
                    $billableRooms->push($eligibleRoomsById->get($contractRoomId));
                }
            }

            if ($matchedJobAdviceRooms->isNotEmpty() && $linkedContractRoomIds->isEmpty()) {
                continue;
            }

            if ($linkedContractRoomIds->isEmpty() && $jobSchedule->room_id && $eligibleRoomsByRoomId->has($jobSchedule->room_id)) {
                $billableRooms = $billableRooms->merge($eligibleRoomsByRoomId->get($jobSchedule->room_id));
            }
        }

        if ($billableRooms->isEmpty()) {
            return $eligibleRooms->values();
        }

        return $billableRooms
            ->filter()
            ->unique('id')
            ->values();
    }

    /**
     * Keep invoice header totals aligned with the actual generated details.
     */
    private function syncInvoiceTotalsFromDetails(Invoice $invoice): void
    {
        $subtotal = (float) $invoice->invoiceDetails()->sum('total_price') + (float) $invoice->invoiceRentalDetails()->sum('total_price');
        $taxAmount = $invoice->tax_obligation ? round($subtotal * 0.11, 2) : 0;
        $grandTotal = $subtotal + $taxAmount;
        $totalPaid = (float) ($invoice->total_paid ?? 0);

        $invoice->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $grandTotal,
            'grand_total' => $grandTotal,
            'outstanding' => max($grandTotal - $totalPaid, 0),
        ]);
    }

    /**
     * Resolve billing period boundaries for room-based invoice filtering.
     */
    private function getBillingDateRange(BillingGroup $billingGroup, ?Carbon $billingDate = null): array
    {
        $billingStartDate = Carbon::parse($billingGroup->billing_start_date)->startOfDay();
        $billingEndDate = $billingGroup->billing_end_date
            ? Carbon::parse($billingGroup->billing_end_date)->endOfDay()
            : Carbon::now()->endOfDay();

        if ($billingDate) {
            $periodStart = $billingDate->copy()->startOfMonth()->startOfDay();
            $periodEnd = $billingDate->copy()->endOfMonth()->endOfDay();

            if ($periodStart->greaterThan($billingStartDate)) {
                $billingStartDate = $periodStart;
            }

            if ($periodEnd->lessThan($billingEndDate)) {
                $billingEndDate = $periodEnd;
            }
        }

        return [$billingStartDate, $billingEndDate];
    }


    /**
     * Validate billing data
     */
    private function validateBillingData(array $data): void
    {
        if (empty($data['billing_group_name'])) {
            throw new \Exception('Billing group name is required');
        }

        if (empty($data['contract_id'])) {
            throw new \Exception('Contract ID is required');
        }

        if (empty($data['billing_frequency'])) {
            throw new \Exception('Billing frequency is required');
        }

        if (!in_array($data['billing_frequency'], ['monthly', 'quarterly', 'yearly', 'one_time'])) {
            throw new \Exception('Invalid billing frequency');
        }

        if (empty($data['billing_start_date'])) {
            throw new \Exception('Billing start date is required');
        }

        if (empty($data['billing_amount']) || $data['billing_amount'] <= 0) {
            throw new \Exception('Billing amount must be greater than 0');
        }

        // Validate date range
        if ($data['billing_end_date'] && 
            Carbon::parse($data['billing_end_date'])->lte(Carbon::parse($data['billing_start_date']))) {
            throw new \Exception('Billing end date must be after start date');
        }
    }

    /**
     * Update billing group
     */
    public function updateBillingGroup(int $billingGroupId, array $data): array
    {
        try {
            DB::beginTransaction();

            $billingGroup = BillingGroup::findOrFail($billingGroupId);
            
            // Validate billing data
            $this->validateBillingData(array_merge($billingGroup->toArray(), $data));

            $billingGroup->update(array_merge($data, [
                'updated_by' => auth()->id(),
            ]));

            DB::commit();

            return [
                'success' => true,
                'billing_group' => $billingGroup,
                'message' => 'Billing group updated successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Billing group update failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to update billing group: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Deactivate billing group
     */
    public function deactivateBillingGroup(int $billingGroupId): array
    {
        try {
            $billingGroup = BillingGroup::findOrFail($billingGroupId);
            
            $billingGroup->update([
                'is_active' => false,
                'updated_by' => auth()->id(),
            ]);

            return [
                'success' => true,
                'message' => 'Billing group deactivated successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Billing group deactivation failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to deactivate billing group: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get billing group statistics
     */
    public function getBillingGroupStatistics(int $billingGroupId): array
    {
        $billingGroup = BillingGroup::with(['contract'])->findOrFail($billingGroupId);
        
        $invoices = Invoice::where('billing_group_id', $billingGroupId)->get();
        
        $totalInvoices = $invoices->count();
        $totalAmount = $invoices->sum('total_amount');
        $paidAmount = $invoices->where('status', 'paid')->sum('total_amount');
        $pendingAmount = $invoices->whereIn('status', ['draft', 'sent', 'overdue'])->sum('total_amount');
        
        return [
            'billing_group' => $billingGroup,
            'total_invoices' => $totalInvoices,
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'pending_amount' => $pendingAmount,
            'collection_rate' => $totalAmount > 0 ? ($paidAmount / $totalAmount) * 100 : 0,
        ];
    }

    /**
     * Get upcoming billing dates
     */
    public function getUpcomingBillingDates(int $billingGroupId, int $months = 6): array
    {
        $billingGroup = BillingGroup::findOrFail($billingGroupId);
        
        if (!$billingGroup->is_active) {
            return [];
        }

        $dates = [];
        $currentDate = Carbon::parse($billingGroup->billing_start_date);
        $endDate = Carbon::now()->addMonths($months);
        
        if ($billingGroup->billing_end_date) {
            $endDate = min($endDate, Carbon::parse($billingGroup->billing_end_date));
        }

        while ($currentDate->lte($endDate)) {
            if ($currentDate->gte(Carbon::now())) {
                $dates[] = [
                    'date' => $currentDate->toDateString(),
                    'amount' => $billingGroup->billing_amount,
                    'frequency' => $billingGroup->billing_frequency,
                ];
            }
            
            $currentDate = $this->getNextBillingDate($currentDate, $billingGroup->billing_frequency);
        }

        return $dates;
    }
}
