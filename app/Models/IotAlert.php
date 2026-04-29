<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IotAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'alert_type',
        'alert_message',
        'alert_level',
        'is_resolved'
    ];

    protected $casts = [
        'is_resolved' => 'boolean'
    ];

    // Relationships
    public function device()
    {
        return $this->belongsTo(IotDevice::class, 'device_id');
    }

    // Scopes
    public function scopeByDevice($query, $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('alert_type', $type);
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('alert_level', $level);
    }

    public function scopeResolved($query)
    {
        return $query->where('is_resolved', true);
    }

    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopeCritical($query)
    {
        return $query->where('alert_level', 'critical');
    }

    public function scopeHigh($query)
    {
        return $query->where('alert_level', 'high');
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Helper Methods
    public function resolve()
    {
        $this->update(['is_resolved' => true]);
    }

    public function unresolve()
    {
        $this->update(['is_resolved' => false]);
    }

    // Accessors
    public function getAlertLevelColorAttribute()
    {
        $colors = [
            'low' => 'blue',
            'medium' => 'yellow',
            'high' => 'orange',
            'critical' => 'red'
        ];
        
        return $colors[$this->alert_level] ?? 'gray';
    }

    public function getAlertLevelIconAttribute()
    {
        $icons = [
            'low' => 'info',
            'medium' => 'warning',
            'high' => 'exclamation-triangle',
            'critical' => 'exclamation-circle'
        ];
        
        return $icons[$this->alert_level] ?? 'question';
    }

    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    public function getShortMessageAttribute()
    {
        return strlen($this->alert_message) > 100 
            ? substr($this->alert_message, 0, 100) . '...' 
            : $this->alert_message;
    }

    // Static Methods
    public static function getAlertTypes()
    {
        return [
            'temperature' => 'Temperature Alert',
            'humidity' => 'Humidity Alert',
            'battery' => 'Battery Alert',
            'signal' => 'Signal Alert',
            'device_offline' => 'Device Offline',
            'maintenance' => 'Maintenance Required',
            'error' => 'Device Error',
            'other' => 'Other'
        ];
    }

    public static function getAlertLevels()
    {
        return [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'critical' => 'Critical'
        ];
    }

    public static function getUnresolvedCount()
    {
        return static::where('is_resolved', false)->count();
    }

    public static function getCriticalCount()
    {
        return static::where('alert_level', 'critical')
                    ->where('is_resolved', false)
                    ->count();
    }
}
