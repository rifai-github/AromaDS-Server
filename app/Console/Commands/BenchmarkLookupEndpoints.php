<?php

namespace App\Console\Commands;

use App\Http\Controllers\Company\CustomerController;
use App\Http\Controllers\System\ProvinceController;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BenchmarkLookupEndpoints extends Command
{
    protected $signature = 'perf:lookups-benchmark {--iterations=20 : Number of repeated calls per endpoint}';

    protected $description = 'Benchmark repeated lookup endpoints used by dropdowns and wizards';

    public function handle(): int
    {
        $iterations = max(2, (int) $this->option('iterations'));
        $targets = $this->resolveTargets();

        if (!$targets) {
            $this->error('Unable to resolve sample customer/location data for benchmark.');
            return self::FAILURE;
        }

        $customerController = app(CustomerController::class);
        $provinceController = app(ProvinceController::class);

        $results = [];

        $results['customer_buildings'] = $this->benchmarkEndpoint(
            clearKeys: [
                "customer:{$targets['customer_id']}:api-buildings:v1",
            ],
            iterations: $iterations,
            callback: fn () => $customerController->getBuildings($targets['customer_id'])
        );

        $results['customer_contacts'] = $this->benchmarkEndpoint(
            clearKeys: [
                "customer:{$targets['customer_id']}:api-contacts:v1",
            ],
            iterations: $iterations,
            callback: fn () => $customerController->getContacts($targets['customer_id'])
        );

        $results['location_provinces'] = $this->benchmarkEndpoint(
            clearKeys: ['location:provinces:v1'],
            iterations: $iterations,
            callback: fn () => $provinceController->getProvinces(new Request())
        );

        $results['location_cities'] = $this->benchmarkEndpoint(
            clearKeys: [
                "location:cities:province:{$targets['province_id']}:v1",
            ],
            iterations: $iterations,
            callback: fn () => $provinceController->getCities(new Request(['province_id' => $targets['province_id']]))
        );

        $results['location_districts'] = $this->benchmarkEndpoint(
            clearKeys: [
                "location:districts:city:{$targets['city_id']}:v1",
            ],
            iterations: $iterations,
            callback: fn () => $provinceController->getDistricts(new Request(['city_id' => $targets['city_id']]))
        );

        $results['location_subdistricts'] = $this->benchmarkEndpoint(
            clearKeys: [
                "location:subdistricts:district:{$targets['district_id']}:v1",
            ],
            iterations: $iterations,
            callback: fn () => $provinceController->getSubdistricts(new Request(['district_id' => $targets['district_id']]))
        );

        $this->line(json_encode([
            'targets' => $targets,
            'iterations' => $iterations,
            'results' => $results,
        ], JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }

    protected function benchmarkEndpoint(array $clearKeys, int $iterations, callable $callback): array
    {
        foreach ($clearKeys as $key) {
            Cache::forget($key);
        }

        $cold = $this->measure($callback);

        $warmRuns = [];
        for ($i = 1; $i < $iterations; $i++) {
            $warmRuns[] = $this->measure($callback);
        }

        return [
            'cold' => $cold,
            'warm_avg_ms' => round(collect($warmRuns)->avg('elapsed_ms') ?? 0, 2),
            'warm_avg_query_count' => round(collect($warmRuns)->avg('query_count') ?? 0, 2),
            'warm_avg_query_time_ms' => round(collect($warmRuns)->avg('query_time_ms') ?? 0, 2),
        ];
    }

    protected function measure(callable $callback): array
    {
        $queryCount = 0;
        $queryTimeMs = 0.0;

        DB::flushQueryLog();
        DB::enableQueryLog();

        $listener = DB::listen(function ($query) use (&$queryCount, &$queryTimeMs) {
            $queryCount++;
            $queryTimeMs += $query->time;
        });

        $startedAt = hrtime(true);
        $response = $callback();
        $elapsedMs = (hrtime(true) - $startedAt) / 1_000_000;

        unset($listener);

        if (method_exists($response, 'getStatusCode')) {
            $response->getStatusCode();
        }

        return [
            'elapsed_ms' => round($elapsedMs, 2),
            'query_count' => $queryCount,
            'query_time_ms' => round($queryTimeMs, 2),
        ];
    }

    protected function resolveTargets(): ?array
    {
        $customerId = DB::table('building_customers')
            ->join('customers', 'customers.id', '=', 'building_customers.customer_id')
            ->where('building_customers.is_active', true)
            ->whereNull('customers.deleted_at')
            ->orderBy('building_customers.customer_id')
            ->value('building_customers.customer_id');

        if (!$customerId) {
            return null;
        }

        $locationRow = DB::table('cities')
            ->join('districts', 'districts.city_id', '=', 'cities.id')
            ->join('subdistricts', 'subdistricts.district_id', '=', 'districts.id')
            ->select([
                'cities.province_id',
                'cities.id as city_id',
                'districts.id as district_id',
            ])
            ->orderBy('cities.id')
            ->first();

        if (!$locationRow) {
            return null;
        }

        return [
            'customer_id' => (int) $customerId,
            'province_id' => (int) $locationRow->province_id,
            'city_id' => (int) $locationRow->city_id,
            'district_id' => (int) $locationRow->district_id,
        ];
    }
}
