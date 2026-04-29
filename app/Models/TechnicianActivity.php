<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TechnicianActivity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'technician_id',
        'job_schedule_id',
        'activity_type',
        'activity_time',
        'latitude',
        'longitude',
        'location_address',
        'notes',
        'metadata'
    ];

    protected $casts = [
        'activity_time' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'metadata' => 'array'
    ];

    // Relationships
    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function jobSchedule()
    {
        return $this->belongsTo(JobSchedule::class);
    }

    // Scopes
    public function scopeByTechnician($query, $technicianId)
    {
        return $query->where('technician_id', $technicianId);
    }

    public function scopeByJobSchedule($query, $jobScheduleId)
    {
        return $query->where('job_schedule_id', $jobScheduleId);
    }

    public function scopeByActivityType($query, $activityType)
    {
        return $query->where('activity_type', $activityType);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('activity_time', [$startDate, $endDate]);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('activity_time', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('activity_time', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('activity_time', now()->month);
    }

    // Accessors
    public function getActivityTypeLabelAttribute()
    {
        $labels = [
            'check_in' => 'Check In',
            'check_out' => 'Check Out',
            'start_work' => 'Start Work',
            'break_start' => 'Break Start',
            'break_end' => 'Break End',
            'complete_work' => 'Complete Work',
            'issue_report' => 'Issue Report'
        ];

        return $labels[$this->activity_type] ?? ucfirst(str_replace('_', ' ', $this->activity_type));
    }

    public function getFormattedActivityTimeAttribute()
    {
        return $this->activity_time ? $this->activity_time->format('d/m/Y H:i:s') : '-';
    }

    public function getLocationCoordinatesAttribute()
    {
        if ($this->latitude && $this->longitude) {
            return $this->latitude . ', ' . $this->longitude;
        }
        return 'N/A';
    }

    // Methods
    public function getDurationFromPreviousActivity()
    {
        $previousActivity = $this->jobSchedule->technicianActivities()
            ->where('activity_time', '<', $this->activity_time)
            ->orderBy('activity_time', 'desc')
            ->first();

        if ($previousActivity) {
            return $this->activity_time->diffInMinutes($previousActivity->activity_time);
        }

        return 0;
    }

    public function getTotalWorkTime()
    {
        $checkIn = $this->jobSchedule->technicianActivities()
            ->where('activity_type', 'check_in')
            ->where('activity_time', '<=', $this->activity_time)
            ->orderBy('activity_time', 'desc')
            ->first();

        if ($checkIn) {
            return $this->activity_time->diffInMinutes($checkIn->activity_time);
        }

        return 0;
    }
}
