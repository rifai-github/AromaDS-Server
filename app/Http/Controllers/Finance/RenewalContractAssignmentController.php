<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Finance\RenewalContractAssignment;
use App\Models\Finance\AchievementPeriod;
use App\Services\Finance\RenewalAssignmentService;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class RenewalContractAssignmentController extends Controller
{
    protected $renewalAssignmentService;

    public function __construct(RenewalAssignmentService $renewalAssignmentService)
    {
        $this->renewalAssignmentService = $renewalAssignmentService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $assignments = RenewalContractAssignment::with(['user', 'achievementPeriod', 'createdBy', 'updatedBy'])
            ->orderBy('created_at', 'desc')
            ->paginateStd(25);

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'data' => $assignments
            ]);
        }

        return view('finance.renewal-contract-assignments.index', compact('assignments'));
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

        return view('finance.renewal-contract-assignments.create', compact('users', 'periods'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'achievement_period_id' => 'required|exists:achievement_periods,id',
            'contract_number_from' => 'nullable|string|max:100',
            'contract_number_to' => 'nullable|string|max:100',
            'target_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000'
        ]);

        $result = $this->renewalAssignmentService->assignRenewalContracts($request->all());

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json($result, $result['success'] ? 201 : 500);
        }

        if ($result['success']) {
            return redirect()->route('renewal-contract-assignments.index')
                ->with('success', 'Renewal contract assignment created successfully.');
        }

        return redirect()->back()
            ->with('error', $result['message'])
            ->withInput();
    }

    /**
     * Display the specified resource.
     */
    public function show(RenewalContractAssignment $renewalContractAssignment)
    {
        $renewalContractAssignment->load(['user', 'achievementPeriod', 'createdBy', 'updatedBy', 'lockedBy']);

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'data' => $renewalContractAssignment
            ]);
        }

        return view('finance.renewal-contract-assignments.show', compact('renewalContractAssignment'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RenewalContractAssignment $renewalContractAssignment)
    {
        // Check if assignment is locked
        if ($renewalContractAssignment->is_locked) {
            return redirect()->back()
                ->with('error', 'Cannot edit locked assignment.');
        }

        $renewalContractAssignment->load(['user', 'achievementPeriod']);
        
        $users = User::whereHas('roles', function($q) {
            $q->where('name', 'like', 'Marketing%');
        })->get();
        
        $periods = AchievementPeriod::active()->orderBy('start_date', 'desc')->get();

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'data' => $renewalContractAssignment,
                'users' => $users,
                'periods' => $periods
            ]);
        }

        return view('finance.renewal-contract-assignments.edit', compact('renewalContractAssignment', 'users', 'periods'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RenewalContractAssignment $renewalContractAssignment)
    {
        // Check if assignment is locked
        if ($renewalContractAssignment->is_locked) {
            $error = 'Cannot update locked assignment. Please unlock it first.';
            
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => $error
                ], 403);
            }

            return redirect()->back()->with('error', $error);
        }

        $request->validate([
            'contract_number_from' => 'nullable|string|max:100',
            'contract_number_to' => 'nullable|string|max:100',
            'target_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000'
        ]);

        try {
            $renewalContractAssignment->update([
                'contract_number_from' => $request->contract_number_from,
                'contract_number_to' => $request->contract_number_to,
                'target_amount' => $request->target_amount,
                'notes' => $request->notes,
                'updated_by' => Auth::id()
            ]);

            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Renewal contract assignment updated successfully',
                    'data' => $renewalContractAssignment
                ]);
            }

            return redirect()->route('renewal-contract-assignments.index')
                ->with('success', 'Renewal contract assignment updated successfully.');
        } catch (\Exception $e) {
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update renewal contract assignment: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to update renewal contract assignment: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RenewalContractAssignment $renewalContractAssignment)
    {
        try {
            // Check if assignment is locked
            if ($renewalContractAssignment->is_locked) {
                throw new \Exception('Cannot delete locked assignment. Please unlock it first.');
            }

            $renewalContractAssignment->delete();

            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Renewal contract assignment deleted successfully'
                ]);
            }

            return redirect()->route('renewal-contract-assignments.index')
                ->with('success', 'Renewal contract assignment deleted successfully.');
        } catch (\Exception $e) {
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to delete renewal contract assignment: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to delete renewal contract assignment: ' . $e->getMessage());
        }
    }

    /**
     * Lock assignment
     */
    public function lock(RenewalContractAssignment $renewalContractAssignment)
    {
        $result = $this->renewalAssignmentService->lockAssignment($renewalContractAssignment->id, Auth::id());

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json($result, $result['success'] ? 200 : 500);
        }

        if ($result['success']) {
            return redirect()->back()
                ->with('success', 'Assignment locked successfully.');
        }

        return redirect()->back()
            ->with('error', $result['message']);
    }

    /**
     * Unlock assignment
     */
    public function unlock(RenewalContractAssignment $renewalContractAssignment)
    {
        try {
            $renewalContractAssignment->unlock();

            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Assignment unlocked successfully'
                ]);
            }

            return redirect()->back()
                ->with('success', 'Assignment unlocked successfully.');
        } catch (\Exception $e) {
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to unlock assignment: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to unlock assignment: ' . $e->getMessage());
        }
    }
}
