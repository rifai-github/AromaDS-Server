<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\SalesCommission;
use App\Models\Finance\CommissionCondition;
use App\Models\Finance\FinanceLog;
use App\Models\Finance\CommissionWithdrawal;
use App\Models\User;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SalesCommissionController extends Controller
{
    public function index(Request $request)
    {
        $query = SalesCommission::with(['user', 'contract', 'commissionConditions']);

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
            $query->whereBetween('calculated_date', [$request->start_date, $request->end_date]);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            })->orWhereHas('contract', function($q) use ($search) {
                $q->where('contract_number', 'like', '%' . $search . '%');
            });
        }

        $commissions = $query->orderBy('calculated_date', 'desc')->paginate(15);

        // Get filter options
        $users = User::where('is_active', true)->get();
        $statuses = ['pending', 'valid', 'void', 'paid'];

        return view('finance.sales-commissions.index', compact('commissions', 'users', 'statuses'));
    }

    public function create()
    {
        $users = User::where('is_active', true)->get();
        $contracts = Contract::where('status', 'active')->get();
        $commissionTypes = ['percentage', 'fixed'];

        return view('finance.sales-commissions.create', compact('users', 'contracts', 'commissionTypes'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'contract_id' => 'required|exists:contracts,id',
            'commission_type' => 'required|in:percentage,fixed',
            'amount' => 'required|numeric|min:0',
            'calculated_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $commission = SalesCommission::create([
                'user_id' => $request->user_id,
                'contract_id' => $request->contract_id,
                'commission_type' => $request->commission_type,
                'amount' => $request->amount,
                'status' => 'pending',
                'calculated_date' => $request->calculated_date,
            ]);

            // Create finance log
            FinanceLog::create([
                'user_id' => $request->user_id,
                'transaction_type' => 'commission',
                'amount' => $request->amount,
                'balance' => 0, // Will be calculated later
                'notes' => 'Commission created for contract: ' . $commission->contract->contract_number,
            ]);

            DB::commit();

            return redirect()->route('finance.sales-commissions.index')
                ->with('success', 'Sales commission created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error creating sales commission: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show($id)
    {
        $commission = SalesCommission::with(['user', 'contract', 'commissionConditions.invoice'])
            ->findOrFail($id);

        return view('finance.sales-commissions.show', compact('commission'));
    }

    public function edit($id)
    {
        $commission = SalesCommission::findOrFail($id);
        $users = User::where('is_active', true)->get();
        $contracts = Contract::where('status', 'active')->get();
        $commissionTypes = ['percentage', 'fixed'];

        return view('finance.sales-commissions.edit', compact('commission', 'users', 'contracts', 'commissionTypes'));
    }

    public function update(Request $request, $id)
    {
        $commission = SalesCommission::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'contract_id' => 'required|exists:contracts,id',
            'commission_type' => 'required|in:percentage,fixed',
            'amount' => 'required|numeric|min:0',
            'calculated_date' => 'required|date',
            'status' => 'required|in:pending,valid,void,paid',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $commission->update([
                'user_id' => $request->user_id,
                'contract_id' => $request->contract_id,
                'commission_type' => $request->commission_type,
                'amount' => $request->amount,
                'status' => $request->status,
                'calculated_date' => $request->calculated_date,
            ]);

            // Update finance log if status changed
            if ($commission->wasChanged('status')) {
                FinanceLog::create([
                    'user_id' => $request->user_id,
                    'transaction_type' => 'commission',
                    'amount' => $request->amount,
                    'balance' => 0, // Will be calculated later
                    'notes' => 'Commission status updated to: ' . $request->status,
                ]);
            }

            DB::commit();

            return redirect()->route('finance.sales-commissions.index')
                ->with('success', 'Sales commission updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error updating sales commission: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $commission = SalesCommission::findOrFail($id);
            $commission->delete();

            return redirect()->route('finance.sales-commissions.index')
                ->with('success', 'Sales commission deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error deleting sales commission: ' . $e->getMessage());
        }
    }

    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:sales_commissions,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $count = SalesCommission::whereIn('id', $request->ids)->delete();
            
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

    public function validateCommission($id)
    {
        try {
            $commission = SalesCommission::findOrFail($id);
            
            // Check if payment is within 180 days
            $conditions = $commission->commissionConditions;
            $isValid = true;
            
            foreach ($conditions as $condition) {
                if ($condition->days_overdue > 180) {
                    $isValid = false;
                    break;
                }
            }

            $commission->update(['status' => $isValid ? 'valid' : 'void']);

            return redirect()->back()
                ->with('success', 'Commission validation completed.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error validating commission: ' . $e->getMessage());
        }
    }

    public function requestWithdrawal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check if user has valid commissions
            $validCommissions = SalesCommission::where('user_id', $request->user_id)
                ->where('status', 'valid')
                ->sum('amount');

            if ($validCommissions < $request->amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient commission balance'
                ], 400);
            }

            CommissionWithdrawal::create([
                'user_id' => $request->user_id,
                'amount' => $request->amount,
                'status' => 'pending',
                'requested_date' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal request submitted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating withdrawal request: ' . $e->getMessage()
            ], 500);
        }
    }
}
