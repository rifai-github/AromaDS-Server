<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobScheduleController;
use App\Models\JobSchedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA 26 Nov 2026 (JKT-JA/26-11/0003): Ruang-1 dan Ruang-6 sudah BA Install Free tetapi Remove
 * Free tidak pernah muncul. Syarat lama "Unit On Wall baru dibuat di panggilan BA" selalu false
 * sejak unit dicatat per ruangan saat ruangan selesai (25 Nov).
 */
class InstallFreeRemoveFreeGateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['unit_on_walls', 'contracts', 'quotations', 'contract_rooms', 'job_schedule_rooms', 'job_advice_rooms', 'job_schedules'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::create('unit_on_walls', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('install_job_schedule_id')->nullable();
            $t->string('status')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('quotations', function (Blueprint $t) {
            $t->id();
            $t->string('quotation_number')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('contracts', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('quotation_id')->nullable();
            $t->string('contract_number')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('contract_rooms', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('contract_id');
            $t->unsignedBigInteger('room_id')->nullable();
            $t->timestamps();
        });
        Schema::create('job_advice_rooms', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('room_id')->nullable();
            $t->unsignedBigInteger('contract_room_id')->nullable();
            $t->unsignedBigInteger('quotation_room_id')->nullable();
            $t->unsignedBigInteger('remove_job_schedule_id')->nullable();
            $t->string('room_name')->nullable();
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
        Schema::create('job_schedules', function (Blueprint $t) {
            $t->id();
            $t->string('job_number')->nullable();
            $t->string('type')->nullable();
            $t->string('status')->nullable();
            $t->string('quotation_number')->nullable();
            $t->unsignedBigInteger('room_id')->nullable();
            $t->text('internal_notes')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
    }

    private function contractWithRooms(array $roomIds): int
    {
        $quotationId = DB::table('quotations')->insertGetId(['quotation_number' => 'JKT-SQ/26-11/0003']);
        $contractId = DB::table('contracts')->insertGetId(['quotation_id' => $quotationId, 'contract_number' => 'BLP-CA/26-11/0004']);
        foreach ($roomIds as $roomId) {
            DB::table('contract_rooms')->insert(['contract_id' => $contractId, 'room_id' => $roomId]);
        }

        return $contractId;
    }

    private function gate(int $installJobId, bool $createdNow = false, ?string $removeDate = '2026-11-28'): bool
    {
        $job = new JobSchedule();
        $job->id = $installJobId;
        $job->quotation_number = 'JKT-SQ/26-11/0003';

        $jobAdvice = (object) [
            'remove_date' => $removeDate,
            'quotation' => (object) ['quotation_number' => 'JKT-SQ/26-11/0003'],
        ];

        return app(JobScheduleController::class)->installFreeNeedsRemoveFree($job, $jobAdvice, $createdNow);
    }

    public function test_unit_recorded_earlier_at_room_completion_still_triggers_remove_free(): void
    {
        DB::table('unit_on_walls')->insert(['install_job_schedule_id' => 59848, 'status' => 'active']);

        $this->assertTrue($this->gate(59848));
    }

    public function test_no_unit_on_the_wall_means_no_remove_free(): void
    {
        $this->assertFalse($this->gate(59848));

        DB::table('unit_on_walls')->insert(['install_job_schedule_id' => 59848, 'status' => 'removed']);
        $this->assertFalse($this->gate(59848));
    }

    public function test_unit_created_in_the_same_call_keeps_working(): void
    {
        $this->assertTrue($this->gate(59848, true));
    }

    public function test_no_remove_date_means_no_remove_free(): void
    {
        DB::table('unit_on_walls')->insert(['install_job_schedule_id' => 59848, 'status' => 'active']);

        $this->assertFalse($this->gate(59848, true, null));
    }

    public function test_trial_already_turned_into_a_contract_gets_no_remove_free(): void
    {
        DB::table('unit_on_walls')->insert(['install_job_schedule_id' => 59848, 'status' => 'active']);
        $quotationId = DB::table('quotations')->insertGetId(['quotation_number' => 'JKT-SQ/26-11/0003']);
        DB::table('contracts')->insert(['quotation_id' => $quotationId]);

        $this->assertFalse($this->gate(59848));
        $this->assertFalse($this->gate(59848, true));
    }

    public function test_all_three_completion_paths_use_the_gate(): void
    {
        $web = file_get_contents(app_path('Http/Controllers/Operational/JobScheduleController.php'));
        $mobile = file_get_contents(app_path('Http/Controllers/Api/Mobile/JobController.php'));

        $this->assertSame(2, substr_count($web, '$this->installFreeNeedsRemoveFree('));
        $this->assertSame(1, substr_count($mobile, '$jobScheduleController->installFreeNeedsRemoveFree('));
        $this->assertStringNotContainsString('$unitCreated && $isInstallTypeSchedule', $mobile);
    }

    public function test_trial_room_left_out_of_the_contract_still_gets_remove_free(): void
    {
        // QA 26 Nov 2026: SQ-2 hanya membawa Ruang-1 (room 10) ke kontrak; Ruang-3 (room 30) tidak.
        DB::table('unit_on_walls')->insert(['install_job_schedule_id' => 59848, 'status' => 'active']);
        $this->contractWithRooms([10]);
        DB::table('job_schedule_rooms')->insert([
            ['job_schedule_id' => 59848, 'room_id' => 10, 'room_name' => 'Ruang-1', 'status' => 'completed'],
            ['job_schedule_id' => 59848, 'room_id' => 30, 'room_name' => 'Ruang-3', 'status' => 'completed'],
        ]);

        $this->assertTrue($this->gate(59848));

        DB::table('job_schedule_rooms')->where('room_id', 30)->delete();
        $this->assertFalse($this->gate(59848), 'Semua ruangan masuk kontrak: unit tetap di lokasi.');
    }

    public function test_contract_cancels_only_remove_free_of_rooms_carried_into_it(): void
    {
        $contractId = $this->contractWithRooms([10, 40]);

        DB::table('job_schedules')->insert([
            ['id' => 1, 'job_number' => 'RF-OUT', 'type' => 'remove_free', 'status' => 'new_job', 'quotation_number' => 'JKT-SQ/26-11/0003'],
            ['id' => 2, 'job_number' => 'RF-IN', 'type' => 'remove_free', 'status' => 'new_job', 'quotation_number' => 'JKT-SQ/26-11/0003'],
            ['id' => 3, 'job_number' => 'RF-MIX', 'type' => 'remove_free', 'status' => 'new_job', 'quotation_number' => 'JKT-SQ/26-11/0003'],
            ['id' => 4, 'job_number' => 'RF-LEGACY', 'type' => 'remove_free', 'status' => 'new_job', 'quotation_number' => 'JKT-SQ/26-11/0003'],
        ]);
        DB::table('job_advice_rooms')->insert(['id' => 500, 'room_id' => 40, 'room_name' => 'Ruang-4', 'remove_job_schedule_id' => 3]);
        DB::table('job_schedule_rooms')->insert([
            ['id' => 11, 'job_schedule_id' => 1, 'room_id' => 30, 'room_name' => 'Ruang-3', 'job_advice_room_id' => null],
            ['id' => 21, 'job_schedule_id' => 2, 'room_id' => 10, 'room_name' => 'Ruang-1', 'job_advice_room_id' => null],
            ['id' => 31, 'job_schedule_id' => 3, 'room_id' => 30, 'room_name' => 'Ruang-3', 'job_advice_room_id' => null],
            ['id' => 32, 'job_schedule_id' => 3, 'room_id' => null, 'room_name' => 'Ruang-4', 'job_advice_room_id' => 500],
        ]);

        $cancelled = app(\App\Services\Operational\InstallFreeContractRoomScope::class)->cancelPendingRemoveFreeForContract(
            \App\Models\Contract::findOrFail($contractId),
            ['new_job'],
            'Auto-cancelled because quotation turned into Contract: BLP-CA/26-11/0004'
        );

        $this->assertSame(2, $cancelled);
        $this->assertSame('new_job', DB::table('job_schedules')->where('id', 1)->value('status'), 'Ruang-3 tak ikut kontrak: tetap dicabut.');
        $this->assertSame('cancelled', DB::table('job_schedules')->where('id', 2)->value('status'));
        $this->assertSame('new_job', DB::table('job_schedules')->where('id', 3)->value('status'));
        $this->assertNotNull(DB::table('job_schedule_rooms')->where('id', 32)->value('deleted_at'), 'Ruang-4 dilepas dari RF campuran.');
        $this->assertNull(DB::table('job_schedule_rooms')->where('id', 31)->value('deleted_at'));
        $this->assertNull(DB::table('job_advice_rooms')->where('id', 500)->value('remove_job_schedule_id'));
        $this->assertSame('cancelled', DB::table('job_schedules')->where('id', 4)->value('status'), 'RF lama tanpa data ruangan: perilaku lama.');
    }
}
