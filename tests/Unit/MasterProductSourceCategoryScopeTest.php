<?php

namespace Tests\Unit;

use App\Models\MasterProduct;
use App\Models\ProductType;
use Tests\TestCase;

class MasterProductSourceCategoryScopeTest extends TestCase
{
    public function test_material_only_filters_on_product_type_source_category(): void
    {
        $query = MasterProduct::query()->materialOnly();

        $this->assertStringContainsString('exists', strtolower($query->toSql()));
        $this->assertStringContainsString('source_category', $query->toSql());
        $this->assertContains(ProductType::SOURCE_CATEGORY_MATERIAL, $query->getBindings());
    }

    public function test_material_only_excludes_products_without_product_type(): void
    {
        // whereHas menghasilkan EXISTS, jadi baris dengan product_type_id NULL ikut
        // terbuang - sama seperti LEFT JOIN + WHERE t.ProductCategory = 'Material'
        // di sistem lama.
        $sql = strtolower(MasterProduct::query()->materialOnly()->toSql());

        $this->assertStringNotContainsString('left join', $sql);
        $this->assertStringContainsString('exists (select', $sql);
    }

    public function test_by_source_category_accepts_multiple_categories(): void
    {
        $query = MasterProduct::query()->bySourceCategory([
            ProductType::SOURCE_CATEGORY_MATERIAL,
            ProductType::SOURCE_CATEGORY_RENTAL,
        ]);

        $this->assertContains(ProductType::SOURCE_CATEGORY_MATERIAL, $query->getBindings());
        $this->assertContains(ProductType::SOURCE_CATEGORY_RENTAL, $query->getBindings());
    }

    public function test_source_categories_constant_matches_legacy_catalyst_values(): void
    {
        $this->assertSame(
            ['Material', 'Rental', 'Fixed Asset', 'Other'],
            ProductType::SOURCE_CATEGORIES
        );
    }
}
