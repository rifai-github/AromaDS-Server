<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;

class ProductPhoto extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail;

    protected $fillable = [
        'master_product_id',
        'file_name',
        'file_path',
        'file_url',
        'file_type',
        'file_size',
        'alt_text',
        'description',
        'sort_order',
        'is_primary',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'file_size' => 'integer',
        'sort_order' => 'integer',
        'is_primary' => 'boolean',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function masterProduct()
    {
        return $this->belongsTo(MasterProduct::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at');
    }

    // Helper methods
    public function getFormattedFileSizeAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getThumbnailUrlAttribute()
    {
        // Generate thumbnail URL (assuming thumbnails are stored with _thumb suffix)
        $pathInfo = pathinfo($this->file_path);
        $thumbnailPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_thumb.' . $pathInfo['extension'];
        
        return str_replace(public_path(), '', $thumbnailPath);
    }

    public function isImage()
    {
        return in_array(strtolower($this->file_type), ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }

    public function isDocument()
    {
        return in_array(strtolower($this->file_type), [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]);
    }

    public function getFileIconAttribute()
    {
        if ($this->isImage()) {
            return 'fas fa-image';
        } elseif ($this->isDocument()) {
            return 'fas fa-file-alt';
        } else {
            return 'fas fa-file';
        }
    }
}