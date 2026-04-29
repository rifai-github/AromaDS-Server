<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackupLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'backup_type',
        'file_path',
        'file_size',
        'status'
    ];

    protected $casts = [
        'file_size' => 'integer'
    ];

    // Constants for backup types
    const DATABASE = 'database';
    const FILES = 'files';
    const FULL_SYSTEM = 'full_system';
    const INCREMENTAL = 'incremental';
    const DIFFERENTIAL = 'differential';

    // Constants for status
    const PENDING = 'pending';
    const IN_PROGRESS = 'in_progress';
    const COMPLETED = 'completed';
    const FAILED = 'failed';
    const CANCELLED = 'cancelled';

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('backup_type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::FAILED);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Helper methods
    public function getFormattedFileSizeAttribute()
    {
        $bytes = $this->file_size;
        
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 2) . ' KB';
        } elseif ($bytes < 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024), 2) . ' MB';
        } else {
            return round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
        }
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            self::PENDING => 'blue',
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
            self::PENDING => 'Pending',
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
            self::DATABASE => 'Database',
            self::FILES => 'Files',
            self::FULL_SYSTEM => 'Full System',
            self::INCREMENTAL => 'Incremental',
            self::DIFFERENTIAL => 'Differential'
        ];

        return $types[$this->backup_type] ?? 'Unknown';
    }

    public function isCompleted()
    {
        return $this->status === self::COMPLETED;
    }

    public function isFailed()
    {
        return $this->status === self::FAILED;
    }

    public function isInProgress()
    {
        return $this->status === self::IN_PROGRESS;
    }

    // Static methods
    public static function getTypes()
    {
        return [
            self::DATABASE => 'Database',
            self::FILES => 'Files',
            self::FULL_SYSTEM => 'Full System',
            self::INCREMENTAL => 'Incremental',
            self::DIFFERENTIAL => 'Differential'
        ];
    }

    public static function getStatuses()
    {
        return [
            self::PENDING => 'Pending',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled'
        ];
    }
}
