<?php

namespace App\Repositories\Reports;

use App\Models\DataExport;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class DataExportRepository
{
    /**
     * Get all data exports with pagination
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = DataExport::with(['template', 'creator', 'updater']);

        // Apply filters
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['export_format'])) {
            $query->where('export_format', $filters['export_format']);
        }

        if (isset($filters['data_source'])) {
            $query->where('data_source', $filters['data_source']);
        }

        if (isset($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')
                    ->paginate($perPage);
    }

    /**
     * Get data export by ID
     */
    public function getById(int $id): ?DataExport
    {
        return DataExport::with(['template', 'creator', 'updater'])
                        ->find($id);
    }

    /**
     * Get exports by status
     */
    public function getByStatus(string $status): Collection
    {
        return DataExport::with(['template'])
                        ->where('status', $status)
                        ->orderBy('created_at', 'desc')
                        ->get();
    }

    /**
     * Get scheduled exports ready for execution
     */
    public function getScheduledExports(): Collection
    {
        return DataExport::where('status', 'scheduled')
                        ->where('scheduled_at', '<=', now())
                        ->get();
    }

    /**
     * Get exports by user
     */
    public function getByUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return DataExport::with(['template'])
                        ->where('created_by', $userId)
                        ->orderBy('created_at', 'desc')
                        ->paginate($perPage);
    }

    /**
     * Get exports by template
     */
    public function getByTemplate(int $templateId): Collection
    {
        return DataExport::where('template_id', $templateId)
                        ->orderBy('created_at', 'desc')
                        ->get();
    }

    /**
     * Create data export
     */
    public function create(array $data): DataExport
    {
        return DataExport::create($data);
    }

    /**
     * Update data export
     */
    public function update(DataExport $export, array $data): bool
    {
        return $export->update($data);
    }

    /**
     * Delete data export
     */
    public function delete(DataExport $export): bool
    {
        return $export->delete();
    }

    /**
     * Get export statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_exports' => DataExport::count(),
            'completed_exports' => DataExport::where('status', 'completed')->count(),
            'failed_exports' => DataExport::where('status', 'failed')->count(),
            'running_exports' => DataExport::where('status', 'running')->count(),
            'scheduled_exports' => DataExport::where('status', 'scheduled')->count(),
            'pending_exports' => DataExport::where('status', 'pending')->count(),
            'exports_by_format' => DataExport::selectRaw('export_format, COUNT(*) as count')
                                            ->groupBy('export_format')
                                            ->pluck('count', 'export_format')
                                            ->toArray(),
            'exports_by_status' => DataExport::selectRaw('status, COUNT(*) as count')
                                            ->groupBy('status')
                                            ->pluck('count', 'status')
                                            ->toArray(),
            'total_file_size' => DataExport::where('status', 'completed')
                                          ->sum('file_size'),
            'average_execution_time' => DataExport::where('status', 'completed')
                                                 ->avg('execution_time'),
        ];
    }

    /**
     * Get recent exports
     */
    public function getRecent(int $limit = 10): Collection
    {
        return DataExport::with(['template'])
                        ->orderBy('created_at', 'desc')
                        ->limit($limit)
                        ->get();
    }

    /**
     * Get exports by date range
     */
    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        return DataExport::with(['template'])
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->orderBy('created_at', 'desc')
                        ->get();
    }

    /**
     * Search exports
     */
    public function search(string $search, int $perPage = 15): LengthAwarePaginator
    {
        return DataExport::with(['template'])
                        ->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                              ->orWhere('description', 'like', "%{$search}%")
                              ->orWhere('data_source', 'like', "%{$search}%");
                        })
                        ->orderBy('created_at', 'desc')
                        ->paginate($perPage);
    }

    /**
     * Get exports with files
     */
    public function getWithFiles(): Collection
    {
        return DataExport::where('status', 'completed')
                        ->whereNotNull('file_path')
                        ->orderBy('created_at', 'desc')
                        ->get();
    }

    /**
     * Get exports without files
     */
    public function getWithoutFiles(): Collection
    {
        return DataExport::where('status', 'completed')
                        ->whereNull('file_path')
                        ->orderBy('created_at', 'desc')
                        ->get();
    }

    /**
     * Get failed exports
     */
    public function getFailedExports(): Collection
    {
        return DataExport::where('status', 'failed')
                        ->whereNotNull('error_message')
                        ->orderBy('created_at', 'desc')
                        ->get();
    }

    /**
     * Get exports by data source
     */
    public function getByDataSource(string $dataSource): Collection
    {
        return DataExport::where('data_source', $dataSource)
                        ->orderBy('created_at', 'desc')
                        ->get();
    }

    /**
     * Get exports by format
     */
    public function getByFormat(string $format): Collection
    {
        return DataExport::where('export_format', $format)
                        ->orderBy('created_at', 'desc')
                        ->get();
    }

    /**
     * Get export trends
     */
    public function getExportTrends(int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        $trends = DataExport::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                           ->where('created_at', '>=', $startDate)
                           ->groupBy('date')
                           ->orderBy('date')
                           ->get();

        return [
            'labels' => $trends->pluck('date')->toArray(),
            'data' => $trends->pluck('count')->toArray()
        ];
    }

    /**
     * Get export performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        $completedExports = DataExport::where('status', 'completed');
        
        return [
            'total_completed' => $completedExports->count(),
            'average_execution_time' => $completedExports->avg('execution_time'),
            'fastest_export' => $completedExports->min('execution_time'),
            'slowest_export' => $completedExports->max('execution_time'),
            'total_file_size' => $completedExports->sum('file_size'),
            'average_file_size' => $completedExports->avg('file_size'),
            'success_rate' => $this->getSuccessRate(),
        ];
    }

    /**
     * Get success rate
     */
    private function getSuccessRate(): float
    {
        $total = DataExport::count();
        $completed = DataExport::where('status', 'completed')->count();
        
        return $total > 0 ? round(($completed / $total) * 100, 2) : 0;
    }

    /**
     * Clean up old exports
     */
    public function cleanupOldExports(int $daysOld = 30): int
    {
        $cutoffDate = now()->subDays($daysOld);
        
        return DataExport::where('created_at', '<', $cutoffDate)
                        ->where('status', 'completed')
                        ->delete();
    }

    /**
     * Get exports by user activity
     */
    public function getByUserActivity(int $userId, int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        $exports = DataExport::where('created_by', $userId)
                            ->where('created_at', '>=', $startDate)
                            ->get();

        return [
            'total_exports' => $exports->count(),
            'completed_exports' => $exports->where('status', 'completed')->count(),
            'failed_exports' => $exports->where('status', 'failed')->count(),
            'total_file_size' => $exports->where('status', 'completed')->sum('file_size'),
            'most_used_format' => $exports->groupBy('export_format')
                                         ->map->count()
                                         ->sortDesc()
                                         ->keys()
                                         ->first(),
        ];
    }
}
