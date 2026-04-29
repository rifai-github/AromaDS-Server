<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemHealth extends Model
{
    use HasFactory;

    protected $fillable = [
        'component',
        'status',
        'message'
    ];

    // Constants for components
    const DATABASE = 'database';
    const CACHE = 'cache';
    const QUEUE = 'queue';
    const STORAGE = 'storage';
    const EMAIL = 'email';
    const API = 'api';
    const WEB_SERVER = 'web_server';
    const MEMORY = 'memory';
    const DISK_SPACE = 'disk_space';
    const CPU = 'cpu';

    // Constants for status
    const HEALTHY = 'healthy';
    const WARNING = 'warning';
    const CRITICAL = 'critical';
    const UNKNOWN = 'unknown';

    // Scopes
    public function scopeByComponent($query, $component)
    {
        return $query->where('component', $component);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeHealthy($query)
    {
        return $query->where('status', self::HEALTHY);
    }

    public function scopeWarning($query)
    {
        return $query->where('status', self::WARNING);
    }

    public function scopeCritical($query)
    {
        return $query->where('status', self::CRITICAL);
    }

    public function scopeRecent($query, $minutes = 60)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }

    // Helper methods
    public function getStatusColorAttribute()
    {
        $colors = [
            self::HEALTHY => 'green',
            self::WARNING => 'yellow',
            self::CRITICAL => 'red',
            self::UNKNOWN => 'gray'
        ];

        return $colors[$this->status] ?? 'gray';
    }

    public function getStatusTextAttribute()
    {
        $texts = [
            self::HEALTHY => 'Healthy',
            self::WARNING => 'Warning',
            self::CRITICAL => 'Critical',
            self::UNKNOWN => 'Unknown'
        ];

        return $texts[$this->status] ?? 'Unknown';
    }

    public function getComponentTextAttribute()
    {
        $components = [
            self::DATABASE => 'Database',
            self::CACHE => 'Cache',
            self::QUEUE => 'Queue',
            self::STORAGE => 'Storage',
            self::EMAIL => 'Email',
            self::API => 'API',
            self::WEB_SERVER => 'Web Server',
            self::MEMORY => 'Memory',
            self::DISK_SPACE => 'Disk Space',
            self::CPU => 'CPU'
        ];

        return $components[$this->component] ?? 'Unknown';
    }

    public function getStatusIconAttribute()
    {
        $icons = [
            self::HEALTHY => 'fas fa-check-circle',
            self::WARNING => 'fas fa-exclamation-triangle',
            self::CRITICAL => 'fas fa-times-circle',
            self::UNKNOWN => 'fas fa-question-circle'
        ];

        return $icons[$this->status] ?? 'fas fa-question-circle';
    }

    public function isHealthy()
    {
        return $this->status === self::HEALTHY;
    }

    public function isWarning()
    {
        return $this->status === self::WARNING;
    }

    public function isCritical()
    {
        return $this->status === self::CRITICAL;
    }

    public function isUnknown()
    {
        return $this->status === self::UNKNOWN;
    }

    // Static methods
    public static function getComponents()
    {
        return [
            self::DATABASE => 'Database',
            self::CACHE => 'Cache',
            self::QUEUE => 'Queue',
            self::STORAGE => 'Storage',
            self::EMAIL => 'Email',
            self::API => 'API',
            self::WEB_SERVER => 'Web Server',
            self::MEMORY => 'Memory',
            self::DISK_SPACE => 'Disk Space',
            self::CPU => 'CPU'
        ];
    }

    public static function getStatuses()
    {
        return [
            self::HEALTHY => 'Healthy',
            self::WARNING => 'Warning',
            self::CRITICAL => 'Critical',
            self::UNKNOWN => 'Unknown'
        ];
    }

    public static function getOverallStatus()
    {
        $latest = self::orderBy('created_at', 'desc')->get();
        
        if ($latest->isEmpty()) {
            return self::UNKNOWN;
        }

        $criticalCount = $latest->where('status', self::CRITICAL)->count();
        $warningCount = $latest->where('status', self::WARNING)->count();
        $healthyCount = $latest->where('status', self::HEALTHY)->count();

        if ($criticalCount > 0) {
            return self::CRITICAL;
        } elseif ($warningCount > 0) {
            return self::WARNING;
        } elseif ($healthyCount > 0) {
            return self::HEALTHY;
        }

        return self::UNKNOWN;
    }
}
