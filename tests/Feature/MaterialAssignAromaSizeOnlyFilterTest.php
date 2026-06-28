<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * QA bug: the Material dropdown for an aroma/fragrance row mixed in
 * completely unrelated aromas (e.g. selecting "Fragrance Amberwood Sport Mix
 * 100 ml" also showed "Fragrance Garden Mix" sizes). Root cause:
 * master_products.variant_name is a generic brand-line code ("Luxo GHI",
 * "Artisan DEF") shared by MULTIPLE distinct aromas in the same brand line,
 * not a per-aroma identifier — filtering on it alone could not tell "Loco
 * Floral" apart from "Ginger Blossom", or "Garden Mix" from "Amberwood Sport
 * Mix". Fix: group by the product NAME with the size (ml) suffix stripped,
 * which is the only field that actually identifies a specific aroma.
 *
 * These tests exercise the exact base-name regex used in
 * job-assign-material-issues/index.blade.php so the stripping logic itself
 * is locked in, independent of the full page render (which needs a large
 * relation graph: JobAssignSchedule -> JobSchedule -> JobAdvice -> rooms ->
 * rentalProduct -> rentalDetails -> allowedProducts).
 */
class MaterialAssignAromaSizeOnlyFilterTest extends TestCase
{
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

    public function test_blade_view_groups_aroma_dropdown_by_stripped_product_name_not_variant_name(): void
    {
        $view = file_get_contents(resource_path('views/operational/job-assign-material-issues/index.blade.php'));

        // The fix must compute a base-name grouping key and use it as the
        // primary same-aroma check, not variant_name alone.
        $this->assertStringContainsString('$normalizedCurrentBaseName', $view);
        $this->assertStringContainsString('$normalizedProductBaseName', $view);
        $this->assertStringContainsString('$normalizedProductBaseName === $normalizedCurrentBaseName', $view);
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

    /**
     * The exact QA-reported case: a "Fragrance Amberwood Sport Mix" row must
     * never match "Fragrance Garden Mix" sizes, even though both products
     * share the SAME variant_name ("Artisan DEF") and brand_line in the
     * staging database.
     */
    public function test_different_aroma_sharing_same_variant_name_is_not_treated_as_a_match(): void
    {
        $current = $this->aromaBaseName('Fragrance Amberwood Sport Mix 100 ml');
        $unrelatedAroma = $this->aromaBaseName('Fragrance Garden Mix 100 ml');

        $this->assertNotSame(
            $current,
            $unrelatedAroma,
            'Two different aromas must not collapse to the same base name even when they share a generic variant_name/brand_line.'
        );
    }

    /**
     * Same scenario for the other real pair found in staging data: "Loco
     * Floral" and "Ginger Blossom" both carry variant_name "Luxo GHI".
     */
    public function test_loco_floral_and_ginger_blossom_are_not_treated_as_the_same_aroma(): void
    {
        $locoFloral = $this->aromaBaseName('Fragrance Loco Floral 50 ml');
        $gingerBlossom = $this->aromaBaseName('Fragrance Ginger Blossom 50 ml');

        $this->assertNotSame($locoFloral, $gingerBlossom);
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
