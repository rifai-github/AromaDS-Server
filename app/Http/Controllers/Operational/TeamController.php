<?php

namespace App\Http\Controllers\Operational;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\Branch;
use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

use App\Http\Traits\ColumnFilterTrait;

class TeamController extends Controller
{
    use ColumnFilterTrait;
    public function index(Request $request)
    {
        $query = Team::with(['branch:id,name', 'createdBy:id,name', 'updatedBy:id,name', 'teamHead:id,name'])
                     ->withCount('users');

        // Apply per-column filters
        try {
            // Capture flat structure filters
            $customFilters = [];
            if ($request->has('team_name')) $customFilters['team_name'] = $request->team_name;
            if ($request->has('team_code')) $customFilters['team_code'] = $request->team_code;
            if ($request->has('description')) $customFilters['description'] = $request->description;
            if ($request->has('active_status')) $customFilters['active_status'] = $request->active_status;
            if ($request->has('created_at')) $customFilters['created_at'] = $request->created_at;

            // Skip AutoFilter for manually handled columns to avoid conflicts
            if (!empty($customFilters)) {
                $request->merge([
                    '_skip_auto_filter' => array_merge(
                        $request->input('_skip_auto_filter', []),
                        array_fill_keys(array_keys($customFilters), true)
                    )
                ]);
            }

            $this->applyColumnFilters($query, 'teamsTable', [
                // 0 => checkbox
                1 => ['column' => 'team_name'],
                'team_name' => ['column' => 'team_name'],
                
                2 => ['column' => 'team_code'],
                'team_code' => ['column' => 'team_code'],
                
                3 => ['column' => 'description'],
                'description' => ['column' => 'description'],
                
                4 => ['relation' => 'branch', 'column' => 'name'],
                'branch.name' => ['relation' => 'branch', 'column' => 'name'],
                
                5 => ['relation' => 'teamHead', 'column' => 'name'],
                'teamHead.name' => ['relation' => 'teamHead', 'column' => 'name'],
                
                6 => ['column' => 'active_status', 'boolean' => true],
                'active_status' => ['column' => 'active_status', 'boolean' => true],
                
                // 7 => Member Count (no filter)
                
                8 => ['column' => 'created_at', 'type' => 'date'],
                'created_at' => ['column' => 'created_at', 'type' => 'date'],
            ], $customFilters);
        } catch (\Exception $e) {
            \Log::error('Error applying column filters in TeamController', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        $teams = $query->orderBy('created_at', 'desc')->paginate(25);
        $branches = Cache::remember('team:index:branches', 300, function () {
            return Branch::select('id', 'name')->orderBy('name')->get();
        });
        $users = Cache::remember('team:index:users', 300, function () {
            return User::select('id', 'name', 'email')->orderBy('name')->get();
        });

        // Check if this is an API request
        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'data' => $teams,
                'meta' => [
                    'branches' => $branches
                ]
            ]);
        }

