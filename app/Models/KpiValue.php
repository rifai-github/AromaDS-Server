<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KpiValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'kpi_id',
        'value',
        'period_start',
        'period_end'
    ];

    protected $casts = [
        'value' => 'decimal:4',
        'period_start' => 'date',
        'period_end' => 'date'
    ];

    // Relationships
    public function kpi()
    {
        return $this->belongsTo(KpiDefinition::class, 'kpi_id');
    }

    // Scopes
    public function scopeByPeriod($query, $start, $end)
    {
        return $query->whereBetween('period_start', [$start, $end])
                    ->orWhereBetween('period_end', [$start, $end]);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Accessors
    public function getPeriodTextAttribute()
    {
        return $this->period_start->format('M Y') . ' - ' . $this->period_end->format('M Y');
    }

    public function getPerformanceAttribute()
    {
        if ($this->kpi && $this->kpi->target_value > 0) {
            return ($this->value / $this->kpi->target_value) * 100;
        }
        return 0;
    }

    public function getStatusAttribute()
    {
        $performance = $this->performance;
        if ($performance >= 100) return 'excellent';
        if ($performance >= 80) return 'good';
        if ($performance >= 60) return 'fair';
        return 'poor';
    }
}
