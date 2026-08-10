<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Import Catalyst mengisi contracts.quotation_id lewat kolom sumber `SqNo`.
 * Ketika kolom itu kosong, resolveContractQuotationId() menyerah dan kontrak
 * tersimpan tanpa tautan ke SQ — dan karena step contract_surveys menurunkan
 * datanya dari contracts.quotation_id, tautan ke survey ikut hilang.
 *
 * Command ini menyambung ulang tautannya dari data yang sudah ada di sistem
 * baru, dan hanya kalau hasilnya tidak ambigu.
 */
class RelinkContractQuotationSurvey extends Command
{
    protected $signature = 'contracts:relink-quotation-survey
                            {--contract= : Batasi ke satu contract_number atau ID}
                            {--limit=0 : Batasi jumlah kontrak yang diproses (0 = semua)}
                            {--surveys-only : Lewati penautan SQ, hanya bangun ulang contract_surveys}
                            {--apply : Tulis perubahan (default dry-run)}';

    protected $description = 'Sambung ulang contracts.quotation_id dan contract_surveys yang kosong akibat SqNo hilang saat import Catalyst';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        if (! $apply) {
            $this->info('DRY RUN. Tidak ada perubahan yang ditulis. Tambahkan --apply untuk menyimpan.');
        }

        $linked = 0;
        $ambiguous = 0;
        $unmatched = 0;
        $plannedLinks = [];

        if (! $this->option('surveys-only')) {
            [$linked, $ambiguous, $unmatched, $plannedLinks] = $this->relinkQuotations($apply);
        }

        // Saat dry-run, contracts.quotation_id belum berubah. Bawa tautan yang
        // baru direncanakan supaya angka survey yang dilaporkan sama dengan
        // hasil --apply nantinya.
        $surveys = $this->rebuildContractSurveys($apply, $apply ? [] : $plannedLinks);

        $this->newLine();
        $this->table(['Hasil', 'Jumlah'], [
            ['Kontrak tertaut ke SQ', $linked],
            ['Ambigu (>1 kandidat, dilewati)', $ambiguous],
            ['Tanpa kandidat', $unmatched],
            ['Baris contract_surveys dibuat', $surveys],
        ]);

        if (! $apply) {
            $this->warn('Dry-run selesai. Jalankan ulang dengan --apply untuk menerapkan.');
        }

