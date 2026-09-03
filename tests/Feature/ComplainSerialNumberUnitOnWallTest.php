<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Mobile\JobController;
use App\Models\JobAdvice;
use App\Models\JobSchedule;
use App\Models\SerialNumber;
use App\Models\UnitOnWall;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA 3 Sep 2026, job 725 SBY-NR/26-08/0004 (Complain, "With Materials" = No): "saya coba
 * mulai pekerjaan via APK tidak bisa karena minta masukan SN, saya coba masukan SN yang
 * sudah terpasang, tidak bisa" - the technician had to finish the job from the web instead.
 *
 * A no-material Complain has no MaterialIssue / InventoryIssuing at all, so Step 1 of
 * validateSerialNumber() (match against verified materials) can never succeed. The Unit On
 * Wall fallback that would have matched the installed SN was gated on isServiceLikeJob(),
 * which deliberately excludes `complain` - so every scan died on the final 404 "Serial
 * number tidak terdaftar untuk job ini" (live log: SN DW300W2606017, active on the wall in
 * the job's own room).
 *
 * The scoping is the load-bearing part of these tests: Complain may read Unit On Wall
 * because it is sent to an already-installed unit, but Install must keep failing without
 * verified material, and the customer/building/room checks must keep rejecting a SN that
 * belongs somewhere else.
 */
class ComplainSerialNumberUnitOnWallTest extends TestCase
{
    private const CUSTOMER_ID = 10;

    private const BUILDING_ID = 206;

    private const ROOM_ID = 13266;

    private const PRODUCT_ID = 31;

