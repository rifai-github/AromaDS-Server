<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Mobile\JobController;
use App\Models\JobPhoto;
use Illuminate\Database\Schema\Blueprint;
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
    }

    protected function tearDown(): void
    {
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
}
