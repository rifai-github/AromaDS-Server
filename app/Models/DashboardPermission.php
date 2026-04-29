<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DashboardPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'dashboard_id',
        'user_id',
        'can_view',
        'can_edit'
    ];

    protected $casts = [
        'can_view' => 'boolean',
        'can_edit' => 'boolean'
    ];

    // Relationships
    public function dashboard()
    {
        return $this->belongsTo(Dashboard::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeCanView($query)
    {
        return $query->where('can_view', true);
    }

    public function scopeCanEdit($query)
    {
        return $query->where('can_edit', true);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
