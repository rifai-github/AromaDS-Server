<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;

use App\Traits\HasComprehensiveAuditTrail;

class Role extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable, HasComprehensiveAuditTrail;

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
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles', 'role_id', 'user_id')
                    ->withTimestamps();
    }

    public function rolePermissions()
    {
        return $this->hasMany(RolePermission::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_id', 'permission_id');
    }
    
    /**
     * Override the permissions attribute to always return the relationship
     * This fixes conflict with the 'permissions' column in database
     */
    public function getPermissionsAttribute($value)
    {
        // If relationship is already loaded, return it
        if ($this->relationLoaded('permissions')) {
            return $this->getRelation('permissions');
        }
        
        // Otherwise load and return the relationship
        return $this->permissions()->get();
    }

    public function roleGroups()
    {
        return $this->belongsToMany(RoleGroup::class, 'role_group_roles', 'role_id', 'role_group_id')
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
    public function hasPermission($permission)
    {
        // Check relationship permissions first
        if ($this->permissions()->where('name', $permission)->exists()) {
            return true;
        }
        
        // Fallback to array permissions (for backward compatibility)
        if ($this->permissions && is_array($this->permissions)) {
            return in_array($permission, $this->permissions);
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
}
