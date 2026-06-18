<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Finance\AchievementPeriod;
use Illuminate\Support\Facades\Auth;

class AchievementPeriodController extends Controller
{
    /**
     * Display a listing of achievement periods
     */
    public function index(Request $request)
    {
        $achievementPeriods = AchievementPeriod::filter($request->all())
            ->with(['createdBy', 'updatedBy'])
            ->orderBy('start_date', 'desc')
            ->paginateStd(25);

        return view('finance.achievement-periods.index', compact('achievementPeriods'));
    }

    /**
     * Show the form for creating a new achievement period
     */
    public function create()
    {
        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success'
            ]);
        }
        
        return view('finance.achievement-periods.create');
    }

    /**
     * Store a newly created achievement period
     */
    public function store(Request $request)
    {
        $request->validate([
            'period_name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'description' => 'nullable|string|max:1000'
        ]);

        try {
            AchievementPeriod::create([
                'period_name' => $request->period_name,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'status' => 'active',
                'description' => $request->description,
                'created_by' => Auth::id()
            ]);

            return redirect()->route('achievement-periods.index')
                ->with('success', 'Achievement period created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create achievement period: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified achievement period
     */
    public function show(AchievementPeriod $achievementPeriod)
    {
        $achievementPeriod->load(['createdBy', 'achievements', 'commissionCalculations']);
        $stats = $achievementPeriod->getAchievementStats();
        
        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'period' => $achievementPeriod,
                'stats' => $stats
            ]);
        }
        
        return view('finance.achievement-periods.show', compact('achievementPeriod', 'stats'));
    }

    /**
     * Show the form for editing the specified achievement period
     */
    public function edit(AchievementPeriod $achievementPeriod)
    {
        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'period' => $achievementPeriod
            ]);
        }
        
        return view('finance.achievement-periods.edit', compact('achievementPeriod'));
    }

    /**
     * Update the specified achievement period
     */
    public function update(Request $request, AchievementPeriod $achievementPeriod)
    {
        $request->validate([
            'period_name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:active,inactive,completed',
            'description' => 'nullable|string|max:1000'
        ]);

        try {
            $achievementPeriod->update([
                'period_name' => $request->period_name,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'status' => $request->status,
                'description' => $request->description,
                'updated_by' => Auth::id()
            ]);

            return redirect()->route('achievement-periods.index')
                ->with('success', 'Achievement period updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update achievement period: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified achievement period
     */
    public function destroy(AchievementPeriod $achievementPeriod)
    {
        try {
            $achievementPeriod->delete();
            return redirect()->route('achievement-periods.index')
                ->with('success', 'Achievement period deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete achievement period: ' . $e->getMessage());
        }
    }
}
