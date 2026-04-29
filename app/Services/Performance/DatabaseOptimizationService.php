<?php

namespace App\Services\Performance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DatabaseOptimizationService
{
    /**
     * Analyze and optimize database queries
     */
    public function analyzeAndOptimizeQueries(): array
    {
        $results = [
            'slow_queries' => $this->getSlowQueries(),
            'missing_indexes' => $this->getMissingIndexes(),
            'unused_indexes' => $this->getUnusedIndexes(),
            'table_sizes' => $this->getTableSizes(),
            'optimization_suggestions' => $this->getOptimizationSuggestions(),
        ];

        return $results;
    }

    /**
     * Get slow queries from database
     */
    private function getSlowQueries(): array
    {
        try {
            $slowQueries = DB::select("
                SELECT 
                    query,
                    avg_timer_wait/1000000000 as avg_time_seconds,
                    count_star as execution_count,
                    sum_timer_wait/1000000000 as total_time_seconds
                FROM performance_schema.events_statements_summary_by_digest 
                WHERE avg_timer_wait/1000000000 > 1
                ORDER BY avg_timer_wait DESC 
                LIMIT 20
            ");

            return $slowQueries;
        } catch (\Exception $e) {
            Log::error('Failed to get slow queries: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get missing indexes
     */
    private function getMissingIndexes(): array
    {
        try {
            $missingIndexes = DB::select("
                SELECT 
                    object_schema,
                    object_name,
                    count_star as execution_count,
                    sum_timer_wait/1000000000 as total_time_seconds
                FROM performance_schema.table_io_waits_summary_by_table 
                WHERE count_star > 1000
                ORDER BY sum_timer_wait DESC 
                LIMIT 20
            ");

            return $missingIndexes;
        } catch (\Exception $e) {
            Log::error('Failed to get missing indexes: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get unused indexes
     */
    private function getUnusedIndexes(): array
    {
        try {
            $unusedIndexes = DB::select("
                SELECT 
                    object_schema,
                    object_name,
                    index_name,
                    count_star as usage_count
                FROM performance_schema.table_io_waits_summary_by_index_usage 
                WHERE count_star = 0
                AND object_schema = DATABASE()
                ORDER BY object_name
            ");

            return $unusedIndexes;
        } catch (\Exception $e) {
            Log::error('Failed to get unused indexes: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get table sizes
     */
    private function getTableSizes(): array
    {
        try {
            $tableSizes = DB::select("
                SELECT 
                    table_name,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
                    table_rows,
                    ROUND((data_length / 1024 / 1024), 2) AS data_mb,
                    ROUND((index_length / 1024 / 1024), 2) AS index_mb
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
                ORDER BY (data_length + index_length) DESC
            ");

            return $tableSizes;
        } catch (\Exception $e) {
            Log::error('Failed to get table sizes: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get optimization suggestions
     */
    private function getOptimizationSuggestions(): array
    {
        $suggestions = [];

        // Check for tables without primary keys
        $tablesWithoutPK = DB::select("
            SELECT table_name
            FROM information_schema.tables t
            LEFT JOIN information_schema.table_constraints tc 
                ON t.table_name = tc.table_name 
                AND tc.constraint_type = 'PRIMARY KEY'
            WHERE t.table_schema = DATABASE()
            AND tc.table_name IS NULL
        ");

        if (!empty($tablesWithoutPK)) {
            $suggestions[] = [
                'type' => 'missing_primary_key',
                'message' => 'Tables without primary keys: ' . implode(', ', array_column($tablesWithoutPK, 'table_name')),
                'priority' => 'high'
            ];
        }

        // Check for tables with many columns
        $tablesWithManyColumns = DB::select("
            SELECT table_name, COUNT(*) as column_count
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
            GROUP BY table_name
            HAVING column_count > 20
            ORDER BY column_count DESC
        ");

        if (!empty($tablesWithManyColumns)) {
            $suggestions[] = [
                'type' => 'too_many_columns',
                'message' => 'Tables with many columns: ' . implode(', ', array_column($tablesWithManyColumns, 'table_name')),
                'priority' => 'medium'
            ];
        }

        // Check for tables with many indexes
        $tablesWithManyIndexes = DB::select("
            SELECT table_name, COUNT(*) as index_count
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
            GROUP BY table_name
            HAVING index_count > 10
            ORDER BY index_count DESC
        ");

        if (!empty($tablesWithManyIndexes)) {
            $suggestions[] = [
                'type' => 'too_many_indexes',
                'message' => 'Tables with many indexes: ' . implode(', ', array_column($tablesWithManyIndexes, 'table_name')),
                'priority' => 'medium'
            ];
        }

        return $suggestions;
    }

    /**
     * Optimize database tables
     */
    public function optimizeTables(): array
    {
        $results = [];
        $tables = $this->getAllTables();

        foreach ($tables as $table) {
            try {
                $startTime = microtime(true);
                
                DB::statement("OPTIMIZE TABLE {$table}");
                
                $endTime = microtime(true);
                $executionTime = round(($endTime - $startTime) * 1000, 2);
                
                $results[] = [
                    'table' => $table,
                    'status' => 'optimized',
                    'execution_time_ms' => $executionTime
                ];
                
            } catch (\Exception $e) {
                $results[] = [
                    'table' => $table,
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Analyze table statistics
     */
    public function analyzeTables(): array
    {
        $results = [];
        $tables = $this->getAllTables();

        foreach ($tables as $table) {
            try {
                $startTime = microtime(true);
                
                DB::statement("ANALYZE TABLE {$table}");
                
                $endTime = microtime(true);
                $executionTime = round(($endTime - $startTime) * 1000, 2);
                
                $results[] = [
                    'table' => $table,
                    'status' => 'analyzed',
                    'execution_time_ms' => $executionTime
                ];
                
            } catch (\Exception $e) {
                $results[] = [
                    'table' => $table,
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Get all tables in the database
     */
    private function getAllTables(): array
    {
        $tables = DB::select("SHOW TABLES");
        return array_map(function($table) {
            return array_values((array)$table)[0];
        }, $tables);
    }

    /**
     * Get database performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        try {
            $metrics = [];

            // Connection count
            $connections = DB::select("SHOW STATUS LIKE 'Threads_connected'");
            $metrics['connections'] = $connections[0]->Value ?? 0;

            // Query cache hit rate
            $queryCache = DB::select("SHOW STATUS LIKE 'Qcache%'");
            $queryCacheData = [];
            foreach ($queryCache as $stat) {
                $queryCacheData[$stat->Variable_name] = $stat->Value;
            }
            
            $hitRate = 0;
            if (isset($queryCacheData['Qcache_hits']) && isset($queryCacheData['Qcache_inserts'])) {
                $total = $queryCacheData['Qcache_hits'] + $queryCacheData['Qcache_inserts'];
                if ($total > 0) {
                    $hitRate = round(($queryCacheData['Qcache_hits'] / $total) * 100, 2);
                }
            }
            $metrics['query_cache_hit_rate'] = $hitRate;

            // InnoDB buffer pool hit rate
            $innodbStats = DB::select("SHOW STATUS LIKE 'Innodb_buffer_pool%'");
            $innodbData = [];
            foreach ($innodbStats as $stat) {
                $innodbData[$stat->Variable_name] = $stat->Value;
            }
            
            $bufferPoolHitRate = 0;
            if (isset($innodbData['Innodb_buffer_pool_reads']) && isset($innodbData['Innodb_buffer_pool_read_requests'])) {
                $total = $innodbData['Innodb_buffer_pool_reads'] + $innodbData['Innodb_buffer_pool_read_requests'];
                if ($total > 0) {
                    $bufferPoolHitRate = round(($innodbData['Innodb_buffer_pool_read_requests'] / $total) * 100, 2);
                }
            }
            $metrics['innodb_buffer_pool_hit_rate'] = $bufferPoolHitRate;

            // Slow query count
            $slowQueries = DB::select("SHOW STATUS LIKE 'Slow_queries'");
            $metrics['slow_queries'] = $slowQueries[0]->Value ?? 0;

            // Uptime
            $uptime = DB::select("SHOW STATUS LIKE 'Uptime'");
            $metrics['uptime_seconds'] = $uptime[0]->Value ?? 0;

            return $metrics;

        } catch (\Exception $e) {
            Log::error('Failed to get database performance metrics: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Clean up old data
     */
    public function cleanupOldData(): array
    {
        $results = [];

        // Clean up old audit logs (older than 1 year)
        try {
            $deletedAuditLogs = DB::table('audit_logs')
                ->where('created_at', '<', now()->subYear())
                ->delete();
            
            $results[] = [
                'table' => 'audit_logs',
                'action' => 'cleanup',
                'deleted_records' => $deletedAuditLogs
            ];
        } catch (\Exception $e) {
            $results[] = [
                'table' => 'audit_logs',
                'action' => 'cleanup_failed',
                'error' => $e->getMessage()
            ];
        }

        // Clean up old login histories (older than 6 months)
        try {
            $deletedLoginHistories = DB::table('login_histories')
                ->where('created_at', '<', now()->subMonths(6))
                ->delete();
            
            $results[] = [
                'table' => 'login_histories',
                'action' => 'cleanup',
                'deleted_records' => $deletedLoginHistories
            ];
        } catch (\Exception $e) {
            $results[] = [
                'table' => 'login_histories',
                'action' => 'cleanup_failed',
                'error' => $e->getMessage()
            ];
        }

        // Clean up old external API logs (older than 3 months)
        try {
            $deletedApiLogs = DB::table('external_api_logs')
                ->where('created_at', '<', now()->subMonths(3))
                ->delete();
            
            $results[] = [
                'table' => 'external_api_logs',
                'action' => 'cleanup',
                'deleted_records' => $deletedApiLogs
            ];
        } catch (\Exception $e) {
            $results[] = [
                'table' => 'external_api_logs',
                'action' => 'cleanup_failed',
                'error' => $e->getMessage()
            ];
        }

        return $results;
    }

    /**
     * Get database health report
     */
    public function getHealthReport(): array
    {
        return [
            'timestamp' => now()->toISOString(),
            'performance_metrics' => $this->getPerformanceMetrics(),
            'table_sizes' => $this->getTableSizes(),
            'slow_queries' => $this->getSlowQueries(),
            'optimization_suggestions' => $this->getOptimizationSuggestions(),
            'health_score' => $this->calculateHealthScore(),
        ];
    }

    /**
     * Calculate database health score
     */
    private function calculateHealthScore(): int
    {
        $score = 100;
        $metrics = $this->getPerformanceMetrics();

        // Deduct points for slow queries
        if ($metrics['slow_queries'] > 100) {
            $score -= 20;
        } elseif ($metrics['slow_queries'] > 50) {
            $score -= 10;
        }

        // Deduct points for low buffer pool hit rate
        if ($metrics['innodb_buffer_pool_hit_rate'] < 90) {
            $score -= 15;
        } elseif ($metrics['innodb_buffer_pool_hit_rate'] < 95) {
            $score -= 10;
        }

        // Deduct points for low query cache hit rate
        if ($metrics['query_cache_hit_rate'] < 80) {
            $score -= 10;
        }

        return max(0, $score);
    }
}
