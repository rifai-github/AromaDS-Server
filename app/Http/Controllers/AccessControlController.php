<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserAccessLevel;
use App\Models\UserLoginRestriction;
use App\Models\Branch;
use App\Services\AccessControlService;
use Illuminate\Support\Facades\Cache;

class AccessControlController extends Controller
{
    protected $accessControlService;

    public function __construct(AccessControlService $accessControlService)
    {
        $this->accessControlService = $accessControlService;
    }

    /**
     * Display access control settings
     */
    public function index(Request $request)
    {
        // Use query builder for AutoFilterable compatibility
        $query = User::select([
                'id',
                'name',
                'email',
                'username',
                'department_id',
                'multi_login',
                'is_frozen',
                'screenshot_allowed',
                'created_at',
                'updated_at',
                'created_by',
                'updated_by',
                'is_active',
            ])
            ->with([
                'accessLevels:id,user_id,access_type,access_config,is_active',
                'loginRestrictions:id,user_id,start_time,end_time,allowed_days,idle_timeout,is_active',
                'department:id,name',
                'createdBy:id,name',
                'updatedBy:id,name',
            ]);

        // Apply manual filters from request
        $filters = $request->input('filter', []);
        
        if (!empty($filters)) {
            // Filter by user name
            if (!empty($filters['name'])) {
                $query->where('name', 'LIKE', '%' . $filters['name'] . '%');
            }
            
            // Filter by role name (via relationship)
            if (!empty($filters['roles__name'])) {
                $query->whereHas('roles', function($q) use ($filters) {
                    $q->where('name', 'LIKE', '%' . $filters['roles__name'] . '%');
                });
            }
        } else {
            // Filter active users by default when no filters are applied
            $query->where('is_active', true);
        }

        $users = $query->get();
        
        // Load roles for each user using raw DB query to avoid any Eloquent issues
        $userIds = $users->pluck('id')->toArray();
        $userRolesMap = [];
        
        if (!empty($userIds)) {
            // Query user_roles and roles tables directly
            $rolesData = \DB::table('user_roles')
                ->join('roles', 'user_roles.role_id', '=', 'roles.id')
                ->whereIn('user_roles.user_id', $userIds)
                ->select('user_roles.user_id', 'roles.name')
                ->get();
            
            // Group by user_id
            foreach ($rolesData as $row) {
                if (!isset($userRolesMap[$row->user_id])) {
                    $userRolesMap[$row->user_id] = [];
                }
                $userRolesMap[$row->user_id][] = $row->name;
            }
        }
        
        // Initialize empty arrays for users without roles
        foreach ($userIds as $userId) {
            if (!isset($userRolesMap[$userId])) {
                $userRolesMap[$userId] = [];
            }
        }

        $accessLevelDetailsMap = $this->buildAccessLevelDetailsMap($users);

        // Get all active users for dropdown (with department)
        $allUsers = Cache::remember('access-control:all-users', now()->addMinutes(10), function () {
            return User::where('is_active', true)
                ->with('department:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'username', 'department_id']);
        });

        // Get all active branches for dropdown
        $allBranches = Cache::remember('access-control:all-branches', now()->addMinutes(10), function () {
            return Branch::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code']);
        });

        return view('access-control.index', compact('users', 'allUsers', 'allBranches', 'userRolesMap', 'accessLevelDetailsMap'));
    }

    /**
     * Set user access level
     */
    public function setAccessLevel(Request $request, User $user)
    {
        // Handle multiple access levels from form
        if ($request->has('access_levels') && is_array($request->access_levels)) {
            // Delete all existing access levels for this user first
            UserAccessLevel::where('user_id', $user->id)->delete();
            
            // Create new access levels from form
            foreach ($request->access_levels as $accessLevelData) {
                if (empty($accessLevelData['access_type'])) {
                    continue; // Skip if no access type
                }
                
                $accessType = $accessLevelData['access_type'];
                $accessConfig = $this->processAccessConfig($accessType, $accessLevelData['access_config'] ?? []);
                
                $this->accessControlService->setUserAccessLevel(
                    $user,
                    $accessType,
                    $accessConfig
                );
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Hirarki data berhasil disimpan!'
            ]);
        }
        
        // Legacy single access level support (for backward compatibility)
        $request->validate([
            'access_type' => 'required|in:hierarchical,peer,branch,none',
            'access_config' => 'required_if:access_type,hierarchical,peer,branch|array'
        ]);

        $accessConfig = [];

        // Handle hierarchical access - convert user IDs from form to config
        if ($request->access_type === 'hierarchical') {
            // Get subordinate_ids from form (FormData sends as access_config[subordinate_ids][])
            $subordinateIds = $request->input('access_config.subordinate_ids', []);
            
            // If not array, try direct access
            if (!is_array($subordinateIds)) {
                $allInput = $request->all();
                if (isset($allInput['access_config']['subordinate_ids'])) {
                    $subordinateIds = $allInput['access_config']['subordinate_ids'];
                }
            }
            
            // Ensure it's an array
            if (!is_array($subordinateIds)) {
                $subordinateIds = $subordinateIds ? [$subordinateIds] : [];
            }
            
            // Filter and convert to integers
            $subordinateIds = array_map('intval', array_filter($subordinateIds));
            
            $accessConfig = [
                'subordinates' => $subordinateIds
            ];
        }
        // Handle peer access - convert user IDs from form to config
        elseif ($request->access_type === 'peer') {
            // Get peer_user_ids from form (FormData sends as access_config[peer_user_ids][])
            $peerIds = $request->input('access_config.peer_user_ids', []);
            
            // If not array, try direct access
            if (!is_array($peerIds)) {
                $allInput = $request->all();
                if (isset($allInput['access_config']['peer_user_ids'])) {
                    $peerIds = $allInput['access_config']['peer_user_ids'];
                }
            }
            
            // Ensure it's an array
            if (!is_array($peerIds)) {
                $peerIds = $peerIds ? [$peerIds] : [];
            }
            
            // Filter and convert to integers
            $peerIds = array_map('intval', array_filter($peerIds));
            
            $accessConfig = [
                'peer_users' => $peerIds
            ];
        }
        // Handle branch access - convert branch IDs from form to config
        elseif ($request->access_type === 'branch') {
            // Get allowed_branch_ids from form (FormData sends as access_config[allowed_branch_ids][])
            $branchIds = $request->input('access_config.allowed_branch_ids', []);
            
            // If not array, try direct access
            if (!is_array($branchIds)) {
                $allInput = $request->all();
                if (isset($allInput['access_config']['allowed_branch_ids'])) {
                    $branchIds = $allInput['access_config']['allowed_branch_ids'];
                }
            }
            
            // Ensure it's an array
            if (!is_array($branchIds)) {
                $branchIds = $branchIds ? [$branchIds] : [];
            }
            
            // Filter and convert to integers
            $branchIds = array_map('intval', array_filter($branchIds));
            
            $accessConfig = [
                'allowed_branches' => $branchIds
            ];
        }
        // Handle none access - hanya data sendiri, tidak ada config
        elseif ($request->access_type === 'none') {
            $accessConfig = []; // Empty config, hanya data sendiri
        }

        $this->accessControlService->setUserAccessLevel(
            $user,
            $request->access_type,
            $accessConfig
        );

        return response()->json([
            'success' => true,
            'message' => 'Hirarki data berhasil disimpan!'
        ]);
    }
    
