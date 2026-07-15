<?php

namespace Tests\Feature;

use App\Services\System\CatalystImportConsoleService;
use Mockery;
use Tests\TestCase;

class CatalystImportControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
    }

    public function test_apply_background_action_requires_exact_confirmation(): void
    {
        $service = Mockery::mock(CatalystImportConsoleService::class);
        $service->shouldReceive('definition')
            ->once()
            ->with('migration_full_apply')
            ->andReturn([
                'label' => 'Backup + Full Migration Apply',
                'execution' => 'background',
                'requires_confirmation' => true,
                'confirmation_value' => 'MIGRASI',
            ]);
        $service->shouldNotReceive('launchBackground');
        $service->shouldNotReceive('run');

        $this->app->instance(CatalystImportConsoleService::class, $service);

        $response = $this->from('/system/catalyst-import')->post(route('system.catalyst-import.run'), [
            'action' => 'migration_full_apply',
            'confirmation' => 'salah',
        ]);

        $response->assertRedirect('/system/catalyst-import');
        $response->assertSessionHas('error');
    }

    public function test_background_action_is_dispatched_without_waiting_for_http_request(): void
    {
        $service = Mockery::mock(CatalystImportConsoleService::class);
        $service->shouldReceive('definition')
            ->once()
            ->with('migration_full_dry_run')
            ->andReturn([
                'label' => 'Full Migration Dry Run',
                'execution' => 'background',
            ]);
        $service->shouldReceive('isBackgroundAction')
            ->once()
            ->with('migration_full_dry_run')
            ->andReturn(true);
        $service->shouldReceive('launchBackground')
            ->once()
            ->with('migration_full_dry_run', null)
            ->andReturn([
                'run_id' => 17,
                'label' => 'Full Migration Dry Run',
            ]);
        $service->shouldNotReceive('run');

        $this->app->instance(CatalystImportConsoleService::class, $service);

        $response = $this->from('/system/catalyst-import')->post(route('system.catalyst-import.run'), [
            'action' => 'migration_full_dry_run',
        ]);

        $response->assertRedirect('/system/catalyst-import');
        $response->assertSessionHas('success');
    }

    public function test_sync_action_keeps_existing_command_result_flash(): void
    {
        $service = Mockery::mock(CatalystImportConsoleService::class);
        $service->shouldReceive('definition')
            ->once()
            ->with('check_source_connection')
            ->andReturn([
                'label' => 'Check Source Connection',
                'execution' => 'sync',
            ]);
        $service->shouldReceive('isBackgroundAction')
            ->once()
            ->with('check_source_connection')
            ->andReturn(false);
        $service->shouldReceive('run')
            ->once()
            ->with('check_source_connection')
            ->andReturn([
                'label' => 'Check Source Connection',
                'successful' => true,
                'duration_ms' => 1200,
                'output' => 'ok',
            ]);

        $this->app->instance(CatalystImportConsoleService::class, $service);

        $response = $this->from('/system/catalyst-import')->post(route('system.catalyst-import.run'), [
            'action' => 'check_source_connection',
        ]);

        $response->assertRedirect('/system/catalyst-import');
        $response->assertSessionHas('success');
        $response->assertSessionHas('catalyst_command_result');
    }
}