        return view('operational.teams.index', compact('teams', 'branches', 'users'));
    }

    public function create()
    {
        $branches = Branch::all();
        $users = User::all();
        $departments = Department::all();

        return view('operational.teams.create', compact('branches', 'users', 'departments'));
    }

    public function store(Request $request)
    {
        // Debug: Log request data
        \Log::info('Team Store Request', [
            'all_data' => $request->all(),
            'members' => $request->members,
            'user_ids' => $request->user_ids
        ]);
        
        // Normalize active_status to boolean/integer
        $activeStatus = $request->active_status;
        if (is_string($activeStatus)) {
            $activeStatus = in_array(strtolower($activeStatus), ['1', 'true', 'yes', 'on']) ? 1 : 0;
        } elseif (is_bool($activeStatus)) {
            $activeStatus = $activeStatus ? 1 : 0;
        } else {
            $activeStatus = (int)$activeStatus;
        }
        
        $validator = Validator::make($request->all(), [
            'team_name' => 'required|string|max:255|unique:teams',
            'branch_office' => 'required|exists:branches,id',
            'description' => 'nullable|string|max:1000',
            'active_status' => 'nullable', // We'll handle validation manually
            'team_head_id' => 'nullable|exists:users,id',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
            'members' => 'nullable|array',
            'members.*' => 'exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $team = null;
            $maxRetries = 3;
            $retryCount = 0;
            $lastException = null;
            
            while ($retryCount < $maxRetries) {
                try {
                    $team = Team::create([
                        'team_name' => $request->team_name,
                        'branch_office' => $request->branch_office,
                        'team_code' => Team::generateTeamCode(),
                        'description' => $request->description,
                        'active_status' => $activeStatus,
                        'team_head_id' => $request->team_head_id,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id()
                    ]);
                    break; // Success!
                } catch (\Illuminate\Database\QueryException $e) {
                    $lastException = $e;
                    // Check if error is duplicate entry for team_code (SQLSTATE 23000, Error 1062)
                    if ($e->getCode() == 23000 && str_contains($e->getMessage(), 'teams_team_code_unique')) {
                        $retryCount++;
                        continue;
                    }
                    throw $e; // Rethrow other database errors
                }
            }
            
            if (!$team) {
                throw $lastException;
            }

            // Attach users to team (support both 'user_ids' and 'members')
            $memberIds = $request->user_ids ?? $request->members ?? $request->input('members[]') ?? [];
            
            // Convert to array if it's a single value
            if (!is_array($memberIds)) {
                $memberIds = [$memberIds];
            }
            
            if (!empty($memberIds)) {
                $team->users()->attach($memberIds);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Team created successfully',
                'data' => $team
            ]);
        } catch (\Exception $e) {
            \Log::error('Error creating team', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Error creating team: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Team $team)
    {
        $team->load(['branch', 'createdBy', 'users', 'teamHead']);
        
        // Check if this is an API request, AJAX request, or fetch request
        if (request()->expectsJson() || request()->is('api/*') || request()->ajax() || request()->header('Accept') === 'application/json') {
            return response()->json([
                'status' => 'success',
                'data' => $team
            ]);
        }
        
        return view('operational.teams.show', compact('team'));
    }

    public function edit(Team $team)
    {
        $team->load(['branch', 'createdBy', 'users', 'teamHead']);
        
        // Check if this is an API request, AJAX request, or fetch request
        if (request()->expectsJson() || request()->is('api/*') || request()->ajax() || request()->header('Accept') === 'application/json') {
            $teamData = $team->toArray();
            $teamData['members'] = $team->users; // Add members for modal
            
            return response()->json([
                'status' => 'success',
                'data' => $teamData
            ]);
        }
        
        $branches = Branch::all();
        $users = User::all();
        $departments = Department::all();

        return view('operational.teams.edit', compact('team', 'branches', 'users', 'departments'));
    }

    public function update(Request $request, Team $team)
    {
        // Debug: Log request data
        \Log::info('Team Update Request', [
            'team_id' => $team->id,
            'all_data' => $request->all(),
            'members' => $request->members,
            'user_ids' => $request->user_ids
        ]);
        
        // Normalize active_status to boolean/integer
        $activeStatus = $request->active_status;
        if (is_string($activeStatus)) {
            $activeStatus = in_array(strtolower($activeStatus), ['1', 'true', 'yes', 'on']) ? 1 : 0;
        } elseif (is_bool($activeStatus)) {
            $activeStatus = $activeStatus ? 1 : 0;
        } else {
            $activeStatus = (int)$activeStatus;
        }
        
        $validator = Validator::make($request->all(), [
            'team_name' => 'required|string|max:255|unique:teams,team_name,' . $team->id,
            'branch_office' => 'required|exists:branches,id',
            'description' => 'nullable|string|max:1000',
            'active_status' => 'nullable', // We'll handle validation manually
            'team_head_id' => 'nullable|exists:users,id',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
            'members' => 'nullable|array',
            'members.*' => 'exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $team->update([
                'team_name' => $request->team_name,
                'branch_office' => $request->branch_office,
                'description' => $request->description,
                'active_status' => $activeStatus,
                'team_head_id' => $request->team_head_id,
                'updated_by' => Auth::id()
            ]);

            // Sync users to team (support both 'user_ids' and 'members')
            $memberIds = $request->user_ids ?? $request->members ?? $request->input('members[]') ?? [];
            
            // Convert to array if it's a single value
            if (!is_array($memberIds)) {
                $memberIds = [$memberIds];
            }
            
            if (!empty($memberIds)) {
                $team->users()->sync($memberIds);
            } else {
                $team->users()->detach();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Team updated successfully',
                'data' => $team
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating team: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Team $team)
    {
        try {
            // Detach all users first
            $team->users()->detach();
            $team->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Team deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error deleting team: ' . $e->getMessage()
            ], 500);
        }
    }

    public function dashboard()
    {
        $total_teams = Team::count();
        $active_teams = Team::where('active_status', true)->count();
        $inactive_teams = Team::where('active_status', false)->count();

        $teams_by_branch = Team::with('branch')
            ->selectRaw('branch_office, count(*) as count')
            ->groupBy('branch_office')
            ->get();

        $team_members_count = Team::withCount('users')->get();

        $recent_teams = Team::with(['branch', 'createdBy'])
            ->withCount('users')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('operational.teams.dashboard', compact(
            'total_teams',
            'active_teams',
            'inactive_teams',
            'teams_by_branch',
            'team_members_count',
            'recent_teams'
        ));
    }

    public function addMember(Request $request, Team $team)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $team->users()->attach($request->user_id);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Member added to team successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error adding member to team: ' . $e->getMessage()
            ], 500);
        }
    }

    public function removeMember(Request $request, Team $team)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $team->users()->detach($request->user_id);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Member removed from team successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error removing member from team: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:teams,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $teams = Team::whereIn('id', $request->ids)->get();
            
            foreach ($teams as $team) {
                // Detach all users first
                $team->users()->detach();
                $team->delete();
            }
            
            return response()->json([
                'status' => 'success',
                'success' => true,
                'count' => count($request->ids),
                'message' => count($request->ids) . ' team(s) deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error deleting teams: ' . $e->getMessage()
            ], 500);
        }
    }
}
