<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitInstallation extends Model
{
    protected $fillable = [
        'job_schedule_id',
        'serial_number',
        'room_id',
        'install_date',
        'status',
        'installation_notes',
        'installation_data'
    ];

    protected $casts = [
        'install_date' => 'date',
        'installation_data' => 'array'
    ];

    /**
     * Get the job schedule that owns the installation.
     */
    public function jobSchedule(): BelongsTo
    {
        return $this->belongsTo(JobSchedule::class);
    }

    /**
     * Get the room where the unit is installed.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(MasterRoom::class, 'room_id');
    }

    /**
     * Scope for active installations.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'installed');
    }

    /**
     * Scope for removed installations.
     */
    public function scopeRemoved($query)
    {
        return $query->where('status', 'removed');
    }

    /**
     * Scope for installations by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for installations within date range.
     */
    public function scopeWithinDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('install_date', [$startDate, $endDate]);
    }

    /**
     * Get the installation age in days.
     */
    public function getInstallationAgeAttribute(): int
    {
        return $this->install_date->diffInDays(now());
    }

    /**
     * Check if installation is recent (within 30 days).
     */
    public function isRecentInstallation(): bool
    {
        return $this->installation_age <= 30;
    }

    /**
     * Get the installation status badge color.
     */
    public function getStatusBadgeColorAttribute(): string
    {
        return match($this->status) {
            'installed' => 'success',
            'removed' => 'danger',
            'maintenance' => 'warning',
            'replaced' => 'info',
            default => 'secondary'
        };
    }
}
