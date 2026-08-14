<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BackfillBuildingCityMapping extends Command
{
    protected $signature = 'catalyst:backfill-building-city
                            {--apply : Apply the repair (default is dry-run)}
                            {--limit=5000 : Max buildings to scan}
                            {--name= : Only scan buildings whose name/nama_gedung matches this (LIKE %value%), ignoring the missing/orphaned city/district filter}';

    protected $description = 'Backfill buildings.city_id/province_id and buildings.district_id for Catalyst-imported buildings whose city/district failed to resolve during import, using the CityName/AreaCity (notes) and AreaServiceName (description) already captured during import';

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

        $districtLookup = $this->loadTargetDistrictLookup();
        if (empty($districtLookup)) {
            $this->warn('Local "districts" table is empty - district backfill will be skipped, city backfill still runs.');
        }

        // City/District both use SoftDeletes: a building's city_id/district_id can
        // point at a row that still physically exists but is soft-deleted, which
        // Building::city()/district() (a normal Eloquent belongsTo) will not
        // resolve. Only count non-deleted rows as "valid" so those buildings are
        // picked up for repair too.
        $validCityIds = DB::table('cities')->whereNull('deleted_at')->pluck('id')->all();
        $validCityIdSet = array_flip($validCityIds);

        $validDistrictIds = DB::table('districts')->whereNull('deleted_at')->pluck('id')->all();
        $validDistrictIdSet = array_flip($validDistrictIds);

        $nameFilter = trim((string) $this->option('name'));

        $buildingsQuery = DB::table('buildings');

        if ($nameFilter !== '') {
            $this->info('Name filter active ("' . $nameFilter . '") - scanning matching buildings regardless of current city/district state.');
            $buildingsQuery->where(function ($query) use ($nameFilter) {
                $query->where('name', 'like', '%' . $nameFilter . '%')
                    ->orWhere('nama_gedung', 'like', '%' . $nameFilter . '%');
            });
        } else {
            $buildingsQuery->where(function ($query) use ($validCityIds, $validDistrictIds) {
                $query->whereNull('city_id')
                    ->orWhereNotIn('city_id', $validCityIds)
                    ->orWhereNull('district_id');

                if (!empty($validDistrictIds)) {
                    $query->orWhereNotIn('district_id', $validDistrictIds);
                }
            });
        }

        $buildings = $buildingsQuery
            ->orderBy('id')
            ->limit(max((int) $this->option('limit'), 1))
            ->get(['id', 'name', 'nama_gedung', 'notes', 'description', 'city_id', 'district_id']);

        if ($buildings->isEmpty()) {
            $this->info($nameFilter !== '' ? 'No buildings matched that name.' : 'No buildings found with a missing/orphaned city_id or district_id.');

            return self::SUCCESS;
        }

        $rows = [];
        $cityPlans = 0;
        $citySkipped = 0;
        $cityAlreadyValid = 0;
        $districtPlans = 0;
        $districtSkipped = 0;
        $districtAlreadyValid = 0;
        $updates = [];

        foreach ($buildings as $building) {
            $buildingName = $building->name ?: $building->nama_gedung;

            $cityValid = $building->city_id !== null && isset($validCityIdSet[(int) $building->city_id]);
            $effectiveCityId = $cityValid ? (int) $building->city_id : null;

            if ($cityValid) {
                $cityAlreadyValid++;
                $cityLabel = 'OK (city_id=' . $building->city_id . ')';
            } else {
                $priorState = $building->city_id === null ? 'was null' : 'orphaned/soft-deleted';
                $cityName = $this->extractLabelValue($building->notes, 'CityName')
                    ?: $this->extractLabelValue($building->notes, 'AreaCity');

                if (!$cityName) {
                    $citySkipped++;
                    $cityLabel = "SKIP ($priorState; no CityName/AreaCity in notes)";
                } else {
                    $match = $cityLookup[$this->normalizePlace($cityName)] ?? null;

                    if (!$match) {
                        $citySkipped++;
                        $candidates = $this->findCandidateCities($cityName);
                        $cityLabel = "SKIP ($priorState; \"$cityName\" no match" . ($candidates ? ', close: ' . implode(', ', $candidates) : '') . ')';
                    } else {
                        $cityPlans++;
                        $effectiveCityId = $match['id'];
                        $cityLabel = ($apply ? 'FIX ' : 'PLAN ') . "\"$cityName\" -> \"{$match['name']}\"";
                        $updates[$building->id]['city_id'] = $match['id'];
                        $updates[$building->id]['province_id'] = $match['province_id'];
                    }
                }
            }

            $districtValid = $building->district_id !== null && isset($validDistrictIdSet[(int) $building->district_id]);

            if ($districtValid) {
                $districtAlreadyValid++;
                $districtLabel = 'OK (district_id=' . $building->district_id . ')';
            } elseif (!$effectiveCityId) {
                $districtSkipped++;
                $districtLabel = 'SKIP (city unresolved, cannot scope district match)';
            } elseif (empty($districtLookup)) {
                $districtSkipped++;
                $districtLabel = 'SKIP (local districts table empty)';
            } else {
                $priorState = $building->district_id === null ? 'was null' : 'orphaned/soft-deleted';
                $areaServiceName = $this->extractLabelValue($building->description, 'AreaServiceName');

                if (!$areaServiceName) {
                    $districtSkipped++;
                    $districtLabel = "SKIP ($priorState; no AreaServiceName in description)";
                } else {
                    $key = $effectiveCityId . '|' . $this->normalizePlace($areaServiceName);
                    $match = $districtLookup[$key] ?? null;

                    if (!$match) {
                        $districtSkipped++;
                        $districtLabel = "SKIP ($priorState; \"$areaServiceName\" no match under matched city)";
                    } else {
                        $districtPlans++;
                        $districtLabel = ($apply ? 'FIX ' : 'PLAN ') . "\"$areaServiceName\" -> \"{$match['name']}\"";
                        $updates[$building->id]['district_id'] = $match['id'];
                    }
                }
            }

            $overallStatus = 'OK';
            if (str_starts_with($cityLabel, 'FIX') || str_starts_with($districtLabel, 'FIX')) {
                $overallStatus = 'FIX';
            } elseif (str_starts_with($cityLabel, 'PLAN') || str_starts_with($districtLabel, 'PLAN')) {
                $overallStatus = 'PLAN';
            } elseif (str_starts_with($cityLabel, 'SKIP') || str_starts_with($districtLabel, 'SKIP')) {
                $overallStatus = 'SKIP';
            }

            $rows[] = [$overallStatus, $building->id, $buildingName, $cityLabel, $districtLabel];
        }

        $appliedBuildings = 0;
        if ($apply) {
            foreach ($updates as $buildingId => $fields) {
                $fields['updated_at'] = now();
                $fields['updated_by'] = auth()->id();
                DB::table('buildings')->where('id', $buildingId)->update($fields);
                $appliedBuildings++;
            }
        }

        $this->table(
            ['Status', 'Building ID', 'Building', 'City', 'District'],
            $rows
        );

        $this->line('Scanned buildings      : ' . $buildings->count());
        $this->line('City already valid      : ' . $cityAlreadyValid);
        $this->line('City repair plans       : ' . $cityPlans);
        $this->line('City skipped            : ' . $citySkipped);
        $this->line('District already valid  : ' . $districtAlreadyValid);
        $this->line('District repair plans   : ' . $districtPlans);
        $this->line('District skipped        : ' . $districtSkipped);
        $this->line('Buildings updated       : ' . ($apply ? $appliedBuildings : 'dry-run'));

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
     * Districts are scoped per city (districts.city_id NOT NULL) since the same
     * kecamatan name can occur in more than one city. Key is "cityId|normalizedName".
     */
    private function loadTargetDistrictLookup(): array
    {
        $lookup = [];

        $rows = DB::table('districts')
            ->whereNull('deleted_at')
            ->select('id', 'city_id', 'name')
            ->get();

        foreach ($rows as $row) {
            $key = $row->city_id . '|' . $this->normalizePlace($row->name);
            if ($key && !isset($lookup[$key])) {
                $lookup[$key] = [
                    'id' => (int) $row->id,
                    'name' => $row->name,
                ];
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
