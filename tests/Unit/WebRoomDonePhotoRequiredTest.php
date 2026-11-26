<?php

namespace Tests\Unit;

use App\Http\Controllers\Operational\JobScheduleController;
use PHPUnit\Framework\TestCase;

/**
 * QA 25 Nov 2026: foto Before/After bertanda wajib di form Done room (web), tetapi room tetap
 * bisa selesai tanpa foto. Aturannya harus sama dengan APK: tiap jenis wajib ada (baru diunggah
 * atau sudah tersimpan), dan dicek SEBELUM unit/foto disimpan.
 */
class WebRoomDonePhotoRequiredTest extends TestCase
{
    public function test_complete_room_manual_requires_before_and_after_photos(): void
    {
        $method = new \ReflectionMethod(JobScheduleController::class, 'completeRoomManual');
        $lines = file($method->getFileName());
        $body = implode('', array_slice($lines, $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1));

        $gate = strpos($body, "Foto sebelum dan sesudah pengerjaan wajib diupload untuk menyelesaikan room.");
        $this->assertNotFalse($gate, 'Penjaga foto wajib tidak ada.');
        $this->assertStringContainsString("\$request->hasFile('before_photos') && ! \$hasPhotoType('Before Work')", $body);
        $this->assertStringContainsString("\$request->hasFile('after_photos') && ! \$hasPhotoType('After Work')", $body);
        $this->assertLessThan(strpos($body, '$webCompletion->saveScannedUnit('), $gate, 'Foto harus dicek sebelum unit disimpan.');
    }
}
