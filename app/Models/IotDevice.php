<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IotDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'device_name',
        'device_type',
        'room_id',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    // Relationships
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function data()
    {
        return $this->hasMany(IotData::class, 'device_id');
    }

    public function alerts()
    {
        return $this->hasMany(IotAlert::class, 'device_id');
    }

    public function locations()
    {
        return $this->hasMany(IotDeviceLocation::class, 'device_id');
    }

    public function maintenance()
    {
        return $this->hasMany(IotDeviceMaintenance::class, 'device_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('device_type', $type);
    }

    public function scopeByRoom($query, $roomId)
    {
        return $query->where('room_id', $roomId);
    }

    public function scopeOnline($query)
    {
        return $query->whereHas('data', function($q) {
            $q->where('recorded_at', '>=', now()->subMinutes(5));
        });
    }

    public function scopeOffline($query)
    {
        return $query->whereDoesntHave('data', function($q) {
            $q->where('recorded_at', '>=', now()->subMinutes(5));
        });
    }

    // Accessors
    public function getStatusAttribute()
    {
        $latestData = $this->data()->latest('recorded_at')->first();
        
        if (!$latestData) {
            return 'offline';
        }
        
        $lastSeen = $latestData->recorded_at;
        $minutesAgo = now()->diffInMinutes($lastSeen);
        
        if ($minutesAgo <= 5) {
            return 'online';
        } elseif ($minutesAgo <= 30) {
            return 'warning';
        } else {
            return 'offline';
        }
    }

    public function getStatusColorAttribute()
    {
        $status = $this->status;
        
        switch ($status) {
            case 'online':
                return 'green';
            case 'warning':
                return 'yellow';
            case 'offline':
                return 'red';
            default:
                return 'gray';
        }
    }

    public function getLastSeenAttribute()
    {
        $latestData = $this->data()->latest('recorded_at')->first();
        return $latestData ? $latestData->recorded_at : null;
    }

    public function getLastSeenHumanAttribute()
    {
        $lastSeen = $this->last_seen;
        return $lastSeen ? $lastSeen->diffForHumans() : 'Never';
    }

    // Static Methods
    public static function getDeviceTypes()
    {
        return [
            'diffuser' => 'Diffuser',
            'sensor' => 'Sensor',
            'controller' => 'Controller'
        ];
    }
}