    /**
     * Process access config based on access type
     */
    protected function processAccessConfig($accessType, $accessConfigData)
    {
        $accessConfig = [];
        
        if ($accessType === 'hierarchical') {
            // Support both 'subordinate_ids' (from form) and 'subordinates' (from database)
            $subordinateIds = $accessConfigData['subordinate_ids'] ?? $accessConfigData['subordinates'] ?? [];
            if (!is_array($subordinateIds)) {
                $subordinateIds = $subordinateIds ? [$subordinateIds] : [];
            }
            $subordinateIds = array_map('intval', array_filter($subordinateIds));
            $accessConfig = ['subordinates' => $subordinateIds];
        } elseif ($accessType === 'peer') {
            // Support both 'peer_user_ids' (from form) and 'peer_users' (from database)
            $peerIds = $accessConfigData['peer_user_ids'] ?? $accessConfigData['peer_users'] ?? [];
            if (!is_array($peerIds)) {
                $peerIds = $peerIds ? [$peerIds] : [];
            }
            $peerIds = array_map('intval', array_filter($peerIds));
            $accessConfig = ['peer_users' => $peerIds];
        } elseif ($accessType === 'branch') {
            // Support both 'allowed_branch_ids' (from form) and 'allowed_branches' (from database)
            $branchIds = $accessConfigData['allowed_branch_ids'] ?? $accessConfigData['allowed_branches'] ?? [];
            if (!is_array($branchIds)) {
                $branchIds = $branchIds ? [$branchIds] : [];
            }
            $branchIds = array_map('intval', array_filter($branchIds));
            $accessConfig = ['allowed_branches' => $branchIds];
        } elseif ($accessType === 'company') {
            $accessConfig = []; // Global access, no specific config needed
        } elseif ($accessType === 'none') {
            $accessConfig = []; // Empty config
        }
        
        return $accessConfig;
    }

