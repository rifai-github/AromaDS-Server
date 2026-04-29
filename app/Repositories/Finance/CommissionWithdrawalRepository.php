<?php

namespace App\Repositories\Finance;

use App\Models\Finance\CommissionWithdrawal;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class CommissionWithdrawalRepository
{
    protected $model;

    public function __construct(CommissionWithdrawal $model)
    {
        $this->model = $model;
    }

    /**
     * Get all commission withdrawals with pagination
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['user']);

        // Apply filters
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('requested_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('requested_date', '<=', $filters['date_to']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                             ->orWhere('email', 'like', "%{$search}%");
                });
            });
        }

        return $query->orderBy('requested_date', 'desc')->paginate($perPage);
    }

    /**
     * Get commission withdrawal by ID
     */
    public function getById(int $id): ?CommissionWithdrawal
    {
        return $this->model->with(['user'])->find($id);
    }

    /**
     * Create new commission withdrawal
     */
    public function create(array $data): CommissionWithdrawal
    {
        return $this->model->create($data);
    }

    /**
     * Update commission withdrawal
     */
    public function update(int $id, array $data): bool
    {
        $withdrawal = $this->model->find($id);
        if (!$withdrawal) {
            return false;
        }

        return $withdrawal->update($data);
    }

    /**
     * Delete commission withdrawal
     */
    public function delete(int $id): bool
    {
        $withdrawal = $this->model->find($id);
        if (!$withdrawal) {
            return false;
        }

        return $withdrawal->delete();
    }

    /**
     * Get commission withdrawals by user ID
     */
    public function getByUserId(int $userId, array $filters = []): Collection
    {
        $query = $this->model->where('user_id', $userId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('requested_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('requested_date', '<=', $filters['date_to']);
        }

        return $query->orderBy('requested_date', 'desc')->get();
    }

    /**
     * Get pending commission withdrawals
     */
    public function getPending(): Collection
    {
        return $this->model->with(['user'])
            ->where('status', 'pending')
            ->orderBy('requested_date', 'asc')
            ->get();
    }

    /**
     * Get approved commission withdrawals
     */
    public function getApproved(): Collection
    {
        return $this->model->with(['user'])
            ->where('status', 'approved')
            ->orderBy('approved_date', 'desc')
            ->get();
    }

    /**
     * Get rejected commission withdrawals
     */
    public function getRejected(): Collection
    {
        return $this->model->with(['user'])
            ->where('status', 'rejected')
            ->orderBy('rejected_date', 'desc')
            ->get();
    }

    /**
     * Get completed commission withdrawals
     */
    public function getCompleted(): Collection
    {
        return $this->model->with(['user'])
            ->where('status', 'completed')
            ->orderBy('processed_date', 'desc')
            ->get();
    }

    /**
     * Get commission withdrawal statistics
     */
    public function getStatistics(): array
    {
        $total = $this->model->count();
        $pending = $this->model->where('status', 'pending')->count();
        $approved = $this->model->where('status', 'approved')->count();
        $rejected = $this->model->where('status', 'rejected')->count();
        $completed = $this->model->where('status', 'completed')->count();

        $totalAmount = $this->model->sum('amount');
        $pendingAmount = $this->model->where('status', 'pending')->sum('amount');
        $approvedAmount = $this->model->where('status', 'approved')->sum('amount');
        $completedAmount = $this->model->where('status', 'completed')->sum('amount');

        return [
            'total_withdrawals' => $total,
            'pending_withdrawals' => $pending,
            'approved_withdrawals' => $approved,
            'rejected_withdrawals' => $rejected,
            'completed_withdrawals' => $completed,
            'total_amount' => $totalAmount,
            'pending_amount' => $pendingAmount,
            'approved_amount' => $approvedAmount,
            'completed_amount' => $completedAmount,
            'approval_rate' => $total > 0 ? (($approved + $completed) / $total) * 100 : 0,
        ];
    }

    /**
     * Get user withdrawal statistics
     */
    public function getUserStatistics(int $userId): array
    {
        $withdrawals = $this->model->where('user_id', $userId)->get();

        $total = $withdrawals->count();
        $pending = $withdrawals->where('status', 'pending')->count();
        $approved = $withdrawals->where('status', 'approved')->count();
        $rejected = $withdrawals->where('status', 'rejected')->count();
        $completed = $withdrawals->where('status', 'completed')->count();

        $totalAmount = $withdrawals->sum('amount');
        $completedAmount = $withdrawals->where('status', 'completed')->sum('amount');

        return [
            'user_id' => $userId,
            'total_withdrawals' => $total,
            'pending_withdrawals' => $pending,
            'approved_withdrawals' => $approved,
            'rejected_withdrawals' => $rejected,
            'completed_withdrawals' => $completed,
            'total_amount' => $totalAmount,
            'completed_amount' => $completedAmount,
            'success_rate' => $total > 0 ? ($completed / $total) * 100 : 0,
        ];
    }

    /**
     * Get commission withdrawal trends
     */
    public function getTrends(string $period = 'month'): array
    {
        $startDate = $this->getPeriodStartDate($period);
        
        $withdrawals = $this->model->where('created_at', '>=', $startDate)
            ->get()
            ->groupBy(function ($withdrawal) use ($period) {
                return $withdrawal->created_at->format($this->getDateFormat($period));
            });

        $trends = [];
        foreach ($withdrawals as $date => $group) {
            $trends[] = [
                'date' => $date,
                'total_withdrawals' => $group->count(),
                'total_amount' => $group->sum('amount'),
                'approved_count' => $group->where('status', 'approved')->count(),
                'completed_count' => $group->where('status', 'completed')->count(),
                'rejected_count' => $group->where('status', 'rejected')->count(),
            ];
        }

        return $trends;
    }

    /**
     * Get top users by withdrawal amount
     */
    public function getTopUsersByWithdrawal(int $limit = 10): Collection
    {
        return $this->model->with('user')
            ->selectRaw('user_id, COUNT(*) as withdrawal_count, SUM(amount) as total_amount')
            ->where('status', 'completed')
            ->groupBy('user_id')
            ->orderBy('total_amount', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get withdrawal requests for approval
     */
    public function getForApproval(): Collection
    {
        return $this->model->with(['user'])
            ->where('status', 'pending')
            ->orderBy('requested_date', 'asc')
            ->get();
    }

    /**
     * Bulk update withdrawal status
     */
    public function bulkUpdateStatus(array $ids, string $status): int
    {
        return $this->model->whereIn('id', $ids)->update(['status' => $status]);
    }

    /**
     * Search commission withdrawals
     */
    public function search(string $query, int $limit = 20): Collection
    {
        return $this->model->with(['user'])
            ->where(function ($q) use ($query) {
                $q->whereHas('user', function ($userQuery) use ($query) {
                    $userQuery->where('name', 'like', "%{$query}%")
                             ->orWhere('email', 'like', "%{$query}%");
                });
            })
            ->orderBy('requested_date', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get commission withdrawals for export
     */
    public function getForExport(array $filters = []): Collection
    {
        $query = $this->model->with(['user']);

        // Apply filters
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('requested_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('requested_date', '<=', $filters['date_to']);
        }

        return $query->orderBy('requested_date', 'desc')->get();
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
     * Get commission withdrawal dashboard data
     */
    public function getDashboardData(): array
    {
        $today = now()->toDateString();
        $thisMonth = now()->startOfMonth()->toDateString();
        $lastMonth = now()->subMonth()->startOfMonth()->toDateString();

        $todayWithdrawals = $this->model->whereDate('requested_date', $today)->get();
        $thisMonthWithdrawals = $this->model->where('requested_date', '>=', $thisMonth)->get();
        $lastMonthWithdrawals = $this->model->whereBetween('requested_date', [$lastMonth, $thisMonth])->get();

        return [
            'today' => [
                'withdrawals' => $todayWithdrawals->count(),
                'total_amount' => $todayWithdrawals->sum('amount'),
                'pending_count' => $todayWithdrawals->where('status', 'pending')->count(),
                'approved_count' => $todayWithdrawals->where('status', 'approved')->count(),
            ],
            'this_month' => [
                'withdrawals' => $thisMonthWithdrawals->count(),
                'total_amount' => $thisMonthWithdrawals->sum('amount'),
                'pending_count' => $thisMonthWithdrawals->where('status', 'pending')->count(),
                'approved_count' => $thisMonthWithdrawals->where('status', 'approved')->count(),
            ],
            'last_month' => [
                'withdrawals' => $lastMonthWithdrawals->count(),
                'total_amount' => $lastMonthWithdrawals->sum('amount'),
                'pending_count' => $lastMonthWithdrawals->where('status', 'pending')->count(),
                'approved_count' => $lastMonthWithdrawals->where('status', 'approved')->count(),
            ],
        ];
    }

    /**
     * Get recent withdrawals
     */
    public function getRecent(int $limit = 10): Collection
    {
        return $this->model->with(['user'])
            ->orderBy('requested_date', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get withdrawals by status
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->with(['user'])
            ->where('status', $status)
            ->orderBy('requested_date', 'desc')
            ->get();
    }

    /**
     * Get withdrawals by date range
     */
    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        return $this->model->with(['user'])
            ->whereBetween('requested_date', [$startDate, $endDate])
            ->orderBy('requested_date', 'desc')
            ->get();
    }

    /**
     * Get withdrawal summary by status
     */
    public function getSummaryByStatus(): array
    {
        return $this->model->selectRaw('status, COUNT(*) as count, SUM(amount) as total_amount')
            ->groupBy('status')
            ->get()
            ->keyBy('status')
            ->toArray();
    }

    /**
     * Get withdrawal summary by user
     */
    public function getSummaryByUser(int $limit = 10): Collection
    {
        return $this->model->with('user')
            ->selectRaw('user_id, COUNT(*) as withdrawal_count, SUM(amount) as total_amount')
            ->groupBy('user_id')
            ->orderBy('total_amount', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get average withdrawal amount
     */
    public function getAverageWithdrawalAmount(): float
    {
        return $this->model->avg('amount') ?? 0;
    }

    /**
     * Get minimum withdrawal amount
     */
    public function getMinimumWithdrawalAmount(): float
    {
        return $this->model->min('amount') ?? 0;
    }

    /**
     * Get maximum withdrawal amount
     */
    public function getMaximumWithdrawalAmount(): float
    {
        return $this->model->max('amount') ?? 0;
    }

    /**
     * Get withdrawal amount distribution
     */
    public function getAmountDistribution(): array
    {
        $ranges = [
            '0-1000' => $this->model->whereBetween('amount', [0, 1000])->count(),
            '1000-5000' => $this->model->whereBetween('amount', [1000, 5000])->count(),
            '5000-10000' => $this->model->whereBetween('amount', [5000, 10000])->count(),
            '10000+' => $this->model->where('amount', '>', 10000)->count(),
        ];

        return $ranges;
    }
}
