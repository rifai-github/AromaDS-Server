<?php

namespace Tests\Feature;

use App\Models\QuotationRoom;
use App\Services\Marketing\AromaProductResolver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA 7 Sep 2026, JA SBY-JA/26-09/0009 (C.A.S): the Job Advice rooms showed Lavender Luxo and
 * White Tea Artisan, but Material Assign Issue issued Aroma Alluring for both.
 *
 * The wizard stores the picked brand variant as a label in quotation_rooms.aroma_variant and
 * (only when it can map it to a product) as aroma_product_id. No master_products row carries
 * brand_variant_id or variant_name, so the FK was NULL on 18.981 of 19.109 QA rooms, and every
 * consumer that read the FK alone fell back to the rental BOM's default scent.
 *
 * The resolver closes that gap by matching on the product NAME after stripping the brand-line
 * word from the label.
 */
class AromaProductResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('option_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('master_option_id')->nullable();
            $table->string('option_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_brand_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('brand_line_id')->nullable();
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

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
            $table->string('name')->nullable();
            $table->boolean('is_unit')->default(false);
            $table->boolean('has_serial_number')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_category_id')->nullable();
            $table->unsignedBigInteger('product_type_id')->nullable();
            $table->unsignedBigInteger('packaging_size_id')->nullable();
            $table->unsignedBigInteger('brand_variant_id')->nullable();
            $table->string('name')->nullable();
            $table->string('variant_name')->nullable();
            $table->string('brand_line')->nullable();
            $table->string('sku')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('product_categories')->insert([
            ['id' => 1, 'name' => 'REFILL AROMA', 'is_unit' => false, 'has_serial_number' => false],
            ['id' => 2, 'name' => 'DIFFUSER', 'is_unit' => true, 'has_serial_number' => true],
        ]);

        DB::table('option_details')->insert([
            ['id' => 520, 'master_option_id' => 42, 'option_name' => 'Signature'],
            ['id' => 521, 'master_option_id' => 42, 'option_name' => 'Artisan'],
            ['id' => 522, 'master_option_id' => 42, 'option_name' => 'Luxo'],
        ]);

        DB::table('product_brand_variants')->insert([
            ['id' => 13, 'brand_line_id' => 520, 'name' => 'Alluring Signature', 'is_active' => true],
            ['id' => 14, 'brand_line_id' => 521, 'name' => 'White Tea Artisan', 'is_active' => true],
            ['id' => 15, 'brand_line_id' => 522, 'name' => 'Lavender Luxo', 'is_active' => true],
            ['id' => 1, 'brand_line_id' => 520, 'name' => 'Signature Lemon Grass', 'is_active' => true],
        ]);

        // Names and sizes mirror the QA catalogue: no brand_variant_id, no variant_name.
        $products = [
            [541, 'Fragrance Alluring Floral 100 ml', 'REFALLURING100', 1, true],
            [546, 'Fragrance Alluring Floral 50 ml', 'REFALLURING50', 1, true],
            [811, 'Fragrance Lavender 100 ml', 'REFLAVENDER100', 1, true],
            [817, 'Fragrance Lavender 500 ml', 'REFLAVENDER500', 1, true],
            [1059, 'Fragrance White Tea & Lily 100 ml', 'REFWHITEALILY100', 1, true],
            [1, 'Fragrance Lemongrass Mix 100 ml', 'REFLEMONGRASS100', 1, true],
            [1147, 'ADS W100', 'ADSW100', 2, true],
            [999, 'Fragrance Lavender 100 ml TEST', 'TESTLAV', 1, true],
            [998, 'Fragrance Discontinued Lavender 100 ml', 'REFOLDLAV', 1, false],
        ];

        foreach ($products as [$id, $name, $sku, $categoryId, $isActive]) {
            DB::table('master_products')->insert([
                'id' => $id,
                'product_category_id' => $categoryId,
                'name' => $name,
                'sku' => $sku,
                'is_active' => $isActive,
            ]);
        }
    }

    protected function tearDown(): void
    {
        foreach (['master_products', 'product_types', 'product_categories', 'product_brand_variants', 'option_details'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    private function resolver(): AromaProductResolver
    {
        return new AromaProductResolver;
    }

    public function test_brand_variant_label_resolves_to_the_matching_scent(): void
    {
        $resolver = $this->resolver();

        $this->assertSame(811, $resolver->resolveFromVariantText('Lavender Luxo')?->id);
        $this->assertSame(1059, $resolver->resolveFromVariantText('White Tea Artisan')?->id);
        $this->assertSame(541, $resolver->resolveFromVariantText('Alluring Signature')?->id);
    }

    public function test_brand_line_word_may_lead_the_label(): void
    {
        $this->assertSame(1, $this->resolver()->resolveFromVariantText('Signature Lemon Grass')?->id);
    }

    public function test_hundred_ml_is_preferred_over_other_sizes(): void
    {
        $product = $this->resolver()->resolveFromVariantText('Lavender Luxo');

        $this->assertNotNull($product);
        $this->assertStringContainsString('100 ml', $product->name);
    }

    public function test_units_test_rows_and_inactive_products_are_never_picked(): void
    {
        $resolver = $this->resolver();

        $this->assertNull($resolver->resolveFromVariantText('ADS'), 'a unit is not an aroma');
        $this->assertNotSame(999, $resolver->resolveFromVariantText('Lavender Luxo')?->id, 'test rows are excluded');
        $this->assertNotSame(998, $resolver->resolveFromVariantText('Lavender Luxo')?->id, 'inactive rows are excluded');
    }

    public function test_unknown_and_empty_labels_resolve_to_nothing(): void
    {
        $resolver = $this->resolver();

        $this->assertNull($resolver->resolveFromVariantText(null));
        $this->assertNull($resolver->resolveFromVariantText('   '));
        $this->assertNull($resolver->resolveFromVariantText('Sandalwood Imperial'));
    }

    public function test_quotation_room_prefers_its_fk_and_falls_back_to_the_label(): void
    {
        $withFk = new QuotationRoom;
        $withFk->aroma_product_id = 546;
        $withFk->aroma_variant = 'Lavender Luxo';

        $this->assertSame(546, $withFk->resolveAromaProduct()?->id, 'an explicit product wins');

        $labelOnly = new QuotationRoom;
        $labelOnly->aroma_variant = 'Lavender Luxo';

        $this->assertSame(811, $labelOnly->resolveAromaProduct()?->id, 'the label is used when the FK is empty');

        $empty = new QuotationRoom;

        $this->assertNull($empty->resolveAromaProduct());
    }
}
