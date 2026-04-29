<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleGroupRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'role_group_id',
        'role_id',
        'created_by',
        'updated_by'
    ];

    // Relationships
    public function roleGroup()
    {
        return $this->belongsTo(RoleGroup::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}