<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;

class MaintenanceRecord extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail;

    protected $fillable = [
        'maintenance_schedule_id',
        'unit_id',
        'room_id',
        'building_id',
        'maintenance_type',
        'description',
        'work_performed',
        'findings',
        'recommendations',
        'checklist_results',
        'photos',
        'attachments',
        'cost',
        'duration_minutes',
        'status',
        'started_at',
        'completed_at',
        'performed_by',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $casts = [
        'checklist_results' => 'array',
        'photos' => 'array',
        'attachments' => 'array',
        'cost' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    // Constants for status
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    // Relationships
    public function maintenanceSchedule()
    {
        return $this->belongsTo(MaintenanceSchedule::class);
    }

    public function unit()
    {
        return $this->belongsTo(SerialNumber::class, 'unit_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
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

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('maintenance_type', $type);
    }

    public function scopeByPerformedBy($query, $userId)
    {
        return $query->where('performed_by', $userId);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    // Accessors
    public function getDurationFormattedAttribute()
    {
        if (!$this->duration_minutes) return null;
        
        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;
        
        if ($hours > 0) {
            return $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours}h";
        }
        
        return "{$minutes}m";
    }

    public function getIsCompletedAttribute()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function getIsInProgressAttribute()
    {
        return $this->status === self::STATUS_IN_PROGRESS;
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

    public function markAsFailed()
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => now()
        ]);
    }

    public function cancel()
    {
        $this->update([
            'status' => self::STATUS_CANCELLED
        ]);
    }
}
