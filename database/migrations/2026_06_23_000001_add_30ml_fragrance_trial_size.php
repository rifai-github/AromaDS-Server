<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('packaging_sizes') || ! Schema::hasTable('master_products')) {
            return;
        }

        $packagingSizeId = $this->ensurePackagingSize();

        $fiftyMlProducts = DB::table('master_products')
            ->where('packaging_size_id', 1)
            ->whereNull('deleted_at')
            ->get();

        $productIdMap = [];

        foreach ($fiftyMlProducts as $product) {
            $thirtyMlProduct = DB::table('master_products')->where('sku', $this->buildSku($product->sku))->first();

            if ($thirtyMlProduct) {
                $productIdMap[$product->id] = $thirtyMlProduct->id;

                continue;
            }

            $newId = DB::table('master_products')->insertGetId([
                'sku' => $this->buildSku($product->sku),
                'product_type_id' => $product->product_type_id,
                'product_category_id' => $product->product_category_id,
                'name' => preg_replace('/\b50\s*ml\b/i', '30 ml', $product->name),
                'variant_name' => $product->variant_name,
                'dimensions' => $product->dimensions,
                'net_weight' => $product->net_weight,
                'gross_weight' => $product->gross_weight,
                'packaging_size_id' => $packagingSizeId,
                'brand_line' => $product->brand_line,
                'description' => $product->description,
                'unit' => $product->unit,
                'minimum_stock' => $product->minimum_stock,
                'maximum_stock' => $product->maximum_stock,
                'bom_quantity' => round(((float) $product->bom_quantity) * 30 / 50, 2),
                'last_unit_price' => $product->last_unit_price,
                'is_active' => 1,
                'is_trading' => $product->is_trading,
                'is_stock_substitute' => $product->is_stock_substitute,
                'created_by' => $product->created_by,
                'updated_by' => $product->updated_by,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $productIdMap[$product->id] = $newId;
        }

        if (! Schema::hasTable('rental_detail_materials') || empty($productIdMap)) {
            return;
        }

        $selectedFiftyMlRows = DB::table('rental_detail_materials')
            ->whereIn('master_product_id', array_keys($productIdMap))
            ->where('is_selected', true)
            ->get();

        foreach ($selectedFiftyMlRows as $row) {
            $thirtyMlProductId = $productIdMap[$row->master_product_id];

            $exists = DB::table('rental_detail_materials')
                ->where('rental_detail_id', $row->rental_detail_id)
                ->where('master_product_id', $thirtyMlProductId)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('rental_detail_materials')->insert([
                'rental_detail_id' => $row->rental_detail_id,
                'master_product_id' => $thirtyMlProductId,
                'is_selected' => true,
                'sort_order' => $row->sort_order,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('master_products') || ! Schema::hasTable('packaging_sizes')) {
            return;
        }

        $packagingSize = DB::table('packaging_sizes')->where('code', '30ML')->first();

        if (! $packagingSize) {
            return;
        }

        $productIds = DB::table('master_products')
            ->where('packaging_size_id', $packagingSize->id)
            ->pluck('id');

        if (Schema::hasTable('rental_detail_materials') && $productIds->isNotEmpty()) {
            DB::table('rental_detail_materials')->whereIn('master_product_id', $productIds)->delete();
        }

        if ($productIds->isNotEmpty()) {
            DB::table('master_products')->whereIn('id', $productIds)->delete();
        }

        DB::table('packaging_sizes')->where('id', $packagingSize->id)->delete();
    }

    private function buildSku(string $fiftyMlSku): string
    {
        return preg_replace('/50$/', '30', $fiftyMlSku);
    }

    private function ensurePackagingSize(): int
    {
        $existing = DB::table('packaging_sizes')->where('code', '30ML')->first();

        if ($existing) {
            return $existing->id;
        }

        return DB::table('packaging_sizes')->insertGetId([
            'name' => '30ml',
            'code' => '30ML',
            'description' => 'Trial size packaging - 30 milliliters.',
            'sort_order' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
