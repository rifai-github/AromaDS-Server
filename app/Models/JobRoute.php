<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobRoute extends Model
{
    protected $fillable = [
        'job_schedule_id',
        'route_order',
        'estimated_time',
        'actual_time',
        'distance',
        'route_data'
    ];

    protected $casts = [
        'route_order' => 'integer',
        'estimated_time' => 'integer',
        'actual_time' => 'integer',
        'distance' => 'decimal:2',
        'route_data' => 'array'
    ];

    /**
     * Get the job schedule that owns the route.
     */
    public function jobSchedule(): BelongsTo
    {
        return $this->belongsTo(JobSchedule::class);
    }

    /**
     * Get the efficiency percentage (actual vs estimated time).
     */
    public function getEfficiencyAttribute(): float
    {
        if ($this->estimated_time == 0) {
            return 0;
        }
        return ($this->estimated_time / $this->actual_time) * 100;
    }

    /**
     * Get the time difference in minutes.
     */
    public function getTimeDifferenceAttribute(): int
    {
        return $this->actual_time - $this->estimated_time;
    }

    /**
     * Scope for routes with delays.
     */
    public function scopeWithDelays($query)
    {
        return $query->whereRaw('actual_time > estimated_time');
    }

    /**
     * Scope for efficient routes.
     */
    public function scopeEfficient($query)
    {
        return $query->whereRaw('actual_time <= estimated_time');
    }

    /**
     * Calculate route optimization score.
     */
    public function getOptimizationScoreAttribute(): float
    {
        $timeScore = $this->estimated_time > 0 ? ($this->estimated_time / $this->actual_time) * 50 : 0;
        $distanceScore = $this->distance > 0 ? (1 / $this->distance) * 50 : 0;
        
        return min(100, $timeScore + $distanceScore);
    }
}
