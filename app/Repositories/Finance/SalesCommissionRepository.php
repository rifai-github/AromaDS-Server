<?php

namespace App\Repositories\Finance;

use App\Models\Finance\SalesCommission;
use App\Models\Finance\CommissionCondition;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SalesCommissionRepository
{
    protected $model;

    public function __construct(SalesCommission $model)
    {
        $this->model = $model;
    }

    /**
     * Get all sales commissions with pagination
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['user', 'contract']);

        // Apply filters
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['contract_id'])) {
            $query->where('contract_id', $filters['contract_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['commission_type'])) {
            $query->where('commission_type', $filters['commission_type']);
        }

        if (isset($filters['date_from'])) {
            $query->where('calculated_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('calculated_date', '<=', $filters['date_to']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                             ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhereHas('contract', function ($contractQuery) use ($search) {
                    $contractQuery->where('contract_number', 'like', "%{$search}%");
                });
            });
        }

        return $query->orderBy('calculated_date', 'desc')->paginate($perPage);
    }

    /**
     * Get sales commission by ID
     */
    public function getById(int $id): ?SalesCommission
    {
        return $this->model->with(['user', 'contract', 'commissionConditions'])->find($id);
    }

    /**
     * Create new sales commission
     */
    public function create(array $data): SalesCommission
    {
        return $this->model->create($data);
    }

    /**
     * Update sales commission
     */
    public function update(int $id, array $data): bool
    {
        $commission = $this->model->find($id);
        if (!$commission) {
            return false;
        }

        return $commission->update($data);
    }

    /**
     * Delete sales commission
     */
    public function delete(int $id): bool
    {
        $commission = $this->model->find($id);
        if (!$commission) {
            return false;
        }

        return $commission->delete();
    }

    /**
     * Get sales commissions by user ID
     */
    public function getByUserId(int $userId, array $filters = []): Collection
    {
        $query = $this->model->with(['contract'])
            ->where('user_id', $userId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('calculated_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('calculated_date', '<=', $filters['date_to']);
        }

        return $query->orderBy('calculated_date', 'desc')->get();
    }

    /**
     * Get sales commissions by contract ID
     */
    public function getByContractId(int $contractId): Collection
    {
        return $this->model->with(['user'])
            ->where('contract_id', $contractId)
            ->orderBy('calculated_date', 'desc')
            ->get();
    }

    /**
     * Get pending sales commissions
     */
    public function getPending(): Collection
    {
        return $this->model->with(['user', 'contract'])
            ->where('status', 'pending')
            ->orderBy('calculated_date', 'asc')
            ->get();
    }

    /**
     * Get approved sales commissions
     */
    public function getApproved(): Collection
    {
        return $this->model->with(['user', 'contract'])
            ->where('status', 'approved')
            ->orderBy('calculated_date', 'desc')
            ->get();
    }

    /**
     * Get rejected sales commissions
     */
    public function getRejected(): Collection
    {
        return $this->model->with(['user', 'contract'])
            ->where('status', 'rejected')
            ->orderBy('calculated_date', 'desc')
            ->get();
    }

    /**
     * Get sales commission statistics
     */
    public function getStatistics(): array
    {
        $total = $this->model->count();
        $pending = $this->model->where('status', 'pending')->count();
        $approved = $this->model->where('status', 'approved')->count();
        $rejected = $this->model->where('status', 'rejected')->count();

        $totalAmount = $this->model->sum('amount');
        $pendingAmount = $this->model->where('status', 'pending')->sum('amount');
        $approvedAmount = $this->model->where('status', 'approved')->sum('amount');
        $rejectedAmount = $this->model->where('status', 'rejected')->sum('amount');

        return [
            'total_commissions' => $total,
            'pending_commissions' => $pending,
            'approved_commissions' => $approved,
            'rejected_commissions' => $rejected,
            'total_amount' => $totalAmount,
            'pending_amount' => $pendingAmount,
            'approved_amount' => $approvedAmount,
            'rejected_amount' => $rejectedAmount,
            'approval_rate' => $total > 0 ? ($approved / $total) * 100 : 0,
        ];
    }

    /**
     * Get user commission statistics
     */
    public function getUserStatistics(int $userId): array
    {
        $commissions = $this->model->where('user_id', $userId)->get();

        $total = $commissions->count();
        $pending = $commissions->where('status', 'pending')->count();
        $approved = $commissions->where('status', 'approved')->count();
        $rejected = $commissions->where('status', 'rejected')->count();

        $totalAmount = $commissions->sum('amount');
        $pendingAmount = $commissions->where('status', 'pending')->sum('amount');
        $approvedAmount = $commissions->where('status', 'approved')->sum('amount');
        $rejectedAmount = $commissions->where('status', 'rejected')->sum('amount');

        return [
            'user_id' => $userId,
            'total_commissions' => $total,
            'pending_commissions' => $pending,
            'approved_commissions' => $approved,
            'rejected_commissions' => $rejected,
            'total_amount' => $totalAmount,
            'pending_amount' => $pendingAmount,
            'approved_amount' => $approvedAmount,
            'rejected_amount' => $rejectedAmount,
            'approval_rate' => $total > 0 ? ($approved / $total) * 100 : 0,
        ];
    }

    /**
     * Get commission trends
     */
    public function getTrends(string $period = 'month'): array
    {
        $startDate = $this->getPeriodStartDate($period);
        
        $commissions = $this->model->where('calculated_date', '>=', $startDate)
            ->get()
            ->groupBy(function ($commission) use ($period) {
                return $commission->calculated_date->format($this->getDateFormat($period));
            });

        $trends = [];
        foreach ($commissions as $date => $group) {
            $trends[] = [
                'date' => $date,
                'total_commissions' => $group->count(),
                'total_amount' => $group->sum('amount'),
                'approved_count' => $group->where('status', 'approved')->count(),
                'pending_count' => $group->where('status', 'pending')->count(),
                'rejected_count' => $group->where('status', 'rejected')->count(),
            ];
        }

        return $trends;
    }

    /**
     * Get top users by commission
     */
    public function getTopUsersByCommission(int $limit = 10): Collection
    {
        return $this->model->with('user')
            ->selectRaw('user_id, COUNT(*) as commission_count, SUM(amount) as total_amount')
            ->where('status', 'approved')
            ->groupBy('user_id')
            ->orderBy('total_amount', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Bulk update commission status
     */
    public function bulkUpdateStatus(array $ids, string $status): int
    {
        return $this->model->whereIn('id', $ids)->update(['status' => $status]);
    }

    /**
     * Get commission conditions for a commission
     */
    public function getCommissionConditions(int $commissionId): Collection
    {
        return CommissionCondition::where('commission_id', $commissionId)->get();
    }

    /**
     * Create commission condition
     */
    public function createCommissionCondition(array $data): CommissionCondition
    {
        return CommissionCondition::create($data);
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
     * Search sales commissions
     */
    public function search(string $query, int $limit = 20): Collection
    {
        return $this->model->with(['user', 'contract'])
            ->where(function ($q) use ($query) {
                $q->whereHas('user', function ($userQuery) use ($query) {
                    $userQuery->where('name', 'like', "%{$query}%")
                             ->orWhere('email', 'like', "%{$query}%");
                })
                ->orWhereHas('contract', function ($contractQuery) use ($query) {
                    $contractQuery->where('contract_number', 'like', "%{$query}%");
                });
            })
            ->orderBy('calculated_date', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get sales commissions for export
     */
    public function getForExport(array $filters = []): Collection
    {
        $query = $this->model->with(['user', 'contract']);

        // Apply filters
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['contract_id'])) {
            $query->where('contract_id', $filters['contract_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('calculated_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('calculated_date', '<=', $filters['date_to']);
        }

        return $query->orderBy('calculated_date', 'desc')->get();
    }
}
