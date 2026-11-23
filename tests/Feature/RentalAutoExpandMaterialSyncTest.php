<?php

namespace Tests\Feature;

use App\Models\RentalDetail;
use App\Services\Warehouse\RentalDetailMaterialSync;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * MOM 18 Nov 2026 p7: rental hasil seed/import (auto_expand=1 + kategori, material list kosong)
 * harus terhubung otomatis ke produk kategorinya, tanpa membuka & menyimpan Material List satu
 * per satu.
 */
class RentalAutoExpandMaterialSyncTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['rental_detail_materials', 'rental_details', 'master_products', 'master_rentals', 'product_categories', 'product_types', 'packaging_sizes', 'audit_logs'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('product_categories', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('parent_id')->nullable();
            $t->string('name');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('product_types', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('packaging_sizes', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('master_products', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('sku')->nullable();
            $t->unsignedBigInteger('product_category_id')->nullable();
            $t->unsignedBigInteger('product_type_id')->nullable();
            $t->unsignedBigInteger('packaging_size_id')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('master_rentals', function (Blueprint $t) {
            $t->id();
            $t->string('rental_code');
            $t->string('rental_name');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('rental_details', function (Blueprint $t) {
            $t->id();
            $t->string('item_type')->nullable();
            $t->unsignedBigInteger('item_id')->nullable();
            $t->unsignedBigInteger('master_rental_id');
            $t->unsignedBigInteger('product_type_id')->nullable();
            $t->unsignedBigInteger('product_category_id')->nullable();
            $t->unsignedBigInteger('master_product_id')->nullable();
            $t->integer('service_frequency_multiplier')->default(1);
            $t->integer('quantity')->default(1);
            $t->boolean('auto_expand')->default(false);
            $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('rental_detail_materials', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('rental_detail_id');
            $t->unsignedBigInteger('master_product_id');
            $t->boolean('is_selected')->default(true);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('action')->nullable();
            $t->string('model_type')->nullable();
            $t->unsignedBigInteger('model_id')->nullable();
            $t->text('old_values')->nullable();
            $t->text('new_values')->nullable();
            $t->text('changed_fields')->nullable();
            $t->string('ip_address')->nullable();
            $t->text('user_agent')->nullable();
            $t->string('page_name')->nullable();
            $t->string('module_name')->nullable();
            $t->timestamps();
        });

        DB::table('product_categories')->insert([['id' => 1, 'name' => 'FRAGRANCE'], ['id' => 2, 'name' => 'HEPA']]);
        DB::table('master_products')->insert([
            ['id' => 11, 'name' => 'Alluring Floral 100 ml', 'product_category_id' => 1, 'is_active' => true],
            ['id' => 12, 'name' => 'Amberwood 100 ml', 'product_category_id' => 1, 'is_active' => true],
            ['id' => 13, 'name' => 'Fragrance Lama', 'product_category_id' => 1, 'is_active' => false],
            ['id' => 21, 'name' => 'HEPA Filter', 'product_category_id' => 2, 'is_active' => true],
        ]);
        DB::table('master_rentals')->insert(['id' => 1, 'rental_code' => 'ADS106', 'rental_name' => 'ADS 106 - 150ml']);
        DB::table('rental_details')->insert([
            // Kosong (seperti hasil seed): harus dihubungkan.
            ['id' => 1, 'master_rental_id' => 1, 'product_category_id' => 1, 'auto_expand' => true],
            // Sudah punya material list pilihan user: tidak disentuh.
            ['id' => 2, 'master_rental_id' => 1, 'product_category_id' => 1, 'auto_expand' => true],
            // Bukan auto_expand: tidak disentuh.
            ['id' => 3, 'master_rental_id' => 1, 'product_category_id' => 2, 'auto_expand' => false],
        ]);
        DB::table('rental_detail_materials')->insert(['rental_detail_id' => 2, 'master_product_id' => 21, 'is_selected' => true]);
    }

    public function test_dry_run_reports_but_does_not_write(): void
    {
        $this->artisan('master-rentals:sync-auto-expand-materials')
            ->expectsOutputToContain('bisa dihubungkan: 1')
            ->assertSuccessful();

        $this->assertSame(0, DB::table('rental_detail_materials')->where('rental_detail_id', 1)->count());
    }

    public function test_apply_links_only_empty_auto_expand_rows_to_active_products(): void
    {
        $this->artisan('master-rentals:sync-auto-expand-materials', ['--apply' => true])->assertSuccessful();

        $this->assertEqualsCanonicalizing(
            [11, 12],
            DB::table('rental_detail_materials')->where('rental_detail_id', 1)->pluck('master_product_id')->map(fn ($id) => (int) $id)->all()
        );
        $this->assertSame(11, (int) DB::table('rental_details')->where('id', 1)->value('master_product_id'));

        // Baris yang sudah punya material dan baris non auto_expand tidak berubah.
        $this->assertSame([21], DB::table('rental_detail_materials')->where('rental_detail_id', 2)->pluck('master_product_id')->map(fn ($id) => (int) $id)->all());
        $this->assertSame(0, DB::table('rental_detail_materials')->where('rental_detail_id', 3)->count());
    }

    public function test_parent_category_without_own_products_uses_its_sub_categories(): void
    {
        // QA 23 Nov: BOM "Refill Hand Sanitizer" (induk) - produknya ada di sub-kategori.
        DB::table('product_categories')->insert([
            ['id' => 44, 'parent_id' => null, 'name' => 'Refill Hand Sanitizer'],
            ['id' => 45, 'parent_id' => 44, 'name' => 'Hand Sanitizer Gel'],
            ['id' => 46, 'parent_id' => 45, 'name' => 'Hand Sanitizer Gel 1L'],
        ]);
        DB::table('master_products')->insert([
            ['id' => 31, 'name' => 'HS Gel 500 ml', 'product_category_id' => 45, 'is_active' => true],
            ['id' => 32, 'name' => 'HS Gel 1000 ml', 'product_category_id' => 46, 'is_active' => true],
            ['id' => 33, 'name' => 'HS Lama', 'product_category_id' => 45, 'is_active' => false],
        ]);
        DB::table('rental_details')->insert(['id' => 4, 'master_rental_id' => 1, 'product_category_id' => 44, 'auto_expand' => true]);

        $this->artisan('master-rentals:sync-auto-expand-materials', ['--apply' => true])->assertSuccessful();

        $this->assertEqualsCanonicalizing(
            [31, 32],
            DB::table('rental_detail_materials')->where('rental_detail_id', 4)->pluck('master_product_id')->map(fn ($id) => (int) $id)->all()
        );
        // Kategori yang punya produk sendiri tetap hanya memakai produknya sendiri.
        $this->assertEqualsCanonicalizing([11, 12], app(RentalDetailMaterialSync::class)->productsForScope(1)->pluck('id')->map(fn ($id) => (int) $id)->all());
    }

    public function test_needs_sync_only_for_empty_auto_expand_rows(): void
    {
        $sync = app(RentalDetailMaterialSync::class);

        $this->assertTrue($sync->needsSync(RentalDetail::findOrFail(1)));
        $this->assertFalse($sync->needsSync(RentalDetail::findOrFail(2)));
        $this->assertFalse($sync->needsSync(RentalDetail::findOrFail(3)));
    }
}
