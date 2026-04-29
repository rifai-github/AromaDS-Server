<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\CommissionWithdrawal;
use App\Models\Finance\SalesCommission;
use App\Models\Finance\FinanceLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CommissionWithdrawalController extends Controller
{
    public function index(Request $request)
    {
        $query = CommissionWithdrawal::with(['user', 'approver']);

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('requested_date', [$request->start_date, $request->end_date]);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            });
        }

        $withdrawals = $query->orderBy('requested_date', 'desc')->paginate(15);

        // Get filter options
        $users = User::where('is_active', true)->get();
        $statuses = ['pending', 'approved', 'rejected', 'processed'];

        return view('finance.commission-withdrawals.index', compact('withdrawals', 'users', 'statuses'));
    }

    public function create()
    {
        $users = User::where('is_active', true)->get();

        return view('finance.commission-withdrawals.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Check if user has valid commissions
            $validCommissions = SalesCommission::where('user_id', $request->user_id)
                ->where('status', 'valid')
                ->sum('amount');

            if ($validCommissions < $request->amount) {
                return redirect()->back()
                    ->with('error', 'Insufficient commission balance. Available: Rp ' . number_format($validCommissions, 0, ',', '.'))
                    ->withInput();
            }

            CommissionWithdrawal::create([
                'user_id' => $request->user_id,
                'amount' => $request->amount,
                'status' => 'pending',
                'requested_date' => now(),
            ]);

            return redirect()->route('finance.commission-withdrawals.index')
                ->with('success', 'Commission withdrawal request created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error creating commission withdrawal: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show($id)
    {
        $withdrawal = CommissionWithdrawal::with(['user', 'approver'])
            ->findOrFail($id);

        return view('finance.commission-withdrawals.show', compact('withdrawal'));
    }

    public function edit($id)
    {
        $withdrawal = CommissionWithdrawal::findOrFail($id);
        $users = User::where('is_active', true)->get();
        $statuses = ['pending', 'approved', 'rejected', 'processed'];

        return view('finance.commission-withdrawals.edit', compact('withdrawal', 'users', 'statuses'));
    }

    public function update(Request $request, $id)
    {
        $withdrawal = CommissionWithdrawal::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0',
            'status' => 'required|in:pending,approved,rejected,processed',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $oldStatus = $withdrawal->status;
            $withdrawal->update([
                'user_id' => $request->user_id,
                'amount' => $request->amount,
                'status' => $request->status,
                'approved_date' => $request->status === 'approved' ? now() : null,
                'approved_by' => $request->status === 'approved' ? auth()->id() : null,
            ]);

            // Create finance log if status changed
            if ($oldStatus !== $request->status) {
                FinanceLog::create([
                    'user_id' => $request->user_id,
                    'transaction_type' => 'withdrawal',
                    'amount' => $request->amount,
                    'balance' => 0, // Will be calculated later
                    'notes' => 'Withdrawal status updated to: ' . $request->status,
                ]);
            }

            DB::commit();

            return redirect()->route('finance.commission-withdrawals.index')
                ->with('success', 'Commission withdrawal updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error updating commission withdrawal: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $withdrawal = CommissionWithdrawal::findOrFail($id);
            $withdrawal->delete();

            return redirect()->route('finance.commission-withdrawals.index')
                ->with('success', 'Commission withdrawal deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error deleting commission withdrawal: ' . $e->getMessage());
        }
    }

    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:commission_withdrawals,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $count = CommissionWithdrawal::whereIn('id', $request->ids)->delete();
            
            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$count} record(s)",
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting records: ' . $e->getMessage()
            ], 500);
        }
    }

    public function approve($id)
    {
        try {
            $withdrawal = CommissionWithdrawal::findOrFail($id);
            
            if ($withdrawal->status !== 'pending') {
                return redirect()->back()
                    ->with('error', 'Only pending withdrawals can be approved.');
            }

            DB::beginTransaction();

            $withdrawal->update([
                'status' => 'approved',
                'approved_date' => now(),
                'approved_by' => auth()->id(),
            ]);

            // Create finance log
            FinanceLog::create([
                'user_id' => $withdrawal->user_id,
                'transaction_type' => 'withdrawal',
                'amount' => $withdrawal->amount,
                'balance' => 0, // Will be calculated later
                'notes' => 'Withdrawal approved',
            ]);

            DB::commit();

            return redirect()->back()
                ->with('success', 'Commission withdrawal approved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error approving commission withdrawal: ' . $e->getMessage());
        }
    }

    public function reject($id)
    {
        try {
            $withdrawal = CommissionWithdrawal::findOrFail($id);
            
            if ($withdrawal->status !== 'pending') {
                return redirect()->back()
                    ->with('error', 'Only pending withdrawals can be rejected.');
            }

            $withdrawal->update([
                'status' => 'rejected',
                'approved_date' => now(),
                'approved_by' => auth()->id(),
            ]);

            return redirect()->back()
                ->with('success', 'Commission withdrawal rejected successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error rejecting commission withdrawal: ' . $e->getMessage());
        }
    }

    public function process($id)
    {
        try {
            $withdrawal = CommissionWithdrawal::findOrFail($id);
            
            if ($withdrawal->status !== 'approved') {
                return redirect()->back()
                    ->with('error', 'Only approved withdrawals can be processed.');
            }

            DB::beginTransaction();

            $withdrawal->update([
                'status' => 'processed',
            ]);

            // Create finance log
            FinanceLog::create([
                'user_id' => $withdrawal->user_id,
                'transaction_type' => 'withdrawal',
                'amount' => $withdrawal->amount,
                'balance' => 0, // Will be calculated later
                'notes' => 'Withdrawal processed',
            ]);

            DB::commit();

            return redirect()->back()
                ->with('success', 'Commission withdrawal processed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error processing commission withdrawal: ' . $e->getMessage());
        }
    }

    public function getUserCommissionBalance($userId)
    {
        $validCommissions = SalesCommission::where('user_id', $userId)
            ->where('status', 'valid')
            ->sum('amount');

        $pendingWithdrawals = CommissionWithdrawal::where('user_id', $userId)
            ->whereIn('status', ['pending', 'approved'])
            ->sum('amount');

        $availableBalance = $validCommissions - $pendingWithdrawals;

        return response()->json([
            'total_commissions' => $validCommissions,
            'pending_withdrawals' => $pendingWithdrawals,
            'available_balance' => $availableBalance
        ]);
    }
}
