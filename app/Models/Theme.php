<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Theme extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'theme_name',
        'theme_description',
        'color_primary',
        'color_secondary',
        'color_accent',
        'font_family',
        'font_size',
        'is_default',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'font_size' => 'integer'
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

    public function customizations()
    {
        return $this->hasMany(ThemeCustomization::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeByName($query, $name)
    {
        return $query->where('theme_name', $name);
    }

    // Accessors
    public function getFontFamilyTextAttribute()
    {
        $fonts = [
            'Arial' => 'Arial',
            'Helvetica' => 'Helvetica',
            'Times New Roman' => 'Times New Roman',
            'Courier New' => 'Courier New',
            'Verdana' => 'Verdana',
            'Georgia' => 'Georgia',
            'Palatino' => 'Palatino',
            'Garamond' => 'Garamond',
            'Bookman' => 'Bookman',
            'Comic Sans MS' => 'Comic Sans MS',
            'Trebuchet MS' => 'Trebuchet MS',
            'Arial Black' => 'Arial Black',
            'Impact' => 'Impact'
        ];
        return $fonts[$this->font_family] ?? $this->font_family;
    }

    // Methods
    public function getThemeConfig()
    {
        return [
            'name' => $this->theme_name,
            'description' => $this->theme_description,
            'colors' => [
                'primary' => $this->color_primary,
                'secondary' => $this->color_secondary,
                'accent' => $this->color_accent
            ],
            'typography' => [
                'font_family' => $this->font_family,
                'font_size' => $this->font_size
            ],
            'is_default' => $this->is_default,
            'is_active' => $this->is_active
        ];
    }

    public function setAsDefault()
    {
        // Remove default from other themes
        static::where('is_default', true)->update(['is_default' => false]);
        
        // Set this theme as default
        $this->update(['is_default' => true]);
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
