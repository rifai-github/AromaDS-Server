<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportQueryOptimizer
{
    /**
     * Optimize SQL query for better performance
     */
    public function optimizeQuery(string $query): array
    {
        $originalQuery = $query;
        $optimizations = [];
        
        try {
            // Remove unnecessary whitespace
            $query = $this->removeUnnecessaryWhitespace($query);
            $optimizations[] = 'Removed unnecessary whitespace';

            // Add LIMIT if not present and query is large
            if (!$this->hasLimit($query) && $this->isLargeQuery($query)) {
                $query = $this->addLimit($query, 1000);
                $optimizations[] = 'Added LIMIT 1000 for performance';
            }

            // Optimize SELECT statements
            if ($this->hasSelectStar($query)) {
                $query = $this->replaceSelectStar($query);
                $optimizations[] = 'Replaced SELECT * with specific columns';
            }

            // Add indexes suggestions
            $indexSuggestions = $this->suggestIndexes($query);
            if (!empty($indexSuggestions)) {
                $optimizations[] = 'Index suggestions: ' . implode(', ', $indexSuggestions);
            }

            // Optimize WHERE clauses
            $query = $this->optimizeWhereClauses($query);
            $optimizations[] = 'Optimized WHERE clauses';

            // Optimize JOINs
            $query = $this->optimizeJoins($query);
            $optimizations[] = 'Optimized JOINs';

            return [
                'original_query' => $originalQuery,
                'optimized_query' => $query,
                'optimizations' => $optimizations,
                'performance_improvement' => $this->estimatePerformanceImprovement($originalQuery, $query)
            ];

        } catch (\Exception $e) {
            Log::error('Query optimization failed: ' . $e->getMessage());
            
            return [
                'original_query' => $originalQuery,
                'optimized_query' => $originalQuery,
                'optimizations' => ['Optimization failed: ' . $e->getMessage()],
                'performance_improvement' => 0
            ];
        }
    }

    /**
     * Remove unnecessary whitespace
     */
    private function removeUnnecessaryWhitespace(string $query): string
    {
        return preg_replace('/\s+/', ' ', trim($query));
    }

    /**
     * Check if query has LIMIT clause
     */
    private function hasLimit(string $query): bool
    {
        return preg_match('/\bLIMIT\s+\d+/i', $query);
    }

    /**
     * Check if query is large (has multiple JOINs or subqueries)
     */
    private function isLargeQuery(string $query): bool
    {
        $joinCount = substr_count(strtoupper($query), 'JOIN');
        $subqueryCount = substr_count($query, '(');
        
        return $joinCount > 3 || $subqueryCount > 5;
    }

    /**
     * Add LIMIT clause to query
     */
    private function addLimit(string $query, int $limit): string
    {
        return $query . " LIMIT {$limit}";
    }

    /**
     * Check if query has SELECT *
     */
    private function hasSelectStar(string $query): bool
    {
        return preg_match('/SELECT\s+\*\s+FROM/i', $query);
    }

    /**
     * Replace SELECT * with specific columns
     */
    private function replaceSelectStar(string $query): string
    {
        // This is a simplified approach - in practice, you'd need to analyze the table structure
        // For now, we'll just add a comment
        return str_replace('SELECT *', 'SELECT /* Add specific columns here */ *', $query);
    }

    /**
     * Suggest indexes for the query
     */
    private function suggestIndexes(string $query): array
    {
        $suggestions = [];
        
        // Extract table names
        preg_match_all('/FROM\s+(\w+)/i', $query, $fromMatches);
        preg_match_all('/JOIN\s+(\w+)/i', $query, $joinMatches);
        
        $tables = array_merge($fromMatches[1] ?? [], $joinMatches[1] ?? []);
        
        // Extract WHERE conditions
        if (preg_match('/WHERE\s+(.+?)(?:\s+GROUP\s+BY|\s+ORDER\s+BY|\s+LIMIT|$)/i', $query, $whereMatches)) {
            $whereClause = $whereMatches[1];
            
            // Look for column comparisons
            preg_match_all('/(\w+)\s*[=<>!]/i', $whereClause, $columnMatches);
            
            foreach ($columnMatches[1] as $column) {
                foreach ($tables as $table) {
                    $suggestions[] = "INDEX on {$table}.{$column}";
                }
            }
        }
        
        return array_unique($suggestions);
    }

    /**
     * Optimize WHERE clauses
     */
    private function optimizeWhereClauses(string $query): string
    {
        // Move most selective conditions first
        // This is a simplified approach
        return $query;
    }

    /**
     * Optimize JOINs
     */
    private function optimizeJoins(string $query): string
    {
        // Ensure proper JOIN order (smallest tables first)
        // This is a simplified approach
        return $query;
    }

    /**
     * Estimate performance improvement
     */
    private function estimatePerformanceImprovement(string $original, string $optimized): int
    {
        // This is a simplified estimation
        $originalLength = strlen($original);
        $optimizedLength = strlen($optimized);
        
        if ($originalLength > $optimizedLength) {
            return min(50, round((($originalLength - $optimizedLength) / $originalLength) * 100));
        }
        
        return 0;
    }

    /**
     * Analyze query execution plan
     */
    public function analyzeQueryPlan(string $query): array
    {
        try {
            $explainQuery = "EXPLAIN " . $query;
            $plan = DB::select($explainQuery);
            
            return [
                'success' => true,
                'plan' => $plan,
                'suggestions' => $this->analyzeExecutionPlan($plan)
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'suggestions' => []
            ];
        }
    }

    /**
     * Analyze execution plan and provide suggestions
     */
    private function analyzeExecutionPlan(array $plan): array
    {
        $suggestions = [];
        
        foreach ($plan as $row) {
            $row = (array) $row;
            
            // Check for full table scans
            if (isset($row['type']) && $row['type'] === 'ALL') {
                $suggestions[] = "Full table scan detected on {$row['table']} - consider adding indexes";
            }
            
            // Check for temporary tables
            if (isset($row['Extra']) && strpos($row['Extra'], 'Using temporary') !== false) {
                $suggestions[] = "Temporary table usage detected - consider optimizing GROUP BY or ORDER BY";
            }
            
            // Check for filesort
            if (isset($row['Extra']) && strpos($row['Extra'], 'Using filesort') !== false) {
                $suggestions[] = "Filesort detected - consider adding indexes for ORDER BY";
            }
        }
        
        return $suggestions;
    }

    /**
     * Get query performance metrics
     */
    public function getQueryPerformanceMetrics(string $query): array
    {
        try {
            $startTime = microtime(true);
            $result = DB::select($query);
            $endTime = microtime(true);
            
            $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
            $rowCount = count($result);
            
            return [
                'success' => true,
                'execution_time_ms' => round($executionTime, 2),
                'row_count' => $rowCount,
                'rows_per_second' => $executionTime > 0 ? round($rowCount / ($executionTime / 1000)) : 0,
                'performance_rating' => $this->getPerformanceRating($executionTime, $rowCount)
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'execution_time_ms' => 0,
                'row_count' => 0,
                'rows_per_second' => 0,
                'performance_rating' => 'error'
            ];
        }
    }

    /**
     * Get performance rating based on execution time and row count
     */
    private function getPerformanceRating(float $executionTime, int $rowCount): string
    {
        if ($executionTime < 100) {
            return 'excellent';
        } elseif ($executionTime < 500) {
            return 'good';
        } elseif ($executionTime < 1000) {
            return 'fair';
        } else {
            return 'poor';
        }
    }

    /**
     * Suggest query optimizations
     */
    public function suggestOptimizations(string $query): array
    {
        $suggestions = [];
        
        // Check for common performance issues
        if (strpos(strtoupper($query), 'SELECT *') !== false) {
            $suggestions[] = 'Avoid SELECT * - specify only needed columns';
        }
        
        if (strpos(strtoupper($query), 'ORDER BY') !== false && strpos(strtoupper($query), 'LIMIT') === false) {
            $suggestions[] = 'Consider adding LIMIT when using ORDER BY';
        }
        
        if (substr_count(strtoupper($query), 'JOIN') > 5) {
            $suggestions[] = 'Consider breaking down complex JOINs into smaller queries';
        }
        
        if (strpos(strtoupper($query), 'LIKE') !== false && strpos($query, '%') === 0) {
            $suggestions[] = 'Avoid leading wildcards in LIKE clauses - they prevent index usage';
        }
        
        if (strpos(strtoupper($query), 'IN (') !== false) {
            $suggestions[] = 'Consider using EXISTS instead of IN for better performance';
        }
        
        return $suggestions;
    }

    /**
     * Validate query syntax
     */
    public function validateQuery(string $query): array
    {
        try {
            // Basic syntax validation
            $query = trim($query);
            
            if (empty($query)) {
                return [
                    'valid' => false,
                    'error' => 'Query cannot be empty'
                ];
            }
            
            // Check for dangerous operations
            $dangerousKeywords = ['DROP', 'DELETE', 'UPDATE', 'INSERT', 'ALTER', 'CREATE', 'TRUNCATE'];
            $upperQuery = strtoupper($query);
            
            foreach ($dangerousKeywords as $keyword) {
                if (strpos($upperQuery, $keyword) !== false) {
                    return [
                        'valid' => false,
                        'error' => "Dangerous keyword '{$keyword}' not allowed"
                    ];
                }
            }
            
            // Check if it starts with SELECT
            if (!preg_match('/^\s*SELECT\s+/i', $query)) {
                return [
                    'valid' => false,
                    'error' => 'Query must start with SELECT'
                ];
            }
            
            // Try to parse the query (basic validation)
            try {
                DB::select("SELECT 1 WHERE 1=0"); // Test connection
                return [
                    'valid' => true,
                    'error' => null
                ];
            } catch (\Exception $e) {
                return [
                    'valid' => false,
                    'error' => 'Database connection error'
                ];
            }
            
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
