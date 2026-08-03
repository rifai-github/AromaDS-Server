<?php

namespace Tests\Feature;

use App\Http\Controllers\Warehouse\InventoryIssuingController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Manual Issuing ("Add Product" picker at /warehouse/inventory-issuings/all-products",
 * wired from index.blade.php and show.blade.php) listed every active MasterProduct with
 * no filter - the exact same bug already fixed for Inventory Request & Stock Adjustment.
 * Rental packages (RNT/RNNQR) have zero stock and shouldn't be picked to issue; cost
 * items (Other/Fixed Asset) aren't warehouse goods either.
 */
class InventoryIssuingGetAllProductsFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('sku_prefix')->nullable();
            $table->string('source_category')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('sku')->nullable();
            $table->foreignId('product_type_id')->nullable();
            $table->foreignId('product_category_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_product_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('product_types')->insert([
            ['id' => 1, 'name' => 'REFILL', 'sku_prefix' => 'REF', 'source_category' => 'Material', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'DIFFUSER', 'sku_prefix' => 'DIS', 'source_category' => 'Rental', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Rental w/QR', 'sku_prefix' => 'RNT', 'source_category' => 'Rental', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'Rental Non-QR', 'sku_prefix' => 'RNNQR', 'source_category' => 'Rental', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'ATK', 'sku_prefix' => 'AK', 'source_category' => 'Other', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('master_products')->insert([
            ['id' => 1, 'name' => 'Fragrance Coffee Mix 100 ml', 'sku' => 'REFCOFFEE100', 'product_type_id' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'ADS Dispenser 005 Bluetooth', 'sku' => 'DIS005BB', 'product_type_id' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'ADS 103 12 SVC / YR PCKG', 'sku' => 'RNT103', 'product_type_id' => 3, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'PURE Hand Sanitizer 1000ml', 'sku' => 'RNNQR-HS1000', 'product_type_id' => 4, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'Kertas A4', 'sku' => 'AK-A4', 'product_type_id' => 5, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_get_all_products_excludes_rental_packages_and_cost_items(): void
    {
        $response = app(InventoryIssuingController::class)->getAllProducts();
        $payload = $response->getData(true);
        $this->assertSame('success', $payload['status'] ?? null, $payload['message'] ?? 'no message');
        $names = collect($payload['data'])->pluck('name');

        $this->assertTrue($names->contains('Fragrance Coffee Mix 100 ml'));
        $this->assertTrue($names->contains('ADS Dispenser 005 Bluetooth'));

        $this->assertFalse($names->contains('ADS 103 12 SVC / YR PCKG'));
        $this->assertFalse($names->contains('PURE Hand Sanitizer 1000ml'));
        $this->assertFalse($names->contains('Kertas A4'));
    }
}
