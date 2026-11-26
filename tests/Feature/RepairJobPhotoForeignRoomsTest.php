<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RepairJobPhotoForeignRoomsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['job_photos', 'job_schedule_room_rentals', 'job_schedule_rooms', 'job_schedules'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::create('job_schedules', function (Blueprint $t) {
            $t->id();
            $t->string('job_number')->nullable();
        });
        Schema::create('job_schedule_rooms', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('job_schedule_id')->nullable();
            $t->unsignedBigInteger('job_advice_room_id')->nullable();
            $t->string('room_name')->nullable();
            $t->softDeletes();
        });
        Schema::create('job_schedule_room_rentals', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('job_schedule_room_id')->nullable();
            $t->unsignedBigInteger('job_advice_room_id')->nullable();
            $t->softDeletes();
        });
        Schema::create('job_photos', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('job_schedule_id');
            $t->unsignedBigInteger('job_schedule_room_id')->nullable();
            $t->timestamps();
        });

        DB::table('job_schedules')->insert([
            ['id' => 59849, 'job_number' => 'JKT-IF/26-11/0003'],
            ['id' => 32347, 'job_number' => 'PWT-CSR/26-01/0001'],
            ['id' => 700, 'job_number' => 'JKT-CSR/26-10/0001'],
        ]);
        DB::table('job_schedule_rooms')->insert([
            ['id' => 78124, 'job_schedule_id' => 32347, 'job_advice_room_id' => 18527, 'room_name' => 'Hijab Mandjha Purwokerto'],
            ['id' => 78126, 'job_schedule_id' => 32347, 'job_advice_room_id' => 18528, 'room_name' => 'Hijab Mandjha Purwokerto'],
            ['id' => 164337, 'job_schedule_id' => 59849, 'job_advice_room_id' => 78124, 'room_name' => 'Ruang-3'],
            ['id' => 164338, 'job_schedule_id' => 59849, 'job_advice_room_id' => 78130, 'room_name' => 'Ruang-4'],
            ['id' => 800, 'job_schedule_id' => 700, 'job_advice_room_id' => 5000, 'room_name' => 'Ruang Periksa Simo'],
        ]);
        DB::table('job_photos')->insert([
            ['id' => 1, 'job_schedule_id' => 59849, 'job_schedule_room_id' => 78124],  // salah, ada padanan
            ['id' => 2, 'job_schedule_id' => 59849, 'job_schedule_room_id' => 78126],  // salah, tanpa padanan
            ['id' => 3, 'job_schedule_id' => 59849, 'job_schedule_room_id' => 164337], // benar
            ['id' => 4, 'job_schedule_id' => 700, 'job_schedule_room_id' => 78126],    // salah, job satu ruangan
        ]);
    }

    public function test_dry_run_writes_nothing(): void
    {
        $this->artisan('job-photos:repair-foreign-rooms')->assertSuccessful();

        $this->assertSame(78124, (int) DB::table('job_photos')->where('id', 1)->value('job_schedule_room_id'));
    }

    public function test_apply_remaps_or_clears_only_foreign_rooms(): void
    {
        $this->artisan('job-photos:repair-foreign-rooms', ['--apply' => true])->assertSuccessful();

        $this->assertSame(164337, (int) DB::table('job_photos')->where('id', 1)->value('job_schedule_room_id'));
        $this->assertNull(DB::table('job_photos')->where('id', 2)->value('job_schedule_room_id'));
        $this->assertSame(164337, (int) DB::table('job_photos')->where('id', 3)->value('job_schedule_room_id'));
        $this->assertSame(800, (int) DB::table('job_photos')->where('id', 4)->value('job_schedule_room_id'), 'Job satu ruangan: fotonya milik ruangan itu.');
    }
}
