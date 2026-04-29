<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobTeamLocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'job_schedule_id',
        'user_id',
        'team_id',
        'latitude',
        'longitude',
        'device_info',
        'action',
        'notes',
        'recorded_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'recorded_at' => 'datetime',
    ];

    // Relationships
    public function jobSchedule()
    {
        return $this->belongsTo(JobSchedule::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    // Scopes
    public function scopeByJobSchedule($query, $jobScheduleId)
    {
        return $query->where('job_schedule_id', $jobScheduleId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, $limit = 10)
    {
        return $query->orderBy('recorded_at', 'desc')->limit($limit);
    }

    public function scopeArrived($query)
    {
        return $query->where('action', 'arrived');
    }

    public function scopeLeft($query)
    {
        return $query->where('action', 'left');
    }

    // Accessors
    public function getFormattedLocationAttribute()
    {
        return number_format($this->latitude, 6) . ', ' . number_format($this->longitude, 6);
    }

    public function getGoogleMapsLinkAttribute()
    {
        return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
    }

    public function getActionBadgeClassAttribute()
    {
        return match($this->action) {
            'arrived' => 'bg-success',
            'left' => 'bg-danger',
            'updated' => 'bg-info',
            default => 'bg-secondary',
        };
    }

    public function getActionTextAttribute()
    {
        return ucfirst($this->action);
    }
}
