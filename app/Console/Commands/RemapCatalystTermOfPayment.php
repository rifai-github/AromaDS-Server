<?php

namespace App\Console\Commands;

use App\Services\Imports\Catalyst\CatalystMasterDataImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Perbaiki quotation/contract hasil import Catalyst yang masih menyimpan kode
 * TermOfPayment mentah ("00", "3xM", "QUA", "5xA") dan rental_period bergaya
 * "6 bulan" (bikin list SQ menampilkan "6 bulan Bulan").
 *
 * Mapping-nya satu sumber dengan importer:
 * CatalystMasterDataImporter::mapCatalystTermOfPayment().
 */
class RemapCatalystTermOfPayment extends Command
{
    protected $signature = 'catalyst:remap-term-of-payment
                            {--apply : Persist changes (default: dry-run)}';

    protected $description = 'Remap Catalyst TermOfPayment codes and rental_period on imported quotations/contracts.';

    public function handle(CatalystMasterDataImporter $importer): int
    {
        $apply = (bool) $this->option('apply');

        $this->info($apply ? 'Mode: APPLY (menulis ke database)' : 'Mode: DRY-RUN (tidak menulis apa pun)');

        $quotationStats = $this->remapQuotations($importer, $apply);
        $contractStats = $this->remapContracts($importer, $apply);

        $this->newLine();
        $this->table(['Tabel', 'Diperiksa', 'Diubah', 'Tidak dikenali'], [
            ['quotations', $quotationStats['processed'], $quotationStats['updated'], $quotationStats['unmapped']],
            ['contracts', $contractStats['processed'], $contractStats['updated'], $contractStats['unmapped']],
        ]);

        $unmapped = array_unique(array_merge($quotationStats['codes'], $contractStats['codes']));
        if ($unmapped) {
            sort($unmapped);
            $this->warn('Kode TermOfPayment yang tidak bisa dipetakan: '.implode(', ', $unmapped));
        }

        if (! $apply) {
            $this->newLine();
            $this->comment('Jalankan ulang dengan --apply untuk menyimpan perubahan.');
        }

        return self::SUCCESS;
    }

    private function remapQuotations(CatalystMasterDataImporter $importer, bool $apply): array
    {
        $stats = ['processed' => 0, 'updated' => 0, 'unmapped' => 0, 'codes' => []];

        DB::table('quotations')
            ->select('id', 'quotation_number', 'rental_period', 'rental_unit', 'terms_of_payment', 'top_months', 'payment_method', 'billing_methods')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($importer, $apply, &$stats) {
                foreach ($rows as $row) {
                    $stats['processed']++;
                    $payload = [];

                    $periodMonths = $this->normalizeRentalPeriod($row->rental_period, $row->rental_unit);
                    if ($periodMonths !== null && (string) $row->rental_period !== (string) $periodMonths) {
                        $payload['rental_period'] = (string) $periodMonths;
                    }

                    $term = $importer->mapCatalystTermOfPayment($row->terms_of_payment);

                    if ($term && $term['top'] !== $row->terms_of_payment) {
                        $payload['terms_of_payment'] = $term['top'];

                        $topMonths = $importer->catalystTopMonths($term, (int) ($periodMonths ?? 0));
                        if ($topMonths !== null && (int) $row->top_months !== $topMonths) {
                            $payload['top_months'] = $topMonths;
                        }

                        // Before/After Service hanya diisi kalau masih kosong, supaya
                        // tidak menimpa koreksi manual marketing.
                        if (blank($row->payment_method)) {
                            $payload['payment_method'] = $term['timing'];
                        }
                        if (blank($row->billing_methods)) {
                            $payload['billing_methods'] = $term['timing'];
                        }
                    } elseif (! $term && filled($row->terms_of_payment) && $this->looksLikeCatalystCode($row->terms_of_payment)) {
                        $stats['unmapped']++;
                        $stats['codes'][] = (string) $row->terms_of_payment;
                    }

                    if (! $payload) {
                        continue;
                    }

                    $stats['updated']++;
                    $this->line(sprintf(
                        '  %s: %s',
                        $row->quotation_number,
                        collect($payload)->map(fn ($v, $k) => "{$k}={$v}")->implode(', ')
                    ), null, 'v');

                    if ($apply) {
                        $payload['updated_at'] = now();
                        DB::table('quotations')->where('id', $row->id)->update($payload);
                    }
                }
            });

        return $stats;
    }

    private function remapContracts(CatalystMasterDataImporter $importer, bool $apply): array
    {
        $stats = ['processed' => 0, 'updated' => 0, 'unmapped' => 0, 'codes' => []];

        DB::table('contracts')
            ->select('id', 'contract_number', 'term_of_payment')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($importer, $apply, &$stats) {
                foreach ($rows as $row) {
                    $stats['processed']++;

                    $term = $importer->mapCatalystTermOfPayment($row->term_of_payment);

                    if (! $term) {
                        if (filled($row->term_of_payment) && $this->looksLikeCatalystCode($row->term_of_payment)) {
                            $stats['unmapped']++;
                            $stats['codes'][] = (string) $row->term_of_payment;
                        }

                        continue;
                    }

                    if ($term['top'] === $row->term_of_payment) {
                        continue;
                    }

                    $stats['updated']++;
                    $this->line("  {$row->contract_number}: term_of_payment={$term['top']}", null, 'v');

                    if ($apply) {
                        DB::table('contracts')->where('id', $row->id)->update([
                            'term_of_payment' => $term['top'],
                            'updated_at' => now(),
                        ]);
                    }
                }
            });

        return $stats;
    }

    /**
     * "6 bulan" / "6" -> 6. Satuan hari dibiarkan apa adanya (return null)
     * karena rental_unit-nya bukan bulan.
     */
    private function normalizeRentalPeriod($rentalPeriod, $rentalUnit): ?int
    {
        if (blank($rentalPeriod) || $rentalUnit === 'hari') {
            return null;
        }

        if (! preg_match('/^\s*(\d+)/', (string) $rentalPeriod, $m)) {
            return null;
        }

        $months = (int) $m[1];

        return $months > 0 ? $months : null;
    }

    /**
     * Nilai TOP yang sah di ADS selalu memuat "bulan", "tahunan", atau
     * "periode contract". Sisanya dianggap kode Catalyst yang belum terpetakan.
     */
    private function looksLikeCatalystCode(string $value): bool
    {
        return ! preg_match('/bulan|tahunan|periode contract/i', $value);
    }
}
