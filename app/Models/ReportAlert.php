<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportAlert extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'alert_name',
        'alert_type',
        'condition',
        'threshold',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'threshold' => 'decimal:4',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function notifications()
    {
        return $this->hasMany(AlertNotification::class, 'alert_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('alert_type', $type);
    }

    public function scopeByName($query, $name)
    {
        return $query->where('alert_name', 'like', "%{$name}%");
    }

    // Accessors
    public function getTypeTextAttribute()
    {
        $types = [
            'threshold' => 'Threshold',
            'anomaly' => 'Anomaly',
            'trend' => 'Trend'
        ];
        return $types[$this->alert_type] ?? $this->alert_type;
    }

    public function getNotificationCountAttribute()
    {
        return $this->notifications()->count();
    }

    public function getLastNotificationAttribute()
    {
        return $this->notifications()->latest()->first();
    }
}
