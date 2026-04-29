<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RunPerformanceTests extends Command
{
    protected $signature = 'test:performance {--threshold=1000 : Performance threshold in milliseconds}';
    protected $description = 'Run performance tests for ERP system';

    public function handle()
    {
        $this->info('⚡ Starting ERP Performance Tests...');
        
        $threshold = (int) $this->option('threshold');
        $startTime = microtime(true);
        
        try {
            $this->runPerformanceTestSuite($threshold);
            
            $endTime = microtime(true);
            $executionTime = round(($endTime - $startTime), 2);
            
            $this->info("✅ Performance tests completed successfully in {$executionTime} seconds");
            
        } catch (\Exception $e) {
            $this->error("❌ Performance tests failed: " . $e->getMessage());
            Log::error('Performance test failure', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
        
        return 0;
    }

    private function runPerformanceTestSuite($threshold)
    {
        $this->info("🎯 Running Performance Tests (Threshold: {$threshold}ms)...");
        
        $tests = [
            'Tests\\Feature\\Performance\\SystemPerformanceTest::customer_list_loads_within_acceptable_time',
            'Tests\\Feature\\Performance\\SystemPerformanceTest::contract_list_loads_within_acceptable_time',
            'Tests\\Feature\\Performance\\SystemPerformanceTest::job_schedule_list_loads_within_acceptable_time',
            'Tests\\Feature\\Performance\\SystemPerformanceTest::invoice_list_loads_within_acceptable_time',
            'Tests\\Feature\\Performance\\SystemPerformanceTest::dashboard_loads_within_acceptable_time',
            'Tests\\Feature\\Performance\\SystemPerformanceTest::search_performance_is_acceptable',
            'Tests\\Feature\\Performance\\SystemPerformanceTest::filtering_performance_is_acceptable',
            'Tests\\Feature\\Performance\\SystemPerformanceTest::pagination_performance_is_acceptable',
            'Tests\\Feature\\Performance\\SystemPerformanceTest::database_query_performance_is_acceptable',
            'Tests\\Feature\\Performance\\SystemPerformanceTest::memory_usage_is_acceptable',
            'Tests\\Feature\\Performance\\SystemPerformanceTest::cache_performance_is_acceptable',
            'Tests\\Feature\\Performance\\SystemPerformanceTest::concurrent_request_performance_is_acceptable',
        ];
        
        $passed = 0;
        $failed = 0;
        $performanceResults = [];
        
        foreach ($tests as $test) {
            $this->info("  Running: {$test}");
            
            try {
                $startTime = microtime(true);
                
                $exitCode = Artisan::call('test', [
                    '--filter' => $test,
                    '--stop-on-failure' => false
                ]);
                
                $endTime = microtime(true);
                $testTime = round(($endTime - $startTime) * 1000, 2);
                
                if ($exitCode === 0) {
                    $this->info("    ✅ PASSED ({$testTime}ms)");
                    $passed++;
                } else {
                    $this->error("    ❌ FAILED ({$testTime}ms)");
                    $failed++;
                }
                
                $performanceResults[] = [
                    'test' => $test,
                    'time' => $testTime,
                    'status' => $exitCode === 0 ? 'PASSED' : 'FAILED'
                ];
                
            } catch (\Exception $e) {
                $this->error("    ❌ ERROR: " . $e->getMessage());
                $failed++;
            }
        }
        
        $this->displayPerformanceResults($performanceResults, $threshold);
        
        if ($failed > 0) {
            throw new \Exception("Performance tests failed: {$failed} failures");
        }
    }

    private function displayPerformanceResults($results, $threshold)
    {
        $this->info("\n📊 Performance Test Results:");
        $this->info("┌─────────────────────────────────────────────────────────────────┐");
        $this->info("│ Test Name                                                    │ Time │ Status │");
        $this->info("├─────────────────────────────────────────────────────────────────┤");
        
        foreach ($results as $result) {
            $testName = substr($result['test'], strrpos($result['test'], '::') + 2);
            $testName = str_pad($testName, 50);
            $time = str_pad($result['time'] . 'ms', 6);
            $status = str_pad($result['status'], 7);
            
            $this->info("│ {$testName} │ {$time} │ {$status} │");
        }
        
        $this->info("└─────────────────────────────────────────────────────────────────┘");
        
        // Performance summary
        $avgTime = round(array_sum(array_column($results, 'time')) / count($results), 2);
        $maxTime = max(array_column($results, 'time'));
        $minTime = min(array_column($results, 'time'));
        
        $this->info("\n📈 Performance Summary:");
        $this->info("  Average Time: {$avgTime}ms");
        $this->info("  Maximum Time: {$maxTime}ms");
        $this->info("  Minimum Time: {$minTime}ms");
        $this->info("  Threshold: {$threshold}ms");
        
        if ($maxTime > $threshold) {
            $this->warn("  ⚠️  Some tests exceeded the performance threshold!");
        } else {
            $this->info("  ✅ All tests met the performance threshold!");
        }
    }
}
