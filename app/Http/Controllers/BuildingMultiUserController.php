<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\User;
use App\Models\BuildingUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class BuildingMultiUserController extends Controller
{
    /**
     * Get building users
     */
    public function getBuildingUsers(Request $request)
    {
        try {
            $request->validate([
                'building_id' => 'required|exists:buildings,id',
                'active_only' => 'boolean'
            ]);

            $building = Building::findOrFail($request->building_id);
            $activeOnly = $request->active_only ?? true;
            
            $users = BuildingUser::getBuildingUsers($building->id, $activeOnly);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'building' => [
                        'id' => $building->id,
                        'name' => $building->name,
                        'nama_gedung' => $building->building_name
                    ],
                    'users' => $users,
                    'total_users' => $users->count(),
                    'primary_user' => $building->getPrimaryUser()
                ],
                'message' => 'Building users retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get building users: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign user to building
     */
    public function assignUser(Request $request)
    {
        try {
            $request->validate([
                'building_id' => 'required|exists:buildings,id',
                'user_id' => 'required|exists:users,id',
                'role' => 'required|in:admin,manager,user,viewer',
                'is_primary' => 'boolean',
                'notes' => 'nullable|string|max:500'
            ]);

            $building = Building::findOrFail($request->building_id);
            $user = User::findOrFail($request->user_id);

            // Check if user is already assigned to this building
            if ($building->hasUser($user->id)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User is already assigned to this building'
                ], 422);
            }

            $buildingUser = $building->assignUser(
                $user->id,
                $request->role,
                $request->is_primary ?? false,
                $request->notes
            );

            return response()->json([
                'status' => 'success',
                'data' => [
                    'building_user' => $buildingUser,
                    'building' => [
                        'id' => $building->id,
                        'name' => $building->name
                    ],
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email
                    ]
                ],
                'message' => 'User assigned to building successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to assign user to building: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove user from building
     */
    public function removeUser(Request $request)
    {
        try {
            $request->validate([
                'building_id' => 'required|exists:buildings,id',
                'user_id' => 'required|exists:users,id'
            ]);

            $building = Building::findOrFail($request->building_id);
            $user = User::findOrFail($request->user_id);

            if (!$building->hasUser($user->id)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User is not assigned to this building'
                ], 422);
            }

            $success = $building->removeUser($user->id);

            if ($success) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'User removed from building successfully'
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to remove user from building'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to remove user from building: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set primary user for building
     */
    public function setPrimaryUser(Request $request)
    {
        try {
            $request->validate([
                'building_id' => 'required|exists:buildings,id',
                'user_id' => 'required|exists:users,id'
            ]);

            $building = Building::findOrFail($request->building_id);
            $user = User::findOrFail($request->user_id);

            if (!$building->hasUser($user->id)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User is not assigned to this building'
                ], 422);
            }

            $success = $building->setPrimaryUser($user->id);

            if ($success) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Primary user set successfully'
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to set primary user'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to set primary user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user role in building
     */
    public function updateUserRole(Request $request)
    {
        try {
            $request->validate([
                'building_id' => 'required|exists:buildings,id',
                'user_id' => 'required|exists:users,id',
                'role' => 'required|in:admin,manager,user,viewer',
                'notes' => 'nullable|string|max:500'
            ]);

            $buildingUser = BuildingUser::where('building_id', $request->building_id)
                ->where('user_id', $request->user_id)
                ->where('is_active', true)
                ->first();

            if (!$buildingUser) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User is not assigned to this building'
                ], 422);
            }

            $buildingUser->update([
                'role' => $request->role,
                'notes' => $request->notes,
                'updated_by' => auth()->id()
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $buildingUser,
                'message' => 'User role updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update user role: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user buildings
     */
    public function getUserBuildings(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'active_only' => 'boolean'
            ]);

            $user = User::findOrFail($request->user_id);
            $activeOnly = $request->active_only ?? true;
            
            $buildings = BuildingUser::getUserBuildings($user->id, $activeOnly);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email
                    ],
                    'buildings' => $buildings,
                    'total_buildings' => $buildings->count(),
                    'primary_buildings' => $user->getPrimaryBuildings()
                ],
                'message' => 'User buildings retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get user buildings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get building statistics
     */
    public function getBuildingStatistics(Request $request)
    {
        try {
            $request->validate([
                'building_id' => 'nullable|exists:buildings,id'
            ]);

            if ($request->building_id) {
                // Statistics for specific building
                $building = Building::findOrFail($request->building_id);
                
                $stats = [
                    'building' => [
                        'id' => $building->id,
                        'name' => $building->name
                    ],
                    'total_users' => $building->getUserCount(),
                    'users_by_role' => [
                        'admin' => $building->getUsersByRole('admin')->count(),
                        'manager' => $building->getUsersByRole('manager')->count(),
                        'user' => $building->getUsersByRole('user')->count(),
                        'viewer' => $building->getUsersByRole('viewer')->count()
                    ],
                    'primary_user' => $building->getPrimaryUser(),
                    'active_users' => $building->getActiveUsers()->count()
                ];
            } else {
                // Global statistics
                $stats = [
                    'total_buildings' => Building::count(),
                    'total_building_users' => BuildingUser::where('is_active', true)->count(),
                    'buildings_with_users' => Building::whereHas('buildingUsers', function($query) {
                        $query->where('is_active', true);
                    })->count(),
                    'users_with_buildings' => User::whereHas('buildingUsers', function($query) {
                        $query->where('is_active', true);
                    })->count(),
                    'role_distribution' => [
                        'admin' => BuildingUser::where('role', 'admin')->where('is_active', true)->count(),
                        'manager' => BuildingUser::where('role', 'manager')->where('is_active', true)->count(),
                        'user' => BuildingUser::where('role', 'user')->where('is_active', true)->count(),
                        'viewer' => BuildingUser::where('role', 'viewer')->where('is_active', true)->count()
                    ]
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => $stats,
                'message' => 'Building statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get building statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk assign users to building
     */
    public function bulkAssignUsers(Request $request)
    {
        try {
            $request->validate([
                'building_id' => 'required|exists:buildings,id',
                'users' => 'required|array|min:1',
                'users.*.user_id' => 'required|exists:users,id',
                'users.*.role' => 'required|in:admin,manager,user,viewer',
                'users.*.is_primary' => 'boolean',
                'users.*.notes' => 'nullable|string|max:500'
            ]);

            $building = Building::findOrFail($request->building_id);
            $results = [];
            $errors = [];

            DB::beginTransaction();

            foreach ($request->users as $userData) {
                try {
                    $user = User::findOrFail($userData['user_id']);
                    
                    if ($building->hasUser($user->id)) {
                        $errors[] = "User {$user->name} is already assigned to this building";
                        continue;
                    }

                    $buildingUser = $building->assignUser(
                        $user->id,
                        $userData['role'],
                        $userData['is_primary'] ?? false,
                        $userData['notes'] ?? null
                    );

                    $results[] = [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'role' => $userData['role'],
                        'is_primary' => $userData['is_primary'] ?? false,
                        'status' => 'success'
                    ];

                } catch (\Exception $e) {
                    $errors[] = "Failed to assign user {$userData['user_id']}: " . $e->getMessage();
                }
            }

            if (empty($errors)) {
                DB::commit();
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'assigned_users' => $results,
                        'total_assigned' => count($results)
                    ],
                    'message' => 'Users assigned to building successfully'
                ]);
            } else {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'data' => [
                        'assigned_users' => $results,
                        'errors' => $errors
                    ],
                    'message' => 'Some users could not be assigned'
                ], 422);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to bulk assign users: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available users for building assignment
     */
    public function getAvailableUsers(Request $request)
    {
        try {
            $request->validate([
                'building_id' => 'required|exists:buildings,id',
                'search' => 'nullable|string|max:255',
                'limit' => 'nullable|integer|min:1|max:100'
            ]);

            $building = Building::findOrFail($request->building_id);
            $search = $request->search;
            $limit = $request->limit ?? 20;

            // Get users not assigned to this building
            $query = User::whereDoesntHave('buildingUsers', function($q) use ($building) {
                $q->where('building_id', $building->id)
                  ->where('is_active', true);
            })
            ->where('is_active', true);

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('username', 'like', "%{$search}%");
                });
            }

            $users = $query->select('id', 'name', 'email', 'username', 'roles')
                ->limit($limit)
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'building' => [
                        'id' => $building->id,
                        'name' => $building->name
                    ],
                    'available_users' => $users,
                    'total_available' => $users->count()
                ],
                'message' => 'Available users retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get available users: ' . $e->getMessage()
            ], 500);
        }
    }
}
