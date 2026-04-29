<?php

namespace App\Services\Reports;

use App\Models\Report;
use App\Models\ReportHistory;
use App\Models\DataExport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ReportPerformanceService
{
    /**
     * Monitor report generation performance
     */
    public function monitorReportGeneration(int $reportId, callable $generationCallback): array
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        try {
            $result = $generationCallback();
            
            $endTime = microtime(true);
            $endMemory = memory_get_usage();
            
            $metrics = [
                'report_id' => $reportId,
                'execution_time' => ($endTime - $startTime) * 1000, // Convert to milliseconds
                'memory_usage' => $endMemory - $startMemory,
                'peak_memory' => memory_get_peak_usage(),
                'status' => 'success',
                'generated_at' => now()
            ];
            
            // Store performance metrics
            $this->storePerformanceMetrics($metrics);
            
            return [
                'success' => true,
                'result' => $result,
                'metrics' => $metrics
            ];
            
        } catch (\Exception $e) {
            $endTime = microtime(true);
            $endMemory = memory_get_usage();
            
            $metrics = [
                'report_id' => $reportId,
                'execution_time' => ($endTime - $startTime) * 1000,
                'memory_usage' => $endMemory - $startMemory,
                'peak_memory' => memory_get_peak_usage(),
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'generated_at' => now()
            ];
            
            $this->storePerformanceMetrics($metrics);
            
            Log::error("Report generation failed for report {$reportId}: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'metrics' => $metrics
            ];
        }
    }

    /**
     * Store performance metrics
     */
    private function storePerformanceMetrics(array $metrics): void
    {
        // Store in cache for quick access
        $cacheKey = 'report_performance:' . $metrics['report_id'] . ':' . now()->format('Y-m-d');
        Cache::put($cacheKey, $metrics, 3600); // Cache for 1 hour
        
        // Store in database for historical analysis
        ReportHistory::create([
            'user_id' => auth()->id(),
            'report_id' => $metrics['report_id'],
            'execution_time' => $metrics['execution_time'],
            'status' => $metrics['status'],
            'error_message' => $metrics['error_message'] ?? null,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id()
        ]);
    }

    /**
     * Get report performance statistics
     */
    public function getReportPerformanceStats(int $reportId = null, int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        $query = ReportHistory::where('created_at', '>=', $startDate);
        
        if ($reportId) {
            $query->where('report_id', $reportId);
        }
        
        $histories = $query->get();
        
        if ($histories->isEmpty()) {
            return [
                'total_reports' => 0,
                'successful_reports' => 0,
                'failed_reports' => 0,
                'average_execution_time' => 0,
                'fastest_execution' => 0,
                'slowest_execution' => 0,
                'success_rate' => 0,
                'performance_trend' => []
            ];
        }
        
        $successful = $histories->where('status', 'success');
        $failed = $histories->where('status', 'failed');
        
        return [
            'total_reports' => $histories->count(),
            'successful_reports' => $successful->count(),
            'failed_reports' => $failed->count(),
            'average_execution_time' => $successful->avg('execution_time'),
            'fastest_execution' => $successful->min('execution_time'),
            'slowest_execution' => $successful->max('execution_time'),
            'success_rate' => round(($successful->count() / $histories->count()) * 100, 2),
            'performance_trend' => $this->getPerformanceTrend($histories),
            'daily_performance' => $this->getDailyPerformance($histories)
        ];
    }

    /**
     * Get performance trend
     */
    private function getPerformanceTrend($histories): array
    {
        $trend = $histories->where('status', 'success')
                          ->groupBy(function ($history) {
                              return $history->created_at->format('Y-m-d');
                          })
                          ->map(function ($group) {
                              return [
                                  'date' => $group->first()->created_at->format('Y-m-d'),
                                  'count' => $group->count(),
                                  'avg_execution_time' => $group->avg('execution_time')
                              ];
                          })
                          ->values();
        
        return $trend->toArray();
    }

    /**
     * Get daily performance
     */
    private function getDailyPerformance($histories): array
    {
        return $histories->groupBy(function ($history) {
            return $history->created_at->format('Y-m-d');
        })->map(function ($group) {
            return [
                'date' => $group->first()->created_at->format('Y-m-d'),
                'total' => $group->count(),
                'successful' => $group->where('status', 'success')->count(),
                'failed' => $group->where('status', 'failed')->count(),
                'avg_execution_time' => $group->where('status', 'success')->avg('execution_time')
            ];
        })->values()->toArray();
    }

    /**
     * Get slow reports
     */
    public function getSlowReports(int $thresholdMs = 5000, int $limit = 10): array
    {
        $slowReports = ReportHistory::where('status', 'success')
                                  ->where('execution_time', '>', $thresholdMs)
                                  ->with('report')
                                  ->orderBy('execution_time', 'desc')
                                  ->limit($limit)
                                  ->get();
        
        return $slowReports->map(function ($history) {
            return [
                'report_id' => $history->report_id,
                'report_name' => $history->report->name ?? 'Unknown',
                'execution_time' => $history->execution_time,
                'generated_at' => $history->created_at,
                'user_id' => $history->user_id
            ];
        })->toArray();
    }

    /**
     * Get failed reports
     */
    public function getFailedReports(int $days = 7, int $limit = 10): array
    {
        $failedReports = ReportHistory::where('status', 'failed')
                                    ->where('created_at', '>=', now()->subDays($days))
                                    ->with('report')
                                    ->orderBy('created_at', 'desc')
                                    ->limit($limit)
                                    ->get();
        
        return $failedReports->map(function ($history) {
            return [
                'report_id' => $history->report_id,
                'report_name' => $history->report->name ?? 'Unknown',
                'error_message' => $history->error_message,
                'failed_at' => $history->created_at,
                'user_id' => $history->user_id
            ];
        })->toArray();
    }

    /**
     * Get export performance statistics
     */
    public function getExportPerformanceStats(int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        $exports = DataExport::where('created_at', '>=', $startDate)->get();
        
        if ($exports->isEmpty()) {
            return [
                'total_exports' => 0,
                'completed_exports' => 0,
                'failed_exports' => 0,
                'average_file_size' => 0,
                'total_file_size' => 0,
                'success_rate' => 0
            ];
        }
        
        $completed = $exports->where('status', 'completed');
        $failed = $exports->where('status', 'failed');
        
        return [
            'total_exports' => $exports->count(),
            'completed_exports' => $completed->count(),
            'failed_exports' => $failed->count(),
            'average_file_size' => $completed->avg('file_size'),
            'total_file_size' => $completed->sum('file_size'),
            'success_rate' => round(($completed->count() / $exports->count()) * 100, 2),
            'exports_by_format' => $exports->groupBy('export_format')->map->count(),
            'daily_exports' => $this->getDailyExportStats($exports)
        ];
    }

    /**
     * Get daily export statistics
     */
    private function getDailyExportStats($exports): array
    {
        return $exports->groupBy(function ($export) {
            return $export->created_at->format('Y-m-d');
        })->map(function ($group) {
            return [
                'date' => $group->first()->created_at->format('Y-m-d'),
                'total' => $group->count(),
                'completed' => $group->where('status', 'completed')->count(),
                'failed' => $group->where('status', 'failed')->count(),
                'total_file_size' => $group->where('status', 'completed')->sum('file_size')
            ];
        })->values()->toArray();
    }

    /**
     * Optimize report queries
     */
    public function optimizeReportQueries(): array
    {
        $reports = Report::where('is_active', true)->get();
        $optimizations = [];
        
        foreach ($reports as $report) {
            $queryOptimizer = new ReportQueryOptimizer();
            $optimization = $queryOptimizer->optimizeQuery($report->query);
            
            if (!empty($optimization['optimizations'])) {
                $optimizations[] = [
                    'report_id' => $report->id,
                    'report_name' => $report->name,
                    'optimizations' => $optimization['optimizations'],
                    'performance_improvement' => $optimization['performance_improvement']
                ];
            }
        }
        
        return $optimizations;
    }

    /**
     * Get system performance metrics
     */
    public function getSystemPerformanceMetrics(): array
    {
        return [
            'database_connections' => $this->getDatabaseConnectionCount(),
            'cache_hit_rate' => $this->getCacheHitRate(),
            'memory_usage' => $this->getMemoryUsage(),
            'disk_usage' => $this->getDiskUsage(),
            'active_users' => $this->getActiveUserCount(),
            'queue_size' => $this->getQueueSize()
        ];
    }

    /**
     * Get database connection count
     */
    private function getDatabaseConnectionCount(): int
    {
        try {
            $result = DB::select("SHOW STATUS LIKE 'Threads_connected'");
            return $result[0]->Value ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get cache hit rate
     */
    private function getCacheHitRate(): float
    {
        // This would depend on your cache implementation
        // For Redis, you could use INFO stats
        return 0.0;
    }

    /**
     * Get memory usage
     */
    private function getMemoryUsage(): array
    {
        return [
            'current' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => ini_get('memory_limit')
        ];
    }

    /**
     * Get disk usage
     */
    private function getDiskUsage(): array
    {
        $total = disk_total_space(storage_path());
        $free = disk_free_space(storage_path());
        $used = $total - $free;
        
        return [
            'total' => $total,
            'used' => $used,
            'free' => $free,
            'percentage' => round(($used / $total) * 100, 2)
        ];
    }

    /**
     * Get active user count
     */
    private function getActiveUserCount(): int
    {
        // This would depend on how you track active users
        // Could be based on recent activity, sessions, etc.
        return 0;
    }

    /**
     * Get queue size
     */
    private function getQueueSize(): int
    {
        // This would depend on your queue implementation
        return 0;
    }

    /**
     * Generate performance report
     */
    public function generatePerformanceReport(int $days = 30): array
    {
        return [
            'period' => [
                'start' => now()->subDays($days)->toDateString(),
                'end' => now()->toDateString(),
                'days' => $days
            ],
            'report_performance' => $this->getReportPerformanceStats(null, $days),
            'export_performance' => $this->getExportPerformanceStats($days),
            'system_metrics' => $this->getSystemPerformanceMetrics(),
            'slow_reports' => $this->getSlowReports(),
            'failed_reports' => $this->getFailedReports($days),
            'optimization_suggestions' => $this->optimizeReportQueries()
        ];
    }
}
