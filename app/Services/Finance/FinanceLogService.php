<?php

namespace App\Services\Finance;

use App\Models\Finance\FinanceLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FinanceLogService
{
    /**
     * Create finance log entry
     */
    public function createFinanceLog(array $data): array
    {
        try {
            DB::beginTransaction();

            // Validate finance log data
            $this->validateFinanceLogData($data);

            $userId = $data['user_id'] ?? auth()->id();
            $amount = $data['amount'];
            $transactionType = $data['transaction_type'];

            // Calculate new balance
            $currentBalance = $this->getUserBalance($userId);
            $newBalance = $currentBalance + $amount;

            $financeLog = FinanceLog::create([
                'user_id' => $userId,
                'transaction_type' => $transactionType,
                'amount' => $amount,
                'balance' => $newBalance,
                'notes' => $data['notes'] ?? '',
            ]);

            DB::commit();

            return [
                'success' => true,
                'finance_log' => $financeLog,
                'message' => 'Finance log created successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Finance log creation failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to create finance log: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get user's finance log history
     */
    public function getUserFinanceLogs(int $userId, int $limit = 50): array
    {
        $logs = FinanceLog::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return [
            'success' => true,
            'logs' => $logs
        ];
    }

    /**
     * Get user's current balance
     */
    public function getUserBalance(int $userId): float
    {
        $lastLog = FinanceLog::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->first();
            
        return $lastLog ? $lastLog->balance : 0;
    }

    /**
     * Get user's balance history
     */
    public function getUserBalanceHistory(int $userId, string $period = 'month'): array
    {
        $startDate = $this->getPeriodStartDate($period);
        
        $logs = FinanceLog::where('user_id', $userId)
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'asc')
            ->get();

        $balanceHistory = [];
        foreach ($logs as $log) {
            $balanceHistory[] = [
                'date' => $log->created_at->toDateString(),
                'time' => $log->created_at->toTimeString(),
                'transaction_type' => $log->transaction_type,
                'amount' => $log->amount,
                'balance' => $log->balance,
                'notes' => $log->notes,
            ];
        }

        return [
            'success' => true,
            'balance_history' => $balanceHistory
        ];
    }

    /**
     * Get finance log statistics
     */
    public function getFinanceLogStatistics(): array
    {
        $total = FinanceLog::count();
        $commission = FinanceLog::where('transaction_type', 'commission')->count();
        $withdrawal = FinanceLog::where('transaction_type', 'withdrawal')->count();
        $payment = FinanceLog::where('transaction_type', 'payment')->count();
        $adjustment = FinanceLog::where('transaction_type', 'adjustment')->count();

        $totalAmount = FinanceLog::sum('amount');
        $commissionAmount = FinanceLog::where('transaction_type', 'commission')->sum('amount');
        $withdrawalAmount = FinanceLog::where('transaction_type', 'withdrawal')->sum('amount');
        $paymentAmount = FinanceLog::where('transaction_type', 'payment')->sum('amount');
        $adjustmentAmount = FinanceLog::where('transaction_type', 'adjustment')->sum('amount');

        return [
            'total_transactions' => $total,
            'commission_transactions' => $commission,
            'withdrawal_transactions' => $withdrawal,
            'payment_transactions' => $payment,
            'adjustment_transactions' => $adjustment,
            'total_amount' => $totalAmount,
            'commission_amount' => $commissionAmount,
            'withdrawal_amount' => $withdrawalAmount,
            'payment_amount' => $paymentAmount,
            'adjustment_amount' => $adjustmentAmount,
        ];
    }

    /**
     * Get user's finance log statistics
     */
    public function getUserFinanceLogStatistics(int $userId): array
    {
        $logs = FinanceLog::where('user_id', $userId)->get();

        $total = $logs->count();
        $commission = $logs->where('transaction_type', 'commission')->count();
        $withdrawal = $logs->where('transaction_type', 'withdrawal')->count();
        $payment = $logs->where('transaction_type', 'payment')->count();
        $adjustment = $logs->where('transaction_type', 'adjustment')->count();

        $totalAmount = $logs->sum('amount');
        $commissionAmount = $logs->where('transaction_type', 'commission')->sum('amount');
        $withdrawalAmount = $logs->where('transaction_type', 'withdrawal')->sum('amount');
        $paymentAmount = $logs->where('transaction_type', 'payment')->sum('amount');
        $adjustmentAmount = $logs->where('transaction_type', 'adjustment')->sum('amount');

        $currentBalance = $this->getUserBalance($userId);

        return [
            'user_id' => $userId,
            'current_balance' => $currentBalance,
            'total_transactions' => $total,
            'commission_transactions' => $commission,
            'withdrawal_transactions' => $withdrawal,
            'payment_transactions' => $payment,
            'adjustment_transactions' => $adjustment,
            'total_amount' => $totalAmount,
            'commission_amount' => $commissionAmount,
            'withdrawal_amount' => $withdrawalAmount,
            'payment_amount' => $paymentAmount,
            'adjustment_amount' => $adjustmentAmount,
        ];
    }

    /**
     * Get finance log trends
     */
    public function getFinanceLogTrends(string $period = 'month'): array
    {
        $startDate = $this->getPeriodStartDate($period);
        
        $logs = FinanceLog::where('created_at', '>=', $startDate)
            ->get()
            ->groupBy(function ($log) use ($period) {
                return $log->created_at->format($this->getDateFormat($period));
            });

        $trends = [];
        foreach ($logs as $date => $group) {
            $trends[] = [
                'date' => $date,
                'total_transactions' => $group->count(),
                'total_amount' => $group->sum('amount'),
                'commission_count' => $group->where('transaction_type', 'commission')->count(),
                'withdrawal_count' => $group->where('transaction_type', 'withdrawal')->count(),
                'payment_count' => $group->where('transaction_type', 'payment')->count(),
                'adjustment_count' => $group->where('transaction_type', 'adjustment')->count(),
            ];
        }

        return [
            'success' => true,
            'trends' => $trends
        ];
    }

    /**
     * Get top users by balance
     */
    public function getTopUsersByBalance(int $limit = 10): array
    {
        $users = User::with(['financeLogs' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(1);
            }])
            ->get()
            ->map(function ($user) {
                $user->current_balance = $this->getUserBalance($user->id);
                return $user;
            })
            ->sortByDesc('current_balance')
            ->take($limit);

        return [
            'success' => true,
            'users' => $users
        ];
    }

    /**
     * Get finance log summary by transaction type
     */
    public function getFinanceLogSummaryByType(string $period = 'month'): array
    {
        $startDate = $this->getPeriodStartDate($period);
        
        $summary = FinanceLog::where('created_at', '>=', $startDate)
            ->selectRaw('transaction_type, COUNT(*) as count, SUM(amount) as total_amount, AVG(amount) as avg_amount')
            ->groupBy('transaction_type')
            ->get();

        return [
            'success' => true,
            'summary' => $summary
        ];
    }

    /**
     * Create adjustment entry
     */
    public function createAdjustment(int $userId, float $amount, string $notes = ''): array
    {
        return $this->createFinanceLog([
            'user_id' => $userId,
            'transaction_type' => 'adjustment',
            'amount' => $amount,
            'notes' => $notes ?: 'Manual balance adjustment',
        ]);
    }

    /**
     * Create payment entry
     */
    public function createPayment(int $userId, float $amount, string $notes = ''): array
    {
        return $this->createFinanceLog([
            'user_id' => $userId,
            'transaction_type' => 'payment',
            'amount' => $amount,
            'notes' => $notes ?: 'Payment received',
        ]);
    }

    /**
     * Create commission entry
     */
    public function createCommission(int $userId, float $amount, string $notes = ''): array
    {
        return $this->createFinanceLog([
            'user_id' => $userId,
            'transaction_type' => 'commission',
            'amount' => $amount,
            'notes' => $notes ?: 'Commission earned',
        ]);
    }

    /**
     * Create withdrawal entry
     */
    public function createWithdrawal(int $userId, float $amount, string $notes = ''): array
    {
        return $this->createFinanceLog([
            'user_id' => $userId,
            'transaction_type' => 'withdrawal',
            'amount' => -$amount, // Negative amount for withdrawal
            'notes' => $notes ?: 'Withdrawal processed',
        ]);
    }

    /**
     * Validate finance log data
     */
    private function validateFinanceLogData(array $data): void
    {
        if (empty($data['user_id'])) {
            throw new \Exception('User ID is required');
        }

        if (empty($data['transaction_type'])) {
            throw new \Exception('Transaction type is required');
        }

        if (!in_array($data['transaction_type'], ['commission', 'withdrawal', 'payment', 'adjustment'])) {
            throw new \Exception('Invalid transaction type');
        }

        if (!isset($data['amount']) || !is_numeric($data['amount'])) {
            throw new \Exception('Amount must be a valid number');
        }

        $user = User::find($data['user_id']);
        if (!$user) {
            throw new \Exception('User not found');
        }
    }

    /**
     * Get period start date
     */
    private function getPeriodStartDate(string $period): string
    {
        switch ($period) {
            case 'week':
                return now()->subWeek()->toDateString();
            case 'month':
                return now()->subMonth()->toDateString();
            case 'quarter':
                return now()->subQuarter()->toDateString();
            case 'year':
                return now()->subYear()->toDateString();
            default:
                return now()->subMonth()->toDateString();
        }
    }

    /**
     * Get date format for grouping
     */
    private function getDateFormat(string $period): string
    {
        switch ($period) {
            case 'week':
                return 'Y-W';
            case 'month':
                return 'Y-m';
            case 'quarter':
                return 'Y-Q';
            case 'year':
                return 'Y';
            default:
                return 'Y-m';
        }
    }

    /**
     * Export finance logs to CSV
     */
    public function exportFinanceLogs(array $filters = []): array
    {
        try {
            $query = FinanceLog::with('user');

            if (isset($filters['user_id'])) {
                $query->where('user_id', $filters['user_id']);
            }

            if (isset($filters['transaction_type'])) {
                $query->where('transaction_type', $filters['transaction_type']);
            }

            if (isset($filters['start_date'])) {
                $query->where('created_at', '>=', $filters['start_date']);
            }

            if (isset($filters['end_date'])) {
                $query->where('created_at', '<=', $filters['end_date']);
            }

            $logs = $query->orderBy('created_at', 'desc')->get();

            return [
                'success' => true,
                'logs' => $logs,
                'count' => $logs->count()
            ];

        } catch (\Exception $e) {
            Log::error('Finance log export failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to export finance logs: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get finance log dashboard data
     */
    public function getFinanceLogDashboard(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        $todayLogs = FinanceLog::whereDate('created_at', $today)->get();
        $thisMonthLogs = FinanceLog::where('created_at', '>=', $thisMonth)->get();
        $lastMonthLogs = FinanceLog::whereBetween('created_at', [$lastMonth, $thisMonth])->get();

        return [
            'success' => true,
            'dashboard' => [
                'today' => [
                    'transactions' => $todayLogs->count(),
                    'total_amount' => $todayLogs->sum('amount'),
                    'commission_amount' => $todayLogs->where('transaction_type', 'commission')->sum('amount'),
                    'withdrawal_amount' => $todayLogs->where('transaction_type', 'withdrawal')->sum('amount'),
                ],
                'this_month' => [
                    'transactions' => $thisMonthLogs->count(),
                    'total_amount' => $thisMonthLogs->sum('amount'),
                    'commission_amount' => $thisMonthLogs->where('transaction_type', 'commission')->sum('amount'),
                    'withdrawal_amount' => $thisMonthLogs->where('transaction_type', 'withdrawal')->sum('amount'),
                ],
                'last_month' => [
                    'transactions' => $lastMonthLogs->count(),
                    'total_amount' => $lastMonthLogs->sum('amount'),
                    'commission_amount' => $lastMonthLogs->where('transaction_type', 'commission')->sum('amount'),
                    'withdrawal_amount' => $lastMonthLogs->where('transaction_type', 'withdrawal')->sum('amount'),
                ],
            ]
        ];
    }
}
