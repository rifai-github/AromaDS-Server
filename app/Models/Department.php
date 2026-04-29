<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;

class Department extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $fillable = [
        'name',
        'sub_department',
        'description',
        'system_reserved',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'system_reserved' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function subDepartment()
    {
        return $this->belongsTo(Department::class, 'sub_department');
    }

    public function subDepartments()
    {
        return $this->hasMany(Department::class, 'sub_department');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Role relationships
    public function departmentRoles()
    {
        return $this->hasMany(DepartmentRole::class);
    }

    public function positionRoles()
    {
        return $this->hasMany(PositionRole::class);
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
