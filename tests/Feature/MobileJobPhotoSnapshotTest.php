<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Mobile\JobController;
use App\Models\JobPhoto;
use App\Models\JobSchedule;
use App\Models\JobScheduleRoom;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileJobPhotoSnapshotTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('job_photos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_schedule_id')->nullable();
            $table->unsignedBigInteger('job_schedule_room_id')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('photo_type')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();
        });

        Schema::create('job_advices', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contract_rooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->timestamps();
        });

        Schema::create('job_advice_rooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_advice_id')->nullable();
            $table->unsignedBigInteger('contract_room_id')->nullable();
            $table->string('room_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_advice_id')->nullable();
            $table->string('job_number')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedule_rooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_schedule_id')->nullable();
            $table->unsignedBigInteger('job_advice_room_id')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->string('room_name')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('job_schedule_rooms');
        Schema::dropIfExists('job_schedules');
        Schema::dropIfExists('job_advice_rooms');
        Schema::dropIfExists('contract_rooms');
        Schema::dropIfExists('job_advices');
        Schema::dropIfExists('job_photos');

        parent::tearDown();
    }

    public function test_mobile_room_photo_snapshot_updates_existing_photo_type_per_room(): void
    {
        $controller = app(JobController::class);
        $method = new \ReflectionMethod($controller, 'syncJobPhotoRecord');
        $method->setAccessible(true);

        $method->invoke(
            $controller,
            1433,
            'Before Work',
            'job-verifications/before-first.jpg',
            'Foto sebelum pengerjaan',
            2306
        );

        $method->invoke(
            $controller,
            1433,
            'Before Work',
            'job-verifications/before-retry.jpg',
            'Foto sebelum pengerjaan retry',
            2306
        );

        $this->assertSame(1, JobPhoto::count());
        $this->assertDatabaseHas('job_photos', [
            'job_schedule_id' => 1433,
            'job_schedule_room_id' => 2306,
            'photo_type' => 'Before Work',
            'photo_path' => 'job-verifications/before-retry.jpg',
            'description' => 'Foto sebelum pengerjaan retry',
        ]);
    }

    public function test_photo_display_timestamp_prefers_last_update_time(): void
    {
        $createdAt = Carbon::parse('2026-06-04 03:52:54', 'Asia/Jakarta');
        $updatedAt = Carbon::parse('2026-06-04 04:15:10', 'Asia/Jakarta');

        DB::table('job_photos')->insert([
            'job_schedule_id' => 1433,
            'job_schedule_room_id' => 2306,
            'photo_type' => 'After Work',
            'photo_path' => 'job-verifications/after.jpg',
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ]);

        $photo = JobPhoto::firstOrFail();

        $this->assertTrue($updatedAt->equalTo($photo->display_updated_at));
    }

    public function test_mobile_room_photo_snapshot_backfills_sibling_physical_room(): void
    {
        DB::table('job_advices')->insert([
            'id' => 30,
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
                'room_name' => 'Ruang Lobby',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 91,
                'job_advice_id' => 30,
                'contract_room_id' => 70,
                'room_name' => 'Ruang Lobby',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('job_schedules')->insert([
            'id' => 1433,
            'job_advice_id' => 30,
            'job_number' => 'JKT-IR/26-06/0001',
            'type' => 'install',
            'status' => 'teknisi_selesai_pengerjaan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_schedule_rooms')->insert([
            [
                'id' => 2306,
                'job_schedule_id' => 1433,
                'job_advice_room_id' => 90,
                'room_id' => 500,
                'room_name' => 'Ruang Lobby',
                'status' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2307,
                'job_schedule_id' => 1433,
                'job_advice_room_id' => 91,
                'room_id' => 500,
                'room_name' => 'Ruang Lobby',
                'status' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('job_photos')->insert([
            [
                'job_schedule_id' => 1433,
                'job_schedule_room_id' => 2306,
                'photo_type' => 'Before Work',
                'photo_path' => 'job-verifications/before.jpg',
                'description' => 'Foto sebelum pengerjaan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'job_schedule_id' => 1433,
                'job_schedule_room_id' => 2306,
                'photo_type' => 'After Work',
                'photo_path' => 'job-verifications/after.jpg',
                'description' => 'Foto sesudah pengerjaan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $controller = app(JobController::class);
        $method = new \ReflectionMethod($controller, 'ensurePhysicalRoomPhotosForScheduleRoom');
        $method->setAccessible(true);

        $method->invoke(
            $controller,
            JobSchedule::findOrFail(1433),
            JobScheduleRoom::findOrFail(2307)
        );

        $this->assertDatabaseHas('job_photos', [
            'job_schedule_id' => 1433,
            'job_schedule_room_id' => 2307,
            'photo_type' => 'Before Work',
            'photo_path' => 'job-verifications/before.jpg',
        ]);
        $this->assertDatabaseHas('job_photos', [
            'job_schedule_id' => 1433,
            'job_schedule_room_id' => 2307,
            'photo_type' => 'After Work',
            'photo_path' => 'job-verifications/after.jpg',
        ]);
    }
}
