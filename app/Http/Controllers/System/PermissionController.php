<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        $query = Permission::with(['users']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('permission_name', 'like', "%{$search}%")
                  ->orWhere('permission_code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        $permissions = $query->orderBy('module')->orderBy('action')->paginateStd(25);

        $modules = Permission::distinct()->pluck('module');
        $actions = Permission::distinct()->pluck('action');

        return view('system.permissions.index', compact('permissions', 'modules', 'actions'));
    }

    public function create()
    {
        $modules = Permission::distinct()->pluck('module');
        $actions = ['create', 'read', 'update', 'delete', 'manage'];
        $users = User::all();

        return view('system.permissions.create', compact('modules', 'actions', 'users'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'permission_name' => 'required|string|max:255|unique:permissions',
            'permission_code' => 'required|string|max:100|unique:permissions',
            'module' => 'required|string|max:100',
            'action' => 'required|string|max:50',
            'description' => 'nullable|string',
            'user_ids' => 'array',
            'user_ids.*' => 'exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $permission = Permission::create([
                'permission_name' => $request->permission_name,
                'permission_code' => $request->permission_code,
                'module' => $request->module,
                'action' => $request->action,
                'description' => $request->description,
                'created_by' => Auth::id()
            ]);

            // Attach users to permission
            if ($request->has('user_ids')) {
                $permission->users()->attach($request->user_ids);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Permission created successfully',
                'data' => $permission
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error creating permission: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Permission $permission)
    {
        $permission->load(['createdBy', 'users']);
        return view('system.permissions.show', compact('permission'));
    }

    public function edit(Permission $permission)
    {
        $modules = Permission::distinct()->pluck('module');
        $actions = ['create', 'read', 'update', 'delete', 'manage'];
        $users = User::all();
        $permission->load('users');

        return view('system.permissions.edit', compact('permission', 'modules', 'actions', 'users'));
    }

    public function update(Request $request, Permission $permission)
    {
        $validator = Validator::make($request->all(), [
            'permission_name' => 'required|string|max:255|unique:permissions,permission_name,' . $permission->id,
            'permission_code' => 'required|string|max:100|unique:permissions,permission_code,' . $permission->id,
            'module' => 'required|string|max:100',
            'action' => 'required|string|max:50',
            'description' => 'nullable|string',
            'user_ids' => 'array',
            'user_ids.*' => 'exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $permission->update([
                'permission_name' => $request->permission_name,
                'permission_code' => $request->permission_code,
                'module' => $request->module,
                'action' => $request->action,
                'description' => $request->description,
                'updated_by' => Auth::id()
            ]);

            // Sync users to permission
            if ($request->has('user_ids')) {
                $permission->users()->sync($request->user_ids);
            } else {
                $permission->users()->detach();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Permission updated successfully',
                'data' => $permission
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating permission: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Permission $permission)
    {
        try {
            // Detach all users first
            $permission->users()->detach();
            $permission->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Permission deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error deleting permission: ' . $e->getMessage()
            ], 500);
        }
    }

    public function dashboard()
    {
        $total_permissions = Permission::count();
        
        $permissions_by_module = Permission::selectRaw('module, count(*) as count')
            ->groupBy('module')
            ->get();

        $permissions_by_action = Permission::selectRaw('action, count(*) as count')
            ->groupBy('action')
            ->get();

        $most_assigned_permissions = Permission::withCount('users')
            ->orderBy('users_count', 'desc')
            ->limit(10)
            ->get();

        $recent_permissions = Permission::with('createdBy')
            ->withCount('users')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('system.permissions.dashboard', compact(
            'total_permissions',
            'permissions_by_module',
            'permissions_by_action',
            'most_assigned_permissions',
            'recent_permissions'
        ));
    }

    public function assignToUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'permission_id' => 'required|exists:permissions,id',
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $permission = Permission::find($request->permission_id);
            $user = User::find($request->user_id);
            
            $permission->users()->attach($user->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Permission assigned to user successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error assigning permission: ' . $e->getMessage()
            ], 500);
        }
    }

    public function removeFromUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'permission_id' => 'required|exists:permissions,id',
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $permission = Permission::find($request->permission_id);
            $user = User::find($request->user_id);
            
            $permission->users()->detach($user->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Permission removed from user successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error removing permission: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getUserPermissions(User $user)
    {
        $permissions = $user->permissions()->get();
        return response()->json([
            'status' => 'success',
            'data' => $permissions
        ]);
    }

    public function getPermissionUsers(Permission $permission)
    {
        $users = $permission->users()->get();
        return response()->json([
            'status' => 'success',
            'data' => $users
        ]);
    }

    public function bulkAssign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'exists:permissions,id',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            foreach ($request->permission_ids as $permission_id) {
                $permission = Permission::find($permission_id);
                $permission->users()->syncWithoutDetaching($request->user_ids);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Permissions assigned to users successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error bulk assigning permissions: ' . $e->getMessage()
            ], 500);
        }
    }
}
