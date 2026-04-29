<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;

class ContractRemoval extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail;

    protected $fillable = [
        'removal_number',
        'contract_id',
        'contract_room_id',
        'building_id',
        'room_id',
        'removal_reason',
        'removal_notes',
        'removal_date',
        'has_active_jobs',
        'active_jobs_data',
        'affect_room_rental',
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
        'removal_date' => 'date',
        'has_active_jobs' => 'boolean',
        'affect_room_rental' => 'boolean',
        'active_jobs_data' => 'array',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    // Constants
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

    public function scopeWithActiveJobs($query)
    {
        return $query->where('has_active_jobs', true);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
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
            self::STATUS_COMPLETED => 'badge-success',
            self::STATUS_CANCELLED => 'badge-dark'
        ];
        return $badges[$this->status] ?? 'badge-secondary';
    }

    // Methods
    public function submitForApproval()
    {
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
        
        \Log::info("Contract Removal approved: {$this->removal_number}");
    }

    public function reject($approvedBy, $notes = null)
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by' => $approvedBy,
            'approval_notes' => $notes,
            'approved_at' => now()
        ]);
        
        \Log::info("Contract Removal rejected: {$this->removal_number}");
    }

    public function schedule($scheduledDate)
    {
        $this->update([
            'status' => self::STATUS_SCHEDULED,
            'removal_date' => $scheduledDate
        ]);
    }

    public function complete($completedBy)
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_by' => $completedBy,
            'completed_at' => now()
        ]);
        
        // Update ContractRoom status (MOM6: Affect room rental)
        if ($this->affect_room_rental && $this->contract_room_id) {
            $this->contractRoom->update([
                'is_active' => false,
                'removed_at' => now(),
                'removal_notes' => "Removed via {$this->removal_number}"
            ]);
            
            \Log::info("ContractRoom deactivated: Room #{$this->contract_room_id}");
        }
    }

    public function cancel()
    {
        $this->update([
            'status' => self::STATUS_CANCELLED
        ]);
    }

    /**
     * Check for active jobs (MOM6 Requirement)
     * Must ensure no active jobs before removal
     */
    public function checkActiveJobs()
    {
        $activeJobs = JobSchedule::where('contract_id', $this->contract_id)
            ->where(function($q) {
                $q->where('room_id', $this->room_id)
                  ->orWhereHas('jobAdvice', function($ja) {
                      $ja->where('contract_room_id', $this->contract_room_id);
                  });
            })
            ->whereIn('status', ['scheduled', 'in_progress', 'pending'])
            ->get();

        $this->update([
            'has_active_jobs' => $activeJobs->count() > 0,
            'active_jobs_data' => $activeJobs->map(function($job) {
                return [
                    'job_number' => $job->job_number,
                    'status' => $job->status,
                    'scheduled_date' => $job->scheduled_date,
                    'type' => $job->type
                ];
            })->toArray()
        ]);

        return $activeJobs->count() === 0;
    }

    /**
     * Generate unique removal number (MOM6 Format: CR-YYYYMMDD-XXXX)
     */
    public static function generateRemovalNumber()
    {
        $prefix = 'CR-' . date('Ymd');
        $count = self::whereDate('created_at', today())->count() + 1;
        return $prefix . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}