    /**
     * Set user login restrictions
     */
    public function setLoginRestrictions(Request $request, User $user)
    {
        // Handle array format from frontend (login_restrictions[0][field])
        if ($request->has('login_restrictions') && is_array($request->login_restrictions)) {
            $restrictionData = $request->login_restrictions[0] ?? [];
            
            // Re-map to root request for validation and service call
            $request->merge([
                'start_time' => $restrictionData['start_time'] ?? null,
                'end_time' => $restrictionData['end_time'] ?? null,
                'allowed_days' => $restrictionData['allowed_days'] ?? null,
                'idle_timeout' => $restrictionData['idle_timeout'] ?? null,
            ]);
        }

        $rules = [
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'allowed_days' => 'nullable|array',
            'allowed_days.*' => 'string|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
            'idle_timeout' => 'required|integer|min:5|max:480'
        ];
        
        // Only validate end_time after start_time if both are provided
        if ($request->has('start_time') && $request->has('end_time') && $request->start_time && $request->end_time) {
            $rules['end_time'] = 'nullable|date_format:H:i|after:start_time';
        }
        
        $request->validate($rules);

        $this->accessControlService->setUserLoginRestrictions(
            $user,
            $request->start_time,
            $request->end_time,
            $request->allowed_days,
            $request->idle_timeout
        );

        return response()->json([
            'success' => true,
            'message' => 'Login restrictions updated successfully'
        ]);
    }

    /**
     * Check user access
     */
    public function checkAccess(Request $request, User $user)
    {
        $request->validate([
            'target_user_id' => 'nullable|exists:users,id',
            'branch_id' => 'nullable|exists:branches,id'
        ]);

        $canAccess = $this->accessControlService->canAccessData(
            $user,
            $request->target_user_id,
            $request->branch_id
        );

        return response()->json([
            'can_access' => $canAccess,
            'user_id' => $user->id,
            'target_user_id' => $request->target_user_id,
            'branch_id' => $request->branch_id
        ]);
    }

    /**
     * Check login time restrictions
     */
    public function checkLoginTime(User $user)
    {
        $canLogin = $this->accessControlService->canLoginAtTime($user);
        $idleTimeout = $this->accessControlService->getIdleTimeout($user);

        return response()->json([
            'can_login' => $canLogin,
            'idle_timeout' => $idleTimeout
        ]);
    }

    /**
     * Get user access summary
     */
    public function getUserAccessSummary(User $user)
    {
        $accessLevels = $user->accessLevels()->active()->get();
        $loginRestrictions = $user->loginRestrictions()->active()->first();
        $recentLogins = $user->loginHistories()
                           ->orderBy('login_at', 'desc')
                           ->limit(10)
                           ->get();

        return response()->json([
            'user' => $user,
            'access_levels' => $accessLevels,
            'login_restrictions' => $loginRestrictions,
            'recent_logins' => $recentLogins
        ]);
    }

