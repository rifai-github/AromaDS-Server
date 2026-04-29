<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KpiDefinition extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'kpi_name',
        'kpi_description',
        'calculation_formula',
        'target_value',
        'unit',
        'frequency',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'target_value' => 'decimal:4',
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

    public function values()
    {
        return $this->hasMany(KpiValue::class, 'kpi_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByFrequency($query, $frequency)
    {
        return $query->where('frequency', $frequency);
    }

    public function scopeByName($query, $name)
    {
        return $query->where('kpi_name', 'like', "%{$name}%");
    }

    // Accessors
    public function getFrequencyTextAttribute()
    {
        $frequencies = [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly' => 'Yearly'
        ];
        return $frequencies[$this->frequency] ?? $this->frequency;
    }

    public function getLatestValueAttribute()
    {
        return $this->values()->latest('created_at')->first();
    }

    public function getCurrentValueAttribute()
    {
        $latest = $this->latest_value;
        return $latest ? $latest->value : 0;
    }

    public function getPerformanceAttribute()
    {
        if ($this->target_value == 0) return 0;
        return ($this->current_value / $this->target_value) * 100;
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
