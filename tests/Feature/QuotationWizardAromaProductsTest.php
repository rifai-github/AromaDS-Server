<?php

namespace Tests\Feature;

use App\Http\Controllers\Marketing\QuotationWizardController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuotationWizardAromaProductsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->boolean('is_unit')->default(false);
            $table->boolean('has_serial_number')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_category_id')->nullable();
            $table->string('name')->nullable();
            $table->boolean('is_unit')->default(false);
            $table->boolean('has_serial_number')->default(false);
            $table->timestamps();
        });

        Schema::create('packaging_sizes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
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
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_brand_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_line_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_type_id')->nullable();
            $table->foreignId('product_category_id')->nullable();
            $table->foreignId('packaging_size_id')->nullable();
            $table->foreignId('brand_variant_id')->nullable();
            $table->string('name')->nullable();
            $table->string('sku')->nullable();
            $table->string('variant_name')->nullable();
            $table->string('brand_line')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('product_categories')->insert([
            ['id' => 1, 'name' => 'Consumable', 'is_unit' => false, 'has_serial_number' => false, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Unit', 'is_unit' => true, 'has_serial_number' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Brand line "Signature" with one active variant "Lemongrass" — the new
        // single source of truth for the Survey "Pilih Aroma/Variant" dropdown.
        DB::table('option_details')->insert([
            'id' => 10,
            'option_name' => 'Signature',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_brand_variants')->insert([
            'id' => 100,
            'brand_line_id' => 10,
            'name' => 'Lemongrass',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('master_products')->insert([
            [
                'id' => 1,
                'product_category_id' => 1,
                'brand_variant_id' => 100,
                'name' => 'Fragrance Lemongrass Mix 100 ml',
                'sku' => 'REFLEMONGRASS100',
                'variant_name' => 'Lemongrass',
                'brand_line' => 'Signature',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'product_category_id' => 2,
                'brand_variant_id' => null,
                'name' => 'Signature Test Unit',
                'sku' => 'TA001',
                'variant_name' => 'Lemongrass',
                'brand_line' => 'Signature',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('master_products');
        Schema::dropIfExists('product_brand_variants');
        Schema::dropIfExists('option_details');
        Schema::dropIfExists('packaging_sizes');
        Schema::dropIfExists('product_types');
        Schema::dropIfExists('product_categories');

        parent::tearDown();
    }

    public function test_quotation_wizard_aroma_products_list_brand_lines_from_brand_variants(): void
    {
        $response = (new QuotationWizardController())->getAromaProducts(Request::create(
            '/marketing/quotations/wizard/get-aroma-products',
            'GET'
        ));

        $payload = collect($response->getData(true));

        $this->assertSame(['Signature'], $payload->pluck('display_name')->all());
        $this->assertSame(['brandline:10'], $payload->pluck('id')->all());
    }

    public function test_resolve_canonical_aroma_product_id_prefers_brand_variant_linked_product(): void
    {
        $controller = new QuotationWizardController();
        $resolve = (new \ReflectionClass($controller))->getMethod('resolveCanonicalAromaProductId');
        $resolve->setAccessible(true);

        $resolvedId = $resolve->invoke($controller, 'brandline:10');

        $this->assertSame(1, $resolvedId);
    }
}
