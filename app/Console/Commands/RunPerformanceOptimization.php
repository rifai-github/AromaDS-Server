<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Performance\DatabaseOptimizationService;
use App\Services\Performance\CacheOptimizationService;
use App\Services\Performance\ApiOptimizationService;
use Illuminate\Support\Facades\Log;

class RunPerformanceOptimization extends Command
{
    protected $signature = 'performance:optimize {--type=all : Type of optimization (database, cache, api, all)}';
    protected $description = 'Run performance optimization for ERP system';

    protected $databaseOptimizationService;
    protected $cacheOptimizationService;
    protected $apiOptimizationService;

    public function __construct(
        DatabaseOptimizationService $databaseOptimizationService,
        CacheOptimizationService $cacheOptimizationService,
        ApiOptimizationService $apiOptimizationService
    ) {
        parent::__construct();
        $this->databaseOptimizationService = $databaseOptimizationService;
        $this->cacheOptimizationService = $cacheOptimizationService;
        $this->apiOptimizationService = $apiOptimizationService;
    }

    public function handle()
    {
        $this->info('🚀 Starting ERP Performance Optimization...');
        
        $type = $this->option('type');
        $startTime = microtime(true);
        
        try {
            switch ($type) {
                case 'database':
                    $this->optimizeDatabase();
                    break;
                case 'cache':
                    $this->optimizeCache();
                    break;
                case 'api':
                    $this->optimizeApi();
                    break;
                case 'all':
                default:
                    $this->optimizeAll();
                    break;
            }
            
            $endTime = microtime(true);
            $executionTime = round(($endTime - $startTime), 2);
            
            $this->info("✅ Performance optimization completed successfully in {$executionTime} seconds");
            
        } catch (\Exception $e) {
            $this->error("❌ Performance optimization failed: " . $e->getMessage());
            Log::error('Performance optimization failure', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
        
        return 0;
    }

    private function optimizeDatabase()
    {
        $this->info('🗄️ Optimizing Database...');
        
        // Analyze and optimize queries
        $this->info('  Analyzing database queries...');
        $analysis = $this->databaseOptimizationService->analyzeAndOptimizeQueries();
        
        if (!empty($analysis['slow_queries'])) {
            $this->warn("  Found " . count($analysis['slow_queries']) . " slow queries");
        }
        
        if (!empty($analysis['missing_indexes'])) {
            $this->warn("  Found " . count($analysis['missing_indexes']) . " missing indexes");
        }
        
        // Optimize tables
        $this->info('  Optimizing database tables...');
        $optimizationResults = $this->databaseOptimizationService->optimizeTables();
        
        $optimizedCount = 0;
        $failedCount = 0;
        
        foreach ($optimizationResults as $result) {
            if ($result['status'] === 'optimized') {
                $optimizedCount++;
            } else {
                $failedCount++;
            }
        }
        
        $this->info("  Optimized {$optimizedCount} tables, {$failedCount} failed");
        
        // Analyze tables
        $this->info('  Analyzing table statistics...');
        $analysisResults = $this->databaseOptimizationService->analyzeTables();
        
        $analyzedCount = 0;
        foreach ($analysisResults as $result) {
            if ($result['status'] === 'analyzed') {
                $analyzedCount++;
            }
        }
        
        $this->info("  Analyzed {$analyzedCount} tables");
        
        // Clean up old data
        $this->info('  Cleaning up old data...');
        $cleanupResults = $this->databaseOptimizationService->cleanupOldData();
        
        $totalDeleted = 0;
        foreach ($cleanupResults as $result) {
            if (isset($result['deleted_records'])) {
                $totalDeleted += $result['deleted_records'];
            }
        }
        
        $this->info("  Cleaned up {$totalDeleted} old records");
        
        // Get health report
        $healthReport = $this->databaseOptimizationService->getHealthReport();
        $this->info("  Database health score: {$healthReport['health_score']}/100");
    }

    private function optimizeCache()
    {
        $this->info('💾 Optimizing Cache...');
        
        // Get cache statistics
        $this->info('  Getting cache statistics...');
        $stats = $this->cacheOptimizationService->getCacheStatistics();
        
        if (isset($stats['hit_rate'])) {
            $this->info("  Current cache hit rate: {$stats['hit_rate']}%");
        }
        
        if (isset($stats['used_memory'])) {
            $this->info("  Current memory usage: {$stats['used_memory']}");
        }
        
        // Optimize cache configuration
        $this->info('  Optimizing cache configuration...');
        $optimizationResults = $this->cacheOptimizationService->optimizeCacheConfiguration();
        
        if (isset($optimizationResults['warmup'])) {
            $this->info('  Cache warmup completed');
        }
        
        if (isset($optimizationResults['cleanup'])) {
            if (isset($optimizationResults['cleanup']['expired_keys_removed'])) {
                $this->info("  Removed {$optimizationResults['cleanup']['expired_keys_removed']} expired keys");
            }
        }
        
        if (isset($optimizationResults['memory_optimization'])) {
            if (isset($optimizationResults['memory_optimization']['memory_saved'])) {
                $this->info("  Memory saved: {$optimizationResults['memory_optimization']['memory_saved']}");
            }
        }
        
        // Get health report
        $healthReport = $this->cacheOptimizationService->getHealthReport();
        $this->info("  Cache health score: {$healthReport['health_score']}/100");
    }

    private function optimizeApi()
    {
        $this->info('🌐 Optimizing API...');
        
        // Get API performance metrics
        $this->info('  Getting API performance metrics...');
        $metrics = $this->apiOptimizationService->getApiPerformanceMetrics();
        
        if (isset($metrics['response_times']['avg_response_time_ms'])) {
            $this->info("  Average response time: {$metrics['response_times']['avg_response_time_ms']}ms");
        }
        
        if (isset($metrics['error_rates']['error_rate_percentage'])) {
            $this->info("  Error rate: {$metrics['error_rates']['error_rate_percentage']}%");
        }
        
        if (isset($metrics['cache_performance']['cache_hit_rate_percentage'])) {
            $this->info("  Cache hit rate: {$metrics['cache_performance']['cache_hit_rate_percentage']}%");
        }
        
        // Optimize API performance
        $this->info('  Optimizing API performance...');
        $optimizationResults = $this->apiOptimizationService->optimizeApiPerformance();
        
        if (isset($optimizationResults['database_optimization'])) {
            $this->info('  Database optimization completed');
        }
        
        if (isset($optimizationResults['cache_optimization'])) {
            $this->info('  Cache optimization completed');
        }
        
        if (isset($optimizationResults['compression_optimization'])) {
            $this->info('  Response compression optimized');
        }
        
        if (isset($optimizationResults['rate_limiting_optimization'])) {
            $this->info('  Rate limiting optimized');
        }
        
        // Get health report
        $healthReport = $this->apiOptimizationService->getHealthReport();
        $this->info("  API health score: {$healthReport['health_score']}/100");
    }

    private function optimizeAll()
    {
        $this->info('🎯 Running Complete Performance Optimization...');
        
        $this->optimizeDatabase();
        $this->newLine();
        $this->optimizeCache();
        $this->newLine();
        $this->optimizeApi();
        
        $this->newLine();
        $this->info('🎉 Complete performance optimization finished!');
        
        // Display overall health scores
        $this->displayOverallHealthScores();
    }

    private function displayOverallHealthScores()
    {
        $this->info('📊 Overall Health Scores:');
        
        try {
            $dbHealth = $this->databaseOptimizationService->getHealthReport();
            $cacheHealth = $this->cacheOptimizationService->getHealthReport();
            $apiHealth = $this->apiOptimizationService->getHealthReport();
            
            $this->info("  Database: {$dbHealth['health_score']}/100");
            $this->info("  Cache: {$cacheHealth['health_score']}/100");
            $this->info("  API: {$apiHealth['health_score']}/100");
            
            $overallScore = round(($dbHealth['health_score'] + $cacheHealth['health_score'] + $apiHealth['health_score']) / 3);
            $this->info("  Overall: {$overallScore}/100");
            
            if ($overallScore >= 90) {
                $this->info('  🟢 Excellent performance!');
            } elseif ($overallScore >= 80) {
                $this->info('  🟡 Good performance with room for improvement');
            } elseif ($overallScore >= 70) {
                $this->warn('  🟠 Performance needs attention');
            } else {
                $this->error('  🔴 Performance requires immediate attention');
            }
            
        } catch (\Exception $e) {
            $this->error('  Failed to get health scores: ' . $e->getMessage());
        }
    }
}
