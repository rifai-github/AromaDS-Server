<?php

namespace App\Models;

use App\Services\DocumentNumberService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ContractSwitching extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'switching_number',
        'old_contract_id',
        'old_customer_id',
        'new_customer_id',
        'new_contract_id',
        'switching_reason',
        'switching_description',
        'switching_notes',
        'continue_period',
        'continue_top',
        'reset_dates',
        'continue_from_period',
        'status',
        'approval_notes',
        'rejection_reason',
        'approved_at',
        'rejected_at',
        'executed_at',
        'completed_at',
        'initiated_by',
        'approved_by',
        'rejected_by',
        'executed_by',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'continue_period' => 'boolean',
        'continue_top' => 'boolean',
        'reset_dates' => 'boolean',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'executed_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    protected $appends = ['status_badge', 'status_text'];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_EXECUTED = 'executed';
    const STATUS_COMPLETED = 'completed';

    // Switching Reason constants
    const REASON_REBRANDING = 'Company Rebranding';
    const REASON_MERGER = 'Company Merger';
    const REASON_ACQUISITION = 'Company Acquisition';
    const REASON_NAME_CHANGE = 'Company Name Change';
    const REASON_RESTRUCTURING = 'Corporate Restructuring';
    const REASON_TRANSFER = 'Business Transfer';
    const REASON_OTHER = 'Other';

    // Relationships
    public function oldContract()
    {
        return $this->belongsTo(Contract::class, 'old_contract_id');
    }

    public function oldCustomer()
    {
        return $this->belongsTo(Customer::class, 'old_customer_id');
    }

    public function newCustomer()
    {
        return $this->belongsTo(Customer::class, 'new_customer_id');
    }

    public function newContract()
    {
        return $this->belongsTo(Contract::class, 'new_contract_id');
    }

    public function initiatedBy()
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function executedBy()
    {
        return $this->belongsTo(User::class, 'executed_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', self::STATUS_PENDING_APPROVAL);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeExecuted($query)
    {
        return $query->where('status', self::STATUS_EXECUTED);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        $statuses = [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING_APPROVAL => 'Pending Approval',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_EXECUTED => 'Executed',
            self::STATUS_COMPLETED => 'Completed'
        ];
        return $statuses[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            self::STATUS_DRAFT => 'badge-secondary',
            self::STATUS_PENDING_APPROVAL => 'badge-warning',
            self::STATUS_APPROVED => 'badge-info',
            self::STATUS_REJECTED => 'badge-danger',
            self::STATUS_CANCELLED => 'badge-dark',
            self::STATUS_EXECUTED => 'badge-primary',
            self::STATUS_COMPLETED => 'badge-success'
        ];
        return $badges[$this->status] ?? 'badge-secondary';
    }

    // Boolean accessors
    public function getIsDraftAttribute(): bool { return $this->status === self::STATUS_DRAFT; }
    public function getIsPendingApprovalAttribute(): bool { return $this->status === self::STATUS_PENDING_APPROVAL; }
    public function getIsApprovedAttribute(): bool { return $this->status === self::STATUS_APPROVED; }
    public function getIsExecutedAttribute(): bool { return $this->status === self::STATUS_EXECUTED; }
    public function getIsCompletedAttribute(): bool { return $this->status === self::STATUS_COMPLETED; }
    public function getIsRejectedAttribute(): bool { return $this->status === self::STATUS_REJECTED; }
    public function getIsCancelledAttribute(): bool { return $this->status === self::STATUS_CANCELLED; }

    // Methods
    public function submitForApproval()
    {
        if (!$this->isDraft) {
            throw new \Exception('Switching must be in draft status');
        }
        
        $this->update([
            'status' => self::STATUS_PENDING_APPROVAL
        ]);
    }

    public function approve($approvedBy, $notes = null)
    {
        if (!$this->isPendingApproval) {
            throw new \Exception('Switching must be pending approval');
        }
        
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => $approvedBy,
            'approval_notes' => $notes
        ]);
    }

    public function reject($rejectedBy, $reason)
    {
        if (!$this->isPendingApproval) {
            throw new \Exception('Switching must be pending approval');
        }
        
        $this->update([
            'status' => self::STATUS_REJECTED,
            'rejected_at' => now(),
            'rejected_by' => $rejectedBy,
            'rejection_reason' => $reason
        ]);
    }

    public function cancel()
    {
        if ($this->isCompleted) {
            throw new \Exception('Cannot cancel completed switching');
        }
        
        $this->update([
            'status' => self::STATUS_CANCELLED
        ]);
    }

    /**
     * Execute contract switching - Transfer customer (PT ABC → PT DGH)
     * This will:
     * 1. Clone old contract
     * 2. Assign to new customer
     * 3. Continue job numbering
     * 4. Continue invoice sequence
     * 5. Unit stays in location (NO remove/install)
     */
    public function execute($executedBy)
    {
        if (!$this->isApproved) {
            throw new \Exception('Switching must be approved before execution');
        }

        try {
            DB::beginTransaction();

            $oldContract = $this->oldContract;
            
            // Clone contract data for new customer
            $newContractData = $oldContract->toArray();
            
            // Remove fields that shouldn't be copied
            unset($newContractData['id'], $newContractData['created_at'], $newContractData['updated_at'], 
                  $newContractData['deleted_at'], $newContractData['contract_number']);
            
            // Update customer
            $newContractData['customer_id'] = $this->new_customer_id;
            
            // Generate new contract number
            $newContractData['contract_number'] = $this->generateNewContractNumber();
            
            $effectiveDate = $this->getEffectiveDate();
            $isResetAll = !$this->continue_period || $this->reset_dates;

            if ($isResetAll) {
                $newContractData['start_date'] = $effectiveDate->toDateString();
                $newContractData['end_date'] = $this->calculateResetEndDate($oldContract, $effectiveDate);
            } else {
                $newContractData['start_date'] = $effectiveDate->toDateString();
                $newContractData['end_date'] = $oldContract->end_date
                    ? Carbon::parse($oldContract->end_date)->toDateString()
                    : $this->calculateResetEndDate($oldContract, $effectiveDate);
            }

            // Apply Continue TOP Logic
            if (!$this->continue_top) {
                // User clarified that TOP refers to Frequency (e.g. 1 Bulan 1x), not Payment Method (Cash/Transfer).
                // So default reset value should be a frequency, likely "1 Bulan" (Monthly).
                $newContractData['payment_terms'] = '1 Bulan';
                $newContractData['term_of_payment'] = '1 Bulan';
                \Log::info("Contract Switching: TOP reset to '1 Bulan' for new contract because continue_top is false");
            }
            
            // Set user tracking
            $newContractData['created_by'] = $executedBy;
            $newContractData['updated_by'] = $executedBy;
            $newContractData['approved_by'] = $executedBy;
            $newContractData['posted_by'] = $executedBy;
            
            // Create new contract (without observers to avoid audit trail issues)
            $newContract = Contract::withoutEvents(function () use ($newContractData) {
                return Contract::create($newContractData);
            });
            
            // Copy billing groups
            $billingGroupIdMap = $this->copyBillingGroups($oldContract, $newContract, $executedBy);

            // Copy contract rooms/rentals
            $contractRoomIdMap = $this->copyContractRooms($oldContract, $newContract, $executedBy, $billingGroupIdMap);
            $contractRentalIdMap = $this->copyContractRentals($oldContract, $newContract, $executedBy);
            $this->copyContractSurveys($oldContract, $newContract, $executedBy);
            $transferSummary = $this->transferFutureOperationalData(
                $oldContract,
                $newContract,
                $effectiveDate,
                $executedBy,
                $contractRoomIdMap,
                $contractRentalIdMap
            );
            
            // Update switching record
            $this->update([
                'new_contract_id' => $newContract->id,
                'executed_at' => now(),
                'executed_by' => $executedBy,
                'status' => self::STATUS_EXECUTED
            ]);
            
            // Update old contract status to 'terminated' or 'switched'
            $oldContract->update([
                'contract_status' => 'terminated',
                'status' => 'terminated',
                'notes' => ($oldContract->notes ?? '') . "\n\nContract switched to new customer {$this->newCustomer->name} on " . now()->format('Y-m-d H:i:s') . " (Switching: {$this->switching_number})"
            ]);
            
            // Complete switching
            $this->update([
                'status' => self::STATUS_COMPLETED,
                'completed_at' => now()
            ]);

            DB::commit();

            Log::info("Contract Switching executed: {$this->switching_number}", [
                'old_contract' => $oldContract->contract_number,
                'new_contract' => $newContract->contract_number,
                'old_customer' => $this->oldCustomer->name,
                'new_customer' => $this->newCustomer->name,
                'mode' => $isResetAll ? 'reset_all' : 'continue_remaining',
                'effective_date' => $effectiveDate->toDateString(),
                'future_jobs_transferred' => $transferSummary['future_jobs_transferred'],
                'job_advices_cloned' => $transferSummary['job_advices_cloned'],
                'active_units_reassigned' => $transferSummary['active_units_reassigned'],
                'existing_invoices_preserved' => $transferSummary['existing_invoices_preserved'],
            ]);

            return $newContract;

        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error executing contract switching: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Backfill switched contract structure if an old switching created the new contract
     * without rooms, rentals, or billing groups.
     */
    public static function syncNewContractStructureIfMissing(Contract $newContract, ?int $performedBy = null): bool
    {
        $switching = self::with([
            'oldContract.contractRooms',
            'oldContract.contractRentals',
            'oldContract.contractSurveys',
            'oldContract.billingGroups.buildings',
        ])->where('new_contract_id', $newContract->id)->latest('id')->first();

        if (!$switching || !$switching->oldContract) {
            return false;
        }

        $oldContract = $switching->oldContract;
        $needsBillingGroups = !$newContract->billingGroups()->exists() && $oldContract->billingGroups->isNotEmpty();
        $needsRooms = !$newContract->contractRooms()->exists() && $oldContract->contractRooms->isNotEmpty();
        $needsRentals = !$newContract->contractRentals()->exists() && $oldContract->contractRentals->isNotEmpty();
        $missingSurveyIds = $oldContract->contractSurveys
            ->pluck('survey_id')
            ->filter()
            ->unique()
            ->diff($newContract->contractSurveys()->pluck('survey_id'));
        $needsContractSurveys = $missingSurveyIds->isNotEmpty();
        $existingBillingGroupMap = $needsBillingGroups
            ? []
            : $switching->mapExistingBillingGroups($oldContract, $newContract);
        $needsExistingBillingGroupBuildings = !$needsBillingGroups
            && $switching->hasMissingBillingGroupBuildings($oldContract, $newContract, $existingBillingGroupMap);

        if (
            !$needsBillingGroups
            && !$needsExistingBillingGroupBuildings
            && !$needsRooms
            && !$needsRentals
            && !$needsContractSurveys
        ) {
            return false;
        }

        try {
            DB::beginTransaction();

            $billingGroupIdMap = $needsBillingGroups
                ? $switching->copyBillingGroups($oldContract, $newContract, $performedBy)
                : $existingBillingGroupMap;

            $syncedExistingBillingGroupBuildings = false;
            if ($needsExistingBillingGroupBuildings && !empty($billingGroupIdMap)) {
                $switching->syncExistingBillingGroupBuildings($oldContract, $newContract, $billingGroupIdMap, $performedBy);
                $syncedExistingBillingGroupBuildings = true;
            }

            if ($needsRooms) {
                $switching->copyContractRooms($oldContract, $newContract, $performedBy, $billingGroupIdMap);
            }

            if ($needsRentals) {
                $switching->copyContractRentals($oldContract, $newContract, $performedBy);
            }

            if ($needsContractSurveys) {
                $switching->copyContractSurveys($oldContract, $newContract, $performedBy, $missingSurveyIds->all());
            }

            DB::commit();

            Log::info('Contract Switching: Backfilled missing contract structure', [
                'switching_id' => $switching->id,
                'old_contract_id' => $oldContract->id,
                'new_contract_id' => $newContract->id,
                'copied_billing_groups' => $needsBillingGroups,
                'synced_existing_billing_group_buildings' => $syncedExistingBillingGroupBuildings,
                'copied_rooms' => $needsRooms,
                'copied_rentals' => $needsRentals,
                'copied_contract_surveys' => $needsContractSurveys,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::warning('Contract Switching: Failed to backfill missing contract structure', [
                'switching_id' => $switching->id,
                'new_contract_id' => $newContract->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Copy contract rooms from old contract to new contract
     */
    protected function copyContractRooms($oldContract, $newContract, ?int $performedBy = null, array $billingGroupIdMap = []): array
    {
        $oldContract->loadMissing('contractRooms');

        if ($oldContract->contractRooms->count() === 0) {
            return [];
        }

        $performedBy = $this->resolvePerformedBy($performedBy);
        $contractRoomIdMap = [];

        foreach ($oldContract->contractRooms as $room) {
            $newRoom = $room->replicate([
                'id',
                'contract_id',
                'billing_group_id',
                'source_contract_id',
                'source_contract_room_id',
                'created_at',
                'updated_at',
            ]);

            $newRoom->contract_id = $newContract->id;
            $newRoom->billing_group_id = $room->billing_group_id
                ? ($billingGroupIdMap[$room->billing_group_id] ?? null)
                : null;
            $newRoom->source_contract_id = $room->source_contract_id ?: $oldContract->id;
            $newRoom->source_contract_room_id = $room->source_contract_room_id ?: $room->id;
            $newRoom->created_by = $performedBy;
            $newRoom->updated_by = $performedBy;
            $newRoom->save();

            $contractRoomIdMap[$room->id] = $newRoom->id;
        }

        return $contractRoomIdMap;
    }

    /**
     * Copy billing groups from old contract to new contract
     */
    protected function copyBillingGroups($oldContract, $newContract, ?int $performedBy = null): array
    {
        $oldContract->loadMissing('billingGroups.buildings');

        if ($oldContract->billingGroups->count() === 0) {
            return [];
        }

        $performedBy = $this->resolvePerformedBy($performedBy);
        $billingGroupIdMap = [];

        foreach ($oldContract->billingGroups as $billingGroup) {
            $newBillingGroup = $billingGroup->replicate([
                'id',
                'contract_id',
                'customer_id',
                'created_at',
                'updated_at',
                'deleted_at',
            ]);

            $newBillingGroup->contract_id = $newContract->id;
            $newBillingGroup->customer_id = $newContract->customer_id;
            $newBillingGroup->created_by = $performedBy;
            $newBillingGroup->updated_by = $performedBy;
            $newBillingGroup->save();

            if ($billingGroup->relationLoaded('buildings') && $billingGroup->buildings->isNotEmpty()) {
                $pivotRows = [];
                foreach ($billingGroup->buildings as $building) {
                    $pivotRows[$building->id] = [
                        'billing_amount' => $building->pivot->billing_amount,
                        'notes' => $building->pivot->notes,
                        'is_active' => $building->pivot->is_active,
                        'created_by' => $performedBy,
                        'updated_by' => $performedBy,
                        'created_at' => $building->pivot->created_at ?? now(),
                        'updated_at' => $building->pivot->updated_at ?? now(),
                    ];
                }

                $newBillingGroup->buildings()->sync($pivotRows);
            }

            $billingGroupIdMap[$billingGroup->id] = $newBillingGroup->id;
        }

        return $billingGroupIdMap;
    }

    /**
     * Copy contract rentals from old contract to new contract
     */
    protected function copyContractRentals($oldContract, $newContract, ?int $performedBy = null): array
    {
        $oldContract->loadMissing('contractRentals');

        if ($oldContract->contractRentals->count() === 0) {
            return [];
        }

        $performedBy = $this->resolvePerformedBy($performedBy);
        $contractRentalIdMap = [];

        foreach ($oldContract->contractRentals as $rental) {
            $newRental = $rental->replicate([
                'id',
                'contract_id',
                'created_at',
                'updated_at',
            ]);

            $newRental->contract_id = $newContract->id;
            $newRental->created_by = $performedBy;
            $newRental->updated_by = $performedBy;
            $newRental->save();

            $contractRentalIdMap[$rental->id] = $newRental->id;
        }

        return $contractRentalIdMap;
    }

    /**
     * Copy contract survey links from old contract to new contract.
     */
    protected function copyContractSurveys($oldContract, $newContract, ?int $performedBy = null, array $surveyIds = []): void
    {
        $oldContract->loadMissing('contractSurveys');

        if ($oldContract->contractSurveys->count() === 0) {
            return;
        }

        $performedBy = $this->resolvePerformedBy($performedBy);
        $existingSurveyIds = $newContract->contractSurveys()->pluck('survey_id')->filter()->all();

        foreach ($oldContract->contractSurveys as $contractSurvey) {
            if (!$contractSurvey->survey_id) {
                continue;
            }

            if (!empty($surveyIds) && !in_array($contractSurvey->survey_id, $surveyIds, true)) {
                continue;
            }

            if (in_array($contractSurvey->survey_id, $existingSurveyIds, true)) {
                continue;
            }

            ContractSurvey::create([
                'contract_id' => $newContract->id,
                'survey_id' => $contractSurvey->survey_id,
                'added_at' => $contractSurvey->added_at ?? now(),
                'added_by' => $performedBy,
                'sort_order' => $contractSurvey->sort_order,
            ]);

            $existingSurveyIds[] = $contractSurvey->survey_id;
        }
    }

    protected function getEffectiveDate(): Carbon
    {
        return $this->executed_at
            ? Carbon::parse($this->executed_at)->startOfDay()
            : now()->startOfDay();
    }

    protected function calculateResetEndDate(Contract $oldContract, Carbon $effectiveDate): string
    {
        $start = $oldContract->start_date ? Carbon::parse($oldContract->start_date) : null;
        $end = $oldContract->end_date ? Carbon::parse($oldContract->end_date) : null;

        if ($start && $end && $end->gte($start)) {
            return $effectiveDate->copy()
                ->addDays($start->diffInDays($end))
                ->toDateString();
        }

        $rentalPeriod = $oldContract->quotation->rental_period ?? null;
        if ($rentalPeriod && preg_match('/(\d+)\s*(hari|day|days|bulan|month|months|tahun|year|years)/i', strtolower($rentalPeriod), $matches)) {
            $amount = (int) $matches[1];
            $unit = strtolower($matches[2]);

            return match (true) {
                in_array($unit, ['hari', 'day', 'days'], true) => $effectiveDate->copy()->addDays($amount)->subDay()->toDateString(),
                in_array($unit, ['tahun', 'year', 'years'], true) => $effectiveDate->copy()->addYears($amount)->subDay()->toDateString(),
                default => $effectiveDate->copy()->addMonths($amount)->subDay()->toDateString(),
            };
        }

        return $effectiveDate->copy()->addMonths(12)->subDay()->toDateString();
    }

    protected function transferFutureOperationalData(
        Contract $oldContract,
        Contract $newContract,
        Carbon $effectiveDate,
        ?int $performedBy,
        array $contractRoomIdMap,
        array $contractRentalIdMap
    ): array {
        $performedBy = $this->resolvePerformedBy($performedBy);
        $transferableStatuses = ['scheduled', 'new_job', 'new job'];

        $futureJobs = JobSchedule::with(['jobAdvice.rooms', 'jobScheduleRooms'])
            ->whereDate('schedule_date', '>=', $effectiveDate->toDateString())
            ->whereIn(DB::raw('LOWER(TRIM(status))'), $transferableStatuses)
            ->where(function ($query) use ($oldContract) {
                $query->whereHas('jobAdvice', function ($jobAdviceQuery) use ($oldContract) {
                    $jobAdviceQuery->where('contract_id', $oldContract->id);
                });

                if ($oldContract->contract_number) {
                    $query->orWhere('contract_number', $oldContract->contract_number);
                }
            })
            ->get();

        $jobAdvicesCloned = 0;
        $jobAdviceMap = [];

        foreach ($futureJobs->groupBy('job_advice_id') as $jobAdviceId => $jobsForAdvice) {
            $jobAdvice = $jobsForAdvice->first()->jobAdvice;
            if (!$jobAdvice) {
                $this->updateFutureJobsContractFields($jobsForAdvice, $newContract, $performedBy);
                continue;
            }

            $allJobIdsForAdvice = $jobAdvice->jobSchedules()->pluck('id');
            $futureJobIds = $jobsForAdvice->pluck('id');
            $targetJobAdvice = $jobAdvice;

            if ($allJobIdsForAdvice->diff($futureJobIds)->isNotEmpty()) {
                $targetJobAdvice = $this->cloneJobAdviceForNewContract(
                    $jobAdvice,
                    $newContract,
                    $performedBy,
                    $contractRoomIdMap,
                    $contractRentalIdMap
                );
                $jobAdvicesCloned++;
            } else {
                $targetJobAdvice->update([
                    'contract_id' => $newContract->id,
                    'customer_id' => $newContract->customer_id,
                    'company_name' => $newContract->customer->name ?? $targetJobAdvice->company_name,
                    'updated_by' => $performedBy,
                ]);
                $this->remapExistingJobAdviceRoomSources($targetJobAdvice, $contractRoomIdMap, $contractRentalIdMap, $performedBy);
            }

            $jobAdviceMap[$jobAdviceId] = $targetJobAdvice->id;
            $this->updateFutureJobsContractFields($jobsForAdvice, $newContract, $performedBy, $targetJobAdvice->id);
            $this->remapFutureJobRooms($jobsForAdvice, $jobAdvice, $targetJobAdvice);
        }

        $activeUnitsReassigned = $this->reassignActiveUnitsOnWall($oldContract, $newContract, $performedBy);
        $existingInvoicesPreserved = $this->countPreservedInvoices($oldContract);

        return [
            'future_jobs_transferred' => $futureJobs->count(),
            'job_advices_cloned' => $jobAdvicesCloned,
            'job_advices_relinked' => count($jobAdviceMap) - $jobAdvicesCloned,
            'active_units_reassigned' => $activeUnitsReassigned,
            'existing_invoices_preserved' => $existingInvoicesPreserved,
        ];
    }

    protected function cloneJobAdviceForNewContract(
        JobAdvice $jobAdvice,
        Contract $newContract,
        int $performedBy,
        array $contractRoomIdMap,
        array $contractRentalIdMap
    ): JobAdvice {
        $newJobAdvice = $jobAdvice->replicate([
            'id',
            'job_advice_number',
            'contract_id',
            'customer_id',
            'company_name',
            'created_at',
            'updated_at',
            'deleted_at',
        ]);

        $newJobAdvice->contract_id = $newContract->id;
        $newJobAdvice->customer_id = $newContract->customer_id;
        $newJobAdvice->company_name = $newContract->customer->name ?? $jobAdvice->company_name;
        $newJobAdvice->job_advice_number = app(DocumentNumberService::class)->generate(
            'job_advice',
            null,
            null,
            $newContract->id,
            $jobAdvice->quotation_id ?? null,
            null,
            null,
            null,
            $jobAdvice->expected_date ?? null
        );
        $newJobAdvice->notes = trim(($jobAdvice->notes ?? '') . "\nSwitched from contract {$this->oldContract->contract_number} via {$this->switching_number}.");
        $newJobAdvice->created_by = $performedBy;
        $newJobAdvice->updated_by = $performedBy;
        $newJobAdvice->save();

        $jobAdvice->loadMissing('rooms');
        foreach ($jobAdvice->rooms as $room) {
            $newRoom = $room->replicate([
                'id',
                'job_advice_id',
                'contract_room_id',
                'contract_rental_id',
                'install_job_schedule_id',
                'service_job_schedule_id',
                'remove_job_schedule_id',
                'created_at',
                'updated_at',
                'deleted_at',
            ]);

            $newRoom->job_advice_id = $newJobAdvice->id;
            $newRoom->contract_room_id = $room->contract_room_id
                ? ($contractRoomIdMap[$room->contract_room_id] ?? $room->contract_room_id)
                : null;
            $newRoom->contract_rental_id = $room->contract_rental_id
                ? ($contractRentalIdMap[$room->contract_rental_id] ?? $room->contract_rental_id)
                : null;
            $newRoom->install_job_schedule_id = null;
            $newRoom->service_job_schedule_id = null;
            $newRoom->remove_job_schedule_id = null;
            $newRoom->created_by = $performedBy;
            $newRoom->updated_by = $performedBy;
            $newRoom->save();
        }

        return $newJobAdvice->load('rooms');
    }

    protected function updateFutureJobsContractFields($jobs, Contract $newContract, int $performedBy, ?int $jobAdviceId = null): void
    {
        foreach ($jobs as $job) {
            $updates = [
                'contract_number' => $newContract->contract_number,
                'company_name' => $newContract->customer->name ?? $job->company_name,
                'updated_by' => $performedBy,
            ];

            if ($jobAdviceId) {
                $updates['job_advice_id'] = $jobAdviceId;
            }

            if (Schema::hasColumn('job_schedules', 'contract_id')) {
                $updates['contract_id'] = $newContract->id;
            }

            if (Schema::hasColumn('job_schedules', 'customer_id')) {
                $updates['customer_id'] = $newContract->customer_id;
            }

            $job->forceFill($updates)->save();
        }
    }

    protected function remapExistingJobAdviceRoomSources(
        JobAdvice $jobAdvice,
        array $contractRoomIdMap,
        array $contractRentalIdMap,
        int $performedBy
    ): void {
        $jobAdvice->loadMissing('rooms');

        foreach ($jobAdvice->rooms as $room) {
            $updates = ['updated_by' => $performedBy];
            $hasUpdates = false;

            if ($room->contract_room_id && isset($contractRoomIdMap[$room->contract_room_id])) {
                $updates['contract_room_id'] = $contractRoomIdMap[$room->contract_room_id];
                $hasUpdates = true;
            }

            if ($room->contract_rental_id && isset($contractRentalIdMap[$room->contract_rental_id])) {
                $updates['contract_rental_id'] = $contractRentalIdMap[$room->contract_rental_id];
                $hasUpdates = true;
            }

            if ($hasUpdates) {
                $room->update($updates);
            }
        }
    }

    protected function remapFutureJobRooms($jobs, JobAdvice $oldJobAdvice, JobAdvice $newJobAdvice): void
    {
        if ($oldJobAdvice->id === $newJobAdvice->id) {
            return;
        }

        $oldRooms = $oldJobAdvice->rooms->values();
        $newRooms = $newJobAdvice->rooms->values();

        foreach ($oldRooms as $index => $oldRoom) {
            $newRoom = $newRooms->get($index);
            if (!$newRoom) {
                continue;
            }

            $jobScheduleRoomIds = JobScheduleRoom::whereIn('job_schedule_id', $jobs->pluck('id'))
                ->where('job_advice_room_id', $oldRoom->id)
                ->pluck('id');

            if ($jobScheduleRoomIds->isEmpty()) {
                continue;
            }

            JobScheduleRoom::whereIn('id', $jobScheduleRoomIds)->update([
                'job_advice_room_id' => $newRoom->id,
                'updated_at' => now(),
            ]);

            if (Schema::hasTable('job_schedule_room_rentals')) {
                DB::table('job_schedule_room_rentals')
                    ->whereIn('job_schedule_room_id', $jobScheduleRoomIds)
                    ->where('job_advice_room_id', $oldRoom->id)
                    ->update([
                        'job_advice_room_id' => $newRoom->id,
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    protected function reassignActiveUnitsOnWall(Contract $oldContract, Contract $newContract, int $performedBy): int
    {
        $oldContract->loadMissing('contractRooms');
        $roomIds = $oldContract->contractRooms->pluck('room_id')->filter()->unique()->values();

        if ($roomIds->isEmpty()) {
            return 0;
        }

        $query = UnitOnWall::where('customer_id', $oldContract->customer_id)
            ->whereIn('room_id', $roomIds)
            ->whereIn('status', ['active', 'installed', 'on_wall', 'on wall', 'onwall']);

        $count = (clone $query)->count();

        if ($count === 0) {
            return 0;
        }

        $query->update([
            'customer_id' => $newContract->customer_id,
            'company_name' => $newContract->customer->name ?? null,
            'updated_by' => $performedBy,
            'updated_at' => now(),
        ]);

        return $count;
    }

    protected function countPreservedInvoices(Contract $oldContract): int
    {
        if (!Schema::hasTable('invoices')) {
            return 0;
        }

        return DB::table('invoices')
            ->where(function ($query) use ($oldContract) {
                if (Schema::hasColumn('invoices', 'contract_id')) {
                    $query->where('contract_id', $oldContract->id);
                }

                if ($oldContract->contract_number && Schema::hasColumn('invoices', 'contract_number')) {
                    $method = Schema::hasColumn('invoices', 'contract_id') ? 'orWhere' : 'where';
                    $query->{$method}('contract_number', $oldContract->contract_number);
                }
            })
            ->count();
    }

    /**
     * Map billing groups by name when they already exist on the new contract.
     */
    protected function mapExistingBillingGroups($oldContract, $newContract): array
    {
        $oldContract->loadMissing('billingGroups');
        $newContract->loadMissing('billingGroups');

        $newGroupsByName = $newContract->billingGroups
            ->filter(fn ($group) => !empty($group->billing_group_name))
            ->groupBy(fn ($group) => mb_strtolower(trim($group->billing_group_name)));

        $billingGroupIdMap = [];

        foreach ($oldContract->billingGroups as $billingGroup) {
            $nameKey = mb_strtolower(trim((string) $billingGroup->billing_group_name));
            $matchedGroup = $newGroupsByName->get($nameKey)?->first();

            if ($matchedGroup) {
                $billingGroupIdMap[$billingGroup->id] = $matchedGroup->id;
            }
        }

        return $billingGroupIdMap;
    }

    /**
     * Sync missing building pivots into existing billing groups on the new contract.
     */
    protected function syncExistingBillingGroupBuildings($oldContract, $newContract, array $billingGroupIdMap, ?int $performedBy = null): void
    {
        $oldContract->loadMissing('billingGroups.buildings');
        $newContract->loadMissing('billingGroups.buildings');

        $performedBy = $this->resolvePerformedBy($performedBy);
        $newGroupsById = $newContract->billingGroups->keyBy('id');

        foreach ($oldContract->billingGroups as $oldBillingGroup) {
            $newBillingGroupId = $billingGroupIdMap[$oldBillingGroup->id] ?? null;
            $newBillingGroup = $newBillingGroupId ? $newGroupsById->get($newBillingGroupId) : null;

            if (!$newBillingGroup || !$oldBillingGroup->relationLoaded('buildings')) {
                continue;
            }

            $existingBuildingIds = $newBillingGroup->buildings->pluck('id')->all();
            $pivotRows = [];

            foreach ($oldBillingGroup->buildings as $building) {
                if (in_array($building->id, $existingBuildingIds, true)) {
                    continue;
                }

                $pivotRows[$building->id] = [
                    'billing_amount' => $building->pivot->billing_amount,
                    'notes' => $building->pivot->notes,
                    'is_active' => $building->pivot->is_active,
                    'created_by' => $performedBy,
                    'updated_by' => $performedBy,
                    'created_at' => $building->pivot->created_at ?? now(),
                    'updated_at' => $building->pivot->updated_at ?? now(),
                ];
            }

            if (!empty($pivotRows)) {
                $newBillingGroup->buildings()->syncWithoutDetaching($pivotRows);
                $newBillingGroup->load('buildings');
            }
        }
    }

    protected function hasMissingBillingGroupBuildings($oldContract, $newContract, array $billingGroupIdMap): bool
    {
        if (empty($billingGroupIdMap)) {
            return false;
        }

        $oldContract->loadMissing('billingGroups.buildings');
        $newContract->loadMissing('billingGroups.buildings');

        $newGroupsById = $newContract->billingGroups->keyBy('id');

        foreach ($oldContract->billingGroups as $oldBillingGroup) {
            $newBillingGroupId = $billingGroupIdMap[$oldBillingGroup->id] ?? null;
            $newBillingGroup = $newBillingGroupId ? $newGroupsById->get($newBillingGroupId) : null;

            if (!$newBillingGroup) {
                continue;
            }

            $missingBuildingIds = $oldBillingGroup->buildings
                ->pluck('id')
                ->diff($newBillingGroup->buildings->pluck('id'));

            if ($missingBuildingIds->isNotEmpty()) {
                return true;
            }
        }

        return false;
    }

    protected function resolvePerformedBy(?int $performedBy = null): int
    {
        return $performedBy
            ?? auth()->id()
            ?? $this->executed_by
            ?? $this->updated_by
            ?? $this->created_by
            ?? 28;
    }

    /**
     * Generate new contract number
     */
    protected function generateNewContractNumber()
    {
        $prefix = 'CT-' . date('Ymd');
        $count = Contract::whereDate('created_at', today())->count() + 1;
        return $prefix . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate unique switching number
     */
    public static function generateSwitchingNumber()
    {
        $prefix = 'CSW-' . date('Ymd');
        $count = self::whereDate('created_at', today())->count() + 1;
        return $prefix . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
