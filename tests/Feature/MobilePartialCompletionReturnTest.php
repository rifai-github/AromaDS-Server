<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Mobile\JobController;
use App\Models\JobSchedule;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class MobilePartialCompletionReturnTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->foreignId('branch_office')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_center')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('job_advice_id')->nullable();
            $table->foreignId('building_id')->nullable();
            $table->foreignId('branch_id')->nullable();
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
            $table->string('status')->nullable();
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

        Schema::create('material_issue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_issue_id')->nullable();
            $table->foreignId('job_assign_schedule_id')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->string('room_name')->nullable();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->nullable();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->string('status')->nullable();
            $table->date('return_date')->nullable();
            $table->string('return_reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('returned_by')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_return_id')->nullable();
            $table->foreignId('material_issue_item_id')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->string('room_name')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_receivings', function (Blueprint $table) {
            $table->id();
            $table->string('receiving_number')->nullable();
            $table->string('reference_no')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->foreignId('received_from')->nullable();
            $table->foreignId('received_by_old')->nullable();
            $table->date('receive_date')->nullable();
            $table->date('schedule_date')->nullable();
            $table->string('status')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_receiving_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_receiving_id')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('quantity_received', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_issuings', function (Blueprint $table) {
            $table->id();
            $table->string('issuing_number')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_issuing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_issuing_id')->nullable();
            $table->foreignId('job_assign_schedule_id')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->string('room_name')->nullable();
            $table->decimal('quantity_requested', 10, 2)->default(0);
            $table->decimal('quantity_issued', 10, 2)->default(0);
            $table->foreignId('serial_number_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number')->nullable();
            $table->string('status')->nullable();
            $table->string('location_type')->nullable();
            $table->foreignId('location_id')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->foreignId('inventory_receiving_id')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('unit_on_walls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('serial_number_id')->nullable();
            $table->string('status')->nullable();
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

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->string('movement_type')->nullable();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->date('movement_date')->nullable();
            $table->string('reference_no')->nullable();
            $table->string('reference_type')->nullable();
            $table->string('movement_no')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
        });

        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Administrator',
            'email' => 'admin@example.test',
            'password' => 'password',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Auth::login(User::findOrFail(1));
    }

    protected function tearDown(): void
    {
        foreach ([
            'inventory_movements',
            'warehouse_products',
            'unit_on_walls',
            'serial_numbers',
            'inventory_issuing_items',
            'inventory_issuings',
            'inventory_receiving_items',
            'inventory_receivings',
            'material_return_items',
            'material_returns',
            'material_issue_items',
            'job_assign_material_issues',
            'material_issues',
            'job_assign_schedules',
            'job_schedules',
            'warehouses',
            'teams',
            'buildings',
            'branches',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_partial_completion_creates_pending_return_without_receiving_stock_or_releasing_serial_number(): void
    {
        $this->seedPartialCompletionScenario();

        $controller = app(JobController::class);
        $job = JobSchedule::findOrFail(10);
        $room = (object) [
            'room_name' => 'VIP ROOM',
            'room_id' => 900,
        ];

        $contextMethod = new ReflectionMethod($controller, 'preparePartialCompletionReturnContext');
        $contextMethod->setAccessible(true);
        $returnContext = $contextMethod->invoke($controller, $job, collect([$room]), now());

        $processMethod = new ReflectionMethod($controller, 'processPartialCompletionMaterialReturnItems');
        $processMethod->setAccessible(true);
        $processMethod->invoke($controller, $job, $room, collect([20]), $returnContext, now());

        $this->assertDatabaseHas('material_returns', [
            'job_schedule_id' => 10,
            'warehouse_id' => 5,
            'status' => 'pending',
            'returned_by' => null,
            'returned_at' => null,
        ]);

        $this->assertDatabaseHas('inventory_receivings', [
            'reference_no' => 'BDG-IR/26-05/0002',
            'branch_id' => 2,
            'status' => 'pending',
            'receive_date' => null,
        ]);
        $this->assertStringStartsWith('BDG-IRC/', DB::table('inventory_receivings')->value('receiving_number'));

        $this->assertDatabaseHas('inventory_receiving_items', [
            'master_product_id' => 100,
            'quantity' => 1,
            'quantity_received' => 0,
        ]);

        $this->assertDatabaseHas('serial_numbers', [
            'id' => 200,
            'status' => 'on_hand',
            'location_type' => 'technician',
            'location_id' => 1,
            'inventory_receiving_id' => null,
        ]);

        $this->assertDatabaseHas('inventory_issuing_items', [
            'id' => 60,
            'serial_number_id' => 200,
        ]);

        $this->assertDatabaseCount('warehouse_products', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    private function seedPartialCompletionScenario(): void
    {
        DB::table('branches')->insert([
            [
                'id' => 1,
                'code' => 'JKT',
                'name' => 'Jakarta Branch',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'code' => 'BDG',
                'name' => 'Bandung Branch',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('buildings')->insert([
            'id' => 10,
            'branch_id' => 1,
            'name' => 'Spektrum Biologi I',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('warehouses')->insert([
            'id' => 5,
            'name' => 'Warehouse Bandung',
            'branch_id' => 2,
            'is_active' => true,
            'is_center' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_schedules')->insert([
            'id' => 10,
            'job_number' => 'BDG-IR/26-05/0002',
            'type' => 'Install (IR)',
            'status' => 'meninggalkan_lokasi',
            'building_id' => 10,
            'branch_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_assign_schedules')->insert([
            'id' => 20,
            'job_schedule_id' => 10,
            'status' => 'assigned',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('material_issues')->insert([
            'id' => 30,
            'issue_number' => 'BDG-MA/26-05/0002',
            'warehouse_id' => 5,
            'status' => 'issued',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_assign_material_issues')->insert([
            'id' => 40,
            'job_assign_schedule_id' => 20,
            'material_issue_id' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('material_issue_items')->insert([
            'id' => 50,
            'material_issue_id' => 30,
            'job_assign_schedule_id' => 20,
            'product_id' => 100,
            'room_name' => 'VIP ROOM',
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventory_issuings')->insert([
            'id' => 55,
            'issuing_number' => 'BDG-WI/26-05/0002',
            'warehouse_id' => 5,
            'status' => 'sent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventory_issuing_items')->insert([
            'id' => 60,
            'inventory_issuing_id' => 55,
            'job_assign_schedule_id' => 20,
            'product_id' => 100,
            'room_name' => 'VIP ROOM',
            'quantity_requested' => 1,
            'quantity_issued' => 1,
            'serial_number_id' => 200,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('serial_numbers')->insert([
            'id' => 200,
            'serial_number' => 'C100B0526002',
            'status' => 'on_hand',
            'location_type' => 'technician',
            'location_id' => 1,
            'master_product_id' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
