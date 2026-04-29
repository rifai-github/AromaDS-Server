<?php

namespace App\Repositories\Finance;

use App\Models\Finance\FinanceLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class FinanceLogRepository
{
    protected $model;

    public function __construct(FinanceLog $model)
    {
        $this->model = $model;
    }

    /**
     * Get all finance logs with pagination
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['user']);

        // Apply filters
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['transaction_type'])) {
            $query->where('transaction_type', $filters['transaction_type']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get finance log by ID
     */
    public function getById(int $id): ?FinanceLog
    {
        return $this->model->with(['user'])->find($id);
    }

    /**
     * Create new finance log
     */
    public function create(array $data): FinanceLog
    {
        return $this->model->create($data);
    }

    /**
     * Update finance log
     */
    public function update(int $id, array $data): bool
    {
        $financeLog = $this->model->find($id);
        if (!$financeLog) {
            return false;
        }

        return $financeLog->update($data);
    }

    /**
     * Delete finance log
     */
    public function delete(int $id): bool
    {
        $financeLog = $this->model->find($id);
        if (!$financeLog) {
            return false;
        }

        return $financeLog->delete();
    }

    /**
     * Get finance logs by user ID
     */
    public function getByUserId(int $userId, int $limit = 50): Collection
    {
        return $this->model->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get user's current balance
     */
    public function getUserBalance(int $userId): float
    {
        $lastLog = $this->model->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->first();
            
        return $lastLog ? $lastLog->balance : 0;
    }

    /**
     * Get user's balance history
     */
    public function getUserBalanceHistory(int $userId, string $period = 'month'): Collection
    {
        $startDate = $this->getPeriodStartDate($period);
        
        return $this->model->where('user_id', $userId)
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get finance logs by transaction type
     */
    public function getByTransactionType(string $transactionType): Collection
    {
        return $this->model->with(['user'])
            ->where('transaction_type', $transactionType)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get finance log statistics
     */
    public function getStatistics(): array
    {
        $total = $this->model->count();
        $commission = $this->model->where('transaction_type', 'commission')->count();
        $withdrawal = $this->model->where('transaction_type', 'withdrawal')->count();
        $payment = $this->model->where('transaction_type', 'payment')->count();
        $adjustment = $this->model->where('transaction_type', 'adjustment')->count();

        $totalAmount = $this->model->sum('amount');
        $commissionAmount = $this->model->where('transaction_type', 'commission')->sum('amount');
        $withdrawalAmount = $this->model->where('transaction_type', 'withdrawal')->sum('amount');
        $paymentAmount = $this->model->where('transaction_type', 'payment')->sum('amount');
        $adjustmentAmount = $this->model->where('transaction_type', 'adjustment')->sum('amount');

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
     * Get user finance log statistics
     */
    public function getUserStatistics(int $userId): array
    {
        $logs = $this->model->where('user_id', $userId)->get();

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
    public function getTrends(string $period = 'month'): array
    {
        $startDate = $this->getPeriodStartDate($period);
        
        $logs = $this->model->where('created_at', '>=', $startDate)
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

        return $trends;
    }

    /**
     * Get top users by balance
     */
    public function getTopUsersByBalance(int $limit = 10): Collection
    {
        $users = $this->model->selectRaw('user_id, MAX(balance) as current_balance')
            ->groupBy('user_id')
            ->orderBy('current_balance', 'desc')
            ->limit($limit)
            ->get();

        return $users->load('user');
    }

    /**
     * Get finance log summary by transaction type
     */
    public function getSummaryByType(string $period = 'month'): Collection
    {
        $startDate = $this->getPeriodStartDate($period);
        
        return $this->model->where('created_at', '>=', $startDate)
            ->selectRaw('transaction_type, COUNT(*) as count, SUM(amount) as total_amount, AVG(amount) as avg_amount')
            ->groupBy('transaction_type')
            ->get();
    }

    /**
     * Search finance logs
     */
    public function search(string $query, int $limit = 20): Collection
    {
        return $this->model->with(['user'])
            ->where(function ($q) use ($query) {
                $q->where('notes', 'like', "%{$query}%")
                  ->orWhereHas('user', function ($userQuery) use ($query) {
                      $userQuery->where('name', 'like', "%{$query}%")
                               ->orWhere('email', 'like', "%{$query}%");
                  });
            })
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get finance logs for export
     */
    public function getForExport(array $filters = []): Collection
    {
        $query = $this->model->with(['user']);

        // Apply filters
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

        return $query->orderBy('created_at', 'desc')->get();
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
     * Get finance log dashboard data
     */
    public function getDashboardData(): array
    {
        $today = now()->toDateString();
        $thisMonth = now()->startOfMonth()->toDateString();
        $lastMonth = now()->subMonth()->startOfMonth()->toDateString();

        $todayLogs = $this->model->whereDate('created_at', $today)->get();
        $thisMonthLogs = $this->model->where('created_at', '>=', $thisMonth)->get();
        $lastMonthLogs = $this->model->whereBetween('created_at', [$lastMonth, $thisMonth])->get();

        return [
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
        ];
    }

    /**
     * Get recent finance logs
     */
    public function getRecent(int $limit = 10): Collection
    {
        return $this->model->with(['user'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get finance logs by date range
     */
    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        return $this->model->with(['user'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get finance log summary by user
     */
    public function getSummaryByUser(int $limit = 10): Collection
    {
        return $this->model->with('user')
            ->selectRaw('user_id, COUNT(*) as transaction_count, SUM(amount) as total_amount, MAX(balance) as current_balance')
            ->groupBy('user_id')
            ->orderBy('total_amount', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get average transaction amount
     */
    public function getAverageTransactionAmount(): float
    {
        return $this->model->avg('amount') ?? 0;
    }

    /**
     * Get minimum transaction amount
     */
    public function getMinimumTransactionAmount(): float
    {
        return $this->model->min('amount') ?? 0;
    }

    /**
     * Get maximum transaction amount
     */
    public function getMaximumTransactionAmount(): float
    {
        return $this->model->max('amount') ?? 0;
    }

    /**
     * Get transaction amount distribution
     */
    public function getAmountDistribution(): array
    {
        $ranges = [
            '0-100' => $this->model->whereBetween('amount', [0, 100])->count(),
            '100-1000' => $this->model->whereBetween('amount', [100, 1000])->count(),
            '1000-5000' => $this->model->whereBetween('amount', [1000, 5000])->count(),
            '5000+' => $this->model->where('amount', '>', 5000)->count(),
        ];

        return $ranges;
    }

    /**
     * Get balance distribution
     */
    public function getBalanceDistribution(): array
    {
        $ranges = [
            '0-1000' => $this->model->whereBetween('balance', [0, 1000])->count(),
            '1000-5000' => $this->model->whereBetween('balance', [1000, 5000])->count(),
            '5000-10000' => $this->model->whereBetween('balance', [5000, 10000])->count(),
            '10000+' => $this->model->where('balance', '>', 10000)->count(),
        ];

        return $ranges;
    }

    /**
     * Get total balance across all users
     */
    public function getTotalBalance(): float
    {
        $latestBalances = $this->model->selectRaw('user_id, MAX(created_at) as latest_date')
            ->groupBy('user_id')
            ->get();

        $totalBalance = 0;
        foreach ($latestBalances as $balance) {
            $latestLog = $this->model->where('user_id', $balance->user_id)
                ->where('created_at', $balance->latest_date)
                ->first();
            
            if ($latestLog) {
                $totalBalance += $latestLog->balance;
            }
        }

        return $totalBalance;
    }
}
