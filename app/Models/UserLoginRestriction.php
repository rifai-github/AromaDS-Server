<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLoginRestriction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'start_time',
        'end_time',
        'allowed_days',
        'idle_timeout',
        'is_active'
    ];

    protected $casts = [
        'allowed_days' => 'array',
        'is_active' => 'boolean'
    ];

    /**
     * Get the user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for active restrictions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if current time is within allowed time range
     */
    public function isTimeAllowed()
    {
        $now = now();
        $currentTime = $now->format('H:i:s');

        if ($this->start_time && $this->end_time) {
            return $currentTime >= $this->start_time && $currentTime <= $this->end_time;
        }

        return true;
    }

    /**
     * Check if current day is allowed
     */
    public function isDayAllowed()
    {
        if (!$this->allowed_days) {
            return true;
        }

        $currentDay = now()->dayOfWeek;
        return in_array($currentDay, $this->allowed_days);
    }
}
