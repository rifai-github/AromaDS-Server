<?php

namespace App\Console\Commands;

use App\Services\Imports\Catalyst\CatalystMasterDataImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Audit read-only. Menjawab satu pertanyaan:
 *
 *   Step `master_rentals` ada di DISABLED_STEPS (rental sekarang dari Master Product.xlsx),
 *   padahal `quotation_rentals`, `quotation_details`, dan `contract_rentals` masih resolve
 *   rental lewat peta MsProduct -> master_rentals yang HANYA ditulis step itu. Petanya kosong,
 *   jadi setiap baris rental gagal dengan "Master rental missing".
 *
 *   Kalau di-fallback ke master_rentals.rental_code (formatnya sama persis: makeKey(ProductCode)),
 *   berapa persen baris yang tertolong, dan kode apa saja yang tetap tidak ketemu?
 *
 * Command ini TIDAK menulis apa pun ke database.
 */
class AuditCatalystRentalCoverage extends Command
{
    protected $signature = 'catalyst:audit-rental-coverage
                            {--top=30 : Berapa kode Product tak-cocok teratas yang ditampilkan}
                            {--export= : Tulis daftar lengkap kode tak-cocok ke file CSV di path ini}
                            {--skip-source : Lewati bagian yang butuh koneksi SQL Server Catalyst}';

    protected $description = 'Audit read-only: cakupan kode Product Catalyst (SQ/kontrak) terhadap master_rentals.rental_code, untuk mengukur apakah fallback rental_code cukup menggantikan peta MsProduct->master_rentals yang kosong';

