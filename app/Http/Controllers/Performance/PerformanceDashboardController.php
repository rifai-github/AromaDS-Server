<?php

namespace App\Http\Controllers\Performance;

use App\Http\Controllers\Controller;
use App\Services\Performance\DatabaseOptimizationService;
use App\Services\Performance\CacheOptimizationService;
use App\Services\Performance\ApiOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class PerformanceDashboardController extends Controller
{
    protected $databaseOptimizationService;
    protected $cacheOptimizationService;
    protected $apiOptimizationService;

    public function __construct(
        DatabaseOptimizationService $databaseOptimizationService,
        CacheOptimizationService $cacheOptimizationService,
        ApiOptimizationService $apiOptimizationService
    ) {
        $this->databaseOptimizationService = $databaseOptimizationService;
        $this->cacheOptimizationService = $cacheOptimizationService;
        $this->apiOptimizationService = $apiOptimizationService;
    }

    /**
     * Display performance dashboard
     */
    public function index()
    {
        $performanceData = $this->getPerformanceData();
        
        return view('performance.dashboard', compact('performanceData'));
    }

    /**
     * Get API performance data
     */
    public function getApiPerformance(Request $request)
    {
        $timeRange = $request->get('time_range', '1h');
        $performanceData = $this->getApiPerformanceData($timeRange);
        
        return response()->json($performanceData);
    }

    /**
     * Get database performance data
     */
    public function getDatabasePerformance(Request $request)
    {
        $performanceData = $this->databaseOptimizationService->getHealthReport();
        
        return response()->json($performanceData);
    }

    /**
     * Get cache performance data
     */
    public function getCachePerformance(Request $request)
    {
        $performanceData = $this->cacheOptimizationService->getHealthReport();
        
        return response()->json($performanceData);
    }

    /**
     * Get system performance overview
     */
    public function getSystemOverview(Request $request)
    {
        $overview = [
            'timestamp' => now()->toISOString(),
            'database' => $this->databaseOptimizationService->getPerformanceMetrics(),
            'cache' => $this->cacheOptimizationService->getCacheStatistics(),
            'api' => $this->apiOptimizationService->getApiPerformanceMetrics(),
            'system' => $this->getSystemMetrics(),
        ];
        
        return response()->json($overview);
    }

    /**
     * Run performance optimization
     */
    public function runOptimization(Request $request)
    {
        $optimizationType = $request->get('type', 'all');
        $results = [];
        
        try {
            switch ($optimizationType) {
                case 'database':
                    $results = $this->databaseOptimizationService->optimizeTables();
                    break;
                case 'cache':
                    $results = $this->cacheOptimizationService->optimizeCacheConfiguration();
                    break;
                case 'api':
                    $results = $this->apiOptimizationService->optimizeApiPerformance();
                    break;
                case 'all':
                default:
                    $results = [
                        'database' => $this->databaseOptimizationService->optimizeTables(),
                        'cache' => $this->cacheOptimizationService->optimizeCacheConfiguration(),
                        'api' => $this->apiOptimizationService->optimizeApiPerformance(),
                    ];
                    break;
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Optimization completed successfully',
                'results' => $results
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Optimization failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get performance alerts
     */
    public function getPerformanceAlerts(Request $request)
    {
        $alerts = $this->getPerformanceAlertsData();
        
        return response()->json($alerts);
    }

    /**
     * Get performance trends
     */
    public function getPerformanceTrends(Request $request)
    {
        $timeRange = $request->get('time_range', '24h');
        $trends = $this->getPerformanceTrendsData($timeRange);
        
        return response()->json($trends);
    }

    /**
     * Get performance data for dashboard
     */
    private function getPerformanceData(): array
    {
        return [
            'api_performance' => $this->apiOptimizationService->getApiPerformanceMetrics(),
            'database_performance' => $this->databaseOptimizationService->getPerformanceMetrics(),
            'cache_performance' => $this->cacheOptimizationService->getCacheStatistics(),
            'system_metrics' => $this->getSystemMetrics(),
            'alerts' => $this->getPerformanceAlertsData(),
            'trends' => $this->getPerformanceTrendsData('24h'),
        ];
    }

    /**
     * Get API performance data for specific time range
     */
    private function getApiPerformanceData(string $timeRange): array
    {
        $timeCondition = $this->getTimeCondition($timeRange);
        
        try {
            $metrics = DB::table('api_performance_logs')
                ->selectRaw('
                    AVG(response_time_ms) as avg_response_time,
                    MIN(response_time_ms) as min_response_time,
                    MAX(response_time_ms) as max_response_time,
                    AVG(memory_usage_bytes) as avg_memory_usage,
                    COUNT(*) as total_requests,
                    COUNT(CASE WHEN status_code >= 400 THEN 1 END) as error_requests,
                    COUNT(CASE WHEN response_time_ms > 2000 THEN 1 END) as slow_requests
                ')
                ->whereRaw($timeCondition)
                ->first();

            return [
                'avg_response_time_ms' => round($metrics->avg_response_time ?? 0, 2),
                'min_response_time_ms' => $metrics->min_response_time ?? 0,
                'max_response_time_ms' => $metrics->max_response_time ?? 0,
                'avg_memory_usage_mb' => round(($metrics->avg_memory_usage ?? 0) / 1024 / 1024, 2),
                'total_requests' => $metrics->total_requests ?? 0,
                'error_requests' => $metrics->error_requests ?? 0,
                'slow_requests' => $metrics->slow_requests ?? 0,
                'error_rate_percentage' => $metrics->total_requests > 0 
                    ? round(($metrics->error_requests / $metrics->total_requests) * 100, 2) 
                    : 0,
                'slow_request_percentage' => $metrics->total_requests > 0 
                    ? round(($metrics->slow_requests / $metrics->total_requests) * 100, 2) 
                    : 0,
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get system metrics
     */
    private function getSystemMetrics(): array
    {
        return [
            'cpu_usage' => $this->getCpuUsage(),
            'memory_usage' => $this->getMemoryUsage(),
            'disk_usage' => $this->getDiskUsage(),
            'load_average' => $this->getLoadAverage(),
            'uptime' => $this->getUptime(),
        ];
    }

    /**
     * Get CPU usage
     */
    private function getCpuUsage(): float
    {
        try {
            $load = sys_getloadavg();
            return round($load[0] * 100, 2);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get memory usage
     */
    private function getMemoryUsage(): array
    {
        try {
            $memory = memory_get_usage(true);
            $peakMemory = memory_get_peak_usage(true);
            $memoryLimit = ini_get('memory_limit');
            
            return [
                'current_mb' => round($memory / 1024 / 1024, 2),
                'peak_mb' => round($peakMemory / 1024 / 1024, 2),
                'limit' => $memoryLimit,
                'usage_percentage' => $this->parseMemoryLimit($memoryLimit) > 0 
                    ? round(($memory / $this->parseMemoryLimit($memoryLimit)) * 100, 2) 
                    : 0,
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get disk usage
     */
    private function getDiskUsage(): array
    {
        try {
            $total = disk_total_space(storage_path());
            $free = disk_free_space(storage_path());
            $used = $total - $free;
            
            return [
                'total_gb' => round($total / 1024 / 1024 / 1024, 2),
                'used_gb' => round($used / 1024 / 1024 / 1024, 2),
                'free_gb' => round($free / 1024 / 1024 / 1024, 2),
                'usage_percentage' => round(($used / $total) * 100, 2),
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get load average
     */
    private function getLoadAverage(): array
    {
        try {
            $load = sys_getloadavg();
            return [
                '1min' => round($load[0], 2),
                '5min' => round($load[1], 2),
                '15min' => round($load[2], 2),
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get uptime
     */
    private function getUptime(): string
    {
        try {
            $uptime = shell_exec('uptime -p');
            return trim($uptime) ?: 'Unknown';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * Get performance alerts data
     */
    private function getPerformanceAlertsData(): array
    {
        $alerts = [];
        
        // Check for slow API responses
        $slowResponses = DB::table('api_performance_logs')
            ->where('response_time_ms', '>', 2000)
            ->where('created_at', '>=', now()->subHour())
            ->count();
        
        if ($slowResponses > 10) {
            $alerts[] = [
                'type' => 'slow_responses',
                'severity' => 'high',
                'message' => "High number of slow responses: {$slowResponses} in the last hour",
                'timestamp' => now()->toISOString(),
            ];
        }
        
        // Check for high error rates
        $errorRate = DB::table('api_performance_logs')
            ->selectRaw('
                COUNT(*) as total_requests,
                COUNT(CASE WHEN status_code >= 400 THEN 1 END) as error_requests
            ')
            ->where('created_at', '>=', now()->subHour())
            ->first();
        
        if ($errorRate->total_requests > 0) {
            $errorPercentage = ($errorRate->error_requests / $errorRate->total_requests) * 100;
            if ($errorPercentage > 10) {
                $alerts[] = [
                    'type' => 'high_error_rate',
                    'severity' => 'high',
                    'message' => "High error rate: {$errorPercentage}% in the last hour",
                    'timestamp' => now()->toISOString(),
                ];
            }
        }
        
        return $alerts;
    }

    /**
     * Get performance trends data
     */
    private function getPerformanceTrendsData(string $timeRange): array
    {
        $timeCondition = $this->getTimeCondition($timeRange);
        
        try {
            $trends = DB::table('api_performance_logs')
                ->selectRaw('
                    DATE_FORMAT(created_at, "%Y-%m-%d %H:00:00") as hour,
                    AVG(response_time_ms) as avg_response_time,
                    COUNT(*) as request_count,
                    COUNT(CASE WHEN status_code >= 400 THEN 1 END) as error_count
                ')
                ->whereRaw($timeCondition)
                ->groupBy('hour')
                ->orderBy('hour')
                ->get();
            
            return $trends->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get time condition for queries
     */
    private function getTimeCondition(string $timeRange): string
    {
        switch ($timeRange) {
            case '1h':
                return "created_at >= '" . now()->subHour()->toDateTimeString() . "'";
            case '24h':
                return "created_at >= '" . now()->subDay()->toDateTimeString() . "'";
            case '7d':
                return "created_at >= '" . now()->subWeek()->toDateTimeString() . "'";
            case '30d':
                return "created_at >= '" . now()->subMonth()->toDateTimeString() . "'";
            default:
                return "created_at >= '" . now()->subHour()->toDateTimeString() . "'";
        }
    }

    /**
     * Parse memory limit string to bytes
     */
    private function parseMemoryLimit(string $memoryLimit): int
    {
        $memoryLimit = trim($memoryLimit);
        $last = strtolower($memoryLimit[strlen($memoryLimit) - 1]);
        $memoryLimit = (int) $memoryLimit;
        
        switch ($last) {
            case 'g':
                $memoryLimit *= 1024;
            case 'm':
                $memoryLimit *= 1024;
            case 'k':
                $memoryLimit *= 1024;
        }
        
        return $memoryLimit;
    }
}
