<?php

namespace Tests\Feature;

use App\Models\JobScheduleRoom;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * UAT 24 Nov 2026: kolom "Done" per room di tab Rental & Team - "Done by Web" bila room
 * diselesaikan dari web, "Done by App" bila dari aplikasi teknisi.
 */
class JobScheduleRoomCompletionSourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('job_schedule_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->string('room_name')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable();
            $table->string('completion_source', 10)->nullable();
            $table->text('completion_notes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('job_schedule_rooms');
        parent::tearDown();
    }

    private function room(array $attributes = []): JobScheduleRoom
    {
        return JobScheduleRoom::create(array_merge(['job_schedule_id' => 1, 'room_name' => 'Room 301', 'status' => 'pending'], $attributes));
    }

    public function test_completion_records_where_the_room_was_done(): void
    {
        $web = $this->room();
        $web->markAsCompleted(7, 'Completed manually via web admin', JobScheduleRoom::SOURCE_WEB);
        $app = $this->room();
        $app->markAsCompleted(8, 'CSR 0012', JobScheduleRoom::SOURCE_APP);

        $this->assertSame('web', DB::table('job_schedule_rooms')->where('id', $web->id)->value('completion_source'));
        $this->assertSame('Done by Web', $web->fresh()->completion_source_label);
        $this->assertSame('Done by App', $app->fresh()->completion_source_label);
        $this->assertNull($this->room()->completion_source_label, 'Room belum selesai tidak punya label');
    }

    /**
     * QA 26 Nov 2026, JKT-IF/26-11/0001: room diselesaikan di web (catatan ketikan admin, sebelum
     * kolom completion_source ada) berlabel "Done by App". APK selalu meninggalkan log
     * complete_room; room tanpa log itu = selesai dari web.
     */
    public function test_legacy_rooms_with_typed_notes_use_the_mobile_sync_log(): void
    {
        Schema::create('mobile_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_room_id')->nullable();
            $table->string('action')->nullable();
            $table->timestamps();
        });
        DB::table('mobile_sync_logs')->insert(['job_schedule_room_id' => 999, 'action' => 'start_room', 'created_at' => '2026-11-01 08:00:00']);

        $web = $this->room(['status' => 'completed', 'completed_by' => 1, 'completed_at' => '2026-11-24 17:39:47', 'completion_notes' => 'catatan di web']);
        $app = $this->room(['status' => 'completed', 'completed_by' => 2, 'completed_at' => '2026-11-24 17:26:33', 'completion_notes' => 'pasang IR']);
        DB::table('mobile_sync_logs')->insert(['job_schedule_room_id' => $app->id, 'action' => 'complete_room', 'created_at' => '2026-11-24 17:26:33']);
        $beforeLogs = $this->room(['status' => 'completed', 'completed_by' => 2, 'completed_at' => '2026-10-01 10:00:00', 'completion_notes' => 'lama']);

        $this->assertSame('Done by Web', $web->fresh()->completion_source_label);
        $this->assertSame('Done by App', $app->fresh()->completion_source_label);
        $this->assertSame('Done by App', $beforeLogs->fresh()->completion_source_label, 'Sebelum log ada: perilaku lama.');

        Schema::dropIfExists('mobile_sync_logs');
    }

    public function test_system_completion_notes_are_hidden_from_the_technician_column(): void
    {
        $this->assertNull($this->room(['completion_notes' => 'Auto-synced from active Unit On Wall during mobile verification retry'])->display_completion_notes);
        $this->assertNull($this->room(['completion_notes' => 'Completed via web Done Job'])->display_completion_notes);
        $this->assertSame('pasang IF HIKV-2', $this->room(['completion_notes' => 'pasang IF HIKV-2'])->display_completion_notes);
        $this->assertTrue(JobScheduleRoom::isSystemNote('Mobile rental-level tracking'));
        $this->assertFalse(JobScheduleRoom::isSystemNote('sewa IF A-5'));
    }

    public function test_rooms_done_before_the_column_existed_are_inferred(): void
    {
        $this->assertSame('Done by Web', $this->room(['status' => 'completed', 'completed_by' => 1, 'completed_at' => now(), 'completion_notes' => 'Completed via web Done Job'])->completion_source_label);
        $this->assertSame('Done by App', $this->room(['status' => 'completed', 'completed_by' => 1, 'completed_at' => now(), 'completion_notes' => 'pasang IR 0002'])->completion_source_label);
        $this->assertSame('Import', $this->room(['status' => 'completed', 'completed_at' => Carbon::parse('2026-06-24 00:00:00')])->completion_source_label);
    }
}
