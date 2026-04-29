<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
class File extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'original_name',
        'stored_name',
        'file_path',
        'file_url',
        'mime_type',
        'file_extension',
        'file_size',
        'file_hash',
        'category_id',
        'uploaded_by',
        'model_type',
        'model_id',
        'storage_driver',
        'metadata',
        'is_public',
        'is_encrypted',
        'expires_at',
        'download_count',
        'view_count'
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_public' => 'boolean',
        'is_encrypted' => 'boolean',
        'expires_at' => 'datetime',
        'file_size' => 'integer',
        'download_count' => 'integer',
        'view_count' => 'integer'
    ];

    /**
     * Get the category that owns the file
     */
    public function category()
    {
        return $this->belongsTo(FileCategory::class, 'category_id');
    }

    /**
     * Get the user who uploaded the file
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get file versions
     */
    public function versions()
    {
        return $this->hasMany(FileVersion::class, 'file_id')->orderBy('version_number', 'desc');
    }

    /**
     * Get file permissions
     */
    public function permissions()
    {
        return $this->hasMany(FilePermission::class, 'file_id');
    }

    /**
     * Get file access logs
     */
    public function accessLogs()
    {
        return $this->hasMany(FileAccessLog::class, 'file_id')->orderBy('created_at', 'desc');
    }

    /**
     * Get file shares
     */
    public function shares()
    {
        return $this->hasMany(FileShare::class, 'file_id');
    }

    /**
     * Get the related model
     */
    public function relatedModel()
    {
        if ($this->model_type && $this->model_id) {
            return $this->morphTo();
        }
        return null;
    }

    /**
     * Get formatted file size
     */
    public function getFormattedSizeAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get file icon based on extension
     */
    public function getFileIconAttribute()
    {
        $extension = strtolower($this->file_extension);
        
        $icons = [
            'pdf' => 'fas fa-file-pdf text-red-500',
            'doc' => 'fas fa-file-word text-blue-500',
            'docx' => 'fas fa-file-word text-blue-500',
            'xls' => 'fas fa-file-excel text-green-500',
            'xlsx' => 'fas fa-file-excel text-green-500',
            'ppt' => 'fas fa-file-powerpoint text-orange-500',
            'pptx' => 'fas fa-file-powerpoint text-orange-500',
            'jpg' => 'fas fa-file-image text-purple-500',
            'jpeg' => 'fas fa-file-image text-purple-500',
            'png' => 'fas fa-file-image text-purple-500',
            'gif' => 'fas fa-file-image text-purple-500',
            'zip' => 'fas fa-file-archive text-yellow-500',
            'rar' => 'fas fa-file-archive text-yellow-500',
            'txt' => 'fas fa-file-alt text-gray-500',
            'csv' => 'fas fa-file-csv text-green-600',
        ];
        
        return $icons[$extension] ?? 'fas fa-file text-gray-500';
    }

    /**
     * Check if file is expired
     */
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if user can access this file
     */
    public function canAccess($user)
    {
        // Owner can always access
        if ($this->uploaded_by === $user->id) {
            return true;
        }

        // Public files can be accessed by anyone
        if ($this->is_public) {
            return true;
        }

        // Check permissions
        return $this->permissions()
            ->where(function($query) use ($user) {
                $query->where(function($q) use ($user) {
                    $q->where('permissible_type', User::class)
                      ->where('permissible_id', $user->id);
                })->orWhere(function($q) use ($user) {
                    $q->where('permissible_type', 'App\Models\Role')
                      ->whereIn('permissible_id', $user->roles->pluck('id'));
                })->orWhere(function($q) use ($user) {
                    $q->where('permissible_type', 'App\Models\Department')
                      ->where('permissible_id', $user->department_id);
                });
            })
            ->where('is_granted', true)
            ->where(function($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Get file URL
     */
    public function getUrl()
    {
        if ($this->file_url) {
            return $this->file_url;
        }

        if ($this->storage_driver === 'local') {
            return Storage::url($this->file_path);
        }

        return Storage::disk($this->storage_driver)->url($this->file_path);
    }

    /**
     * Download file
     */
    public function download()
    {
        $this->increment('download_count');
        
        // Log download
        $this->accessLogs()->create([
            'user_id' => auth()->id(),
            'action' => 'download',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);

        return Storage::disk($this->storage_driver)->download($this->file_path, $this->original_name);
    }

    /**
     * Scope for public files
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope for files by category
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope for files by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('uploaded_by', $userId);
    }

    /**
     * Scope for files by model
     */
    public function scopeByModel($query, $modelType, $modelId)
    {
        return $query->where('model_type', $modelType)->where('model_id', $modelId);
    }

    /**
     * Scope for non-expired files
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }
}