        return self::SUCCESS;
    }

    /**
     * @return array{0:int,1:int,2:int,3:array<int,int>}
     */
    private function relinkQuotations(bool $apply): array
    {
        $query = DB::table('contracts')
            ->whereNull('deleted_at')
            ->whereNull('quotation_id')
            ->whereNotNull('contract_number')
            ->orderBy('id');

        $this->applyContractFilter($query);

        if ($limit = (int) $this->option('limit')) {
            $query->limit($limit);
        }

        $contracts = $query->get(['id', 'contract_number', 'customer_id']);

        if ($contracts->isEmpty()) {
            $this->info('Tidak ada kontrak tanpa tautan SQ yang cocok dengan filter.');

            return [0, 0, 0, []];
        }

        $this->info("Memeriksa {$contracts->count()} kontrak tanpa tautan SQ...");

        $linked = 0;
        $ambiguous = 0;
        $unmatched = 0;
        $samples = [];
        $plannedLinks = [];

        foreach ($contracts as $contract) {
            $match = $this->resolveQuotation($contract);

            if ($match['status'] === 'linked') {
                $linked++;
                $plannedLinks[(int) $contract->id] = $match['quotation_id'];
                if (count($samples) < 10) {
                    $samples[] = [
                        $contract->contract_number,
                        $match['quotation_number'],
                        $match['strategy'],
                    ];
                }

                if ($apply) {
                    DB::table('contracts')
                        ->where('id', $contract->id)
                        ->whereNull('quotation_id')
                        ->update([
                            'quotation_id' => $match['quotation_id'],
                            'updated_at' => now(),
                        ]);
                }
            } elseif ($match['status'] === 'ambiguous') {
                $ambiguous++;
            } else {
                $unmatched++;
            }
        }

        if ($samples) {
            $this->newLine();
            $this->line('Contoh tautan yang '.($apply ? 'dibuat' : 'akan dibuat').':');
            $this->table(['Contract', 'Quotation', 'Strategi'], $samples);
        }

        return [$linked, $ambiguous, $unmatched, $plannedLinks];
    }

    /**
     * @return array{status:string,quotation_id?:int,quotation_number?:string,strategy?:string}
     */
    private function resolveQuotation(object $contract): array
    {
        // Strategi 1 — nomor kontrak memang sebuah nomor SQ. Terjadi pada data
        // lama yang dokumen kontraknya tidak pernah dinomori ulang.
        $number = trim((string) $contract->contract_number);
        $candidates = array_values(array_unique(array_filter([
            $number,
            preg_replace('/X$/', '', $number),
        ])));

        foreach ($candidates as $candidate) {
            $exact = DB::table('quotations')
                ->whereNull('deleted_at')
                ->where('quotation_number', $candidate)
                ->when($contract->customer_id, fn ($q) => $q->where('customer_id', $contract->customer_id))
                ->get(['id', 'quotation_number']);

            if ($exact->count() === 1) {
                return [
                    'status' => 'linked',
                    'quotation_id' => (int) $exact[0]->id,
                    'quotation_number' => $exact[0]->quotation_number,
                    'strategy' => 'nomor sama',
                ];
            }
        }

        // Strategi 2 — customer + cabang + periode yang sama, dan hanya ada satu
        // kandidat. Kalau lebih dari satu, biarkan manusia yang memutuskan.
        $branch = $this->branchPrefix($number);
        $period = $this->documentPeriod($number);

        if (! $contract->customer_id || ! $branch || ! $period) {
            return ['status' => 'unmatched'];
        }

        $matches = DB::table('quotations')
            ->whereNull('deleted_at')
            ->where('customer_id', $contract->customer_id)
            ->where('quotation_number', 'like', $branch.'-%/'.$period.'/%')
            ->get(['id', 'quotation_number']);

        if ($matches->count() === 1) {
            return [
                'status' => 'linked',
                'quotation_id' => (int) $matches[0]->id,
                'quotation_number' => $matches[0]->quotation_number,
                'strategy' => 'customer+cabang+periode',
            ];
        }

        return ['status' => $matches->count() > 1 ? 'ambiguous' : 'unmatched'];
    }

    /**
     * Bangun ulang contract_surveys dari quotation_surveys — logika yang sama
     * dengan step contract_surveys di importer Catalyst.
     */
    /**
     * @param  array<int,int>  $plannedLinks  contract_id => quotation_id yang belum tersimpan (dry-run)
     */
    private function rebuildContractSurveys(bool $apply, array $plannedLinks = []): int
    {
        $created = 0;

        $query = DB::table('contracts')
            ->whereNull('deleted_at')
            ->whereNotNull('quotation_id')
            ->orderBy('id');

        $this->applyContractFilter($query);

        $query->select(['id', 'quotation_id'])->chunkById(500, function ($contracts) use ($apply, &$created, &$plannedLinks) {
            foreach ($contracts as $contract) {
                unset($plannedLinks[(int) $contract->id]);
                $created += $this->syncSurveysForContract((int) $contract->id, (int) $contract->quotation_id, $apply);
            }
        });

        foreach ($plannedLinks as $contractId => $quotationId) {
            $created += $this->syncSurveysForContract($contractId, $quotationId, $apply);
        }

        return $created;
    }

    private function syncSurveysForContract(int $contractId, int $quotationId, bool $apply): int
    {
        $surveys = DB::table('quotation_surveys')
            ->where('quotation_id', $quotationId)
            ->orderBy('sort_order')
            ->get(['survey_id', 'sort_order']);

        $created = 0;

        foreach ($surveys as $survey) {
            $exists = DB::table('contract_surveys')
                ->where('contract_id', $contractId)
                ->where('survey_id', $survey->survey_id)
                ->exists();

            if ($exists) {
                continue;
            }

            $created++;

            if ($apply) {
                DB::table('contract_surveys')->insert([
                    'contract_id' => $contractId,
                    'survey_id' => $survey->survey_id,
                    'added_at' => now(),
                    'sort_order' => $survey->sort_order ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return $created;
    }

    private function applyContractFilter($query): void
    {
        $filter = $this->option('contract');

        if (! $filter) {
            return;
        }

        if (ctype_digit((string) $filter)) {
            $query->where('id', (int) $filter);

            return;
        }

        $query->where('contract_number', $filter);
    }

    /**
     * "SMG-AG/25-04/0013" => "SMG"
     */
    private function branchPrefix(string $documentNumber): ?string
    {
        return preg_match('/^([A-Z]+)-/', $documentNumber, $m) ? $m[1] : null;
    }

    /**
     * "SMG-AG/25-04/0013" => "25-04"
     */
    private function documentPeriod(string $documentNumber): ?string
    {
        return preg_match('#/(\d{2}-\d{2})/#', $documentNumber, $m) ? $m[1] : null;
    }
}
