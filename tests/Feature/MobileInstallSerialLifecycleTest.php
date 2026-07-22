<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Mobile\JobController;
use App\Models\JobAdvice;
use App\Models\JobSchedule;
use App\Models\SerialNumber;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileInstallSerialLifecycleTest extends TestCase
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
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_advice_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_advice_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedule_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('job_advice_room_id')->nullable();
            $table->foreignId('unit_id')->nullable();
            $table->foreignId('unit_on_wall_id')->nullable();
            $table->string('mac')->nullable();
            $table->string('device_type')->nullable();
            $table->string('device_name')->nullable();
            $table->json('device_snapshot')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->string('photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number')->nullable();
            $table->string('status')->nullable();
            $table->string('location_type')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('master_product_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('serial_numbers');
        Schema::dropIfExists('job_schedule_units');
        Schema::dropIfExists('job_advice_rooms');
        Schema::dropIfExists('job_schedules');
        Schema::dropIfExists('job_advices');

        parent::tearDown();
    }

    public function test_save_scanned_unit_does_not_mark_install_serial_in_use_before_room_completion(): void
    {
        $jobAdvice = JobAdvice::create([
            'customer_id' => 10,
            'type' => 'install',
        ]);

        $job = JobSchedule::create([
            'job_number' => 'BDG-IR/26-05/0001',
            'type' => 'install',
            'status' => 'in_progress',
            'job_advice_id' => $jobAdvice->id,
        ]);

        $serialNumber = SerialNumber::create([
            'serial_number' => 'C100B0526002',
            'status' => 'on_hand',
            'location_type' => 'technician',
            'location_id' => 5,
        ]);

        $response = app(JobController::class)->saveScannedUnit(Request::create(
            '/api/v1/mobile/units/save-scanned',
            'POST',
            [
                'job_schedule_id' => $job->id,
                'room_id' => 99,
                'mac' => $serialNumber->serial_number,
                'device_type' => 'Diffuser',
                'device_snapshot' => '{}',
            ]
        ));

        $payload = $response->getData(true);

        $this->assertSame('success', $payload['status']);
        $this->assertSame('on_hand', $serialNumber->fresh()->status);
        $this->assertDatabaseHas('job_schedule_units', [
            'job_schedule_id' => $job->id,
            'mac' => 'C100B0526002',
        ]);
    }
}
