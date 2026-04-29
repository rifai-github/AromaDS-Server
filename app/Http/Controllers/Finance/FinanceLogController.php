<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FinanceLogController extends Controller
{
    public function index(Request $request)
    {
        $query = FinanceLog::with(['user']);

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by transaction type
        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            })->orWhere('notes', 'like', '%' . $search . '%');
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(15);

        // Get filter options
        $users = User::where('is_active', true)->get();
        $transactionTypes = ['commission', 'withdrawal', 'payment', 'adjustment'];

        return view('finance.finance-logs.index', compact('logs', 'users', 'transactionTypes'));
    }

    public function show($id)
    {
        $log = FinanceLog::with(['user'])
            ->findOrFail($id);

        return view('finance.finance-logs.show', compact('log'));
    }

    public function getUserLogs($userId)
    {
        $logs = FinanceLog::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($logs);
    }

    public function getUserBalance($userId)
    {
        $totalCommissions = FinanceLog::where('user_id', $userId)
            ->where('transaction_type', 'commission')
            ->sum('amount');

        $totalWithdrawals = FinanceLog::where('user_id', $userId)
            ->where('transaction_type', 'withdrawal')
            ->sum('amount');

        $totalPayments = FinanceLog::where('user_id', $userId)
            ->where('transaction_type', 'payment')
            ->sum('amount');

        $totalAdjustments = FinanceLog::where('user_id', $userId)
            ->where('transaction_type', 'adjustment')
            ->sum('amount');

        $currentBalance = $totalCommissions + $totalAdjustments - $totalWithdrawals - $totalPayments;

        return response()->json([
            'total_commissions' => $totalCommissions,
            'total_withdrawals' => $totalWithdrawals,
            'total_payments' => $totalPayments,
            'total_adjustments' => $totalAdjustments,
            'current_balance' => $currentBalance
        ]);
    }

    public function getTransactionSummary(Request $request)
    {
        $query = FinanceLog::query();

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        $summary = $query->selectRaw('
            transaction_type,
            COUNT(*) as count,
            SUM(amount) as total_amount,
            AVG(amount) as average_amount
        ')
        ->groupBy('transaction_type')
        ->get();

        return response()->json($summary);
    }

    public function export(Request $request)
    {
        $query = FinanceLog::with(['user']);

        // Apply same filters as index
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        $logs = $query->orderBy('created_at', 'desc')->get();

        // This would typically generate a CSV or Excel file
        // For now, just return the data
        return response()->json([
            'success' => true,
            'data' => $logs,
            'count' => $logs->count()
        ]);
    }

    public function createAdjustment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric',
            'notes' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            FinanceLog::create([
                'user_id' => $request->user_id,
                'transaction_type' => 'adjustment',
                'amount' => $request->amount,
                'balance' => 0, // Will be calculated later
                'notes' => $request->notes,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Adjustment created successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating adjustment: ' . $e->getMessage()
            ], 500);
        }
    }
}
