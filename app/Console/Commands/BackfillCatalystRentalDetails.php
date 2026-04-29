<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BackfillCatalystRentalDetails extends Command
{
    protected $signature = 'catalyst:backfill-rental-details {--rental= : Specific master_rentals.id or rental_code}';

    protected $description = 'Backfill rental_details and service_frequency_id for imported Catalyst master rentals using local MySQL data';

    private array $serviceFrequencyLookup = [];
    private array $productCategoryLookup = [];
    private array $productTypeLookup = [];
    private array $masterProductCategoryLookup = [];
    private array $masterProductTypeLookup = [];
    private array $masterProductBySkuLookup = [];
    private array $masterProductIdsByTypeLookup = [];
    private array $masterProductIdsByCategoryLookup = [];

    public function handle(): int
    {
        $this->loadLookups();

        $query = DB::table('master_rentals')
            ->join('source_import_maps', function ($join) {
                $join->on('source_import_maps.target_id', '=', 'master_rentals.id')
                    ->where('source_import_maps.source_system', '=', 'catalyst')
                    ->where('source_import_maps.source_table', '=', 'MsProduct')
                    ->where('source_import_maps.target_table', '=', 'master_rentals');
            })
            ->select('master_rentals.*')
            ->distinct();

        if ($rental = $this->option('rental')) {
            $query->where(function ($inner) use ($rental) {
                $inner->where('master_rentals.id', $rental)
                    ->orWhere('master_rentals.rental_code', $rental);
            });
        }

        $rentals = $query->get();

        $stats = [
            'rentals' => 0,
            'service_frequency_updated' => 0,
            'details_inserted' => 0,
            'details_updated' => 0,
            'materials_synced' => 0,
            'stale_details_pruned' => 0,
        ];

        foreach ($rentals as $rental) {
            $stats['rentals']++;

            $serviceFrequencyId = $this->resolveServiceFrequencyId($rental->service_frequency);
            if ($serviceFrequencyId && (int) ($rental->service_frequency_id ?? 0) !== $serviceFrequencyId) {
                DB::table('master_rentals')
                    ->where('id', $rental->id)
                    ->update([
                        'service_frequency_id' => $serviceFrequencyId,
                        'updated_at' => now(),
                    ]);
                $stats['service_frequency_updated']++;
            }

            $components = DB::table('rental_components')
                ->where('master_rental_id', $rental->id)
                ->where('is_active', true)
                ->orderBy('id')
                ->get();
            $currentDetailIds = [];

            foreach ($components as $component) {
                $materialKey = $this->extractMaterialKey($component->description ?? null);
                $masterProductId = $this->resolveDetailProductId($materialKey, $rental->rental_code, $component->component_name);
                $productCategoryId = $this->resolveDetailCategoryId($component->component_name, $masterProductId);
                $productTypeId = $this->resolveDetailProductTypeId($component->component_name);
                $selectedProductIds = $this->resolveDetailSelectedProductIds(
                    $rental->rental_code,
                    $component->component_name,
                    $masterProductId,
                    $productTypeId,
                    $productCategoryId
                );
                $itemType = $masterProductId
                    ? 'product'
                    : ($productTypeId ? 'product_type' : ($productCategoryId ? 'product_category' : 'product'));
                $itemId = $masterProductId ?: ($productTypeId ?: $productCategoryId);
                $frequencyMultiplier = max(1, (int) ($component->replacement_frequency_months ?? 1));
                $bomQty = (float) ($component->quantity ?? 1);

                $match = [
                    'master_rental_id' => $rental->id,
                    'product_category_id' => $productCategoryId,
                    'product_type_id' => $productTypeId,
                    'service_frequency_multiplier' => $frequencyMultiplier,
                    'bom_rental_qty' => $bomQty,
                ];

                $payload = [
                    'master_product_id' => $masterProductId,
                    'item_type' => $itemType,
                    'item_id' => $itemId,
                    'quantity' => $bomQty,
                    'bom_rental_qty' => $bomQty,
                    'auto_expand' => !$masterProductId && (bool) ($productTypeId || $productCategoryId),
                    'unit' => $component->unit ?: 'UNIT',
                    'updated_at' => now(),
                ];

                $existing = DB::table('rental_details')
                    ->where('master_rental_id', $rental->id)
                    ->where('product_category_id', $productCategoryId)
                    ->where('product_type_id', $productTypeId)
                    ->where('service_frequency_multiplier', $frequencyMultiplier)
                    ->where('bom_rental_qty', $bomQty)
                    ->first();

                if ($existing) {
                    DB::table('rental_details')->where('id', $existing->id)->update($payload);
                    $detailId = (int) $existing->id;
                    $stats['details_updated']++;
                } else {
                    $detailId = (int) DB::table('rental_details')->insertGetId(array_merge($match, $payload, [
                        'created_at' => now(),
                    ]));
                    $stats['details_inserted']++;
                }

                if ($detailId > 0) {
                    $currentDetailIds[] = $detailId;
                }

                if ($detailId > 0) {
                    $stats['materials_synced'] += $this->syncDetailMaterials($detailId, $selectedProductIds);
                }
            }

            $stats['stale_details_pruned'] += $this->pruneStaleImportedDetails($rental->id, $currentDetailIds);
        }

        $this->table(
            ['Metric', 'Value'],
            [
                ['Rentals', $stats['rentals']],
                ['Service Frequency Updated', $stats['service_frequency_updated']],
                ['Details Inserted', $stats['details_inserted']],
                ['Details Updated', $stats['details_updated']],
                ['Materials Synced', $stats['materials_synced']],
                ['Stale Details Pruned', $stats['stale_details_pruned']],
            ]
        );

        return self::SUCCESS;
    }

    private function loadLookups(): void
    {
        if (Schema::hasTable('rental_service_frequencies')) {
            foreach (DB::table('rental_service_frequencies')->whereNull('deleted_at')->get() as $row) {
                $months = (int) ($row->frequency_months ?? 0);
                $times = (int) ($row->frequency_times_per_month ?? 0);
                if ($months > 0 && $times > 0) {
                    $this->serviceFrequencyLookup[$months . 'x' . $times] = (int) $row->id;
                }
            }
        }

        foreach (DB::table('product_categories')->whereNull('deleted_at')->get(['id', 'code', 'name', 'sku_prefix']) as $row) {
            foreach (array_filter([
                $this->normalizeLookupKey($row->name ?? null),
                $this->normalizeLookupKey($row->code ?? null),
                $this->normalizeLookupKey($row->sku_prefix ?? null),
            ]) as $key) {
                $this->productCategoryLookup[$key] ??= (int) $row->id;
            }
        }

        foreach (DB::table('product_types')->whereNull('deleted_at')->get(['id', 'sku_prefix', 'name']) as $row) {
            foreach (array_filter([
                $this->normalizeLookupKey($row->name ?? null),
                $this->normalizeLookupKey($row->sku_prefix ?? null),
            ]) as $key) {
                $this->productTypeLookup[$key] ??= (int) $row->id;
            }
        }

        foreach (DB::table('master_products')->whereNull('deleted_at')->where('is_active', true)->orderBy('name')->get(['id', 'sku', 'product_category_id', 'product_type_id']) as $row) {
            $this->masterProductCategoryLookup[(int) $row->id] = $row->product_category_id ? (int) $row->product_category_id : null;
            $this->masterProductTypeLookup[(int) $row->id] = $row->product_type_id ? (int) $row->product_type_id : null;

            $skuKey = $this->normalizeLookupKey($row->sku ?? null);
            if ($skuKey) {
                $this->masterProductBySkuLookup[$skuKey] = (int) $row->id;
            }

            if ($row->product_type_id) {
                $this->masterProductIdsByTypeLookup[(int) $row->product_type_id][] = (int) $row->id;
            }

            if ($row->product_category_id) {
                $this->masterProductIdsByCategoryLookup[(int) $row->product_category_id][] = (int) $row->id;
            }
        }
    }

    private function resolveServiceFrequencyId(?string $value): ?int
    {
        $value = Str::upper(trim((string) $value));

        return match ($value) {
            '1XM' => $this->serviceFrequencyLookup['1x1'] ?? null,
            '2XM' => $this->serviceFrequencyLookup['1x2'] ?? null,
            '3XM' => $this->serviceFrequencyLookup['1x3'] ?? null,
            '2B1X' => $this->serviceFrequencyLookup['2x1'] ?? null,
            default => null,
        };
    }

    private function resolveDetailProductId(?string $materialKey, ?string $rentalCode, ?string $componentName): ?int
    {
        $componentName = Str::upper(trim((string) $componentName));
        if ($materialKey) {
            $mapped = $this->masterProductBySkuLookup[$materialKey] ?? null;
            if ($mapped) {
                return $mapped;
            }
        }

        $rentalCodeKey = $this->normalizeLookupKey($rentalCode);

        if ($rentalCodeKey && Str::contains($componentName, ['DIFFUSER', 'DISPENSER'])) {
            return $this->masterProductBySkuLookup[$rentalCodeKey] ?? null;
        }

        return null;
    }

    private function resolveDetailCategoryId(?string $componentName, ?int $masterProductId): ?int
    {
        if ($masterProductId && array_key_exists($masterProductId, $this->masterProductCategoryLookup)) {
            return $this->masterProductCategoryLookup[$masterProductId];
        }

        $componentName = Str::upper(trim((string) $componentName));

        $candidates = match (true) {
            Str::contains($componentName, 'DIFFUSER') => ['diffuser', 'ads diffuser', 'ads'],
            Str::contains($componentName, 'DISPENSER') => ['dispenser'],
            Str::contains($componentName, 'REFILL') => ['refill', 'aroma refill', 'hs refill'],
            Str::contains($componentName, 'BATTERY') || $componentName === 'BIN' => ['battery set', 'battery'],
            Str::contains($componentName, 'PART') => ['spare part', 'part'],
            Str::contains($componentName, 'CLEAN') => ['cleaner'],
            default => [$componentName],
        };

        foreach ($candidates as $candidate) {
            $key = $this->normalizeLookupKey($candidate);
            if ($key && isset($this->productCategoryLookup[$key])) {
                return $this->productCategoryLookup[$key];
            }
        }

        return null;
    }

    private function resolveDetailProductTypeId(?string $componentName): ?int
    {
        $componentName = Str::upper(trim((string) $componentName));

        $candidates = match (true) {
            Str::contains($componentName, 'DIFFUSER') => ['diffuser', 'diff'],
            Str::contains($componentName, 'DISPENSER') => ['dispenser', 'dsp', 'hsd'],
            Str::contains($componentName, 'REFILL') => ['refill', 'ref', 'ar', 'hsr'],
            Str::contains($componentName, 'BATTERY') || $componentName === 'BIN' => ['battery', 'btr'],
            Str::contains($componentName, 'PART') => ['part'],
            Str::contains($componentName, 'CLEAN') => ['cleaner', 'clean'],
            default => [$componentName],
        };

        foreach ($candidates as $candidate) {
            $key = $this->normalizeLookupKey($candidate);
            if ($key && isset($this->productTypeLookup[$key])) {
                return $this->productTypeLookup[$key];
            }
        }

        return null;
    }

    private function resolveDetailSelectedProductIds(
        ?string $rentalCode,
        ?string $componentName,
        ?int $masterProductId,
        ?int $productTypeId,
        ?int $productCategoryId
    ): array
    {
        $productId = $this->resolveDetailDefaultProductId(
            $rentalCode,
            $componentName,
            $masterProductId,
            $productTypeId,
            $productCategoryId
        );

        return $productId ? [$productId] : [];
    }

    private function resolveDetailDefaultProductId(
        ?string $rentalCode,
        ?string $componentName,
        ?int $masterProductId,
        ?int $productTypeId,
        ?int $productCategoryId
    ): ?int
    {
        if ($masterProductId) {
            return $masterProductId;
        }

        $componentName = Str::upper(trim((string) $componentName));
        $rentalCodeKey = $this->normalizeLookupKey($rentalCode);
        $rentalProductId = $rentalCodeKey ? ($this->masterProductBySkuLookup[$rentalCodeKey] ?? null) : null;

        if (
            $rentalProductId
            && Str::contains($componentName, ['DIFFUSER', 'DISPENSER', 'REFILL'])
            && $this->matchesDetailProductContext($rentalProductId, $productTypeId, $productCategoryId)
        ) {
            return $rentalProductId;
        }

        $candidates = [];

        if ($productTypeId) {
            $candidates = $this->masterProductIdsByTypeLookup[$productTypeId] ?? [];
        }

        if ($candidates === [] && $productCategoryId) {
            $candidates = $this->masterProductIdsByCategoryLookup[$productCategoryId] ?? [];
        }

        $candidates = array_values(array_unique(array_filter(array_map('intval', $candidates))));

        return count($candidates) === 1 ? $candidates[0] : null;
    }

    private function matchesDetailProductContext(int $productId, ?int $productTypeId, ?int $productCategoryId): bool
    {
        if ($productTypeId && ($this->masterProductTypeLookup[$productId] ?? null) !== $productTypeId) {
            return false;
        }

        if ($productCategoryId && ($this->masterProductCategoryLookup[$productId] ?? null) !== $productCategoryId) {
            return false;
        }

        return true;
    }

    private function syncDetailMaterials(int $detailId, array $productIds): int
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        DB::table('rental_detail_materials')
            ->where('rental_detail_id', $detailId)
            ->delete();

        foreach ($productIds as $index => $productId) {
            DB::table('rental_detail_materials')->updateOrInsert(
                [
                    'rental_detail_id' => $detailId,
                    'master_product_id' => $productId,
                ],
                [
                    'is_selected' => true,
                    'sort_order' => $index,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        return count($productIds);
    }

    private function pruneStaleImportedDetails(int $rentalId, array $currentDetailIds): int
    {
        $currentDetailIds = array_values(array_unique(array_filter(array_map('intval', $currentDetailIds))));

        $staleIds = DB::table('rental_details')
            ->where('master_rental_id', $rentalId)
            ->whereNull('deleted_at')
            ->whereNull('created_by')
            ->when($currentDetailIds !== [], fn ($query) => $query->whereNotIn('id', $currentDetailIds))
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('source_import_maps as sim')
                    ->whereColumn('sim.target_id', 'rental_details.id')
                    ->where('sim.source_system', 'catalyst')
                    ->where('sim.target_table', 'rental_details');
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($staleIds === []) {
            return 0;
        }

        DB::table('rental_detail_materials')
            ->whereIn('rental_detail_id', $staleIds)
            ->delete();

        DB::table('rental_details')
            ->whereIn('id', $staleIds)
            ->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);

        return count($staleIds);
    }

    private function extractMaterialKey(?string $description): ?string
    {
        $description = trim((string) $description);
        if ($description === '') {
            return null;
        }

        if (preg_match('/Material:\s*([^\r\n]+)/i', $description, $matches) !== 1) {
            return null;
        }

        $value = trim((string) $matches[1]);
        if ($value === '' || strtoupper($value) === 'NULL') {
            return null;
        }

        if (str_contains($value, ';')) {
            $value = trim(Str::before($value, ';'));
        }

        return $this->normalizeLookupKey($value);
    }

    private function normalizeLookupKey($value): ?string
    {
        $value = Str::ascii(Str::lower(trim((string) $value)));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value);
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
