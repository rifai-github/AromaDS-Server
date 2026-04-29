<?php

namespace App\Services\System;

use App\Models\RoleGroup;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RoleGroupService
{
    /**
     * Create a new role group
     */
    public function createRoleGroup($data)
    {
        try {
            DB::beginTransaction();

            $roleGroup = RoleGroup::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'permissions' => $data['permissions'] ?? [],
                'is_active' => $data['is_active'] ?? true,
                'system_reserved' => $data['system_reserved'] ?? false,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id()
            ]);

            // Add roles if provided
            if (isset($data['role_ids']) && is_array($data['role_ids'])) {
                $roleGroup->syncRoles($data['role_ids']);
            }

            DB::commit();

            return [
                'status' => 'success',
                'message' => 'Role group created successfully',
                'data' => $roleGroup->load(['roles', 'createdBy'])
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create role group: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Update role group
     */
    public function updateRoleGroup($roleGroupId, $data)
    {
        try {
            DB::beginTransaction();

            $roleGroup = RoleGroup::findOrFail($roleGroupId);
            
            // Check if system reserved role group can be modified
            if ($roleGroup->system_reserved && isset($data['name']) && $data['name'] !== $roleGroup->name) {
                throw new \Exception("System reserved role group name cannot be changed");
            }

            $roleGroup->update([
                'name' => $data['name'] ?? $roleGroup->name,
                'description' => $data['description'] ?? $roleGroup->description,
                'permissions' => $data['permissions'] ?? $roleGroup->permissions,
                'is_active' => $data['is_active'] ?? $roleGroup->is_active,
                'updated_by' => auth()->id()
            ]);

            // Update roles if provided
            if (isset($data['role_ids']) && is_array($data['role_ids'])) {
                $roleGroup->syncRoles($data['role_ids']);
            }

            DB::commit();

            return [
                'status' => 'success',
                'message' => 'Role group updated successfully',
                'data' => $roleGroup->load(['roles', 'updatedBy'])
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update role group: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete role group
     */
    public function deleteRoleGroup($roleGroupId)
    {
        try {
            DB::beginTransaction();

            $roleGroup = RoleGroup::findOrFail($roleGroupId);
            
            // Check if role group can be deleted
            if (!$roleGroup->canBeDeleted()) {
                throw new \Exception("Role group cannot be deleted. It may be system reserved or has associated roles.");
            }

            $roleGroup->delete();

            DB::commit();

            return [
                'status' => 'success',
                'message' => 'Role group deleted successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to delete role group: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get role group with details
     */
    public function getRoleGroup($roleGroupId)
    {
        $roleGroup = RoleGroup::with(['roles', 'createdBy', 'updatedBy'])->findOrFail($roleGroupId);
        
        return [
            'status' => 'success',
            'data' => $roleGroup
        ];
    }

    /**
     * Get all role groups with filters
     */
    public function getRoleGroups($filters = [])
    {
        $query = RoleGroup::with(['roles', 'createdBy', 'updatedBy']);

        // Apply filters
        if (isset($filters['search']) && $filters['search']) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['system_reserved'])) {
            $query->where('system_reserved', $filters['system_reserved']);
        }

        if (isset($filters['role_id'])) {
            $query->whereHas('roles', function($q) use ($filters) {
                $q->where('role_id', $filters['role_id']);
            });
        }

        $roleGroups = $query->withCount('roles')->orderBy('name')->paginate(25);

        return [
            'status' => 'success',
            'data' => $roleGroups->items(),
            'pagination' => [
                'total' => $roleGroups->total(),
                'per_page' => $roleGroups->perPage(),
                'current_page' => $roleGroups->currentPage(),
                'last_page' => $roleGroups->lastPage(),
                'from' => $roleGroups->firstItem(),
                'to' => $roleGroups->lastItem(),
            ]
        ];
    }

    /**
     * Add role to role group
     */
    public function addRoleToGroup($roleGroupId, $roleId)
    {
        try {
            $roleGroup = RoleGroup::findOrFail($roleGroupId);
            $role = Role::findOrFail($roleId);

            if ($roleGroup->hasRole($roleId)) {
                throw new \Exception("Role is already in this role group");
            }

            $roleGroup->addRole($roleId);

            return [
                'status' => 'success',
                'message' => 'Role added to role group successfully',
                'data' => $roleGroup->load(['roles'])
            ];

        } catch (\Exception $e) {
            Log::error("Failed to add role to role group: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Remove role from role group
     */
    public function removeRoleFromGroup($roleGroupId, $roleId)
    {
        try {
            $roleGroup = RoleGroup::findOrFail($roleGroupId);

            if (!$roleGroup->hasRole($roleId)) {
                throw new \Exception("Role is not in this role group");
            }

            $roleGroup->removeRole($roleId);

            return [
                'status' => 'success',
                'message' => 'Role removed from role group successfully',
                'data' => $roleGroup->load(['roles'])
            ];

        } catch (\Exception $e) {
            Log::error("Failed to remove role from role group: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get available roles for role group
     */
    public function getAvailableRoles($roleGroupId = null)
    {
        $query = Role::where('is_active', true);

        if ($roleGroupId) {
            $roleGroup = RoleGroup::findOrFail($roleGroupId);
            $existingRoleIds = $roleGroup->roles()->pluck('role_id')->toArray();
            $query->whereNotIn('id', $existingRoleIds);
        }

        $roles = $query->orderBy('name')->get();

        return [
            'status' => 'success',
            'data' => $roles
        ];
    }

    /**
     * Get role group statistics
     */
    public function getRoleGroupStatistics()
    {
        $totalRoleGroups = RoleGroup::count();
        $activeRoleGroups = RoleGroup::where('is_active', true)->count();
        $systemReservedRoleGroups = RoleGroup::where('system_reserved', true)->count();
        $userCreatedRoleGroups = RoleGroup::where('system_reserved', false)->count();

        $roleGroupsWithRoles = RoleGroup::whereHas('roles')->count();
        $roleGroupsWithoutRoles = RoleGroup::whereDoesntHave('roles')->count();

        $averageRolesPerGroup = $totalRoleGroups > 0 ? 
            round(RoleGroup::withCount('roles')->get()->avg('roles_count'), 2) : 0;

        return [
            'total_role_groups' => $totalRoleGroups,
            'active_role_groups' => $activeRoleGroups,
            'system_reserved_role_groups' => $systemReservedRoleGroups,
            'user_created_role_groups' => $userCreatedRoleGroups,
            'role_groups_with_roles' => $roleGroupsWithRoles,
            'role_groups_without_roles' => $roleGroupsWithoutRoles,
            'average_roles_per_group' => $averageRolesPerGroup
        ];
    }

    /**
     * Duplicate role group
     */
    public function duplicateRoleGroup($roleGroupId, $newName)
    {
        try {
            DB::beginTransaction();

            $originalRoleGroup = RoleGroup::with('roles')->findOrFail($roleGroupId);
            
            if ($originalRoleGroup->system_reserved) {
                throw new \Exception("System reserved role groups cannot be duplicated");
            }

            $newRoleGroup = $originalRoleGroup->duplicate($newName);

            DB::commit();

            return [
                'status' => 'success',
                'message' => 'Role group duplicated successfully',
                'data' => $newRoleGroup->load(['roles', 'createdBy'])
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to duplicate role group: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get role group permissions
     */
    public function getRoleGroupPermissions($roleGroupId)
    {
        $roleGroup = RoleGroup::with('roles')->findOrFail($roleGroupId);
        
        $permissions = $roleGroup->getAllPermissions();

        return [
            'status' => 'success',
            'data' => [
                'role_group' => $roleGroup,
                'permissions' => $permissions,
                'permission_count' => count($permissions)
            ]
        ];
    }

    /**
     * Validate role group
     */
    public function validateRoleGroup($data, $roleGroupId = null)
    {
        $errors = [];
        $warnings = [];

        // Check name uniqueness
        $nameQuery = RoleGroup::where('name', $data['name']);
        if ($roleGroupId) {
            $nameQuery->where('id', '!=', $roleGroupId);
        }
        
        if ($nameQuery->exists()) {
            $errors[] = "Role group name already exists";
        }

        // Check if system reserved role group is being modified
        if ($roleGroupId) {
            $roleGroup = RoleGroup::find($roleGroupId);
            if ($roleGroup && $roleGroup->system_reserved) {
                if (isset($data['name']) && $data['name'] !== $roleGroup->name) {
                    $errors[] = "System reserved role group name cannot be changed";
                }
            }
        }

        // Check role IDs if provided
        if (isset($data['role_ids']) && is_array($data['role_ids'])) {
            $validRoleIds = Role::whereIn('id', $data['role_ids'])->pluck('id')->toArray();
            $invalidRoleIds = array_diff($data['role_ids'], $validRoleIds);
            
            if (!empty($invalidRoleIds)) {
                $errors[] = "Invalid role IDs: " . implode(', ', $invalidRoleIds);
            }
        }

        return [
            'is_valid' => count($errors) === 0,
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
}
