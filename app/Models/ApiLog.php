<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'api_id',
        'request_data',
        'response_data',
        'status_code',
        'execution_time'
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array'
    ];

    // Relationships
    public function api()
    {
        return $this->belongsTo(ExternalApi::class, 'api_id');
    }

    // Scopes
    public function scopeByApi($query, $apiId)
    {
        return $query->where('api_id', $apiId);
    }

    public function scopeByStatusCode($query, $code)
    {
        return $query->where('status_code', $code);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status_code', '>=', 200)
                    ->where('status_code', '<', 300);
    }

    public function scopeFailed($query)
    {
        return $query->where('status_code', '>=', 400);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeSlow($query, $threshold = 5000) // 5 seconds
    {
        return $query->where('execution_time', '>', $threshold);
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        if ($this->status_code >= 200 && $this->status_code < 300) {
            return 'Success';
        } elseif ($this->status_code >= 300 && $this->status_code < 400) {
            return 'Redirect';
        } elseif ($this->status_code >= 400 && $this->status_code < 500) {
            return 'Client Error';
        } elseif ($this->status_code >= 500) {
            return 'Server Error';
        } else {
            return 'Unknown';
        }
    }

    public function getStatusColorAttribute()
    {
        if ($this->status_code >= 200 && $this->status_code < 300) {
            return 'green';
        } elseif ($this->status_code >= 300 && $this->status_code < 400) {
            return 'blue';
        } elseif ($this->status_code >= 400 && $this->status_code < 500) {
            return 'yellow';
        } elseif ($this->status_code >= 500) {
            return 'red';
        } else {
            return 'gray';
        }
    }

    public function getExecutionTimeFormattedAttribute()
    {
        if ($this->execution_time < 1000) {
            return $this->execution_time . 'ms';
        } else {
            return round($this->execution_time / 1000, 2) . 's';
        }
    }

    public function getIsSlowAttribute()
    {
        return $this->execution_time > 5000; // 5 seconds
    }

    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    // Helper Methods
    public function isSuccessful()
    {
        return $this->status_code >= 200 && $this->status_code < 300;
    }

    public function isFailed()
    {
        return $this->status_code >= 400;
    }

    public function getErrorMessage()
    {
        if (!$this->isFailed()) {
            return null;
        }
        
        $response = $this->response_data;
        
        if (isset($response['error'])) {
            return $response['error'];
        }
        
        if (isset($response['message'])) {
            return $response['message'];
        }
        
        return 'Unknown error occurred';
    }

    // Static Methods
    public static function getStatusCodeGroups()
    {
        return [
            '2xx' => 'Success (200-299)',
            '3xx' => 'Redirect (300-399)',
            '4xx' => 'Client Error (400-499)',
            '5xx' => 'Server Error (500-599)'
        ];
    }

    public static function getAverageResponseTime($apiId = null, $days = 7)
    {
        $query = static::recent($days);
        
        if ($apiId) {
            $query->where('api_id', $apiId);
        }
        
        return $query->avg('execution_time');
    }

    public static function getSuccessRate($apiId = null, $days = 7)
    {
        $query = static::recent($days);
        
        if ($apiId) {
            $query->where('api_id', $apiId);
        }
        
        $total = $query->count();
        
        if ($total === 0) {
            return 0;
        }
        
        $successful = $query->successful()->count();
        
        return round(($successful / $total) * 100, 2);
    }
}
