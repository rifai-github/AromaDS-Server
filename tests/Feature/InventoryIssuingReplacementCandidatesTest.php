<?php

namespace Tests\Feature;

use App\Http\Controllers\Warehouse\InventoryIssuingController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InventoryIssuingReplacementCandidatesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('product_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->boolean('is_unit')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->boolean('is_unit')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('packaging_sizes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('variant_name')->nullable();
            $table->string('brand_line')->nullable();
            $table->string('sku')->nullable();
            $table->foreignId('product_type_id')->nullable();
            $table->foreignId('product_category_id')->nullable();
            $table->foreignId('packaging_size_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouse_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->decimal('quantity', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // Brand-line refill products migrated to product_category_id only —
        // product_type_id is NULL, which is the exact shape that broke the old
        // whereHas('productType', ...) filter.
        DB::table('product_categories')->insert([
            ['id' => 1, 'name' => 'Refill', 'is_unit' => false, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Dispenser', 'is_unit' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('packaging_sizes')->insert([
            ['id' => 1, 'name' => '100ml', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => '250ml', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('master_products')->insert([
            [
                'id' => 1,
                'name' => 'Fragrance Ginger Blossom 100 ml',
                'variant_name' => 'Luxo',
                'brand_line' => 'Luxo',
                'sku' => 'REFGINGER100',
                'product_type_id' => null,
                'product_category_id' => 1,
                'packaging_size_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Fragrance Loco Floral 100 ml',
                'variant_name' => 'Luxo',
                'brand_line' => 'Luxo',
                'sku' => 'REFLOCO100',
                'product_type_id' => null,
                'product_category_id' => 1,
                'packaging_size_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'Fragrance Loco Floral 250 ml',
                'variant_name' => 'Luxo',
                'brand_line' => 'Luxo',
                'sku' => 'REFLOCO250',
                'product_type_id' => null,
                'product_category_id' => 1,
                'packaging_size_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'name' => 'Fragrance White Rose 100 ml',
                'variant_name' => 'Artisan',
                'brand_line' => 'Artisan',
                'sku' => 'REFROSE100',
                'product_type_id' => null,
                'product_category_id' => 1,
                'packaging_size_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'name' => 'Luxo Dispenser Unit',
                'variant_name' => null,
                'brand_line' => 'Luxo',
                'sku' => 'UNITLUXO',
                'product_type_id' => null,
                'product_category_id' => 2,
                'packaging_size_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('warehouse_products')->insert([
            ['warehouse_id' => 1, 'master_product_id' => 1, 'quantity' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['warehouse_id' => 1, 'master_product_id' => 2, 'quantity' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['warehouse_id' => 1, 'master_product_id' => 3, 'quantity' => 10, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('warehouse_products');
        Schema::dropIfExists('master_products');
        Schema::dropIfExists('packaging_sizes');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('product_types');

        parent::tearDown();
    }

    private function callCandidates(array $params): array
    {
        $request = Request::create('/warehouse/inventory-issuings/get-replacement-candidates', 'GET', $params);

        $response = app(InventoryIssuingController::class)->getReplacementCandidates($request);

        return $response->getData(true);
    }

    public function test_products_with_null_product_type_but_category_based_is_unit_flag_are_not_excluded(): void
    {
        $payload = $this->callCandidates([
            'brand_line' => 'Luxo',
            'packaging_size_id' => 1,
            'warehouse_id' => 1,
        ]);

        $this->assertSame('success', $payload['status'], $payload['message'] ?? '');
        $ids = collect($payload['data'])->pluck('id')->all();

        // Both same-brand-line, same-size refills should be present despite product_type_id being NULL.
        $this->assertContains(1, $ids);
        $this->assertContains(2, $ids);
    }

    public function test_only_same_brand_line_and_same_packaging_size_are_returned(): void
    {
        $payload = $this->callCandidates([
            'brand_line' => 'Luxo',
            'packaging_size_id' => 1,
            'warehouse_id' => 1,
        ]);

        $ids = collect($payload['data'])->pluck('id')->all();

        // Different packaging size (id 3, 250ml) must not appear at all, not just be disabled.
        $this->assertNotContains(3, $ids);

        // Different brand line (id 4, Artisan) must not appear.
        $this->assertNotContains(4, $ids);
    }

    public function test_unit_products_are_excluded_even_with_null_product_type(): void
    {
        $payload = $this->callCandidates([
            'brand_line' => 'Luxo',
            'packaging_size_id' => 1,
            'warehouse_id' => 1,
        ]);

        $ids = collect($payload['data'])->pluck('id')->all();

        $this->assertNotContains(5, $ids);
    }

    public function test_out_of_stock_same_size_variant_is_listed_but_not_selectable(): void
    {
        $payload = $this->callCandidates([
            'brand_line' => 'Luxo',
            'packaging_size_id' => 1,
            'warehouse_id' => 1,
        ]);

        $outOfStock = collect($payload['data'])->firstWhere('id', 2);

        $this->assertNotNull($outOfStock);
        $this->assertFalse($outOfStock['is_selectable']);
        $this->assertSame('Stok Kosong', $outOfStock['reason']);
    }
}
