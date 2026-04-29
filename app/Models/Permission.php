<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'system_reserved',
        'is_active'
    ];

    protected $casts = [
        'system_reserved' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_permission', 'permission_id', 'user_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSystemReserved($query)
    {
        return $query->where('system_reserved', true);
    }
}
