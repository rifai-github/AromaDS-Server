<?php

namespace App\Services\Operational;

/**
 * Membaca kadens service dari nama master rental.
 *
 * Nama rental dari "Master Product.xlsx" sudah memuat kadensnya sendiri dalam
 * dua ejaan: "12 SVC / YR PCKG" (berapa kali setahun) dan "1 Bln 1x" (setiap
 * N bulan, M kali). Kolom service_frequency_id sendiri tidak ikut ter-import,
 * jadi nama inilah satu-satunya sumber yang ada di data.
 *
 * Hasilnya sengaja dibatasi ke kadens yang sudah didefinisikan
 * RentalServiceFrequency::getCommonFrequencies() -- parser ini tidak mengarang
 * frekuensi baru. Nama tanpa petunjuk mengembalikan null; pemanggil yang
 * memutuskan apa artinya (umumnya: lewati, biar diisi manusia).
 */
class RentalServiceCadenceParser
{
    /**
     * @return array{frequency_months:int, frequency_times_per_month:int, source:string}|null
     */
    public function parse(?string $rentalName): ?array
    {
        $name = trim((string) $rentalName);

        if ($name === '') {
            return null;
        }

        // "1 Bln 1x", "1BLN 1X", "2 Bln 1x" -> setiap N bulan, M kali.
        if (preg_match('/(\d+)\s*BLN\s*(\d+)\s*X/i', $name, $m)) {
            $months = (int) $m[1];
            $times = (int) $m[2];

            return $months > 0 && $times > 0
                ? ['frequency_months' => $months, 'frequency_times_per_month' => $times, 'source' => 'bln']
                : null;
        }

        // "12 SVC / YR PCKG", "6 SVC / YR" -> N service setahun.
        if (preg_match('/(\d+)\s*SVC\s*\/?\s*YR/i', $name, $m)) {
            $perYear = (int) $m[1];

            if ($perYear <= 0) {
                return null;
            }

            // <= 12x setahun: satu service setiap 12/N bulan. Hanya pembagi
            // bulat yang diterima -- "5 SVC / YR" bukan interval bulat dan
            // tidak punya padanan di getCommonFrequencies().
            if ($perYear <= 12 && 12 % $perYear === 0) {
                return ['frequency_months' => intdiv(12, $perYear), 'frequency_times_per_month' => 1, 'source' => 'svc'];
            }

            // > 12x setahun: beberapa kali dalam satu bulan.
            if ($perYear % 12 === 0) {
                return ['frequency_months' => 1, 'frequency_times_per_month' => intdiv($perYear, 12), 'source' => 'svc'];
            }

            return null;
        }

        return null;
    }
}
