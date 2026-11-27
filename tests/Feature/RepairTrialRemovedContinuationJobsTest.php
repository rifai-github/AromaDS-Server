<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA 27 Nov 2026 (BLP-CA/26-11/0005): Job Check Ruang-1 (job 59939-59949) terbentuk padahal unit
 * trial Ruang-1 sudah dicabut (ADS-RF/26-11/0001 selesai). Ruang-3 yang unitnya masih di lokasi tetap.
 */
class RepairTrialRemovedContinuationJobsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['quotations', 'contracts', 'job_advices', 'job_advice_rooms', 'job_schedules', 'job_schedule_rooms', 'job_schedule_room_rentals', 'unit_on_walls'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::create('quotations', function (Blueprint $t) {
            $t->id();
            $t->string('quotation_number')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('contracts', function (Blueprint $t) {
            $t->id();
            $t->string('contract_number')->nullable();
            $t->unsignedBigInteger('quotation_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('job_advices', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('contract_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('job_advice_rooms', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('room_id')->nullable();
            $t->unsignedBigInteger('contract_room_id')->nullable();
            $t->unsignedBigInteger('quotation_room_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('job_schedules', function (Blueprint $t) {
            $t->id();
            $t->string('job_number')->nullable();
            $t->string('type')->nullable();
            $t->string('status')->nullable();
            $t->unsignedBigInteger('job_advice_id')->nullable();
            $t->string('quotation_number')->nullable();
            $t->string('contract_number')->nullable();
            $t->unsignedBigInteger('room_id')->nullable();
            $t->integer('period')->nullable();
            $t->date('ba_date')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('job_schedule_rooms', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('job_schedule_id');
            $t->unsignedBigInteger('job_advice_room_id')->nullable();
            $t->unsignedBigInteger('room_id')->nullable();
            $t->string('room_name')->nullable();
            $t->string('status')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('job_schedule_room_rentals', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('job_schedule_room_id');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('unit_on_walls', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('install_job_schedule_id')->nullable();
            $t->unsignedBigInteger('room_id')->nullable();
            $t->string('status')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        DB::table('quotations')->insert(['id' => 30576, 'quotation_number' => 'BLP-SQ/26-11/0133']);
        DB::table('contracts')->insert(['id' => 21791, 'contract_number' => 'BLP-CA/26-11/0005', 'quotation_id' => 30576]);
        DB::table('job_advices')->insert(['id' => 27674, 'contract_id' => null]); // JA trial

        DB::table('job_schedules')->insert([
            // trial
            ['id' => 59930, 'type' => 'install_free', 'status' => 'done_job', 'job_advice_id' => 27674, 'quotation_number' => 'BLP-SQ/26-11/0133', 'contract_number' => null, 'period' => null, 'job_number' => 'ADS-IF/1', 'ba_date' => '2026-11-27'],
            ['id' => 59931, 'type' => 'install_free', 'status' => 'done_job', 'job_advice_id' => 27674, 'quotation_number' => 'BLP-SQ/26-11/0133', 'contract_number' => null, 'period' => null, 'job_number' => 'ADS-IF/1', 'ba_date' => '2026-11-27'],
            ['id' => 59935, 'type' => 'remove_free', 'status' => 'done_job', 'job_advice_id' => 27674, 'quotation_number' => 'BLP-SQ/26-11/0133', 'contract_number' => null, 'period' => null, 'job_number' => 'ADS-RF/26-11/0001', 'ba_date' => '2026-11-27'],
            // Job Check salah (Ruang-1, unit sudah dicabut)
            ['id' => 59939, 'type' => 'service_routine', 'status' => 'scheduled', 'job_advice_id' => 27674, 'quotation_number' => 'BLP-SQ/26-11/0133', 'contract_number' => 'BLP-CA/26-11/0005', 'period' => 2, 'job_number' => null, 'ba_date' => null],
            ['id' => 59940, 'type' => 'service_routine', 'status' => 'scheduled', 'job_advice_id' => 27674, 'quotation_number' => 'BLP-SQ/26-11/0133', 'contract_number' => 'BLP-CA/26-11/0005', 'period' => 3, 'job_number' => null, 'ba_date' => null],
            // Job Check benar (Ruang-3, unit masih di lokasi)
            ['id' => 59950, 'type' => 'service_routine', 'status' => 'scheduled', 'job_advice_id' => 27674, 'quotation_number' => 'BLP-SQ/26-11/0133', 'contract_number' => 'BLP-CA/26-11/0005', 'period' => 2, 'job_number' => null, 'ba_date' => null],
            // Ruang-1 tapi sudah dikerjakan: jangan disentuh
            ['id' => 59960, 'type' => 'service_routine', 'status' => 'assign_material', 'job_advice_id' => 27674, 'quotation_number' => 'BLP-SQ/26-11/0133', 'contract_number' => 'BLP-CA/26-11/0005', 'period' => 4, 'job_number' => 'X', 'ba_date' => null],
        ]);
        DB::table('job_schedule_rooms')->insert([
            ['id' => 1, 'job_schedule_id' => 59935, 'room_id' => 45024, 'room_name' => 'Ruang-1', 'status' => 'completed'],
            ['id' => 2, 'job_schedule_id' => 59939, 'room_id' => 45024, 'room_name' => 'Ruang-1', 'status' => 'pending'],
            ['id' => 3, 'job_schedule_id' => 59940, 'room_id' => 45024, 'room_name' => 'Ruang-1', 'status' => 'pending'],
            ['id' => 4, 'job_schedule_id' => 59950, 'room_id' => 45026, 'room_name' => 'Ruang-3', 'status' => 'pending'],
            ['id' => 5, 'job_schedule_id' => 59960, 'room_id' => 45024, 'room_name' => 'Ruang-1', 'status' => 'pending'],
        ]);
        DB::table('job_schedule_room_rentals')->insert([
            ['id' => 11, 'job_schedule_room_id' => 2],
            ['id' => 12, 'job_schedule_room_id' => 4],
        ]);
        DB::table('unit_on_walls')->insert([
            ['id' => 59, 'install_job_schedule_id' => 59930, 'room_id' => 45024, 'status' => 'removed'],
            ['id' => 60, 'install_job_schedule_id' => 59931, 'room_id' => 45026, 'status' => 'active'],
        ]);
    }

    public function test_dry_run_changes_nothing(): void
    {
        $this->artisan('contracts:repair-trial-removed-continuation-jobs')->assertSuccessful();

        $this->assertSame(0, DB::table('job_schedules')->whereNotNull('deleted_at')->count());
    }

    public function test_apply_removes_only_unstarted_jobs_of_the_removed_room(): void
    {
        $this->artisan('contracts:repair-trial-removed-continuation-jobs', ['--apply' => true])->assertSuccessful();

        $deleted = DB::table('job_schedules')->whereNotNull('deleted_at')->orderBy('id')->pluck('id')->all();
        $this->assertSame([59939, 59940], $deleted);
        $this->assertNotNull(DB::table('job_schedule_rooms')->where('id', 2)->value('deleted_at'));
        $this->assertNotNull(DB::table('job_schedule_room_rentals')->where('id', 11)->value('deleted_at'));
        $this->assertNull(DB::table('job_schedule_room_rentals')->where('id', 12)->value('deleted_at'), 'Ruang-3 tetap.');
        $this->assertNull(DB::table('job_schedules')->where('id', 59960)->value('deleted_at'), 'Job yang sudah jalan tidak disentuh.');
    }

    public function test_contract_option_limits_the_scope(): void
    {
        $this->artisan('contracts:repair-trial-removed-continuation-jobs', ['--apply' => true, '--contract' => 'XYZ'])->assertSuccessful();

        $this->assertSame(0, DB::table('job_schedules')->whereNotNull('deleted_at')->count());
    }
}
