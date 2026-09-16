<?php

namespace App\Console\Commands;

use App\Models\MasterRental;
use App\Models\RentalServiceFrequency;
use App\Services\Operational\RentalServiceCadenceParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Mengisi katalog frekuensi service dan memetakannya ke master rental.
 *
 * Kondisi awal (QA 15 Sep 2026 part 2, dilaporkan ulang 16 Sep): tabel
 * rental_service_frequencies KOSONG dan SELURUH master rental punya
 * service_frequency_id NULL. Akibatnya
 * JobScheduleController::calculateTotalServicePeriodsForRental() selalu
 * mengembalikan 0, sehingga TIDAK ADA kontrak mana pun yang bisa menghasilkan
 * job service lanjutan -- bukan cuma job yang dilaporkan. Dropdown frekuensi di
 * form Master Rental pun sumbernya tabel kosong itu, jadi lubang ini tidak bisa
 * ditutup lewat UI.
 *
 * Katalognya bukan karangan: daftarnya sudah ada di
 * RentalServiceFrequency::getCommonFrequencies() sejak awal, cuma tidak pernah
 * dipanggil siapa pun. Pemetaan per rental dibaca dari nama rental
 * (lihat RentalServiceCadenceParser) -- nama yang tidak memuat kadens dilewati,
 * tidak ditebak.
 */
class SeedRentalServiceFrequencies extends Command
{
    protected $signature = 'rentals:seed-service-frequencies
                            {--apply : Terapkan perubahan (default dry-run)}
                            {--catalog-only : Hanya isi katalog frekuensi, jangan petakan master rental}
                            {--overwrite : Petakan juga rental yang service_frequency_id-nya sudah terisi}
                            {--show-unmapped : Cetak seluruh rental yang tidak punya petunjuk kadens di namanya}';

    protected $description = 'Isi rental_service_frequencies dari katalog bawaan dan petakan master_rentals.service_frequency_id dari kadens di nama rental';

    public function handle(RentalServiceCadenceParser $parser): int
    {
        $apply = (bool) $this->option('apply');

        if (! $apply) {
            $this->info('DRY RUN. Tidak ada perubahan database. Tambahkan --apply untuk menerapkan.');
        }

        $this->newLine();
        $catalog = $this->syncCatalog($apply);

        if ($this->option('catalog-only')) {
            return self::SUCCESS;
        }

        $this->newLine();

        return $this->mapRentals($parser, $catalog, $apply);
    }

    /**
     * @return array<string, mixed>
     */
    private function syncCatalog(bool $apply): array
    {
        $this->line('== Katalog frekuensi ==');

        $existing = RentalServiceFrequency::withTrashed()->get()->keyBy('code');
        $catalog = [];
        $created = 0;
        $restored = 0;

        foreach (RentalServiceFrequency::getCommonFrequencies() as $index => $definition) {
            $row = $existing->get($definition['code']);

            if ($row) {
                if ($row->trashed()) {
                    $this->warn("  ~ {$definition['code']} ada tapi terhapus (soft delete)".($apply ? ' - dipulihkan' : ''));
                    if ($apply) {
                        $row->restore();
                    }
                    $restored++;
                } else {
                    $this->line("  = {$definition['code']} sudah ada");
                }

                $catalog[$definition['code']] = $row;

                continue;
            }

            $attributes = $definition + [
                'description' => $this->describe($definition),
                'sort_order' => $index + 1,
                'is_active' => true,
            ];

            $this->line("  + {$definition['code']}  {$attributes['description']}");
            $created++;

            $catalog[$definition['code']] = $apply
                ? RentalServiceFrequency::create($attributes)
                : (object) $attributes;
        }

        $this->line("  -> baru: {$created}, dipulihkan: {$restored}, total katalog: ".count($catalog));

        return $catalog;
    }

    private function describe(array $definition): string
    {
        $months = max(1, (int) $definition['frequency_months']);
        $times = max(1, (int) $definition['frequency_times_per_month']);
        $perYear = (12 / $months) * $times;

        $interval = $months > 1 ? "setiap {$months} bulan" : 'setiap bulan';
        $repeat = $times > 1 ? " {$times} kali" : ' 1 kali';

        return "Service {$interval}{$repeat} ({$perYear}x setahun)";
    }

    /**
     * @param  array<string, mixed>  $catalog
     */
    private function mapRentals(RentalServiceCadenceParser $parser, array $catalog, bool $apply): int
    {
        $this->line('== Pemetaan master rental ==');

        // Katalog dikunci berdasarkan kadensnya, bukan kodenya, supaya hasil
        // parser jatuh ke baris yang benar tanpa menebak-nebak kode.
        $byCadence = [];
        foreach ($catalog as $code => $row) {
            $byCadence[$row->frequency_months.'x'.$row->frequency_times_per_month] = ['code' => $code, 'row' => $row];
        }

        $query = MasterRental::query();
        if (! $this->option('overwrite')) {
            $query->whereNull('service_frequency_id');
        }
        $rentals = $query->orderBy('rental_code')->get();

        $perCadence = [];
        $unmapped = [];
        $noCatalogMatch = [];
        $updates = [];

        foreach ($rentals as $rental) {
            $cadence = $parser->parse($rental->rental_name);

            if (! $cadence) {
                $unmapped[] = $rental;

                continue;
            }

            $key = $cadence['frequency_months'].'x'.$cadence['frequency_times_per_month'];

            if (! isset($byCadence[$key])) {
                $noCatalogMatch[] = [$rental, $key];

                continue;
            }

            $match = $byCadence[$key];
            $perCadence[$match['code']] = ($perCadence[$match['code']] ?? 0) + 1;

            if ($apply) {
                $updates[$match['row']->id][] = $rental->id;
            }
        }

        $this->line('  Rental diperiksa: '.$rentals->count());

        foreach ($perCadence as $code => $count) {
            $row = $catalog[$code];
            $this->line(sprintf('  %-6s %4d rental   (%s)', $code, $count, $this->describe([
                'frequency_months' => $row->frequency_months,
                'frequency_times_per_month' => $row->frequency_times_per_month,
            ])));
        }

        $mapped = array_sum($perCadence);
        $this->line("  -> terpetakan: {$mapped}, tanpa petunjuk di nama: ".count($unmapped).', kadens di luar katalog: '.count($noCatalogMatch));

        foreach ($noCatalogMatch as [$rental, $key]) {
            $this->warn("     ! {$rental->rental_code} butuh kadens {$key} yang belum ada di katalog - {$rental->rental_name}");
        }

        if ($this->option('show-unmapped')) {
            $this->newLine();
            $this->line('  -- tanpa petunjuk kadens (dilewati, isi manual lewat UI Master Rental) --');
            foreach ($unmapped as $rental) {
                $this->line("     {$rental->rental_code}  {$rental->rental_name}");
            }
        } elseif ($unmapped) {
            $this->line('     (pakai --show-unmapped untuk mencetak daftarnya)');
        }

        if (! $apply) {
            $this->newLine();
            $this->info('Dry-run selesai. Tidak ada yang ditulis.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($updates) {
            foreach ($updates as $frequencyId => $rentalIds) {
                foreach (array_chunk($rentalIds, 500) as $chunk) {
                    MasterRental::whereIn('id', $chunk)->update(['service_frequency_id' => $frequencyId]);
                }
            }
        });

        $this->newLine();
        $this->info("Selesai. {$mapped} master rental diperbarui.");

        return self::SUCCESS;
    }
}
