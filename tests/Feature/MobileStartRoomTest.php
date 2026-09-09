<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Mobile\JobController;
use App\Models\JobScheduleRoom;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA 8 Sep 2026, job SBY-CSR/26-09/0028: "Start Job" was blank for Ruang Lobby even though
 * the room was completed, while both Ruang Meeting rows showed the same 16:18.
 *
 * startWork() writes started_at on ONE job_schedules row and fires once per visit, before
 * the room picker. A visit is split across sibling schedules sharing a job number, so every
 * room that is not on the schedule the app happened to hold has no start of its own, and
 * rooms that are on it all report the same figure.
 *
 * startRoom() records the moment a single room was opened. These tests pin the parts that
 * would quietly break it: it has to land on the same row completeRoom() will later close,
 * it must not overwrite itself, and it must not touch job status.
 */
class MobileStartRoomTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_advices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_advice_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_advice_id')->nullable();
            $table->string('room_name')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->foreignId('rental_product_id')->nullable();
            $table->integer('quantity')->default(1);
            $table->string('status')->nullable();
            $table->foreignId('remove_job_schedule_id')->nullable();
            $table->foreignId('install_job_schedule_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('job_advice_id')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedule_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('job_advice_room_id')->nullable();
            $table->string('room_name')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->foreignId('started_by')->nullable();
            $table->decimal('start_latitude', 10, 8)->nullable();
            $table->decimal('start_longitude', 11, 8)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable();
            $table->text('completion_notes')->nullable();
            $table->string('material_return_status')->nullable();
            $table->text('notes')->nullable();
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

        Schema::create('mobile_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('job_schedule_room_id')->nullable();
            $table->string('action');
            $table->string('idempotency_key')->nullable();
            $table->timestamp('client_clicked_at')->nullable();
            $table->string('client_delivery_mode')->nullable();
            $table->timestamp('client_queued_at')->nullable();
            $table->timestamp('client_synced_at')->nullable();
            $table->timestamp('server_received_at')->nullable();
            $table->string('sync_status')->default('synced');
            $table->string('payload_hash')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Teknisi',
            'email' => 'teknisi@example.test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Auth::login(User::findOrFail(1));
    }

    protected function tearDown(): void
    {
        foreach ([
            'mobile_sync_logs',
            'job_schedule_room_rentals',
            'job_schedule_rooms',
            'job_schedules',
            'job_advice_rooms',
            'job_advices',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    /**
     * The QA shape: one Job Advice, two rooms, one schedule per room, both carrying the
     * same job number. Returns [lobbySchedule, meetingSchedule].
     */
    private function seedSplitVisit(): array
    {
        DB::table('job_advices')->insert([
            'id' => 6292,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_advice_rooms')->insert([
            [
                'id' => 18333,
                'job_advice_id' => 6292,
                'room_name' => 'Ruang Lobby',
                'quantity' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 18334,
                'job_advice_id' => 6292,
                'room_name' => 'Ruang Meeting',
                'quantity' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('job_schedules')->insert([
            [
                'id' => 825,
                'job_number' => 'SBY-CSR/26-09/0028',
                'type' => 'service',
                'status' => 'in_progress',
                'job_advice_id' => 6292,
                'started_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 826,
                'job_number' => 'SBY-CSR/26-09/0028',
                'type' => 'service',
                'status' => 'in_progress',
                'job_advice_id' => 6292,
                'started_at' => '2026-09-08 16:18:17',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('job_schedule_rooms')->insert([
            [
                'id' => 895,
                'job_schedule_id' => 825,
                'job_advice_room_id' => 18333,
                'room_name' => 'Ruang Lobby',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 896,
                'job_schedule_id' => 826,
                'job_advice_room_id' => 18334,
                'room_name' => 'Ruang Meeting',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        return [825, 826];
    }

    private function startRoom(int $jobAdviceRoomId, array $payload): array
    {
        $response = app(JobController::class)->startRoom(
            Request::create("/api/v1/mobile/rooms/{$jobAdviceRoomId}/start", 'POST', $payload),
            $jobAdviceRoomId
        );

        return [$response->getStatusCode(), json_decode($response->getContent(), true)];
    }

    public function test_start_lands_on_the_sibling_that_owns_the_room_not_the_job_the_app_holds(): void
    {
        $this->seedSplitVisit();

        // The app holds the Meeting schedule (826) - the one that got startWork - but the
        // technician opens Ruang Lobby, which lives on schedule 825. Recording the start on
        // 826 would repeat the very bug this closes: start on one job, completion on another.
        [$status, $payload] = $this->startRoom(18333, ['job_schedule_id' => 826]);

        $this->assertSame(200, $status);
        $this->assertTrue($payload['data']['recorded']);
        $this->assertSame(825, $payload['data']['job_schedule_id']);
        $this->assertSame(895, $payload['data']['job_schedule_room_id']);

        $this->assertNotNull(DB::table('job_schedule_rooms')->where('id', 895)->value('started_at'));
        $this->assertNull(DB::table('job_schedule_rooms')->where('id', 896)->value('started_at'));
    }

    public function test_reopening_a_room_keeps_the_first_start(): void
    {
        $this->seedSplitVisit();

        $this->startRoom(18333, [
            'job_schedule_id' => 825,
            'client_clicked_at' => '2026-09-08 16:19:00',
        ]);

        $firstStart = DB::table('job_schedule_rooms')->where('id', 895)->value('started_at');

        // Technician backs out of the card and opens it again minutes later.
        [$status, $payload] = $this->startRoom(18333, [
            'job_schedule_id' => 825,
            'client_clicked_at' => '2026-09-08 16:24:00',
        ]);

        $this->assertSame(200, $status);
        $this->assertFalse($payload['data']['recorded']);
        $this->assertSame(
            $firstStart,
            DB::table('job_schedule_rooms')->where('id', 895)->value('started_at')
        );
    }

    public function test_a_room_worked_offline_keeps_the_time_it_was_opened_on_the_device(): void
    {
        $this->seedSplitVisit();

        // Replayed by SyncService the next morning; now() would stamp the sync, not the work.
        $this->startRoom(18333, [
            'job_schedule_id' => 825,
            'client_clicked_at' => '2026-09-08 16:19:07',
        ]);

        $this->assertSame(
            '2026-09-08 16:19:07',
            (string) DB::table('job_schedule_rooms')->where('id', 895)->value('started_at')
        );
    }

    public function test_a_device_clock_reporting_the_future_does_not_win(): void
    {
        $this->seedSplitVisit();

        $this->startRoom(18333, [
            'job_schedule_id' => 825,
            'client_clicked_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
        ]);

        $this->assertTrue(
            now()->addMinute()->greaterThan(
                DB::table('job_schedule_rooms')->where('id', 895)->value('started_at')
            )
        );
    }

    public function test_start_moves_a_pending_room_to_in_progress_without_touching_job_status(): void
    {
        $this->seedSplitVisit();

        $this->startRoom(18333, ['job_schedule_id' => 825]);

        $this->assertSame(
            JobScheduleRoom::STATUS_IN_PROGRESS,
            DB::table('job_schedule_rooms')->where('id', 895)->value('status')
        );

        // The job state machine belongs to startWork/completeRoom - this call only adds data.
        $this->assertSame('in_progress', DB::table('job_schedules')->where('id', 825)->value('status'));
        $this->assertNull(DB::table('job_schedules')->where('id', 825)->value('started_at'));
    }

    public function test_an_already_completed_room_is_not_reopened(): void
    {
        $this->seedSplitVisit();

        DB::table('job_schedule_rooms')->where('id', 895)->update([
            'status' => 'completed',
            'completed_at' => '2026-09-08 16:25:18',
        ]);

        $this->startRoom(18333, ['job_schedule_id' => 825]);

        $this->assertSame(
            'completed',
            DB::table('job_schedule_rooms')->where('id', 895)->value('status')
        );
    }

    public function test_an_unresolvable_room_records_nothing_instead_of_guessing(): void
    {
        $this->seedSplitVisit();

        DB::table('job_advice_rooms')->insert([
            'id' => 19000,
            'job_advice_id' => 6292,
            'room_name' => 'Ruang Tanpa Jadwal',
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        [$status, $payload] = $this->startRoom(19000, []);

        $this->assertSame(200, $status);
        $this->assertFalse($payload['data']['recorded']);
    }
}
