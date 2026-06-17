<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobAssignMaterialIssueController;
use App\Models\JobAssignMaterialIssue;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class SubmitIssueGroupedSelectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('master_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->nullable();
            $table->foreignId('job_advice_id')->nullable();
            $table->foreignId('building_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->string('room_name')->nullable();
            $table->string('type')->nullable();
            $table->integer('period')->nullable();
            $table->string('status')->nullable();
            $table->boolean('material_checked')->default(false);
            $table->timestamp('material_checked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedule_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->foreignId('job_advice_room_id')->nullable();
            $table->string('room_name')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_assign_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_issues', function (Blueprint $table) {
            $table->id();
            $table->string('issue_number')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('packaging_sizes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_type_id')->nullable();
            $table->foreignId('packaging_size_id')->nullable();
            $table->string('name')->nullable();
            $table->decimal('bom_quantity', 10, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouse_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('rental_details', function (Blueprint $table) {
            $table->id();
            $table->decimal('bom_rental_qty', 10, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_issue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_issue_id')->nullable();
            $table->foreignId('job_assign_schedule_id')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->string('room_name')->nullable();
            $table->integer('quantity')->nullable();
            $table->decimal('bom_quantity', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_assign_material_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_assign_schedule_id')->nullable();
            $table->foreignId('material_issue_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        foreach ([
            'job_assign_material_issues',
            'material_issue_items',
            'rental_details',
            'warehouse_products',
            'warehouses',
            'master_products',
            'packaging_sizes',
            'product_types',
            'material_issues',
            'job_assign_schedules',
            'job_schedule_rooms',
            'job_schedules',
            'master_rooms',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_material_checked_check_room_without_material_does_not_block_submit_issue(): void
    {
        $now = now();

        DB::table('master_rooms')->insert([
            ['id' => 10, 'room_name' => 'Lobby', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 11, 'room_name' => 'VIP Room', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('job_schedules')->insert([
            [
                'id' => 1,
                'job_number' => 'JKT-CSR/26-05/0003',
                'job_advice_id' => 99,
                'building_id' => 7,
                'room_id' => 10,
                'room_name' => 'Lobby',
                'type' => 'service_first',
                'period' => 1,
                'status' => 'assign_material',
                'material_checked' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'job_number' => 'JKT-CSR/26-05/0003',
                'job_advice_id' => 99,
                'building_id' => 7,
                'room_id' => 11,
                'room_name' => 'VIP Room',
                'type' => 'service_first',
                'period' => 1,
                'status' => 'assign_material',
                'material_checked' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('job_assign_schedules')->insert([
            'id' => 20,
            'job_schedule_id' => 2,
            'status' => 'assigned',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('material_issues')->insert([
            'id' => 30,
            'issue_number' => 'JKT-MI/26-05/0001',
            'status' => 'pending',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('job_assign_material_issues')->insert([
            'id' => 40,
            'job_assign_schedule_id' => 20,
            'material_issue_id' => 30,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $errors = $this->validateSelectedIssues([40]);

        $this->assertSame([], $errors);
    }

    public function test_stock_validation_uses_total_needed_for_same_product(): void
    {
        $now = now();

        DB::table('warehouses')->insert([
            'id' => 1,
            'name' => 'Gudang Surabaya',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('material_issues')->insert([
            'id' => 30,
            'issue_number' => 'SBY-MI/26-06/0011',
            'warehouse_id' => 1,
            'status' => 'approved',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('master_products')->insert([
            'id' => 60,
            'name' => 'All Purpose Cleaner 100 ml',
            'bom_quantity' => 100,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('warehouse_products')->insert([
            'warehouse_id' => 1,
            'master_product_id' => 60,
            'quantity' => 5,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('material_issue_items')->insert([
            [
                'material_issue_id' => 30,
                'product_id' => 60,
                'room_name' => 'Lobby',
                'quantity' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'material_issue_id' => 30,
                'product_id' => 60,
                'room_name' => 'Meeting VIP',
                'quantity' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('job_assign_material_issues')->insert([
            'id' => 40,
            'material_issue_id' => 30,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $result = $this->checkStockForRentalItems(40);

        $this->assertFalse($result['can_fulfill']);
        $this->assertStringContainsString('Total butuh: 6', implode("\n", $result['warnings']));
        $this->assertStringContainsString('Stock: 5', implode("\n", $result['warnings']));
    }

    public function test_qty_update_reopens_out_of_stock_material_issue(): void
    {
        $now = now();

        DB::table('warehouses')->insert([
            'id' => 1,
            'name' => 'Gudang Surabaya',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('material_issues')->insert([
            'id' => 30,
            'issue_number' => 'SBY-MI/26-06/0011',
            'warehouse_id' => 1,
            'status' => 'out_of_stock',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('packaging_sizes')->insert([
            'id' => 10,
            'name' => '100ml',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('master_products')->insert([
            'id' => 60,
            'packaging_size_id' => 10,
            'name' => 'All Purpose Cleaner 100 ml',
            'bom_quantity' => 100,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('warehouse_products')->insert([
            'warehouse_id' => 1,
            'master_product_id' => 60,
            'quantity' => 5,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('material_issue_items')->insert([
            'id' => 80,
            'material_issue_id' => 30,
            'product_id' => 60,
            'room_name' => 'Lobby',
            'quantity' => 2,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $request = Request::create('/operational/job-assign-material-issues/update-qty/80', 'PUT', [
            'quantity' => 1,
        ]);

        app(JobAssignMaterialIssueController::class)->updateQtyIssue($request, 80);

        $this->assertSame('pending', DB::table('material_issues')->where('id', 30)->value('status'));
    }

    public function test_unchecked_sibling_without_material_still_blocks_submit_issue(): void
    {
        $now = now();

        DB::table('master_rooms')->insert([
            ['id' => 10, 'room_name' => 'Lobby', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 11, 'room_name' => 'VIP Room', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('job_schedules')->insert([
            [
                'id' => 1,
                'job_number' => 'JKT-CSR/26-05/0004',
                'job_advice_id' => 99,
                'building_id' => 7,
                'room_id' => 10,
                'room_name' => 'Lobby',
                'type' => 'service_first',
                'period' => 1,
                'status' => 'assign_material',
                'material_checked' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'job_number' => 'JKT-CSR/26-05/0004',
                'job_advice_id' => 99,
                'building_id' => 7,
                'room_id' => 11,
                'room_name' => 'VIP Room',
                'type' => 'service_first',
                'period' => 1,
                'status' => 'assign_material',
                'material_checked' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('job_assign_schedules')->insert([
            'id' => 20,
            'job_schedule_id' => 2,
            'status' => 'assigned',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('material_issues')->insert([
            'id' => 30,
            'issue_number' => 'JKT-MI/26-05/0002',
            'status' => 'pending',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('job_assign_material_issues')->insert([
            'id' => 40,
            'job_assign_schedule_id' => 20,
            'material_issue_id' => 30,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $errors = $this->validateSelectedIssues([40]);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('Lobby', $errors[0]);
    }

    public function test_material_issue_bom_target_mismatch_blocks_submit_issue(): void
    {
        $now = now();

        DB::table('job_schedules')->insert([
            'id' => 1,
            'job_number' => 'JKT-IF/26-06/0001',
            'room_name' => 'Meeting Room',
            'type' => 'install_free',
            'status' => 'assign_material',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('job_assign_schedules')->insert([
            'id' => 20,
            'job_schedule_id' => 1,
            'status' => 'assigned',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('material_issues')->insert([
            'id' => 30,
            'issue_number' => 'JKT-MI/26-06/0001',
            'status' => 'approved',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('job_assign_material_issues')->insert([
            'id' => 40,
            'job_assign_schedule_id' => 20,
            'material_issue_id' => 30,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('product_types')->insert([
            'id' => 50,
            'name' => 'ADS W300',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('master_products')->insert([
            'id' => 60,
            'product_type_id' => 50,
            'name' => 'ADS W300 Battery',
            'bom_quantity' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('rental_details')->insert([
            'id' => 70,
            'bom_rental_qty' => 4,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('material_issue_items')->insert([
            'id' => 80,
            'material_issue_id' => 30,
            'job_assign_schedule_id' => 20,
            'product_id' => 60,
            'room_name' => 'Meeting Room',
            'quantity' => 1,
            'bom_quantity' => 1,
            'notes' => 'Room: Meeting Room, Rental: ADS W300, ComponentID: 70',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $errors = $this->validateBomTargets(40);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('Qty BOM: 1, Target: 4', $errors[0]);
        $this->assertStringContainsString('JKT-IF/26-06/0001', $errors[0]);
    }

    public function test_material_issue_matching_bom_target_can_submit_issue(): void
    {
        $now = now();

        DB::table('job_schedules')->insert([
            'id' => 1,
            'job_number' => 'JKT-IF/26-06/0002',
            'room_name' => 'Meeting Room',
            'type' => 'install_free',
            'status' => 'assign_material',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('job_assign_schedules')->insert([
            'id' => 20,
            'job_schedule_id' => 1,
            'status' => 'assigned',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('material_issues')->insert([
            'id' => 30,
            'issue_number' => 'JKT-MI/26-06/0002',
            'status' => 'approved',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('job_assign_material_issues')->insert([
            'id' => 40,
            'job_assign_schedule_id' => 20,
            'material_issue_id' => 30,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('product_types')->insert([
            'id' => 50,
            'name' => 'ADS W300',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('master_products')->insert([
            'id' => 60,
            'product_type_id' => 50,
            'name' => 'ADS W300 Battery',
            'bom_quantity' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('rental_details')->insert([
            'id' => 70,
            'bom_rental_qty' => 4,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('material_issue_items')->insert([
            'id' => 80,
            'material_issue_id' => 30,
            'job_assign_schedule_id' => 20,
            'product_id' => 60,
            'room_name' => 'Meeting Room',
            'quantity' => 4,
            'bom_quantity' => 1,
            'notes' => 'Room: Meeting Room, Rental: ADS W300, ComponentID: 70',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $errors = $this->validateBomTargets(40);

        $this->assertSame([], $errors);
    }

    private function validateSelectedIssues(array $ids): array
    {
        $selected = JobAssignMaterialIssue::with([
            'jobAssignSchedule.jobSchedule.room',
            'jobAssignSchedule.jobSchedule.jobScheduleRooms',
        ])->whereIn('id', $ids)->get();

        $method = new ReflectionMethod(JobAssignMaterialIssueController::class, 'validateGroupedSubmitIssueSelection');
        $method->setAccessible(true);

        return $method->invoke(app(JobAssignMaterialIssueController::class), $selected);
    }

    private function validateBomTargets(int $id): array
    {
        $jobAssignMaterialIssue = JobAssignMaterialIssue::findOrFail($id);

        $method = new ReflectionMethod(JobAssignMaterialIssueController::class, 'validateMaterialIssueBomTargets');
        $method->setAccessible(true);

        return $method->invoke(app(JobAssignMaterialIssueController::class), $jobAssignMaterialIssue);
    }

    private function checkStockForRentalItems(int $id): array
    {
        $jobAssignMaterialIssue = JobAssignMaterialIssue::findOrFail($id);

        $method = new ReflectionMethod(JobAssignMaterialIssueController::class, 'checkStockForRentalItems');
        $method->setAccessible(true);

        return $method->invoke(app(JobAssignMaterialIssueController::class), $jobAssignMaterialIssue);
    }
}
