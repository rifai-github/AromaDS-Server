<?php

namespace App\Services\Performance;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CacheOptimizationService
{
    /**
     * Cache configuration
     */
    private static $cacheStrategies = [
        'user_data' => [
            'duration' => 3600, // 1 hour
            'tags' => ['user', 'auth'],
            'priority' => 'high'
        ],
        'customer_data' => [
            'duration' => 1800, // 30 minutes
            'tags' => ['customer', 'business'],
            'priority' => 'high'
        ],
        'contract_data' => [
            'duration' => 1800, // 30 minutes
            'tags' => ['contract', 'business'],
            'priority' => 'high'
        ],
        'job_schedule_data' => [
            'duration' => 900, // 15 minutes
            'tags' => ['job', 'operational'],
            'priority' => 'medium'
        ],
        'invoice_data' => [
            'duration' => 1800, // 30 minutes
            'tags' => ['invoice', 'finance'],
            'priority' => 'high'
        ],
        'report_data' => [
            'duration' => 3600, // 1 hour
            'tags' => ['report', 'analytics'],
            'priority' => 'medium'
        ],
        'dashboard_data' => [
            'duration' => 300, // 5 minutes
            'tags' => ['dashboard', 'real-time'],
            'priority' => 'high'
        ],
        'system_settings' => [
            'duration' => 86400, // 24 hours
            'tags' => ['settings', 'system'],
            'priority' => 'low'
        ],
        'permissions' => [
            'duration' => 7200, // 2 hours
            'tags' => ['permissions', 'auth'],
            'priority' => 'high'
        ],
        'notifications' => [
            'duration' => 600, // 10 minutes
            'tags' => ['notifications', 'user'],
            'priority' => 'medium'
        ]
    ];

    /**
     * Get cache strategies
     */
    public static function getCacheStrategies(): array
    {
        return self::$cacheStrategies;
    }

