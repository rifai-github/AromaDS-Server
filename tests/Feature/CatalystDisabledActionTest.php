<?php

namespace Tests\Feature;

use App\Services\Imports\Catalyst\CatalystMasterDataImporter;
use App\Services\System\CatalystImportConsoleService;
use ReflectionClass;
use RuntimeException;
use Tests\TestCase;

/**
 * Product Structure, Produk, dan Rental bersumber dari Master Product.xlsx,
 * bukan Catalyst. Test ini mengunci sakelar yang mematikannya.
 */
class CatalystDisabledActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
    }

    private function resolveSteps(array $requested, bool $withDependencies = true): array
    {
        $importer = app(CatalystMasterDataImporter::class);
        $method = (new ReflectionClass($importer))->getMethod('resolveSteps');
        $method->setAccessible(true);

        return $method->invoke($importer, $requested, $withDependencies, []);
    }

    public function test_every_disabled_step_is_refused_with_an_explanatory_message(): void
    {
        foreach (CatalystMasterDataImporter::DISABLED_STEPS as $step) {
            try {
                $this->resolveSteps([$step]);
                $this->fail("Step [$step] seharusnya ditolak, tapi malah lolos.");
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('sengaja dimatikan', $e->getMessage());
                $this->assertStringContainsString($step, $e->getMessage());
            }
        }
    }

    public function test_exact_steps_mode_also_refuses_disabled_steps(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/sengaja dimatikan/');

        $this->resolveSteps(['rental_details'], false);
    }

    public function test_full_run_never_includes_a_disabled_step(): void
    {
        $steps = $this->resolveSteps([]);

        $this->assertNotEmpty($steps);
        $this->assertSame(
            [],
            array_values(array_intersect($steps, CatalystMasterDataImporter::DISABLED_STEPS)),
            'Full run tidak boleh membawa step yang dimatikan.'
        );
    }

    public function test_dependent_step_still_runs_without_dragging_in_a_disabled_one(): void
    {
        $steps = $this->resolveSteps(['contract_rentals']);

        $this->assertContains('contract_rentals', $steps);
        $this->assertNotContains('master_rentals', $steps);
    }

    public function test_console_actions_touching_disabled_work_are_flagged_automatically(): void
    {
        $service = app(CatalystImportConsoleService::class);

        $expected = [
            'apply_master_rentals',
            'apply_repair_merged_rental_details',
            'backfill_product_relations',
            'backfill_product_warehouses',
            'backfill_rental_details',
            'backfill_rental_material_options',
            'bootstrap_fresh_database',
            'dry_run_master_rentals',
            'dry_run_repair_merged_rental_details',
            'normalize_rental_detail_duplicates',
            'post_import_sync',
        ];

        $actual = [];
        foreach ($service->actions() as $key => $action) {
            if (!empty($action['disabled'])) {
                $actual[] = $key;
            }
        }
        sort($actual);

        $this->assertSame($expected, $actual);
    }

    public function test_read_only_and_unrelated_actions_stay_enabled(): void
    {
        $service = app(CatalystImportConsoleService::class);

        foreach (['apply_users', 'apply_job_advices', 'apply_system_core', 'export_rental_materials', 'export_warehouse_links'] as $action) {
            $this->assertNull(
                $service->disabledReason($action),
                "Action [$action] seharusnya tetap aktif."
            );
        }
    }

    public function test_controller_refuses_a_disabled_action_without_running_anything(): void
    {
        $response = $this->from('/system/catalyst-import')
            ->post(route('system.catalyst-import.run'), ['action' => 'apply_master_rentals']);

        $response->assertRedirect('/system/catalyst-import');
        $response->assertSessionHas('error');
        $response->assertSessionMissing('catalyst_command_result');
        $this->assertStringContainsString('Master Product.xlsx', (string) session('error'));
    }

    public function test_controller_refuses_a_backfill_action_that_bypasses_the_importer(): void
    {
        $response = $this->from('/system/catalyst-import')
            ->post(route('system.catalyst-import.run'), ['action' => 'post_import_sync']);

        $response->assertRedirect('/system/catalyst-import');
        $response->assertSessionHas('error');
        $response->assertSessionMissing('catalyst_command_result');
    }
}
