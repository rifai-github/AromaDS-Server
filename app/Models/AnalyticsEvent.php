<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalyticsEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_type',
        'event_data',
        'user_id'
    ];

    protected $casts = [
        'event_data' => 'array'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('event_type', $type);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
    }

    // Accessors
    public function getEventTypeTextAttribute()
    {
        $types = [
            'page_view' => 'Page View',
            'report_generated' => 'Report Generated',
            'dashboard_view' => 'Dashboard View',
            'export_download' => 'Export Download',
            'kpi_view' => 'KPI View',
            'alert_triggered' => 'Alert Triggered'
        ];
        return $types[$this->event_type] ?? $this->event_type;
    }
}