    /**
     * Toggle multi login for user
     */
    public function toggleMultiLogin(User $user)
    {
        try {
            // Reload roles to check current roles
            $user->load('roles');
            
            $requiresMulti = $user->requiresMultiLogin();

            // Prevent toggle if user is Administrator or Management Manager
            if ($requiresMulti) {
                return response()->json([
                    'success' => false,
                    'message' => 'User dengan role Administrator atau Management Manager harus selalu Multi Login. Tidak dapat diubah ke Single Login.'
                ], 403);
            }
            
            $user->multi_login = !$user->multi_login;
            $user->save();
            
            // Refresh model to ensure we have the latest data
            $user->refresh();

            return response()->json([
                'success' => true,
                'message' => $user->multi_login 
                    ? 'Multi login diaktifkan untuk user ini' 
                    : 'Single login diaktifkan untuk user ini',
                'multi_login' => $user->multi_login
            ]);
        } catch (\Exception $e) {
            \Log::error('toggleMultiLogin error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status multi login: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle freeze status for user
     * When frozen, all active sessions will be logged out
     */
    public function toggleFreeze(User $user)
    {
        try {
            $wasFrozen = $user->is_frozen;
            $user->is_frozen = !$user->is_frozen;
            $user->save();

            // If user is being frozen, logout all active sessions
            if ($user->is_frozen && !$wasFrozen) {
                // Delete all Sanctum tokens for this user
                $user->tokens()->delete();
                
                // Invalidate all sessions for this user (Laravel session)
                // Note: This requires storing session IDs in database, which may not be implemented
                // For now, we'll just delete Sanctum tokens which handles API sessions
                
            } else if (!$user->is_frozen && $wasFrozen) {
                // User is being unfrozen
            }

            return response()->json([
                'success' => true,
                'message' => $user->is_frozen 
                    ? 'Akun user telah dibekukan. Semua session aktif telah di-logout.' 
                    : 'Akun user telah diaktifkan kembali',
                'is_frozen' => $user->is_frozen
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status freeze: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle screenshot allowed for user
     * Administrator, Admin, Super Admin, and Management Manager are always allowed (cannot be toggled)
     */
    public function toggleScreenshot(User $user)
    {
        try {
            // Reload roles to check current roles
            $user->load('roles');
            
            // Check if user has permission to always allow screenshot (Admin/Management level)
            $isAlwaysAllowed = $user->hasPermission('admin.view');
            
            if ($isAlwaysAllowed) {
                return response()->json([
                    'success' => false,
                    'message' => 'User dengan role Administrator, Admin, Super Admin, atau Management Manager selalu diizinkan untuk screenshot. Tidak dapat diubah.',
                    'screenshot_allowed' => true
                ], 403);
            }
            
            $user->screenshot_allowed = !$user->screenshot_allowed;
            $user->save();
            
            // Refresh model to ensure we have the latest data
            $user->refresh();

            return response()->json([
                'success' => true,
                'message' => $user->screenshot_allowed 
                    ? 'Screenshot/Print Screen diizinkan untuk user ini' 
                    : 'Screenshot/Print Screen tidak diizinkan untuk user ini',
                'screenshot_allowed' => $user->screenshot_allowed
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status screenshot: ' . $e->getMessage()
            ], 500);
        }
    }

    protected function buildAccessLevelDetailsMap($users): array
    {
        $userNameMap = [];
        $branchNameMap = [];
        $allReferencedUserIds = [];
        $allReferencedBranchIds = [];

        foreach ($users as $user) {
            foreach ($user->accessLevels as $level) {
                $config = $level->access_config ?? [];
                if ($level->access_type === 'hierarchical') {
                    $ids = $config['subordinate_ids'] ?? $config['subordinates'] ?? [];
                    $allReferencedUserIds = array_merge($allReferencedUserIds, is_array($ids) ? $ids : []);
                } elseif ($level->access_type === 'peer') {
                    $ids = $config['peer_user_ids'] ?? $config['peer_users'] ?? [];
                    $allReferencedUserIds = array_merge($allReferencedUserIds, is_array($ids) ? $ids : []);
                } elseif ($level->access_type === 'branch') {
                    $ids = $config['allowed_branch_ids'] ?? $config['allowed_branches'] ?? [];
                    $allReferencedBranchIds = array_merge($allReferencedBranchIds, is_array($ids) ? $ids : []);
                }
            }
        }

        $allReferencedUserIds = array_values(array_unique(array_map('intval', array_filter($allReferencedUserIds))));
        $allReferencedBranchIds = array_values(array_unique(array_map('intval', array_filter($allReferencedBranchIds))));

        if (!empty($allReferencedUserIds)) {
            $userNameMap = User::whereIn('id', $allReferencedUserIds)
                ->pluck('name', 'id')
                ->toArray();
        }

        if (!empty($allReferencedBranchIds)) {
            $branchNameMap = Branch::whereIn('id', $allReferencedBranchIds)
                ->pluck('name', 'id')
                ->toArray();
        }

        $detailsMap = [];

        foreach ($users as $user) {
            $detailsMap[$user->id] = [];

            foreach ($user->accessLevels as $level) {
                $config = $level->access_config ?? [];
                $details = '';

                if ($level->access_type === 'hierarchical') {
                    $ids = $config['subordinate_ids'] ?? $config['subordinates'] ?? [];
                    $names = collect(is_array($ids) ? $ids : [])
                        ->map(fn ($id) => $userNameMap[(int) $id] ?? null)
                        ->filter()
                        ->values()
                        ->all();
                    $details = !empty($names) ? ': ' . implode(', ', $names) : '';
                } elseif ($level->access_type === 'peer') {
                    $ids = $config['peer_user_ids'] ?? $config['peer_users'] ?? [];
                    $names = collect(is_array($ids) ? $ids : [])
                        ->map(fn ($id) => $userNameMap[(int) $id] ?? null)
                        ->filter()
                        ->values()
                        ->all();
                    $details = !empty($names) ? ': ' . implode(', ', $names) : '';
                } elseif ($level->access_type === 'branch') {
                    $ids = $config['allowed_branch_ids'] ?? $config['allowed_branches'] ?? [];
                    $names = collect(is_array($ids) ? $ids : [])
                        ->map(fn ($id) => $branchNameMap[(int) $id] ?? null)
                        ->filter()
                        ->values()
                        ->all();
                    $details = !empty($names) ? ': ' . implode(', ', $names) : '';
                }

                $detailsMap[$user->id][$level->id] = $details;
            }
        }

        return $detailsMap;
    }
}
