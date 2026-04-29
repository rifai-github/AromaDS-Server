<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'maintenance_type',
        'description',
        'start_time',
        'end_time',
        'status',
        'created_by'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime'
    ];

    // Constants for maintenance types
    const SYSTEM_UPDATE = 'system_update';
    const DATABASE_BACKUP = 'database_backup';
    const SECURITY_PATCH = 'security_patch';
    const PERFORMANCE_OPTIMIZATION = 'performance_optimization';
    const HARDWARE_MAINTENANCE = 'hardware_maintenance';
    const SOFTWARE_INSTALLATION = 'software_installation';
    const CONFIGURATION_CHANGE = 'configuration_change';
    const OTHER = 'other';

    // Constants for status
    const SCHEDULED = 'scheduled';
    const IN_PROGRESS = 'in_progress';
    const COMPLETED = 'completed';
    const FAILED = 'failed';
    const CANCELLED = 'cancelled';

    // Relationships
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('maintenance_type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', self::SCHEDULED);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::FAILED);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_time', '>', now());
    }

    public function scopePast($query)
    {
        return $query->where('end_time', '<', now());
    }

    // Helper methods
    public function getDurationAttribute()
    {
        if (!$this->start_time || !$this->end_time) {
            return null;
        }

        return $this->start_time->diffInMinutes($this->end_time);
    }

    public function getFormattedDurationAttribute()
    {
        if (!$this->duration) {
            return 'N/A';
        }

        $minutes = $this->duration;
        
        if ($minutes < 60) {
            return $minutes . ' minutes';
        }
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        return $hours . 'h ' . $remainingMinutes . 'm';
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            self::SCHEDULED => 'blue',
            self::IN_PROGRESS => 'yellow',
            self::COMPLETED => 'green',
            self::FAILED => 'red',
            self::CANCELLED => 'gray'
        ];

        return $colors[$this->status] ?? 'gray';
    }

    public function getStatusTextAttribute()
    {
        $texts = [
            self::SCHEDULED => 'Scheduled',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled'
        ];

        return $texts[$this->status] ?? 'Unknown';
    }

    public function getTypeTextAttribute()
    {
        $types = [
            self::SYSTEM_UPDATE => 'System Update',
            self::DATABASE_BACKUP => 'Database Backup',
            self::SECURITY_PATCH => 'Security Patch',
            self::PERFORMANCE_OPTIMIZATION => 'Performance Optimization',
            self::HARDWARE_MAINTENANCE => 'Hardware Maintenance',
            self::SOFTWARE_INSTALLATION => 'Software Installation',
            self::CONFIGURATION_CHANGE => 'Configuration Change',
            self::OTHER => 'Other'
        ];

        return $types[$this->maintenance_type] ?? 'Unknown';
    }

    public function isUpcoming()
    {
        return $this->start_time > now();
    }

    public function isInProgress()
    {
        return $this->status === self::IN_PROGRESS;
    }

    public function isCompleted()
    {
        return $this->status === self::COMPLETED;
    }

    public function isFailed()
    {
        return $this->status === self::FAILED;
    }

    // Static methods
    public static function getTypes()
    {
        return [
            self::SYSTEM_UPDATE => 'System Update',
            self::DATABASE_BACKUP => 'Database Backup',
            self::SECURITY_PATCH => 'Security Patch',
            self::PERFORMANCE_OPTIMIZATION => 'Performance Optimization',
            self::HARDWARE_MAINTENANCE => 'Hardware Maintenance',
            self::SOFTWARE_INSTALLATION => 'Software Installation',
            self::CONFIGURATION_CHANGE => 'Configuration Change',
            self::OTHER => 'Other'
        ];
    }

    public static function getStatuses()
    {
        return [
            self::SCHEDULED => 'Scheduled',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled'
        ];
    }
}
