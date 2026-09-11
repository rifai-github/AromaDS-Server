<?php

namespace Tests\Unit;

use App\Http\Controllers\Operational\JobAssignMaterialIssueController;
use App\Http\Controllers\Operational\JobScheduleController;
use App\Models\MasterProduct;
use App\Models\ProductCategory;
use App\Models\ProductType;
use App\Models\RentalDetail;
use ReflectionClass;
use Tests\TestCase;

/**
 * QA 10 Sep 2026: rental "ADS 005 1Bln 1x 100ml" has a BOM of Diffuser + Refill, but
 * Material Assign rendered TWO refill rows. Root cause: the "is this an aroma slot?"
 * classifiers scan a text haystack that includes the product's CATEGORY NAME, and the
 * Rental-w/QR service products sit in categories literally named "Aroma Delivery Sys Svc"
 * and "Aroma Delivery System Paket Jasa Scenting ... Pure Scenting". The substring "aroma"
 * matched, the unit slot was classified as an aroma slot, and the caller substituted the
 * diffuser away into the quotation's refill.
 *
 * On QA this hit 129 of 944 rental_details slots: 17 rentals rendered the reported two
 * refill rows, and 112 more silently lost their unit row entirely.
 *
 * The fix is an is_unit guard on the SLOT, applied before the keyword scan. These tests
 * lock both the guard and the keyword behaviour it must not break.
 */
class AromaSlotUnitGuardTest extends TestCase
{
    /**
     * Unsaved models with relations pre-set — no DB needed, and the MA classifier is
     * typed `?MasterProduct` so plain stand-in objects will not do.
     */
    private function category(?string $name, ?int $isUnit): ProductCategory
    {
        return (new ProductCategory)->forceFill(['name' => $name, 'is_unit' => $isUnit]);
    }

    private function type(?string $name, ?int $isUnit): ProductType
    {
        return (new ProductType)->forceFill(['name' => $name, 'is_unit' => $isUnit]);
    }

    private function product(?ProductCategory $category, ?ProductType $type, string $name = '', string $sku = ''): MasterProduct
    {
        $product = (new MasterProduct)->forceFill([
            'name' => $name,
            'sku' => $sku,
            'brand_line' => null,
            'variant_name' => null,
        ]);

        return $product
            ->setRelation('productCategory', $category)
            ->setRelation('productType', $type);
    }

    private function detail(?ProductCategory $category, ?ProductType $type): RentalDetail
    {
        return (new RentalDetail)
            ->setRelation('productCategory', $category)
            ->setRelation('productType', $type);
    }

    private function callPrivate(object $target, string $method, array $args)
    {
        $m = (new ReflectionClass($target))->getMethod($method);
        $m->setAccessible(true);

        return $m->invokeArgs($target, $args);
    }

    private function isAromaSlot(object $detail, object $product): array
    {
        return [
            'job_schedule' => $this->callPrivate(
                app(JobScheduleController::class),
                'isAromaRentalDetail',
                [$detail, $product]
            ),
            'material_assign' => $this->callPrivate(
                app(JobAssignMaterialIssueController::class),
                'isQuotationAromaMaterialSlot',
                [$detail, $product, $product->productCategory->name ?? null]
            ),
        ];
    }

    public static function unitCategoryNamesThatContainAromaKeywords(): array
    {
        return [
            ['Aroma Delivery Sys Svc'],
            ['Aroma Delivery System Paket Jasa Scenting Meridien Pure Scenting'],
            ['Aroma Delivery System Paket Jasa Scenting Sky Pure Scenting'],
            ['Aroma Delivery System Paket Jasa Scenting Space Pure Scenting'],
            ['Aroma Delivery System Paket Jasa Scenting Micro Pure Scenting'],
            ['Aroma Delivery System Paket Jasa Scenting Atmos Large Pure S'],
        ];
    }

    /**
     * The exact production shape: category is_unit = 0 but product_type is_unit = 1.
     * A `??` chain or isUnitProductByCategory() would read this as NON-unit, because both
     * stop at the category's non-null 0 and never reach the type.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('unitCategoryNamesThatContainAromaKeywords')]
    public function test_rental_w_qr_unit_slot_is_not_an_aroma_slot(string $categoryName): void
    {
        $product = $this->product(
            $this->category($categoryName, 0),
            $this->type('Rental w/QR', 1),
            'ADS 005 1Bln 1x 100ml'
        );
        $detail = $this->detail($this->category('Diffuser', 1), null);

        foreach ($this->isAromaSlot($detail, $product) as $where => $isAroma) {
            $this->assertFalse(
                $isAroma,
                "[{$where}] a unit slot in category '{$categoryName}' must not be classified as an aroma slot."
            );
        }
    }

    /** The unit signal may sit on the product category instead. */
    public function test_diffuser_unit_category_is_not_an_aroma_slot(): void
    {
        $product = $this->product(
            $this->category('Diffuser', 1),
            $this->type('DIFFUSER', 1),
            'ADS Dispenser 005 Bluetooth (Black)',
            'DIS005BB'
        );
        $detail = $this->detail($this->category('Diffuser', 1), null);

        foreach ($this->isAromaSlot($detail, $product) as $where => $isAroma) {
            $this->assertFalse($isAroma, "[{$where}] a diffuser must not be an aroma slot.");
        }
    }

    /** The guard must not swallow genuine refills — that would break the substitution. */
    public function test_genuine_refill_slot_is_still_an_aroma_slot(): void
    {
        $product = $this->product(
            $this->category('REFILL', 0),
            $this->type('REFILL', 0),
            'Fragrance Ginger Blossom 50 ml',
            'REFGINGERBLOSSOM50'
        );
        $product->brand_line = 'Luxo';
        $product->variant_name = 'Luxo Gingger Blossom';
        $detail = $this->detail($this->category('REFILL', 0), $this->type('Aroma Refill', 0));

        foreach ($this->isAromaSlot($detail, $product) as $where => $isAroma) {
            $this->assertTrue($isAroma, "[{$where}] a genuine refill must still be an aroma slot.");
        }
    }

    /** Unknown is_unit (all null) must not be treated as a unit — keyword scan still decides. */
    public function test_null_is_unit_falls_through_to_keyword_classification(): void
    {
        $product = $this->product(
            $this->category('REFILL', null),
            $this->type(null, null),
            'Fragrance Garden Mix 100 ml'
        );
        $detail = $this->detail($this->category('REFILL', null), null);

        foreach ($this->isAromaSlot($detail, $product) as $where => $isAroma) {
            $this->assertTrue($isAroma, "[{$where}] null is_unit must not block a fragrance slot.");
        }
    }

    /** Hand sanitizer stays excluded — the pre-existing carve-out must survive the guard. */
    public function test_hand_sanitizer_is_still_not_an_aroma_slot(): void
    {
        $product = $this->product(
            $this->category('REFILL', 0),
            $this->type('REFILL', 0),
            'PURE Hand Sanitizer (Gel) 1000 ml',
            'REFHSG1000'
        );
        $detail = $this->detail($this->category('REFILL', 0), null);

        foreach ($this->isAromaSlot($detail, $product) as $where => $isAroma) {
            $this->assertFalse($isAroma, "[{$where}] hand sanitizer must not be an aroma slot.");
        }
    }
}
