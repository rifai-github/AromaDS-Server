<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class FilePermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_id',
        'permissible_type',
        'permissible_id',
        'permission_type',
        'is_granted',
        'expires_at',
        'granted_by'
    ];

    protected $casts = [
        'is_granted' => 'boolean',
        'expires_at' => 'datetime'
    ];

    /**
     * Get the file that owns this permission
     */
    public function file()
    {
        return $this->belongsTo(File::class, 'file_id');
    }

    /**
     * Get the user who granted this permission
     */
    public function granter()
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    /**
     * Get the permissible model (user, role, department, etc.)
     */
    public function permissible()
    {
        return $this->morphTo();
    }

    /**
     * Check if permission is active
     */
    public function isActive()
    {
        return $this->is_granted && 
               (!$this->expires_at || $this->expires_at->isFuture());
    }

    /**
     * Scope for active permissions
     */
    public function scopeActive($query)
    {
        return $query->where('is_granted', true)
                    ->where(function($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    /**
     * Scope for permissions by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('permission_type', $type);
    }

    /**
     * Scope for permissions by permissible
     */
    public function scopeByPermissible($query, $type, $id)
    {
        return $query->where('permissible_type', $type)
                    ->where('permissible_id', $id);
    }
}