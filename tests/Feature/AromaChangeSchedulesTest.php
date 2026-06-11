<?php

namespace Tests\Feature;

use App\Http\Controllers\Marketing\AromaChangeController;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AromaChangeSchedulesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-06-12'));

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contract_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->timestamps();
        });

        Schema::create('job_advices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_advice_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->string('contract_number')->nullable();
            $table->string('status')->nullable();
            $table->integer('period')->nullable();
            $table->date('schedule_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedule_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('contracts')->insert([
            'id' => 10,
            'contract_number' => 'SBY-CA/26-06/0001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('contract_rooms')->insert([
            [
                'id' => 100,
                'contract_id' => 10,
                'room_id' => 501,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 101,
                'contract_id' => 10,
                'room_id' => 502,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('job_advices')->insert([
            'id' => 20,
            'contract_id' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('job_schedule_rooms');
        Schema::dropIfExists('job_schedules');
        Schema::dropIfExists('job_advices');
        Schema::dropIfExists('contract_rooms');
        Schema::dropIfExists('contracts');

        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_aroma_change_schedules_are_scoped_to_selected_room_and_unique_per_period(): void
    {
        DB::table('job_schedules')->insert([
            [
                'id' => 1000,
                'job_advice_id' => 20,
                'room_id' => 501,
                'contract_number' => 'SBY-CA/26-06/0001',
                'status' => 'scheduled',
                'period' => 2,
                'schedule_date' => '2026-07-01',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 1001,
                'job_advice_id' => 20,
                'room_id' => 502,
                'contract_number' => 'SBY-CA/26-06/0001',
                'status' => 'scheduled',
                'period' => 2,
                'schedule_date' => '2026-07-01',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 1002,
                'job_advice_id' => 20,
                'room_id' => 501,
                'contract_number' => 'SBY-CA/26-06/0001',
                'status' => 'scheduled',
                'period' => 2,
                'schedule_date' => '2026-07-15',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 1003,
                'job_advice_id' => 20,
                'room_id' => 501,
                'contract_number' => 'SBY-CA/26-06/0001',
                'status' => 'scheduled',
                'period' => 3,
                'schedule_date' => '2026-08-01',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = (new AromaChangeController())->getSchedules(Request::create(
            '/marketing/aroma-changes/get-schedules',
            'GET',
            ['contract_id' => 10, 'contract_room_id' => 100]
        ));

        $payload = $response->getData(true);

        $this->assertSame([
            'Jul 2026 (Service Period: 2)',
            'Aug 2026 (Service Period: 3)',
        ], collect($payload)->pluck('label')->all());
        $this->assertSame([1000, 1003], collect($payload)->pluck('id')->all());
    }

    public function test_aroma_change_schedules_include_multi_room_schedule_for_selected_room_once(): void
    {
        DB::table('job_schedules')->insert([
            [
                'id' => 2000,
                'job_advice_id' => 20,
                'room_id' => null,
                'contract_number' => 'SBY-CA/26-06/0001',
                'status' => 'scheduled',
                'period' => 4,
                'schedule_date' => '2026-09-01',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2001,
                'job_advice_id' => 20,
                'room_id' => null,
                'contract_number' => 'SBY-CA/26-06/0001',
                'status' => 'scheduled',
                'period' => 4,
                'schedule_date' => '2026-09-05',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('job_schedule_rooms')->insert([
            [
                'job_schedule_id' => 2000,
                'room_id' => 501,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'job_schedule_id' => 2001,
                'room_id' => 502,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = (new AromaChangeController())->getSchedules(Request::create(
            '/marketing/aroma-changes/get-schedules',
            'GET',
            ['contract_id' => 10, 'contract_room_id' => 100]
        ));

        $payload = $response->getData(true);

        $this->assertSame(['Sep 2026 (Service Period: 4)'], collect($payload)->pluck('label')->all());
        $this->assertSame([2000], collect($payload)->pluck('id')->all());
    }
}
