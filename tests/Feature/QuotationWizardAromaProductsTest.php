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

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_type_id')->nullable();
            $table->foreignId('product_category_id')->nullable();
            $table->foreignId('packaging_size_id')->nullable();
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

        DB::table('master_products')->insert([
            [
                'id' => 1,
                'product_category_id' => 1,
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
        Schema::dropIfExists('packaging_sizes');
        Schema::dropIfExists('product_types');
        Schema::dropIfExists('product_categories');

        parent::tearDown();
    }

    public function test_quotation_wizard_aroma_products_include_fragrance_names_with_neutral_category(): void
    {
        $response = (new QuotationWizardController())->getAromaProducts(Request::create(
            '/marketing/quotations/wizard/get-aroma-products',
            'GET'
        ));

        $payload = collect($response->getData(true));

        $this->assertSame(['Lemongrass'], $payload->pluck('display_name')->all());
        $this->assertSame([1], $payload->pluck('id')->all());
    }
}
