<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Mobile\JobController;
use App\Models\JobSchedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA 26 Nov 2026, JKT-IF/26-11/0003 Ruang-3: satu ruangan berisi dua rental (TestUO1b3x dan
 * TestHIKDisp). Teknisi baru memasang TestHIKDisp, tetapi syncInstallRoomsFromActiveUnitOnWall
 * ikut menandai baris TestUO1b3x selesai karena ruangan fisiknya sama, dan APK langsung minta BA.
 */
class MobileUnitOnWallSyncPerRentalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['job_schedules', 'job_advices', 'job_advice_rooms', 'job_schedule_rooms', 'job_schedule_room_rentals',
            'job_assign_schedules', 'inventory_issuing_items', 'unit_on_walls'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('job_schedules', function (Blueprint $t) {
            $t->id();
            $t->string('job_number')->nullable();
            $t->string('type')->nullable();
            $t->string('status')->nullable();
            $t->unsignedBigInteger('job_advice_id')->nullable();
            $t->unsignedBigInteger('building_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('job_advices', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('job_advice_rooms', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('job_advice_id')->nullable();
            $t->unsignedBigInteger('rental_product_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('job_schedule_rooms', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('job_schedule_id')->nullable();
            $t->unsignedBigInteger('job_advice_room_id')->nullable();
            $t->unsignedBigInteger('room_id')->nullable();
            $t->string('room_name')->nullable();
            $t->string('status')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->unsignedBigInteger('completed_by')->nullable();
            $t->text('completion_notes')->nullable();
            $t->string('completion_source')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('job_schedule_room_rentals', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('job_schedule_room_id')->nullable();
            $t->unsignedBigInteger('job_advice_room_id')->nullable();
            $t->boolean('is_primary')->default(false);
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('job_assign_schedules', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('job_schedule_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('inventory_issuing_items', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('job_assign_schedule_id')->nullable();
            $t->unsignedBigInteger('serial_number_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('unit_on_walls', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('serial_number_id')->nullable();
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->unsignedBigInteger('building_id')->nullable();
            $t->unsignedBigInteger('room_id')->nullable();
            $t->string('room_name')->nullable();
            $t->unsignedBigInteger('rental_id')->nullable();
            $t->string('status')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        DB::table('job_advices')->insert(['id' => 1, 'customer_id' => 15230]);
        DB::table('job_advice_rooms')->insert([
            ['id' => 78125, 'job_advice_id' => 1, 'rental_product_id' => 382], // TestUO1b3x
            ['id' => 78126, 'job_advice_id' => 1, 'rental_product_id' => 383], // TestHIKDisp
        ]);
        DB::table('job_schedules')->insert(['id' => 59849, 'job_number' => 'JKT-IF/26-11/0003', 'type' => 'install_free', 'status' => 'verifikasi', 'job_advice_id' => 1, 'building_id' => 20025]);
        DB::table('job_schedule_rooms')->insert([
            ['id' => 164337, 'job_schedule_id' => 59849, 'job_advice_room_id' => 78125, 'room_id' => 45026, 'room_name' => 'Ruang-3', 'status' => 'pending'],
            ['id' => 164340, 'job_schedule_id' => 59849, 'job_advice_room_id' => 78126, 'room_id' => 45026, 'room_name' => 'Ruang-3', 'status' => 'completed'],
        ]);
        DB::table('job_schedule_room_rentals')->insert(['job_schedule_room_id' => 164337, 'job_advice_room_id' => 78126]);
        DB::table('job_assign_schedules')->insert(['id' => 10, 'job_schedule_id' => 59849]);
        DB::table('inventory_issuing_items')->insert([
            ['job_assign_schedule_id' => 10, 'serial_number_id' => 501], // HIKV
            ['job_assign_schedule_id' => 10, 'serial_number_id' => 502], // dispenser
        ]);
        // Unit HIKV terpasang untuk TestHIKDisp.
        DB::table('unit_on_walls')->insert(['serial_number_id' => 501, 'customer_id' => 15230, 'building_id' => 20025, 'room_id' => 45026, 'room_name' => 'Ruang-3', 'rental_id' => 383, 'status' => 'active']);
    }

    private function sync(): void
    {
        $method = new \ReflectionMethod(JobController::class, 'syncInstallRoomsFromActiveUnitOnWall');
        $method->setAccessible(true);
        $method->invoke(app(JobController::class), JobSchedule::find(59849));
    }

    public function test_a_unit_of_another_rental_in_the_same_room_does_not_complete_the_row(): void
    {
        $this->sync();

        $this->assertSame('pending', DB::table('job_schedule_rooms')->where('id', 164337)->value('status'));
    }

    public function test_the_row_completes_once_every_rental_on_it_has_its_own_unit(): void
    {
        DB::table('unit_on_walls')->insert(['serial_number_id' => 502, 'customer_id' => 15230, 'building_id' => 20025, 'room_id' => 45026, 'room_name' => 'Ruang-3', 'rental_id' => 382, 'status' => 'active']);

        $this->sync();

        $this->assertSame('completed', DB::table('job_schedule_rooms')->where('id', 164337)->value('status'));
    }
}
