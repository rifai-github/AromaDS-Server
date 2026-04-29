<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attempted_identifier',
        'ip_address',
        'user_agent',
        'location',
        'login_at',
        'logout_at',
        'is_successful',
        'failure_reason'
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
        'is_successful' => 'boolean'
    ];

    /**
     * Get the user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for successful logins
     */
    public function scopeSuccessful($query)
    {
        return $query->where('is_successful', true);
    }

    /**
     * Scope for failed logins
     */
    public function scopeFailed($query)
    {
        return $query->where('is_successful', false);
    }

    /**
     * Scope for specific IP
     */
    public function scopeForIp($query, $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Scope for date range
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('login_at', [$startDate, $endDate]);
    }

    /**
     * Get session duration in minutes
     */
    public function getSessionDurationAttribute()
    {
        if ($this->logout_at) {
            return $this->login_at->diffInMinutes($this->logout_at);
        }
        return null;
    }
}