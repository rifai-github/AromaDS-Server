<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;

class AccessManagement extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $table = 'access_managements';

    protected $fillable = [
        'name',
        'description',
        'regional_table_view',
        'permissions',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_access_management', 'access_management_id', 'user_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByName($query, $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    public function scopeByDescription($query, $description)
    {
        return $query->where('description', 'like', "%{$description}%");
    }

    public function scopeByRegionalTableView($query, $regionalTableView)
    {
        return $query->where('regional_table_view', $regionalTableView);
    }

    // Accessors
    public function getIsActiveAttribute()
    {
        return $this->is_active;
    }

    // Methods
    public function hasPermission($module, $action)
    {
        if (!$this->permissions || !is_array($this->permissions)) {
            return false;
        }

        return isset($this->permissions[$module][$action]) && $this->permissions[$module][$action];
    }

    public function grantPermission($module, $action)
    {
        $permissions = $this->permissions ?? [];
        $permissions[$module][$action] = true;
        $this->update(['permissions' => $permissions]);
    }

    public function revokePermission($module, $action)
    {
        $permissions = $this->permissions ?? [];
        if (isset($permissions[$module][$action])) {
            $permissions[$module][$action] = false;
        }
        $this->update(['permissions' => $permissions]);
    }

    public function getModulePermissions($module)
    {
        return $this->permissions[$module] ?? [];
    }

    public function getAllowedModules()
    {
        if (!$this->permissions) {
            return [];
        }

        $allowedModules = [];
        foreach ($this->permissions as $module => $actions) {
            if (is_array($actions) && array_filter($actions)) {
                $allowedModules[] = $module;
            }
        }

        return $allowedModules;
    }

    public function activate()
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate()
    {
        $this->update(['is_active' => false]);
    }

    // Additional helper methods for better functionality

    public function setModulePermissions($module, $permissions)
    {
        $currentPermissions = $this->permissions ?? [];
        $currentPermissions[$module] = $permissions;
        $this->update(['permissions' => $currentPermissions]);
    }

    public function hasModuleAccess($module)
    {
        $permissions = $this->permissions ?? [];
        return isset($permissions[$module]) && !empty(array_filter($permissions[$module]));
    }

    public function getAccessibleModules()
    {
        $permissions = $this->permissions ?? [];
        $accessibleModules = [];
        
        foreach ($permissions as $module => $modulePermissions) {
            if (!empty(array_filter($modulePermissions))) {
                $accessibleModules[] = $module;
            }
        }
        
        return $accessibleModules;
    }

    public function assignToUser($userId)
    {
        $this->users()->syncWithoutDetaching([$userId]);
    }

    public function removeFromUser($userId)
    {
        $this->users()->detach($userId);
    }

    public function getAssignedUsers()
    {
        return $this->users()->get();
    }
}
