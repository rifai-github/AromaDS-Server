<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IotData extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'sensor_data',
        'recorded_at'
    ];

    protected $casts = [
        'sensor_data' => 'array',
        'recorded_at' => 'datetime'
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

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('recorded_at', '>=', now()->subHours($hours));
    }

    public function scopeToday($query)
    {
        return $query->whereDate('recorded_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('recorded_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('recorded_at', now()->month)
                    ->whereYear('recorded_at', now()->year);
    }

    // Accessors
    public function getTemperatureAttribute()
    {
        return $this->sensor_data['temperature'] ?? null;
    }

    public function getHumidityAttribute()
    {
        return $this->sensor_data['humidity'] ?? null;
    }

    public function getPressureAttribute()
    {
        return $this->sensor_data['pressure'] ?? null;
    }

    public function getAirQualityAttribute()
    {
        return $this->sensor_data['air_quality'] ?? null;
    }

    public function getDiffuserStatusAttribute()
    {
        return $this->sensor_data['diffuser_status'] ?? null;
    }

    public function getBatteryLevelAttribute()
    {
        return $this->sensor_data['battery_level'] ?? null;
    }

    public function getSignalStrengthAttribute()
    {
        return $this->sensor_data['signal_strength'] ?? null;
    }

    // Helper Methods
    public function hasAlertCondition()
    {
        $data = $this->sensor_data;
        
        // Check for various alert conditions
        if (isset($data['temperature']) && ($data['temperature'] < 15 || $data['temperature'] > 35)) {
            return true;
        }
        
        if (isset($data['humidity']) && ($data['humidity'] < 30 || $data['humidity'] > 80)) {
            return true;
        }
        
        if (isset($data['battery_level']) && $data['battery_level'] < 20) {
            return true;
        }
        
        if (isset($data['signal_strength']) && $data['signal_strength'] < -80) {
            return true;
        }
        
        return false;
    }

    public function getAlertMessage()
    {
        $data = $this->sensor_data;
        $alerts = [];
        
        if (isset($data['temperature']) && ($data['temperature'] < 15 || $data['temperature'] > 35)) {
            $alerts[] = "Temperature out of range: {$data['temperature']}°C";
        }
        
        if (isset($data['humidity']) && ($data['humidity'] < 30 || $data['humidity'] > 80)) {
            $alerts[] = "Humidity out of range: {$data['humidity']}%";
        }
        
        if (isset($data['battery_level']) && $data['battery_level'] < 20) {
            $alerts[] = "Low battery: {$data['battery_level']}%";
        }
        
        if (isset($data['signal_strength']) && $data['signal_strength'] < -80) {
            $alerts[] = "Weak signal: {$data['signal_strength']} dBm";
        }
        
        return implode(', ', $alerts);
    }
}
