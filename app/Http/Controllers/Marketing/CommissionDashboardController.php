<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Finance\MarketingTarget;
use App\Models\Finance\AchievementPeriod;
use App\Models\Finance\CommissionCalculation;
use App\Models\Finance\Achievement;
use App\Services\Finance\MarketingTargetService;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class CommissionDashboardController extends Controller
{
    protected $marketingTargetService;

    public function __construct(MarketingTargetService $marketingTargetService)
    {
        $this->marketingTargetService = $marketingTargetService;
    }

    /**
     * Display commission dashboard for marketing user
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get current achievement period
        $currentPeriod = AchievementPeriod::current()->first();
        
        if (!$currentPeriod) {
            return view('marketing.commissions.dashboard', [
                'user' => $user,
                'currentPeriod' => null,
                'summary' => null,
                'newTarget' => null,
                'renewalTarget' => null,
                'commissions' => null,
                'achievements' => null,
                'message' => 'No active achievement period found.'
            ]);
        }

        // Get target summary
        $targetSummary = $this->marketingTargetService->getTargetSummary($user->id, $currentPeriod->id);

        // Get new contract target
        $newTarget = MarketingTarget::where('user_id', $user->id)
            ->where('achievement_period_id', $currentPeriod->id)
            ->where('target_type', 'new')
            ->first();

        // Get renewal contract target
        $renewalTarget = MarketingTarget::where('user_id', $user->id)
            ->where('achievement_period_id', $currentPeriod->id)
            ->where('target_type', 'renewal')
            ->first();

        // Get commission calculations for current period
        $commissions = CommissionCalculation::where('user_id', $user->id)
            ->where('achievement_period_id', $currentPeriod->id)
            ->with(['contract.customer', 'commissionLevel', 'user'])
            ->orderBy('calculation_date', 'desc')
            ->get();

        // Get achievements
        $achievements = Achievement::where('user_id', $user->id)
            ->where('achievement_period_id', $currentPeriod->id)
            ->with(['contract.customer', 'commissionLevel'])
            ->orderBy('achievement_date', 'desc')
            ->get();

        // Calculate summary statistics
        $totalCommission = $commissions->where('status', 'approved')->sum('final_amount');
        $pendingCommission = $commissions->where('status', 'pending')->sum('final_amount');
        $voidCommission = $commissions->where('status', 'void')->sum('final_amount');

        $summary = [
            'total_commission' => $totalCommission,
            'pending_commission' => $pendingCommission,
            'void_commission' => $voidCommission,
            'total_contracts' => $achievements->count(),
            'installed_contracts' => $achievements->where('is_installed', true)->count(),
            'new_contracts' => $achievements->where('achievement_type', 'new')->count(),
            'renewal_contracts' => $achievements->where('achievement_type', 'renewal')->count(),
        ];

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'user' => $user,
                'period' => $currentPeriod,
                'summary' => $summary,
                'targets' => [
                    'new' => $newTarget,
                    'renewal' => $renewalTarget
                ],
                'commissions' => $commissions,
                'achievements' => $achievements
            ]);
        }

        return view('marketing.commissions.dashboard', compact(
            'user',
            'currentPeriod',
            'summary',
            'newTarget',
            'renewalTarget',
            'commissions',
            'achievements'
        ));
    }

    /**
     * Get commission details for specific period
     */
    public function getPeriodDetails($periodId)
    {
        $user = Auth::user();
        $period = AchievementPeriod::findOrFail($periodId);

        $targetSummary = $this->marketingTargetService->getTargetSummary($user->id, $period->id);

        $commissions = CommissionCalculation::where('user_id', $user->id)
            ->where('achievement_period_id', $period->id)
            ->with(['contract.customer', 'commissionLevel'])
            ->orderBy('calculation_date', 'desc')
            ->get();

        $achievements = Achievement::where('user_id', $user->id)
            ->where('achievement_period_id', $period->id)
            ->with(['contract.customer', 'commissionLevel'])
            ->orderBy('achievement_date', 'desc')
            ->get();

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'period' => $period,
                'targets' => $targetSummary,
                'commissions' => $commissions,
                'achievements' => $achievements
            ]);
        }

        return view('marketing.commissions.period-details', compact('user', 'period', 'targetSummary', 'commissions', 'achievements'));
    }

    /**
     * Get commission calculation details
     */
    public function getCommissionDetails($calculationId)
    {
        $user = Auth::user();
        
        // Security: Ensure user can only view their own commission
        $calculation = CommissionCalculation::where('id', $calculationId)
            ->where('user_id', $user->id) // Filter by logged-in user
            ->with([
                'contract.customer',
                'commissionLevel',
                'user',
                'crVariable',
                'marketingTarget',
                'achievementPeriod'
            ])
            ->firstOrFail(); // Will throw 404 if not found (user cannot access other's commission)

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'data' => $calculation
            ]);
        }

        return view('marketing.commissions.details', compact('calculation'));
    }
}
