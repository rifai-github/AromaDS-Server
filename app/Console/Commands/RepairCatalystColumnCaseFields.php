<?php

namespace App\Console\Commands;

use App\Services\Imports\Catalyst\CatalystMasterDataImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use ReflectionClass;

/**
 * Perbaiki data yang hilang karena importer Catalyst membaca nama kolom sumber
 * dengan ejaan yang salah.
 *
 * Kunci array PHP case-sensitive, sedangkan SQL Server tidak. Baris sumber
 * diambil `SELECT *` lalu di-cast jadi array, jadi kuncinya mengikuti ejaan
 * kolom PERSIS — sementara pengecekan manual lewat `->select('SqNo')` tetap
 * mengembalikan data dengan benar. Akibatnya nilainya diam-diam jadi null dan
 * kesalahannya tidak pernah terlihat.
 *
 * Tiga kolom terdampak, semuanya sudah diperbaiki di importer:
 *  - MKTContractHd.SQNo       (dibaca 'SqNo')       -> contracts.quotation_id
 *  - MKTQuotationHd.PPnForex  (dibaca 'PpnForex')   -> quotations.tax_amount
 *  - MKTQuotationHd.SOContractNo (dibaca 'SoContractNo') -> penentu status 'contract'
 *
 * Perbaikan importer hanya berlaku untuk import BERIKUTNYA; command ini untuk
 * baris yang sudah telanjur masuk. Membaca ulang nilainya dari sumber, bukan
 * menebak dari data lokal, jadi hasilnya sama persis dengan hasil import ulang.
 *
 * Status quotation SENGAJA tidak diubah — hanya dilaporkan. Mengubah status
 * menggeser alur kerja, dan itu keputusan yang harus diambil secara sadar.
 */
class RepairCatalystColumnCaseFields extends Command
{
    protected $signature = 'catalyst:repair-column-case-fields
                            {--only=all : Bagian yang dikerjakan: all, contracts, atau quotations}
                            {--limit=0 : Batasi jumlah baris sumber yang diperiksa (0 = semua)}
                            {--apply : Tulis perubahan (default dry-run)}';

    protected $description = 'Perbaiki contracts.quotation_id dan quotations.tax_amount yang hilang karena salah ejaan nama kolom sumber Catalyst (SQNo / PPnForex)';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $only = strtolower((string) $this->option('only'));

        if (! in_array($only, ['all', 'contracts', 'quotations'], true)) {
            $this->error("--only harus salah satu dari: all, contracts, quotations.");

            return self::FAILURE;
        }

        if (! $apply) {
            $this->info('DRY RUN. Tidak ada perubahan yang ditulis. Tambahkan --apply untuk menyimpan.');
        }

        $rows = [];

        if ($only === 'all' || $only === 'contracts') {
            $rows[] = $this->repairContractQuotationLinks($apply);
        }

        if ($only === 'all' || $only === 'quotations') {
            $rows[] = $this->repairQuotationTaxAmounts($apply);
            $this->reportQuotationContractStatus();
        }

        $this->newLine();
        $this->table(['Bagian', 'Diperiksa', 'Akan diubah', 'Sudah benar', 'Tidak cocok'], $rows);

        if (! $apply) {
            $this->warn('Dry-run selesai. Jalankan ulang dengan --apply untuk menerapkan.');
        }