    /**
     * Get cache statistics
     */
    public function getCacheStatistics(): array
    {
        $stats = [
            'driver' => config('cache.default'),
            'timestamp' => now()->toISOString(),
        ];

        if (config('cache.default') === 'redis') {
            try {
                $redisInfo = Redis::info();
                $stats = array_merge($stats, [
                    'used_memory' => $redisInfo['used_memory_human'] ?? 'N/A',
                    'used_memory_peak' => $redisInfo['used_memory_peak_human'] ?? 'N/A',
                    'connected_clients' => $redisInfo['connected_clients'] ?? 'N/A',
                    'total_commands_processed' => $redisInfo['total_commands_processed'] ?? 'N/A',
                    'keyspace_hits' => $redisInfo['keyspace_hits'] ?? 'N/A',
                    'keyspace_misses' => $redisInfo['keyspace_misses'] ?? 'N/A',
                    'hit_rate' => $this->calculateHitRate($redisInfo),
                    'key_count' => $this->getKeyCount(),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to get Redis statistics: ' . $e->getMessage());
                $stats['error'] = 'Failed to get Redis statistics';
            }
        }

        return $stats;
    }

    /**
     * Calculate cache hit rate
     */
    private function calculateHitRate(array $redisInfo): float
    {
        $hits = $redisInfo['keyspace_hits'] ?? 0;
        $misses = $redisInfo['keyspace_misses'] ?? 0;
        
        if ($hits + $misses === 0) {
            return 0;
        }
        
        return round(($hits / ($hits + $misses)) * 100, 2);
    }

    /**
     * Get key count
     */
    private function getKeyCount(): int
    {
        try {
            if (config('cache.default') === 'redis') {
                return Redis::dbsize();
            }
            return 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Optimize cache configuration
     */
    public function optimizeCacheConfiguration(): array
    {
        $results = [];

        // Warm up frequently accessed data
        $results['warmup'] = $this->warmupCache();

        // Clean up expired keys
        $results['cleanup'] = $this->cleanupExpiredKeys();

        // Optimize memory usage
        $results['memory_optimization'] = $this->optimizeMemoryUsage();

        // Update cache strategies
        $results['strategy_update'] = $this->updateCacheStrategies();

        return $results;
    }

    /**
     * Warm up cache with frequently accessed data
     */
    private function warmupCache(): array
    {
        $results = [];
        
        try {
            // Warm up user permissions
            $this->warmupUserPermissions();
            $results['user_permissions'] = 'warmed_up';

            // Warm up system settings
            $this->warmupSystemSettings();
            $results['system_settings'] = 'warmed_up';

            // Warm up dashboard data
            $this->warmupDashboardData();
            $results['dashboard_data'] = 'warmed_up';

            // Warm up customer data
            $this->warmupCustomerData();
            $results['customer_data'] = 'warmed_up';

        } catch (\Exception $e) {
            Log::error('Cache warmup failed: ' . $e->getMessage());
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Warm up user permissions
     */
    private function warmupUserPermissions(): void
    {
        $users = \App\Models\User::with('roles.permissions')->get();
        
        foreach ($users as $user) {
            $cacheKey = "user_permissions_{$user->id}";
            $permissions = $user->roles->flatMap->permissions->pluck('name')->unique();
            
            Cache::put($cacheKey, $permissions, self::$cacheStrategies['permissions']['duration']);
        }
    }

    /**
     * Warm up system settings
     */
    private function warmupSystemSettings(): void
    {
        $settings = \App\Models\SystemSetting::where('is_active', true)->get();
        
        foreach ($settings as $setting) {
            $cacheKey = "system_setting_{$setting->setting_key}";
            Cache::put($cacheKey, $setting->setting_value, self::$cacheStrategies['system_settings']['duration']);
        }
    }

    /**
     * Warm up dashboard data
     */
    private function warmupDashboardData(): void
    {
        $cacheKey = 'dashboard_summary';
        $data = [
            'total_customers' => \App\Models\Customer::count(),
            'total_contracts' => \App\Models\Contract::count(),
            'total_job_schedules' => \App\Models\JobSchedule::count(),
            'total_invoices' => \App\Models\Invoice::count(),
            'active_users' => \App\Models\User::where('is_active', true)->count(),
        ];
        
        Cache::put($cacheKey, $data, self::$cacheStrategies['dashboard_data']['duration']);
    }

    /**
     * Warm up customer data
     */
    private function warmupCustomerData(): void
    {
        $customers = \App\Models\Customer::with(['contracts', 'jobSchedules'])->limit(100)->get();
        
        foreach ($customers as $customer) {
            $cacheKey = "customer_data_{$customer->id}";
            Cache::put($cacheKey, $customer, self::$cacheStrategies['customer_data']['duration']);
        }
    }

    /**
     * Clean up expired keys
     */
    private function cleanupExpiredKeys(): array
    {
        $results = [];
        
        try {
            if (config('cache.default') === 'redis') {
                // Get all keys
                $keys = Redis::keys('*');
                $expiredCount = 0;
                
                foreach ($keys as $key) {
                    $ttl = Redis::ttl($key);
                    if ($ttl === -2) { // Key has expired
                        Redis::del($key);
                        $expiredCount++;
                    }
                }
                
                $results['expired_keys_removed'] = $expiredCount;
            } else {
                $results['message'] = 'Cleanup not supported for this cache driver';
            }
        } catch (\Exception $e) {
            Log::error('Cache cleanup failed: ' . $e->getMessage());
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Optimize memory usage
     */
    private function optimizeMemoryUsage(): array
    {
        $results = [];
        
        try {
            if (config('cache.default') === 'redis') {
                // Get memory usage before optimization
                $beforeMemory = Redis::info()['used_memory'] ?? 0;
                
                // Remove low-priority cache entries
                $this->removeLowPriorityCache();
                
                // Get memory usage after optimization
                $afterMemory = Redis::info()['used_memory'] ?? 0;
                $memorySaved = $beforeMemory - $afterMemory;
                
                $results = [
                    'memory_before' => $this->formatBytes($beforeMemory),
                    'memory_after' => $this->formatBytes($afterMemory),
                    'memory_saved' => $this->formatBytes($memorySaved),
                ];
            } else {
                $results['message'] = 'Memory optimization not supported for this cache driver';
            }
        } catch (\Exception $e) {
            Log::error('Memory optimization failed: ' . $e->getMessage());
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Remove low-priority cache entries
     */
    private function removeLowPriorityCache(): void
    {
        $lowPriorityStrategies = array_filter(self::$cacheStrategies, function($strategy) {
            return $strategy['priority'] === 'low';
        });

        foreach ($lowPriorityStrategies as $strategyName => $strategy) {
            if (isset($strategy['tags'])) {
                foreach ($strategy['tags'] as $tag) {
                    $this->removeCacheByTag($tag);
                }
            }
        }
    }

    /**
     * Remove cache by tag
     */
    private function removeCacheByTag(string $tag): void
    {
        try {
            if (config('cache.default') === 'redis') {
                $keys = Redis::keys("*{$tag}*");
                if (!empty($keys)) {
                    Redis::del($keys);
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to remove cache by tag {$tag}: " . $e->getMessage());
        }
    }

    /**
     * Update cache strategies
     */
    private function updateCacheStrategies(): array
    {
        $results = [];
        
        try {
            // Update cache strategies based on usage patterns
            $usagePatterns = $this->analyzeUsagePatterns();
            
            foreach ($usagePatterns as $pattern => $data) {
                if (isset(self::$cacheStrategies[$pattern])) {
                    $newDuration = $this->calculateOptimalDuration($data);
                    self::$cacheStrategies[$pattern]['duration'] = $newDuration;
                    $results[$pattern] = "Duration updated to {$newDuration} seconds";
                }
            }
        } catch (\Exception $e) {
            Log::error('Cache strategy update failed: ' . $e->getMessage());
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Analyze cache usage patterns
     */
    private function analyzeUsagePatterns(): array
    {
        $patterns = [];
        
        try {
            if (config('cache.default') === 'redis') {
                $keys = Redis::keys('*');
                
                foreach ($keys as $key) {
                    $ttl = Redis::ttl($key);
                    $keyType = $this->getKeyType($key);
                    
                    if (!isset($patterns[$keyType])) {
                        $patterns[$keyType] = [
                            'count' => 0,
                            'total_ttl' => 0,
                            'access_count' => 0
                        ];
                    }
                    
                    $patterns[$keyType]['count']++;
                    $patterns[$keyType]['total_ttl'] += $ttl;
                }
                
                // Calculate averages
                foreach ($patterns as $type => $data) {
                    $patterns[$type]['avg_ttl'] = $data['total_ttl'] / $data['count'];
                }
            }
        } catch (\Exception $e) {
            Log::error('Usage pattern analysis failed: ' . $e->getMessage());
        }

        return $patterns;
    }

    /**
     * Get key type from cache key
     */
    private function getKeyType(string $key): string
    {
        if (str_contains($key, 'user_')) return 'user_data';
        if (str_contains($key, 'customer_')) return 'customer_data';
        if (str_contains($key, 'contract_')) return 'contract_data';
        if (str_contains($key, 'job_')) return 'job_schedule_data';
        if (str_contains($key, 'invoice_')) return 'invoice_data';
        if (str_contains($key, 'report_')) return 'report_data';
        if (str_contains($key, 'dashboard_')) return 'dashboard_data';
        if (str_contains($key, 'system_')) return 'system_settings';
        if (str_contains($key, 'permission_')) return 'permissions';
        if (str_contains($key, 'notification_')) return 'notifications';
        
        return 'other';
    }

    /**
     * Calculate optimal cache duration
     */
    private function calculateOptimalDuration(array $data): int
    {
        $avgTtl = $data['avg_ttl'] ?? 3600;
        $accessCount = $data['access_count'] ?? 1;
        
        // Adjust duration based on access frequency
        if ($accessCount > 100) {
            return min($avgTtl * 2, 7200); // Double duration for frequently accessed data
        } elseif ($accessCount < 10) {
            return max($avgTtl / 2, 300); // Halve duration for rarely accessed data
        }
        
        return $avgTtl;
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

    /**
     * Get cache health report
     */
    public function getHealthReport(): array
    {
        return [
            'timestamp' => now()->toISOString(),
            'statistics' => $this->getCacheStatistics(),
            'strategies' => self::$cacheStrategies,
            'health_score' => $this->calculateHealthScore(),
            'recommendations' => $this->getRecommendations(),
        ];
    }

    /**
     * Calculate cache health score
     */
    private function calculateHealthScore(): int
    {
        $score = 100;
        $stats = $this->getCacheStatistics();

        // Deduct points for low hit rate
        if ($stats['hit_rate'] < 80) {
            $score -= 20;
        } elseif ($stats['hit_rate'] < 90) {
            $score -= 10;
        }

        // Deduct points for high memory usage
        if (isset($stats['used_memory'])) {
            $memoryMB = $this->parseMemoryToMB($stats['used_memory']);
            if ($memoryMB > 1000) { // More than 1GB
                $score -= 15;
            } elseif ($memoryMB > 500) { // More than 500MB
                $score -= 10;
            }
        }

        return max(0, $score);
    }

    /**
     * Parse memory string to MB
     */
    private function parseMemoryToMB(string $memory): float
    {
        $memory = strtoupper($memory);
        $value = (float) $memory;
        
        if (str_contains($memory, 'GB')) {
            return $value * 1024;
        } elseif (str_contains($memory, 'MB')) {
            return $value;
        } elseif (str_contains($memory, 'KB')) {
            return $value / 1024;
        }
        
        return $value / (1024 * 1024); // Assume bytes
    }

    /**
     * Get cache optimization recommendations
     */
    private function getRecommendations(): array
    {
        $recommendations = [];
        $stats = $this->getCacheStatistics();

        if ($stats['hit_rate'] < 80) {
            $recommendations[] = [
                'type' => 'hit_rate',
                'message' => 'Cache hit rate is low. Consider increasing cache duration for frequently accessed data.',
                'priority' => 'high'
            ];
        }

        if (isset($stats['used_memory'])) {
            $memoryMB = $this->parseMemoryToMB($stats['used_memory']);
            if ($memoryMB > 1000) {
                $recommendations[] = [
                    'type' => 'memory_usage',
                    'message' => 'Cache memory usage is high. Consider reducing cache duration or implementing cache eviction policies.',
                    'priority' => 'medium'
                ];
            }
        }

        if ($stats['key_count'] > 10000) {
            $recommendations[] = [
                'type' => 'key_count',
                'message' => 'High number of cache keys. Consider implementing cache key cleanup strategies.',
                'priority' => 'medium'
            ];
        }

        return $recommendations;
    }
}
