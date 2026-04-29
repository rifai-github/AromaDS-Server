<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class TechnicianLocation extends Model
{
    use SoftDeletes;

    protected $table = 'technician_location_logs';

    protected $fillable = [
        'technician_id',
        'job_schedule_id',
        'latitude',
        'longitude',
        'location_address',
        'accuracy',
        'battery_level',
        'network_type',
        'speed',
        'heading',
        'altitude',
        'is_moving',
        'activity_type',
        'device_info',
        'metadata',
        'timestamp'
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'accuracy' => 'decimal:2',
        'speed' => 'decimal:2',
        'heading' => 'decimal:2',
        'altitude' => 'decimal:2',
        'is_moving' => 'boolean',
        'battery_level' => 'integer',
        'device_info' => 'array',
        'metadata' => 'array',
        'timestamp' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Get the technician that owns the location.
     */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    /**
     * Get the job schedule associated with this location.
     */
    public function jobSchedule(): BelongsTo
    {
        return $this->belongsTo(JobSchedule::class);
    }

    /**
     * Scope for recent locations (last 24 hours).
     */
    public function scopeRecent($query)
    {
        return $query->where('timestamp', '>=', Carbon::now()->subDay());
    }

    /**
     * Scope for locations by technician.
     */
    public function scopeByTechnician($query, $technicianId)
    {
        return $query->where('technician_id', $technicianId);
    }

    /**
     * Scope for locations by job schedule.
     */
    public function scopeByJobSchedule($query, $jobScheduleId)
    {
        return $query->where('job_schedule_id', $jobScheduleId);
    }

    /**
     * Scope for locations by activity type.
     */
    public function scopeByActivityType($query, $activityType)
    {
        return $query->where('activity_type', $activityType);
    }

    /**
     * Scope for moving technicians.
     */
    public function scopeMoving($query)
    {
        return $query->where('is_moving', true);
    }

    /**
     * Scope for stationary technicians.
     */
    public function scopeStationary($query)
    {
        return $query->where('is_moving', false);
    }

    /**
     * Scope for online technicians (last 5 minutes).
     */
    public function scopeOnline($query)
    {
        return $query->where('timestamp', '>=', Carbon::now()->subMinutes(5));
    }

    /**
     * Scope for offline technicians (more than 5 minutes ago).
     */
    public function scopeOffline($query)
    {
        return $query->where('timestamp', '<', Carbon::now()->subMinutes(5));
    }

    /**
     * Scope for locations within a specific radius.
     */
    public function scopeWithinRadius($query, $latitude, $longitude, $radiusKm = 1)
    {
        $earthRadius = 6371; // Earth's radius in kilometers
        
        return $query->selectRaw(
            "*, (
                {$earthRadius} * acos(
                    cos(radians(?)) * cos(radians(latitude)) * 
                    cos(radians(longitude) - radians(?)) + 
                    sin(radians(?)) * sin(radians(latitude))
                )
            ) AS distance",
            [$latitude, $longitude, $latitude]
        )->having('distance', '<=', $radiusKm);
    }

    /**
     * Get the latest location for a technician.
     */
    public static function getLatestForTechnician($technicianId)
    {
        return static::where('technician_id', $technicianId)
            ->orderBy('timestamp', 'desc')
            ->first();
    }

    /**
     * Get status badge for location.
     */
    public function getStatusBadgeAttribute()
    {
        $minutesAgo = $this->timestamp->diffInMinutes(now());
        
        if ($minutesAgo <= 5) {
            return '<span class="badge badge-success">Online</span>';
        } elseif ($minutesAgo <= 60) {
            return '<span class="badge badge-warning">Recent</span>';
        } else {
            return '<span class="badge badge-danger">Offline</span>';
        }
    }

    /**
     * Get formatted coordinates.
     */
    public function getFormattedCoordinatesAttribute()
    {
        return number_format($this->latitude, 6) . ', ' . number_format($this->longitude, 6);
    }

    /**
     * Get activity type label.
     */
    public function getActivityTypeLabelAttribute()
    {
        $labels = [
            'start_work' => 'Start Work',
            'traveling' => 'Traveling',
            'on_site' => 'On Site',
            'working' => 'Working',
            'break' => 'Break',
            'end_work' => 'End Work',
            'emergency' => 'Emergency'
        ];

        return $labels[$this->activity_type] ?? ucfirst(str_replace('_', ' ', $this->activity_type));
    }

    /**
     * Get movement status.
     */
    public function getMovementStatusAttribute()
    {
        return $this->is_moving ? 'Moving' : 'Stationary';
    }

    /**
     * Calculate distance between two points.
     */
    public function distanceTo($latitude, $longitude)
    {
        $earthRadius = 6371; // Earth's radius in kilometers
        
        $latDiff = deg2rad($latitude - $this->latitude);
        $lonDiff = deg2rad($longitude - $this->longitude);
        
        $a = sin($latDiff / 2) * sin($latDiff / 2) +
             cos(deg2rad($this->latitude)) * cos(deg2rad($latitude)) *
             sin($lonDiff / 2) * sin($lonDiff / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }
}
