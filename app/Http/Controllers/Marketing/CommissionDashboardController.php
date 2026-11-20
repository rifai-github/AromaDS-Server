<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Finance\MarketingTarget;
use App\Models\Finance\AchievementPeriod;
use App\Models\Finance\CommissionCalculation;
use App\Models\Finance\CommissionPayment;
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
     * Dashboard komisi marketing: pencapaian per periode (bisa pilih periode), wallet komisi
     * lintas periode, dan tombol Request Pencairan (meeting 17 Nov 2026).
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $periods = AchievementPeriod::orderByDesc('start_date')->get();
        $currentPeriod = $request->filled('period_id')
            ? $periods->firstWhere('id', (int) $request->period_id)
            : AchievementPeriod::current()->first();
        $currentPeriod ??= $periods->first();

        $wallet = $this->walletFor($user->id);

        if (!$currentPeriod) {
            return view('marketing.commissions.dashboard', [
                'user' => $user,
                'periods' => $periods,
                'wallet' => $wallet,
                'currentPeriod' => null,
                'summary' => null,
                'newTarget' => null,
                'renewalTarget' => null,
                'commissions' => null,
                'achievements' => null,
                'message' => 'No active achievement period found.'
            ]);
        }

        $newTarget = MarketingTarget::where('user_id', $user->id)
            ->where('achievement_period_id', $currentPeriod->id)
            ->where('target_type', 'new')
            ->first();

        $renewalTarget = MarketingTarget::where('user_id', $user->id)
            ->where('achievement_period_id', $currentPeriod->id)
            ->where('target_type', 'renewal')
            ->first();

        $commissions = CommissionCalculation::where('user_id', $user->id)
            ->where('achievement_period_id', $currentPeriod->id)
            ->with(['contract.customer', 'commissionLevel', 'user', 'createdBy', 'updatedBy'])
            ->orderBy('calculation_date', 'desc')
            ->get();

        $achievements = Achievement::where('user_id', $user->id)
            ->where('achievement_period_id', $currentPeriod->id)
            ->with(['contract.customer', 'commissionLevel'])
            ->orderBy('achievement_date', 'desc')
            ->get();

        $summary = [
            'total_commission' => $commissions->filter(fn ($c) => in_array($c->status, ['approved', 'paid', 'over_period'], true))->sum('final_amount'),
            'pending_commission' => $commissions->where('status', 'calculated')->sum('final_amount'),
            'void_commission' => $commissions->where('status', 'cancelled')->sum('final_amount'),
            'over_period_count' => $commissions->where('status', 'over_period')->count(),
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
                'wallet' => $wallet,
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
            'periods',
            'wallet',
            'currentPeriod',
            'summary',
            'newTarget',
            'renewalTarget',
            'commissions',
            'achievements'
        ));
    }

    /**
     * Wallet komisi marketing (lintas periode).
     * - available: komisi siap cair (Approved / Over Period yang nominalnya dikembalikan) yang
     *   belum diminta dan belum punya pembayaran.
     * - requested: sudah diminta, menunggu Finance membuat pembayaran.
     * - in_process: sudah ada pembayaran pending/processing.
     * - paid: sudah dibayarkan.
     */
    private function walletFor(int $userId): array
    {
        $payable = fn () => CommissionCalculation::where('user_id', $userId)->payable()->withoutActivePayment();

        return [
            'available' => (float) $payable()->whereNull('payout_requested_at')->sum('final_amount'),
            'available_count' => $payable()->whereNull('payout_requested_at')->count(),
            'requested' => (float) $payable()->whereNotNull('payout_requested_at')->sum('final_amount'),
            'requested_at' => $payable()->max('payout_requested_at'),
            'in_process' => (float) CommissionPayment::where('user_id', $userId)->whereIn('status', ['pending', 'processing'])->sum('amount'),
            'paid' => (float) CommissionPayment::where('user_id', $userId)->where('status', 'completed')->sum('amount'),
        ];
    }

    /**
     * Marketing meminta pencairan semua komisi siap cair di wallet-nya. Admin melihat tandanya
     * di halaman Commission Payment.
     */
    public function requestPayout()
    {
        $user = Auth::user();

        $count = CommissionCalculation::where('user_id', $user->id)
            ->payable()
            ->withoutActivePayment()
            ->whereNull('payout_requested_at')
            ->update([
                'payout_requested_at' => now(),
                'payout_requested_by' => $user->id,
            ]);

        if ($count === 0) {
            return redirect()->back()->with('error', 'Tidak ada komisi siap cair di wallet Anda.');
        }

        return redirect()->back()->with('success', "Request pencairan {$count} komisi terkirim ke Finance.");
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

        // View period-details tidak pernah ada; dashboard kini bisa memilih periode.
        return redirect()->route('marketing.commissions.dashboard', ['period_id' => $period->id]);
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
