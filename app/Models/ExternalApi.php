<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalApi extends Model
{
    use HasFactory;

    protected $fillable = [
        'api_name',
        'api_url',
        'api_key',
        'api_secret',
        'is_active'
    ];

    protected $hidden = [
        'api_key',
        'api_secret'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    // Relationships
    public function logs()
    {
        return $this->hasMany(ApiLog::class, 'api_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByName($query, $name)
    {
        return $query->where('api_name', $name);
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    public function getMaskedApiKeyAttribute()
    {
        return $this->api_key ? substr($this->api_key, 0, 8) . '...' : null;
    }

    public function getMaskedApiSecretAttribute()
    {
        return $this->api_secret ? substr($this->api_secret, 0, 8) . '...' : null;
    }

    // Helper Methods
    public function getLastLog()
    {
        return $this->logs()->latest()->first();
    }

    public function getSuccessRate($days = 7)
    {
        $total = $this->logs()->where('created_at', '>=', now()->subDays($days))->count();
        
        if ($total === 0) {
            return 0;
        }
        
        $successful = $this->logs()
            ->where('created_at', '>=', now()->subDays($days))
            ->where('status_code', '>=', 200)
            ->where('status_code', '<', 300)
            ->count();
        
        return round(($successful / $total) * 100, 2);
    }

    public function getAverageResponseTime($days = 7)
    {
        return $this->logs()
            ->where('created_at', '>=', now()->subDays($days))
            ->avg('execution_time');
    }

    // Static Methods
    public static function getApiTypes()
    {
        return [
            'bank' => 'Banking API',
            'tax' => 'Tax API',
            'payment' => 'Payment Gateway',
            'notification' => 'Notification Service',
            'weather' => 'Weather API',
            'other' => 'Other'
        ];
    }
}
