<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Mobile\JobController;
use App\Models\JobAdvice;
use App\Models\JobAdviceRoom;
use App\Models\JobSchedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

/**
 * A CSR room holding two units of the same rental must be worked unit by unit.
 *
 * The existing gate derives the units it expects from the warehouse issuing, which a
 * service job never has, so such a room closed as soon as the first unit had its
 * photos - one setting and one Before/After for two physical units (QA 7 Sep 2026,
 * SBY-CSR/26-09/0023, where job_schedule_units held a single NO-SN placeholder while
 * two units sat active on the wall).
 */
class ServiceRoomWallUnitsCoverageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_advice_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_advice_id')->nullable();
            $table->foreignId('contract_room_id')->nullable();
            $table->string('room_name')->nullable();
            $table->foreignId('rental_product_id')->nullable();
            $table->decimal('quantity', 8, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contract_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->timestamps();
        });

        Schema::create('unit_on_walls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('building_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->string('room_name')->nullable();
            $table->foreignId('serial_number_id')->nullable();
            $table->string('serial_number')->nullable();
            $table->foreignId('rental_id')->nullable();
            $table->foreignId('contract_id')->nullable();
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

        Schema::create('job_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_room_id')->nullable();
            $table->foreignId('job_schedule_unit_id')->nullable();
            $table->string('photo_type')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('job_photos');
        Schema::dropIfExists('job_schedule_units');
        Schema::dropIfExists('unit_on_walls');
        Schema::dropIfExists('contract_rooms');
        Schema::dropIfExists('job_advice_rooms');
        Schema::dropIfExists('job_schedules');
        Schema::dropIfExists('job_advices');

        parent::tearDown();
    }

    public function test_both_wall_units_are_pending_before_any_work(): void
    {
        [$job, $room] = $this->makeServiceRoomWithTwoUnits();

        $this->assertSame(
            ['SN-UNIT-1', 'SN-UNIT-2'],
            $this->missingWallUnits($job, $room)
        );
    }

    public function test_the_second_unit_is_still_pending_after_the_first_is_photographed(): void
    {
        [$job, $room] = $this->makeServiceRoomWithTwoUnits();
        $this->workUnit($job, $room, 'SN-UNIT-1');

        $this->assertSame(['SN-UNIT-2'], $this->missingWallUnits($job, $room));
    }

    public function test_a_unit_with_only_a_before_photo_is_not_done(): void
    {
        [$job, $room] = $this->makeServiceRoomWithTwoUnits();
        $this->workUnit($job, $room, 'SN-UNIT-1', withAfter: false);
        $this->workUnit($job, $room, 'SN-UNIT-2');

        $this->assertSame(['SN-UNIT-1'], $this->missingWallUnits($job, $room));
    }

    public function test_nothing_is_pending_once_every_unit_has_both_photos(): void
    {
        [$job, $room] = $this->makeServiceRoomWithTwoUnits();
        $this->workUnit($job, $room, 'SN-UNIT-1');
        $this->workUnit($job, $room, 'SN-UNIT-2');

        $this->assertSame([], $this->missingWallUnits($job, $room));
    }

    public function test_a_single_unit_room_is_left_alone(): void
    {
        // Deliberate: the single-unit flow has always worked, and a stale Unit On
        // Wall row must not start blocking those rooms from being closed.
        [$job, $room] = $this->makeServiceRoomWithTwoUnits();
        DB::table('unit_on_walls')->where('serial_number', 'SN-UNIT-2')->delete();

        $this->assertSame([], $this->missingWallUnits($job, $room));
    }

    public function test_an_install_job_keeps_using_the_issuing_based_check(): void
    {
        [$job, $room] = $this->makeServiceRoomWithTwoUnits();
        $job->update(['type' => 'install']);

        $this->assertSame([], $this->missingWallUnits($job->fresh(), $room));
    }

    public function test_units_of_another_contract_sharing_the_room_are_ignored(): void
    {
        // One physical room is often rented by several customers' contracts at once -
        // QA room 460 holds eight active units across four contracts, two each. Only
        // this job's own two may be demanded, or the room could never be closed.
        [$job, $room] = $this->makeServiceRoomWithTwoUnits();

        foreach (['SN-OTHER-1', 'SN-OTHER-2'] as $serial) {
            DB::table('unit_on_walls')->insert([
                'customer_id' => 10,
                'building_id' => 206,
                'room_id' => 13269,
                'room_name' => 'Ruang Ganti Rental Qty 2',
                'serial_number' => $serial,
                'rental_id' => 4,
                'contract_id' => 5948,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->assertSame(
            ['SN-UNIT-1', 'SN-UNIT-2'],
            $this->missingWallUnits($job, $room)
        );
    }

    /** @return array{0: JobSchedule, 1: JobAdviceRoom} */
    private function makeServiceRoomWithTwoUnits(): array
    {
        $advice = JobAdvice::create(['customer_id' => 10, 'type' => 'service']);

        $job = JobSchedule::create([
            'job_number' => 'SBY-CSR/26-09/0023',
            'type' => 'service_first',
            'status' => 'in_progress',
            'job_advice_id' => $advice->id,
            'building_id' => 206,
        ]);

        $contractRoomId = DB::table('contract_rooms')->insertGetId([
            'contract_id' => 5972,
            'room_id' => 13269,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $room = JobAdviceRoom::create([
            'job_advice_id' => $advice->id,
            'contract_room_id' => $contractRoomId,
            'room_name' => 'Ruang Ganti Rental Qty 2',
            'rental_product_id' => 4,
            'quantity' => 2,
        ]);

        foreach (['SN-UNIT-1', 'SN-UNIT-2'] as $serial) {
            DB::table('unit_on_walls')->insert([
                'customer_id' => 10,
                'building_id' => 206,
                'room_id' => 13269,
                'room_name' => 'Ruang Ganti Rental Qty 2',
                'serial_number' => $serial,
                'rental_id' => 4,
                'contract_id' => 5972,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return [$job, $room];
    }

    private function workUnit(
        JobSchedule $job,
        JobAdviceRoom $room,
        string $serial,
        bool $withAfter = true
    ): void {
        $unitId = DB::table('job_schedule_units')->insertGetId([
            'job_schedule_id' => $job->id,
            'job_advice_room_id' => $room->id,
            'mac' => $serial,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $photoTypes = $withAfter ? ['Before Work', 'After Work'] : ['Before Work'];

        foreach ($photoTypes as $photoType) {
            DB::table('job_photos')->insert([
                'job_schedule_room_id' => self::JOB_SCHEDULE_ROOM_ID,
                'job_schedule_unit_id' => $unitId,
                'photo_type' => $photoType,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private const JOB_SCHEDULE_ROOM_ID = 821;

    private function missingWallUnits(JobSchedule $job, JobAdviceRoom $room): array
    {
        $method = new ReflectionMethod(JobController::class, 'getWallUnitsMissingWorkForServiceRoom');
        $method->setAccessible(true);

        $missing = $method->invoke(app(JobController::class), $job, $room, self::JOB_SCHEDULE_ROOM_ID);
        sort($missing);

        return $missing;
    }
}
