<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RunIntegrationTests extends Command
{
    protected $signature = 'test:integration {--flow=all : Specific flow to test (marketing-operational, operational-finance, system, all)}';
    protected $description = 'Run integration tests for ERP system flows';

    public function handle()
    {
        $this->info('🧪 Starting ERP Integration Tests...');
        
        $flow = $this->option('flow');
        $startTime = microtime(true);
        
        try {
            switch ($flow) {
                case 'marketing-operational':
                    $this->runMarketingToOperationalTests();
                    break;
                case 'operational-finance':
                    $this->runOperationalToFinanceTests();
                    break;
                case 'system':
                    $this->runSystemIntegrationTests();
                    break;
                case 'all':
                default:
                    $this->runAllIntegrationTests();
                    break;
            }
            
            $endTime = microtime(true);
            $executionTime = round(($endTime - $startTime), 2);
            
            $this->info("✅ Integration tests completed successfully in {$executionTime} seconds");
            
        } catch (\Exception $e) {
            $this->error("❌ Integration tests failed: " . $e->getMessage());
            Log::error('Integration test failure', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
        
        return 0;
    }

    private function runMarketingToOperationalTests()
    {
        $this->info('📋 Running Marketing → Operational Flow Tests...');
        
        $tests = [
            'Tests\\Feature\\Integration\\MarketingToOperationalFlowTest::complete_marketing_to_operational_flow_works',
            'Tests\\Feature\\Integration\\MarketingToOperationalFlowTest::job_advice_creation_updates_contract_status',
            'Tests\\Feature\\Integration\\MarketingToOperationalFlowTest::job_schedule_creation_updates_job_advice_status',
            'Tests\\Feature\\Integration\\MarketingToOperationalFlowTest::material_issue_creation_affects_inventory',
            'Tests\\Feature\\Integration\\MarketingToOperationalFlowTest::job_completion_triggers_invoice_generation',
        ];
        
        $this->runTestSuite($tests, 'Marketing → Operational Flow');
    }

    private function runOperationalToFinanceTests()
    {
        $this->info('💰 Running Operational → Finance Flow Tests...');
        
        $tests = [
            'Tests\\Feature\\Integration\\OperationalToFinanceFlowTest::complete_operational_to_finance_flow_works',
            'Tests\\Feature\\Integration\\OperationalToFinanceFlowTest::invoice_generation_updates_job_schedule_status',
            'Tests\\Feature\\Integration\\OperationalToFinanceFlowTest::virtual_account_creation_links_to_invoice',
            'Tests\\Feature\\Integration\\OperationalToFinanceFlowTest::bank_receipt_verification_updates_invoice_status',
            'Tests\\Feature\\Integration\\OperationalToFinanceFlowTest::payment_creation_affects_customer_balance',
        ];
        
        $this->runTestSuite($tests, 'Operational → Finance Flow');
    }

    private function runSystemIntegrationTests()
    {
        $this->info('🔧 Running System Integration Tests...');
        
        $tests = [
            'Tests\\Feature\\Integration\\SystemIntegrationTest::user_authentication_and_authorization_works',
            'Tests\\Feature\\Integration\\SystemIntegrationTest::audit_trail_tracks_all_operations',
            'Tests\\Feature\\Integration\\SystemIntegrationTest::login_history_tracks_user_sessions',
            'Tests\\Feature\\Integration\\SystemIntegrationTest::data_restriction_works_based_on_user_role',
            'Tests\\Feature\\Integration\\SystemIntegrationTest::system_handles_concurrent_user_operations',
        ];
        
        $this->runTestSuite($tests, 'System Integration');
    }

    private function runAllIntegrationTests()
    {
        $this->info('🚀 Running All Integration Tests...');
        
        $this->runMarketingToOperationalTests();
        $this->runOperationalToFinanceTests();
        $this->runSystemIntegrationTests();
        
        $this->info('🎉 All integration tests completed!');
    }

    private function runTestSuite(array $tests, string $suiteName)
    {
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
        
        $this->info("📊 {$suiteName} Results: {$passed} passed, {$failed} failed");
        
        if ($failed > 0) {
            throw new \Exception("{$suiteName} tests failed: {$failed} failures");
        }
    }
}
