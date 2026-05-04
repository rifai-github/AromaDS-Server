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
        if (! $this->shouldTrack($request)) {
            return $next($request);
        }

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
        $responseSize = $this->getResponseSize($response);
        
        // Log performance metrics
        $this->logPerformanceMetrics($request, $response, $executionTime, $memoryUsage, $peakMemory, $responseSize);
        
        // Add performance headers
        $response->headers->set('X-Execution-Time', round($executionTime, 2) . 'ms');
        $response->headers->set('X-Memory-Usage', $this->formatBytes($memoryUsage));
        $response->headers->set('X-Peak-Memory', $this->formatBytes($peakMemory));
        $response->headers->set('X-Response-Size', $this->formatBytes($responseSize));
        
        // Check for performance issues
        $this->checkPerformanceIssues($request, $executionTime, $memoryUsage, $response, $responseSize);
        
        return $response;
    }

    /**
     * Log performance metrics
     */
    private function logPerformanceMetrics(Request $request, $response, float $executionTime, int $memoryUsage, int $peakMemory, int $responseSize): void
    {
        $metrics = [
            'url' => $this->limitString($request->fullUrl(), 255),
            'method' => $request->method(),
            'endpoint' => $this->limitString($request->path(), 255),
            'response_time_ms' => round($executionTime, 2),
            'memory_usage_bytes' => $memoryUsage,
            'peak_memory_bytes' => $peakMemory,
            'response_size_bytes' => $responseSize,
            'status_code' => $response->getStatusCode(),
            'user_id' => auth()->id(),
            'ip_address' => $this->limitString((string) $request->ip(), 45),
            'user_agent' => $request->userAgent(),
            'cache_hit' => $this->wasCacheHit($request),
            'created_at' => now(),
        ];
        
        // Log to database
        try {
            DB::table('api_performance_logs')->insert($metrics);
        } catch (\Exception $e) {
            Log::error('Failed to log API performance metrics: ' . $e->getMessage());
        }
        
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
    private function checkPerformanceIssues(Request $request, float $executionTime, int $memoryUsage, $response, int $responseSize): void
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

    private function shouldTrack(Request $request): bool
    {
        $path = ltrim($request->path(), '/');

        $trackedPrefixes = [
            'api/login',
            'api/logout',
            'api/v1/mobile',
            'api/v1/warehouse/inventory-issuings',
            'api/v1/warehouse/inventory-receivings',
            'api/v1/warehouse/inventory-requests',
            'api/warehouse/inventory-issuings',
            'api/warehouse/inventory-receivings',
            'api/warehouse/inventory-requests',
            'warehouse/inventory-issuings',
            'warehouse/inventory-receivings',
            'warehouse/inventory-requests',
        ];

        foreach ($trackedPrefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }

    private function getResponseSize($response): int
    {
        try {
            $content = $response->getContent();

            return is_string($content) ? strlen($content) : 0;
        } catch (\Throwable) {
            return 0;
        }
    }

    private function limitString(string $value, int $limit): string
    {
        return strlen($value) > $limit ? substr($value, 0, $limit) : $value;
    }
}
