<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BackfillBuildingCityMapping extends Command
{
    protected $signature = 'catalyst:backfill-building-city
                            {--apply : Apply the repair (default is dry-run)}
                            {--limit=1000 : Max buildings to scan}';

    protected $description = 'Backfill buildings.city_id/province_id for Catalyst-imported buildings whose city failed to resolve during import, using the CityName/AreaCity already captured in buildings.notes';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        if (!$apply) {
            $this->info('DRY RUN mode. No database changes will be made. Re-run with --apply to persist repairs.');
        }

        $cityLookup = $this->loadTargetCityLookup();
        if (empty($cityLookup)) {
            $this->error('Local "cities" table is empty or missing. Nothing to match against.');

            return self::FAILURE;
        }

        // City uses SoftDeletes: a building's city_id can point at a row that
        // still physically exists but is soft-deleted, which Building::city()
        // (a normal Eloquent belongsTo) will not resolve. Only count non-deleted
        // rows as "valid" so those buildings are picked up for repair too.
        $validCityIds = DB::table('cities')->whereNull('deleted_at')->pluck('id')->all();

        $buildings = DB::table('buildings')
            ->where(function ($query) use ($validCityIds) {
                $query->whereNull('city_id')->orWhereNotIn('city_id', $validCityIds);
            })
            ->orderBy('id')
            ->limit(max((int) $this->option('limit'), 1))
            ->get(['id', 'name', 'nama_gedung', 'notes', 'city_id']);

        if ($buildings->isEmpty()) {
            $this->info('No buildings found with a missing or orphaned city_id.');

            return self::SUCCESS;
        }

        $rows = [];
        $plans = [];
        $skipped = 0;

        foreach ($buildings as $building) {
            $priorState = $building->city_id === null ? 'city_id was null' : 'city_id=' . $building->city_id . ' is orphaned (no matching cities row)';

            $cityName = $this->extractLabelValue($building->notes, 'CityName')
                ?: $this->extractLabelValue($building->notes, 'AreaCity');

            if (!$cityName) {
                $skipped++;
                $rows[] = ['SKIP', $building->id, $building->name ?: $building->nama_gedung, '-', '-', $priorState . '; no CityName/AreaCity captured in notes - needs re-import from Catalyst source'];
                continue;
            }

            $match = $cityLookup[$this->normalizePlace($cityName)] ?? null;

            if (!$match) {
                $skipped++;
                $candidates = $this->findCandidateCities($cityName);
                $note = $priorState . '; ' . ($candidates ? 'no exact match, close names: ' . implode(', ', $candidates) : 'no matching local city at all');
                $rows[] = ['SKIP', $building->id, $building->name ?: $building->nama_gedung, $cityName, '-', $note];
                continue;
            }

            $plans[] = ['building' => $building, 'match' => $match];
            $rows[] = [
                $apply ? 'FIX' : 'PLAN',
                $building->id,
                $building->name ?: $building->nama_gedung,
                $cityName,
                $match['name'],
                'matched by normalized name',
            ];
        }

        $applied = 0;
        if ($apply) {
            foreach ($plans as $plan) {
                DB::table('buildings')->where('id', $plan['building']->id)->update([
                    'city_id' => $plan['match']['id'],
                    'province_id' => $plan['match']['province_id'],
                    'updated_at' => now(),
                    'updated_by' => auth()->id(),
                ]);
                $applied++;
            }
        }

        $this->table(
            ['Status', 'Building ID', 'Building', 'Source City', 'Matched City', 'Note'],
            $rows
        );

        $this->line('Scanned buildings: ' . $buildings->count());
        $this->line('Repair plans     : ' . count($plans));
        $this->line('Applied repairs  : ' . ($apply ? $applied : 'dry-run'));
        $this->line('Skipped          : ' . $skipped);

        if (!$apply) {
            $this->line('Dry run only. Re-run with --apply after reviewing PLAN rows.');
        }

        return self::SUCCESS;
    }

    private function findCandidateCities(string $cityName): array
    {
        $normalized = $this->normalizePlace($cityName);
        if (!$normalized) {
            return [];
        }

        $token = collect(explode(' ', $normalized))->sortByDesc(fn ($word) => strlen($word))->first();
        if (!$token || strlen($token) < 3) {
            return [];
        }

        return DB::table('cities')
            ->whereNull('deleted_at')
            ->where('name', 'like', '%' . $token . '%')
            ->limit(5)
            ->pluck('name')
            ->all();
    }

    private function loadTargetCityLookup(): array
    {
        $lookup = [];

        // Province also uses SoftDeletes: only carry province_id through when
        // that province row is not itself soft-deleted, otherwise
        // Building::province() would refuse to resolve it too.
        $rows = DB::table('cities')
            ->leftJoin('provinces', function ($join) {
                $join->on('provinces.id', '=', 'cities.province_id')
                    ->whereNull('provinces.deleted_at');
            })
            ->whereNull('cities.deleted_at')
            ->select('cities.id', 'cities.name', 'provinces.id as joined_province_id')
            ->get();

        foreach ($rows as $row) {
            $target = [
                'id' => (int) $row->id,
                'name' => $row->name,
                'province_id' => $row->joined_province_id ? (int) $row->joined_province_id : null,
            ];

            foreach ($this->cityLookupKeys($row->name) as $key) {
                if ($key && !isset($lookup[$key])) {
                    $lookup[$key] = $target;
                }
            }
        }

        return $lookup;
    }

    /**
     * Local city names sometimes carry a bracketed alias, e.g. "SURAKARTA (SOLO)"
     * or "BULUNGAN (BULONGAN)". Catalyst's MsCity/AreaService names are plain
     * ("Surakarta"), so register the full name, the part before the bracket, and
     * the alias inside the bracket as separate lookup keys.
     */
    private function cityLookupKeys(string $name): array
    {
        $keys = [$this->normalizePlace($name)];

        if (preg_match('/^(.*?)\(([^)]+)\)\s*$/', trim($name), $matches)) {
            $keys[] = $this->normalizePlace($matches[1]);
            $keys[] = $this->normalizePlace($matches[2]);
        }

        return array_values(array_filter(array_unique($keys)));
    }

    private function extractLabelValue(?string $text, string $label): ?string
    {
        $text = trim((string) $text);
        if ($text === '') {
            return null;
        }

        foreach (preg_split('/\r\n|\r|\n/', $text) as $line) {
            if (str_starts_with($line, $label . ':')) {
                $value = trim(substr($line, strlen($label) + 1));

                return $value === '' ? null : $value;
            }
        }

        return null;
    }

    private function normalizePlace(?string $value): ?string
    {
        $value = Str::ascii(Str::lower(trim((string) $value)));
        $value = preg_replace('/\b(kota|kabupaten|kab|city)\b/', ' ', $value);
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value);
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
