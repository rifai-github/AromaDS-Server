<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceHistory extends Model
{
    protected $fillable = [
        'job_schedule_id',
        'service_date',
        'service_type',
        'technician_id',
        'notes',
        'service_data'
    ];

    protected $casts = [
        'service_date' => 'date',
        'service_data' => 'array'
    ];

    /**
     * Get the job schedule that owns the service history.
     */
    public function jobSchedule(): BelongsTo
    {
        return $this->belongsTo(JobSchedule::class);
    }

    /**
     * Get the technician who performed the service.
     */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    /**
     * Scope for services by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('service_type', $type);
    }

    /**
     * Scope for services by technician.
     */
    public function scopeByTechnician($query, $technicianId)
    {
        return $query->where('technician_id', $technicianId);
    }

    /**
     * Scope for services within date range.
     */
    public function scopeWithinDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('service_date', [$startDate, $endDate]);
    }

    /**
     * Get the service frequency for a job.
     */
    public static function getServiceFrequency($jobScheduleId, $serviceType = null)
    {
        $query = static::where('job_schedule_id', $jobScheduleId);
        
        if ($serviceType) {
            $query->where('service_type', $serviceType);
        }
        
        return $query->count();
    }

    /**
     * Get the last service date for a job.
     */
    public static function getLastServiceDate($jobScheduleId, $serviceType = null)
    {
        $query = static::where('job_schedule_id', $jobScheduleId);
        
        if ($serviceType) {
            $query->where('service_type', $serviceType);
        }
        
        return $query->orderBy('service_date', 'desc')->first()?->service_date;
    }
}
