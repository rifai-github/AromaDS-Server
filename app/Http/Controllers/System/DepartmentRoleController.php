<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Traits\ColumnFilterTrait;
use App\Models\Department;
use App\Models\Role;
use App\Models\DepartmentRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentRoleController extends Controller
{
    use ColumnFilterTrait;
    public function index(Request $request)
    {
        $query = Department::with(['departmentRoles.role', 'createdBy', 'updatedBy']);
        
        // Apply column filters using the trait
        $this->applyColumnFilters($query);
        
        $departments = $query->get();
        $roles = Role::where('is_active', true)->get();
        
        return view('system.department-roles.index', compact('departments', 'roles'));
    }

    public function show($id)
    {
        $department = Department::findOrFail($id);
        $department->load(['departmentRoles.role', 'users']);
        
        // Count users in this department
        $usersCount = $department->users->count();
        $department->users_count = $usersCount;
        
        // Get assigned roles
        $assignedRoles = $department->departmentRoles->pluck('role.name')->toArray();
        $department->assigned_roles = $assignedRoles;
        
        // Debug log
        \Log::info('Department Show Debug:', [
            'department_id' => $department->id,
            'department_name' => $department->name,
            'users_count' => $usersCount,
            'assigned_roles' => $assignedRoles
        ]);
        
        return response()->json([
            'status' => 'success',
            'department' => $department
        ]);
    }

    public function create()
    {
        $departments = Department::where('is_active', true)->get();
        $roles = Role::where('is_active', true)->get();
        
        return response()->json([
            'status' => 'success',
            'departments' => $departments,
            'roles' => $roles
        ]);
    }

    public function edit(DepartmentRole $departmentRole)
    {
        $departmentRole->load(['department', 'role']);
        $departments = Department::where('is_active', true)->get();
        $roles = Role::where('is_active', true)->get();
        
        return response()->json([
            'status' => 'success',
            'departmentRole' => $departmentRole,
            'departments' => $departments,
            'roles' => $roles
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'role_id' => 'required|exists:roles,id',
        ]);

        try {
            DepartmentRole::updateOrCreate(
                [
                    'department_id' => $request->department_id,
                    'role_id' => $request->role_id
                ],
                [
                    'is_active' => true
                ]
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Department role assigned successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error assigning role: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, DepartmentRole $departmentRole)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'is_active' => 'boolean'
        ]);

        try {
            $departmentRole->update([
                'role_id' => $request->role_id,
                'is_active' => $request->is_active ?? true
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Department role updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating role: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(DepartmentRole $departmentRole)
    {
        try {
            $departmentRole->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Department role removed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error removing role: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkAssignUsers(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'role_id' => 'required|exists:roles,id',
        ]);

        try {
            DB::beginTransaction();

            // Get all users in the department
            $users = User::where('department_id', $request->department_id)
                        ->where('is_active', true)
                        ->get();

            $assignedCount = 0;
            foreach ($users as $user) {
                // Only assign if user doesn't have individual role set
                if (!$user->roles) {
                    $user->update(['roles' => null]); // Clear individual role to use department role
                    $assignedCount++;
                }
            }

            // Update department role mapping
            DepartmentRole::updateOrCreate(
                [
                    'department_id' => $request->department_id,
                    'role_id' => $request->role_id
                ],
                [
                    'is_active' => true
                ]
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => "Role assigned to department. {$assignedCount} users will now use this role.",
                'assigned_count' => $assignedCount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Error assigning role to department: ' . $e->getMessage()
            ], 500);
        }
    }
}
