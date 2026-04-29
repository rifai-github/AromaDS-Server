<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;

class ContractTermination extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail;

    protected $fillable = [
        'termination_number',
        'contract_id',
        'new_contract_id',
        'customer_id',
        'reason',
        'penalty_amount',
        'notes',
        'status',
        'approval_notes',
        'requested_at',
        'approved_at',
        'requested_by',
        'approved_by',
        'created_by',
        'updated_by',
        'auto_generated',
    ];

    protected $casts = [
        'penalty_amount' => 'decimal:2',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime'
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_APPROVAL = 'pending_approval'; // Match database enum
    const STATUS_WAITING_FOR_APPROVAL = 'pending_approval'; // Alias for backward compatibility
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_TERM_RENEW = 'term-renew'; // Contract diakhiri karena merge ke contract baru

    // Relationships
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Contract baru yang menggantikan contract ini (saat term-renew/merge)
     */
    public function newContract()
    {
        return $this->belongsTo(Contract::class, 'new_contract_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
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

    public function scopeByType($query, $type)
    {
        return $query->where('termination_type', $type);
    }

    public function scopeByContract($query, $contractId)
    {
        return $query->where('contract_id', $contractId);
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

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeEarlyTermination($query)
    {
        return $query->where('termination_type', self::TYPE_EARLY_TERMINATION);
    }

    public function scopeNaturalExpiry($query)
    {
        return $query->where('termination_type', self::TYPE_NATURAL_EXPIRY);
    }

    public function scopeAutoTermination($query)
    {
        return $query->where('auto_termination', true);
    }

    public function scopePendingSettlement($query)
    {
        return $query->where('settlement_paid', false)
                    ->whereNotNull('final_settlement_amount')
                    ->whereIn('status', [self::STATUS_APPROVED, self::STATUS_IN_PROGRESS]);
    }

    public function scopePendingEquipmentReturn($query)
    {
        return $query->where('requires_equipment_return', true)
                    ->where('equipment_returned', false)
                    ->whereIn('status', [self::STATUS_APPROVED, self::STATUS_IN_PROGRESS]);
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        $statuses = [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_WAITING_FOR_APPROVAL => 'Waiting for Approval',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
        ];
        return $statuses[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            self::STATUS_DRAFT => 'badge-secondary',
            self::STATUS_WAITING_FOR_APPROVAL => 'badge-warning',
            self::STATUS_APPROVED => 'badge-success',
            self::STATUS_REJECTED => 'badge-danger',
        ];
        return $badges[$this->status] ?? 'badge-secondary';
    }

    public function getTypeTextAttribute()
    {
        $types = [
            self::TYPE_NATURAL_EXPIRY => 'Natural Expiry',
            self::TYPE_EARLY_TERMINATION => 'Early Termination',
            self::TYPE_MUTUAL_AGREEMENT => 'Mutual Agreement',
            self::TYPE_BREACH => 'Contract Breach',
            self::TYPE_NON_PAYMENT => 'Non-Payment',
            self::TYPE_FORCE_MAJEURE => 'Force Majeure'
        ];
        return $types[$this->termination_type] ?? ucfirst(str_replace('_', ' ', $this->termination_type));
    }

    public function getIsEarlyAttribute()
    {
        return $this->days_early > 0;
    }

    // Boolean accessors
    public function getIsDraftAttribute()
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function getIsPendingApprovalAttribute()
    {
        return $this->status === self::STATUS_PENDING_APPROVAL;
    }

    public function getIsApprovedAttribute()
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function getIsRejectedAttribute()
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function getIsInProgressAttribute()
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function getIsCompletedAttribute()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function getIsCancelledAttribute()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    // Methods
    public function submitForApproval()
    {
        $this->update([
            'status' => self::STATUS_PENDING_APPROVAL
        ]);
    }

    public function approve($approvedBy, $notes = null)
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => $approvedBy,
            'approval_notes' => $notes
        ]);

        // Auto-calculate settlement
        $this->calculateFinalSettlement();

        // Auto-generate Job Advice for equipment removal if needed
        if ($this->requires_equipment_return && !$this->removal_job_advice_id) {
            $this->generateRemovalJobAdvice();
        }

        // Mark as in progress
        $this->update(['status' => self::STATUS_IN_PROGRESS]);
    }

    public function reject($rejectedBy, $reason)
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'rejected_at' => now(),
            'rejected_by' => $rejectedBy,
            'rejection_reason' => $reason
        ]);
    }

    public function markSettlementPaid()
    {
        $this->update([
            'settlement_paid' => true,
            'settlement_paid_at' => now()
        ]);
        $this->checkCompletionStatus();
    }

    public function markEquipmentReturned($notes = null)
    {
        $this->update([
            'equipment_returned' => true,
            'equipment_returned_at' => now(),
            'equipment_return_notes' => $notes
        ]);
        $this->checkCompletionStatus();
    }

    public function checkCompletionStatus()
    {
        // Check if all requirements are met
        $settlementOk = !$this->final_settlement_amount || $this->settlement_paid;
        $equipmentOk = !$this->requires_equipment_return || $this->equipment_returned;

        if ($settlementOk && $equipmentOk && $this->isInProgress) {
            $this->complete();
        }
    }

    public function complete()
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now()
        ]);

        // Update contract status
        if ($this->contract) {
            $this->contract->update(['status' => 'terminated']);
        }
    }

    public function cancel()
    {
        $this->update([
            'status' => self::STATUS_CANCELLED
        ]);
    }

    /**
     * Calculate final settlement amount
     */
    public function calculateFinalSettlement()
    {
        $total = 0;

        // Add outstanding balance
        if ($this->outstanding_balance) {
            $total += $this->outstanding_balance;
        }

        // Add early termination penalty
        if ($this->early_termination_penalty) {
            $total += $this->early_termination_penalty;
        }

        // Subtract equipment return deposit
        if ($this->equipment_return_deposit) {
            $total -= $this->equipment_return_deposit;
        }

        $this->update([
            'final_settlement_amount' => $total
        ]);

        return $total;
    }

    /**
     * Calculate early termination penalty based on contract terms
     */
    public function calculateEarlyTerminationPenalty()
    {
        if (!$this->isEarly) {
            return 0;
        }

        $contract = $this->contract;
        if (!$contract) {
            return 0;
        }

        // Example: 3 months of rental value as penalty
        // You can customize this based on business rules
        $monthlyValue = $contract->total_value / 12;
        $penalty = $monthlyValue * 3;

        $this->update([
            'early_termination_penalty' => $penalty
        ]);

        return $penalty;
    }

    /**
     * Generate Job Advice for equipment removal
     */
    private function generateRemovalJobAdvice()
    {
        try {
            $contract = $this->contract;
            $customer = $this->customer;

            $jobAdviceNumber = app(\App\Services\DocumentNumberService::class)->generate('job_advice', null, null, $contract->id);

            $jobAdvice = JobAdvice::create([
                'job_advice_number' => $jobAdviceNumber,
                'contract_id' => $contract->id,
                'customer_id' => $customer->id,
                'company_name' => $customer->name,
                'type' => 'remove',
                'reference_number' => $this->termination_number,
                'expected_date' => $this->termination_date ?? now()->addDays(7),
                'status' => 'approved',
                'date_approval' => now(),
                'approved_by' => $this->approved_by,
                'notes' => "Auto-generated from Contract Termination: {$this->termination_number}. Type: {$this->termination_type}.",
                'submitted_by' => $this->approved_by,
                'created_by' => $this->approved_by,
            ]);

            $this->update(['removal_job_advice_id' => $jobAdvice->id]);

            \Log::info("Job Advice auto-generated for Contract Termination {$this->termination_number}: {$jobAdvice->job_advice_number}");
            return $jobAdvice;

        } catch (\Exception $e) {
            \Log::error("Failed to auto-generate Job Advice for Contract Termination {$this->termination_number}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate unique termination number
     */
    public static function generateTerminationNumber()
    {
        $prefix = 'TRM-' . date('Ymd');
        $count = self::whereDate('created_at', today())->count() + 1;
        return $prefix . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Auto-create terminations for expired contracts (Command/Schedule)
     */
    public static function autoCreateForExpiredContracts()
    {
        $expiredContracts = Contract::where('status', 'active')
            ->whereDate('end_date', '<', now())
            ->whereDoesntHave('terminations', function ($query) {
                $query->where('status', '!=', self::STATUS_REJECTED)
                      ->where('status', '!=', self::STATUS_CANCELLED);
            })
            ->get();

        $created = [];
        foreach ($expiredContracts as $contract) {
            $termination = self::create([
                'termination_number' => self::generateTerminationNumber(),
                'contract_id' => $contract->id,
                'customer_id' => $contract->customer_id,
                'termination_type' => self::TYPE_NATURAL_EXPIRY,
                'contract_end_date' => $contract->end_date,
                'termination_date' => $contract->end_date,
                'days_early' => 0,
                'termination_reason' => 'Contract has reached its natural expiry date',
                'requires_equipment_return' => true,
                'status' => self::STATUS_DRAFT,
                'auto_termination' => true,
                'created_by' => 1 // System user
            ]);
            $created[] = $termination;
        }

        return $created;
    }
}

