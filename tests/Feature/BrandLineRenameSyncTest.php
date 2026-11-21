<?php

namespace Tests\Feature;

use App\Http\Controllers\Other\MasterOptionController;
use App\Http\Controllers\Other\OptionDetailController;
use App\Http\Controllers\Warehouse\BrandVariantController;
use App\Models\MasterOption;
use App\Models\OptionDetail;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * UAT 17 Nov 2026:
 * - Rename Brand Line di Master Option tidak sampai ke master_products.brand_line (salinan teks),
 *   sehingga index/detail produk menampilkan nama lama dan dropdown edit tidak preselect.
 * - Rename Brand Variant hanya ikut bila brand_line & variant_name produk SAMA PERSIS.
 * - Master Option 'Units' (id 45) tidak dipakai kode mana pun, disembunyikan dari layar generik.
 */
class BrandLineRenameSyncTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('master_options', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->boolean('system_reserved')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('option_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_option_id')->nullable();
            $table->foreignId('parent_option_id')->nullable();
            $table->string('option_name')->nullable();
            $table->string('option_description')->nullable();
            $table->string('label')->nullable();
            $table->string('code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_brand_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('brand_line_id')->nullable();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_category_id')->nullable();
            $table->unsignedBigInteger('product_type_id')->nullable();
            $table->unsignedBigInteger('brand_variant_id')->nullable();
            $table->string('name')->nullable();
            $table->string('variant_name')->nullable();
            $table->string('brand_line')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('master_options')->insert([
            ['id' => 42, 'name' => 'Brand Lines'],
            ['id' => 45, 'name' => 'Units'],
            ['id' => 46, 'name' => 'Product Units'],
        ]);

        DB::table('option_details')->insert([
            ['id' => 520, 'master_option_id' => 42, 'option_name' => 'Signature', 'label' => 'Signature'],
            ['id' => 521, 'master_option_id' => 42, 'option_name' => 'Artisan', 'label' => 'Artisan'],
            ['id' => 600, 'master_option_id' => 46, 'option_name' => 'Pcs', 'label' => 'Pcs'],
        ]);

        DB::table('product_brand_variants')->insert([
            ['id' => 14, 'brand_line_id' => 521, 'name' => 'White Tea'],
            ['id' => 15, 'brand_line_id' => 521, 'name' => 'Lavender'],
        ]);

        DB::table('master_products')->insert([
            ['id' => 1, 'name' => 'Fragrance Lemon 100 ml', 'brand_line' => 'Signature', 'variant_name' => null, 'brand_variant_id' => null],
            ['id' => 2, 'name' => 'Fragrance Lemon 500 ml', 'brand_line' => ' signature ', 'variant_name' => null, 'brand_variant_id' => null],
            ['id' => 3, 'name' => 'Fragrance Rose 100 ml', 'brand_line' => 'Artisan', 'variant_name' => null, 'brand_variant_id' => null],
            ['id' => 4, 'name' => 'Box Pcs', 'brand_line' => 'Pcs', 'variant_name' => null, 'brand_variant_id' => null],
            // Varian: FK ke varian 14 tapi teks variant_name beda kapital.
            ['id' => 10, 'name' => 'Fragrance White Tea 100 ml', 'brand_line' => 'Artisan', 'variant_name' => 'white tea', 'brand_variant_id' => 14],
            // Tanpa FK, teks beda kapital/spasi di brand_line & variant_name.
            ['id' => 11, 'name' => 'Fragrance White Tea 500 ml', 'brand_line' => ' ARTISAN', 'variant_name' => ' White Tea ', 'brand_variant_id' => null],
            // FK ke varian LAIN dengan teks yang kebetulan sama: tidak boleh ikut.
            ['id' => 12, 'name' => 'Fragrance Other 100 ml', 'brand_line' => 'Artisan', 'variant_name' => 'White Tea', 'brand_variant_id' => 15],
        ]);
    }

    protected function tearDown(): void
    {
        foreach (['master_products', 'product_brand_variants', 'option_details', 'master_options'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    private function brandLineOf(int $productId): ?string
    {
        return DB::table('master_products')->where('id', $productId)->value('brand_line');
    }

    public function test_option_detail_rename_of_brand_line_updates_master_products_case_insensitively(): void
    {
        $request = Request::create('/', 'PUT', ['option_name' => 'Signature Prime', 'is_active' => 1]);

        app(OptionDetailController::class)->update($request, OptionDetail::findOrFail(520));

        $this->assertSame('Signature Prime', DB::table('option_details')->where('id', 520)->value('option_name'));
        $this->assertSame('Signature Prime', $this->brandLineOf(1));
        $this->assertSame('Signature Prime', $this->brandLineOf(2));
        $this->assertSame('Artisan', $this->brandLineOf(3));
    }

    public function test_rename_of_non_brand_line_option_does_not_touch_master_products(): void
    {
        $request = Request::create('/', 'PUT', ['option_name' => 'Piece', 'is_active' => 1]);

        app(OptionDetailController::class)->update($request, OptionDetail::findOrFail(600));

        $this->assertSame('Pcs', $this->brandLineOf(4));
    }

    public function test_master_option_edit_form_rename_of_brand_line_updates_master_products(): void
    {
        $request = Request::create('/', 'PUT', [
            'name' => 'Brand Lines',
            'options' => [
                ['id' => 520, 'option_name' => 'Signature', 'label' => 'Signature'],
                ['id' => 521, 'option_name' => 'Artisan Plus', 'label' => 'Artisan Plus'],
            ],
        ]);

        app(MasterOptionController::class)->update($request, MasterOption::findOrFail(42));

        $this->assertSame('Signature', $this->brandLineOf(1));
        $this->assertSame('Artisan Plus', $this->brandLineOf(3));
        $this->assertSame('Artisan Plus', $this->brandLineOf(10));
        $this->assertSame('Artisan Plus', $this->brandLineOf(11));
    }

    public function test_brand_variant_rename_follows_fk_and_normalised_text_but_not_other_variants(): void
    {
        $request = Request::create('/', 'PUT', ['brand_line_id' => 521, 'name' => 'White Tea & Lily']);

        $response = app(BrandVariantController::class)->update($request, 14);

        $this->assertSame('success', $response->getData(true)['status']);

        $product = fn (int $id) => DB::table('master_products')->where('id', $id)->first();

        $this->assertSame('White Tea & Lily', $product(10)->variant_name);
        $this->assertSame('Artisan', $product(10)->brand_line);
        $this->assertSame('Fragrance White Tea & Lily 100 ml', $product(10)->name);

        $this->assertSame('White Tea & Lily', $product(11)->variant_name);
        $this->assertSame('Artisan', $product(11)->brand_line);

        $this->assertSame('White Tea', $product(12)->variant_name);
        $this->assertSame('Fragrance Other 100 ml', $product(12)->name);
    }

    public function test_unused_units_master_option_is_hidden_but_product_units_stays(): void
    {
        $hidden = MasterOption::hiddenFromGenericScreen();
        $this->assertContains('Units', $hidden);
        $this->assertContains('Term of Payment', $hidden);
        $this->assertNotContains('Product Units', $hidden);

        $this->app->instance('request', Request::create('/other/master-options', 'GET'));
        $names = app(MasterOptionController::class)->index(request())->getData()['masterOptions']->pluck('name')->all();
        $this->assertNotContains('Units', $names);
        $this->assertContains('Product Units', $names);
        $this->assertContains('Brand Lines', $names);

        $redirect = app(MasterOptionController::class)->show(MasterOption::findOrFail(45));
        $this->assertInstanceOf(RedirectResponse::class, $redirect);
        $this->assertSame(route('other.master-options.index'), $redirect->getTargetUrl());

        $this->assertNotInstanceOf(RedirectResponse::class, app(MasterOptionController::class)->show(MasterOption::findOrFail(46)));

        // Data tidak dihapus.
        $this->assertSame(1, DB::table('master_options')->where('id', 45)->whereNull('deleted_at')->count());
    }
}
