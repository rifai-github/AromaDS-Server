<?php

use App\Models\RentalServiceFrequency;
use App\Services\Operational\RentalServiceCadenceParser;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Isi katalog frekuensi service dan petakan master rental — supaya reset database
 * tidak menghapusnya lagi.
 *
 * Tanpa isi tabel ini, calculateTotalServicePeriodsForRental() selalu 0, sehingga
 * TIDAK ADA kontrak mana pun yang bisa menghasilkan job service/check lanjutan.
 *
 * Ini sudah dua kali dilaporkan QA (15/16 Sep 2026 dan 21 Sep 2026) untuk sebab yang
 * sama persis. Perbaikan 16 Sep dijalankan manual lewat
 * `php artisan rentals:seed-service-frequencies --apply`, lalu **hilang lagi** waktu
 * produksi di-bootstrap ulang 20 Sep 2026 23:57 — `aroma_fresh_bootstrap.sql` punya
 * tabel `rental_service_frequencies` tanpa satu pun baris INSERT, dan pemetaannya
 * hanya pernah ada sebagai command manual yang tidak masuk alur reset.
 *
 * Alur reset menjalankan `php artisan migrate`, jadi menaruhnya di migrasi membuatnya
 * ikut pulih sendiri setiap kali. Command manualnya tetap ada untuk menjalankan ulang
 * pemetaan di luar reset.
 *
 * Isinya bukan karangan: katalognya dari RentalServiceFrequency::getCommonFrequencies()
 * yang sudah ada sejak awal, dan kadens per rental dibaca dari nama rental oleh
 * RentalServiceCadenceParser. Nama tanpa petunjuk kadens DILEWATI, tidak ditebak — di
 * data produksi 230 dari 337 rental terpetakan, 107 sisanya (lini Hand Sanitizer/Soap
 * dan sejenisnya) memang tidak menyebut kadens dan menunggu keputusan manusia.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rental_service_frequencies') || ! Schema::hasTable('master_rentals')) {
            return;
        }

        $catalog = $this->syncCatalog();

        if (empty($catalog)) {
            return;
        }

        $this->mapRentals($catalog);
    }

    /**
     * Sengaja tidak menghapus apa pun.
     *
     * Baris katalog bisa sudah ditunjuk master rental, job schedule, dan periode service
     * yang terlanjur terbit; membuangnya saat rollback justru mengulang kerusakan yang
     * migrasi ini tutup. Rollback di sini berarti "biarkan datanya".
     */
    public function down(): void
    {
        // no-op, lihat docblock.
    }

    /**
     * @return array<string, int> code => rental_service_frequencies.id
     */
    private function syncCatalog(): array
    {
        $catalog = [];
        $now = now();

        foreach (RentalServiceFrequency::getCommonFrequencies() as $index => $definition) {
            $existing = DB::table('rental_service_frequencies')
                ->where('code', $definition['code'])
                ->first();

            if ($existing) {
                // Baris yang pernah dihapus (soft delete) dipulihkan: master rental yang
                // menunjuknya jadi menggantung, dan dropdown frekuensi ikut kehilangan
                // pilihannya.
                if (($existing->deleted_at ?? null) !== null) {
                    DB::table('rental_service_frequencies')
                        ->where('id', $existing->id)
                        ->update(['deleted_at' => null, 'updated_at' => $now]);
                }

                $catalog[$definition['code']] = (int) $existing->id;

                continue;
            }

            $months = max(1, (int) $definition['frequency_months']);
            $times = max(1, (int) $definition['frequency_times_per_month']);
            $perYear = (12 / $months) * $times;
            $interval = $months > 1 ? "setiap {$months} bulan" : 'setiap bulan';
            $repeat = $times > 1 ? " {$times} kali" : ' 1 kali';

            $catalog[$definition['code']] = (int) DB::table('rental_service_frequencies')->insertGetId([
                'code' => $definition['code'],
                'name' => $definition['name'],
                'description' => "Service {$interval}{$repeat} ({$perYear}x setahun)",
                'frequency_months' => $months,
                'frequency_times_per_month' => $times,
                'is_active' => true,
                'sort_order' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return $catalog;
    }

    /**
     * @param  array<string, int>  $catalog
     */
    private function mapRentals(array $catalog): void
    {
        $parser = new RentalServiceCadenceParser();

        $byCadence = [];
        foreach (DB::table('rental_service_frequencies')->whereIn('code', array_keys($catalog))->get() as $row) {
            $byCadence[((int) $row->frequency_months).'x'.((int) $row->frequency_times_per_month)] = (int) $row->id;
        }

        DB::table('master_rentals')
            ->whereNull('service_frequency_id')
            ->select('id', 'rental_name')
            ->orderBy('id')
            ->chunk(200, function ($rentals) use ($parser, $byCadence) {
                foreach ($rentals as $rental) {
                    $cadence = $parser->parse($rental->rental_name ?? '');

                    if (! $cadence) {
                        continue;
                    }

                    $key = $cadence['frequency_months'].'x'.$cadence['frequency_times_per_month'];

                    if (! isset($byCadence[$key])) {
                        continue;
                    }

                    DB::table('master_rentals')
                        ->where('id', $rental->id)
                        ->whereNull('service_frequency_id')
                        ->update(['service_frequency_id' => $byCadence[$key]]);
                }
            });
    }
};
