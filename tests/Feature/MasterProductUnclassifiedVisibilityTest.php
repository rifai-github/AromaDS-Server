<?php

namespace Tests\Feature;

use App\Models\MasterProduct;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * product_type_id itu nullable di form Master Product create/edit. Produk
 * yang dibuat tanpa memilih Product Type dulu otomatis terbuang dari
 * Inventory Request/Issuing/Stock Adjustment tanpa peringatan apa pun -
 * ditemukan dari laporan QA 10 Agu 2026 saat "PURE Hand Sanitizer (Gel)
 * 1000 mL" (kategori REFILL, barang fisik asli) hilang total dari dropdown
 * Inventory Request meski aktif dan ada stoknya.
 */
class MasterProductUnclassifiedVisibilityTest extends TestCase
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

        DB::table('product_types')->insert([
            ['id' => 1, 'name' => 'REFILL', 'sku_prefix' => 'REF', 'source_category' => 'Material', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Rental Non-QR', 'sku_prefix' => 'RNNQR', 'source_category' => 'Rental', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('master_products')->insert([
            // Diklasifikasi dengan benar sebagai barang REFILL fisik.
            ['id' => 1, 'name' => 'Fragrance Coffee Mix 100 ml', 'sku' => 'REFCOFFEE100', 'product_type_id' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            // Belum sempat diklasifikasi - product_type_id kosong.
            ['id' => 2, 'name' => 'PURE Hand Sanitizer (Gel) 1000 mL', 'sku' => 'HSG1000', 'product_type_id' => null, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            // Diklasifikasi sebagai paket sewa - tetap harus tersembunyi.
            ['id' => 3, 'name' => 'PURE Hand Sanitizer (Gel) 1000 ml', 'sku' => 'HSG1k', 'product_type_id' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    protected function tearDown(): void
    {
        foreach (['master_products', 'product_types', 'product_categories'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_a_product_without_a_product_type_is_still_offered(): void
    {
        $names = MasterProduct::where('is_active', true)->stockableGoods()->pluck('sku');

        $this->assertTrue($names->contains('HSG1000'));
    }

    public function test_a_classified_rental_package_is_still_excluded(): void
    {
        $names = MasterProduct::where('is_active', true)->stockableGoods()->pluck('sku');

        $this->assertFalse($names->contains('HSG1k'));
    }

    public function test_a_normally_classified_product_still_appears(): void
    {
        $names = MasterProduct::where('is_active', true)->stockableGoods()->pluck('sku');

        $this->assertTrue($names->contains('REFCOFFEE100'));
    }
}
