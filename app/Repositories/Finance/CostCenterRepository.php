<?php

namespace App\Repositories\Finance;

use App\Models\Finance\CostCenter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class CostCenterRepository
{
    protected $model;

    public function __construct(CostCenter $model)
    {
        $this->model = $model;
    }

    /**
     * Get all cost centers with pagination
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
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('code')->paginate($perPage);
    }

    /**
     * Get cost center by ID
     */
    public function getById(int $id): ?CostCenter
    {
        return $this->model->find($id);
    }

    /**
     * Create new cost center
     */
    public function create(array $data): CostCenter
    {
        return $this->model->create($data);
    }

    /**
     * Update cost center
     */
    public function update(int $id, array $data): bool
    {
        $costCenter = $this->model->find($id);
        if (!$costCenter) {
            return false;
        }

        return $costCenter->update($data);
    }

    /**
     * Delete cost center
     */
    public function delete(int $id): bool
    {
        $costCenter = $this->model->find($id);
        if (!$costCenter) {
            return false;
        }

        return $costCenter->delete();
    }

    /**
     * Get cost center by code
     */
    public function getByCode(string $code): ?CostCenter
    {
        return $this->model->where('code', strtoupper($code))->first();
    }

    /**
     * Get active cost centers
     */
    public function getActive(): Collection
    {
        return $this->model->where('is_active', true)
            ->orderBy('code')
            ->get();
    }

    /**
     * Get inactive cost centers
     */
    public function getInactive(): Collection
    {
        return $this->model->where('is_active', false)
            ->orderBy('code')
            ->get();
    }

    /**
     * Get cost center statistics
     */
    public function getStatistics(): array
    {
        $total = $this->model->count();
        $active = $this->model->where('is_active', true)->count();
        $inactive = $this->model->where('is_active', false)->count();

        return [
            'total_cost_centers' => $total,
            'active_cost_centers' => $active,
            'inactive_cost_centers' => $inactive,
            'activation_rate' => $total > 0 ? ($active / $total) * 100 : 0,
        ];
    }

    /**
     * Search cost centers
     */
    public function search(string $query, int $limit = 20): Collection
    {
        return $this->model->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('code', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->orderBy('code')
            ->limit($limit)
            ->get();
    }

    /**
     * Get cost centers for dropdown/select
     */
    public function getForSelect(): array
    {
        return $this->model->where('is_active', true)
            ->orderBy('code')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Get cost centers for dropdown/select by code
     */
    public function getForSelectByCode(): array
    {
        return $this->model->where('is_active', true)
            ->orderBy('code')
            ->pluck('name', 'code')
            ->toArray();
    }

    /**
     * Check if cost center code exists
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
     * Bulk update cost center status
     */
    public function bulkUpdateStatus(array $ids, bool $isActive): int
    {
        return $this->model->whereIn('id', $ids)->update(['is_active' => $isActive]);
    }

    /**
     * Get cost centers for export
     */
    public function getForExport(array $filters = []): Collection
    {
        $query = $this->model->newQuery();

        // Apply filters
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('code')->get();
    }

    /**
     * Get cost center trends
     */
    public function getTrends(string $period = 'month'): array
    {
        $startDate = $this->getPeriodStartDate($period);
        
        $costCenters = $this->model->where('created_at', '>=', $startDate)
            ->get()
            ->groupBy(function ($costCenter) use ($period) {
                return $costCenter->created_at->format($this->getDateFormat($period));
            });

        $trends = [];
        foreach ($costCenters as $date => $group) {
            $trends[] = [
                'date' => $date,
                'total_cost_centers' => $group->count(),
                'active_count' => $group->where('is_active', true)->count(),
                'inactive_count' => $group->where('is_active', false)->count(),
            ];
        }

        return $trends;
    }

    /**
     * Activate cost center
     */
    public function activate(int $id): bool
    {
        $costCenter = $this->model->find($id);
        if (!$costCenter) {
            return false;
        }

        return $costCenter->update(['is_active' => true]);
    }

    /**
     * Deactivate cost center
     */
    public function deactivate(int $id): bool
    {
        $costCenter = $this->model->find($id);
        if (!$costCenter) {
            return false;
        }

        return $costCenter->update(['is_active' => false]);
    }

    /**
     * Get cost centers by status
     */
    public function getByStatus(bool $isActive): Collection
    {
        return $this->model->where('is_active', $isActive)
            ->orderBy('code')
            ->get();
    }

    /**
     * Get most used cost centers
     */
    public function getMostUsed(int $limit = 10): Collection
    {
        // This would typically join with related tables to get usage count
        // For now, return active cost centers ordered by code
        return $this->model->where('is_active', true)
            ->orderBy('code')
            ->limit($limit)
            ->get();
    }

    /**
     * Import cost centers
     */
    public function import(array $costCentersData): array
    {
        $imported = 0;
        $errors = [];

        foreach ($costCentersData as $index => $data) {
            try {
                if (!$this->codeExists($data['code'])) {
                    $this->create([
                        'code' => strtoupper($data['code']),
                        'name' => $data['name'],
                        'description' => $data['description'] ?? '',
                        'is_active' => $data['is_active'] ?? true,
                    ]);
                    $imported++;
                } else {
                    $errors[] = "Row " . ($index + 1) . ": Cost center code '{$data['code']}' already exists";
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
     * Get cost center dashboard data
     */
    public function getDashboardData(): array
    {
        $total = $this->model->count();
        $active = $this->model->where('is_active', true)->count();
        $inactive = $this->model->where('is_active', false)->count();

        $recentlyCreated = $this->model->where('created_at', '>=', now()->subDays(30))->count();
        $recentlyUpdated = $this->model->where('updated_at', '>=', now()->subDays(30))->count();

        return [
            'total_cost_centers' => $total,
            'active_cost_centers' => $active,
            'inactive_cost_centers' => $inactive,
            'recently_created' => $recentlyCreated,
            'recently_updated' => $recentlyUpdated,
            'activation_rate' => $total > 0 ? ($active / $total) * 100 : 0,
        ];
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
     * Get cost center usage count (placeholder for future implementation)
     */
    public function getUsageCount(int $id): int
    {
        // This would typically count related records (invoices, expenses, etc.)
        // For now, return 0 as placeholder
        return 0;
    }

    /**
     * Get cost center hierarchy (placeholder for future implementation)
     */
    public function getHierarchy(): array
    {
        // This would typically return hierarchical structure
        // For now, return flat structure
        $costCenters = $this->getActive();
        
        return [
            'cost_centers' => $costCenters,
            'hierarchy' => [], // Would contain hierarchical structure
        ];
    }

    /**
     * Get cost center usage statistics
     */
    public function getUsageStatistics(int $id): array
    {
        $costCenter = $this->getById($id);
        
        if (!$costCenter) {
            return [];
        }

        // This would typically involve counting related records
        // For now, return basic information
        return [
            'cost_center' => $costCenter,
            'usage_count' => 0, // Would be calculated based on actual usage
            'last_used' => null, // Would be calculated based on actual usage
            'total_amount' => 0, // Would be calculated based on actual usage
        ];
    }

    /**
     * Get cost centers by created by user
     */
    public function getByCreatedBy(int $userId): Collection
    {
        return $this->model->where('created_by', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get cost centers by updated by user
     */
    public function getByUpdatedBy(int $userId): Collection
    {
        return $this->model->where('updated_by', $userId)
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * Get cost center activity log (placeholder for future implementation)
     */
    public function getActivityLog(int $id): array
    {
        // This would typically return activity log for the cost center
        // For now, return empty array as placeholder
        return [];
    }

    /**
     * Get cost center summary
     */
    public function getSummary(): array
    {
        $total = $this->model->count();
        $active = $this->model->where('is_active', true)->count();
        $inactive = $this->model->where('is_active', false)->count();

        $recentlyCreated = $this->model->where('created_at', '>=', now()->subDays(7))->count();
        $recentlyUpdated = $this->model->where('updated_at', '>=', now()->subDays(7))->count();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'recently_created' => $recentlyCreated,
            'recently_updated' => $recentlyUpdated,
            'activation_rate' => $total > 0 ? ($active / $total) * 100 : 0,
        ];
    }
}
