<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Sebagian produk tanpa Product Type ternyata cuma data lama yang dobel
 * dengan produk hasil import Catalyst (nama sama, SKU beda, sudah
 * terklasifikasi). Command ini menonaktifkannya - tapi HANYA kalau
 * "kembaran"-nya benar-benar barang gudang, bukan paket sewa. Kalau tidak,
 * menonaktifkan yang lama berarti tidak ada lagi versi barang gudang yang
 * tersisa untuk nama produk itu.
 */
class DeactivateUnclassifiedDuplicateProductsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('product_types')->insert([
            ['id' => 1, 'name' => 'BATTERY', 'sku_prefix' => 'BTR', 'source_category' => 'Material', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Rental Non-QR', 'sku_prefix' => 'RNNQR', 'source_category' => 'Rental', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('master_products')->insert([
            // Duplikat lama tanpa Product Type, punya kembaran BARANG GUDANG asli.
            ['id' => 1, 'name' => 'Battery R6 ABC Super Power', 'sku' => 'BATR6', 'product_type_id' => null, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Battery R6 ABC Super Power', 'sku' => 'BTRATR6ABC', 'product_type_id' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            // Tanpa Product Type, tapi kembarannya justru PAKET SEWA - jangan disentuh.
            ['id' => 3, 'name' => 'PURE Hand Sanitizer (Gel) 1000 mL', 'sku' => 'HSG1000', 'product_type_id' => null, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'PURE Hand Sanitizer (Gel) 1000 mL', 'sku' => 'HSG1k', 'product_type_id' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            // Tanpa Product Type, tanpa kembaran sama sekali - jangan disentuh.
            ['id' => 5, 'name' => 'Diffuser W300 Black', 'sku' => 'DIFW300B', 'product_type_id' => null, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    protected function tearDown(): void
    {
        foreach (['master_products', 'product_types'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_dry_run_does_not_write_anything(): void
    {
        $this->artisan('master-products:deactivate-unclassified-duplicates')->assertExitCode(0);

        $this->assertTrue((bool) DB::table('master_products')->where('id', 1)->value('is_active'));
    }

    public function test_apply_deactivates_a_duplicate_with_a_stockable_twin(): void
    {
        $this->artisan('master-products:deactivate-unclassified-duplicates --apply')->assertExitCode(0);

        $this->assertFalse((bool) DB::table('master_products')->where('id', 1)->value('is_active'));
        $this->assertTrue((bool) DB::table('master_products')->where('id', 2)->value('is_active'));
    }

    public function test_apply_never_deactivates_when_the_only_twin_is_a_rental_package(): void
    {
        $this->artisan('master-products:deactivate-unclassified-duplicates --apply')->assertExitCode(0);

        $this->assertTrue((bool) DB::table('master_products')->where('id', 3)->value('is_active'));
    }

    public function test_apply_never_deactivates_a_product_with_no_twin_at_all(): void
    {
        $this->artisan('master-products:deactivate-unclassified-duplicates --apply')->assertExitCode(0);

        $this->assertTrue((bool) DB::table('master_products')->where('id', 5)->value('is_active'));
    }

    public function test_id_option_restricts_the_scan(): void
    {
        $this->artisan('master-products:deactivate-unclassified-duplicates --id=5 --apply')->assertExitCode(0);

        // ID 1 has a valid stockable twin but was excluded by --id=5.
        $this->assertTrue((bool) DB::table('master_products')->where('id', 1)->value('is_active'));
    }
}
