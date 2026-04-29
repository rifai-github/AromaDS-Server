<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Pivot model for linking JobScheduleRoom to multiple JobAdviceRooms
 * Enables multi-rental support per room in Job Schedules
 */
class JobScheduleRoomRental extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'job_schedule_room_rentals';

    protected $fillable = [
        'job_schedule_room_id',
        'job_advice_room_id',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    /**
     * Relationships
     */
    
    public function jobScheduleRoom()
    {
        return $this->belongsTo(JobScheduleRoom::class);
    }

    public function jobAdviceRoom()
    {
        return $this->belongsTo(JobAdviceRoom::class);
    }

    /**
     * Get the rental product through the job advice room
     */
    public function getRentalProductAttribute()
    {
        return $this->jobAdviceRoom?->rentalProduct;
    }

    /**
     * Get the rental name for display
     */
    public function getRentalNameAttribute()
    {
        return $this->jobAdviceRoom?->rentalProduct?->rental_name 
            ?? $this->jobAdviceRoom?->rental_name 
            ?? '-';
    }

    /**
     * Scopes
     */
    
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeByJobScheduleRoom($query, $jobScheduleRoomId)
    {
        return $query->where('job_schedule_room_id', $jobScheduleRoomId);
    }
}
