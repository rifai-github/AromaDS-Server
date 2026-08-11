<?php

namespace Tests\Feature;

use App\Models\JobAdvice;
use App\Models\JobAdviceRoom;
use App\Models\JobSchedule;
use App\Models\JobScheduleRoom;
use App\Models\JobScheduleRoomRental;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA bug (job SMG-CSR/26-08/0003, room "Main Hall KW"): the room has two
 * DIFFERENT rentals -- a unit_refill "Dispenser Hand Sanitizer 7600S--A"
 * (needs install THEN first service) and a refill_only "PURE Hand Sanitizer
 * (Gel) 1000 ml" (needs first service only, never install). The
 * `operational:repair-job-schedule-rentals` / `marketing:repair-ja-room-materials`
 * Artisan commands grouped a JobAdviceRoom's rentals purely by physical room
 * (building+room), with no rental_type check, so when backfilling links for
 * the install (IR) job schedule they attached BOTH rentals to it -- including
 * the refill_only one that should never have an install-job row at all.
 */
class RepairJobScheduleRentalsFlowGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_rooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('building_id')->nullable();
            $table->string('room_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contract_rooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_rentals', function (Blueprint $table) {
            $table->id();
            $table->string('rental_name')->nullable();
            $table->string('rental_type')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Empty on purpose: determineRentalJobFlow() falls back to querying
        // this for rental_type values other than unit_only/refill_only (e.g.
        // this test's unit_refill rental); with zero rows the composition
        // detector's default ("needs both install and service") is exactly
        // correct for unit_refill, so an empty table is enough here.
        Schema::create('rental_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('master_rental_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_advices', function (Blueprint $table) {
            $table->id();
            $table->string('job_advice_number')->nullable();
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
            $table->integer('quantity')->default(1);
            $table->boolean('rental_has_installation')->default(false);
            $table->boolean('rental_has_service')->default(false);
            $table->string('status')->nullable();
            $table->boolean('is_trial')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('job_advice_id')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->unsignedBigInteger('building_id')->nullable();
            $table->string('room_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedule_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('job_advice_room_id')->nullable();
            $table->string('room_name')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->string('status')->nullable();
            $table->string('material_return_status')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
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
    }

    protected function tearDown(): void
    {
        foreach ([
            'job_schedule_room_rentals', 'job_schedule_rooms', 'job_schedules',
            'job_advice_rooms', 'job_advices', 'rental_details', 'master_rentals',
            'contract_rooms', 'master_rooms', 'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_refill_only_rental_is_not_linked_into_the_install_job(): void
    {
        DB::table('users')->insert(['id' => 1, 'name' => 'Admin', 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs(User::findOrFail(1));

        $jobAdvice = JobAdvice::create(['job_advice_number' => 'SMG-JA/26-08/0004']);

        $masterRoomId = DB::table('master_rooms')->insertGetId([
            'room_name' => 'Main Hall KW',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $contractRoomId = DB::table('contract_rooms')->insertGetId([
            'room_id' => $masterRoomId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $unitRefillRental = DB::table('master_rentals')->insertGetId([
            'rental_name' => 'Dispenser Hand Sanitizer 7600S--A',
            'rental_type' => 'unit_refill',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $refillOnlyRental = DB::table('master_rentals')->insertGetId([
            'rental_name' => 'PURE Hand Sanitizer (Gel) 1000 ml',
            'rental_type' => 'refill_only',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $unitRefillRoom = JobAdviceRoom::create([
            'job_advice_id' => $jobAdvice->id,
            'contract_room_id' => $contractRoomId,
            'rental_product_id' => $unitRefillRental,
            'room_name' => 'Main Hall KW',
            'quantity' => 1,
        ]);
        $refillOnlyRoom = JobAdviceRoom::create([
            'job_advice_id' => $jobAdvice->id,
            'contract_room_id' => $contractRoomId,
            'rental_product_id' => $refillOnlyRental,
            'room_name' => 'Main Hall KW',
            'quantity' => 1,
        ]);

        $installJob = JobSchedule::create([
            'job_number' => 'SMG-IR/26-08/0003',
            'type' => 'install',
            'status' => 'in_progress',
            'job_advice_id' => $jobAdvice->id,
            'room_id' => $masterRoomId,
        ]);
        $serviceJob = JobSchedule::create([
            'job_number' => 'SMG-CSR/26-08/0003',
            'type' => 'service_first',
            'status' => 'in_progress',
            'job_advice_id' => $jobAdvice->id,
            'room_id' => $masterRoomId,
        ]);

        Artisan::call('operational:repair-job-schedule-rentals', ['--apply' => true]);

        $installRoom = JobScheduleRoom::where('job_schedule_id', $installJob->id)->first();
        $serviceRoom = JobScheduleRoom::where('job_schedule_id', $serviceJob->id)->first();

        $this->assertNotNull($installRoom, 'Install job should still get a room row for its unit_refill rental.');
        $installLinkedJaRoomIds = JobScheduleRoomRental::where('job_schedule_room_id', $installRoom->id)
            ->pluck('job_advice_room_id');
        $this->assertContains($unitRefillRoom->id, $installLinkedJaRoomIds, 'unit_refill rental must be linked to the install job.');
        $this->assertNotContains($refillOnlyRoom->id, $installLinkedJaRoomIds, 'refill_only rental must never be linked to the install job.');

        $this->assertNotNull($serviceRoom, 'Service job should get a room row.');
        $serviceLinkedJaRoomIds = JobScheduleRoomRental::where('job_schedule_room_id', $serviceRoom->id)
            ->pluck('job_advice_room_id');
        $this->assertContains($unitRefillRoom->id, $serviceLinkedJaRoomIds, 'unit_refill rental also needs first service.');
        $this->assertContains($refillOnlyRoom->id, $serviceLinkedJaRoomIds, 'refill_only rental belongs on the service job.');
    }
}
