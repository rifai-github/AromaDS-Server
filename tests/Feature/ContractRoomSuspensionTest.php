<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\JobAdviceRoom;
use App\Models\JobSchedule;
use App\Services\Operational\ContractRoomSuspensionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * MOM 19 Nov 2026 p6: Suspend/Unsuspend room kontrak. Room yang di-suspend terkunci; job-nya
 * tidak bisa dikerjakan di aplikasi teknisi maupun web. Setiap aksi tercatat.
 */
class ContractRoomSuspensionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-22 10:00:00');

        $tables = ['contract_room_suspensions', 'audit_logs', 'job_schedule_rooms', 'job_schedules', 'job_advice_rooms', 'job_advices', 'contract_rooms', 'master_rooms', 'contracts'];
        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('contracts', function (Blueprint $t) {
            $t->id();
            $t->string('contract_number')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('master_rooms', function (Blueprint $t) {
            $t->id();
            $t->string('room_name')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('contract_rooms', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('contract_id');
            $t->unsignedBigInteger('room_id')->nullable();
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
            $t->unsignedBigInteger('job_advice_id');
            $t->unsignedBigInteger('contract_room_id')->nullable();
            $t->unsignedBigInteger('quotation_room_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('job_schedules', function (Blueprint $t) {
            $t->id();
            $t->string('type')->nullable();
            $t->string('status')->nullable();
            $t->unsignedBigInteger('job_advice_id')->nullable();
            $t->unsignedBigInteger('room_id')->nullable();
            $t->date('schedule_date')->nullable();
            $t->date('reschedule_date')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('job_schedule_rooms', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('job_schedule_id');
            $t->unsignedBigInteger('job_advice_room_id')->nullable();
            $t->unsignedBigInteger('room_id')->nullable();
            $t->string('status')->default('pending');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('action')->nullable();
            $t->string('model_type')->nullable();
            $t->unsignedBigInteger('model_id')->nullable();
            $t->text('old_values')->nullable();
            $t->text('new_values')->nullable();
            $t->text('changed_fields')->nullable();
            $t->string('ip_address')->nullable();
            $t->text('user_agent')->nullable();
            $t->string('page_name')->nullable();
            $t->string('module_name')->nullable();
            $t->timestamps();
        });
        (require database_path('migrations/2026_11_22_000004_create_contract_room_suspensions_table.php'))->up();

        DB::table('contracts')->insert(['id' => 1, 'contract_number' => 'JKT-CA/26-11/0001']);
        DB::table('master_rooms')->insert([['id' => 101, 'room_name' => 'Lobby'], ['id' => 102, 'room_name' => 'Meeting']]);
        DB::table('contract_rooms')->insert([
            ['id' => 11, 'contract_id' => 1, 'room_id' => 101],
            ['id' => 12, 'contract_id' => 1, 'room_id' => 102],
        ]);
        DB::table('job_advices')->insert(['id' => 1, 'contract_id' => 1]);
        DB::table('job_advice_rooms')->insert([
            ['id' => 21, 'job_advice_id' => 1, 'contract_room_id' => 11],
            ['id' => 22, 'job_advice_id' => 1, 'contract_room_id' => 12],
        ]);
        // Job 1 memegang kedua room; job 2 hanya Lobby.
        DB::table('job_schedules')->insert([
            ['id' => 1, 'type' => 'service', 'status' => 'new_job', 'job_advice_id' => 1],
            ['id' => 2, 'type' => 'service', 'status' => 'new_job', 'job_advice_id' => 1],
        ]);
        DB::table('job_schedule_rooms')->insert([
            ['job_schedule_id' => 1, 'job_advice_room_id' => 21, 'room_id' => 101],
            ['job_schedule_id' => 1, 'job_advice_room_id' => 22, 'room_id' => 102],
            ['job_schedule_id' => 2, 'job_advice_room_id' => 21, 'room_id' => 101],
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function service(): ContractRoomSuspensionService
    {
        return new ContractRoomSuspensionService;
    }

    public function test_suspend_locks_the_room_and_records_who_and_when(): void
    {
        $count = $this->service()->suspend(Contract::findOrFail(1), [11], '2026-11-22', 7, 'Grace period lewat');

        $this->assertSame(1, $count);
        $row = DB::table('contract_room_suspensions')->sole();
        $this->assertSame(11, (int) $row->contract_room_id);
        $this->assertSame(101, (int) $row->room_id);
        $this->assertSame(7, (int) $row->suspended_by);
        $this->assertNotNull($row->suspended_at);
        $this->assertSame(1, DB::table('audit_logs')->where('action', 'contract_room_suspended')->count());

        $this->assertTrue($this->service()->isJobAdviceRoomSuspended(JobAdviceRoom::findOrFail(21)));
        $this->assertFalse($this->service()->isJobAdviceRoomSuspended(JobAdviceRoom::findOrFail(22)));
    }

    public function test_job_level_actions_are_blocked_only_when_every_room_is_suspended(): void
    {
        $this->service()->suspend(Contract::findOrFail(1), [11], '2026-11-22', 7);

        // Job 1 masih punya Meeting yang aktif: tiba/mulai boleh, Done Job semua room tidak.
        $this->assertNull($this->service()->jobBlockReason(JobSchedule::findOrFail(1), true));
        $this->assertNotNull($this->service()->jobBlockReason(JobSchedule::findOrFail(1), false));

        // Job 2 hanya Lobby: semua room suspend -> ditolak.
        $this->assertStringContainsString('Lobby', (string) $this->service()->jobBlockReason(JobSchedule::findOrFail(2), true));
    }

    public function test_a_future_suspend_date_does_not_block_yet(): void
    {
        $this->service()->suspend(Contract::findOrFail(1), [11], '2026-12-01', 7);

        $this->assertFalse($this->service()->isJobAdviceRoomSuspended(JobAdviceRoom::findOrFail(21)));
        $this->assertNull($this->service()->jobBlockReason(JobSchedule::findOrFail(2), true));
    }

    public function test_unsuspend_releases_the_room_and_keeps_the_log(): void
    {
        $this->service()->suspend(Contract::findOrFail(1), [11], '2026-11-22', 7);
        $released = $this->service()->unsuspend(Contract::findOrFail(1), [11], 8);

        $this->assertSame(1, $released);
        $this->assertNull($this->service()->jobBlockReason(JobSchedule::findOrFail(2), true));
        $row = DB::table('contract_room_suspensions')->sole();
        $this->assertSame(8, (int) $row->unsuspended_by);
        $this->assertNotNull($row->unsuspended_at);
    }

    public function test_suspending_twice_does_not_duplicate_and_foreign_rooms_are_rejected(): void
    {
        $this->service()->suspend(Contract::findOrFail(1), [11], '2026-11-22', 7);
        $this->assertSame(0, $this->service()->suspend(Contract::findOrFail(1), [11], '2026-11-22', 7));
        $this->assertSame(1, DB::table('contract_room_suspensions')->count());

        // UAT 24 Nov 2026: tanggal lain memperbarui suspensi yang masih terbuka (dulu diabaikan).
        $this->assertSame(1, $this->service()->suspend(Contract::findOrFail(1), [11], '2026-11-23', 7));
        $this->assertSame(1, DB::table('contract_room_suspensions')->count());
        $this->assertSame('2026-11-23', substr((string) DB::table('contract_room_suspensions')->value('suspend_date'), 0, 10));
        $this->assertSame(1, DB::table('audit_logs')->where('action', 'contract_room_suspend_date_changed')->count());

        DB::table('contracts')->insert(['id' => 2, 'contract_number' => 'X']);
        $this->expectException(\InvalidArgumentException::class);
        $this->service()->suspend(Contract::findOrFail(2), [11], '2026-11-22', 7);
    }

    /**
     * UAT 24 Nov 2026, kontrak BLP-CA/26-11/0002: job service 23 Feb, admin suspend per 2 Feb
     * -> job 23 Feb dan sesudahnya terkunci SEKARANG (bukan baru saat 2 Feb tiba), job
     * sebelum 2 Feb tetap boleh dikerjakan bahkan setelah 2 Feb lewat.
     */
    public function test_suspension_follows_the_job_date_not_today(): void
    {
        DB::table('job_schedules')->where('id', 2)->update(['schedule_date' => '2027-02-23']);
        DB::table('job_schedules')->insert(['id' => 4, 'type' => 'service', 'status' => 'new_job', 'job_advice_id' => 1, 'schedule_date' => '2027-01-23']);
        DB::table('job_schedule_rooms')->insert(['job_schedule_id' => 4, 'job_advice_room_id' => 21, 'room_id' => 101]);

        $this->service()->suspend(Contract::findOrFail(1), [11], '2027-02-02', 7);

        $feb = JobSchedule::findOrFail(2);
        $jan = JobSchedule::findOrFail(4);
        $this->assertNotNull($this->service()->jobBlockReason($feb, true), 'Job 23 Feb harus terkunci walau hari ini masih Nov 2026');
        $this->assertTrue($this->service()->isJobAdviceRoomSuspended(JobAdviceRoom::findOrFail(21), $feb));
        $this->assertNull($this->service()->jobBlockReason($jan, true));

        Carbon::setTestNow('2027-03-01 09:00:00');
        $this->assertNull($this->service()->jobBlockReason(JobSchedule::findOrFail(4), true), 'Job sebelum tanggal suspend tetap jalan');
        $this->assertNotNull($this->service()->jobBlockReason(JobSchedule::findOrFail(2), true));

        // Reschedule ke sebelum tanggal suspend melepas job itu.
        DB::table('job_schedules')->where('id', 2)->update(['reschedule_date' => '2027-01-30']);
        $this->assertNull($this->service()->jobBlockReason(JobSchedule::findOrFail(2), true));
    }

    public function test_suspension_badge_reports_room_and_start_date(): void
    {
        DB::table('job_schedules')->where('id', 2)->update(['schedule_date' => '2027-02-23']);
        $this->service()->suspend(Contract::findOrFail(1), [11], '2027-02-02', 7);

        $badge = $this->service()->jobSuspensionBadge(JobSchedule::findOrFail(2));

        $this->assertSame('Lobby', $badge['rooms']);
        $this->assertSame('2027-02-02', $badge['since']);
        $this->assertNull($this->service()->jobSuspensionBadge(JobSchedule::findOrFail(1)->setAttribute('schedule_date', '2027-01-10')));
    }

    public function test_legacy_job_without_schedule_rooms_falls_back_to_contract_and_master_room(): void
    {
        DB::table('job_schedules')->insert(['id' => 3, 'type' => 'service', 'status' => 'new_job', 'job_advice_id' => 1, 'room_id' => 102]);
        $this->service()->suspend(Contract::findOrFail(1), [12], '2026-11-22', 7);

        $this->assertNotNull($this->service()->jobBlockReason(JobSchedule::findOrFail(3), true));
    }
}
