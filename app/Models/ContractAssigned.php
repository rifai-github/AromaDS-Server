<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContractAssigned extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail;

    protected $table = 'contract_assigned';

    protected $fillable = [
        'switching_number',
        'old_contract_id',
        'old_marketing_id',
        'new_marketing_id',
        'switching_reason',
        'switching_description',
        'switching_notes',
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
    const STATUS_COMPLETED = 'completed';

    // Relationships
    public function oldContract()
    {
        return $this->belongsTo(Contract::class, 'old_contract_id');
    }

    public function oldMarketing()
    {
        return $this->belongsTo(User::class, 'old_marketing_id');
    }

    public function newMarketing()
    {
        return $this->belongsTo(User::class, 'new_marketing_id');
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
            self::STATUS_COMPLETED => 'badge-success'
        ];
        return $badges[$this->status] ?? 'badge-secondary';
    }

    // Boolean accessors
    public function getIsDraftAttribute(): bool { return $this->status === self::STATUS_DRAFT; }
    public function getIsPendingApprovalAttribute(): bool { return $this->status === self::STATUS_PENDING_APPROVAL; }
    public function getIsApprovedAttribute(): bool { return $this->status === self::STATUS_APPROVED; }
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
     * Execute contract switching - Transfer marketing responsibility
     */
    public function execute($executedBy)
    {
        if (!$this->isApproved) {
            throw new \Exception('Switching must be approved before execution');
        }

        try {
            DB::beginTransaction();

            $this->update([
                'executed_at' => now(),
                'executed_by' => $executedBy
            ]);

            // Update contract's marketing_id
            $contract = $this->oldContract;
            $contract->update([
                'marketing_id' => $this->new_marketing_id,
                'notes' => ($contract->notes ?? '') . "\n\nMarketing transferred from {$this->oldMarketing->name} to {$this->newMarketing->name} on " . now()->format('Y-m-d H:i:s') . " (Switching: {$this->switching_number})"
            ]);

            // Complete switching
            $this->update([
                'status' => self::STATUS_COMPLETED,
                'completed_at' => now()
            ]);

            DB::commit();

            Log::info("Contract Switching executed: {$this->switching_number}", [
                'contract' => $contract->contract_number,
                'old_marketing' => $this->oldMarketing->name,
                'new_marketing' => $this->newMarketing->name
            ]);

            return $contract;

        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error executing contract switching: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate unique switching number with format: [BRANCH]-CAS/YY-MM/NNNN
     */
    public static function generateSwitchingNumber()
    {
        // Get branch code from authenticated user
        $branchCode = auth()->user()->branch->branch_code ?? 'JKT';
        
        // Get year and month
        $year = date('y');  // 2-digit year
        $month = date('m'); // 2-digit month
        
        // Get count for this month
        $startOfMonth = now()->startOfMonth();
        $count = self::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count() + 1;
        
        // Format: BRANCH-CAS/YY-MM/NNNN
        return $branchCode . '-CAS/' . $year . '-' . $month . '/' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
