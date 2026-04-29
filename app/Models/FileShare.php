<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
class FileShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_id',
        'share_token',
        'email',
        'permission_type',
        'expires_at',
        'max_downloads',
        'download_count',
        'is_active',
        'shared_by'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'download_count' => 'integer',
        'max_downloads' => 'integer',
        'is_active' => 'boolean'
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->share_token) {
                $model->share_token = Str::random(32);
            }
        });
    }

    /**
     * Get the file that is shared
     */
    public function file()
    {
        return $this->belongsTo(File::class, 'file_id');
    }

    /**
     * Get the user who shared the file
     */
    public function sharer()
    {
        return $this->belongsTo(User::class, 'shared_by');
    }

    /**
     * Check if share is active
     */
    public function isActive()
    {
        return $this->is_active && 
               (!$this->expires_at || $this->expires_at->isFuture()) &&
               (!$this->max_downloads || $this->download_count < $this->max_downloads);
    }

    /**
     * Increment download count
     */
    public function incrementDownloadCount()
    {
        $this->increment('download_count');
    }

    /**
     * Get share URL
     */
    public function getShareUrl()
    {
        return route('files.share', ['token' => $this->share_token]);
    }

    /**
     * Scope for active shares
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where(function($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    })
                    ->where(function($q) {
                        $q->whereNull('max_downloads')
                          ->orWhereRaw('download_count < max_downloads');
                    });
    }

    /**
     * Scope for shares by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('shared_by', $userId);
    }

    /**
     * Scope for shares by token
     */
    public function scopeByToken($query, $token)
    {
        return $query->where('share_token', $token);
    }
}