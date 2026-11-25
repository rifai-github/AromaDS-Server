<?php

namespace Tests\Unit;

use App\Models\JobSchedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * UAT 24 Nov 2026: log ubah BA date tertulis "by User ID: 918" - harus nama user (Hengky),
 * termasuk untuk log lama yang sudah tersimpan.
 */
class JobScheduleInternalNotesDisplayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        DB::table('users')->insert(['id' => 918, 'name' => 'Hengky']);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('users');
        parent::tearDown();
    }

    public function test_user_ids_in_logs_are_shown_as_names(): void
    {
        $job = new JobSchedule([
            'internal_notes' => "Catatan teknisi\n[BA_DATE_EDIT] BA Date changed from 2026-11-20 to 2026-11-21 by User ID: 918 at 2026-11-24 10:00:00",
        ]);

        $this->assertStringContainsString('to 2026-11-21 by Hengky at', $job->internal_notes_display);
        $this->assertStringNotContainsString('User ID', $job->internal_notes_display);
        // Stored text is untouched.
        $this->assertStringContainsString('User ID: 918', $job->internal_notes);
    }

    public function test_unknown_ids_and_plain_notes_are_left_alone(): void
    {
        $this->assertSame('by User ID: 5', (new JobSchedule(['internal_notes' => 'by User ID: 5']))->internal_notes_display);
        $this->assertSame('Catatan biasa', (new JobSchedule(['internal_notes' => 'Catatan biasa']))->internal_notes_display);
        $this->assertNull((new JobSchedule)->internal_notes_display);
    }
}
