<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dashboard extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'layout',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'layout' => 'array',
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

    public function widgets()
    {
        return $this->hasMany(DashboardWidget::class);
    }

    public function permissions()
    {
        return $this->hasMany(DashboardPermission::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByName($query, $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    // Accessors
    public function getActiveWidgetsAttribute()
    {
        return $this->widgets()->where('is_active', true)->get();
    }

    public function getWidgetCountAttribute()
    {
        return $this->widgets()->count();
    }
}
