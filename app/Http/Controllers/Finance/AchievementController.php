<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Finance\Achievement;
use App\Models\Finance\AchievementPeriod;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AchievementController extends Controller
{
    /**
     * Display a listing of achievements
     */
    public function index(Request $request)
    {
        $query = Achievement::with(['user', 'achievementPeriod', 'contract', 'createdBy', 'updatedBy'])
            ->orderBy('achievement_date', 'desc');

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by period
        if ($request->filled('period_id')) {
            $query->where('achievement_period_id', $request->period_id);
        }

        // Filter by type
        if ($request->filled('achievement_type')) {
            $query->where('achievement_type', $request->achievement_type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('achievement_date', [$request->start_date, $request->end_date]);
        }

        $achievements = $query->paginate(20);
        $users = User::where('is_active', true)->get();
        $periods = AchievementPeriod::orderBy('start_date', 'desc')->get();

        return view('finance.achievements.index', compact('achievements', 'users', 'periods'));
    }

    /**
     * Show the form for creating a new achievement
     */
    public function create()
    {
        $users = User::where('is_active', true)->get();
        $periods = AchievementPeriod::active()->get();
        $contracts = \App\Models\Contract::where('status', 'active')->get();

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'users' => $users,
                'periods' => $periods,
                'contracts' => $contracts
            ]);
        }

        return view('finance.achievements.create', compact('users', 'periods', 'contracts'));
    }

    /**
     * Store a newly created achievement
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'achievement_period_id' => 'required|exists:achievement_periods,id',
            'contract_id' => 'nullable|exists:contracts,id',
            'achievement_type' => 'required|in:sales,service,installation',
            'target_amount' => 'required|numeric|min:0',
            'achieved_amount' => 'required|numeric|min:0',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'achievement_date' => 'required|date',
            'notes' => 'nullable|string|max:1000'
        ]);

        try {
            DB::beginTransaction();

            $achievement = Achievement::create([
                'user_id' => $request->user_id,
                'achievement_period_id' => $request->achievement_period_id,
                'contract_id' => $request->contract_id,
                'achievement_type' => $request->achievement_type,
                'target_amount' => $request->target_amount,
                'achieved_amount' => $request->achieved_amount,
                'commission_rate' => $request->commission_rate,
                'commission_amount' => 0, // Will be calculated
                'status' => 'pending',
                'achievement_date' => $request->achievement_date,
                'notes' => $request->notes,
                'created_by' => Auth::id()
            ]);

            // Calculate commission and update status
            $achievement->calculateCommission();
            $achievement->updateStatus();
            $achievement->save();

            DB::commit();

            return redirect()->route('achievements.index')
                ->with('success', 'Achievement created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to create achievement: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified achievement
     */
    public function show(Achievement $achievement)
    {
        $achievement->load(['user', 'achievementPeriod', 'contract', 'createdBy']);
        
        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'achievement' => $achievement
            ]);
        }
        
        return view('finance.achievements.show', compact('achievement'));
    }

    /**
     * Show the form for editing the specified achievement
     */
    public function edit(Achievement $achievement)
    {
        $users = User::where('is_active', true)->get();
        $periods = AchievementPeriod::active()->get();
        $contracts = \App\Models\Contract::where('status', 'active')->get();

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'achievement' => $achievement,
                'users' => $users,
                'periods' => $periods,
                'contracts' => $contracts
            ]);
        }

        return view('finance.achievements.edit', compact('achievement', 'users', 'periods', 'contracts'));
    }

    /**
     * Update the specified achievement
     */
    public function update(Request $request, Achievement $achievement)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'achievement_period_id' => 'required|exists:achievement_periods,id',
            'contract_id' => 'nullable|exists:contracts,id',
            'achievement_type' => 'required|in:sales,service,installation',
            'target_amount' => 'required|numeric|min:0',
            'achieved_amount' => 'required|numeric|min:0',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'achievement_date' => 'required|date',
            'notes' => 'nullable|string|max:1000'
        ]);

        try {
            DB::beginTransaction();

            $achievement->update([
                'user_id' => $request->user_id,
                'achievement_period_id' => $request->achievement_period_id,
                'contract_id' => $request->contract_id,
                'achievement_type' => $request->achievement_type,
                'target_amount' => $request->target_amount,
                'achieved_amount' => $request->achieved_amount,
                'commission_rate' => $request->commission_rate,
                'achievement_date' => $request->achievement_date,
                'notes' => $request->notes,
                'updated_by' => Auth::id()
            ]);

            // Recalculate commission and update status
            $achievement->calculateCommission();
            $achievement->updateStatus();
            $achievement->save();

            DB::commit();

            return redirect()->route('achievements.index')
                ->with('success', 'Achievement updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to update achievement: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified achievement
     */
    public function destroy(Achievement $achievement)
    {
        try {
            $achievement->delete();
            return redirect()->route('achievements.index')
                ->with('success', 'Achievement deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete achievement: ' . $e->getMessage());
        }
    }

    /**
     * Get achievement statistics
     */
    public function statistics(Request $request)
    {
        try {
            $periodId = $request->get('period_id');
            $userId = $request->get('user_id');

            $query = Achievement::query();

            if ($periodId) {
                $query->where('achievement_period_id', $periodId);
            }

            if ($userId) {
                $query->where('user_id', $userId);
            }

            $stats = [
                'total_achievements' => $query->count(),
                'achieved_count' => $query->where('status', 'achieved')->count(),
                'exceeded_count' => $query->where('status', 'exceeded')->count(),
                'failed_count' => $query->where('status', 'failed')->count(),
                'pending_count' => $query->where('status', 'pending')->count(),
                'total_amount' => $query->sum('achieved_amount') ?: 0,
                'total_commissions' => $query->sum('commission_amount') ?: 0
            ];

            return response()->json($stats, 200, [
                'Content-Type' => 'application/json'
            ]);
        } catch (\Exception $e) {
            \Log::error('Achievement statistics error: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to load statistics'], 500);
        }
    }

    /**
     * Get user performance report
     */
    public function performanceReport(Request $request)
    {
        $userId = $request->get('user_id');
        $periodId = $request->get('period_id');

        $query = Achievement::with(['user', 'achievementPeriod', 'contract'])
            ->where('user_id', $userId);

        if ($periodId) {
            $query->where('achievement_period_id', $periodId);
        }

        $achievements = $query->orderBy('achievement_date', 'desc')->get();

        $performance = [
            'user' => User::find($userId),
            'achievements' => $achievements,
            'total_target' => $achievements->sum('target_amount'),
            'total_achieved' => $achievements->sum('achieved_amount'),
            'total_commission' => $achievements->sum('commission_amount'),
            'achievement_rate' => $achievements->sum('target_amount') > 0 
                ? ($achievements->sum('achieved_amount') / $achievements->sum('target_amount')) * 100 
                : 0
        ];

        return response()->json($performance);
    }
}
