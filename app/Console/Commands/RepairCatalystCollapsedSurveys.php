<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Perbaiki survei hasil import Catalyst yang menyatu jadi satu baris.
 *
 * Penyebabnya dua bug yang sudah diperbaiki di kode:
 *  1. DocumentNumberService membaca 4 karakter terakhir nomor, jadi di atas 9999 dia
 *     mengembalikan nomor yang SUDAH dipakai.
 *  2. Step `surveys` mencocokkan baris lewat `survey_number`, jadi nomor tabrakan tidak
 *     membuat baris baru melainkan MENIMPA survei milik pasangan (SQ, gedung) lain.
 *
 * Memperbaiki kodenya saja tidak cukup: source_import_maps ikut tercemar - banyak source key
 * menunjuk ke satu survei yang sama. Selama baris peta itu ada, re-run akan meng-update survei
 * yang sama lagi, bukan membuat yang hilang.
 *
 * Urutan pemakaian:
 *   1. php artisan catalyst:repair-collapsed-surveys                    (laporan)
 *   2. php artisan catalyst:repair-collapsed-surveys --phase=purge --apply
 *   3. php artisan catalyst:import-masters --exact-steps --apply \
 *        --step=surveys --step=survey_details --step=quotation_surveys \
 *        --step=quotation_rooms --step=quotation_details
 *   4. php artisan catalyst:repair-collapsed-surveys --phase=relink --apply
 *
 * Langkah 3 sengaja TIDAK menyertakan step `quotations`: step itu menimpa field SQ lain
 * (status, total, TOP) dengan nilai Catalyst. Kolom quotations.survey_id diperbaiki
 * tersendiri di fase relink.
 */
class RepairCatalystCollapsedSurveys extends Command
{
    protected $signature = 'catalyst:repair-collapsed-surveys
                            {--phase=report : report | purge | relink}
                            {--apply : Tulis perubahan. Tanpa ini semuanya hanya simulasi.}
                            {--limit=15 : Jumlah baris contoh yang ditampilkan}';

    protected $description = 'Perbaiki survei Catalyst yang menyatu jadi satu baris akibat tabrakan nomor dokumen (dry-run secara default)';

    private const SURVEY_SOURCE_TABLE = 'MKTQuotationRental_survey';

    public function handle(): int
    {
        $phase = (string) $this->option('phase');
        $apply = (bool) $this->option('apply');

        if (!$apply) {
            $this->warn('DRY-RUN. Tidak ada yang ditulis. Tambahkan --apply untuk menjalankan.');
        }

        return match ($phase) {
            'report' => $this->report(),
            'purge' => $this->purge($apply),
            'relink' => $this->relink($apply),
            default => $this->invalidPhase($phase),
        };
    }

    private function invalidPhase(string $phase): int
    {
        $this->error("Phase tidak dikenal: {$phase}. Pilih report, purge, atau relink.");

        return self::FAILURE;
    }

