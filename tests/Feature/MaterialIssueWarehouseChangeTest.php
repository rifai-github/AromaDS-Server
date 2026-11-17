<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobAssignMaterialIssueController;
use App\Models\JobAssignMaterialIssue;
use App\Models\MaterialIssue;
use App\Services\Operational\MaterialIssueWarehouseService;
use DomainException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Phase 3b of docs/warehouse-opsi3-implementation-plan.md: before a material
 * issue is issued, the admin may move it to another active warehouse of the
 * same branch; other branches, inactive warehouses and issued material issues
 * are refused.
 */
class MaterialIssueWarehouseChangeTest extends TestCase
{
    private int $defaultId;

    private int $secondId;

    private int $inactiveId;

    private int $otherBranchId;

    private int $materialIssueId;

    private int $jamiId;

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

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouse_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('master_product_id');
            $table->decimal('quantity', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_issues', function (Blueprint $table) {
            $table->id();
            $table->string('issue_number')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->string('status')->nullable();
            $table->text('metadata')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_issue_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('material_issue_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('room_name')->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_issuings', function (Blueprint $table) {
            $table->id();
            $table->string('issuing_number')->nullable();
            $table->string('reference_no')->nullable();
            $table->string('status')->nullable();
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

        Schema::create('job_assign_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_schedule_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('building_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $this->defaultId = DB::table('warehouses')->insertGetId(['branch_id' => 10, 'name' => 'Gudang Jakarta', 'is_active' => true, 'is_default' => true]);
        $this->secondId = DB::table('warehouses')->insertGetId(['branch_id' => 10, 'name' => 'Gudang BSD', 'is_active' => true]);
        $this->inactiveId = DB::table('warehouses')->insertGetId(['branch_id' => 10, 'name' => 'Gudang Lama', 'is_active' => false]);
        $this->otherBranchId = DB::table('warehouses')->insertGetId(['branch_id' => 20, 'name' => 'Gudang Surabaya', 'is_active' => true, 'is_default' => true]);

        $refill = DB::table('master_products')->insertGetId(['name' => 'Refill Lavender 500 ml']);
        $battery = DB::table('master_products')->insertGetId(['name' => 'Battery R6']);

        // Default warehouse is short on refill; the second warehouse has everything.
        DB::table('warehouse_products')->insert([
            ['warehouse_id' => $this->defaultId, 'master_product_id' => $refill, 'quantity' => 1],
            ['warehouse_id' => $this->defaultId, 'master_product_id' => $battery, 'quantity' => 10],
            ['warehouse_id' => $this->secondId, 'master_product_id' => $refill, 'quantity' => 5],
            ['warehouse_id' => $this->secondId, 'master_product_id' => $battery, 'quantity' => 4],
        ]);

        $this->materialIssueId = DB::table('material_issues')->insertGetId([
            'issue_number' => 'JKT-MI/26-11/0001',
            'warehouse_id' => $this->defaultId,
            'status' => 'approved',
        ]);
        DB::table('material_issue_items')->insert([
            ['material_issue_id' => $this->materialIssueId, 'product_id' => $refill, 'room_name' => 'Lobby', 'quantity' => 2],
            ['material_issue_id' => $this->materialIssueId, 'product_id' => $refill, 'room_name' => 'Ruang Meeting', 'quantity' => 1],
            ['material_issue_id' => $this->materialIssueId, 'product_id' => $battery, 'room_name' => 'Lobby', 'quantity' => 2],
        ]);

        $buildingId = DB::table('buildings')->insertGetId(['branch_id' => 10]);
        $jobId = DB::table('job_schedules')->insertGetId(['building_id' => $buildingId]);
        $assignId = DB::table('job_assign_schedules')->insertGetId(['job_schedule_id' => $jobId]);
        $this->jamiId = DB::table('job_assign_material_issues')->insertGetId([
            'material_issue_id' => $this->materialIssueId,
            'job_assign_schedule_id' => $assignId,
        ]);
    }

    private function service(): MaterialIssueWarehouseService
    {
        return app(MaterialIssueWarehouseService::class);
    }

    private function materialIssue(): MaterialIssue
    {
        return MaterialIssue::findOrFail($this->materialIssueId);
    }

    public function test_options_list_active_branch_warehouses_with_stock_per_product(): void
    {
        $options = $this->service()->options($this->materialIssue());

        $this->assertNull($options['lock_reason']);
        $this->assertSame($this->defaultId, $options['current_warehouse_id']);
        $this->assertSame([$this->defaultId, $this->secondId], array_column($options['warehouses'], 'id'));

        [$default, $second] = $options['warehouses'];
        $this->assertTrue($default['is_default']);
        $this->assertTrue($default['is_current']);
        $this->assertFalse($default['sufficient'], 'refill needs 3, default holds 1');
        $this->assertTrue($second['sufficient']);

        $refill = collect($default['items'])->firstWhere('product_name', 'Refill Lavender 500 ml');
        $this->assertEquals(3, $refill['required']);
        $this->assertEquals(1, $refill['stock']);
    }

    public function test_change_moves_the_material_issue_and_records_history(): void
    {
        $this->service()->change($this->materialIssue(), $this->secondId, null, 7);

        $materialIssue = $this->materialIssue();
        $this->assertSame($this->secondId, (int) $materialIssue->warehouse_id);
        $this->assertSame('approved', $materialIssue->status);

        $history = $materialIssue->metadata['warehouse_changes'];
        $this->assertCount(1, $history);
        $this->assertSame($this->defaultId, (int) $history[0]['from_warehouse_id']);
        $this->assertSame($this->secondId, (int) $history[0]['to_warehouse_id']);
        $this->assertSame(7, $history[0]['changed_by']);
    }

    public function test_change_rejects_a_warehouse_of_another_branch(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('bukan gudang cabang job ini');

        $this->service()->change($this->materialIssue(), $this->otherBranchId);
    }

    public function test_change_rejects_an_inactive_warehouse(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('tidak aktif');

        $this->service()->change($this->materialIssue(), $this->inactiveId);
    }

    public function test_issued_material_issue_is_locked(): void
    {
        DB::table('material_issues')->where('id', $this->materialIssueId)->update(['status' => 'issued']);

        $this->assertNotNull($this->service()->options($this->materialIssue())['lock_reason']);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('sudah berstatus issued');

        $this->service()->change($this->materialIssue(), $this->secondId);
    }

    public function test_material_issue_with_an_open_inventory_issuing_is_locked(): void
    {
        DB::table('inventory_issuings')->insert([
            'issuing_number' => 'JKT-WI/26-11/0001',
            'reference_no' => 'JKT-MI/26-11/0001',
            'status' => 'pending',
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('JKT-WI/26-11/0001');

        $this->service()->change($this->materialIssue(), $this->secondId);
    }

    public function test_cancelled_inventory_issuing_does_not_lock(): void
    {
        DB::table('inventory_issuings')->insert([
            'issuing_number' => 'JKT-WI/26-11/0001',
            'reference_no' => 'JKT-MI/26-11/0001',
            'status' => 'cancelled',
        ]);

        $this->service()->change($this->materialIssue(), $this->secondId);

        $this->assertSame($this->secondId, (int) $this->materialIssue()->warehouse_id);
    }

    public function test_change_warehouse_endpoint_returns_422_for_another_branch_and_200_for_same_branch(): void
    {
        $controller = app(JobAssignMaterialIssueController::class);
        $jami = JobAssignMaterialIssue::findOrFail($this->jamiId);

        $rejected = $controller->changeWarehouse(
            Request::create('/x', 'POST', ['warehouse_id' => $this->otherBranchId]),
            $jami
        );
        $this->assertSame(422, $rejected->getStatusCode());
        $this->assertSame($this->defaultId, (int) $this->materialIssue()->warehouse_id);

        $accepted = $controller->changeWarehouse(
            Request::create('/x', 'POST', ['warehouse_id' => $this->secondId]),
            JobAssignMaterialIssue::findOrFail($this->jamiId)
        );
        $this->assertSame(200, $accepted->getStatusCode(), $accepted->getContent());
        $this->assertSame($this->secondId, (int) $this->materialIssue()->warehouse_id);
    }

    public function test_manual_material_issue_create_honours_a_same_branch_pick_only(): void
    {
        $assignId = DB::table('job_assign_material_issues')->where('id', $this->jamiId)->value('job_assign_schedule_id');
        $method = new ReflectionMethod(JobAssignMaterialIssueController::class, 'resolveMaterialIssueWarehouse');
        $method->setAccessible(true);
        $controller = app(JobAssignMaterialIssueController::class);

        $resolve = fn (?int $warehouseId) => $method->invoke($controller, Request::create('/x', 'POST', [
            'job_assign_schedule_id' => $assignId,
            'warehouse_id' => $warehouseId,
        ]))->id;

        $this->assertSame($this->secondId, $resolve($this->secondId));
        $this->assertSame($this->defaultId, $resolve(null));
        $this->assertSame($this->defaultId, $resolve($this->otherBranchId), 'other branch falls back to the default');
        $this->assertSame($this->defaultId, $resolve($this->inactiveId), 'inactive falls back to the default');
    }
}
