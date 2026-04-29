<?php

namespace App\Console\Commands;

use App\Models\Survey;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BackfillCatalystSurveyDisplayFields extends Command
{
    protected $signature = 'catalyst:backfill-survey-display-fields
                            {--apply : Persist changes to surveys table}';

    protected $description = 'Backfill survey display fields from migrated customer/building relations.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $stats = [
            'processed' => 0,
            'updated' => 0,
            'skipped' => 0,
        ];

        Survey::with([
            'customer',
            'building.province',
            'building.city',
            'building.district',
            'building.subdistrict',
        ])->orderBy('id')->chunkById(250, function ($surveys) use ($apply, &$stats) {
            foreach ($surveys as $survey) {
                $stats['processed']++;

                $building = $survey->building;
                $customer = $survey->customer;

                if (!$building && !$customer) {
                    $stats['skipped']++;
                    continue;
                }

                $payload = [
                    'company_name' => $this->clean($customer?->name) ?: $this->clean($survey->company_name),
                    'customer_type' => $this->clean($customer?->company_type) ?: $this->clean($customer?->customer_type) ?: $this->clean($survey->customer_type),
                    'building_name' => $this->clean($building?->nama_gedung) ?: $this->clean($building?->name) ?: $this->clean($survey->building_name),
                    'address_1' => $this->clean($building?->alamat_1) ?: $this->clean($building?->address) ?: $this->clean($survey->address_1),
                    'address_2' => $this->clean($building?->alamat_2) ?: $this->clean($survey->address_2),
                    'province' => $this->clean($building?->province?->name) ?: $this->clean($survey->province),
                    'city' => $this->clean($building?->city?->name) ?: $this->extractLabelValue($building?->notes, 'CityName') ?: $this->extractLabelValue($building?->notes, 'AreaCity') ?: $this->clean($survey->city),
                    'district' => $this->clean($building?->district?->name) ?: $this->clean($survey->district),
                    'village' => $this->clean($building?->subdistrict?->name) ?: $this->clean($survey->village),
                    'postal_code' => $this->clean($building?->postal_code) ?: $this->clean($building?->kode_pos) ?: $this->clean($survey->postal_code),
                    'updated_at' => now(),
                ];

                $changed = false;
                foreach ($payload as $column => $value) {
                    if ($column === 'updated_at') {
                        continue;
                    }

                    if (($survey->{$column} ?? null) !== $value && $value !== null) {
                        $changed = true;
                        break;
                    }
                }

                if (!$changed) {
                    $stats['skipped']++;
                    continue;
                }

                $stats['updated']++;

                if ($apply) {
                    DB::table('surveys')->where('id', $survey->id)->update($payload);
                }
            }
        });

        $this->table(['Metric', 'Value'], [
            ['Mode', $apply ? 'apply' : 'dry-run'],
            ['Processed', $stats['processed']],
            ['Updated', $stats['updated']],
            ['Skipped', $stats['skipped']],
        ]);

        return self::SUCCESS;
    }

    private function clean($value): ?string
    {
        $value = trim((string) $value);

        if ($value === '' || $value === '-' || Str::lower($value) === 'null') {
            return null;
        }

        return $value;
    }

    private function extractLabelValue($text, string $label): ?string
    {
        $text = trim((string) $text);
        if ($text === '') {
            return null;
        }

        foreach (preg_split('/\r\n|\r|\n/', $text) as $line) {
            if (str_starts_with($line, $label . ':')) {
                return $this->clean(trim(substr($line, strlen($label) + 1)));
            }
        }

        return null;
    }
}
