<?php

namespace Tests\Unit;

use App\Services\System\CatalystImportConsoleService;
use App\Services\System\CatalystMigrationRunService;
use Tests\TestCase;

class CatalystImportConsoleServiceTest extends TestCase
{
    public function test_migration_actions_are_exposed_with_expected_metadata(): void
    {
        $service = new CatalystImportConsoleService(app(CatalystMigrationRunService::class));

        $apply = $service->definition('migration_full_apply');
        $dryRun = $service->definition('migration_full_dry_run');
        $check = $service->definition('check_source_connection');
        $jobAdviceDryRun = $service->definition('dry_run_job_advices');
        $jobAdviceApply = $service->definition('apply_job_advices');

        $this->assertSame('background', $apply['execution']);
        $this->assertTrue($apply['requires_confirmation']);
        $this->assertSame('MIGRASI', $apply['confirmation_value']);

        $this->assertSame('background', $dryRun['execution']);
        $this->assertSame('sync', $check['execution']);
        $this->assertSame('sync', $jobAdviceDryRun['execution']);
        $this->assertSame('sync', $jobAdviceApply['execution']);
        $this->assertStringContainsString('contract atau quotation', $jobAdviceApply['description']);
        $this->assertStringContainsString('--step=job_advices', implode(' ', $jobAdviceApply['commands'][0]));
        $this->assertStringContainsString('--apply', implode(' ', $jobAdviceApply['commands'][0]));
    }
}
