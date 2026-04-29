<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;

class MaintenanceSchedule extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail;

    protected $fillable = [
        'title',
        'description',
        'maintenance_type',
        'priority',
        'status',
        'scheduled_date',
        'scheduled_time',
        'due_date',
        'estimated_duration',
        'notes',
        'checklist',
        'attachments',
        'assigned_to',
        'created_by',
        'updated_by',
        'deleted_by',
        'completed_at',
        'started_at'
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'due_date' => 'date',
        'scheduled_time' => 'datetime:H:i',
        'checklist' => 'array',
        'attachments' => 'array',
        'completed_at' => 'datetime',
        'started_at' => 'datetime'
    ];

    // Constants for maintenance types
    const TYPE_PREVENTIVE = 'preventive';
    const TYPE_CORRECTIVE = 'corrective';
    const TYPE_PREDICTIVE = 'predictive';
    const TYPE_EMERGENCY = 'emergency';

    // Constants for priority
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_CRITICAL = 'critical';

    // Constants for status
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_OVERDUE = 'overdue';

    // Relationships
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function maintenanceRecords()
    {
        return $this->hasMany(MaintenanceRecord::class);
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('maintenance_type', $type);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_OVERDUE);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_date', '>=', now()->toDateString())
                    ->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeByAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    // Accessors
    public function getIsOverdueAttribute()
    {
        return $this->due_date && $this->due_date < now()->toDateString() && 
               in_array($this->status, [self::STATUS_SCHEDULED, self::STATUS_IN_PROGRESS]);
    }

    public function getDurationFormattedAttribute()
    {
        if (!$this->estimated_duration) return null;
        
        $hours = floor($this->estimated_duration / 60);
        $minutes = $this->estimated_duration % 60;
        
        if ($hours > 0) {
            return $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours}h";
        }
        
        return "{$minutes}m";
    }

    // Methods
    public function markAsStarted()
    {
        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'started_at' => now()
        ]);
    }

    public function markAsCompleted()
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now()
        ]);
    }

    public function markAsOverdue()
    {
        $this->update([
            'status' => self::STATUS_OVERDUE
        ]);
    }

    public function cancel()
    {
        $this->update([
            'status' => self::STATUS_CANCELLED
        ]);
    }
}