    public function handle(): int
    {
        $this->section('1. Kondisi peta & step');

        $disabled = in_array('master_rentals', CatalystMasterDataImporter::DISABLED_STEPS, true);
        $this->line(sprintf(
            'Step master_rentals di DISABLED_STEPS : %s',
            $disabled ? 'YA (peta MsProduct->master_rentals tidak akan pernah terisi)' : 'tidak'
        ));

        $mapCount = DB::table('source_import_maps')
            ->where('source_system', 'catalyst')
            ->where('source_table', 'MsProduct')
            ->where('target_table', 'master_rentals')
            ->count();
        $this->line('Baris source_import_maps MsProduct->master_rentals : ' . $mapCount);

        $rentalTotal = DB::table('master_rentals')->count();
        $rentalWithCode = DB::table('master_rentals')
            ->whereNotNull('rental_code')
            ->where('rental_code', '!=', '')
            ->count();
        $this->line("Baris master_rentals : {$rentalTotal} (punya rental_code: {$rentalWithCode})");

        $this->section('2. Dampak yang sudah terjadi di DB ini');

        $counts = [];
        foreach ([
            'quotations',
            'quotation_rooms',
            'quotation_rentals',
            'quotation_details',
            'contracts',
            'contract_rentals',
            'job_advices',
            'job_advice_rooms',
        ] as $table) {
            $counts[] = [$table, DB::table($table)->count()];
        }
        $this->table(['Tabel', 'Jumlah baris'], $counts);

        $this->section('3. Log importer: pesan "Master rental missing"');

        $batch = DB::table('source_import_batches')
            ->where('source_system', 'catalyst')
            ->orderByDesc('id')
            ->first();

        if (!$batch) {
            $this->warn('Belum ada source_import_batches untuk catalyst.');
        } else {
            $this->line("Batch terakhir #{$batch->id} | status={$batch->status} | selesai={$batch->finished_at}");

            $rows = DB::table('source_import_logs')
                ->where('batch_id', $batch->id)
                ->where('message', 'like', 'Master rental missing%')
                ->select('step', 'status', DB::raw('COUNT(*) as cnt'))
                ->groupBy('step', 'status')
                ->orderByDesc('cnt')
                ->get();

            if ($rows->isEmpty()) {
                $this->line('Tidak ada baris log "Master rental missing" di batch ini.');
                $this->line('(Log bisa saja sudah dipangkas, atau step-nya belum pernah dijalankan.)');
            } else {
                $this->table(
                    ['Step', 'Status', 'Jumlah'],
                    $rows->map(fn ($r) => [$r->step, $r->status, $r->cnt])->all()
                );
            }
        }

        if ($this->option('skip-source')) {
            $this->newLine();
            $this->warn('Bagian 4 dilewati (--skip-source). Cakupan kode Product belum diukur.');

            return self::SUCCESS;
        }

        $this->section('4. Cakupan kode Product Catalyst terhadap master_rentals.rental_code');

        try {
            $source = $this->sourceConnection();
            $source->getPdo();
        } catch (Throwable $e) {
            $this->error('Koneksi source Catalyst gagal: ' . $e->getMessage());
            $this->line('Jalankan command ini di server yang punya driver sqlsrv + akses PinkAds,');
            $this->line('atau pakai --skip-source untuk hanya melihat bagian 1-3.');

            return self::FAILURE;
        }

        // rental_code disimpan apa adanya (makeKey = trim). Cocokkan di PHP supaya hasilnya
        // tidak bergantung collation MySQL - kode/SN di sistem ini case-sensitive.
        $exact = [];
        $loose = [];
        foreach (DB::table('master_rentals')->whereNotNull('rental_code')->pluck('rental_code') as $code) {
            $code = trim((string) $code);
            if ($code === '') {
                continue;
            }
            $exact[$code] = true;
            $loose[Str::lower($code)] = $code;
        }

        $feeds = [
            'quotation_rentals / quotation_details' => 'MKTQuotationRental',
            'contract_rentals' => 'MKTContractDt',
        ];

        $unmatchedAll = [];

        foreach ($feeds as $label => $table) {
            $this->newLine();
            $this->line("<info>Feed: {$label}</info>  (sumber: {$table}.Product)");

            $rows = $source->table($table)
                ->whereNotNull('TransNmbr')
                ->whereNotNull('Product')
                ->selectRaw('Product as code, COUNT(*) as cnt')
                ->groupBy('Product')
                ->get();

            $distinct = 0;
            $rowsTotal = 0;
            $distinctExact = 0;
            $rowsExact = 0;
            $distinctCaseOnly = 0;
            $rowsCaseOnly = 0;
            $unmatched = [];

            foreach ($rows as $row) {
                $code = trim((string) $row->code);
                if ($code === '' || in_array(Str::lower($code), ['null', '\\n'], true)) {
                    continue;
                }

                $cnt = (int) $row->cnt;
                $distinct++;
                $rowsTotal += $cnt;

                if (isset($exact[$code])) {
                    $distinctExact++;
                    $rowsExact += $cnt;

                    continue;
                }

                if (isset($loose[Str::lower($code)])) {
                    $distinctCaseOnly++;
                    $rowsCaseOnly += $cnt;
                    $unmatched[$code] = [
                        'cnt' => $cnt,
                        'note' => 'beda besar-kecil huruf saja -> ' . $loose[Str::lower($code)],
                    ];

                    continue;
                }

                $unmatched[$code] = ['cnt' => $cnt, 'note' => ''];
            }

            $pct = fn ($n, $d) => $d > 0 ? number_format($n / $d * 100, 1) . '%' : '-';

            $this->table(['Ukuran', 'Cocok persis', 'Total', 'Cakupan'], [
                ['Kode Product unik', $distinctExact, $distinct, $pct($distinctExact, $distinct)],
                ['Baris sumber', $rowsExact, $rowsTotal, $pct($rowsExact, $rowsTotal)],
            ]);

            if ($distinctCaseOnly > 0) {
                $this->warn(sprintf(
                    'Tambahan %d kode unik (%d baris) cocok kalau huruf besar-kecil diabaikan. Perlu keputusan: kode/SN di sistem ini case-sensitive.',
                    $distinctCaseOnly,
                    $rowsCaseOnly
                ));
            }

            if (!$unmatched) {
                $this->info('Semua kode Product di feed ini ketemu di master_rentals.rental_code.');

                continue;
            }

            uasort($unmatched, fn ($a, $b) => $b['cnt'] <=> $a['cnt']);

            $this->line('Kode tak-cocok teratas:');
            $this->table(
                ['Kode Product', 'Baris', 'Catatan'],
                collect(array_slice($unmatched, 0, max((int) $this->option('top'), 1), true))
                    ->map(fn ($v, $k) => [$k, $v['cnt'], $v['note']])
                    ->values()
                    ->all()
            );
            $this->line(sprintf('Total kode tak-cocok di feed ini: %d', count($unmatched)));

            foreach ($unmatched as $code => $v) {
                $unmatchedAll[$code]['cnt'] = ($unmatchedAll[$code]['cnt'] ?? 0) + $v['cnt'];
                $unmatchedAll[$code]['note'] = $v['note'];
                $unmatchedAll[$code]['feeds'][$label] = true;
            }
        }

        if ($export = $this->option('export')) {
            uasort($unmatchedAll, fn ($a, $b) => $b['cnt'] <=> $a['cnt']);

            $handle = fopen($export, 'w');
            fputcsv($handle, ['product_code', 'source_rows', 'feeds', 'note']);
            foreach ($unmatchedAll as $code => $v) {
                fputcsv($handle, [$code, $v['cnt'], implode(' + ', array_keys($v['feeds'] ?? [])), $v['note']]);
            }
            fclose($handle);

            $this->newLine();
            $this->info(sprintf('Daftar lengkap %d kode tak-cocok ditulis ke %s', count($unmatchedAll), $export));
        }

        return self::SUCCESS;
    }

    /**
     * Bangun koneksi source dengan cara yang sama seperti CatalystMasterDataImporter::source().
     */
    protected function sourceConnection()
    {
        $name = config('catalyst-import.connection_name', 'catalyst_import');

        if (!config("database.connections.{$name}")) {
            $connection = [
                'driver' => config('catalyst-import.source.driver', 'sqlsrv'),
                'host' => config('catalyst-import.source.host'),
                'port' => config('catalyst-import.source.port'),
                'database' => config('catalyst-import.source.database'),
                'charset' => config('catalyst-import.source.charset', 'utf8'),
                'prefix' => config('catalyst-import.source.prefix', ''),
                'prefix_indexes' => true,
                'encrypt' => config('catalyst-import.source.encrypt', false),
                'trust_server_certificate' => config('catalyst-import.source.trust_server_certificate', true),
                'trusted_connection' => config('catalyst-import.source.trusted_connection', false),
            ];

            $username = config('catalyst-import.source.username');
            $password = config('catalyst-import.source.password');

            if (!blank($username)) {
                $connection['username'] = $username;
            }

            if (!blank($password)) {
                $connection['password'] = $password;
            }

            config(["database.connections.{$name}" => $connection]);
        }

        return DB::connection($name);
    }

    protected function section(string $title): void
    {
        $this->newLine();
        $this->line('<comment>== ' . $title . ' ==</comment>');
    }
}
