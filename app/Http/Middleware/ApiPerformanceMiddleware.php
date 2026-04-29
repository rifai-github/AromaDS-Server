<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ApiPerformanceMiddleware
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
        $peakMemory = memory_get_peak_usage();
        
        // Log performance metrics
        $this->logPerformanceMetrics($request, $response, $executionTime, $memoryUsage, $peakMemory);
        
        // Add performance headers
        $response->headers->set('X-Execution-Time', round($executionTime, 2) . 'ms');
        $response->headers->set('X-Memory-Usage', $this->formatBytes($memoryUsage));
        $response->headers->set('X-Peak-Memory', $this->formatBytes($peakMemory));
        $response->headers->set('X-Response-Size', $this->formatBytes(strlen($response->getContent())));
        
        // Check for performance issues
        $this->checkPerformanceIssues($request, $executionTime, $memoryUsage, $response);
        
        return $response;
    }

    /**
     * Log performance metrics
     */
    private function logPerformanceMetrics(Request $request, $response, float $executionTime, int $memoryUsage, int $peakMemory): void
    {
        $metrics = [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'endpoint' => $request->path(),
            'response_time_ms' => round($executionTime, 2),
            'memory_usage_bytes' => $memoryUsage,
            'peak_memory_bytes' => $peakMemory,
            'response_size_bytes' => strlen($response->getContent()),
            'status_code' => $response->getStatusCode(),
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
            'cache_hit' => $this->wasCacheHit($request),
        ];
        
        // Log to database
        try {
            DB::table('api_performance_logs')->insert($metrics);
        } catch (\Exception $e) {
            Log::error('Failed to log API performance metrics: ' . $e->getMessage());
        }
        
        // Log to file for detailed analysis
        Log::info('API Performance Metrics', $metrics);
        
        // Store in cache for real-time monitoring
        $cacheKey = 'api_performance:' . now()->format('Y-m-d-H-i');
        $existingMetrics = Cache::get($cacheKey, []);
        $existingMetrics[] = $metrics;
        Cache::put($cacheKey, $existingMetrics, 3600); // Cache for 1 hour
    }

    /**
     * Check if request was served from cache
     */
    private function wasCacheHit(Request $request): bool
    {
        // This would depend on your caching implementation
        // For now, return false as a placeholder
        return false;
    }

    /**
     * Check for performance issues
     */
    private function checkPerformanceIssues(Request $request, float $executionTime, int $memoryUsage, $response): void
    {
        $issues = [];
        
        // Check response time
        if ($executionTime > 2000) { // 2 seconds
            $issues[] = [
                'type' => 'slow_response',
                'message' => "Response time exceeded 2 seconds: {$executionTime}ms",
                'severity' => 'high'
            ];
        } elseif ($executionTime > 1000) { // 1 second
            $issues[] = [
                'type' => 'slow_response',
                'message' => "Response time exceeded 1 second: {$executionTime}ms",
                'severity' => 'medium'
            ];
        }
        
        // Check memory usage
        if ($memoryUsage > 128 * 1024 * 1024) { // 128MB
            $issues[] = [
                'type' => 'high_memory',
                'message' => "Memory usage exceeded 128MB: " . $this->formatBytes($memoryUsage),
                'severity' => 'high'
            ];
        } elseif ($memoryUsage > 64 * 1024 * 1024) { // 64MB
            $issues[] = [
                'type' => 'high_memory',
                'message' => "Memory usage exceeded 64MB: " . $this->formatBytes($memoryUsage),
                'severity' => 'medium'
            ];
        }
        
        // Check response size
        $responseSize = strlen($response->getContent());
        if ($responseSize > 10 * 1024 * 1024) { // 10MB
            $issues[] = [
                'type' => 'large_response',
                'message' => "Response size exceeded 10MB: " . $this->formatBytes($responseSize),
                'severity' => 'high'
            ];
        } elseif ($responseSize > 5 * 1024 * 1024) { // 5MB
            $issues[] = [
                'type' => 'large_response',
                'message' => "Response size exceeded 5MB: " . $this->formatBytes($responseSize),
                'severity' => 'medium'
            ];
        }
        
        // Check error status codes
        if ($response->getStatusCode() >= 500) {
            $issues[] = [
                'type' => 'server_error',
                'message' => "Server error: " . $response->getStatusCode(),
                'severity' => 'high'
            ];
        } elseif ($response->getStatusCode() >= 400) {
            $issues[] = [
                'type' => 'client_error',
                'message' => "Client error: " . $response->getStatusCode(),
                'severity' => 'medium'
            ];
        }
        
        // Log performance issues
        if (!empty($issues)) {
            Log::warning('API Performance Issues Detected', [
                'url' => $request->fullUrl(),
                'issues' => $issues
            ]);
            
            // Store issues in cache for monitoring dashboard
            $cacheKey = 'api_performance_issues:' . now()->format('Y-m-d-H');
            $existingIssues = Cache::get($cacheKey, []);
            $existingIssues[] = [
                'timestamp' => now()->toISOString(),
                'url' => $request->fullUrl(),
                'issues' => $issues
            ];
            Cache::put($cacheKey, $existingIssues, 3600);
        }
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
