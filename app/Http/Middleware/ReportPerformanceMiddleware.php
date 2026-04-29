<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ReportPerformanceMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        // Process the request
        $response = $next($request);
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        // Calculate metrics
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $memoryUsage = $endMemory - $startMemory;
        
        // Log performance metrics for report-related requests
        if ($this->isReportRequest($request)) {
            $this->logPerformanceMetrics($request, $executionTime, $memoryUsage);
        }
        
        // Add performance headers
        $response->headers->set('X-Execution-Time', round($executionTime, 2) . 'ms');
        $response->headers->set('X-Memory-Usage', $this->formatBytes($memoryUsage));
        
        return $response;
    }

    /**
     * Check if the request is related to reports
     */
    private function isReportRequest(Request $request): bool
    {
        $path = $request->path();
        return str_contains($path, 'reports') || 
               str_contains($path, 'dashboard') || 
               str_contains($path, 'analytics') ||
               str_contains($path, 'kpi');
    }

    /**
     * Log performance metrics
     */
    private function logPerformanceMetrics(Request $request, float $executionTime, int $memoryUsage): void
    {
        $metrics = [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'execution_time_ms' => round($executionTime, 2),
            'memory_usage_bytes' => $memoryUsage,
            'memory_usage_formatted' => $this->formatBytes($memoryUsage),
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString()
        ];
        
        // Log to file
        Log::info('Report Performance Metrics', $metrics);
        
        // Store in cache for real-time monitoring
        $cacheKey = 'performance_metrics:' . now()->format('Y-m-d-H-i');
        $existingMetrics = Cache::get($cacheKey, []);
        $existingMetrics[] = $metrics;
        Cache::put($cacheKey, $existingMetrics, 3600); // Cache for 1 hour
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
