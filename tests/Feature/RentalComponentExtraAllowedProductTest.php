<?php

namespace Tests\Feature;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Jawaban klien 26 Nov 2026:
 *
 * 1. Material List komponen rental punya 2 bagian: per kategori dan per produk, dan "per produk"
 *    berarti SEMUA produk dari Master Product. Lewat ikon pensil komponen boleh berisi kategori +
 *    satu produk lain (mis. kategori coklat + 1 strawberry).
 * 2. Material Assign: produk yang sengaja diizinkan itu "boleh dikecualikan" dari aturan brand line
 *    (aturan klien Sep 2026) - tetap bisa dipilih walau di luar brand line / kategori. Produk yang
 *    sekategori tetap ikut aturan brand line.
 */
class RentalComponentExtraAllowedProductTest extends TestCase
{
    private function materialAssignView(): string
    {
        return file_get_contents(resource_path('views/operational/job-assign-material-issues/index.blade.php'));
    }

    private function masterRentalView(): string
    {
        return file_get_contents(resource_path('views/warehouse/master-rentals/show.blade.php'));
    }

    public function test_material_list_modal_starts_on_all_master_products(): void
    {
        $view = $this->masterRentalView();

        $this->assertStringContainsString("populateMaterialCategoryFilter('');", $view);
        $this->assertStringNotContainsString('populateMaterialCategoryFilter(globalOwnCategoryId);', $view);
        $this->assertStringContainsString('2. Pilih Produk Satuan (semua Master Product)', $view);
    }

    public function test_pencil_modal_lists_every_master_product_after_the_component_category(): void
    {
        $view = $this->masterRentalView();

        $this->assertStringContainsString(
            'const others = masterProductsCatalog.filter(product => !listedIds.has(String(product.id)));',
            $view
        );
        $this->assertStringContainsString('populateProductSelections(products.concat(others), preservedSelectedIds);', $view);
        $this->assertStringContainsString("'Produk lain (semua Master Product)'", $view);

        // "Select All in Category" tidak boleh ikut mencentang produk kategori lain yang kini ada di dropdown.
        $this->assertStringNotContainsString("\$('#master_product_ids option').prop('selected', true);", $view);
        $this->assertStringContainsString('const categoryIds = new Set(getAllCurrentCategoryProductIds()', $view);
    }

    public function test_blade_dropdown_exempts_explicit_extra_allowed_products(): void
    {
        $collapsed = preg_replace('/\s+/', ' ', $this->materialAssignView());

        $this->assertStringContainsString('$explicitExtraAllowedProductIds', $collapsed);
        $this->assertStringContainsString(
            '!in_array((int) $p->product_category_id, $ownMaterialCategoryIds, true)',
            $collapsed
        );
        $this->assertStringContainsString(
            'if (in_array((int) $p->id, $explicitExtraAllowedProductIds, true)) { return true; }',
            $collapsed
        );
    }

    public function test_js_brand_filter_keeps_extra_allowed_products_and_still_scopes_the_rest(): void
    {
        $result = $this->runFilterScript(<<<'JS'
            const products = [
                { id: 1, name: 'Fragrance Coklat 100 ml', brand_line: 'Artisan', product_category_id: 10 },
                { id: 2, name: 'Fragrance Vanilla 100 ml', brand_line: 'Artisan', product_category_id: 10 },
                { id: 3, name: 'Fragrance Mint 100 ml', brand_line: 'Signature', product_category_id: 10 },
                { id: 4, name: 'Fragrance Strawberry 100 ml', brand_line: 'Luxo', product_category_id: 20 },
                { id: 5, name: 'Fragrance Melon 100 ml', brand_line: 'Luxo', product_category_id: 20 },
            ];
            window.availableProducts = products;
            const allowed = ['1', '2', '3', '4'];
            const candidates = products.filter(p => allowed.includes(String(p.id)));
            const current = products[0];

            // item.product dari server hanya membawa id/nama; kategorinya dicari di availableProducts.
            const extras = rentalExtraAllowedProductIds(candidates, allowed, null, { id: 1, name: 'Fragrance Coklat 100 ml' });
            const withExtras = filterSameBrandFamily(current, candidates, extras).map(p => p.id);
            const strict = filterSameBrandFamily(current, candidates).map(p => p.id);
            const noCategory = rentalExtraAllowedProductIds(candidates, allowed, null, { id: 99, name: 'x' });
            console.log(JSON.stringify({ extras, withExtras, strict, noCategory }));
        JS);

        // Strawberry (kategori lain, sengaja diizinkan) dikecualikan; Melon tidak diizinkan.
        $this->assertSame(['4'], $result['extras']);
        $this->assertSame([1, 2, 4], $result['withExtras']);
        // Mint: sekategori tapi beda brand line -> aturan brand line Sep 2026 tetap berlaku.
        $this->assertNotContains(3, $result['withExtras']);
        // Tanpa pengecualian perilaku lama tidak berubah.
        $this->assertSame([1, 2], $result['strict']);
        // Tanpa kategori acuan tidak ada yang dikecualikan (jangan sampai mematikan aturan brand line).
        $this->assertSame([], $result['noCategory']);
    }

    private function runFilterScript(string $assertions): array
    {
        $node = (new ExecutableFinder())->find('node');
        if (!$node) {
            $this->markTestSkipped('node tidak tersedia');
        }

        $view = $this->materialAssignView();
        $functions = [
            'normalizeProductBrandLine',
            'normalizePackageMaterialFamily',
            'isPackageConversionMaterialProduct',
            'normalizeProductCategoryId',
            'filterSameBrandFamily',
            'rentalExtraAllowedProductIds',
            'filterSameBrandFamilyStrict',
            'filterSamePackageMaterialFamily',
        ];

        $script = "const window = {};\n";
        foreach ($functions as $name) {
            $script .= $this->extractJsFunction($view, $name) . "\n";
        }
        $script .= $assertions;

        $file = tempnam(sys_get_temp_dir(), 'ma-filter-') . '.js';
        file_put_contents($file, $script);

        try {
            $process = new Process([$node, $file]);
            $process->run();
            $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());

            return json_decode(trim($process->getOutput()), true);
        } finally {
            @unlink($file);
        }
    }

    private function extractJsFunction(string $source, string $name): string
    {
        $start = strpos($source, "function {$name}(");
        $this->assertNotFalse($start, "function {$name} tidak ditemukan");

        $open = strpos($source, '{', $start);
        $depth = 0;
        $length = strlen($source);
        for ($i = $open; $i < $length; $i++) {
            if ($source[$i] === '{') {
                $depth++;
            } elseif ($source[$i] === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($source, $start, $i - $start + 1);
                }
            }
        }

        $this->fail("function {$name} tidak tertutup");
    }
}
