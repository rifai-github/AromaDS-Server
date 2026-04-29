<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\RoleGroup;
use App\Models\Role;
use App\Services\System\RoleGroupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class RoleGroupController extends Controller
{
    protected $roleGroupService;

    public function __construct(RoleGroupService $roleGroupService)
    {
        $this->roleGroupService = $roleGroupService;
    }

    /**
     * Display a listing of role groups
     */
    public function index(Request $request)
    {
        $filters = [
            'search' => $request->search,
            'is_active' => $request->is_active,
            'system_reserved' => $request->system_reserved,
            'role_id' => $request->role_id
        ];

        $result = $this->roleGroupService->getRoleGroups($filters);

        if ($request->ajax()) {
            return response()->json($result);
        }

        $roleGroups = $result['data'];
        $pagination = $result['pagination'];

        // Get filter options
        $roles = Role::where('is_active', true)->orderBy('name')->get();

        return view('system.role-groups.index', compact('roleGroups', 'pagination', 'roles'));
    }

    /**
     * Show the form for creating a new role group
     */
    public function create()
    {
        $roles = Role::where('is_active', true)->orderBy('name')->get();
        
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'roles' => $roles
                ]
            ]);
        }

        return view('system.role-groups.create', compact('roles'));
    }

    /**
     * Store a newly created role group
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
            'is_active' => 'required|boolean',
            'system_reserved' => 'boolean'
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $result = $this->roleGroupService->createRoleGroup($request->all());

        if ($result['status'] === 'success') {
            if ($request->ajax()) {
                return response()->json($result);
            }
            
            return redirect()
                ->route('system.role-groups.index')
                ->with('success', $result['message']);
        } else {
            if ($request->ajax()) {
                return response()->json($result, 400);
            }
            
            return back()
                ->withInput()
                ->with('error', $result['message']);
        }
    }

    /**
     * Display the specified role group
     */
    public function show(RoleGroup $roleGroup)
    {
        $result = $this->roleGroupService->getRoleGroup($roleGroup->id);
        
        if (request()->ajax()) {
            return response()->json($result);
        }
        
        $roleGroup = $result['data'];
        return view('system.role-groups.show', compact('roleGroup'));
    }

    /**
     * Show the form for editing the specified role group
     */
    public function edit(RoleGroup $roleGroup)
    {
        $roles = Role::where('is_active', true)->orderBy('name')->get();
        $roleGroup->load(['roles', 'createdBy', 'updatedBy']);
        
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'roleGroup' => $roleGroup,
                    'roles' => $roles
                ]
            ]);
        }

        return view('system.role-groups.edit', compact('roleGroup', 'roles'));
    }

    /**
     * Update the specified role group
     */
    public function update(Request $request, RoleGroup $roleGroup)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
            'is_active' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $result = $this->roleGroupService->updateRoleGroup($roleGroup->id, $request->all());

        if ($result['status'] === 'success') {
            if ($request->ajax()) {
                return response()->json($result);
            }
            
            return redirect()
                ->route('system.role-groups.show', $roleGroup->id)
                ->with('success', $result['message']);
        } else {
            if ($request->ajax()) {
                return response()->json($result, 400);
            }
            
            return back()
                ->withInput()
                ->with('error', $result['message']);
        }
    }

    /**
     * Remove the specified role group
     */
    public function destroy(RoleGroup $roleGroup)
    {
        $result = $this->roleGroupService->deleteRoleGroup($roleGroup->id);

        if ($result['status'] === 'success') {
            if (request()->ajax()) {
                return response()->json($result);
            }
            
            return redirect()
                ->route('system.role-groups.index')
                ->with('success', $result['message']);
        } else {
            if (request()->ajax()) {
                return response()->json($result, 400);
            }
            
            return back()->with('error', $result['message']);
        }
    }

    /**
     * Add role to role group
     */
    public function addRole(Request $request, RoleGroup $roleGroup)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id'
        ]);

        $result = $this->roleGroupService->addRoleToGroup($roleGroup->id, $request->role_id);

        return response()->json($result);
    }

    /**
     * Remove role from role group
     */
    public function removeRole(Request $request, RoleGroup $roleGroup)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id'
        ]);

        $result = $this->roleGroupService->removeRoleFromGroup($roleGroup->id, $request->role_id);

        return response()->json($result);
    }

    /**
     * Get available roles for role group
     */
    public function getAvailableRoles(RoleGroup $roleGroup = null)
    {
        $roleGroupId = $roleGroup ? $roleGroup->id : null;
        $result = $this->roleGroupService->getAvailableRoles($roleGroupId);

        return response()->json($result);
    }

    /**
     * Get role group statistics
     */
    public function statistics()
    {
        $stats = $this->roleGroupService->getRoleGroupStatistics();
        
        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }

    /**
     * Duplicate role group
     */
    public function duplicate(Request $request, RoleGroup $roleGroup)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $result = $this->roleGroupService->duplicateRoleGroup($roleGroup->id, $request->name);

        return response()->json($result);
    }

    /**
     * Get role group permissions
     */
    public function getPermissions(RoleGroup $roleGroup)
    {
        $result = $this->roleGroupService->getRoleGroupPermissions($roleGroup->id);

        return response()->json($result);
    }

    /**
     * Validate role group
     */
    public function validateRoleGroup(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
            'is_active' => 'required|boolean',
            'system_reserved' => 'boolean'
        ]);

        $roleGroupId = $request->get('role_group_id');
        $result = $this->roleGroupService->validateRoleGroup($request->all(), $roleGroupId);

        return response()->json($result);
    }

    /**
     * Bulk operations
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'role_group_ids' => 'required|array',
            'role_group_ids.*' => 'exists:role_groups,id'
        ]);

        $action = $request->action;
        $roleGroupIds = $request->role_group_ids;
        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($roleGroupIds as $roleGroupId) {
            try {
                $roleGroup = RoleGroup::findOrFail($roleGroupId);
                
                switch ($action) {
                    case 'activate':
                        $roleGroup->update(['is_active' => true, 'updated_by' => Auth::id()]);
                        break;
                    case 'deactivate':
                        $roleGroup->update(['is_active' => false, 'updated_by' => Auth::id()]);
                        break;
                    case 'delete':
                        if (!$roleGroup->canBeDeleted()) {
                            $errors[] = "Role group '{$roleGroup->name}' cannot be deleted";
                            $errorCount++;
                            break;
                        }
                        $roleGroup->delete();
                        break;
                }
                
                $successCount++;
            } catch (\Exception $e) {
                $errors[] = "Failed to {$action} role group ID {$roleGroupId}: " . $e->getMessage();
                $errorCount++;
            }
        }

        $message = "Bulk {$action} completed. Success: {$successCount}, Errors: {$errorCount}";
        
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'errors' => $errors
        ]);
    }
}
