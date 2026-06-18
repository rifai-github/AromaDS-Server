<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\AccessManagement;
use Illuminate\Http\Request;

class AccessManagementController extends Controller
{
    public function index()
    {
        $accesses = AccessManagement::with(['creator', 'updater'])
            ->orderBy('created_at', 'desc')
            ->paginateStd(25);
        return view('company.access-management.index', compact('accesses'));
    }

    public function create()
    {
        return view('company.access-management.create');
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'regional_table_view' => 'nullable|string|max:255',
                'permissions' => 'nullable|array',
                'is_active' => 'required|in:0,1,true,false'
            ]);

            $access = AccessManagement::create([
                'name' => $request->name,
                'description' => $request->description,
                'regional_table_view' => $request->regional_table_view,
                'permissions' => $request->permissions ?? [],
                'is_active' => $request->is_active === '1' || $request->is_active === 'true' || $request->is_active === true,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Access created successfully',
                'data' => $access
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error creating access: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $access = AccessManagement::findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $access
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access not found'
            ], 404);
        }
    }

    public function edit($id)
    {
        try {
            $access = AccessManagement::findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $access
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access not found'
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'regional_table_view' => 'nullable|string|max:255',
                'permissions' => 'nullable|array',
                'is_active' => 'required|in:0,1,true,false'
            ]);

            $access = AccessManagement::findOrFail($id);
            $access->update([
                'name' => $request->name,
                'description' => $request->description,
                'regional_table_view' => $request->regional_table_view,
                'permissions' => $request->permissions ?? [],
                'is_active' => $request->is_active === '1' || $request->is_active === 'true' || $request->is_active === true,
                'updated_by' => auth()->id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Access updated successfully',
                'data' => $access
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating access: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $access = AccessManagement::findOrFail($id);
            $access->delete(); // This will use soft delete

            return response()->json([
                'status' => 'success',
                'message' => 'Access hidden successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error hiding access: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'integer|exists:access_managements,id'
            ]);

            $count = AccessManagement::whereIn('id', $request->ids)->delete(); // This will use soft delete

            return response()->json([
                'success' => true,
                'message' => 'Accesses hidden successfully',
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error hiding accesses: ' . $e->getMessage()
            ], 500);
        }
    }

    public function assignUsers(Request $request, $id)
    {
        try {
            $request->validate([
                'user_ids' => 'required|array',
                'user_ids.*' => 'integer|exists:users,id'
            ]);

            $access = AccessManagement::findOrFail($id);
            $access->users()->sync($request->user_ids);

            return response()->json([
                'status' => 'success',
                'message' => 'Users assigned successfully',
                'data' => $access->load('users')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error assigning users: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getAssignedUsers($id)
    {
        try {
            $access = AccessManagement::findOrFail($id);
            $users = $access->users()->get();

            return response()->json([
                'status' => 'success',
                'data' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching assigned users: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getAvailableUsers($id)
    {
        try {
            $access = AccessManagement::findOrFail($id);
            $assignedUserIds = $access->users()->pluck('users.id')->toArray();
            
            $availableUsers = \App\Models\User::whereNotIn('id', $assignedUserIds)
                ->where('is_active', true)
                ->select('id', 'name', 'email', 'username')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $availableUsers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching available users: ' . $e->getMessage()
            ], 500);
        }
    }
}
