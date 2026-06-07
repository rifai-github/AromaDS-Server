<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Traits\AccessControlFilterTrait;
use Illuminate\Http\Request;
use App\Models\Finance\CommissionCalculation;
use App\Models\Finance\AchievementPeriod;
use App\Models\Finance\SalesCommission;
use App\Models\Finance\CommissionWithdrawal;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CommissionController extends Controller
{
    use AccessControlFilterTrait;

    private function accessibleCommissionQuery()
    {
        $query = CommissionCalculation::query();
        $user = Auth::user();

        if ($this->hasUnrestrictedAccessControlData($user)) {
            return $query;
        }

        $accessibleUserIds = $this->getAccessibleUserIds($user);

        return $query->where(function ($q) use ($accessibleUserIds) {
            $q->whereIn('user_id', $accessibleUserIds)
                ->orWhereHas('contract', function ($contractQuery) use ($accessibleUserIds) {
                    $contractQuery->whereIn('created_by', $accessibleUserIds)
                        ->orWhereIn('marketing_id', $accessibleUserIds);
                });
        });
    }
    
    /**
     * Display a listing of commission calculations
     */
    public function index(Request $request)
    {
        $query = CommissionCalculation::filter($request->all())
            ->with(['user', 'achievementPeriod', 'contract', 'approvedBy', 'createdBy', 'updatedBy'])
            ->orderBy('calculation_date', 'desc');

        // Apply access control filter (hierarchical data)
        $user = Auth::user();
        if (!$user->hasRoleStartingWith('Management')) {
            $accessibleUserIds = $this->getAccessibleUserIds($user);
            $query->where(function($q) use ($accessibleUserIds) {
                $q->whereIn('user_id', $accessibleUserIds)
                  ->orWhereHas('contract', function($subQ) use ($accessibleUserIds) {
                      $subQ->whereIn('created_by', $accessibleUserIds)
                           ->orWhereIn('marketing_id', $accessibleUserIds);
                  });
            });
        }

        // Additional manual filters for the top form (if not already handled by autoFilter)
        // AutoFilter handles filter[user_id], but the top form might send user_id directly
        if ($request->filled('user_id') && !$request->has('filter.user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('period_id') && !$request->has('filter.achievement_period_id')) {
            $query->where('achievement_period_id', $request->period_id);
        }

        if ($request->filled('status') && !$request->has('filter.status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('calculation_date', [$request->start_date, $request->end_date]);
        }

        $commissions = $query->paginate(20);
        $users = User::where('is_active', true)->get();
        $periods = AchievementPeriod::orderBy('start_date', 'desc')->get();

        return view('finance.commissions.index', compact('commissions', 'users', 'periods'));
    }

    /**
     * Show the form for creating a new commission calculation
     */
    public function create()
    {
        $users = $this->applyAccessibleUserFilter(User::where('is_active', true), Auth::user())->get();
        $periods = AchievementPeriod::active()->get();
        $contracts = $this->applyContractAccessControlFilter(
            \App\Models\Contract::where('status', 'active'),
            Auth::user()
        )->get();

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'users' => $users,
                'periods' => $periods,
                'contracts' => $contracts
            ]);
        }

        return view('finance.commissions.create', compact('users', 'periods', 'contracts'));
    }

    /**
     * Store a newly created commission calculation
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'achievement_period_id' => 'required|exists:achievement_periods,id',
            'contract_id' => 'nullable|exists:contracts,id',
            'calculation_type' => 'required|in:automatic,manual,adjustment',
            'base_amount' => 'required|numeric|min:0',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'bonus_amount' => 'nullable|numeric|min:0',
            'penalty_amount' => 'nullable|numeric|min:0',
            'calculation_notes' => 'nullable|string|max:1000'
        ]);

        $canUseUser = $this->applyAccessibleUserFilter(User::query(), Auth::user())
            ->whereKey($request->user_id)
            ->exists();

        if (!$canUseUser) {
            return redirect()->back()
                ->with('error', 'User is outside your accessible data scope.')
                ->withInput();
        }

        if ($request->contract_id) {
            $canUseContract = $this->applyContractAccessControlFilter(\App\Models\Contract::query(), Auth::user())
                ->whereKey($request->contract_id)
                ->exists();

            if (!$canUseContract) {
                return redirect()->back()
                    ->with('error', 'Contract is outside your accessible data scope.')
                    ->withInput();
            }
        }

        try {
            DB::beginTransaction();

            $commission = CommissionCalculation::create([
                'user_id' => $request->user_id,
                'achievement_period_id' => $request->achievement_period_id,
                'contract_id' => $request->contract_id,
                'calculation_type' => $request->calculation_type,
                'base_amount' => $request->base_amount,
                'commission_rate' => $request->commission_rate,
                'commission_amount' => $request->base_amount * ($request->commission_rate / 100),
                'bonus_amount' => $request->bonus_amount ?? 0,
                'penalty_amount' => $request->penalty_amount ?? 0,
                'final_amount' => 0, // Will be calculated
                'status' => 'calculated',
                'calculation_date' => now(),
                'calculation_notes' => $request->calculation_notes,
                'created_by' => Auth::id()
            ]);

            // Calculate final amount
            $commission->calculateFinalAmount();
            $commission->save();

            DB::commit();

            return redirect()->route('commissions.index')
                ->with('success', 'Commission calculation created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to create commission calculation: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified commission calculation
     */
    public function show(CommissionCalculation $commission)
    {
        $commission = $this->accessibleCommissionQuery()->whereKey($commission->id)->firstOrFail();
        $commission->load(['user', 'achievementPeriod', 'contract', 'approvedBy', 'createdBy']);
        
        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'commission' => $commission
            ]);
        }
        
        return view('finance.commissions.show', compact('commission'));
    }

    /**
     * Show the form for editing the specified commission calculation
     */
    public function edit(CommissionCalculation $commission)
    {
        $commission = $this->accessibleCommissionQuery()->whereKey($commission->id)->firstOrFail();
        $users = $this->applyAccessibleUserFilter(User::where('is_active', true), Auth::user())->get();
        $periods = AchievementPeriod::active()->get();
        $contracts = $this->applyContractAccessControlFilter(
            \App\Models\Contract::where('status', 'active'),
            Auth::user()
        )->get();

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'commission' => $commission,
                'users' => $users,
                'periods' => $periods,
                'contracts' => $contracts
            ]);
        }

        return view('finance.commissions.edit', compact('commission', 'users', 'periods', 'contracts'));
    }

    /**
     * Update the specified commission calculation
     */
    public function update(Request $request, CommissionCalculation $commission)
    {
        $commission = $this->accessibleCommissionQuery()->whereKey($commission->id)->firstOrFail();

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'achievement_period_id' => 'required|exists:achievement_periods,id',
            'contract_id' => 'nullable|exists:contracts,id',
            'calculation_type' => 'required|in:automatic,manual,adjustment',
            'base_amount' => 'required|numeric|min:0',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'bonus_amount' => 'nullable|numeric|min:0',
            'penalty_amount' => 'nullable|numeric|min:0',
            'calculation_notes' => 'nullable|string|max:1000'
        ]);

        $canUseUser = $this->applyAccessibleUserFilter(User::query(), Auth::user())
            ->whereKey($request->user_id)
            ->exists();

        if (!$canUseUser) {
            return redirect()->back()
                ->with('error', 'User is outside your accessible data scope.')
                ->withInput();
        }

        if ($request->contract_id) {
            $canUseContract = $this->applyContractAccessControlFilter(\App\Models\Contract::query(), Auth::user())
                ->whereKey($request->contract_id)
                ->exists();

            if (!$canUseContract) {
                return redirect()->back()
                    ->with('error', 'Contract is outside your accessible data scope.')
                    ->withInput();
            }
        }

        try {
            DB::beginTransaction();

            $commission->update([
                'user_id' => $request->user_id,
                'achievement_period_id' => $request->achievement_period_id,
                'contract_id' => $request->contract_id,
                'calculation_type' => $request->calculation_type,
                'base_amount' => $request->base_amount,
                'commission_rate' => $request->commission_rate,
                'commission_amount' => $request->base_amount * ($request->commission_rate / 100),
                'bonus_amount' => $request->bonus_amount ?? 0,
                'penalty_amount' => $request->penalty_amount ?? 0,
                'calculation_notes' => $request->calculation_notes,
                'updated_by' => Auth::id()
            ]);

            // Recalculate final amount
            $commission->calculateFinalAmount();
            $commission->save();

            DB::commit();

            return redirect()->route('commissions.index')
                ->with('success', 'Commission calculation updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to update commission calculation: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified commission calculation
     */
    public function destroy(CommissionCalculation $commission)
    {
        try {
            $commission = $this->accessibleCommissionQuery()->whereKey($commission->id)->firstOrFail();
            $commission->delete();
            return redirect()->route('commissions.index')
                ->with('success', 'Commission calculation deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete commission calculation: ' . $e->getMessage());
        }
    }

    /**
     * Approve commission calculation
     */
    public function approve(CommissionCalculation $commission)
    {
        try {
            $commission = $this->accessibleCommissionQuery()->whereKey($commission->id)->firstOrFail();
            $commission->approve(Auth::id());
            return redirect()->back()
                ->with('success', 'Commission calculation approved successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to approve commission calculation: ' . $e->getMessage());
        }
    }

    /**
     * Mark commission as paid
     */
    public function markAsPaid(CommissionCalculation $commission)
    {
        try {
            $commission = $this->accessibleCommissionQuery()->whereKey($commission->id)->firstOrFail();
            $commission->markAsPaid();
            return redirect()->back()
                ->with('success', 'Commission marked as paid successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to mark commission as paid: ' . $e->getMessage());
        }
    }

    /**
     * Cancel commission calculation
     */
    public function cancel(Request $request, CommissionCalculation $commission)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        try {
            $commission = $this->accessibleCommissionQuery()->whereKey($commission->id)->firstOrFail();
            $commission->cancel($request->reason);
            return redirect()->back()
                ->with('success', 'Commission calculation cancelled successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to cancel commission calculation: ' . $e->getMessage());
        }
    }

    /**
     * Get commission statistics
     */
    public function statistics(Request $request)
    {
        try {
            $periodId = $request->get('period_id');
            $userId = $request->get('user_id');

            $query = CommissionCalculation::query();

            if ($periodId) {
                $query->where('achievement_period_id', $periodId);
            }

            if ($userId) {
                $query->where('user_id', $userId);
            }

            $stats = [
                'total_commissions' => $query->count(),
                'total_amount' => $query->sum('final_amount') ?: 0,
                'pending_approval' => $query->where('status', 'calculated')->count(),
                'approved' => $query->where('status', 'approved')->count(),
                'paid' => $query->where('status', 'paid')->count(),
                'cancelled' => $query->where('status', 'cancelled')->count()
            ];

            return response()->json($stats, 200, [
                'Content-Type' => 'application/json'
            ]);
        } catch (\Exception $e) {
            \Log::error('Commission statistics error: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to load statistics'], 500);
        }
    }
}
