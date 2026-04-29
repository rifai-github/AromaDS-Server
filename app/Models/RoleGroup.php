<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoleGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'permissions',
        'is_active',
        'system_reserved',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
        'system_reserved' => 'boolean'
    ];

    // Relationships
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_group_roles', 'role_group_id', 'role_id')
                    ->withPivot('created_by', 'updated_by', 'created_at', 'updated_at')
                    ->withTimestamps();
    }

    public function roleGroupRoles()
    {
        return $this->hasMany(RoleGroupRole::class);
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

    public function scopeSystemReserved($query)
    {
        return $query->where('system_reserved', true);
    }

    public function scopeUserCreated($query)
    {
        return $query->where('system_reserved', false);
    }

    // Helper methods
    public function hasRole($roleId)
    {
        return $this->roles()->where('role_id', $roleId)->exists();
    }

    public function addRole($roleId)
    {
        if (!$this->hasRole($roleId)) {
            $this->roles()->attach($roleId, [
                'created_by' => auth()->id(),
                'updated_by' => auth()->id()
            ]);
        }
    }

    public function removeRole($roleId)
    {
        $this->roles()->detach($roleId);
    }

    public function syncRoles(array $roleIds)
    {
        $this->roles()->sync($roleIds);
    }

    public function hasPermission($permission)
    {
        // Check if role group has direct permission
        if ($this->permissions && is_array($this->permissions)) {
            if (in_array($permission, $this->permissions)) {
                return true;
            }
        }

        // Check permissions from roles in this group
        foreach ($this->roles as $role) {
            if ($role->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public function addPermission($permission)
    {
        $permissions = $this->permissions ?? [];
        
        if (!in_array($permission, $permissions)) {
            $permissions[] = $permission;
            $this->update(['permissions' => $permissions]);
        }
    }

    public function removePermission($permission)
    {
        $permissions = $this->permissions ?? [];
        
        $permissions = array_filter($permissions, function($p) use ($permission) {
            return $p !== $permission;
        });
        
        $this->update(['permissions' => array_values($permissions)]);
    }

    public function syncPermissions(array $permissions)
    {
        $this->update(['permissions' => $permissions]);
    }

    public function getAllPermissions()
    {
        $permissions = $this->permissions ?? [];

        // Add permissions from roles
        foreach ($this->roles as $role) {
            if ($role->permissions && is_array($role->permissions)) {
                $permissions = array_merge($permissions, $role->permissions);
            }
        }

        return array_unique($permissions);
    }

    public function getRoleCount()
    {
        return $this->roles()->count();
    }

    public function getActiveRoleCount()
    {
        return $this->roles()->where('is_active', true)->count();
    }

    public function canBeDeleted()
    {
        return !$this->system_reserved && $this->roles()->count() === 0;
    }

    public function duplicate($newName)
    {
        $newRoleGroup = $this->replicate();
        $newRoleGroup->name = $newName;
        $newRoleGroup->system_reserved = false;
        $newRoleGroup->created_by = auth()->id();
        $newRoleGroup->updated_by = auth()->id();
        $newRoleGroup->save();

        // Copy roles
        $roleIds = $this->roles()->pluck('roles.id')->toArray();
        $newRoleGroup->syncRoles($roleIds);

        return $newRoleGroup;
    }
}