        return self::SUCCESS;
    }

    /**
     * contracts.quotation_id dari MKTContractHd.SQNo.
     */
    private function repairContractQuotationLinks(bool $apply): array
    {
        $this->info('Memeriksa contracts.quotation_id dari kolom sumber SQNo...');

        $sourceRows = $this->sourceTable('MKTContractHd')
            ->whereNotNull('TransNmbr')
            ->select('TransNmbr', 'SQNo')
            ->when($this->limit(), fn ($q, $limit) => $q->limit($limit))
            ->get();

        $quotationIdByNumber = DB::table('quotations')
            ->whereNull('deleted_at')
            ->pluck('id', 'quotation_number');

        $checked = 0;
        $toChange = 0;
        $alreadyCorrect = 0;
        $unmatched = 0;
        $samples = [];
        $pending = [];

        foreach ($sourceRows as $row) {
            $contractNumber = trim((string) $row->TransNmbr);
            $sqNo = trim((string) $row->SQNo);

            if ($contractNumber === '' || $sqNo === '') {
                continue;
            }

            $checked++;

            $contract = DB::table('contracts')
                ->whereNull('deleted_at')
                ->where('contract_number', $contractNumber)
                ->first(['id', 'quotation_id']);

            if (! $contract) {
                $unmatched++;

                continue;
            }

            $quotationId = $quotationIdByNumber[$sqNo] ?? $quotationIdByNumber[strtoupper($sqNo)] ?? null;

            if (! $quotationId) {
                $unmatched++;

                continue;
            }

            if ((int) ($contract->quotation_id ?? 0) === (int) $quotationId) {
                $alreadyCorrect++;

                continue;
            }

            // Kontrak yang sudah menunjuk quotation LAIN tidak ditimpa — itu bisa
            // hasil penautan manual dan bukan wewenang perbaikan ini.
            if (! empty($contract->quotation_id)) {
                $unmatched++;

                continue;
            }

            $toChange++;
            $pending[(int) $contract->id] = (int) $quotationId;

            if (count($samples) < 10) {
                $samples[] = [$contractNumber, $sqNo, $quotationId];
            }
        }

        if ($samples) {
            $this->newLine();
            $this->line('Contoh tautan yang '.($apply ? 'dibuat' : 'akan dibuat').':');
            $this->table(['Contract', 'SQNo (sumber)', 'quotation_id'], $samples);
        }

        if ($apply && $pending) {
            foreach (array_chunk($pending, 500, true) as $chunk) {
                foreach ($chunk as $contractId => $quotationId) {
                    DB::table('contracts')
                        ->where('id', $contractId)
                        ->whereNull('quotation_id')
                        ->update(['quotation_id' => $quotationId, 'updated_at' => now()]);
                }
            }
        }

        return ['contracts.quotation_id', $checked, $toChange, $alreadyCorrect, $unmatched];
    }

    /**
     * quotations.tax_amount dari MKTQuotationHd.PPnForex.
     */
    private function repairQuotationTaxAmounts(bool $apply): array
    {
        $this->info('Memeriksa quotations.tax_amount dari kolom sumber PPnForex...');

        $sourceRows = $this->sourceTable('MKTQuotationHd')
            ->whereNotNull('TransNmbr')
            ->select('TransNmbr', 'PPnForex')
            ->when($this->limit(), fn ($q, $limit) => $q->limit($limit))
            ->get();

        $checked = 0;
        $alreadyCorrect = 0;
        $unmatched = 0;
        $samples = [];
        $pending = [];
        $seenNumbers = [];
        $duplicateNumbers = 0;

        foreach ($sourceRows as $row) {
            $number = trim((string) $row->TransNmbr);

            if ($number === '') {
                continue;
            }

            $checked++;

            // Nomor SQ bisa muncul lebih dari sekali di sumber. Nilainya dikunci per
            // quotation (baris terakhir menang), sama seperti perilaku syncRecord saat
            // import, sehingga hasilnya konsisten dengan import ulang.
            if (isset($seenNumbers[$number])) {
                $duplicateNumbers++;
            }
            $seenNumbers[$number] = true;

            $quotation = DB::table('quotations')
                ->whereNull('deleted_at')
                ->where('quotation_number', $number)
                ->first(['id', 'tax_amount']);

            if (! $quotation) {
                $unmatched++;

                continue;
            }

            $sourceTax = round((float) ($row->PPnForex ?? 0), 2);
            $currentTax = round((float) ($quotation->tax_amount ?? 0), 2);

            if (abs($sourceTax - $currentTax) < 0.01) {
                $alreadyCorrect++;

                continue;
            }

            $pending[(int) $quotation->id] = $sourceTax;

            if (count($samples) < 10) {
                $samples[] = [$number, number_format($currentTax, 2), number_format($sourceTax, 2)];
            }
        }

        if ($samples) {
            $this->newLine();
            $this->line('Contoh nilai pajak yang '.($apply ? 'diperbarui' : 'akan diperbarui').':');
            $this->table(['Quotation', 'tax_amount sekarang', 'tax_amount dari sumber'], $samples);
        }

        $this->line(sprintf(
            'Baris sumber diperiksa: %d, nomor SQ muncul berulang: %d, quotation unik terdampak: %d, total pajak: %s',
            $checked,
            $duplicateNumbers,
            count($pending),
            number_format(array_sum($pending), 2)
        ));

        if ($apply && $pending) {
            foreach ($pending as $quotationId => $tax) {
                DB::table('quotations')
                    ->where('id', $quotationId)
                    ->update(['tax_amount' => $tax, 'updated_at' => now()]);
            }
        }

        // "Akan diubah" dihitung per quotation, bukan per baris sumber: satu nomor SQ
        // yang muncul berulang hanya menghasilkan satu perubahan.
        return ['quotations.tax_amount', $checked, count($pending), $alreadyCorrect, $unmatched];
    }

    /**
     * SOContractNo ikut menentukan status 'contract'. Statusnya TIDAK diubah di
     * sini — mengubah status menggeser alur kerja, jadi angkanya hanya dilaporkan
     * supaya bisa diputuskan secara sadar.
     */
    private function reportQuotationContractStatus(): void
    {
        $withSoContract = $this->sourceTable('MKTQuotationHd')
            ->whereNotNull('SOContractNo')
            ->where('SOContractNo', '<>', '')
            ->count();

        $localContractStatus = DB::table('quotations')->where('status', 'contract')->count();

        $this->newLine();
        $this->line('Status quotation (hanya laporan, tidak diubah oleh command ini):');
        $this->table(['Ukuran', 'Jumlah'], [
            ['Baris sumber dengan SOContractNo terisi', $withSoContract],
            ["Quotation lokal berstatus 'contract'", $localContractStatus],
        ]);
    }

    private function limit(): ?int
    {
        $limit = (int) $this->option('limit');

        return $limit > 0 ? $limit : null;
    }

    /**
     * Koneksi sumber dibangun oleh importer sendiri; di sini hanya dipinjam supaya
     * konfigurasinya tidak digandakan.
     */
    private function sourceTable(string $table)
    {
        $importer = app(CatalystMasterDataImporter::class);
        $method = (new ReflectionClass($importer))->getMethod('source');
        $method->setAccessible(true);

        return $method->invoke($importer)->table($table);
    }
}
