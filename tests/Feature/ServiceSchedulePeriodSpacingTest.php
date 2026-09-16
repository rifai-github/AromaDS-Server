<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobScheduleController;
use App\Models\RentalServiceFrequency;
use ReflectionClass;
use Tests\TestCase;

/**
 * Jarak antar periode service harus menghormati interval bulan (frequency_months).
 *
 * `rental_service_frequencies.frequency_times_per_month` adalah NOT NULL DEFAULT 1,
 * jadi cabang "times per month" di calculateScheduleDateForPeriod() SELALU diambil
 * dan cabang frequency_months di bawahnya tidak pernah tereksekusi. Akibatnya
 * frekuensi "1x per 2 bulan" (2M1X) dijadwalkan berjarak 1 bulan: jumlah periodenya
 * benar (6 untuk kontrak setahun, sudah ditangani calculateTotalServicePeriodsForRental)
 * tapi semuanya menumpuk di 6 bulan pertama.
 *
 * Belum pernah terlihat di produksi karena tabel rental_service_frequencies masih
 * kosong dan seluruh 337 master rental punya service_frequency_id NULL, sehingga
 * generate service lanjutan berhenti lebih dulu di "Total services is 0".
 */
class ServiceSchedulePeriodSpacingTest extends TestCase
{
    private function scheduleDate(string $baseDate, int $period, int $months, int $times): string
    {
        $controller = app(JobScheduleController::class);
        $method = (new ReflectionClass($controller))->getMethod('calculateScheduleDateForPeriod');
        $method->setAccessible(true);

        $frequency = new RentalServiceFrequency([
            'frequency_months' => $months,
            'frequency_times_per_month' => $times,
        ]);

        return $method->invoke($controller, $baseDate, $period, $frequency)->format('Y-m-d');
    }

    public function test_two_month_interval_spaces_services_two_months_apart(): void
    {
        $this->assertSame('2026-01-15', $this->scheduleDate('2026-01-15', 1, 2, 1));
        $this->assertSame('2026-03-15', $this->scheduleDate('2026-01-15', 2, 2, 1));
        $this->assertSame('2026-05-15', $this->scheduleDate('2026-01-15', 3, 2, 1));
        // Periode terakhir dari kontrak setahun harus mendarat di bulan ke-11, bukan ke-6.
        $this->assertSame('2026-11-15', $this->scheduleDate('2026-01-15', 6, 2, 1));
    }

    public function test_quarterly_interval_spaces_services_three_months_apart(): void
    {
        $this->assertSame('2026-04-10', $this->scheduleDate('2026-01-10', 2, 3, 1));
        $this->assertSame('2026-10-10', $this->scheduleDate('2026-01-10', 4, 3, 1));
    }

    public function test_monthly_frequency_is_unchanged(): void
    {
        $this->assertSame('2026-01-20', $this->scheduleDate('2026-01-20', 1, 1, 1));
        $this->assertSame('2026-02-20', $this->scheduleDate('2026-01-20', 2, 1, 1));
        $this->assertSame('2026-06-20', $this->scheduleDate('2026-01-20', 6, 1, 1));
    }

    public function test_multiple_services_per_month_are_unchanged(): void
    {
        // 2x sebulan: periode 1 dan 2 di bulan yang sama, periode 3 di bulan berikutnya.
        $this->assertSame('2026-01-05', $this->scheduleDate('2026-01-05', 1, 1, 2));
        $this->assertSame('2026-01-20', $this->scheduleDate('2026-01-05', 2, 1, 2));
        $this->assertSame('2026-02-05', $this->scheduleDate('2026-01-05', 3, 1, 2));
    }
}
