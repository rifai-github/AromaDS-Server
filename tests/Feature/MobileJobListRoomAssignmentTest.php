<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Mobile\JobController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileJobListRoomAssignmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();

        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Ahmad Wijaya',
            'email' => 'tech@example.test',
            'password' => 'password',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('teams')->insert([
            'id' => 10,
            'team_name' => 'Tim Service Area Bandung Kab',
            'team_head_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('team_members')->insert([
            'id' => 100,
            'team_id' => 10,
            'user_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('customers')->insert([
            'id' => 20,
            'name' => 'Test110526',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_advices')->insert([
            'id' => 30,
            'customer_id' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        foreach ([
            'job_favorites',
            'unit_on_walls',
            'job_schedule_room_rentals',
            'job_schedule_room_assignments',
            'job_assign_material_issues',
            'job_schedule_rooms',
            'job_assign_schedules',
            'job_schedules',
            'job_advice_rooms',
            'job_advices',
            'master_rentals',
            'contract_rooms',
            'master_rooms',
            'team_members',
            'teams',
            'customers',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_mobile_job_list_includes_service_assigned_only_at_room_level(): void
    {
        DB::table('job_schedules')->insert([
            'id' => 40,
            'job_number' => 'BDG-CSR/26-06/0003',
            'job_advice_id' => 30,
            'type' => 'service',
            'status' => 'barang_siap_diambil',
            'room_name' => 'Ruang Melati',
            'schedule_date' => '2026-06-01',
            'material_checked' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_schedule_rooms')->insert([
            'id' => 50,
            'job_schedule_id' => 40,
            'room_name' => 'Ruang Melati',
            'room_id' => 500,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_schedule_room_assignments')->insert([
            'id' => 60,
            'job_schedule_id' => 40,
            'job_schedule_room_id' => 50,
            'team_id' => 10,
            'status' => 'assigned',
            'assigned_date' => '2026-06-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/api/v1/mobile/jobs/today', 'GET');
        $request->setUserResolver(fn () => User::find(1));
        Cache::flush();

        $response = app(JobController::class)->getTodayJobs($request);
        $payload = $response->getData(true);

        $this->assertSame('success', $payload['status']);
        $this->assertContains('BDG-CSR/26-06/0003', collect($payload['data'])->pluck('job_number')->all());
    }

    public function test_mobile_job_list_includes_remove_job_with_assign_team_status(): void
    {
        DB::table('job_schedules')->insert([
            'id' => 41,
            'job_number' => 'JKT-RV/26-05/0001',
            'job_advice_id' => 30,
            'type' => 'remove',
            'status' => 'assign_team',
            'room_name' => 'Ruang Wijaya',
            'schedule_date' => '2026-05-26',
            'material_checked' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_assign_schedules')->insert([
            'id' => 61,
            'job_schedule_id' => 41,
            'team_id' => 10,
            'status' => 'assigned',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/api/v1/mobile/jobs/today', 'GET');
        $request->setUserResolver(fn () => User::find(1));
        Cache::flush();

        $response = app(JobController::class)->getTodayJobs($request);
        $payload = $response->getData(true);
        $job = collect($payload['data'])->firstWhere('job_number', 'JKT-RV/26-05/0001');

        $this->assertSame('success', $payload['status']);
        $this->assertNotNull($job);
        $this->assertSame('remove', $job['type']);
        $this->assertSame('assign_team', $job['status']);
        $this->assertTrue($job['material_checked']);
    }

    public function test_mobile_remove_job_without_job_advice_rooms_gets_fallback_room_data(): void
    {
        DB::table('job_schedules')->insert([
            'id' => 42,
            'job_number' => 'JKT-RV/26-06/0001',
            'job_advice_id' => 30,
            'type' => 'remove',
            'status' => 'assign_team',
            'room_name' => 'Ruang Wijaya',
            'schedule_date' => '2026-06-02',
            'material_checked' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_assign_schedules')->insert([
            'id' => 62,
            'job_schedule_id' => 42,
            'team_id' => 10,
            'status' => 'assigned',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/api/v1/mobile/jobs/42/rooms', 'GET');
        $request->setUserResolver(fn () => User::find(1));
        $this->actingAs(User::find(1));
        Cache::flush();

        $roomsResponse = app(JobController::class)->getJobRooms(42);
        $roomsPayload = $roomsResponse->getData(true);

        $this->assertSame('success', $roomsPayload['status']);
        $this->assertCount(1, $roomsPayload['data']);
        $this->assertSame('Ruang Wijaya', $roomsPayload['data'][0]['name']);
        $this->assertSame(42, $roomsPayload['data'][0]['job_schedule_id']);

        $detailResponse = app(JobController::class)->getJobDetail($request, 42);
        $detailPayload = $detailResponse->getData(true);

        $this->assertSame('success', $detailPayload['status']);
        $this->assertSame('Ruang Wijaya', $detailPayload['data']['room_name']);
        $this->assertSame(1, $detailPayload['data']['total_rooms']);
        $this->assertDatabaseHas('job_advice_rooms', [
            'job_advice_id' => 30,
            'room_name' => 'Ruang Wijaya',
            'remove_job_schedule_id' => 42,
        ]);
    }

    public function test_mobile_job_groups_multiple_rentals_in_one_physical_room(): void
    {
        DB::table('master_rooms')->insert([
            'id' => 500,
            'room_name' => 'Ruang Delima',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('contract_rooms')->insert([
            'id' => 70,
            'room_id' => 500,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_advice_rooms')->insert([
            [
                'id' => 90,
                'job_advice_id' => 30,
                'contract_room_id' => 70,
                'room_name' => 'Ruang Delima',
                'rental_name' => 'ADS XL Unit Only',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 91,
                'job_advice_id' => 30,
                'contract_room_id' => 70,
                'room_name' => 'Ruang Delima',
                'rental_name' => 'Rental-5',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('job_schedules')->insert([
            'id' => 43,
            'job_number' => 'JKT-IR/26-06/0002',
            'job_advice_id' => 30,
            'type' => 'install',
            'status' => 'assign_team',
            'room_id' => 500,
            'room_name' => 'Ruang Delima',
            'schedule_date' => '2026-06-04',
            'material_checked' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_assign_schedules')->insert([
            'id' => 63,
            'job_schedule_id' => 43,
            'team_id' => 10,
            'status' => 'assigned',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/api/v1/mobile/jobs/today', 'GET');
        $request->setUserResolver(fn () => User::find(1));
        $this->actingAs(User::find(1));
        Cache::flush();

        $listResponse = app(JobController::class)->getTodayJobs($request);
        $listPayload = $listResponse->getData(true);
        $job = collect($listPayload['data'])->firstWhere('job_number', 'JKT-IR/26-06/0002');

        $this->assertSame('success', $listPayload['status']);
        $this->assertNotNull($job);
        $this->assertSame(1, $job['total_rooms']);

        $detailResponse = app(JobController::class)->getJobDetail($request, 43);
        $detailPayload = $detailResponse->getData(true);

        $this->assertSame('success', $detailPayload['status']);
        $this->assertSame(1, $detailPayload['data']['total_rooms']);

        $roomsResponse = app(JobController::class)->getJobRooms(43);
        $roomsPayload = $roomsResponse->getData(true);

        $this->assertSame('success', $roomsPayload['status']);
        $this->assertCount(1, $roomsPayload['data']);
        $this->assertSame('Ruang Delima', $roomsPayload['data'][0]['name']);
        $this->assertSame('ADS XL Unit Only, Rental-5', $roomsPayload['data'][0]['rental_name']);
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
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
            $table->foreignId('customer_contact_id')->nullable();
            $table->foreignId('contract_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_name')->nullable();
            $table->string('room_floor')->nullable();
            $table->string('room_type')->nullable();
            $table->string('room_temperature')->nullable();
            $table->string('room_intensity')->nullable();
            $table->string('room_installation_type')->nullable();
            $table->decimal('room_length', 10, 2)->nullable();
            $table->decimal('room_width', 10, 2)->nullable();
            $table->decimal('room_height', 10, 2)->nullable();
            $table->decimal('area', 10, 2)->nullable();
            $table->decimal('volume', 10, 2)->nullable();
            $table->string('room_remark')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contract_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->timestamps();
        });

        Schema::create('master_rentals', function (Blueprint $table) {
            $table->id();
            $table->string('rental_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_advice_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_advice_id')->nullable();
            $table->foreignId('contract_room_id')->nullable();
            $table->foreignId('quotation_room_id')->nullable();
            $table->foreignId('rental_product_id')->nullable();
            $table->string('room_name')->nullable();
            $table->string('rental_name')->nullable();
            $table->integer('quantity')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('remove_job_schedule_id')->nullable();
            $table->foreignId('existing_unit_on_wall_id')->nullable();
            $table->boolean('unit_already_installed')->default(false);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
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
            $table->string('room_name')->nullable();
            $table->date('schedule_date')->nullable();
            $table->boolean('material_checked')->default(false);
            $table->timestamp('material_checked_at')->nullable();
            $table->text('notes')->nullable();
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

        Schema::create('job_schedule_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('job_advice_room_id')->nullable();
            $table->string('room_name')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->string('status')->nullable();
            $table->string('material_return_status')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedule_room_rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_room_id')->nullable();
            $table->foreignId('job_advice_room_id')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedule_room_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('job_schedule_room_id')->nullable();
            $table->foreignId('job_advice_room_id')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->foreignId('job_assign_schedule_id')->nullable();
            $table->boolean('is_custom')->default(false);
            $table->date('assigned_date')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('job_schedule_id')->nullable();
            $table->timestamps();
        });

        Schema::create('unit_on_walls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('building_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->foreignId('rental_id')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->foreignId('serial_number_id')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('status')->nullable();
            $table->string('room_name')->nullable();
            $table->string('rental_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
