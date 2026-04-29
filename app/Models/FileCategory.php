<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class FileCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'allowed_extensions',
        'max_file_size',
        'is_active'
    ];

    protected $casts = [
        'allowed_extensions' => 'array',
        'is_active' => 'boolean'
    ];

    /**
     * Get files in this category
     */
    public function files()
    {
        return $this->hasMany(File::class, 'category_id');
    }

    /**
     * Get active files in this category
     */
    public function activeFiles()
    {
        return $this->hasMany(File::class, 'category_id')->whereNull('deleted_at');
    }

    /**
     * Check if file extension is allowed
     */
    public function isExtensionAllowed($extension)
    {
        if (!$this->allowed_extensions) {
            return true;
        }

        return in_array(strtolower($extension), array_map('strtolower', $this->allowed_extensions));
    }

    /**
     * Check if file size is within limit
     */
    public function isSizeAllowed($size)
    {
        return $size <= $this->max_file_size;
    }

    /**
     * Get formatted max file size
     */
    public function getFormattedMaxSizeAttribute()
    {
        $bytes = $this->max_file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Scope for active categories
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get category by slug
     */
    public function scopeBySlug($query, $slug)
    {
        return $query->where('slug', $slug);
    }
}