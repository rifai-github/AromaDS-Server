<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Material Assign — scope of the Material dropdown for aroma/refill rows.
 *
 * HISTORY (this rule has flip-flopped; read before "fixing" it):
 *
 *  1. Originally the dropdown filtered on master_products.variant_name, which
 *     is a generic brand-line code ("Luxo GHI", "Artisan DEF") shared by
 *     MULTIPLE distinct aromas. QA reported that picking "Fragrance Amberwood
 *     Sport Mix" also listed "Fragrance Garden Mix".
 *  2. That was tightened to a per-aroma scope: group by product NAME with the
 *     size (ml) suffix stripped, so only other packaging sizes of the SAME
 *     aroma appeared.
 *  3. CLIENT RULE (Sep 2026) — CURRENT: the dropdown is scoped to the BRAND
 *     LINE (Artisan / Signature / Luxo / ...) within the SAME product
 *     category, in every packaging size. Picking an Artisan row lists every
 *     Artisan material of that category; the aroma chosen up front
 *     (quotation/contract) stays pre-selected. Showing a sibling aroma of the
 *     same brand is now WANTED, not the bug from step 1.
 *
 * The per-aroma base-name grouping is kept as the FALLBACK for products that
 * carry no brand_line, so both behaviours are locked here.
 */
class MaterialAssignAromaSizeOnlyFilterTest extends TestCase
{
    private function viewSource(): string
    {
        return file_get_contents(resource_path('views/operational/job-assign-material-issues/index.blade.php'));
    }

    private function aromaBaseName(string $name): string
    {
        return trim(preg_replace([
            '/\b\d+(?:[\.,]\d+)?\s*ml\b/i',
            '/[-_\[\]\(\)]+/',
            '/\s+/',
        ], [
            '',
            ' ',
            ' ',
        ], $name));
    }

    /**
     * Blade side: the primary same-family check must be brand_line +
     * product_category, not the aroma base name.
     */
    public function test_blade_view_scopes_aroma_dropdown_to_brand_line_and_category(): void
    {
        $view = $this->viewSource();

        $this->assertStringContainsString('$hasBrandFamilyScope', $view);
        $this->assertStringContainsString('$productBrandLine === $normalizedCurrentBrandLine', $view);
        $this->assertStringContainsString('(int) $p->product_category_id === $currentCategoryId', $view);
    }

    /**
     * The per-aroma grouping must survive as the no-brand_line fallback.
     */
    public function test_blade_view_keeps_base_name_grouping_as_fallback(): void
    {
        $view = $this->viewSource();

        $this->assertStringContainsString('$normalizedCurrentBaseName', $view);
        $this->assertStringContainsString('$normalizedProductBaseName', $view);
        $this->assertStringContainsString('$normalizedProductBaseName === $normalizedCurrentBaseName', $view);
    }

    /**
     * JS side: the dropdown builders use the brand-family filter.
     */
    public function test_javascript_dropdown_builders_use_brand_family_filter(): void
    {
        $view = $this->viewSource();

        $this->assertStringContainsString('function filterSameBrandFamily(', $view);
        $this->assertStringContainsString('filterSameBrandFamily(item.product, filteredProducts)', $view);
        $this->assertStringContainsString('filterSameBrandFamily(originalProduct, filteredProducts)', $view);
    }

    /**
     * Changing the packaging size must still resolve to the SAME aroma in the
     * new size — widening the dropdown must not widen the size swap.
     */
    public function test_packaging_size_swap_stays_scoped_to_the_same_aroma(): void
    {
        $view = $this->viewSource();

        $this->assertStringContainsString('filterSamePackageMaterialFamily(currentProduct, candidates)', $view);
        $this->assertStringNotContainsString('filterSameBrandFamily(currentProduct, candidates)', $view);
    }

    public function test_same_aroma_different_sizes_are_recognized_as_matching(): void
    {
        $current = $this->aromaBaseName('Fragrance Amberwood Sport Mix 100 ml');

        foreach ([
            'Fragrance Amberwood Sport Mix 30 ml',
            'Fragrance Amberwood Sport Mix 50 ml',
            'Fragrance Amberwood Sport Mix 250 ml',
            'Fragrance Amberwood Sport Mix 500 ml',
            'Fragrance Amberwood Sport Mix 1000 ml',
        ] as $sizeVariant) {
            $this->assertSame(
                $current,
                $this->aromaBaseName($sizeVariant),
                "Size variant '{$sizeVariant}' must be recognized as the same aroma as the 100ml product."
            );
        }
    }

    public function test_base_name_stripping_handles_decimal_and_bracketed_sizes(): void
    {
        $this->assertSame(
            'Fragrance Coffee Mix More',
            $this->aromaBaseName('Fragrance Coffee Mix More 2.5 ml')
        );
        $this->assertSame(
            'Fragrance Coffee Mix More',
            $this->aromaBaseName('Fragrance Coffee Mix More [100 ml]')
        );
    }
}
