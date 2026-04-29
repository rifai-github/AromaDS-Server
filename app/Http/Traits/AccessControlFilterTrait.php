<?php

namespace App\Http\Traits;

use App\Models\User;
use App\Services\AccessControlService;
use Illuminate\Support\Facades\Auth;

trait AccessControlFilterTrait
{
    /**
     * Apply access control filter to query
     * Default: Jika tidak set hirarki, hanya bisa lihat data sendiri
     */
    protected function applyAccessControlFilter($query, $user = null, $createdByField = 'created_by', $marketingField = 'marketing_id', $branchField = 'branch_id', $extraAccessLogic = null, $warehouseField = 'warehouse_id')
    {
        if (!$user) {
            $user = Auth::user();
        }

        // Check if user has Management role (can see all data)
        if ($user->hasRoleStartingWith('Management')) {
            return $query; // No filter, can see all
        }

        // Check if user has explicit 'company' access level
        $companyAccess = $user->accessLevels()
                             ->where('access_type', 'company')
                             ->where('is_active', true)
                             ->exists();
        
        if ($companyAccess) {
            return $query;
        }

        // Check default restriction (None)
        $noneAccess = $user->accessLevels()
            ->where('access_type', 'none')
            ->where('is_active', true)
            ->first();

        // Check Branch Access
        $branchIds = [];
        $branchAccess = $user->accessLevels()
            ->where('access_type', 'branch')
            ->where('is_active', true)
            ->first();
            
        if ($branchAccess) {
            $config = $branchAccess->access_config ?? [];
            $branchIds = $config['allowed_branches'] ?? [];
            if (empty($branchIds) && $user->branch_id) {
                $branchIds = [$user->branch_id];
            }
        } else {
             // Logic baru: Jika tidak ada Access Level Branch, kita tidak otomatis set branchIds
             // Kecuali jika nanti dibutuhkan fallback. Saat ini ikut logic existing.
        }
        
        // Get all user IDs that this user can access (Hierarchy + Peer)
        // If None access, this returns only own ID.
        $accessibleUserIds = $this->getAccessibleUserIds($user);

        // Warehouse Management & Admin Logic
        // If user is a manager or admin of any warehouse, they should see all data in that warehouse
        $managedWarehouseIds = \App\Models\Warehouse::where(function($q) use ($user) {
                $q->where('manager', $user->id)
                  ->orWhereHas('admins', function($sq) use ($user) {
                      $sq->where('user_id', $user->id);
                  });
            })
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        // Apply Logic Group
        // We wrap Standard Logic and Extra Logic in a container Closure to ensure they are OR-ed correctly within the AND constraint of the main query
        $query->where(function($containerQ) use ($accessibleUserIds, $createdByField, $marketingField, $branchIds, $branchField, $extraAccessLogic, $noneAccess, $managedWarehouseIds, $warehouseField) {
            
            // 1. Standard Logic (Hierarchy / Branch / Peer / Own)
            $containerQ->where(function($q) use ($accessibleUserIds, $createdByField, $marketingField, $branchIds, $branchField, $noneAccess, $managedWarehouseIds, $warehouseField) {
                
                 if ($noneAccess) {
                     // None Access Strict: Only Created By (or Marketing Field)
                     $q->where($createdByField, $accessibleUserIds[0]); // accessibleUserIds only has own ID
                     if ($marketingField && $marketingField !== $createdByField) {
                        $q->orWhere($marketingField, $accessibleUserIds[0]);
                     }
                 } else {
                     // Normal Logic (Hierarchy, Peer, Branch)
                     $q->whereIn($createdByField, $accessibleUserIds);
                    
                     // If marketing field exists and is different from created_by, also filter by it
                     if ($marketingField && $marketingField !== $createdByField) {
                        $q->orWhereIn($marketingField, $accessibleUserIds);
                     }

                     // Branch Hierarchy Logic
                     if (!empty($branchIds) && $branchField) {
                         if (str_contains($branchField, '.')) {
                             // Handle relationship-based branch check (e.g. 'warehouse.branch_id' or 'assignedTo.branch_id')
                             [$relation, $column] = explode('.', $branchField, 2);
                             $q->orWhereHas($relation, function($sq) use ($column, $branchIds) {
                                 $sq->whereIn($column, $branchIds);
                             });
                         } else {
                             // Handle direct column check
                             $table = $q->getModel()->getTable();
                             $q->orWhereIn($table . '.' . $branchField, $branchIds);
                         }
                     }
                 }

                 // 2. Warehouse Manager Logic (Implicitly OR-ed)
                 if (!empty($managedWarehouseIds) && $warehouseField) {
                    if (str_contains($warehouseField, '.')) {
                        [$relation, $column] = explode('.', $warehouseField, 2);
                        $q->orWhereHas($relation, function($sq) use ($column, $managedWarehouseIds) {
                            $sq->whereIn($column, $managedWarehouseIds);
                        });
                    } else {
                        $table = $q->getModel()->getTable();
                        $q->orWhereIn($table . '.' . $warehouseField, $managedWarehouseIds);
                    }
                 }
            });

            // 2. Extra Custom Logic (e.g. Team Access)
            // Executed as an OR condition to the Standard Logic
            if ($extraAccessLogic) {
                $extraAccessLogic($containerQ);
            }
        });
        
        return $query;
    }
    
    /**
     * Get all user IDs that the current user can access
     * Default: Hanya user sendiri jika tidak set hirarki
     */
    protected function getAccessibleUserIds(User $user)
    {
        $accessibleIds = [$user->id]; // Default: hanya user sendiri
        
        // Check if user has "none" access level (explicitly set to only own data)
        $noneAccess = $user->accessLevels()
            ->where('access_type', 'none')
            ->where('is_active', true)
            ->first();
        
        if ($noneAccess) {
            // User explicitly set to "none" - hanya data sendiri
            return [$user->id];
        }
        
        // Check if user has hierarchical access level set
        $hierarchicalAccess = $user->accessLevels()
            ->where('access_type', 'hierarchical')
            ->where('is_active', true)
            ->first();
        
        if ($hierarchicalAccess) {
            // Get all subordinate IDs recursively
            $accessControlService = app(AccessControlService::class);
            $subordinateIds = $accessControlService->getAllSubordinateIds($user);
            $accessibleIds = array_merge($accessibleIds, $subordinateIds);
        }
        
        // Check peer access
        $peerAccess = $user->accessLevels()
            ->where('access_type', 'peer')
            ->where('is_active', true)
            ->first();
        
        if ($peerAccess) {
            $config = $peerAccess->access_config ?? [];
            $peerUsers = $config['peer_users'] ?? [];
            $accessibleIds = array_merge($accessibleIds, $peerUsers);
        }
        
        // Remove duplicates
        $accessibleIds = array_unique($accessibleIds);
        
        return $accessibleIds;
    }
}

