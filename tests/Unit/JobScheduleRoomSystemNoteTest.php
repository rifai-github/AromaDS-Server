<?php

namespace Tests\Unit;

use App\Models\JobScheduleRoom;
use Tests\TestCase;

/**
 * UAT 24 Nov 2026: room yang belum disentuh menampilkan Rental Remark "rental in this room:2"
 * - teks sistem, bukan isian teknisi/admin.
 */
class JobScheduleRoomSystemNoteTest extends TestCase
{
    public function test_room_count_texts_are_system_notes(): void
    {
        $this->assertTrue(JobScheduleRoom::isSystemNote('Rentals in this room: 2'));
        $this->assertTrue(JobScheduleRoom::isSystemNote('rentals in this room:1'));
        $this->assertTrue(JobScheduleRoom::isSystemNote('Auto CSR for active Unit On Wall rentals: 1'));
        $this->assertFalse(JobScheduleRoom::isSystemNote('Rentals in this room: 2, minta aroma lembut'));
        $this->assertFalse(JobScheduleRoom::isSystemNote('Customer minta aroma lembut'));
        $this->assertFalse(JobScheduleRoom::isSystemNote(null));
    }

    public function test_remark_notes_hide_system_text_only(): void
    {
        $this->assertNull((new JobScheduleRoom(['notes' => 'Rentals in this room: 2']))->remark_notes);
        $this->assertNull((new JobScheduleRoom(['notes' => '  ']))->remark_notes);
        $this->assertSame('[SUSPEND] Room suspended', (new JobScheduleRoom(['notes' => '[SUSPEND] Room suspended']))->remark_notes);
    }
}
