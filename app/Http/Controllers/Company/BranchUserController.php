<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BranchUserController extends Controller
{
    /**
     * Display the multi-branch user assignment page
     */
    public function index()
    {
        $users = User::whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'branch_id']);
        
        $branches = Branch::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);
        
        return view('company.branches.user-branches', compact('users', 'branches'));
    }

    /**
     * Get branches assigned to a specific user
     */
    public function getUserBranches(User $user)
    {
        $assignedBranchIds = DB::table('branch_user')
            ->where('user_id', $user->id)
            ->pluck('branch_id')
            ->toArray();
        
        $primaryBranchId = DB::table('branch_user')
            ->where('user_id', $user->id)
            ->where('is_primary', true)
            ->value('branch_id');
        
        return response()->json([
            'status' => 'success',
            'assigned_branch_ids' => $assignedBranchIds,
            'primary_branch_id' => $primaryBranchId,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'original_branch_id' => $user->branch_id
            ]
        ]);
    }

    /**
     * Update user's branch assignments
     */
    public function updateUserBranches(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'branch_ids' => 'array',
            'branch_ids.*' => 'exists:branches,id',
            'primary_branch_id' => 'nullable|exists:branches,id'
        ]);

        try {
            DB::beginTransaction();

            $userId = $request->user_id;
            $branchIds = $request->branch_ids ?? [];
            $primaryBranchId = $request->primary_branch_id;

            // If primary branch is set but not in branch_ids, add it
            if ($primaryBranchId && !in_array($primaryBranchId, $branchIds)) {
                $branchIds[] = $primaryBranchId;
            }

            // Delete existing assignments
            DB::table('branch_user')->where('user_id', $userId)->delete();

            // Insert new assignments
            $now = now();
            $authId = Auth::id();
            
            foreach ($branchIds as $branchId) {
                DB::table('branch_user')->insert([
                    'branch_id' => $branchId,
                    'user_id' => $userId,
                    'is_primary' => ($branchId == $primaryBranchId),
                    'created_by' => $authId,
                    'updated_by' => $authId,
                    'created_at' => $now,
                    'updated_at' => $now
                ]);
            }

            // Update user's original branch_id to primary branch (for backward compatibility)
            if ($primaryBranchId) {
                User::where('id', $userId)->update([
                    'branch_id' => $primaryBranchId,
                    'updated_by' => $authId
                ]);
            }

            DB::commit();

            \Log::info("🏢 Multi-Branch: Updated user {$userId} branch assignments", [
                'branch_ids' => $branchIds,
                'primary_branch_id' => $primaryBranchId
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Branch assignments updated successfully',
                'assigned_count' => count($branchIds)
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error("❌ Multi-Branch: Failed to update user branches: " . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update branch assignments: ' . $e->getMessage()
            ], 500);
        }
    }
}
