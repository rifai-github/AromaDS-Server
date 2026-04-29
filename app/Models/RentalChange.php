<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;

class RentalChange extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail;

    protected $fillable = [
        'change_number',
        'contract_id',
        'contract_room_id',
        'building_id',
        'room_id',
        'old_rental_id',
        'new_rental_id',
        'change_reason',
        'change_notes',
        'effective_date',
        'has_active_jobs',
        'active_jobs_data',
        'affect_room_rental_config',
        'old_rental_price',
        'new_rental_price',
        'price_difference',
        'status',
        'approval_notes',
        'job_advice_id',
        'requested_at',
        'approved_at',
        'completed_at',
        'requested_by',
        'approved_by',
        'completed_by',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'effective_date' => 'date',
        'has_active_jobs' => 'boolean',
        'affect_room_rental_config' => 'boolean',
        'old_rental_price' => 'decimal:2',
        'new_rental_price' => 'decimal:2',
        'price_difference' => 'decimal:2',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    // Constants for status
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    // Relationships
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function contractRoom()
    {
        return $this->belongsTo(ContractRoom::class);
    }

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function oldRental()
    {
        return $this->belongsTo(MasterRental::class, 'old_rental_id');
    }

    public function newRental()
    {
        return $this->belongsTo(MasterRental::class, 'new_rental_id');
    }

    public function jobAdvice()
    {
        return $this->belongsTo(JobAdvice::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
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

    public function scopeByContract($query, $contractId)
    {
        return $query->where('contract_id', $contractId);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeWithActiveJobs($query)
    {
        return $query->where('has_active_jobs', true);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        $statuses = [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING => 'Pending Approval',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled'
        ];
        return $statuses[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            self::STATUS_DRAFT => 'badge-secondary',
            self::STATUS_PENDING => 'badge-warning',
            self::STATUS_APPROVED => 'badge-success',
            self::STATUS_REJECTED => 'badge-danger',
            self::STATUS_SCHEDULED => 'badge-info',
            self::STATUS_COMPLETED => 'badge-primary',
            self::STATUS_CANCELLED => 'badge-dark'
        ];
        return $badges[$this->status] ?? 'badge-secondary';
    }

    public function getActiveJobsListAttribute()
    {
        if (!$this->active_jobs_data) {
            return [];
        }
        return json_decode($this->active_jobs_data, true) ?? [];
    }

    // Boolean accessors
    public function getIsDraftAttribute()
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function getIsPendingAttribute()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function getIsApprovedAttribute()
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function getIsRejectedAttribute()
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function getIsScheduledAttribute()
    {
        return $this->status === self::STATUS_SCHEDULED;
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
        // Check for active jobs before submitting
        $this->checkActiveJobs();

        $this->update([
            'status' => self::STATUS_PENDING,
            'requested_at' => now()
        ]);
    }

    public function approve($approvedBy, $notes = null)
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approvedBy,
            'approval_notes' => $notes,
            'approved_at' => now()
        ]);

        // TODO: If affect_room_rental_config, update ContractRoom
        // TODO: Auto-generate Job Advice if needed
    }

    public function reject($approvedBy, $notes = null)
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by' => $approvedBy,
            'approval_notes' => $notes,
            'approved_at' => now()
        ]);
    }

    public function schedule()
    {
        $this->update([
            'status' => self::STATUS_SCHEDULED
        ]);
    }

    public function complete($completedBy)
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_by' => $completedBy,
            'completed_at' => now()
        ]);

        // Update ContractRoom rental configuration
        if ($this->affect_room_rental_config && $this->contractRoom) {
            $this->contractRoom->update([
                'rental_id' => $this->new_rental_id,
                // Price update if needed
            ]);
            \Log::info("Rental updated for ContractRoom {$this->contract_room_id}: {$this->old_rental_id} -> {$this->new_rental_id}");
        }
    }

    public function cancel()
    {
        $this->update([
            'status' => self::STATUS_CANCELLED
        ]);
    }

    /**
     * Check for active jobs related to this contract room
     * MOM6 Requirement: Check tidak ada job aktif
     */
    public function checkActiveJobs()
    {
        $activeJobs = JobSchedule::where('contract_id', $this->contract_id)
            ->where('contract_room_id', $this->contract_room_id)
            ->whereIn('status', ['assigned', 'in_progress', 'suspend'])
            ->get();

        $hasActiveJobs = $activeJobs->count() > 0;

        $this->update([
            'has_active_jobs' => $hasActiveJobs,
            'active_jobs_data' => $hasActiveJobs ? json_encode($activeJobs->pluck('job_schedule_number')->toArray()) : null
        ]);

        return $hasActiveJobs;
    }

    /**
     * Calculate price difference
     */
    public function calculatePriceDifference()
    {
        if ($this->oldRental && $this->newRental) {
            $oldPrice = $this->oldRental->rental_price ?? 0;
            $newPrice = $this->newRental->rental_price ?? 0;
            $difference = $newPrice - $oldPrice;

            $this->update([
                'old_rental_price' => $oldPrice,
                'new_rental_price' => $newPrice,
                'price_difference' => $difference
            ]);

            return $difference;
        }

        return 0;
    }

    /**
     * Generate unique change number
     */
    public static function generateChangeNumber()
    {
        $prefix = 'RC-' . date('Ymd');
        $count = self::whereDate('created_at', today())->count() + 1;
        return $prefix . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}

