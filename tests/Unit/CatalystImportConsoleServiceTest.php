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

        $this->assertSame('background', $apply['execution']);
        $this->assertTrue($apply['requires_confirmation']);
        $this->assertSame('MIGRASI', $apply['confirmation_value']);

        $this->assertSame('background', $dryRun['execution']);
        $this->assertSame('sync', $check['execution']);
    }
}
