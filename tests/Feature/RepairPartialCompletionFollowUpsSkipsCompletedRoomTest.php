<?php

namespace Tests\Feature;

use App\Models\JobSchedule;
use App\Models\JobScheduleRoom;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Bug #28 (live QA case: job_schedule 198 "SBY-IR/26-06/0018", 2 rooms "Toilet
 * VIP" (still pending) and "Ruang Meeting VIP" (already completed via mobile
 * at 19:17, 8 minutes before this command ran at 19:25)): the repair
 * command's room filter only excluded CANCELLED rooms, not COMPLETED ones.
 * So running `jobs:repair-partial-followups <jobNumber> --apply` to move the
 * genuinely unfinished "Toilet VIP" room into a follow-up job also dragged
 * the already-finished "Ruang Meeting VIP" room along with it - confirmed
 * live: a new job_schedule_rooms row for "Ruang Meeting VIP" was created
 * with notes "Pindahan dari Job SBY-IR/26-06/0018" even though the original
 * room was already status=completed.
 *
 * The fix mirrors JobWebCompletionService::handlePartialCompletion(), which
 * correctly skips both COMPLETED and CANCELLED rooms.
 */
class RepairPartialCompletionFollowUpsSkipsCompletedRoomTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->nullable();
            $table->foreignId('job_advice_id')->nullable();
            $table->foreignId('building_id')->nullable();
            $table->string('building_name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('contract_number')->nullable();
            $table->string('quotation_number')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->date('schedule_date')->nullable();
            $table->date('expected_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('internal_notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedule_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->foreignId('job_advice_room_id')->nullable();
            $table->string('room_name')->nullable();
            $table->string('status')->nullable();
            $table->string('material_return_status')->nullable();
            $table->text('notes')->nullable();
            $table->text('completion_notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedule_room_rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_room_id')->nullable();
            $table->foreignId('job_advice_room_id')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->string('nama_gedung')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('buildings');
        Schema::dropIfExists('job_schedule_room_rentals');
        Schema::dropIfExists('job_schedule_rooms');
        Schema::dropIfExists('job_schedules');

        parent::tearDown();
    }

    public function test_repair_command_does_not_move_an_already_completed_room(): void
    {
        $sourceJob = JobSchedule::create([
            'job_number' => 'SBY-IR/26-06/0018',
            'job_advice_id' => 29,
            'building_id' => 1,
            'type' => 'install',
            'status' => 'in_progress',
        ]);

        $completedRoom = JobScheduleRoom::create([
            'job_schedule_id' => $sourceJob->id,
            'job_advice_room_id' => 53,
            'room_name' => 'Ruang Meeting VIP',
            'status' => 'completed',
            'completion_notes' => 'Completed via mobile app',
        ]);

        $pendingRoom = JobScheduleRoom::create([
            'job_schedule_id' => $sourceJob->id,
            'job_advice_room_id' => 54,
            'room_name' => 'Toilet VIP',
            'status' => 'pending',
        ]);

        Artisan::call('jobs:repair-partial-followups', [
            'jobNumber' => 'SBY-IR/26-06/0018',
            '--apply' => true,
        ]);

        $completedRoom->refresh();
        $this->assertSame('completed', $completedRoom->status, 'The already-completed room must not be touched by the repair.');

        $this->assertSame(
            0,
            DB::table('job_schedule_rooms')->where('room_name', 'Ruang Meeting VIP')->where('id', '!=', $completedRoom->id)->count(),
            'No follow-up row should be created for a room that was already completed.'
        );

        $pendingRoom->refresh();
        $this->assertSame('cancelled', $pendingRoom->status, 'The genuinely unfinished room must still be moved to a follow-up job.');

        $this->assertSame(
            1,
            DB::table('job_schedule_rooms')->where('room_name', 'Toilet VIP')->where('id', '!=', $pendingRoom->id)->count(),
            'The unfinished room must get exactly one follow-up row.'
        );
    }
}
