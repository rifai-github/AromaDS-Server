<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DashboardWidget extends Model
{
    use HasFactory;

    protected $fillable = [
        'dashboard_id',
        'widget_type',
        'widget_config',
        'position_x',
        'position_y',
        'width',
        'height',
        'is_active'
    ];

    protected $casts = [
        'widget_config' => 'array',
        'is_active' => 'boolean',
        'position_x' => 'integer',
        'position_y' => 'integer',
        'width' => 'integer',
        'height' => 'integer'
    ];

    // Relationships
    public function dashboard()
    {
        return $this->belongsTo(Dashboard::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('widget_type', $type);
    }

    // Accessors
    public function getPositionAttribute()
    {
        return [
            'x' => $this->position_x,
            'y' => $this->position_y
        ];
    }

    public function getSizeAttribute()
    {
        return [
            'width' => $this->width,
            'height' => $this->height
        ];
    }
}
