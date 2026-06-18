<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Finance\CommissionTransfer;
use App\Models\Contract;
use App\Models\User;
use App\Models\Finance\CommissionCalculation;
use Illuminate\Support\Facades\Auth;

class CommissionTransferController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $transfers = CommissionTransfer::with(['fromUser', 'toUser', 'contract', 'approvedBy', 'commissionCalculation', 'createdBy', 'updatedBy'])
            ->orderBy('created_at', 'desc')
            ->paginateStd(25);

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'data' => $transfers
            ]);
        }

        return view('finance.commission-transfers.index', compact('transfers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $users = User::whereHas('roles', function($q) {
            $q->where('name', 'like', 'Marketing%');
        })->get();
        
        $contracts = collect(); // Start with empty collection, will be populated via AJAX based on from_user_id

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'users' => $users,
                'contracts' => $contracts
            ]);
        }

        return view('finance.commission-transfers.create', compact('users', 'contracts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'contract_id' => 'required|exists:contracts,id',
            'from_user_id' => 'required|exists:users,id',
            'to_user_id' => 'required|exists:users,id|different:from_user_id',
            'commission_calculation_id' => 'required|exists:commission_calculations,id',
            'commission_amount' => 'required|numeric|min:0',
            'reason' => 'required|string|max:1000'
        ]);

        try {
            $transfer = CommissionTransfer::create([
                'contract_id' => $request->contract_id,
                'from_user_id' => $request->from_user_id,
                'to_user_id' => $request->to_user_id,
                'commission_calculation_id' => $request->commission_calculation_id,
                'commission_amount' => $request->commission_amount,
                'reason' => $request->reason,
                'status' => 'pending',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Commission transfer request created successfully',
                    'data' => $transfer
                ], 201);
            }

            return redirect()->route('commission-transfers.index')
                ->with('success', 'Commission transfer request created successfully.');
        } catch (\Exception $e) {
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create commission transfer: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to create commission transfer: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(CommissionTransfer $commissionTransfer)
    {
        $commissionTransfer->load([
            'fromUser', 
            'toUser', 
            'contract.customer', 
            'approvedBy', 
            'commissionCalculation',
            'createdBy',
            'updatedBy'
        ]);

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'data' => $commissionTransfer
            ]);
        }

        return view('finance.commission-transfers.show', compact('commissionTransfer'));
    }

    /**
     * Approve commission transfer
     */
    public function approve(Request $request, CommissionTransfer $commissionTransfer)
    {
        $request->validate([
            'approval_notes' => 'nullable|string|max:1000'
        ]);

        try {
            $commissionTransfer->approve(Auth::id(), $request->approval_notes);

            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Commission transfer approved successfully'
                ]);
            }

            return redirect()->back()
                ->with('success', 'Commission transfer approved successfully.');
        } catch (\Exception $e) {
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to approve commission transfer: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to approve commission transfer: ' . $e->getMessage());
        }
    }

    /**
     * Reject commission transfer
     */
    public function reject(Request $request, CommissionTransfer $commissionTransfer)
    {
        $request->validate([
            'reason' => 'nullable|string|max:1000'
        ]);

        try {
            $commissionTransfer->reject($request->reason);

            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Commission transfer rejected successfully'
                ]);
            }

            return redirect()->back()
                ->with('success', 'Commission transfer rejected successfully.');
        } catch (\Exception $e) {
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to reject commission transfer: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to reject commission transfer: ' . $e->getMessage());
        }
    }

    /**
     * Get commission calculations by contract ID (AJAX)
     */
    public function getCalculationsByContract(Request $request, $contractId)
    {
        try {
            $userId = $request->get('user_id');
            $query = CommissionCalculation::where('contract_id', $contractId)
                ->whereIn('status', ['calculated', 'approved']);
            
            if ($userId) {
                $query->where('user_id', $userId);
            }

            $calculations = $query->orderBy('calculation_date', 'desc')->get();

            return response()->json([
                'status' => 'success',
                'data' => $calculations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get contracts by marketing user (AJAX)
     */
    public function getContractsByUser(Request $request, $userId)
    {
        try {
            $contracts = Contract::where('marketing_id', $userId)
                ->where('contract_status', 'active')
                ->with('customer')
                ->orderBy('contract_number', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $contracts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
