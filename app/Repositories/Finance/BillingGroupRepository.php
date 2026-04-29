<?php

namespace App\Repositories\Finance;

use App\Models\Finance\BillingGroup;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class BillingGroupRepository
{
    protected $model;

    public function __construct(BillingGroup $model)
    {
        $this->model = $model;
    }

    /**
     * Get all billing groups with pagination
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['contract']);

        // Apply filters
        if (isset($filters['contract_id'])) {
            $query->where('contract_id', $filters['contract_id']);
        }

        if (isset($filters['billing_frequency'])) {
            $query->where('billing_frequency', $filters['billing_frequency']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['date_from'])) {
            $query->where('billing_start_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('billing_start_date', '<=', $filters['date_to']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('billing_group_name', 'like', "%{$search}%")
                  ->orWhereHas('contract', function ($contractQuery) use ($search) {
                      $contractQuery->where('contract_number', 'like', "%{$search}%");
                  });
            });
        }

        return $query->orderBy('billing_start_date', 'desc')->paginate($perPage);
    }

    /**
     * Get billing group by ID
     */
    public function getById(int $id): ?BillingGroup
    {
        return $this->model->with(['contract'])->find($id);
    }

    /**
     * Create new billing group
     */
    public function create(array $data): BillingGroup
    {
        return $this->model->create($data);
    }

    /**
     * Update billing group
     */
    public function update(int $id, array $data): bool
    {
        $billingGroup = $this->model->find($id);
        if (!$billingGroup) {
            return false;
        }

        return $billingGroup->update($data);
    }

    /**
     * Delete billing group
     */
    public function delete(int $id): bool
    {
        $billingGroup = $this->model->find($id);
        if (!$billingGroup) {
            return false;
        }

        return $billingGroup->delete();
    }

    /**
     * Get billing groups by contract ID
     */
    public function getByContractId(int $contractId): Collection
    {
        return $this->model->where('contract_id', $contractId)
            ->orderBy('billing_start_date', 'desc')
            ->get();
    }

    /**
     * Get active billing groups
     */
    public function getActive(): Collection
    {
        return $this->model->with(['contract'])
            ->where('is_active', true)
            ->orderBy('billing_start_date', 'asc')
            ->get();
    }

    /**
     * Get inactive billing groups
     */
    public function getInactive(): Collection
    {
        return $this->model->with(['contract'])
            ->where('is_active', false)
            ->orderBy('billing_start_date', 'desc')
            ->get();
    }

    /**
     * Get billing groups by frequency
     */
    public function getByFrequency(string $frequency): Collection
    {
        return $this->model->with(['contract'])
            ->where('billing_frequency', $frequency)
            ->where('is_active', true)
            ->orderBy('billing_start_date', 'asc')
            ->get();
    }

    /**
     * Get billing group statistics
     */
    public function getStatistics(): array
    {
        $total = $this->model->count();
        $active = $this->model->where('is_active', true)->count();
        $inactive = $this->model->where('is_active', false)->count();

        $monthly = $this->model->where('billing_frequency', 'monthly')->count();
        $quarterly = $this->model->where('billing_frequency', 'quarterly')->count();
        $yearly = $this->model->where('billing_frequency', 'yearly')->count();
        $oneTime = $this->model->where('billing_frequency', 'one_time')->count();

        $totalAmount = $this->model->sum('billing_amount');
        $activeAmount = $this->model->where('is_active', true)->sum('billing_amount');

        return [
            'total_billing_groups' => $total,
            'active_billing_groups' => $active,
            'inactive_billing_groups' => $inactive,
            'monthly_billing_groups' => $monthly,
            'quarterly_billing_groups' => $quarterly,
            'yearly_billing_groups' => $yearly,
            'one_time_billing_groups' => $oneTime,
            'total_amount' => $totalAmount,
            'active_amount' => $activeAmount,
            'activation_rate' => $total > 0 ? ($active / $total) * 100 : 0,
        ];
    }

    /**
     * Get billing group invoices
     */
    public function getInvoices(int $billingGroupId): Collection
    {
        return Invoice::where('billing_group_id', $billingGroupId)
            ->orderBy('invoice_date', 'desc')
            ->get();
    }

    /**
     * Get billing group invoice statistics
     */
    public function getInvoiceStatistics(int $billingGroupId): array
    {
        $invoices = Invoice::where('billing_group_id', $billingGroupId)->get();
        
        $totalInvoices = $invoices->count();
        $totalAmount = $invoices->sum('total_amount');
        $paidAmount = $invoices->where('status', 'paid')->sum('total_amount');
        $pendingAmount = $invoices->whereIn('status', ['draft', 'sent', 'overdue'])->sum('total_amount');
        
        return [
            'total_invoices' => $totalInvoices,
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'pending_amount' => $pendingAmount,
            'collection_rate' => $totalAmount > 0 ? ($paidAmount / $totalAmount) * 100 : 0,
        ];
    }

    /**
     * Get upcoming billing dates
     */
    public function getUpcomingBillingDates(int $billingGroupId, int $months = 6): array
    {
        $billingGroup = $this->model->find($billingGroupId);
        
        if (!$billingGroup || !$billingGroup->is_active) {
            return [];
        }

        $dates = [];
        $currentDate = \Carbon\Carbon::parse($billingGroup->billing_start_date);
        $endDate = \Carbon\Carbon::now()->addMonths($months);
        
        if ($billingGroup->billing_end_date) {
            $endDate = min($endDate, \Carbon\Carbon::parse($billingGroup->billing_end_date));
        }

        while ($currentDate->lte($endDate)) {
            if ($currentDate->gte(\Carbon\Carbon::now())) {
                $dates[] = [
                    'date' => $currentDate->toDateString(),
                    'amount' => $billingGroup->billing_amount,
                    'frequency' => $billingGroup->billing_frequency,
                ];
            }
            
            $currentDate = $this->getNextBillingDate($currentDate, $billingGroup->billing_frequency);
        }

        return $dates;
    }

    /**
     * Get next billing date based on frequency
     */
    private function getNextBillingDate(\Carbon\Carbon $currentDate, string $frequency): \Carbon\Carbon
    {
        switch ($frequency) {
            case 'monthly':
                return $currentDate->addMonth();
            case 'quarterly':
                return $currentDate->addMonths(3);
            case 'yearly':
                return $currentDate->addYear();
            case 'one_time':
                return $currentDate->addYear(); // For one-time, just add a year to break the loop
            default:
                return $currentDate->addMonth();
        }
    }

    /**
     * Get billing group trends
     */
    public function getTrends(string $period = 'month'): array
    {
        $startDate = $this->getPeriodStartDate($period);
        
        $billingGroups = $this->model->where('created_at', '>=', $startDate)
            ->get()
            ->groupBy(function ($billingGroup) use ($period) {
                return $billingGroup->created_at->format($this->getDateFormat($period));
            });

        $trends = [];
        foreach ($billingGroups as $date => $group) {
            $trends[] = [
                'date' => $date,
                'total_billing_groups' => $group->count(),
                'total_amount' => $group->sum('billing_amount'),
                'active_count' => $group->where('is_active', true)->count(),
                'monthly_count' => $group->where('billing_frequency', 'monthly')->count(),
                'quarterly_count' => $group->where('billing_frequency', 'quarterly')->count(),
                'yearly_count' => $group->where('billing_frequency', 'yearly')->count(),
            ];
        }

        return $trends;
    }

    /**
     * Search billing groups
     */
    public function search(string $query, int $limit = 20): Collection
    {
        return $this->model->with(['contract'])
            ->where(function ($q) use ($query) {
                $q->where('billing_group_name', 'like', "%{$query}%")
                  ->orWhereHas('contract', function ($contractQuery) use ($query) {
                      $contractQuery->where('contract_number', 'like', "%{$query}%");
                  });
            })
            ->orderBy('billing_start_date', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get billing groups for export
     */
    public function getForExport(array $filters = []): Collection
    {
        $query = $this->model->with(['contract']);

        // Apply filters
        if (isset($filters['contract_id'])) {
            $query->where('contract_id', $filters['contract_id']);
        }

        if (isset($filters['billing_frequency'])) {
            $query->where('billing_frequency', $filters['billing_frequency']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['date_from'])) {
            $query->where('billing_start_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('billing_start_date', '<=', $filters['date_to']);
        }

        return $query->orderBy('billing_start_date', 'desc')->get();
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
     * Get billing groups for dropdown/select
     */
    public function getForSelect(): array
    {
        return $this->model->where('is_active', true)
            ->orderBy('billing_group_name')
            ->pluck('billing_group_name', 'id')
            ->toArray();
    }

    /**
     * Bulk update billing group status
     */
    public function bulkUpdateStatus(array $ids, bool $isActive): int
    {
        return $this->model->whereIn('id', $ids)->update(['is_active' => $isActive]);
    }

    /**
     * Get billing groups by date range
     */
    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        return $this->model->with(['contract'])
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('billing_start_date', [$startDate, $endDate])
                      ->orWhereBetween('billing_end_date', [$startDate, $endDate])
                      ->orWhere(function ($q) use ($startDate, $endDate) {
                          $q->where('billing_start_date', '<=', $startDate)
                            ->where('billing_end_date', '>=', $endDate);
                      });
            })
            ->orderBy('billing_start_date', 'asc')
            ->get();
    }
}