    /**
     * Survei yang jadi tujuan lebih dari satu source key - itu definisi "menyatu".
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function collapsedSurveys()
    {
        return DB::table('source_import_maps')
            ->where('source_system', 'catalyst')
            ->where('source_table', self::SURVEY_SOURCE_TABLE)
            ->where('target_table', 'surveys')
            ->select('target_id', DB::raw('COUNT(*) as n'))
            ->groupBy('target_id')
            ->having('n', '>', 1)
            ->orderByDesc('n')
            ->get();
    }

    private function report(): int
    {
        $collapsed = $this->collapsedSurveys();

        if ($collapsed->isEmpty()) {
            $this->info('Tidak ada survei yang menyatu. Tidak ada yang perlu diperbaiki.');

            return self::SUCCESS;
        }

        $ids = $collapsed->pluck('target_id')->all();
        $extra = $collapsed->sum('n') - $collapsed->count();

        $this->table(['Survei', 'Jumlah source key'], $collapsed->map(fn ($r) => [
            $r->target_id . ' (' . (DB::table('surveys')->where('id', $r->target_id)->value('survey_number') ?: '?') . ')',
            $r->n,
        ])->all());

        $this->line("Survei yang hilang dan perlu dibentuk ulang: {$extra}");
        $this->newLine();

        $rows = [];
        foreach (['quotations', 'quotation_surveys', 'survey_details', 'quotation_details', 'contract_surveys'] as $table) {
            $rows[] = [$table, DB::table($table)->whereIn('survey_id', $ids)->count()];
        }
        $this->table(['Tabel yang menunjuk survei tersebut', 'Baris'], $rows);

        $this->newLine();
        $this->line('Langkah berikutnya: --phase=purge --apply, lalu re-run step import, lalu --phase=relink --apply.');
        $this->line('(Lihat docblock command ini untuk perintah lengkapnya.)');

        return self::SUCCESS;
    }

    /**
     * Buang baris peta yang tercemar, sisakan satu pemilik sah per survei.
     *
     * Pemilik sah = source key yang kode gedungnya benar-benar cocok dengan building_id
     * survei itu sekarang. Kalau tidak ada yang cocok, sisakan source key paling awal -
     * yang penting satu survei tetap punya pemilik supaya nomornya tidak ikut hilang.
     */
    private function purge(bool $apply): int
    {
        $collapsed = $this->collapsedSurveys();

        if ($collapsed->isEmpty()) {
            $this->info('Tidak ada baris peta yang perlu dibuang.');

            return self::SUCCESS;
        }

        $deleted = 0;
        $preview = [];

        foreach ($collapsed as $row) {
            $surveyId = (int) $row->target_id;
            $buildingId = DB::table('surveys')->where('id', $surveyId)->value('building_id');

            $keys = DB::table('source_import_maps')
                ->where('source_system', 'catalyst')
                ->where('source_table', self::SURVEY_SOURCE_TABLE)
                ->where('target_table', 'surveys')
                ->where('target_id', $surveyId)
                ->orderBy('id')
                ->get(['id', 'source_key']);

            $keeper = $this->resolveKeeper($keys, $buildingId);

            foreach ($keys as $key) {
                if ((int) $key->id === $keeper) {
                    continue;
                }

                if (count($preview) < (int) $this->option('limit')) {
                    $preview[] = [$surveyId, $key->source_key];
                }

                if ($apply) {
                    DB::table('source_import_maps')->where('id', $key->id)->delete();
                }

                $deleted++;
            }

            $keeperKey = $keys->firstWhere('id', $keeper)->source_key ?? '?';
            $this->line("Survei {$surveyId}: menyisakan pemilik <info>{$keeperKey}</info>");
        }

        if ($preview) {
            $this->newLine();
            $this->table(['Survei', 'Source key yang dibuang (contoh)'], $preview);
        }

        $this->newLine();
        $this->line(($apply ? 'Dibuang: ' : 'Akan dibuang: ') . $deleted . ' baris peta.');
        $this->line('Berikutnya: jalankan ulang step surveys + turunannya, baru --phase=relink.');

        return self::SUCCESS;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $keys
     */
    private function resolveKeeper($keys, $buildingId): int
    {
        if ($buildingId) {
            foreach ($keys as $key) {
                $buildingCode = trim((string) (explode('||', $key->source_key)[1] ?? ''));

                if ($buildingCode === '') {
                    continue;
                }

                $mapped = DB::table('source_import_maps')
                    ->where('source_system', 'catalyst')
                    ->where('source_table', 'MsBuilding')
                    ->where('target_table', 'buildings')
                    ->where('source_key', $buildingCode)
                    ->value('target_id');

                if ((int) $mapped === (int) $buildingId) {
                    return (int) $key->id;
                }
            }
        }

        return (int) $keys->first()->id;
    }

    /**
     * Arahkan quotations.survey_id ke survei milik SQ-nya sendiri.
     *
     * Hanya kolom ini yang disentuh. Tabel turunan lain (quotation_surveys, survey_details,
     * quotation_details) sudah benar setelah step-nya dijalankan ulang, dan step-step itu
     * tidak menulis apa pun ke header SQ.
     */
    private function relink(bool $apply): int
    {
        $suspects = DB::table('quotations')
            ->whereNotNull('survey_id')
            ->whereNotNull('quotation_number')
            ->get(['id', 'quotation_number', 'survey_id']);

        $fixed = 0;
        $ambiguous = [];
        $preview = [];

        foreach ($suspects as $q) {
            $candidates = DB::table('source_import_maps')
                ->where('source_system', 'catalyst')
                ->where('source_table', self::SURVEY_SOURCE_TABLE)
                ->where('target_table', 'surveys')
                ->where('source_key', 'like', $q->quotation_number . '||%')
                ->pluck('target_id')
                ->unique()
                ->values();

            if ($candidates->isEmpty() || $candidates->contains((int) $q->survey_id)) {
                continue; // sudah menunjuk salah satu survei miliknya sendiri
            }

            if ($candidates->count() > 1) {
                // SQ multi-gedung: survei utamanya ditentukan urutan baris sumber, tidak bisa
                // disimpulkan dari peta saja. Serahkan ke step `quotations` kalau memang perlu.
                $ambiguous[] = [$q->quotation_number, $q->survey_id, $candidates->implode(', ')];

                continue;
            }

            $target = (int) $candidates->first();

            if (count($preview) < (int) $this->option('limit')) {
                $preview[] = [$q->quotation_number, $q->survey_id, $target];
            }

            if ($apply) {
                DB::table('quotations')->where('id', $q->id)->update([
                    'survey_id' => $target,
                    'updated_at' => now(),
                ]);
            }

            $fixed++;
        }

        if ($preview) {
            $this->table(['SQ', 'survey_id lama', 'survey_id baru'], $preview);
        }

        $this->line(($apply ? 'Diperbaiki: ' : 'Akan diperbaiki: ') . $fixed . ' SQ.');

        if ($ambiguous) {
            $this->newLine();
            $this->warn(count($ambiguous) . ' SQ multi-gedung dilewati - survei utamanya tidak bisa ditentukan dari peta saja:');
            $this->table(['SQ', 'survey_id sekarang', 'kandidat'], array_slice($ambiguous, 0, (int) $this->option('limit')));
            $this->line('Untuk ini jalankan: php artisan catalyst:import-masters --step=quotations --exact-steps --apply');
            $this->line('(ingat: step itu menimpa field SQ lain dengan nilai Catalyst)');
        }

        return self::SUCCESS;
    }
}
