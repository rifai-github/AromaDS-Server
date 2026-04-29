<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThemeCustomization extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'theme_id',
        'custom_css',
        'custom_js',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function theme()
    {
        return $this->belongsTo(Theme::class);
    }

    // Scopes
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByTheme($query, $themeId)
    {
        return $query->where('theme_id', $themeId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Methods
    public function getCustomStyles()
    {
        return $this->custom_css;
    }

    public function getCustomScripts()
    {
        return $this->custom_js;
    }

    public function setCustomStyles($css)
    {
        $this->custom_css = $css;
    }

    public function setCustomScripts($js)
    {
        $this->custom_js = $js;
    }

    public function activate()
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate()
    {
        $this->update(['is_active' => false]);
    }
}
