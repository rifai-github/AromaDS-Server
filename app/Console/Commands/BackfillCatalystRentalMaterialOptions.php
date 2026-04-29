<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillCatalystRentalMaterialOptions extends Command
{
    protected $signature = 'catalyst:backfill-rental-material-options
                            {--file=storage/app/catalyst/rental_materials.csv : CSV export file with ProductRental, MaterialType, and Material columns}';

    protected $description = 'Backfill exact rental material options from Catalyst MsRentalBOMDt export';

    public function handle(): int
    {
        $file = $this->resolveFilePath((string) $this->option('file'));

        if (!is_file($file)) {
            $this->error('File export rental material tidak ditemukan: ' . $file);
            return self::FAILURE;
        }

        $productMap = DB::table('source_import_maps')
            ->where('source_system', 'catalyst')
            ->where('source_table', 'MsProduct')
            ->where('target_table', 'master_products')
            ->pluck('target_id', 'source_key')
            ->map(fn ($id) => (int) $id)
            ->all();
        $productContexts = DB::table('master_products')
            ->get(['id', 'product_category_id', 'product_type_id'])
            ->mapWithKeys(fn ($row) => [(int) $row->id => [
                'product_category_id' => $row->product_category_id ? (int) $row->product_category_id : null,
                'product_type_id' => $row->product_type_id ? (int) $row->product_type_id : null,
            ]])
            ->all();
        $materialsByRentalAndType = $this->loadMaterialsFromCsv($file, $productMap);

        $details = DB::table('rental_details')
            ->join('master_rentals', 'master_rentals.id', '=', 'rental_details.master_rental_id')
            ->leftJoin('product_categories', 'product_categories.id', '=', 'rental_details.product_category_id')
            ->leftJoin('product_types', 'product_types.id', '=', 'rental_details.product_type_id')
            ->leftJoin('master_products', 'master_products.id', '=', 'rental_details.master_product_id')
            ->select(
                'rental_details.id',
                'rental_details.master_product_id',
                'rental_details.product_category_id',
                'rental_details.product_type_id',
                'master_rentals.rental_code',
                'product_categories.name as product_category_name',
                'product_types.name as product_type_name',
                'product_types.sku_prefix as product_type_code',
                'master_products.sku as master_product_sku',
                'master_products.name as master_product_name'
            )
            ->whereNull('rental_details.deleted_at')
            ->whereIn('master_rentals.id', function ($query) {
                $query->from('source_import_maps')
                    ->select('target_id')
                    ->where('source_system', 'catalyst')
                    ->where('source_table', 'MsProduct')
                    ->where('target_table', 'master_rentals');
            })
            ->get();

        $stats = [
            'processed' => 0,
            'materials_synced' => 0,
            'details_with_single_default' => 0,
            'details_with_multi_options' => 0,
            'details_without_match' => 0,
        ];

        foreach ($details as $detail) {
            $stats['processed']++;

            $rentalKey = $this->cleanKey($detail->rental_code);
            $componentKey = $this->resolveMaterialTypeForDetail($detail);

            if (!$rentalKey || !$componentKey) {
                DB::table('rental_detail_materials')
                    ->where('rental_detail_id', $detail->id)
                    ->delete();

                $stats['details_without_match']++;
                continue;
            }

            $productIds = $componentKey ? ($materialsByRentalAndType[$rentalKey][$componentKey] ?? []) : [];
            $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
            $productIds = $this->filterProductIdsForDetail($detail, $productIds, $productContexts);

            if ($productIds === []) {
                DB::table('rental_detail_materials')
                    ->where('rental_detail_id', $detail->id)
                    ->delete();

                if ((int) ($detail->master_product_id ?? 0) === 0) {
                    DB::table('rental_details')
                        ->where('id', $detail->id)
                        ->update([
                            'updated_at' => now(),
                        ]);
                }

                $stats['details_without_match']++;
                continue;
            }

            $selectedProductId = null;
            $currentProductId = (int) ($detail->master_product_id ?? 0);

            if ($currentProductId > 0 && in_array($currentProductId, $productIds, true)) {
                $selectedProductId = $currentProductId;
            } elseif (count($productIds) === 1) {
                $selectedProductId = $productIds[0];
            }

            DB::table('rental_detail_materials')
                ->where('rental_detail_id', $detail->id)
                ->delete();

            foreach ($productIds as $index => $productId) {
                DB::table('rental_detail_materials')->insert([
                    'rental_detail_id' => $detail->id,
                    'master_product_id' => $productId,
                    'is_selected' => $selectedProductId === $productId,
                    'sort_order' => $index,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $updatePayload = ['updated_at' => now()];

            if ($selectedProductId) {
                $updatePayload['master_product_id'] = $selectedProductId;
                $updatePayload['item_type'] = 'product';
                $updatePayload['item_id'] = $selectedProductId;
                $stats['details_with_single_default']++;
            } elseif ($currentProductId > 0 && !in_array($currentProductId, $productIds, true)) {
                $updatePayload['master_product_id'] = null;
                $stats['details_with_multi_options']++;
            } elseif (count($productIds) > 1) {
                $stats['details_with_multi_options']++;
            }

            DB::table('rental_details')
                ->where('id', $detail->id)
                ->update($updatePayload);

            $stats['materials_synced'] += count($productIds);
        }

        $this->table(
            ['Metric', 'Value'],
            [
                ['Processed', $stats['processed']],
                ['Materials Synced', $stats['materials_synced']],
                ['Details With Single Default', $stats['details_with_single_default']],
                ['Details With Multi Options', $stats['details_with_multi_options']],
                ['Details Without Match', $stats['details_without_match']],
            ]
        );

        $this->warn('Jika source memberi banyak material valid untuk satu component, system akan menyimpan daftar opsinya dan hanya memilih default bila aman.');

        return self::SUCCESS;
    }

    private function filterProductIdsForDetail(object $detail, array $productIds, array $productContexts): array
    {
        $productTypeId = $detail->product_type_id ? (int) $detail->product_type_id : null;
        $productCategoryId = $detail->product_category_id ? (int) $detail->product_category_id : null;

        $filtered = array_values(array_filter($productIds, function (int $productId) use ($productContexts, $productTypeId, $productCategoryId) {
            $context = $productContexts[$productId] ?? null;
            if (!$context) {
                return false;
            }

            if ($productTypeId && ($context['product_type_id'] ?? null) === $productTypeId) {
                return true;
            }

            if ($productCategoryId && ($context['product_category_id'] ?? null) === $productCategoryId) {
                return true;
            }

            return !$productTypeId && !$productCategoryId;
        }));

        return $filtered !== [] ? array_values(array_unique($filtered)) : $productIds;
    }

    private function loadMaterialsFromCsv(string $file, array $productMap): array
    {
        $handle = fopen($file, 'rb');
        if ($handle === false) {
            return [];
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return [];
        }

        $header = array_map(function ($value) {
            $value = trim((string) $value);
            $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
            return trim((string) $value, "\" \t\n\r\0\x0B");
        }, $header);

        $rentalColumn = $this->findHeaderIndex($header, ['ProductRental']);
        $typeColumn = $this->findHeaderIndex($header, ['MaterialType']);
        $materialColumn = $this->findHeaderIndex($header, ['Material']);

        $materials = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rentalKey = $this->cleanKey($row[$rentalColumn] ?? null);
            $typeKey = $this->cleanKey($row[$typeColumn] ?? null);
            $materialKey = $this->cleanKey($row[$materialColumn] ?? null);

            if (!$rentalKey || !$typeKey || !$materialKey) {
                continue;
            }

            $productId = $productMap[$materialKey] ?? null;
            if (!$productId) {
                continue;
            }

            $materials[$rentalKey][$typeKey][] = (int) $productId;
        }

        fclose($handle);

        return $materials;
    }

    private function resolveMaterialTypeForDetail(object $detail): ?string
    {
        $candidates = [
            $this->cleanKey($detail->master_product_sku ?? null),
            $this->cleanKey($detail->master_product_name ?? null),
            $this->cleanKey($detail->product_type_code ?? null),
            $this->cleanKey($detail->product_type_name ?? null),
            $this->cleanKey($detail->product_category_name ?? null),
        ];

        foreach ($candidates as $value) {
            if (!$value) {
                continue;
            }

            if (str_contains($value, 'BATTERY')) {
                return 'BATTERY';
            }

            if (str_contains($value, 'PART')) {
                return 'PART';
            }

            if (str_contains($value, 'REFILL') || str_contains($value, 'AROMA')) {
                return 'REFILL';
            }

            if (str_contains($value, 'DIFFUSER') || str_contains($value, 'DISPENSER') || str_contains($value, 'W/DISP') || str_contains($value, ' DISP ') || str_ends_with($value, 'SVC') || str_contains($value, 'SVC')) {
                return 'DIFFUSER';
            }
        }

        return null;
    }

    private function cleanKey($value): ?string
    {
        $value = strtoupper(trim((string) $value));
        return $value === '' ? null : $value;
    }

    private function findHeaderIndex(array $header, array $candidates): ?int
    {
        foreach ($header as $index => $column) {
            if (in_array($column, $candidates, true)) {
                return $index;
            }
        }

        return null;
    }

    private function resolveFilePath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return base_path('storage/app/catalyst/rental_materials.csv');
        }

        if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1 || str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        }

        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, base_path($path));
    }
}
