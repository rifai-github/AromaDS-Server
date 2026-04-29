<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalyticsMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'metric_name',
        'metric_value',
        'metric_date'
    ];

    protected $casts = [
        'metric_value' => 'decimal:4',
        'metric_date' => 'date'
    ];

    // Scopes
    public function scopeByName($query, $name)
    {
        return $query->where('metric_name', $name);
    }

    public function scopeByDate($query, $date)
    {
        return $query->whereDate('metric_date', $date);
    }

    public function scopeByDateRange($query, $start, $end)
    {
        return $query->whereBetween('metric_date', [$start, $end]);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('metric_date', 'desc');
    }

    // Accessors
    public function getMetricNameTextAttribute()
    {
        $names = [
            'total_users' => 'Total Users',
            'active_users' => 'Active Users',
            'reports_generated' => 'Reports Generated',
            'dashboard_views' => 'Dashboard Views',
            'exports_downloaded' => 'Exports Downloaded',
            'kpi_views' => 'KPI Views',
            'alerts_triggered' => 'Alerts Triggered'
        ];
        return $names[$this->metric_name] ?? $this->metric_name;
    }

    public function getFormattedValueAttribute()
    {
        if ($this->metric_value >= 1000000) {
            return number_format($this->metric_value / 1000000, 1) . 'M';
        } elseif ($this->metric_value >= 1000) {
            return number_format($this->metric_value / 1000, 1) . 'K';
        }
        return number_format($this->metric_value, 0);
    }
}
