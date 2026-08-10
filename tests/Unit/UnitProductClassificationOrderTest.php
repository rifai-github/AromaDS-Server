<?php

namespace Tests\Unit;

use App\Http\Controllers\Operational\JobScheduleController;
use App\Models\MasterProduct;
use App\Models\ProductCategory;
use App\Models\ProductType;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Locks the resolution ORDER of isUnitProductByCategory().
 *
 * The keyword check used to run before is_unit, which forced every product whose
 * category/type/name mentioned "hand sanitizer" / "refill" / "cleaner" to non-unit.
 * That misclassified 16 physical, serial-number-tracked dispensers (e.g.
 * "Dispenser Hand Sanitizer 7600S--A" in category "Hand Sanitizer Disp"), so
 * autoCreateMaterialIssue()'s install-job filter skipped them and they could never be
 * issued on an IR job. Reported by QA on contract SMG-AG/25-04/0013, rental #3.
 *
 * Correct order: product category is_unit -> product type is_unit -> name keywords.
 */
class UnitProductClassificationOrderTest extends TestCase
{
    public function test_dispenser_in_unit_category_is_a_unit_despite_hand_sanitizer_name(): void
    {
        // The reported bug. Both the category name AND the product name contain
        // "Hand Sanitizer", so the old keyword-first order could never classify it correctly.
        $product = $this->product(
            name: 'Dispenser Hand Sanitizer 7600S--A',
            categoryName: 'Hand Sanitizer Disp',
            categoryIsUnit: true,
        );

        $this->assertTrue($this->isUnit($product));
    }

    public function test_hand_sanitizer_refill_in_non_unit_category_is_not_a_unit(): void
    {
        // The case the keyword rule was presumably guarding: liquid refill stays non-unit,
        // now decided by is_unit rather than by its name.
        $product = $this->product(
            name: 'Hand Sanitizer 1000ml (7100--L-)',
            categoryName: 'PURE Hand Sanitizer W/DISP Svc',
            categoryIsUnit: false,
        );

        $this->assertFalse($this->isUnit($product));
    }

    public function test_category_is_unit_wins_over_product_type(): void
    {
        $product = $this->product(
            name: 'ADS Dispenser ADS100 Bluetooth Black',
            categoryName: 'Diffuser',
            categoryIsUnit: true,
            typeName: 'Refill',
            typeIsUnit: false,
        );

        $this->assertTrue($this->isUnit($product));
    }

    public function test_product_type_is_used_when_there_is_no_category(): void
    {
        $product = $this->product(
            name: 'Legacy Diffuser Unit',
            categoryName: null,
            categoryIsUnit: null,
            typeName: 'Diffuser',
            typeIsUnit: true,
        );

        $this->assertTrue($this->isUnit($product));
    }

    public function test_name_keywords_still_apply_when_no_category_and_no_type(): void
    {
        // Keyword fallback must stay alive for legacy rows with neither category nor type.
        $product = $this->product(
            name: 'Aroma Refill 100ml',
            categoryName: null,
            categoryIsUnit: null,
        );

        $this->assertFalse($this->isUnit($product));
    }

    public function test_null_product_is_not_a_unit(): void
    {
        $this->assertFalse($this->isUnit(null));
    }

    private function isUnit(?MasterProduct $product): bool
    {
        $controller = new JobScheduleController;
        $method = (new ReflectionClass($controller))->getMethod('isUnitProductByCategory');
        $method->setAccessible(true);

        return $method->invoke($controller, $product);
    }

    private function product(
        string $name,
        ?string $categoryName,
        ?bool $categoryIsUnit,
        ?string $typeName = null,
        ?bool $typeIsUnit = null,
    ): MasterProduct {
        $product = new MasterProduct(['name' => $name]);

        $product->setRelation('productCategory', $categoryName === null ? null : new ProductCategory([
            'name' => $categoryName,
            'is_unit' => $categoryIsUnit,
        ]));

        $product->setRelation('productType', $typeName === null ? null : new ProductType([
            'name' => $typeName,
            'is_unit' => $typeIsUnit,
        ]));

        return $product;
    }
}
