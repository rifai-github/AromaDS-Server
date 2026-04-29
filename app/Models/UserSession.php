<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'last_activity'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getLastActivityAttribute($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::createFromTimestamp((int) $value);
    }

    public function setLastActivityAttribute($value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['last_activity'] = null;
            return;
        }

        if ($value instanceof Carbon) {
            $this->attributes['last_activity'] = $value->getTimestamp();
            return;
        }

        if (is_numeric($value)) {
            $this->attributes['last_activity'] = (int) $value;
            return;
        }

        $this->attributes['last_activity'] = Carbon::parse($value)->getTimestamp();
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive($query)
    {
        return $query->where('last_activity', '>', now()->subMinutes(config('session.lifetime', 120))->timestamp);
    }

    public function scopeExpired($query)
    {
        return $query->where('last_activity', '<=', now()->subMinutes(config('session.lifetime', 120))->timestamp);
    }

    public function scopeRecent($query, $minutes = 60)
    {
        return $query->where('last_activity', '>=', now()->subMinutes($minutes)->timestamp);
    }

    // Helper methods
    public function isActive()
    {
        return $this->last_activity && $this->last_activity->getTimestamp() > now()->subMinutes(config('session.lifetime', 120))->timestamp;
    }

    public function isExpired()
    {
        return !$this->isActive();
    }

    public function getDurationAttribute()
    {
        if (!$this->last_activity || !$this->created_at) {
            return 0;
        }

        return $this->created_at->diffInMinutes($this->last_activity);
    }

    public function getFormattedDurationAttribute()
    {
        $minutes = $this->duration;
        
        if ($minutes < 60) {
            return $minutes . ' minutes';
        }
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        if ($hours < 24) {
            return $hours . 'h ' . $remainingMinutes . 'm';
        }
        
        $days = floor($hours / 24);
        $remainingHours = $hours % 24;
        
        return $days . 'd ' . $remainingHours . 'h';
    }

    public function getLastActivityFormattedAttribute()
    {
        return $this->last_activity ? $this->last_activity->diffForHumans() : '-';
    }

    public function getStatusAttribute()
    {
        return $this->isActive() ? 'active' : 'expired';
    }

    public function getStatusColorAttribute()
    {
        return $this->isActive() ? 'green' : 'red';
    }

    public function getStatusTextAttribute()
    {
        return $this->isActive() ? 'Active' : 'Expired';
    }
}