    private const SERIAL = 'DW300W2606017';

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

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->string('nama_gedung')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->nullable();
            $table->string('room_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_advices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable();
            $table->string('type')->nullable();
            $table->boolean('with_materials')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_advice_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_advice_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->foreignId('contract_room_id')->nullable();
            $table->foreignId('quotation_room_id')->nullable();
            $table->string('room_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contract_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quotation_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->nullable();
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
            $table->boolean('material_checked')->default(false);
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

        Schema::create('job_assign_material_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_assign_schedule_id')->nullable();
            $table->foreignId('material_issue_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_issues', function (Blueprint $table) {
            $table->id();
            $table->string('issue_number')->nullable();
            $table->string('status')->nullable();
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

        Schema::create('job_schedule_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('job_advice_room_id')->nullable();
            $table->string('mac')->nullable();
            $table->timestamps();
        });

        Schema::create('product_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->boolean('is_unit')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_type_id')->nullable();
            $table->string('name')->nullable();
            $table->string('sku')->nullable();
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
            $table->string('room_name')->nullable();
            $table->string('building_name')->nullable();
            $table->string('product_name')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_issuings', function (Blueprint $table) {
            $table->id();
            $table->string('issuing_number')->nullable();
            $table->string('reference_no')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_issuing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_issuing_id')->nullable();
            $table->string('room_name')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('serial_number_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        foreach ([
            'inventory_issuing_items',
            'inventory_issuings',
            'unit_on_walls',
            'serial_numbers',
            'warehouses',
            'branches',
            'master_products',
            'product_types',
            'job_schedule_units',
            'job_schedule_room_assignments',
            'job_schedule_rooms',
            'material_issues',
            'job_assign_material_issues',
            'job_assign_schedules',
            'job_schedules',
            'quotation_rooms',
            'contract_rooms',
            'job_advice_rooms',
            'job_advices',
            'master_rooms',
            'buildings',
            'customers',
            'team_members',
            'teams',
            'user_roles',
            'roles',
            'user_permission',
            'permissions',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    /**
     * A Complain job exactly like QA's: no material issue anywhere, one room, and the unit
     * the technician is being sent to already active on the wall in that room.
     */
    private function seedNoMaterialFixture(string $jobType = 'complain'): JobSchedule
    {
        DB::table('customers')->insert([
            'id' => self::CUSTOMER_ID, 'customer_name' => 'PT Jayadi', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('buildings')->insert([
            'id' => self::BUILDING_ID, 'nama_gedung' => 'Gedung Jayadi', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('master_rooms')->insert([
            'id' => self::ROOM_ID,
            'building_id' => self::BUILDING_ID,
            'room_name' => 'Ruang Extra',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_types')->insert([
            'id' => 1, 'name' => 'Diffuser', 'is_unit' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('master_products')->insert([
            'id' => self::PRODUCT_ID,
            'product_type_id' => 1,
            'name' => 'Diffuser W300 White',
            'sku' => 'ADW300',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::create(['name' => 'Test Teknisi', 'email' => 'teknisi@test.local']);
        $teamId = DB::table('teams')->insertGetId([
            'team_name' => 'Tim Teknisi', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('team_members')->insert([
            'team_id' => $teamId, 'user_id' => $user->id, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $jobAdvice = JobAdvice::create([
            'customer_id' => self::CUSTOMER_ID,
            'type' => $jobType,
            'with_materials' => false,
        ]);

        DB::table('job_advice_rooms')->insert([
            'job_advice_id' => $jobAdvice->id,
            'room_id' => self::ROOM_ID,
            'room_name' => 'Ruang Extra',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $job = JobSchedule::create([
            'job_number' => 'SBY-NR/26-08/0004',
            'type' => $jobType,
            'status' => 'teknisi_sedang_pengerjaan',
            'job_advice_id' => $jobAdvice->id,
            'building_id' => self::BUILDING_ID,
            'room_id' => self::ROOM_ID,
        ]);

        DB::table('job_assign_schedules')->insert([
            'job_schedule_id' => $job->id,
            'team_id' => $teamId,
            'status' => 'assigned',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $serial = SerialNumber::create([
            'serial_number' => self::SERIAL,
            'status' => 'in_use',
            'condition_status' => 'new',
            'location_type' => 'customer',
            'master_product_id' => self::PRODUCT_ID,
        ]);

        UnitOnWall::create([
            'customer_id' => self::CUSTOMER_ID,
            'building_id' => self::BUILDING_ID,
            'room_id' => self::ROOM_ID,
            'room_name' => 'Ruang Extra',
            'product_id' => self::PRODUCT_ID,
            'serial_number_id' => $serial->id,
            'serial_number' => self::SERIAL,
            'status' => 'active',
        ]);

        Auth::login($user);

        return $job;
    }

    private function validate(JobSchedule $job, string $serialNumber, ?string $roomName = 'Ruang Extra'): array
    {
        $response = app(JobController::class)->validateSerialNumber(
            Request::create(
                "/api/v1/mobile/jobs/{$job->id}/validate-serial-number",
                'POST',
                array_filter([
                    'serial_number' => $serialNumber,
                    'room_name' => $roomName,
                ])
            ),
            $job->id
        );

        return [$response->getStatusCode(), $response->getData(true)];
    }

    public function test_complain_job_without_material_accepts_the_serial_number_on_the_wall(): void
    {
        $job = $this->seedNoMaterialFixture();

        [$status, $payload] = $this->validate($job, self::SERIAL);

        $this->assertSame(200, $status, 'Complain must be able to resolve an installed SN: '.json_encode($payload));
        $this->assertSame('success', $payload['status']);
        $this->assertSame('unit_on_wall', $payload['source']);
        $this->assertSame(self::SERIAL, $payload['data']['serial_number']);
    }

    public function test_install_job_without_material_still_rejects_an_installed_serial_number(): void
    {
        $job = $this->seedNoMaterialFixture('install');

        [$status, $payload] = $this->validate($job, self::SERIAL);

        $this->assertSame(404, $status);
        $this->assertSame('error', $payload['status']);
    }

    public function test_complain_job_rejects_a_serial_number_installed_in_another_room(): void
    {
        $job = $this->seedNoMaterialFixture();

        [$status, $payload] = $this->validate($job, self::SERIAL, 'Ruang Complain');

        $this->assertSame(400, $status);
        $this->assertTrue($payload['room_mismatch'] ?? false);
        $this->assertSame('Ruang Extra', $payload['expected_room']);
    }

    public function test_complain_job_rejects_a_serial_number_belonging_to_another_customer(): void
    {
        $job = $this->seedNoMaterialFixture();

        UnitOnWall::query()->update(['customer_id' => self::CUSTOMER_ID + 1]);

        [$status, $payload] = $this->validate($job, self::SERIAL);

        $this->assertSame(404, $status);
        $this->assertSame('error', $payload['status']);
    }

    public function test_complain_job_rejects_a_serial_number_that_is_no_longer_on_the_wall(): void
    {
        $job = $this->seedNoMaterialFixture();

        UnitOnWall::query()->update(['status' => 'removed']);

        [$status, $payload] = $this->validate($job, self::SERIAL);

        $this->assertSame(404, $status);
        $this->assertSame('error', $payload['status']);
    }
}
