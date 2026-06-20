<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillCatalystProductWarehouses extends Command
{
    protected $signature = 'catalyst:backfill-product-warehouses
                            {--file=storage/app/catalyst/product_warehouse_links.csv : CSV export file with ProductCode and Warehouse columns}';

    protected $description = 'Backfill warehouse presence for imported Catalyst products without overwriting stock quantities';

    public function handle(): int
    {
        $file = $this->resolveFilePath((string) $this->option('file'));

        if (!is_file($file)) {
            $this->error('File export warehouse product tidak ditemukan: ' . $file);
            return self::FAILURE;
        }

        $productMap = DB::table('source_import_maps')
            ->where('source_system', 'catalyst')
            ->where('source_table', 'MsProduct')
            ->where('target_table', 'master_products')
            ->pluck('target_id', 'source_key')
            ->map(fn ($id) => (int) $id)
            ->all();

        $warehouseMap = DB::table('source_import_maps')
            ->where('source_system', 'catalyst')
            ->where('source_table', 'MsWarehouse')
            ->where('target_table', 'warehouses')
            ->pluck('target_id', 'source_key')
            ->map(fn ($id) => (int) $id)
            ->all();

        $handle = fopen($file, 'rb');
        if ($handle === false) {
            $this->error('File export warehouse product tidak bisa dibuka.');
            return self::FAILURE;
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            $this->error('File export warehouse product kosong.');
            return self::FAILURE;
        }

        $header = array_map(function ($value) {
            $value = trim((string) $value);
            $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
            return trim((string) $value, "\" \t\n\r\0\x0B");
        }, $header);
        $productColumn = $this->findHeaderIndex($header, ['ProductCode', 'productcode', 'product_code']);
        $warehouseColumn = $this->findHeaderIndex($header, ['Warehouse', 'warehouse', 'warehouse_code']);

        if ($productColumn === null || $warehouseColumn === null) {
            fclose($handle);
            $this->error('Header CSV harus mengandung kolom ProductCode dan Warehouse.');
            return self::FAILURE;
        }

        $stats = [
            'processed' => 0,
            'inserted' => 0,
            'restored' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        while (($row = fgetcsv($handle)) !== false) {
            $stats['processed']++;

            $productKey = $this->cleanKey($row[$productColumn] ?? null);
            $warehouseKey = $this->cleanKey($row[$warehouseColumn] ?? null);

            if (!$productKey || !$warehouseKey) {
                $stats['skipped']++;
                continue;
            }

            $masterProductId = $productMap[$productKey] ?? null;
            $warehouseId = $warehouseMap[$warehouseKey] ?? null;

            if (!$masterProductId || !$warehouseId) {
                $stats['skipped']++;
                continue;
            }

            $existing = DB::table('warehouse_products')
                ->where('master_product_id', $masterProductId)
                ->where('warehouse_id', $warehouseId)
                ->first();

            if (!$existing) {
                $masterProduct = DB::table('master_products')->where('id', $masterProductId)->first();
                DB::table('warehouse_products')->insert([
                    'warehouse_id' => $warehouseId,
                    'master_product_id' => $masterProductId,
                    'quantity' => 0,
                    'minimum_stock' => $masterProduct->minimum_stock ?? 0,
                    'maximum_stock' => $masterProduct->maximum_stock ?? 0,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                    'deleted_at' => null,
                ]);
                $stats['inserted']++;
                continue;
            }

            if (($existing->deleted_at ?? null) !== null) {
                DB::table('warehouse_products')->where('id', $existing->id)->update([
                    'deleted_at' => null,
                    'updated_by' => auth()->id(),
                    'updated_at' => now(),
                ]);
                $stats['restored']++;
                continue;
            }

            $stats['skipped']++;
        }

        fclose($handle);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Processed', $stats['processed']],
                ['Inserted', $stats['inserted']],
                ['Restored', $stats['restored']],
                ['Skipped', $stats['skipped']],
                ['Failed', $stats['failed']],
            ]
        );

        $this->warn('Stock quantity tidak diubah. Command ini hanya memastikan produk terhubung ke warehouse yang benar.');

        return self::SUCCESS;
    }

    private function cleanKey($value): ?string
    {
        $value = trim((string) $value);
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
            return base_path('storage/app/catalyst/product_warehouse_links.csv');
        }

        if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1 || str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        }

        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, base_path($path));
    }
}
