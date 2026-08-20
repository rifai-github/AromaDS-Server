<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Mobile\JobController;
use App\Models\InventoryIssuing;
use App\Models\JobAdvice;
use App\Models\JobSchedule;
use App\Models\SerialNumber;
use App\Models\UnitOnWall;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Reproduces the live QA report (20 Aug 2026, job SBY-IR/26-08/0009): swapping SN
 * DW300W2606010 -> DW300W2606014 via the mobile "Ganti Unit" endpoint queued the old
 * SN's Inventory Receiving correctly, but the new SN never got an Inventory Issuing
 * record. This test drives the actual swapSerialNumber() HTTP-level flow (not just the
 * isolated queueSwappedUnitIssuing() helper covered by SwapUnitInventoryTrailTest) to
 * catch bugs in the wiring itself.
 */
class SwapUnitEndToEndTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->foreignId('update_by_1')->nullable();
            $table->timestamp('update_at_1')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('permission_id')->nullable();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('role_id')->nullable();
            $table->timestamps();
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('team_name')->nullable();
            $table->foreignId('team_head_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('job_advices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable();
            $table->string('type')->nullable();
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
            $table->foreignId('room_id')->nullable();
            $table->foreignId('assigned_technician_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_assign_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->foreignId('assigned_by')->nullable();
            $table->date('assigned_date')->nullable();
            $table->string('status')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedule_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedule_room_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_room_id')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('warehouse_code')->nullable();
            $table->string('name')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_center')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number')->nullable();
            $table->string('status')->nullable();
            $table->string('condition_status')->nullable();
            $table->string('location_type')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('master_product_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('inventory_receiving_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('unit_on_walls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('building_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->foreignId('serial_number_id')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('status')->nullable();
            $table->date('last_service_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('unit_on_wall_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_on_wall_id')->nullable();
            $table->string('action')->nullable();
            $table->timestamp('action_date')->nullable();
            $table->string('serial_number_before')->nullable();
            $table->string('serial_number_after')->nullable();
            $table->foreignId('performed_by')->nullable();
            $table->foreignId('job_schedule_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_issuings', function (Blueprint $table) {
            $table->id();
            $table->string('issuing_number')->nullable();
            $table->foreignId('inventory_request_id')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->date('issue_date')->nullable();
            $table->string('reference_no')->nullable();
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->unsignedBigInteger('received_by')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->string('status')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_issuing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_issuing_id')->nullable();
            $table->foreignId('job_assign_schedule_id')->nullable();
            $table->string('room_name')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('serial_number_id')->nullable();
            $table->integer('quantity_requested')->nullable();
            $table->integer('quantity_issued')->nullable();
            $table->integer('quantity_received')->nullable();
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->decimal('total_price', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_receivings', function (Blueprint $table) {
            $table->id();
            $table->string('receiving_number')->nullable();
            $table->string('reference_no')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->unsignedBigInteger('received_from')->nullable();
            $table->unsignedBigInteger('received_by_old')->nullable();
            $table->date('schedule_date')->nullable();
            $table->string('status')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_receiving_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_receiving_id')->nullable();
            $table->unsignedBigInteger('master_product_id')->nullable();
            $table->integer('quantity')->nullable();
            $table->integer('quantity_received')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('inventory_receiving_items');
        Schema::dropIfExists('inventory_receivings');
        Schema::dropIfExists('inventory_issuing_items');
        Schema::dropIfExists('inventory_issuings');
        Schema::dropIfExists('unit_on_wall_histories');
        Schema::dropIfExists('unit_on_walls');
        Schema::dropIfExists('serial_numbers');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('job_assign_schedules');
        Schema::dropIfExists('job_schedule_room_assignments');
        Schema::dropIfExists('job_schedule_rooms');
        Schema::dropIfExists('job_schedules');
        Schema::dropIfExists('job_advices');
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('user_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_swap_serial_number_creates_both_issuing_and_receiving(): void
    {
        DB::table('branches')->insert([
            'id' => 1, 'code' => 'SBY', 'name' => 'Surabaya', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $warehouse = Warehouse::create([
            'warehouse_code' => 'WH-SBY', 'name' => 'Gudang Surabaya', 'branch_id' => 1, 'is_active' => true,
        ]);

        $user = User::create(['name' => 'Test Teknisi', 'email' => 'teknisi@test.local']);

        $teamId = DB::table('teams')->insertGetId([
            'team_name' => 'Tim Teknisi', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('team_members')->insert([
            'team_id' => $teamId, 'user_id' => $user->id, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $jobAdvice = JobAdvice::create(['customer_id' => 500, 'type' => 'install']);

        $job = JobSchedule::create([
            'job_number' => 'SBY-IR/26-08/0009',
            'type' => 'install',
            'status' => 'in_progress',
            'job_advice_id' => $jobAdvice->id,
            'building_id' => 206,
            'room_id' => 99,
        ]);

        DB::table('job_assign_schedules')->insert([
            'job_schedule_id' => $job->id,
            'team_id' => $teamId,
            'status' => 'assigned',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $oldSn = SerialNumber::create([
            'serial_number' => 'DW300W2606010',
            'status' => 'in_use',
            'condition_status' => 'new',
            'location_type' => 'customer',
            'master_product_id' => 77,
            'warehouse_id' => $warehouse->id,
        ]);

        $newSn = SerialNumber::create([
            'serial_number' => 'DW300W2606014',
            'status' => 'ready',
            'condition_status' => 'new',
            'location_type' => 'warehouse',
            'master_product_id' => 77,
            'warehouse_id' => $warehouse->id,
        ]);

        $uow = UnitOnWall::create([
            'customer_id' => 500,
            'building_id' => 206,
            'room_id' => 99,
            'product_id' => 77,
            'serial_number_id' => $oldSn->id,
            'serial_number' => 'DW300W2606010',
            'status' => 'active',
        ]);

        Auth::login($user);

        $response = app(JobController::class)->swapSerialNumber(Request::create(
            "/api/v1/mobile/jobs/{$job->id}/swap-serial-number",
            'POST',
            [
                'old_serial_number' => 'DW300W2606010',
                'new_serial_number' => 'DW300W2606014',
                'room_id' => 99,
            ]
        ), $job->id);

        $payload = $response->getData(true);

        $this->assertSame('success', $payload['status'], json_encode($payload));

        $this->assertDatabaseHas('inventory_receivings', [
            'reference_no' => 'SBY-IR/26-08/0009',
            'status' => 'pending',
        ]);

        $issuing = InventoryIssuing::where('reference_no', 'SBY-IR/26-08/0009')->first();
        $this->assertNotNull($issuing, 'Expected an Inventory Issuing record to be created for the new serial number.');
        $this->assertDatabaseHas('inventory_issuing_items', [
            'inventory_issuing_id' => $issuing->id ?? 0,
            'serial_number_id' => $newSn->id,
        ]);
    }
}
