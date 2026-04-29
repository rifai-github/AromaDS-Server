<?php

namespace App\Repositories\Finance;

use App\Models\Finance\PaymentMethod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PaymentMethodRepository
{
    protected $model;

    public function __construct(PaymentMethod $model)
    {
        $this->model = $model;
    }

    /**
     * Get all payment methods with pagination
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        // Apply filters
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * Get payment method by ID
     */
    public function getById(int $id): ?PaymentMethod
    {
        return $this->model->find($id);
    }

    /**
     * Create new payment method
     */
    public function create(array $data): PaymentMethod
    {
        return $this->model->create($data);
    }

    /**
     * Update payment method
     */
    public function update(int $id, array $data): bool
    {
        $paymentMethod = $this->model->find($id);
        if (!$paymentMethod) {
            return false;
        }

        return $paymentMethod->update($data);
    }

    /**
     * Delete payment method
     */
    public function delete(int $id): bool
    {
        $paymentMethod = $this->model->find($id);
        if (!$paymentMethod) {
            return false;
        }

        return $paymentMethod->delete();
    }

    /**
     * Get payment method by code
     */
    public function getByCode(string $code): ?PaymentMethod
    {
        return $this->model->where('code', strtoupper($code))->first();
    }

    /**
     * Get active payment methods
     */
    public function getActive(): Collection
    {
        return $this->model->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get inactive payment methods
     */
    public function getInactive(): Collection
    {
        return $this->model->where('is_active', false)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get payment method statistics
     */
    public function getStatistics(): array
    {
        $total = $this->model->count();
        $active = $this->model->where('is_active', true)->count();
        $inactive = $this->model->where('is_active', false)->count();

        return [
            'total_payment_methods' => $total,
            'active_payment_methods' => $active,
            'inactive_payment_methods' => $inactive,
            'activation_rate' => $total > 0 ? ($active / $total) * 100 : 0,
        ];
    }

    /**
     * Search payment methods
     */
    public function search(string $query, int $limit = 20): Collection
    {
        return $this->model->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('code', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * Get payment methods for dropdown/select
     */
    public function getForSelect(): array
    {
        return $this->model->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Get payment methods for dropdown/select by code
     */
    public function getForSelectByCode(): array
    {
        return $this->model->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'code')
            ->toArray();
    }

    /**
     * Check if payment method code exists
     */
    public function codeExists(string $code, int $excludeId = null): bool
    {
        $query = $this->model->where('code', strtoupper($code));
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * Bulk update payment method status
     */
    public function bulkUpdateStatus(array $ids, bool $isActive): int
    {
        return $this->model->whereIn('id', $ids)->update(['is_active' => $isActive]);
    }

    /**
     * Get payment methods for export
     */
    public function getForExport(array $filters = []): Collection
    {
        $query = $this->model->newQuery();

        // Apply filters
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Get payment method trends
     */
    public function getTrends(string $period = 'month'): array
    {
        $startDate = $this->getPeriodStartDate($period);
        
        $paymentMethods = $this->model->where('created_at', '>=', $startDate)
            ->get()
            ->groupBy(function ($paymentMethod) use ($period) {
                return $paymentMethod->created_at->format($this->getDateFormat($period));
            });

        $trends = [];
        foreach ($paymentMethods as $date => $group) {
            $trends[] = [
                'date' => $date,
                'total_payment_methods' => $group->count(),
                'active_count' => $group->where('is_active', true)->count(),
                'inactive_count' => $group->where('is_active', false)->count(),
            ];
        }

        return $trends;
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
     * Activate payment method
     */
    public function activate(int $id): bool
    {
        $paymentMethod = $this->model->find($id);
        if (!$paymentMethod) {
            return false;
        }

        return $paymentMethod->update(['is_active' => true]);
    }

    /**
     * Deactivate payment method
     */
    public function deactivate(int $id): bool
    {
        $paymentMethod = $this->model->find($id);
        if (!$paymentMethod) {
            return false;
        }

        return $paymentMethod->update(['is_active' => false]);
    }

    /**
     * Get payment methods by status
     */
    public function getByStatus(bool $isActive): Collection
    {
        return $this->model->where('is_active', $isActive)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get payment method usage count (placeholder for future implementation)
     */
    public function getUsageCount(int $id): int
    {
        // This would typically count related records (invoices, payments, etc.)
        // For now, return 0 as placeholder
        return 0;
    }

    /**
     * Get most used payment methods
     */
    public function getMostUsed(int $limit = 10): Collection
    {
        // This would typically join with related tables to get usage count
        // For now, return active payment methods ordered by name
        return $this->model->where('is_active', true)
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * Import payment methods
     */
    public function import(array $paymentMethodsData): array
    {
        $imported = 0;
        $errors = [];

        foreach ($paymentMethodsData as $index => $data) {
            try {
                if (!$this->codeExists($data['code'])) {
                    $this->create([
                        'name' => $data['name'],
                        'code' => strtoupper($data['code']),
                        'is_active' => $data['is_active'] ?? true,
                    ]);
                    $imported++;
                } else {
                    $errors[] = "Row " . ($index + 1) . ": Payment method code '{$data['code']}' already exists";
                }
            } catch (\Exception $e) {
                $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
            }
        }

        return [
            'imported_count' => $imported,
            'errors' => $errors,
        ];
    }

    /**
     * Get payment method dashboard data
     */
    public function getDashboardData(): array
    {
        $total = $this->model->count();
        $active = $this->model->where('is_active', true)->count();
        $inactive = $this->model->where('is_active', false)->count();

        $recentlyCreated = $this->model->where('created_at', '>=', now()->subDays(30))->count();
        $recentlyUpdated = $this->model->where('updated_at', '>=', now()->subDays(30))->count();

        return [
            'total_payment_methods' => $total,
            'active_payment_methods' => $active,
            'inactive_payment_methods' => $inactive,
            'recently_created' => $recentlyCreated,
            'recently_updated' => $recentlyUpdated,
            'activation_rate' => $total > 0 ? ($active / $total) * 100 : 0,
        ];
    }
}
