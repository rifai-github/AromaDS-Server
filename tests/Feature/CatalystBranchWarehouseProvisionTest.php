<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Warehouse;
use App\Services\Imports\Catalyst\CatalystMasterDataImporter;
use App\Services\System\CatalystMigrationExecutor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Tests\TestCase;

/**
 * The Catalyst import no longer brings warehouses over from MsWarehouse; the
 * warehouse master is generated from the branch master right after the
 * `branches` step. Covers that hook in isolation — the step itself needs a live
 * SQL Server source, which the test suite has no access to.
 */
class CatalystBranchWarehouseProvisionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->string('address_1')->nullable();
            $table->string('phone_1')->nullable();
            $table->boolean('is_head_office')->default(false);
            $table->boolean('has_warehouse')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('delete_by')->nullable();
            $table->timestamp('delete_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('warehouse_code')->nullable();
            $table->string('name')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->foreignId('warehouse_type_id')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('manager')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_center')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouse_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // The importer stamps created_by/updated_by via actorId(), which falls
        // back to the first user row when nobody is authenticated.
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('source_import_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->string('source_system')->nullable();
            $table->string('step')->nullable();
            $table->string('level')->nullable();
            $table->string('source_table')->nullable();
            $table->string('source_key')->nullable();
            $table->string('target_table')->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->text('message')->nullable();
            $table->text('context')->nullable();
            $table->timestamps();
        });
    }

    public function test_apply_run_generates_one_warehouse_per_branch(): void
    {
        $this->makeBranch(['code' => 'JKT', 'name' => 'Jakarta', 'is_head_office' => true]);
        $this->makeBranch(['code' => 'SBY', 'name' => 'Surabaya']);

        $created = $this->provision(apply: true, activeSteps: ['branches', 'departments']);

        $this->assertSame(2, $created);
        $this->assertSame(2, Warehouse::count());
        $this->assertTrue(Warehouse::where('name', 'Gudang Jakarta')->first()->is_center);
        $this->assertFalse(Warehouse::where('name', 'Gudang Surabaya')->first()->is_center);
        $this->assertSame(1, DB::table('source_import_logs')->where('step', 'branches')->count());
    }

    public function test_dry_run_writes_nothing(): void
    {
        $this->makeBranch(['code' => 'JKT', 'name' => 'Jakarta']);

        $this->assertSame(0, $this->provision(apply: false, activeSteps: ['branches']));
        $this->assertSame(0, Warehouse::count());
    }

    public function test_skipped_when_catalyst_warehouse_step_runs_in_the_same_batch(): void
    {
        $this->makeBranch(['code' => 'JKT', 'name' => 'Jakarta']);

        $created = $this->provision(apply: true, activeSteps: ['branches', 'warehouses']);

        $this->assertSame(0, $created);
        $this->assertSame(0, Warehouse::count());
    }

    public function test_branches_that_already_have_a_warehouse_are_left_alone(): void
    {
        $branch = $this->makeBranch(['code' => 'BDG', 'name' => 'Bandung']);
        Warehouse::create([
            'warehouse_code' => 'WH-MANUAL',
            'name' => 'Gudang Spare Part Bandung',
            'branch_id' => $branch->id,
        ]);

        $this->assertSame(0, $this->provision(apply: true, activeSteps: ['branches']));
        $this->assertSame(1, Warehouse::where('branch_id', $branch->id)->count());
    }

    public function test_inactive_branches_are_not_provisioned_by_the_import_hook(): void
    {
        $this->makeBranch(['code' => 'OLD', 'name' => 'Cabang Tutup', 'is_active' => false]);

        $this->assertSame(0, $this->provision(apply: true, activeSteps: ['branches']));
        $this->assertSame(0, Warehouse::count());
    }

    public function test_full_migration_step_list_keeps_branches_and_drops_catalyst_warehouses(): void
    {
        // CatalystMigrationExecutor::runFullImport() calls run() with no
        // requested steps and its own exclude list, so the resolved step list is
        // what decides whether the branch hook fires during a Full Migration.
        $importer = app(CatalystMasterDataImporter::class);
        $reflection = new ReflectionClass($importer);

        // Read the executor's real exclude list instead of restating it here.
        $executor = new ReflectionClass(CatalystMigrationExecutor::class);
        $excluded = array_values(array_unique(array_merge(
            $executor->getConstant('EXCLUDED_STEPS'),
            CatalystMasterDataImporter::DISABLED_STEPS,
        )));

        $resolve = $reflection->getMethod('resolveSteps');
        $resolve->setAccessible(true);

        $steps = $resolve->invoke($importer, [], true, $excluded);

        $this->assertContains('branches', $steps);
        $this->assertNotContains('warehouses', $steps);
        $this->assertNotContains('warehouse_types', $steps);
    }

    private function provision(bool $apply, array $activeSteps): int
    {
        $importer = app(CatalystMasterDataImporter::class);
        $reflection = new ReflectionClass($importer);

        foreach (['apply' => $apply, 'activeSteps' => $activeSteps, 'batchId' => 1] as $property => $value) {
            $prop = $reflection->getProperty($property);
            $prop->setAccessible(true);
            $prop->setValue($importer, $value);
        }

        $method = $reflection->getMethod('provisionWarehousesFromBranches');
        $method->setAccessible(true);

        return $method->invoke($importer);
    }

    private function makeBranch(array $attributes): Branch
    {
        return Branch::create(array_merge([
            'is_head_office' => false,
            'has_warehouse' => false,
            'is_active' => true,
        ], $attributes));
    }
}
