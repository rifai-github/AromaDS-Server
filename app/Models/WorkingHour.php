<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkingHour extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_active'
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function exceptions()
    {
        return $this->hasMany(WorkingHoursException::class, 'user_id', 'user_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForDay($query, $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Helper methods
    public function isWorkingTime($time = null)
    {
        $time = $time ?: now();
        $dayOfWeek = $time->dayOfWeek;
        $timeOnly = $time->format('H:i');

        // Check if it's a working day
        $workingDay = $this->where('user_id', $this->user_id)
                          ->where('day_of_week', $dayOfWeek)
                          ->where('is_active', true)
                          ->first();

        if (!$workingDay) {
            return false;
        }

        // Check if time is within working hours
        $startTime = $workingDay->start_time->format('H:i');
        $endTime = $workingDay->end_time->format('H:i');

        return $timeOnly >= $startTime && $timeOnly <= $endTime;
    }

    public function hasException($date)
    {
        return $this->exceptions()
                   ->whereDate('exception_date', $date)
                   ->exists();
    }

    public function getExceptionForDate($date)
    {
        return $this->exceptions()
                   ->whereDate('exception_date', $date)
                   ->first();
    }

    // Constants for day of week
    const MONDAY = 1;
    const TUESDAY = 2;
    const WEDNESDAY = 3;
    const THURSDAY = 4;
    const FRIDAY = 5;
    const SATURDAY = 6;
    const SUNDAY = 0;

    public static function getDayNames()
    {
        return [
            self::SUNDAY => 'Sunday',
            self::MONDAY => 'Monday',
            self::TUESDAY => 'Tuesday',
            self::WEDNESDAY => 'Wednesday',
            self::THURSDAY => 'Thursday',
            self::FRIDAY => 'Friday',
            self::SATURDAY => 'Saturday',
        ];
    }

    public function getDayNameAttribute()
    {
        return self::getDayNames()[$this->day_of_week] ?? 'Unknown';
    }
}
