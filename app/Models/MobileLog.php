<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MobileLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'log_type',
        'log_message',
        'device_info'
    ];

    protected $casts = [
        'device_info' => 'array'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('log_type', $type);
    }

    public function scopeError($query)
    {
        return $query->where('log_type', 'error');
    }

    public function scopeWarning($query)
    {
        return $query->where('log_type', 'warning');
    }

    public function scopeInfo($query)
    {
        return $query->where('log_type', 'info');
    }

    public function scopeDebug($query)
    {
        return $query->where('log_type', 'debug');
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    // Accessors
    public function getDeviceNameAttribute()
    {
        return $this->device_info['device_name'] ?? 'Unknown Device';
    }

    public function getOsVersionAttribute()
    {
        return $this->device_info['os_version'] ?? 'Unknown';
    }

    public function getAppVersionAttribute()
    {
        return $this->device_info['app_version'] ?? 'Unknown';
    }

    public function getLogLevelColorAttribute()
    {
        $colors = [
            'error' => 'red',
            'warning' => 'yellow',
            'info' => 'blue',
            'debug' => 'gray'
        ];
        
        return $colors[$this->log_type] ?? 'gray';
    }

    public function getLogLevelIconAttribute()
    {
        $icons = [
            'error' => 'exclamation-circle',
            'warning' => 'exclamation-triangle',
            'info' => 'info-circle',
            'debug' => 'bug'
        ];
        
        return $icons[$this->log_type] ?? 'question';
    }

    public function getShortMessageAttribute()
    {
        return strlen($this->log_message) > 100 
            ? substr($this->log_message, 0, 100) . '...' 
            : $this->log_message;
    }

    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    // Static Methods
    public static function getLogTypes()
    {
        return [
            'error' => 'Error',
            'warning' => 'Warning',
            'info' => 'Info',
            'debug' => 'Debug'
        ];
    }

    public static function log($userId, $type, $message, $deviceInfo = [])
    {
        return static::create([
            'user_id' => $userId,
            'log_type' => $type,
            'log_message' => $message,
            'device_info' => $deviceInfo
        ]);
    }

    public static function getErrorCount($userId = null, $days = 7)
    {
        $query = static::error()->recent($days);
        
        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        return $query->count();
    }

    public static function getLogStats($userId = null, $days = 7)
    {
        $query = static::recent($days);
        
        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        return $query->selectRaw('log_type, COUNT(*) as count')
                    ->groupBy('log_type')
                    ->pluck('count', 'log_type');
    }
}
