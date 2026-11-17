<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobScheduleController;
use App\Http\Controllers\Warehouse\InventoryReceivingController;
use App\Models\Branch;
use App\Models\InventoryReceiving;
use App\Models\JobSchedule;
use App\Services\Operational\JobWebCompletionService;
use App\Services\OperationalAreaService;
use App\Services\Warehouse\BranchWarehouseResolver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use ReflectionProperty;
use Tests\TestCase;

/**
 * Phase 3 of docs/warehouse-opsi3-implementation-plan.md: every place where the
 * system picks a branch warehouse on its own must land on the branch's Gudang
 * Default, not on "the first active warehouse" (which is the lowest id or an
 * is_center row, and once was any warehouse of any branch).
 */
class BranchDefaultWarehouseFallbackTest extends TestCase
{
    private int $branchId = 10;

    private int $firstId;

    private int $defaultId;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_center')->default(false);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        foreach (['buildings', 'teams'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name) {
                $table->id();
                $table->unsignedBigInteger('branch_id')->nullable();
                if ($name === 'teams') {
                    $table->unsignedBigInteger('branch_office')->nullable();
                }
                $table->timestamps();
                $table->softDeletes();
            });
        }

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('building_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('job_number')->nullable();
            $table->string('type')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_assign_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_schedule_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_issues', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_assign_material_issues', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('material_issue_id')->nullable();
            $table->unsignedBigInteger('job_assign_schedule_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_issuings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_receivings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('issuing_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('reference_no')->nullable();
            $table->string('receiving_number')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_receiving_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Branch 10 runs three warehouses: the lowest id, an is_center one and
        // the default. Branch 20 is another branch with its own default.
        $this->firstId = $this->warehouse($this->branchId, 'Gudang A');
        $this->warehouse($this->branchId, 'Gudang Center', ['is_center' => true]);
        $this->defaultId = $this->warehouse($this->branchId, 'Gudang Default', ['is_default' => true]);
        $this->warehouse(20, 'Gudang cabang lain', ['is_default' => true]);

        $this->resetOperationalAreaCache();
    }

    private function warehouse(int $branchId, string $name, array $extra = []): int
    {
        return DB::table('warehouses')->insertGetId(array_merge([
            'branch_id' => $branchId,
            'name' => $name,
            'is_active' => true,
        ], $extra));
    }

    private function resetOperationalAreaCache(): void
    {
        $cache = new ReflectionProperty(OperationalAreaService::class, 'warehouseByBranch');
        $cache->setAccessible(true);
        $cache->setValue(null, []);
    }

    private function invokePrivate(object $target, string $method, ...$args)
    {
        $reflection = new ReflectionMethod($target, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($target, ...$args);
    }

    public function test_lenient_resolver_returns_the_default_of_the_branch(): void
    {
        $resolver = app(BranchWarehouseResolver::class);

        $this->assertSame($this->defaultId, $resolver->findDefaultForBranch($this->branchId)?->id);
        $this->assertNull($resolver->findDefaultForBranch(null));
        $this->assertNull($resolver->findDefaultForBranch(999));
    }

    public function test_lenient_resolver_falls_back_to_lowest_id_when_no_default_is_flagged(): void
    {
        DB::table('warehouses')->update(['is_default' => false]);

        $this->assertSame($this->firstId, app(BranchWarehouseResolver::class)->findDefaultForBranch($this->branchId)?->id);
    }

    public function test_operational_area_service_uses_the_default_warehouse(): void
    {
        $branch = (new Branch)->forceFill(['id' => $this->branchId]);

        $this->assertSame($this->defaultId, OperationalAreaService::resolveWarehouseForBranch($branch)?->id);
    }

    public function test_remove_job_receiving_falls_back_to_the_default_warehouse(): void
    {
        $buildingId = DB::table('buildings')->insertGetId(['branch_id' => $this->branchId]);
        $jobId = DB::table('job_schedules')->insertGetId(['building_id' => $buildingId, 'type' => 'remove']);

        $warehouse = $this->invokePrivate(
            app(JobScheduleController::class),
            'resolveRemoveUnitReceivingWarehouse',
            JobSchedule::find($jobId),
            null
        );

        $this->assertSame($this->defaultId, $warehouse?->id);
    }

    public function test_web_completion_without_material_issue_uses_the_team_branch_default(): void
    {
        $teamId = DB::table('teams')->insertGetId(['branch_office' => $this->branchId]);
        $jobId = DB::table('job_schedules')->insertGetId(['type' => 'service']);

        $warehouse = $this->invokePrivate(
            app(JobWebCompletionService::class),
            'resolvePartialCompletionWarehouse',
            JobSchedule::find($jobId),
            \App\Models\Team::find($teamId)
        );

        $this->assertSame($this->defaultId, $warehouse?->id);
    }

    public function test_web_completion_still_prefers_the_material_issue_warehouse(): void
    {
        $teamId = DB::table('teams')->insertGetId(['branch_office' => $this->branchId]);
        $jobId = DB::table('job_schedules')->insertGetId(['type' => 'service']);
        $assignId = DB::table('job_assign_schedules')->insertGetId(['job_schedule_id' => $jobId, 'team_id' => $teamId]);
        $issueId = DB::table('material_issues')->insertGetId(['warehouse_id' => $this->firstId]);
        DB::table('job_assign_material_issues')->insert(['material_issue_id' => $issueId, 'job_assign_schedule_id' => $assignId]);

        $warehouse = $this->invokePrivate(
            app(JobWebCompletionService::class),
            'resolvePartialCompletionWarehouse',
            JobSchedule::find($jobId),
            \App\Models\Team::find($teamId)
        );

        $this->assertSame($this->firstId, $warehouse?->id);
    }

    public function test_receiving_without_any_warehouse_hint_lands_in_the_default(): void
    {
        $receivingId = DB::table('inventory_receivings')->insertGetId([
            'branch_id' => $this->branchId,
            'receiving_number' => 'JKT-RCV/26-11/0001',
            'status' => 'pending',
        ]);

        $warehouse = $this->invokePrivate(
            app(InventoryReceivingController::class),
            'resolveReceivingTargetWarehouse',
            InventoryReceiving::find($receivingId)
        );

        $this->assertSame($this->defaultId, $warehouse?->id);
    }

    public function test_receiving_from_an_issuing_keeps_the_issuing_warehouse(): void
    {
        $issuingId = DB::table('inventory_issuings')->insertGetId(['warehouse_id' => $this->firstId]);
        $receivingId = DB::table('inventory_receivings')->insertGetId([
            'issuing_id' => $issuingId,
            'branch_id' => $this->branchId,
            'status' => 'pending',
        ]);

        $warehouse = $this->invokePrivate(
            app(InventoryReceivingController::class),
            'resolveReceivingTargetWarehouse',
            InventoryReceiving::find($receivingId)
        );

        $this->assertSame($this->firstId, $warehouse?->id);
    }
}
