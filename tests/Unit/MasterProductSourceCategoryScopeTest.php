<?php

namespace Tests\Unit;

use App\Models\MasterProduct;
use App\Models\ProductType;
use Tests\TestCase;

class MasterProductSourceCategoryScopeTest extends TestCase
{
    public function test_stockable_goods_excludes_rental_package_type_codes(): void
    {
        $query = MasterProduct::query()->stockableGoods();

        $this->assertStringContainsString('sku_prefix', $query->toSql());

        foreach (ProductType::PACKAGE_TYPE_CODES as $code) {
            $this->assertContains($code, $query->getBindings());
        }
    }

    public function test_stockable_goods_excludes_cost_and_fixed_asset_categories(): void
    {
        $query = MasterProduct::query()->stockableGoods();

        $this->assertContains(ProductType::SOURCE_CATEGORY_OTHER, $query->getBindings());
        $this->assertContains(ProductType::SOURCE_CATEGORY_FIXED_ASSET, $query->getBindings());
    }

    /**
     * Unit fisik (DIFFUSER, DISPENSER, Japan Air Filter, THERMAL) bernilai
     * source_category 'Rental' tapi bukan paket - client menegaskan 3 Agu 2026 bahwa
     * barang ini tetap harus bisa diminta & di-adjust dari gudang.
     */
    public function test_stockable_goods_keeps_rental_category_because_units_are_not_packages(): void
    {
        $bindings = MasterProduct::query()->stockableGoods()->getBindings();

        $this->assertNotContains(ProductType::SOURCE_CATEGORY_RENTAL, $bindings);
        $this->assertNotContains(ProductType::SOURCE_CATEGORY_MATERIAL, $bindings);
    }

    /**
     * product_type_id itu nullable di form Master Product. Sebelumnya produk yang
     * dibuat tanpa Product Type otomatis terbuang dari Inventory Request/Issuing/
     * Stock Adjustment tanpa peringatan - ditemukan lewat laporan QA 10 Agu 2026
     * (produk REFILL asli seperti "PURE Hand Sanitizer (Gel) 1000 mL" hilang total
     * padahal bukan paket sewa). Sekarang produk begini tetap muncul; yang dibuang
     * cuma yang MEMANG dikenali sebagai paket/non-stok lewat Product Type-nya.
     */
    public function test_stockable_goods_keeps_products_without_product_type(): void
    {
        $sql = strtolower(MasterProduct::query()->stockableGoods()->toSql());

        $this->assertStringContainsString('product_type_id" is null', $sql);
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

    public function test_package_type_codes_match_client_definition(): void
    {
        // MsProduct.ProductType = RNT / RNNQR adalah satu-satunya arti "rental"
        // yang berarti paket tanpa stok.
        $this->assertSame(['RNT', 'RNNQR'], ProductType::PACKAGE_TYPE_CODES);
    }
}
