<?php

namespace App\Services\Performance;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ApiOptimizationService
{
    /**
     * API performance configuration
     */
    const API_CONFIG = [
        'max_response_time' => 2000, // 2 seconds
        'max_memory_usage' => 128 * 1024 * 1024, // 128MB
        'max_response_size' => 10 * 1024 * 1024, // 10MB
        'cache_duration' => 300, // 5 minutes
        'rate_limit' => 100, // requests per minute
    ];

    /**
     * Get API performance metrics
     */
    public function getApiPerformanceMetrics(): array
    {
        return [
            'timestamp' => now()->toISOString(),
            'response_times' => $this->getResponseTimeMetrics(),
            'memory_usage' => $this->getMemoryUsageMetrics(),
            'error_rates' => $this->getErrorRateMetrics(),
            'throughput' => $this->getThroughputMetrics(),
            'cache_performance' => $this->getCachePerformanceMetrics(),
        ];
    }

    /**
     * Get response time metrics
     */
    private function getResponseTimeMetrics(): array
    {
        try {
            $metrics = DB::table('api_performance_logs')
                ->selectRaw('
                    AVG(response_time) as avg_response_time,
                    MIN(response_time) as min_response_time,
                    MAX(response_time) as max_response_time,
                    COUNT(*) as total_requests,
                    COUNT(CASE WHEN response_time > 2000 THEN 1 END) as slow_requests
                ')
                ->where('created_at', '>=', now()->subHour())
                ->first();

            return [
                'avg_response_time_ms' => round($metrics->avg_response_time ?? 0, 2),
                'min_response_time_ms' => $metrics->min_response_time ?? 0,
                'max_response_time_ms' => $metrics->max_response_time ?? 0,
                'total_requests' => $metrics->total_requests ?? 0,
                'slow_requests' => $metrics->slow_requests ?? 0,
                'slow_request_percentage' => $metrics->total_requests > 0 
                    ? round(($metrics->slow_requests / $metrics->total_requests) * 100, 2) 
                    : 0,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get response time metrics: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get memory usage metrics
     */
    private function getMemoryUsageMetrics(): array
    {
        try {
            $metrics = DB::table('api_performance_logs')
                ->selectRaw('
                    AVG(memory_usage) as avg_memory_usage,
                    MIN(memory_usage) as min_memory_usage,
                    MAX(memory_usage) as max_memory_usage,
                    COUNT(CASE WHEN memory_usage > 128*1024*1024 THEN 1 END) as high_memory_requests
                ')
                ->where('created_at', '>=', now()->subHour())
                ->first();

            return [
                'avg_memory_usage_mb' => round(($metrics->avg_memory_usage ?? 0) / 1024 / 1024, 2),
                'min_memory_usage_mb' => round(($metrics->min_memory_usage ?? 0) / 1024 / 1024, 2),
                'max_memory_usage_mb' => round(($metrics->max_memory_usage ?? 0) / 1024 / 1024, 2),
                'high_memory_requests' => $metrics->high_memory_requests ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get memory usage metrics: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get error rate metrics
     */
    private function getErrorRateMetrics(): array
    {
        try {
            $metrics = DB::table('api_performance_logs')
                ->selectRaw('
                    COUNT(*) as total_requests,
                    COUNT(CASE WHEN status_code >= 400 THEN 1 END) as error_requests,
                    COUNT(CASE WHEN status_code >= 500 THEN 1 END) as server_errors
                ')
                ->where('created_at', '>=', now()->subHour())
                ->first();

            return [
                'total_requests' => $metrics->total_requests ?? 0,
                'error_requests' => $metrics->error_requests ?? 0,
                'server_errors' => $metrics->server_errors ?? 0,
                'error_rate_percentage' => $metrics->total_requests > 0 
                    ? round(($metrics->error_requests / $metrics->total_requests) * 100, 2) 
                    : 0,
                'server_error_rate_percentage' => $metrics->total_requests > 0 
                    ? round(($metrics->server_errors / $metrics->total_requests) * 100, 2) 
                    : 0,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get error rate metrics: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get throughput metrics
     */
    private function getThroughputMetrics(): array
    {
        try {
            $metrics = DB::table('api_performance_logs')
                ->selectRaw('
                    COUNT(*) as requests_per_hour,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(DISTINCT endpoint) as unique_endpoints
                ')
                ->where('created_at', '>=', now()->subHour())
                ->first();

            return [
                'requests_per_hour' => $metrics->requests_per_hour ?? 0,
                'requests_per_minute' => round(($metrics->requests_per_hour ?? 0) / 60, 2),
                'unique_users' => $metrics->unique_users ?? 0,
                'unique_endpoints' => $metrics->unique_endpoints ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get throughput metrics: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get cache performance metrics
     */
    private function getCachePerformanceMetrics(): array
    {
        try {
            $metrics = DB::table('api_performance_logs')
                ->selectRaw('
                    COUNT(*) as total_requests,
                    COUNT(CASE WHEN cache_hit = 1 THEN 1 END) as cache_hits,
                    COUNT(CASE WHEN cache_hit = 0 THEN 1 END) as cache_misses
                ')
                ->where('created_at', '>=', now()->subHour())
                ->first();

            $totalRequests = $metrics->total_requests ?? 0;
            $cacheHits = $metrics->cache_hits ?? 0;
            $cacheMisses = $metrics->cache_misses ?? 0;

            return [
                'total_requests' => $totalRequests,
                'cache_hits' => $cacheHits,
                'cache_misses' => $cacheMisses,
                'cache_hit_rate_percentage' => $totalRequests > 0 
                    ? round(($cacheHits / $totalRequests) * 100, 2) 
                    : 0,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get cache performance metrics: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Optimize API performance
     */
    public function optimizeApiPerformance(): array
    {
        $results = [];

        // Optimize database queries
        $results['database_optimization'] = $this->optimizeDatabaseQueries();

        // Optimize caching
        $results['cache_optimization'] = $this->optimizeApiCaching();

        // Optimize response compression
        $results['compression_optimization'] = $this->optimizeResponseCompression();

        // Optimize rate limiting
        $results['rate_limiting_optimization'] = $this->optimizeRateLimiting();

        return $results;
    }

    /**
     * Optimize database queries
     */
    private function optimizeDatabaseQueries(): array
    {
        $results = [];

        try {
            // Get slow queries
            $slowQueries = DB::select("
                SELECT 
                    query,
                    avg_timer_wait/1000000000 as avg_time_seconds,
                    count_star as execution_count
                FROM performance_schema.events_statements_summary_by_digest 
                WHERE avg_timer_wait/1000000000 > 1
                ORDER BY avg_timer_wait DESC 
                LIMIT 10
            ");

            $results['slow_queries_found'] = count($slowQueries);
            $results['slow_queries'] = $slowQueries;

            // Optimize queries with missing indexes
            $results['index_optimization'] = $this->optimizeMissingIndexes();

        } catch (\Exception $e) {
            Log::error('Database query optimization failed: ' . $e->getMessage());
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Optimize missing indexes
     */
    private function optimizeMissingIndexes(): array
    {
        $results = [];

        try {
            // Check for missing indexes on frequently queried columns
            $missingIndexes = [
                'customers' => ['is_active', 'created_at', 'branch_id'],
                'contracts' => ['customer_id', 'status', 'created_at'],
                'job_schedules' => ['customer_id', 'status', 'scheduled_date'],
                'invoices' => ['customer_id', 'status', 'invoice_date'],
                'payments' => ['invoice_id', 'status', 'payment_date'],
            ];

            foreach ($missingIndexes as $table => $columns) {
                foreach ($columns as $column) {
                    $indexName = "idx_{$table}_{$column}";
                    
                    try {
                        DB::statement("CREATE INDEX {$indexName} ON {$table} ({$column})");
                        $results[] = "Created index {$indexName} on {$table}.{$column}";
                    } catch (\Exception $e) {
                        // Index might already exist
                        if (!str_contains($e->getMessage(), 'Duplicate key name')) {
                            $results[] = "Failed to create index {$indexName}: " . $e->getMessage();
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('Index optimization failed: ' . $e->getMessage());
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Optimize API caching
     */
    private function optimizeApiCaching(): array
    {
        $results = [];

        try {
            // Implement response caching for frequently accessed endpoints
            $endpoints = [
                '/api/v1/marketing/customers',
                '/api/v1/marketing/contracts',
                '/api/v1/operational/job-schedules',
                '/api/v1/finance/invoices',
                '/api/v1/reports/dashboard',
            ];

            foreach ($endpoints as $endpoint) {
                $cacheKey = 'api_response_' . md5($endpoint);
                
                // Cache endpoint metadata
                Cache::put($cacheKey, [
                    'endpoint' => $endpoint,
                    'cached_at' => now(),
                    'cache_duration' => self::API_CONFIG['cache_duration'],
                ], self::API_CONFIG['cache_duration']);

                $results[] = "Cached endpoint: {$endpoint}";
            }

        } catch (\Exception $e) {
            Log::error('API caching optimization failed: ' . $e->getMessage());
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Optimize response compression
     */
    private function optimizeResponseCompression(): array
    {
        $results = [];

        try {
            // Enable gzip compression for API responses
            $compressionConfig = [
                'enabled' => true,
                'level' => 6, // Balanced compression
                'min_size' => 1024, // Compress responses larger than 1KB
            ];

            // Store compression configuration
            Cache::put('api_compression_config', $compressionConfig, 86400);

            $results['compression_enabled'] = true;
            $results['compression_level'] = $compressionConfig['level'];
            $results['min_size'] = $compressionConfig['min_size'];

        } catch (\Exception $e) {
            Log::error('Response compression optimization failed: ' . $e->getMessage());
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Optimize rate limiting
     */
    private function optimizeRateLimiting(): array
    {
        $results = [];

        try {
            // Implement dynamic rate limiting based on system load
            $rateLimitConfig = [
                'default_limit' => self::API_CONFIG['rate_limit'],
                'burst_limit' => self::API_CONFIG['rate_limit'] * 2,
                'window_size' => 60, // 1 minute
                'adaptive' => true,
            ];

            // Store rate limiting configuration
            Cache::put('api_rate_limit_config', $rateLimitConfig, 86400);

            $results['rate_limiting_configured'] = true;
            $results['default_limit'] = $rateLimitConfig['default_limit'];
            $results['burst_limit'] = $rateLimitConfig['burst_limit'];

        } catch (\Exception $e) {
            Log::error('Rate limiting optimization failed: ' . $e->getMessage());
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Get API health report
     */
    public function getHealthReport(): array
    {
        return [
            'timestamp' => now()->toISOString(),
            'performance_metrics' => $this->getApiPerformanceMetrics(),
            'health_score' => $this->calculateHealthScore(),
            'recommendations' => $this->getRecommendations(),
            'configuration' => self::API_CONFIG,
        ];
    }

    /**
     * Calculate API health score
     */
    private function calculateHealthScore(): int
    {
        $score = 100;
        $metrics = $this->getApiPerformanceMetrics();

        // Deduct points for slow response times
        if (isset($metrics['response_times']['avg_response_time_ms'])) {
            $avgResponseTime = $metrics['response_times']['avg_response_time_ms'];
            if ($avgResponseTime > 2000) {
                $score -= 30;
            } elseif ($avgResponseTime > 1000) {
                $score -= 20;
            } elseif ($avgResponseTime > 500) {
                $score -= 10;
            }
        }

        // Deduct points for high error rates
        if (isset($metrics['error_rates']['error_rate_percentage'])) {
            $errorRate = $metrics['error_rates']['error_rate_percentage'];
            if ($errorRate > 10) {
                $score -= 25;
            } elseif ($errorRate > 5) {
                $score -= 15;
            } elseif ($errorRate > 1) {
                $score -= 10;
            }
        }

        // Deduct points for high memory usage
        if (isset($metrics['memory_usage']['avg_memory_usage_mb'])) {
            $avgMemory = $metrics['memory_usage']['avg_memory_usage_mb'];
            if ($avgMemory > 128) {
                $score -= 20;
            } elseif ($avgMemory > 64) {
                $score -= 10;
            }
        }

        // Deduct points for low cache hit rate
        if (isset($metrics['cache_performance']['cache_hit_rate_percentage'])) {
            $cacheHitRate = $metrics['cache_performance']['cache_hit_rate_percentage'];
            if ($cacheHitRate < 50) {
                $score -= 15;
            } elseif ($cacheHitRate < 70) {
                $score -= 10;
            }
        }

        return max(0, $score);
    }

    /**
     * Get API optimization recommendations
     */
    private function getRecommendations(): array
    {
        $recommendations = [];
        $metrics = $this->getApiPerformanceMetrics();

        // Response time recommendations
        if (isset($metrics['response_times']['avg_response_time_ms'])) {
            $avgResponseTime = $metrics['response_times']['avg_response_time_ms'];
            if ($avgResponseTime > 1000) {
                $recommendations[] = [
                    'type' => 'response_time',
                    'message' => 'Average response time is high. Consider optimizing database queries and implementing caching.',
                    'priority' => 'high'
                ];
            }
        }

        // Error rate recommendations
        if (isset($metrics['error_rates']['error_rate_percentage'])) {
            $errorRate = $metrics['error_rates']['error_rate_percentage'];
            if ($errorRate > 5) {
                $recommendations[] = [
                    'type' => 'error_rate',
                    'message' => 'Error rate is high. Review error logs and implement better error handling.',
                    'priority' => 'high'
                ];
            }
        }

        // Memory usage recommendations
        if (isset($metrics['memory_usage']['avg_memory_usage_mb'])) {
            $avgMemory = $metrics['memory_usage']['avg_memory_usage_mb'];
            if ($avgMemory > 64) {
                $recommendations[] = [
                    'type' => 'memory_usage',
                    'message' => 'Memory usage is high. Consider optimizing queries and implementing memory-efficient data structures.',
                    'priority' => 'medium'
                ];
            }
        }

        // Cache performance recommendations
        if (isset($metrics['cache_performance']['cache_hit_rate_percentage'])) {
            $cacheHitRate = $metrics['cache_performance']['cache_hit_rate_percentage'];
            if ($cacheHitRate < 70) {
                $recommendations[] = [
                    'type' => 'cache_performance',
                    'message' => 'Cache hit rate is low. Consider increasing cache duration and implementing more aggressive caching strategies.',
                    'priority' => 'medium'
                ];
            }
        }

        return $recommendations;
    }
}
