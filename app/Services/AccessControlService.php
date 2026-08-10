<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserAccessLevel;
use App\Models\UserLoginRestriction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AccessControlService
{
    /**
     * Check if user has hierarchical access (supports nested hierarchy)
     * User D → User A → User B, C
     * User D can see User A, B, C (all subordinates recursively)
     */
    public function hasHierarchicalAccess(User $user, $targetUserId, $visited = [])
    {
        // Prevent infinite loops
        if (in_array($user->id, $visited)) {
            return false;
        }
        
        $visited[] = $user->id;
        
        $accessLevel = $user->accessLevels()
                           ->where('access_type', 'hierarchical')
                           ->where('is_active', true)
                           ->first();

        if (!$accessLevel) {
            return false;
        }

        $config = $accessLevel->access_config ?? [];
        
        // Check if target user is direct subordinate
        if ($this->isSubordinate($user, $targetUserId, $config)) {
            return true;
        }
        
        // Check if target user is subordinate of subordinate (nested hierarchy)
        $directSubordinates = $config['subordinates'] ?? [];
        foreach ($directSubordinates as $subordinateId) {
            $subordinate = User::find($subordinateId);
            if ($subordinate && $this->hasHierarchicalAccess($subordinate, $targetUserId, $visited)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if user has peer access
     */
    public function hasPeerAccess(User $user, $targetUserId)
    {
        $accessLevel = $user->accessLevels()
                           ->where('access_type', 'peer')
                           ->where('is_active', true)
                           ->first();

        if (!$accessLevel) {
            return false;
        }

        $config = $accessLevel->access_config ?? [];
        $peerUsers = $config['peer_users'] ?? [];
        
        return in_array($targetUserId, $peerUsers);
    }

    /**
     * Check if user has branch access
     */
    public function hasBranchAccess(User $user, $branchId)
    {
        $accessLevel = $user->accessLevels()
                           ->where('access_type', 'branch')
                           ->where('is_active', true)
                           ->first();

        if (!$accessLevel) {
            return false;
        }

        $config = $accessLevel->access_config ?? [];
        $allowedBranches = $config['allowed_branches'] ?? [];
        
        return in_array($branchId, $allowedBranches);
    }

    /**
     * Check if user can access data
     */
    public function canAccessData(User $user, $dataOwnerId, $branchId = null)
    {
        // User can always access their own data
        if ($user->id === $dataOwnerId) {
            return true;
        }

        // Check if user has permission to view all data (Admin/Management level)
        if ($user->hasPermission('admin.view')) {
            return true;
        }

        // Check if user has explicit 'company' access level
        $companyAccess = $user->accessLevels()
                             ->where('access_type', 'company')
                             ->where('is_active', true)
                             ->exists();
        
        if ($companyAccess) {
            return true;
        }

        // Check hierarchical access
        if ($this->hasHierarchicalAccess($user, $dataOwnerId)) {
            return true;
        }

        // Check peer access
        if ($this->hasPeerAccess($user, $dataOwnerId)) {
            return true;
        }

        // Check branch access
        if ($branchId && $this->hasBranchAccess($user, $branchId)) {
            return true;
        }

        return false;
    }

    /**
     * Check login time restrictions
     */
    public function canLoginAtTime(User $user)
    {
        $restriction = $user->loginRestrictions()
                           ->where('is_active', true)
                           ->first();

        if (!$restriction) {
            return true; // No restrictions
        }

        $now = now();
        $currentTime = $now->format('H:i:s');
        $currentDay = $now->dayOfWeek;

        // Check time restrictions
        if ($restriction->start_time && $restriction->end_time) {
            if ($currentTime < $restriction->start_time || $currentTime > $restriction->end_time) {
                return false;
            }
        }

        // Check day restrictions
        if ($restriction->allowed_days) {
            // Model cast already handles JSON decoding, allowed_days is already an array
            $allowedDays = is_array($restriction->allowed_days)
                ? $restriction->allowed_days
                : json_decode($restriction->allowed_days, true);

            $dayNameToInt = [
                'sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3,
                'thursday' => 4, 'friday' => 5, 'saturday' => 6,
            ];

            // allowed_days is stored as lowercase day-name strings (e.g. "saturday"),
            // but tolerate legacy numeric values too.
            $allowedDaysInt = array_map(function ($day) use ($dayNameToInt) {
                if (is_numeric($day)) {
                    return (int) $day;
                }
                return $dayNameToInt[strtolower((string) $day)] ?? -1;
            }, $allowedDays ?? []);

            if (!in_array($currentDay, $allowedDaysInt)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get idle timeout for user
     */
    public function getIdleTimeout(User $user)
    {
        $restriction = $user->loginRestrictions()
                           ->where('is_active', true)
                           ->first();

        return $restriction ? $restriction->idle_timeout : 30; // Default 30 minutes
    }

    /**
     * Check if user is direct subordinate (not recursive)
     */
    protected function isSubordinate(User $supervisor, $subordinateId, $config)
    {
        $subordinates = $config['subordinates'] ?? [];
        return in_array($subordinateId, $subordinates);
    }
    
    /**
     * Get all subordinate IDs recursively (including subordinates of subordinates)
     * Example: User D → User A → User B, C
     * Returns: [A, B, C] for User D
     */
    public function getAllSubordinateIds(User $user, $visited = [])
    {
        // Prevent infinite loops
        if (in_array($user->id, $visited)) {
            return [];
        }
        
        $visited[] = $user->id;
        $allSubordinates = [];
        
        $accessLevel = $user->accessLevels()
                           ->where('access_type', 'hierarchical')
                           ->where('is_active', true)
                           ->first();
        
        if (!$accessLevel) {
            return [];
        }
        
        $config = $accessLevel->access_config ?? [];
        $directSubordinates = $config['subordinates'] ?? [];
        
        foreach ($directSubordinates as $subordinateId) {
            // Add direct subordinate
            if (!in_array($subordinateId, $allSubordinates)) {
                $allSubordinates[] = $subordinateId;
            }
            
            // Get subordinates of this subordinate (recursive)
            $subordinate = User::find($subordinateId);
            if ($subordinate) {
                $nestedSubordinates = $this->getAllSubordinateIds($subordinate, $visited);
                foreach ($nestedSubordinates as $nestedId) {
                    if (!in_array($nestedId, $allSubordinates)) {
                        $allSubordinates[] = $nestedId;
                    }
                }
            }
        }
        
        return $allSubordinates;
    }

    /**
     * Set user access level
     */
    public function setUserAccessLevel(User $user, $accessType, $config)
    {
        UserAccessLevel::updateOrCreate(
            [
                'user_id' => $user->id,
                'access_type' => $accessType
            ],
            [
                'access_config' => $config,
                'is_active' => true
            ]
        );
    }

    /**
     * Set user login restrictions
     */
    public function setUserLoginRestrictions(User $user, $startTime = null, $endTime = null, $allowedDays = null, $idleTimeout = 30)
    {
        UserLoginRestriction::updateOrCreate(
            ['user_id' => $user->id],
            [
                'start_time' => $startTime,
                'end_time' => $endTime,
                'allowed_days' => $allowedDays, // Model cast handles JSON serialization
                'idle_timeout' => $idleTimeout,
                'is_active' => true
            ]
        );
    }
}
