<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobScheduleController;
use App\Models\JobSchedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Bug: an RV outstanding/follow-up job created after a partial completion
 * ("Lanjutan dari Job {source}...") could end up sharing the exact same
 * job_number as its still-unfinished source job when both were assigned to
 * the same team on the same day. Mobile/web then treated the two distinct
 * job_schedules rows as one job (see JobWebCompletionService's "same
 * job_number siblings" sync), so whichever loaded second got stuck — the
 * technician could not proceed with "Mulai Pekerjaan" for the follow-up.
 *
 * Root cause: JobScheduleController::findReusableAssignedJobNumberSource()
 * implements the (intentional) "Shared Job Number" merge — reusing an
 * existing job_number when the same team is assigned to another job with
 * the same job_advice/building/type/date. It did not exclude the case where
 * the "other job" is actually the source job this follow-up was split off
 * from, which must always keep its own, independent job_number.
 */
class JobScheduleFollowUpJobNumberReuseTest extends TestCase
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
            $table->date('schedule_date')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_assign_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_advices', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedule_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('job_advice_room_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        \DB::table('job_advices')->insert([
            'id' => 30,
            'type' => 'remove',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('job_schedule_rooms');
        Schema::dropIfExists('job_advices');
        Schema::dropIfExists('job_assign_schedules');
        Schema::dropIfExists('job_schedules');

        parent::tearDown();
    }

    public function test_follow_up_job_does_not_reuse_its_own_unfinished_source_jobs_number(): void
    {
        $sourceJob = JobSchedule::create([
            'job_number' => 'SBY-RV/26-07/0007',
            'type' => 'remove',
            'status' => 'meninggalkan_lokasi',
            'job_advice_id' => 30,
            'building_id' => 1,
            'schedule_date' => '2026-07-05',
        ]);

        \DB::table('job_assign_schedules')->insert([
            'job_schedule_id' => $sourceJob->id,
            'team_id' => 5,
            'status' => 'assigned',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $followUpJob = JobSchedule::create([
            'job_number' => null,
            'type' => 'remove',
            'status' => 'new_job',
            'job_advice_id' => 30,
            'building_id' => 1,
            'schedule_date' => '2026-07-05',
            'internal_notes' => "Lanjutan dari Job {$sourceJob->job_number} (Pekerjaan tidak selesai). Room: Ruang Paket Unit Saja.",
        ]);

        $controller = app(JobScheduleController::class);
        $method = new ReflectionMethod($controller, 'findReusableAssignedJobNumberSource');
        $method->setAccessible(true);

        $reused = $method->invoke($controller, $followUpJob, 5);

        $this->assertNull(
            $reused,
            'A partial-completion follow-up job must never reuse the job_number of the source job it was split off from.'
        );
    }

    public function test_unrelated_job_on_same_team_and_day_can_still_share_a_job_number(): void
    {
        // Guard against over-correcting: the legitimate "Shared Job Number" merge
        // (two independent rooms, same job_advice/building/type/date, same team)
        // must still work when there is no follow-up relationship between them.
        $siblingJob = JobSchedule::create([
            'job_number' => 'SBY-RV/26-07/0008',
            'type' => 'remove',
            'status' => 'assign_team',
            'job_advice_id' => 30,
            'building_id' => 1,
            'schedule_date' => '2026-07-05',
        ]);

        \DB::table('job_assign_schedules')->insert([
            'job_schedule_id' => $siblingJob->id,
            'team_id' => 5,
            'status' => 'assigned',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $newRoomJob = JobSchedule::create([
            'job_number' => null,
            'type' => 'remove',
            'status' => 'new_job',
            'job_advice_id' => 30,
            'building_id' => 1,
            'schedule_date' => '2026-07-05',
        ]);

        $controller = app(JobScheduleController::class);
        $method = new ReflectionMethod($controller, 'findReusableAssignedJobNumberSource');
        $method->setAccessible(true);

        $reused = $method->invoke($controller, $newRoomJob, 5);

        $this->assertNotNull($reused);
        $this->assertSame($siblingJob->id, $reused->id);
    }
}
