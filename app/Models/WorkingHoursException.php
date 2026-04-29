<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkingHoursException extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'exception_date',
        'start_time',
        'end_time',
        'reason'
    ];

    protected $casts = [
        'exception_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('exception_date', $date);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('exception_date', '>=', now()->toDateString());
    }

    public function scopePast($query)
    {
        return $query->where('exception_date', '<', now()->toDateString());
    }

    // Helper methods
    public function isActive()
    {
        return $this->exception_date >= now()->toDateString();
    }

    public function isToday()
    {
        return $this->exception_date->isToday();
    }

    public function isTomorrow()
    {
        return $this->exception_date->isTomorrow();
    }

    public function isThisWeek()
    {
        return $this->exception_date->isCurrentWeek();
    }

    public function getDurationAttribute()
    {
        $start = $this->start_time;
        $end = $this->end_time;
        
        if ($end < $start) {
            $end->addDay();
        }
        
        return $start->diffInHours($end);
    }

    public function getFormattedDurationAttribute()
    {
        $hours = $this->duration;
        return $hours . ' hour' . ($hours !== 1 ? 's' : '');
    }
}
