<?php

namespace Tests\Feature;

use App\Http\Controllers\Marketing\AromaChangeController;
use App\Models\MasterProduct;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AromaChangeProductsTest extends TestCase
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
            ['id' => 1, 'name' => 'Aroma', 'is_unit' => false, 'has_serial_number' => false, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Unit', 'is_unit' => true, 'has_serial_number' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('packaging_sizes')->insert([
            ['id' => 1, 'name' => '100ml', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => '250ml', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('master_products')->insert([
            [
                'id' => 1,
                'product_category_id' => 1,
                'packaging_size_id' => 1,
                'name' => 'Fragrance Lemongrass Mix 100 ml',
                'sku' => 'REFLEMONGRASS100',
                'variant_name' => 'Signature',
                'brand_line' => 'Signature',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'product_category_id' => 1,
                'packaging_size_id' => 2,
                'name' => 'Fragrance Lemongrass Mix 250 ml',
                'sku' => 'REFLEMONGRASS250',
                'variant_name' => 'Signature',
                'brand_line' => 'Signature',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'product_category_id' => 1,
                'packaging_size_id' => 1,
                'name' => 'Fragrance Coffee Mix More 100 ml',
                'sku' => 'REFCOFFEEMORE100',
                'variant_name' => 'Signature',
                'brand_line' => 'Signature',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'product_category_id' => 2,
                'packaging_size_id' => null,
                'name' => 'Signature Test Unit',
                'sku' => 'TA001',
                'variant_name' => 'Signature',
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

    public function test_aroma_change_products_are_grouped_by_actual_aroma_name_not_generic_variant(): void
    {
        $response = (new AromaChangeController())->getAromaProducts(Request::create(
            '/marketing/aroma-changes/get-aroma-products',
            'GET'
        ));

        $payload = collect($response->getData(true));

        $this->assertSame([
            'Fragrance Coffee Mix More',
            'Fragrance Lemongrass Mix',
        ], $payload->pluck('display_name')->all());
        $this->assertSame([3, 1], $payload->pluck('id')->all());
        $this->assertSame(['Signature', 'Signature'], $payload->pluck('brand_line')->all());
    }

    public function test_aroma_change_rejects_same_aroma_with_different_packaging(): void
    {
        $controller = new AromaChangeController();
        $method = new \ReflectionMethod($controller, 'ensureDifferentAromaProduct');
        $method->setAccessible(true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Aroma baru harus berbeda dari aroma lama.');

        $method->invoke(
            $controller,
            MasterProduct::with('packagingSize')->find(1),
            MasterProduct::with('packagingSize')->find(2)
        );
    }
}
