<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MobileSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_id',
        'device_type',
        'app_version',
        'session_token',
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function logs()
    {
        return $this->hasMany(MobileLog::class, 'user_id', 'user_id');
    }

    // Scopes
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByDevice($query, $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }

    public function scopeByToken($query, $token)
    {
        return $query->where('session_token', $token);
    }

    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    public function scopeByDeviceType($query, $type)
    {
        return $query->where('device_type', $type);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Helper Methods
    public function isExpired()
    {
        return $this->expires_at < now();
    }

    public function extend($minutes = 1440) // 24 hours default
    {
        $this->update([
            'expires_at' => now()->addMinutes($minutes)
        ]);
    }

    public function invalidate()
    {
        $this->update([
            'expires_at' => now()->subMinute()
        ]);
    }

    // Accessors
    public function getDeviceTypeTextAttribute()
    {
        $types = [
            'ios' => 'iOS',
            'android' => 'Android',
            'web' => 'Web',
            'desktop' => 'Desktop'
        ];
        
        return $types[$this->device_type] ?? ucfirst($this->device_type);
    }

    public function getTimeRemainingAttribute()
    {
        if ($this->isExpired()) {
            return 'Expired';
        }
        
        return $this->expires_at->diffForHumans();
    }

    public function getIsActiveAttribute()
    {
        return !$this->isExpired();
    }

    // Static Methods
    public static function getDeviceTypes()
    {
        return [
            'ios' => 'iOS',
            'android' => 'Android',
            'web' => 'Web',
            'desktop' => 'Desktop'
        ];
    }

    public static function createSession($userId, $deviceId, $deviceType, $appVersion, $expiryMinutes = 1440)
    {
        $session = static::create([
            'user_id' => $userId,
            'device_id' => $deviceId,
            'device_type' => $deviceType,
            'app_version' => $appVersion,
            'session_token' => bin2hex(random_bytes(32)),
            'expires_at' => now()->addMinutes($expiryMinutes)
        ]);
        
        return $session;
    }

    public static function getActiveSessionsCount()
    {
        return static::valid()->count();
    }

    public static function cleanupExpiredSessions()
    {
        return static::expired()->delete();
    }
}
