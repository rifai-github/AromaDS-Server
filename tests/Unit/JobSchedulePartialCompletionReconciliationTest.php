<?php

namespace Tests\Unit;

use App\Models\JobSchedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class JobSchedulePartialCompletionReconciliationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('job_advice_id')->nullable();
            $table->foreignId('building_id')->nullable();
            $table->string('contract_number')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedule_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('job_advice_room_id')->nullable();
            $table->string('room_name')->nullable();
            $table->string('status')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('job_schedule_rooms');
        Schema::dropIfExists('job_schedules');

        parent::tearDown();
    }

    public function test_source_job_is_marked_done_once_its_followup_is_done_job(): void
    {
        // Mirrors the real bug: SBY-CSR/26-06/0005 stuck on 'meninggalkan_lokasi'
        // forever even though its partial-completion follow-up SBY-CSR/26-06/0006
        // already reached 'done_job'.
        $sourceJob = JobSchedule::create([
            'job_number' => 'SBY-CSR/26-06/0005',
            'type' => 'service_first',
            'status' => 'meninggalkan_lokasi',
            'job_advice_id' => 9,
            'building_id' => 190,
            'contract_number' => 'SBY-CA/26-06/0002',
            'internal_notes' => 'Auto-generated first CSR from contract activation',
        ]);

        $sourceJob->jobScheduleRooms()->create([
            'job_advice_room_id' => 12,
            'room_name' => 'Ruang Tunggu',
            'status' => 'cancelled',
            'notes' => 'Pekerjaan tidak selesai, dipindahkan ke Job baru.',
        ]);

        JobSchedule::create([
            'job_number' => 'SBY-CSR/26-06/0006',
            'type' => 'service_first',
            'status' => 'done_job',
            'job_advice_id' => 9,
            'building_id' => 190,
            'contract_number' => 'SBY-CA/26-06/0002',
            'internal_notes' => 'Lanjutan dari Job SBY-CSR/26-06/0005 (Pekerjaan tidak selesai). Room: Ruang Tunggu.',
        ]);

        JobSchedule::reconcilePartialCompletionSourceJobs('SBY-CA/26-06/0002');

        $sourceJob->refresh();
        $this->assertSame('done_job', $sourceJob->status);
        $this->assertNotNull($sourceJob->completed_at);
    }

    public function test_source_job_stays_stale_while_followup_is_still_open(): void
    {
        $sourceJob = JobSchedule::create([
            'job_number' => 'SBY-CSR/26-06/0005',
            'type' => 'service_first',
            'status' => 'meninggalkan_lokasi',
            'job_advice_id' => 9,
            'building_id' => 190,
            'contract_number' => 'SBY-CA/26-06/0002',
        ]);

        $sourceJob->jobScheduleRooms()->create([
            'job_advice_room_id' => 12,
            'room_name' => 'Ruang Tunggu',
            'status' => 'cancelled',
            'notes' => 'Pekerjaan tidak selesai, dipindahkan ke Job baru.',
        ]);

        JobSchedule::create([
            'job_number' => 'SBY-CSR/26-06/0006',
            'type' => 'service_first',
            'status' => 'new_job',
            'job_advice_id' => 9,
            'building_id' => 190,
            'contract_number' => 'SBY-CA/26-06/0002',
            'internal_notes' => 'Lanjutan dari Job SBY-CSR/26-06/0005 (Pekerjaan tidak selesai). Room: Ruang Tunggu.',
        ]);

        JobSchedule::reconcilePartialCompletionSourceJobs('SBY-CA/26-06/0002');

        $sourceJob->refresh();
        $this->assertSame('meninggalkan_lokasi', $sourceJob->status);
    }

    public function test_source_job_with_a_still_unfinished_room_is_not_reconciled(): void
    {
        $sourceJob = JobSchedule::create([
            'job_number' => 'SBY-CSR/26-06/0005',
            'type' => 'service_first',
            'status' => 'meninggalkan_lokasi',
            'job_advice_id' => 9,
            'building_id' => 190,
            'contract_number' => 'SBY-CA/26-06/0002',
        ]);

        $sourceJob->jobScheduleRooms()->create([
            'job_advice_room_id' => 12,
            'room_name' => 'Ruang Tunggu',
            'status' => 'cancelled',
            'notes' => 'Pekerjaan tidak selesai, dipindahkan ke Job baru.',
        ]);

        $sourceJob->jobScheduleRooms()->create([
            'job_advice_room_id' => 13,
            'room_name' => 'Lobby',
            'status' => 'pending',
        ]);

        JobSchedule::create([
            'job_number' => 'SBY-CSR/26-06/0006',
            'type' => 'service_first',
            'status' => 'done_job',
            'job_advice_id' => 9,
            'building_id' => 190,
            'contract_number' => 'SBY-CA/26-06/0002',
            'internal_notes' => 'Lanjutan dari Job SBY-CSR/26-06/0005 (Pekerjaan tidak selesai). Room: Ruang Tunggu.',
        ]);

        JobSchedule::reconcilePartialCompletionSourceJobs('SBY-CA/26-06/0002');

        $sourceJob->refresh();
        $this->assertSame('meninggalkan_lokasi', $sourceJob->status);
    }

    public function test_source_job_is_reconciled_when_its_room_says_moved_but_no_followup_was_ever_created(): void
    {
        // Bug #15 (live QA case: job 130 "SBY-CSR/26-10/0004"): the room is marked
        // 'cancelled' with the "dipindahkan ke Job baru" note, but its follow-up job
        // was never actually created (or was later removed) — zero JobSchedule rows
        // reference "Lanjutan dari Job {job_number}". The old check treated "no
        // follow-up found" the same as "follow-up still open" and skipped
        // reconciliation, leaving this job blocking MOM14's unfinished-job
        // validation forever with nothing left to ever resolve it.
        $sourceJob = JobSchedule::create([
            'job_number' => 'SBY-CSR/26-10/0004',
            'type' => 'service_first',
            'status' => 'meninggalkan_lokasi',
            'job_advice_id' => 19,
            'building_id' => 197,
            'contract_number' => 'SBY-CA/26-06/0010',
        ]);

        $sourceJob->jobScheduleRooms()->create([
            'job_advice_room_id' => 14,
            'room_name' => 'Ruang Aula',
            'status' => 'completed',
        ]);

        $sourceJob->jobScheduleRooms()->create([
            'job_advice_room_id' => 15,
            'room_name' => 'Ruang Meeting',
            'status' => 'cancelled',
            'notes' => 'Pekerjaan tidak selesai, dipindahkan ke Job baru.',
        ]);

        // No JobSchedule referencing "Lanjutan dari Job SBY-CSR/26-10/0004" exists.

        JobSchedule::reconcilePartialCompletionSourceJobs('SBY-CA/26-06/0010');

        $sourceJob->refresh();
        $this->assertSame('done_job', $sourceJob->status);
        $this->assertNotNull($sourceJob->completed_at);
    }
}
