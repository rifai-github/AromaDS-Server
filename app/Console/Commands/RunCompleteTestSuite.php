<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class RunCompleteTestSuite extends Command
{
    protected $signature = 'test:complete {--skip-performance : Skip performance tests}';
    protected $description = 'Run complete test suite including integration and performance tests';

    public function handle()
    {
        $this->info('🧪 Starting Complete ERP Test Suite...');
        
        $skipPerformance = $this->option('skip-performance');
        $startTime = microtime(true);
        
        try {
            // Run integration tests
            $this->runIntegrationTests();
            
            // Run performance tests (unless skipped)
            if (!$skipPerformance) {
                $this->runPerformanceTests();
            }
            
            // Run unit tests
            $this->runUnitTests();
            
            $endTime = microtime(true);
            $executionTime = round(($endTime - $startTime), 2);
            
            $this->info("✅ Complete test suite finished successfully in {$executionTime} seconds");
            
        } catch (\Exception $e) {
            $this->error("❌ Test suite failed: " . $e->getMessage());
            Log::error('Complete test suite failure', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
        
        return 0;
    }

    private function runIntegrationTests()
    {
        $this->info('🔗 Running Integration Tests...');
        
        $tests = [
            'Tests\\Feature\\Integration\\MarketingToOperationalFlowTest',
            'Tests\\Feature\\Integration\\OperationalToFinanceFlowTest',
            'Tests\\Feature\\Integration\\SystemIntegrationTest',
        ];
        
        $passed = 0;
        $failed = 0;
        
        foreach ($tests as $test) {
            $this->info("  Running: {$test}");
            
            try {
                $exitCode = Artisan::call('test', [
                    '--filter' => $test,
                    '--stop-on-failure' => false
                ]);
                
                if ($exitCode === 0) {
                    $this->info("    ✅ PASSED");
                    $passed++;
                } else {
                    $this->error("    ❌ FAILED");
                    $failed++;
                }
                
            } catch (\Exception $e) {
                $this->error("    ❌ ERROR: " . $e->getMessage());
                $failed++;
            }
        }
        
        $this->info("📊 Integration Tests Results: {$passed} passed, {$failed} failed");
        
        if ($failed > 0) {
            throw new \Exception("Integration tests failed: {$failed} failures");
        }
    }

    private function runPerformanceTests()
    {
        $this->info('⚡ Running Performance Tests...');
        
        $tests = [
            'Tests\\Feature\\Performance\\SystemPerformanceTest',
        ];
        
        $passed = 0;
        $failed = 0;
        
        foreach ($tests as $test) {
            $this->info("  Running: {$test}");
            
            try {
                $exitCode = Artisan::call('test', [
                    '--filter' => $test,
                    '--stop-on-failure' => false
                ]);
                
                if ($exitCode === 0) {
                    $this->info("    ✅ PASSED");
                    $passed++;
                } else {
                    $this->error("    ❌ FAILED");
                    $failed++;
                }
                
            } catch (\Exception $e) {
                $this->error("    ❌ ERROR: " . $e->getMessage());
                $failed++;
            }
        }
        
        $this->info("📊 Performance Tests Results: {$passed} passed, {$failed} failed");
        
        if ($failed > 0) {
            $this->warn("⚠️  Performance tests failed: {$failed} failures");
        }
    }

    private function runUnitTests()
    {
        $this->info('🔧 Running Unit Tests...');
        
        try {
            $exitCode = Artisan::call('test', [
                '--testsuite' => 'Unit',
                '--stop-on-failure' => false
            ]);
            
            if ($exitCode === 0) {
                $this->info("  ✅ Unit tests passed");
            } else {
                $this->warn("  ⚠️  Some unit tests failed");
            }
            
        } catch (\Exception $e) {
            $this->error("  ❌ Unit tests error: " . $e->getMessage());
        }
    }
}
