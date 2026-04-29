<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileAccessLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_id',
        'user_id',
        'action',
        'ip_address',
        'user_agent',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    /**
     * Get the file that was accessed
     */
    public function file()
    {
        return $this->belongsTo(File::class, 'file_id');
    }

    /**
     * Get the user who performed the action
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope for specific action
     */
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for specific user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for specific file
     */
    public function scopeByFile($query, $fileId)
    {
        return $query->where('file_id', $fileId);
    }

    /**
     * Scope for recent access
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}