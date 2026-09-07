<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobScheduleController;
use App\Http\Controllers\Warehouse\InventoryReceivingController;
use App\Models\JobSchedule;
use App\Models\SerialNumber;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

/**
 * QA 7 Sep 2026, contract SBY-CA/26-09/0003. One of the two units in "Ruang Ganti Rental
 * Qty 2" was swapped, and the auto Remove job SBY-RV/26-09/0006 took BOTH off the wall and
 * queued both into Inventory Receiving SBY-IRC/26-09/0009 - even though the technician had
 * recorded exactly one, DW300B2606024 on Unit On Wall 77.
 *
 * Deleting the wrongly queued line then hard-deleted the serial number itself, so
 * DW300B2606022 vanished from the install job and from Detail Stock while unit_on_walls
 * still pointed at the deleted row.
 */
class RemoveJobRecordedUnitScopeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedule_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('unit_on_wall_id')->nullable();
            $table->string('mac')->nullable();
            $table->timestamps();
        });

        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->foreignId('inventory_receiving_id')->nullable();
            $table->string('status')->nullable();
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

        Schema::create('inventory_issuing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('serial_number_id')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_issuing_item_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_issuing_item_id')->nullable();
            $table->foreignId('serial_number_id')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('inventory_issuing_item_serials');
        Schema::dropIfExists('inventory_issuing_items');
        Schema::dropIfExists('unit_on_walls');
        Schema::dropIfExists('serial_numbers');
        Schema::dropIfExists('job_schedule_units');
        Schema::dropIfExists('job_schedules');

        parent::tearDown();
    }

    private function recordedUnitIds(JobSchedule $removeJob): array
    {
        $method = new ReflectionMethod(JobScheduleController::class, 'unitOnWallIdsRecordedByRemoveJob');
        $method->setAccessible(true);

        return $method->invoke(app(JobScheduleController::class), $removeJob);
    }

    private function hasLifeOutsideReceiving(SerialNumber $serialNumber): bool
    {
        $method = new ReflectionMethod(InventoryReceivingController::class, 'serialNumberHasLifeOutsideReceiving');
        $method->setAccessible(true);

        return $method->invoke(app(InventoryReceivingController::class), $serialNumber);
    }

    public function test_the_units_a_remove_job_reported_are_read_from_its_own_rows(): void
    {
        $removeJob = JobSchedule::create([
            'job_number' => 'SBY-RV/26-09/0006',
            'type' => 'remove',
            'status' => 'done_job',
        ]);

        DB::table('unit_on_walls')->insert([
            ['id' => 76, 'serial_number_id' => 509, 'status' => 'active'],
            ['id' => 77, 'serial_number_id' => 511, 'status' => 'active'],
        ]);

        DB::table('job_schedule_units')->insert([
            'job_schedule_id' => $removeJob->id,
            'unit_on_wall_id' => 77,
            'mac' => 'DW300B2606024',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame([77], $this->recordedUnitIds($removeJob));
    }

    public function test_a_scanned_serial_alone_still_names_its_unit(): void
    {
        $removeJob = JobSchedule::create([
            'job_number' => 'SBY-RV/26-09/0007',
            'type' => 'remove',
            'status' => 'done_job',
        ]);

        DB::table('serial_numbers')->insert([
            ['id' => 511, 'serial_number' => 'DW300B2606024'],
            ['id' => 509, 'serial_number' => 'DW300B2606022'],
        ]);

        DB::table('unit_on_walls')->insert([
            ['id' => 76, 'serial_number_id' => 509, 'status' => 'active'],
            ['id' => 77, 'serial_number_id' => 511, 'status' => 'active'],
        ]);

        // The technician's row carries the serial only - the app does not always know the
        // Unit On Wall id.
        DB::table('job_schedule_units')->insert([
            'job_schedule_id' => $removeJob->id,
            'mac' => 'DW300B2606024',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame([77], $this->recordedUnitIds($removeJob));
    }

    public function test_a_remove_job_nobody_reported_on_names_no_units(): void
    {
        $removeJob = JobSchedule::create([
            'job_number' => 'SBY-RV/26-09/0008',
            'type' => 'remove',
            'status' => 'done_job',
        ]);

        // No records at all: the room+rental match stays in charge, capped by the room's
        // own quantity.
        $this->assertSame([], $this->recordedUnitIds($removeJob));
    }

    public function test_a_serial_that_was_on_a_wall_is_not_the_receivings_to_delete(): void
    {
        $serial = SerialNumber::create([
            'serial_number' => 'DW300B2606022',
            'inventory_receiving_id' => 88,
            'status' => 'pending',
        ]);

        DB::table('unit_on_walls')->insert([
            'id' => 76,
            'serial_number_id' => $serial->id,
            'status' => 'removed',
        ]);

        $this->assertTrue($this->hasLifeOutsideReceiving($serial));
    }

    public function test_a_serial_an_issuing_handed_out_is_not_the_receivings_to_delete(): void
    {
        $serial = SerialNumber::create([
            'serial_number' => 'DW300B2606024',
            'inventory_receiving_id' => 88,
            'status' => 'pending',
        ]);

        DB::table('inventory_issuing_item_serials')->insert([
            'inventory_issuing_item_id' => 541,
            'serial_number_id' => $serial->id,
        ]);

        $this->assertTrue($this->hasLifeOutsideReceiving($serial));
    }

    public function test_a_serial_this_receiving_brought_in_stays_deletable(): void
    {
        $serial = SerialNumber::create([
            'serial_number' => 'DW300B2606099',
            'inventory_receiving_id' => 88,
            'status' => 'pending',
        ]);

        $this->assertFalse($this->hasLifeOutsideReceiving($serial));
    }
}
