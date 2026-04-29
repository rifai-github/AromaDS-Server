<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemperatureRecord extends Model
{
    protected $fillable = [
        'job_schedule_id',
        'room_id',
        'temperature',
        'recorded_at',
        'recorded_by',
        'notes',
        'additional_data'
    ];

    protected $casts = [
        'temperature' => 'decimal:2',
        'recorded_at' => 'datetime',
        'additional_data' => 'array'
    ];

    /**
     * Get the job schedule that owns the temperature record.
     */
    public function jobSchedule(): BelongsTo
    {
        return $this->belongsTo(JobSchedule::class);
    }

    /**
     * Get the room where temperature was recorded.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(MasterRoom::class, 'room_id');
    }

    /**
     * Get the user who recorded the temperature.
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Scope for temperatures within range.
     */
    public function scopeWithinRange($query, $minTemp, $maxTemp)
    {
        return $query->whereBetween('temperature', [$minTemp, $maxTemp]);
    }

    /**
     * Scope for temperatures above threshold.
     */
    public function scopeAboveThreshold($query, $threshold)
    {
        return $query->where('temperature', '>', $threshold);
    }

    /**
     * Scope for temperatures below threshold.
     */
    public function scopeBelowThreshold($query, $threshold)
    {
        return $query->where('temperature', '<', $threshold);
    }

    /**
     * Scope for recent temperature records.
     */
    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('recorded_at', '>=', now()->subHours($hours));
    }

    /**
     * Get the temperature status (normal, high, low).
     */
    public function getTemperatureStatusAttribute(): string
    {
        if ($this->temperature > 30) {
            return 'high';
        } elseif ($this->temperature < 18) {
            return 'low';
        }
        return 'normal';
    }

    /**
     * Get the temperature status badge color.
     */
    public function getTemperatureStatusBadgeColorAttribute(): string
    {
        return match($this->temperature_status) {
            'high' => 'danger',
            'low' => 'warning',
            'normal' => 'success',
            default => 'secondary'
        };
    }

    /**
     * Check if temperature is within acceptable range.
     */
    public function isWithinAcceptableRange($minTemp = 18, $maxTemp = 30): bool
    {
        return $this->temperature >= $minTemp && $this->temperature <= $maxTemp;
    }

    /**
     * Get the average temperature for a job.
     */
    public static function getAverageTemperatureForJob($jobScheduleId)
    {
        return static::where('job_schedule_id', $jobScheduleId)
            ->avg('temperature');
    }

    /**
     * Get the temperature trend for a job.
     */
    public static function getTemperatureTrendForJob($jobScheduleId)
    {
        $records = static::where('job_schedule_id', $jobScheduleId)
            ->orderBy('recorded_at')
            ->get();
        
        if ($records->count() < 2) {
            return 'stable';
        }
        
        $first = $records->first()->temperature;
        $last = $records->last()->temperature;
        
        if ($last > $first + 1) {
            return 'increasing';
        } elseif ($last < $first - 1) {
            return 'decreasing';
        }
        
        return 'stable';
    }
}
