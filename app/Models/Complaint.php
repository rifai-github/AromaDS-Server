<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;

class Complaint extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail;

    protected $fillable = [
        'complaint_number',
        'customer_id',
        'contract_id',
        'job_schedule_id',
        'building_id',
        'room_id',
        'complaint_type',
        'priority',
        'subject',
        'description',
        'attachments',
        'status',
        'resolution_notes',
        'target_resolution_date',
        'resolved_at',
        'satisfaction_rating',
        'satisfaction_feedback',
        'requires_follow_up',
        'follow_up_date',
        'follow_up_notes',
        'job_advice_id',
        'reported_at',
        'acknowledged_at',
        'closed_at',
        'reported_by',
        'assigned_to',
        'resolved_by',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'attachments' => 'array',
        'target_resolution_date' => 'date',
        'resolved_at' => 'datetime',
        'satisfaction_rating' => 'integer',
        'requires_follow_up' => 'boolean',
        'follow_up_date' => 'date',
        'reported_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'closed_at' => 'datetime'
    ];

    // Constants for status
    const STATUS_OPEN = 'open';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_CLOSED = 'closed';
    const STATUS_REJECTED = 'rejected';

    // Constants for priority
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    // Constants for complaint type
    const TYPE_SERVICE_QUALITY = 'service_quality';
    const TYPE_PRODUCT_QUALITY = 'product_quality';
    const TYPE_AROMA_ISSUE = 'aroma_issue';
    const TYPE_UNIT_MALFUNCTION = 'unit_malfunction';
    const TYPE_BILLING = 'billing';
    const TYPE_STAFF_BEHAVIOR = 'staff_behavior';
    const TYPE_SCHEDULE = 'schedule';
    const TYPE_OTHER = 'other';

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function jobSchedule()
    {
        return $this->belongsTo(JobSchedule::class);
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

    public function reportedBy()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
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

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('complaint_type', $type);
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByContract($query, $contractId)
    {
        return $query->where('contract_id', $contractId);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeResolved($query)
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', self::STATUS_CLOSED);
    }

    public function scopeUrgent($query)
    {
        return $query->where('priority', self::PRIORITY_URGENT);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }

    public function scopeRequiresFollowUp($query)
    {
        return $query->where('requires_follow_up', true);
    }

    public function scopeOverdue($query)
    {
        return $query->where('target_resolution_date', '<', now())
            ->whereNotIn('status', [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        $statuses = [
            self::STATUS_OPEN => 'Open',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_RESOLVED => 'Resolved',
            self::STATUS_CLOSED => 'Closed',
            self::STATUS_REJECTED => 'Rejected'
        ];
        return $statuses[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            self::STATUS_OPEN => 'badge-warning',
            self::STATUS_IN_PROGRESS => 'badge-info',
            self::STATUS_RESOLVED => 'badge-success',
            self::STATUS_CLOSED => 'badge-secondary',
            self::STATUS_REJECTED => 'badge-danger'
        ];
        return $badges[$this->status] ?? 'badge-secondary';
    }

    public function getPriorityTextAttribute()
    {
        $priorities = [
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_MEDIUM => 'Medium',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_URGENT => 'Urgent'
        ];
        return $priorities[$this->priority] ?? ucfirst($this->priority);
    }

    public function getPriorityBadgeAttribute()
    {
        $badges = [
            self::PRIORITY_LOW => 'badge-secondary',
            self::PRIORITY_MEDIUM => 'badge-info',
            self::PRIORITY_HIGH => 'badge-warning',
            self::PRIORITY_URGENT => 'badge-danger'
        ];
        return $badges[$this->priority] ?? 'badge-secondary';
    }

    public function getComplaintTypeTextAttribute()
    {
        $types = [
            self::TYPE_SERVICE_QUALITY => 'Service Quality',
            self::TYPE_PRODUCT_QUALITY => 'Product Quality',
            self::TYPE_AROMA_ISSUE => 'Aroma Issue',
            self::TYPE_UNIT_MALFUNCTION => 'Unit Malfunction',
            self::TYPE_BILLING => 'Billing',
            self::TYPE_STAFF_BEHAVIOR => 'Staff Behavior',
            self::TYPE_SCHEDULE => 'Schedule',
            self::TYPE_OTHER => 'Other'
        ];
        return $types[$this->complaint_type] ?? ucfirst(str_replace('_', ' ', $this->complaint_type));
    }

    public function getIsOverdueAttribute()
    {
        if (!$this->target_resolution_date) {
            return false;
        }
        return $this->target_resolution_date->isPast() && 
               !in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    public function getDaysUntilTargetAttribute()
    {
        if (!$this->target_resolution_date) {
            return null;
        }
        return now()->diffInDays($this->target_resolution_date, false);
    }

    // Boolean accessors
    public function getIsOpenAttribute()
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function getIsInProgressAttribute()
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function getIsResolvedAttribute()
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    public function getIsClosedAttribute()
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function getIsRejectedAttribute()
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function getIsUrgentAttribute()
    {
        return $this->priority === self::PRIORITY_URGENT;
    }

    public function getIsHighPriorityAttribute()
    {
        return in_array($this->priority, [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }

    // Methods
    public function acknowledge($userId = null)
    {
        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'acknowledged_at' => now(),
            'assigned_to' => $userId ?? auth()->id()
        ]);
    }

    public function assignTo($userId)
    {
        $this->update([
            'assigned_to' => $userId,
            'status' => self::STATUS_IN_PROGRESS
        ]);
    }

    public function resolve($notes, $resolvedBy = null)
    {
        $this->update([
            'status' => self::STATUS_RESOLVED,
            'resolution_notes' => $notes,
            'resolved_at' => now(),
            'resolved_by' => $resolvedBy ?? auth()->id()
        ]);
    }

    public function close($closedBy = null)
    {
        $this->update([
            'status' => self::STATUS_CLOSED,
            'closed_at' => now(),
            'updated_by' => $closedBy ?? auth()->id()
        ]);
    }

    public function reject($reason)
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'resolution_notes' => $reason,
            'resolved_at' => now()
        ]);
    }

    public function reopen()
    {
        $this->update([
            'status' => self::STATUS_OPEN,
            'resolved_at' => null,
            'closed_at' => null
        ]);
    }

    public function setFollowUp($date, $notes = null)
    {
        $this->update([
            'requires_follow_up' => true,
            'follow_up_date' => $date,
            'follow_up_notes' => $notes
        ]);
    }

    public function addSatisfactionRating($rating, $feedback = null)
    {
        $this->update([
            'satisfaction_rating' => $rating,
            'satisfaction_feedback' => $feedback
        ]);
    }

    /**
     * Calculate response time (from reported to acknowledged)
     */
    public function getResponseTimeInMinutesAttribute()
    {
        if (!$this->acknowledged_at) {
            return null;
        }
        return $this->reported_at->diffInMinutes($this->acknowledged_at);
    }

    /**
     * Calculate resolution time (from reported to resolved)
     */
    public function getResolutionTimeInHoursAttribute()
    {
        if (!$this->resolved_at) {
            return null;
        }
        return $this->reported_at->diffInHours($this->resolved_at);
    }

    /**
     * Generate unique complaint number
     */
    public static function generateComplaintNumber()
    {
        $prefix = 'CPL-' . date('Ymd');
        $count = self::whereDate('created_at', today())->count() + 1;
        return $prefix . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}

