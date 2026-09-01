<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Mobile\JobController;
use App\Models\JobSchedule;
use App\Models\JobScheduleRoom;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

/**
 * QA 30 Aug 2026, job SBY-CSR/26-09/0020: one of its rooms still showed "pending" hours after
 * the technician had completed it.
 *
 * A Job Advice with several rooms gets one schedule chain per room, and every schedule of a
 * given period shares the same job number. The app posts a room completion against whichever
 * schedule id it is holding, which is not always the one that room lives on - so completeRoom()
 * found no room row, created one on the wrong job, and left the right job showing pending.
 * Schedules 717 and 720 each ended up carrying both rooms.
 */
class CompleteRoomOwningSiblingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedule_rooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_schedule_id')->nullable();
            $table->unsignedBigInteger('job_advice_room_id')->nullable();
            $table->string('room_name')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->string('status')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
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

    private function schedule(?string $jobNumber, string $type = 'service'): JobSchedule
    {
        return JobSchedule::create([
            'job_number' => $jobNumber,
            'type' => $type,
            'status' => 'in_progress',
        ]);
    }

    private function attach(JobSchedule $schedule, int $jobAdviceRoomId, string $roomName): JobScheduleRoom
    {
        return JobScheduleRoom::create([
            'job_schedule_id' => $schedule->id,
            'job_advice_room_id' => $jobAdviceRoomId,
            'room_name' => $roomName,
            'status' => 'pending',
        ]);
    }

    /**
     * The row completeRoom() grafts on when it is handed a schedule that has no row for the
     * room - what every job hit before the redirect existed is still carrying.
     */
    private function attachLeftover(JobSchedule $schedule, int $jobAdviceRoomId, string $roomName, string $status = 'completed'): JobScheduleRoom
    {
        return JobScheduleRoom::create([
            'job_schedule_id' => $schedule->id,
            'job_advice_room_id' => $jobAdviceRoomId,
            'room_name' => $roomName,
            'status' => $status,
            'notes' => 'Mobile rental-level tracking',
        ]);
    }

    private function resolve(?JobSchedule $schedule, ?int $jobAdviceRoomId): ?JobSchedule
    {
        $method = new ReflectionMethod(JobController::class, 'resolveRoomOwningSibling');
        $method->setAccessible(true);

        $room = $jobAdviceRoomId === null ? null : (object) ['id' => $jobAdviceRoomId];

        return $method->invoke(app(JobController::class), $schedule, $room);
    }

    public function test_a_room_addressed_to_the_wrong_sibling_is_routed_to_its_owner(): void
    {
        // The exact QA shape: same job number, one schedule per room.
        $complainJob = $this->schedule('SBY-CSR/26-09/0020');   // owns Ruang Complain
        $extraJob = $this->schedule('SBY-CSR/26-09/0020');      // owns Ruang Extra
        $this->attach($complainJob, 18287, 'Ruang Complain');
        $this->attach($extraJob, 18286, 'Ruang Extra');

        // App holds the Ruang Extra schedule but completes Ruang Complain.
        $resolved = $this->resolve($extraJob, 18287);

        $this->assertSame($complainJob->id, $resolved->id);
    }

    public function test_a_schedule_that_owns_the_room_is_left_alone(): void
    {
        $complainJob = $this->schedule('SBY-CSR/26-09/0020');
        $extraJob = $this->schedule('SBY-CSR/26-09/0020');
        $this->attach($complainJob, 18287, 'Ruang Complain');
        $this->attach($extraJob, 18286, 'Ruang Extra');

        $this->assertSame($complainJob->id, $this->resolve($complainJob, 18287)->id);
        $this->assertSame($extraJob->id, $this->resolve($extraJob, 18286)->id);
    }

    public function test_it_never_jumps_to_a_schedule_with_a_different_job_number(): void
    {
        // Period 3 owns the same room, but it is a DIFFERENT job. Redirecting there would move
        // a technician's work onto a job they are not doing.
        $periodTwo = $this->schedule('SBY-CSR/26-09/0020');
        $periodThree = $this->schedule('SBY-CSR/26-10/0021');
        $this->attach($periodTwo, 18286, 'Ruang Extra');
        $this->attach($periodThree, 18287, 'Ruang Complain');

        $this->assertSame($periodTwo->id, $this->resolve($periodTwo, 18287)->id);
    }

    public function test_a_room_no_sibling_owns_keeps_the_original_schedule(): void
    {
        // Legacy data and genuinely new rooms must keep the old behaviour.
        $job = $this->schedule('SBY-CSR/26-09/0020');
        $this->attach($job, 18286, 'Ruang Extra');

        $this->assertSame($job->id, $this->resolve($job, 99999)->id);
    }

    public function test_an_unnumbered_schedule_is_left_alone(): void
    {
        // Periods 3 and 4 are generated without a job number until they are assigned; there is
        // no "same job" to redirect within.
        $unnumbered = $this->schedule(null);
        $other = $this->schedule(null);
        $this->attach($other, 18287, 'Ruang Complain');

        $this->assertSame($unnumbered->id, $this->resolve($unnumbered, 18287)->id);
    }

    public function test_a_leftover_row_from_the_old_bug_does_not_count_as_owning_the_room(): void
    {
        // QA 1 Sep 2026, SBY-CSR/26-10/0011: the old bug had already grafted Ruang Complain onto
        // the Ruang Extra schedule before the redirect shipped. Reading that leftover as
        // ownership left the job unfixable from the app - every retry landed back on the wrong
        // sibling and was answered "duplicate", so schedule 718 stayed at teknisi_tiba_dilokasi.
        $complainJob = $this->schedule('SBY-CSR/26-10/0011');   // 718, real owner, still pending
        $extraJob = $this->schedule('SBY-CSR/26-10/0011');      // 721, carries the leftover row
        $this->attach($complainJob, 18287, 'Ruang Complain');
        $this->attach($extraJob, 18286, 'Ruang Extra');
        $this->attachLeftover($extraJob, 18287, 'Ruang Complain');

        $this->assertSame($complainJob->id, $this->resolve($extraJob, 18287)->id);
    }

    public function test_a_leftover_row_on_a_sibling_is_not_chased_either(): void
    {
        // Same leftover, other direction: the schedule holding the room for real must keep the
        // completion instead of being redirected into another job's placeholder.
        $complainJob = $this->schedule('SBY-CSR/26-10/0011');
        $extraJob = $this->schedule('SBY-CSR/26-10/0011');
        $this->attach($complainJob, 18287, 'Ruang Complain');
        $this->attachLeftover($extraJob, 18287, 'Ruang Complain');

        $this->assertSame($complainJob->id, $this->resolve($complainJob, 18287)->id);
    }

    public function test_a_placeholder_room_no_sibling_owns_keeps_the_original_schedule(): void
    {
        // Rental-level rooms that only ever existed as a placeholder must still complete where
        // they are - there is no better owner to route them to.
        $job = $this->schedule('SBY-CSR/26-10/0011');
        $sibling = $this->schedule('SBY-CSR/26-10/0011');
        $this->attachLeftover($job, 18287, 'Ruang Complain');
        $this->attach($sibling, 18286, 'Ruang Extra');

        $this->assertSame($job->id, $this->resolve($job, 18287)->id);
    }

    public function test_a_missing_schedule_or_room_is_handled(): void
    {
        $job = $this->schedule('SBY-CSR/26-09/0020');

        $this->assertNull($this->resolve(null, 18287));
        $this->assertSame($job->id, $this->resolve($job, null)->id);
    }
}
