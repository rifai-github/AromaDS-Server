<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Finance\MarketingTarget;
use App\Models\Finance\AchievementPeriod;
use App\Services\Finance\MarketingTargetService;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class MarketingTargetController extends Controller
{
    protected $marketingTargetService;

    public function __construct(MarketingTargetService $marketingTargetService)
    {
        $this->marketingTargetService = $marketingTargetService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $marketingTargets = MarketingTarget::with(['user', 'achievementPeriod', 'createdBy', 'updatedBy'])
            ->orderBy('created_at', 'desc')
            ->paginateStd(25);

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'data' => $marketingTargets
            ]);
        }

        return view('finance.marketing-targets.index', compact('marketingTargets'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $users = User::whereHas('roles', function($q) {
            $q->where('name', 'like', 'Marketing%');
        })->get();
        
        $periods = AchievementPeriod::active()->orderBy('start_date', 'desc')->get();

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'users' => $users,
                'periods' => $periods
            ]);
        }

        return view('finance.marketing-targets.create', compact('users', 'periods'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'achievement_period_id' => 'required|exists:achievement_periods,id',
            'target_type' => 'required|in:new,renewal',
            'target_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000'
        ]);

        $result = $this->marketingTargetService->createOrUpdateTarget($request->all());

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json($result, $result['success'] ? 201 : 500);
        }

        if ($result['success']) {
            return redirect()->route('marketing-targets.index')
                ->with('success', 'Marketing target created successfully.');
        }

        return redirect()->back()
            ->with('error', $result['message'])
            ->withInput();
    }

    /**
     * Display the specified resource.
     */
    public function show(MarketingTarget $marketingTarget)
    {
        $marketingTarget->load(['user', 'achievementPeriod', 'createdBy', 'updatedBy', 'commissionCalculations']);

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'data' => $marketingTarget
            ]);
        }

        return view('finance.marketing-targets.show', compact('marketingTarget'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MarketingTarget $marketingTarget)
    {
        $marketingTarget->load(['user', 'achievementPeriod']);
        
        $users = User::whereHas('roles', function($q) {
            $q->where('name', 'like', 'Marketing%');
        })->get();
        
        $periods = AchievementPeriod::active()->orderBy('start_date', 'desc')->get();

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'data' => $marketingTarget,
                'users' => $users,
                'periods' => $periods
            ]);
        }

        return view('finance.marketing-targets.edit', compact('marketingTarget', 'users', 'periods'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MarketingTarget $marketingTarget)
    {
        $request->validate([
            'target_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000'
        ]);

        // Check if target is locked
        if ($marketingTarget->is_locked) {
            $error = 'Cannot update locked target. Please unlock it first.';
            
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => $error
                ], 403);
            }

            return redirect()->back()->with('error', $error);
        }

        try {
            $marketingTarget->update([
                'target_amount' => $request->target_amount,
                'notes' => $request->notes,
                'updated_by' => Auth::id()
            ]);

            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Marketing target updated successfully',
                    'data' => $marketingTarget
                ]);
            }

            return redirect()->route('marketing-targets.index')
                ->with('success', 'Marketing target updated successfully.');
        } catch (\Exception $e) {
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update marketing target: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to update marketing target: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MarketingTarget $marketingTarget)
    {
        try {
            // Check if target is locked
            if ($marketingTarget->is_locked) {
                throw new \Exception('Cannot delete locked target. Please unlock it first.');
            }

            $marketingTarget->delete();

            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Marketing target deleted successfully'
                ]);
            }

            return redirect()->route('marketing-targets.index')
                ->with('success', 'Marketing target deleted successfully.');
        } catch (\Exception $e) {
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to delete marketing target: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to delete marketing target: ' . $e->getMessage());
        }
    }

    /**
     * Lock target
     */
    public function lock(MarketingTarget $marketingTarget)
    {
        $result = $this->marketingTargetService->lockTarget($marketingTarget->id, Auth::id());

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json($result, $result['success'] ? 200 : 500);
        }

        if ($result['success']) {
            return redirect()->back()
                ->with('success', 'Target locked successfully.');
        }

        return redirect()->back()
            ->with('error', $result['message']);
    }

    /**
     * Unlock target
     */
    public function unlock(MarketingTarget $marketingTarget)
    {
        try {
            $marketingTarget->unlock();

            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Target unlocked successfully'
                ]);
            }

            return redirect()->back()
                ->with('success', 'Target unlocked successfully.');
        } catch (\Exception $e) {
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to unlock target: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to unlock target: ' . $e->getMessage());
        }
    }
}
