<?php

namespace App\Console\Commands;

use App\Models\Survey;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * UAT 18 Nov 2026: halaman detail survey dulu mengirim balik nilai {{ }} (sudah di-escape HTML)
 * saat Save Draft / Finalize, sehingga "l'arc en ciel" tersimpan sebagai "l&#039;arc en ciel"
 * dan makin berlapis setiap disimpan ulang. Command ini mengembalikan teks aslinya.
 *
 * Default dry-run; tulis ke database hanya dengan --apply.
 */
class DecodeSurveyHtmlEntities extends Command
{
    protected $signature = 'surveys:decode-html-entities {--apply : Simpan perubahan ke database} {--limit=20 : Jumlah contoh yang ditampilkan}';

    protected $description = 'Kembalikan teks survey yang tersimpan ter-escape HTML (&#039; &quot; &amp;) ke teks aslinya';

    private const COLUMNS = [
        'company_name', 'contact_person', 'email', 'position', 'building_name',
        'address_1', 'address_2', 'survey_location', 'building_location_detail',
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $examples = [];
        $rowsChanged = 0;
        $valuesChanged = 0;

        $query = DB::table('surveys')->select(array_merge(['id', 'survey_number'], self::COLUMNS))
            ->where(function ($q) {
                foreach (self::COLUMNS as $column) {
                    $q->orWhere($column, 'like', '%&#%')
                        ->orWhere($column, 'like', '%&quot;%')
                        ->orWhere($column, 'like', '%&amp;%')
                        ->orWhere($column, 'like', '%&apos;%');
                }
            })
            ->orderBy('id');

        $query->chunkById(500, function ($rows) use ($apply, &$examples, &$rowsChanged, &$valuesChanged) {
            foreach ($rows as $row) {
                $updates = [];
                foreach (self::COLUMNS as $column) {
                    $original = $row->{$column};
                    if ($original === null) {
                        continue;
                    }
                    $decoded = Survey::decodeStoredHtmlEntities($original);
                    if ($decoded !== $original) {
                        $updates[$column] = $decoded;
                        if (count($examples) < (int) $this->option('limit')) {
                            $examples[] = [$row->id, $row->survey_number, $column, $original, $decoded];
                        }
                    }
                }

                if ($updates) {
                    $rowsChanged++;
                    $valuesChanged += count($updates);
                    if ($apply) {
                        DB::table('surveys')->where('id', $row->id)->update($updates);
                    }
                }
            }
        });

        if ($examples) {
            $this->table(['id', 'survey_number', 'kolom', 'tersimpan', 'menjadi'], $examples);
        }

        $this->info(($apply ? 'Diperbarui' : 'Akan diperbarui (dry-run)').": {$rowsChanged} survey, {$valuesChanged} nilai.");
        if (! $apply && $rowsChanged > 0) {
            $this->line('Jalankan ulang dengan --apply untuk menyimpan.');
        }

        return self::SUCCESS;
    }
}
