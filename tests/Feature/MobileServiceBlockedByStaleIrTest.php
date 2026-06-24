<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Mobile\JobController;
use App\Models\JobSchedule;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Bug #21/#29: after the partial-completion flow (handleCannotCompleteAllRooms /
 * JobWebCompletionService::handlePartialCompletion) moves a job's unfinished
 * rooms into a follow-up JobSchedule, the SOURCE job's own status stays
 * 'meninggalkan_lokasi' forever (it's never flipped to a terminal status).
 * checkJobDependency() used to keep finding that stale source job and block
 * the room's Service/CSR work, even though the room itself was already
 * completed and its only outstanding work had moved to a brand-new follow-up
 * job. Reproduces the exact QA scenario found on job_schedules 99/100/172.
 */
class MobileServiceBlockedByStaleIrTest extends TestCase
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
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('job_advice_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->integer('period')->nullable();
            $table->text('internal_notes')->nullable();
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

        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Teknisi Test',
            'email' => 'teknisi@example.test',
            'password' => 'password',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Auth::login(User::findOrFail(1));
    }

    protected function tearDown(): void
    {
        foreach (['job_schedule_rooms', 'job_schedules', 'job_advices', 'users'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_service_job_is_not_blocked_by_a_stale_source_ir_whose_rooms_are_all_closed(): void
    {
        DB::table('job_advices')->insert(['id' => 18, 'created_at' => now(), 'updated_at' => now()]);

        // Source IR job covers TWO rooms (mirrors bug #21: "2 room dengan 1 nomor job
        // yang sama"). Room Aula finished; room Meeting was left incomplete and moved
        // to a follow-up. The source job's own status stays 'meninggalkan_lokasi'
        // forever (it's never flipped to a terminal status by the partial-completion
        // flow), even though Aula itself has nothing left to do.
        DB::table('job_schedules')->insert([
            'id' => 99,
            'job_advice_id' => 18,
            'job_number' => 'SBY-IR/26-06/0007',
            'type' => 'install',
            'status' => 'meninggalkan_lokasi',
            'room_id' => 416, // Aula
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_schedule_rooms')->insert(['id' => 100, 'job_schedule_id' => 99, 'room_name' => 'Aula', 'status' => 'completed', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('job_schedule_rooms')->insert(['id' => 152, 'job_schedule_id' => 99, 'room_name' => 'Meeting', 'status' => 'cancelled', 'notes' => 'Pekerjaan tidak selesai, dipindahkan ke Job baru.', 'created_at' => now(), 'updated_at' => now()]);

        // Follow-up IR job for the OTHER room (Meeting) only — Aula has no follow-up,
        // it's genuinely done. (mirrors QA job 172, but for a different room_id.)
        DB::table('job_schedules')->insert([
            'id' => 172,
            'job_advice_id' => 18,
            'job_number' => null,
            'type' => 'install',
            'status' => 'new_job',
            'room_id' => 417, // Meeting
            'internal_notes' => 'Lanjutan dari Job SBY-IR/26-06/0007 (Pekerjaan tidak selesai). Room: Meeting.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('job_schedule_rooms')->insert([
            'id' => 185, 'job_schedule_id' => 172, 'room_name' => 'Meeting', 'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);

        // The CSR/Service job for Aula — the room that's genuinely done.
        $csrJob = JobSchedule::create([
            'id' => 100,
            'job_advice_id' => 18,
            'job_number' => 'SBY-CSR/26-06/0011',
            'type' => 'service_first',
            'status' => 'barang_diambil',
            'room_id' => 416, // Aula
            'period' => 1,
        ]);

        $controller = app(JobController::class);
        $method = new ReflectionMethod($controller, 'checkJobDependency');
        $method->setAccessible(true);

        $result = $method->invoke($controller, $csrJob);

        $this->assertFalse(
            $result['is_blocked'],
            'CSR for Aula must not stay blocked by the source IR job — Aula itself is done; only the Meeting room (a different room) has an unfinished follow-up.'
        );
    }

    public function test_service_job_is_still_blocked_when_the_install_job_has_a_genuinely_pending_room(): void
    {
        DB::table('job_advices')->insert(['id' => 19, 'created_at' => now(), 'updated_at' => now()]);

        DB::table('job_schedules')->insert([
            'id' => 200,
            'job_advice_id' => 19,
            'job_number' => 'SBY-IR/26-06/0099',
            'type' => 'install',
            'status' => 'in_progress',
            'room_id' => 500,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_schedule_rooms')->insert([
            'id' => 300, 'job_schedule_id' => 200, 'room_name' => 'Lobby', 'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $csrJob = JobSchedule::create([
            'id' => 201,
            'job_advice_id' => 19,
            'job_number' => 'SBY-CSR/26-06/0099',
            'type' => 'service_first',
            'status' => 'barang_diambil',
            'room_id' => 500,
            'period' => 1,
        ]);

        $controller = app(JobController::class);
        $method = new ReflectionMethod($controller, 'checkJobDependency');
        $method->setAccessible(true);

        $result = $method->invoke($controller, $csrJob);

        $this->assertTrue(
            $result['is_blocked'],
            'CSR must still be blocked when the install job has a genuinely unfinished (pending) room.'
        );
        $this->assertStringContainsString('SBY-IR/26-06/0099', $result['message']);
    }
}
