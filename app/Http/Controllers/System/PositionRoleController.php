<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Role;
use App\Models\PositionRole;
use App\Models\MasterOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PositionRoleController extends Controller
{
    public function index(Request $request)
    {
        // Use PositionRole as base model for better filtering
        $query = PositionRole::with(['department', 'role']);

        // Apply filters if present
        if ($request->has('filter')) {
            $filters = $request->input('filter', []);

            if (!empty($filters['department__name'])) {
                $query->whereHas('department', function ($q) use ($filters) {
                    $q->where('name', 'LIKE', '%' . $filters['department__name'] . '%');
                });
            }

            if (!empty($filters['position_name'])) {
                $query->where('position_name', 'LIKE', '%' . $filters['position_name'] . '%');
            }

            if (!empty($filters['role__name'])) {
                $query->whereHas('role', function ($q) use ($filters) {
                    $q->where('name', 'LIKE', '%' . $filters['role__name'] . '%');
                });
            }

            if (!empty($filters['is_active'])) {
                $query->where('is_active', $filters['is_active'] === '1');
            }
        }

        $positionRoles = $query->paginate(15)->withQueryString();

        // For modal dropdowns - get unique departments from position roles
        $departments = collect();
        foreach ($positionRoles as $positionRole) {
            if ($positionRole->department) {
                $departments->push($positionRole->department);
            }
        }
        $departments = $departments->unique('id');

        // For modal dropdowns
        $roles = Role::where('is_active', true)->get();
        $positionOption = MasterOption::where('name', 'Position')->first();
        $positions = $positionOption ? $positionOption->optionDetails : collect();

        return view('system.position-roles.index', compact('positionRoles', 'roles', 'positions', 'departments'));
    }

    public function show(PositionRole $positionRole)
    {
        $positionRole->load(['department', 'role']);
        
        // Count users with this position in this department
        $usersCount = \App\Models\User::where('department_id', $positionRole->department_id)
            ->where('position_name', $positionRole->position_name)
            ->count();
        
        $positionRole->users_count = $usersCount;
        
        return response()->json([
            'status' => 'success',
            'positionRole' => $positionRole
        ]);
    }

    public function create()
    {
        $departments = Department::where('is_active', true)->get();
        $roles = Role::where('is_active', true)->get();
        
        // Get position options from master options
        $positionOption = MasterOption::where('name', 'Position')->first();
        $positions = $positionOption ? $positionOption->optionDetails : collect();
        
        return response()->json([
            'status' => 'success',
            'departments' => $departments,
            'roles' => $roles,
            'positions' => $positions
        ]);
    }

    public function edit(PositionRole $positionRole)
    {
        $positionRole->load(['department', 'role']);
        $departments = Department::where('is_active', true)->get();
        $roles = Role::where('is_active', true)->get();
        
        // Get position options from master options
        $positionOption = MasterOption::where('name', 'Position')->first();
        $positions = $positionOption ? $positionOption->optionDetails : collect();
        
        return response()->json([
            'status' => 'success',
            'positionRole' => $positionRole,
            'departments' => $departments,
            'roles' => $roles,
            'positions' => $positions
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'position_name' => 'required|string|max:255',
            'role_id' => 'required|exists:roles,id',
        ]);

        try {
            PositionRole::updateOrCreate(
                [
                    'department_id' => $request->department_id,
                    'position_name' => $request->position_name,
                    'role_id' => $request->role_id
                ],
                [
                    'is_active' => true
                ]
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Position role assigned successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error assigning role: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, PositionRole $positionRole)
    {
        $request->validate([
            'position_name' => 'required|string|max:255',
            'role_id' => 'required|exists:roles,id',
            'is_active' => 'boolean'
        ]);

        try {
            $positionRole->update([
                'position_name' => $request->position_name,
                'role_id' => $request->role_id,
                'is_active' => $request->is_active ?? true
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Position role updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating role: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(PositionRole $positionRole)
    {
        try {
            $positionRole->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Position role removed successfully'
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
            'position_name' => 'required|string|max:255',
            'role_id' => 'required|exists:roles,id',
        ]);

        try {
            DB::beginTransaction();

            // Get all users in the department with specific position
            $users = User::where('department_id', $request->department_id)
                        ->where('position', $request->position_name)
                        ->where('is_active', true)
                        ->get();

            $assignedCount = 0;
            foreach ($users as $user) {
                // Only assign if user doesn't have individual role set
                if (!$user->roles) {
                    $assignedCount++;
                }
            }

            // Update position role mapping
            PositionRole::updateOrCreate(
                [
                    'department_id' => $request->department_id,
                    'position_name' => $request->position_name,
                    'role_id' => $request->role_id
                ],
                [
                    'is_active' => true
                ]
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => "Role assigned to position. {$assignedCount} users will now use this role.",
                'assigned_count' => $assignedCount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Error assigning role to position: ' . $e->getMessage()
            ], 500);
        }
    }
}
