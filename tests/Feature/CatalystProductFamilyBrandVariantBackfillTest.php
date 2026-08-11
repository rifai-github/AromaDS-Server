<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Catalyst punya banyak SKU ukuran berbeda untuk satu keluarga fragrance yang
 * sama (mis. "Fragrance Lemongrass Mix 100/250/500/1000 ml"), tapi hanya
 * sebagian yang pernah di-assign brand_line/variant_name secara manual.
 * Backfill harus menyebarkan assignment itu ke ukuran lain dalam keluarga
 * yang sama (dicocokkan dari nama produk minus ukuran), tapi tidak menebak
 * untuk keluarga yang belum pernah punya satu pun anggota ter-assign.
 */
class CatalystProductFamilyBrandVariantBackfillTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('code')->nullable();
            $table->string('sku_prefix')->nullable();
            $table->timestamps();
        });

        Schema::create('product_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('sku_prefix')->nullable();
            $table->foreignId('product_category_id')->nullable();
            $table->timestamps();
        });

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('sku')->nullable();
            $table->foreignId('product_type_id')->nullable();
            $table->foreignId('product_category_id')->nullable();
            $table->string('brand_line')->nullable();
            $table->string('variant_name')->nullable();
            $table->timestamps();
        });

        Schema::create('master_options', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('option_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_option_id')->nullable();
            $table->string('option_name')->nullable();
            $table->timestamps();
        });

        Schema::create('source_import_maps', function (Blueprint $table) {
            $table->id();
            $table->string('source_system');
            $table->string('source_table');
            $table->string('target_table');
            $table->unsignedBigInteger('target_id');
            $table->timestamps();
        });

        DB::table('product_types')->insert([
            'id' => 1, 'name' => 'Refill', 'sku_prefix' => 'REF', 'product_category_id' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('master_products')->insert([
            // Sudah di-assign manual sebelumnya - ini jadi "seed" untuk keluarganya.
            [
                'id' => 1, 'sku' => 'REFLEMONGRASS100', 'name' => 'Fragrance Lemongrass Mix 100 ml',
                'product_type_id' => 1, 'brand_line' => 'Signature', 'variant_name' => 'Signature Lemon Grass',
                'created_at' => now(), 'updated_at' => now(),
            ],
            // Keluarga sama, ukuran beda, belum ke-assign - harus ikut ter-assign.
            [
                'id' => 2, 'sku' => 'REFLEMONGRASS500', 'name' => 'Fragrance Lemongrass Mix 500 ml',
                'product_type_id' => 1, 'brand_line' => null, 'variant_name' => null,
                'created_at' => now(), 'updated_at' => now(),
            ],
            // Keluarga fragrance lain yang belum pernah punya anggota ter-assign - jangan ditebak.
            [
                'id' => 3, 'sku' => 'REFSHLEMON100', 'name' => 'Fragrance Fresh Lemongrass 100 ml',
                'product_type_id' => 1, 'brand_line' => null, 'variant_name' => null,
                'created_at' => now(), 'updated_at' => now(),
            ],
        ]);

        foreach ([1, 2, 3] as $id) {
            DB::table('source_import_maps')->insert([
                'source_system' => 'catalyst', 'source_table' => 'MsProduct',
                'target_table' => 'master_products', 'target_id' => $id,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    protected function tearDown(): void
    {
        foreach (['source_import_maps', 'option_details', 'master_options', 'master_products', 'product_types', 'product_categories'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_same_family_different_size_inherits_the_seeded_brand_and_variant(): void
    {
        $this->artisan('catalyst:backfill-product-relations')->assertExitCode(0);

        $product = DB::table('master_products')->where('id', 2)->first();

        $this->assertSame('Signature', $product->brand_line);
        $this->assertSame('Signature Lemon Grass', $product->variant_name);
    }

    public function test_family_with_no_seeded_member_is_left_unassigned(): void
    {
        $this->artisan('catalyst:backfill-product-relations')->assertExitCode(0);

        $product = DB::table('master_products')->where('id', 3)->first();

        $this->assertNull($product->brand_line);
        $this->assertNull($product->variant_name);
    }

    public function test_seed_product_is_unchanged(): void
    {
        $this->artisan('catalyst:backfill-product-relations')->assertExitCode(0);

        $product = DB::table('master_products')->where('id', 1)->first();

        $this->assertSame('Signature', $product->brand_line);
        $this->assertSame('Signature Lemon Grass', $product->variant_name);
    }
}
