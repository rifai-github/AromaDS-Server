<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThemeSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'theme_name',
        'color_scheme',
        'layout',
        'is_active'
    ];

    protected $casts = [
        'layout' => 'array',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByThemeName($query, $themeName)
    {
        return $query->where('theme_name', $themeName);
    }

    public function scopeByColorScheme($query, $colorScheme)
    {
        return $query->where('color_scheme', $colorScheme);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Accessors
    public function getThemeNameFormattedAttribute()
    {
        return ucwords(str_replace('_', ' ', $this->theme_name));
    }

    public function getColorSchemeFormattedAttribute()
    {
        return ucwords(str_replace('_', ' ', $this->color_scheme));
    }

    public function getIsActiveTextAttribute()
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    public function getIsActiveBadgeAttribute()
    {
        return $this->is_active ? 'badge-success' : 'badge-secondary';
    }

    // Methods
    public function getThemeConfig()
    {
        return [
            'theme_name' => $this->theme_name,
            'color_scheme' => $this->color_scheme,
            'layout' => $this->layout,
            'is_active' => $this->is_active
        ];
    }

    public function activate()
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate()
    {
        $this->update(['is_active' => false]);
    }

    public function toggleActive()
    {
        $this->update(['is_active' => !$this->is_active]);
    }
}