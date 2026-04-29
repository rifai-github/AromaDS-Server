<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAccessLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'access_type',
        'access_config',
        'is_active'
    ];

    protected $casts = [
        'access_config' => 'array',
        'is_active' => 'boolean'
    ];

    /**
     * Get the user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for active access levels
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific access type
     */
    public function scopeForAccessType($query, $accessType)
    {
        return $query->where('access_type', $accessType);
    }
}
