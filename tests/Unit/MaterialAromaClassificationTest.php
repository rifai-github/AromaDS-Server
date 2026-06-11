<?php

namespace Tests\Unit;

use App\Http\Controllers\Operational\JobAssignMaterialIssueController;
use App\Http\Controllers\Operational\JobScheduleController;
use App\Models\MasterProduct;
use App\Models\MaterialIssueItem;
use App\Models\MasterRental;
use App\Models\ProductCategory;
use App\Models\ProductType;
use App\Models\RentalDetail;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class MaterialAromaClassificationTest extends TestCase
{
    public function test_job_schedule_does_not_treat_hand_sanitizer_refill_as_aroma_slot(): void
    {
        $controller = new JobScheduleController();
        $method = (new ReflectionClass($controller))->getMethod('isAromaRentalDetail');
        $method->setAccessible(true);

        $detail = $this->rentalDetail('HS Refill', null);
        $product = $this->product('Hand Sanitizer Gel', 'HSR-HAND-SANITIZER-GEL-1000', 'HS Refill', 'Hand Sanitizer Refill');

        $this->assertFalse($method->invoke($controller, $detail, $product));
    }

    public function test_job_schedule_still_treats_aroma_refill_as_aroma_slot(): void
    {
        $controller = new JobScheduleController();
        $method = (new ReflectionClass($controller))->getMethod('isAromaRentalDetail');
        $method->setAccessible(true);

        $detail = $this->rentalDetail('Aroma Refill', null);
        $product = $this->product('White Rose 100ml', 'ART-WRO-100', 'Aroma Refill', 'Aroma Refill', 'Artisan');

        $this->assertTrue($method->invoke($controller, $detail, $product));
    }

    public function test_job_assign_material_issue_does_not_filter_hand_sanitizer_as_aroma_product(): void
    {
        $controller = new JobAssignMaterialIssueController();
        $method = (new ReflectionClass($controller))->getMethod('isAromaMaterialProduct');
        $method->setAccessible(true);

        $product = $this->product('Hand Sanitizer Gel', 'HSR-HAND-SANITIZER-GEL-1000', 'HS Refill', 'Hand Sanitizer Refill');

        $this->assertFalse($method->invoke($controller, $product));
    }

    public function test_job_assign_material_issue_does_not_treat_hand_sanitizer_refill_as_quotation_aroma_slot(): void
    {
        $controller = new JobAssignMaterialIssueController();
        $method = (new ReflectionClass($controller))->getMethod('isQuotationAromaMaterialSlot');
        $method->setAccessible(true);

        $detail = $this->rentalDetail('HS Refill', null);
        $product = $this->product('Hand Sanitizer Gel', 'HSR-HAND-SANITIZER-GEL-1000', 'HS Refill', 'Hand Sanitizer Refill');

        $this->assertFalse($method->invoke($controller, $detail, $product, 'HS Refill'));
    }

    public function test_job_assign_material_issue_treats_ads_301_aroma_placeholder_as_quotation_aroma_slot(): void
    {
        $controller = new JobAssignMaterialIssueController();
        $method = (new ReflectionClass($controller))->getMethod('isQuotationAromaMaterialSlot');
        $method->setAccessible(true);

        $detail = $this->rentalDetail('Hand Sanitizer', null);
        $product = $this->product('Aroma Diffuser Premium', 'AD001', 'Hand Sanitizer', null);

        $this->assertTrue($method->invoke($controller, $detail, $product, 'Hand Sanitizer'));
    }

    public function test_job_assign_material_issue_still_filters_aroma_products_by_quotation_variant(): void
    {
        $controller = new JobAssignMaterialIssueController();
        $method = (new ReflectionClass($controller))->getMethod('isAromaMaterialProduct');
        $method->setAccessible(true);

        $product = $this->product('White Rose 100ml', 'ART-WRO-100', 'Aroma Refill', 'Aroma Refill', 'Artisan');

        $this->assertTrue($method->invoke($controller, $product));
    }

    public function test_job_assign_material_issue_allows_copy_for_refill_package_conversion(): void
    {
        $controller = new JobAssignMaterialIssueController();
        $method = (new ReflectionClass($controller))->getMethod('isPackageConversionMaterial');
        $method->setAccessible(true);

        $product = $this->product('Ginger Blossom 500ml', 'GB-500', 'Aroma Refill', null);

        $this->assertTrue($method->invoke($controller, $product));
    }

    public function test_job_assign_material_issue_does_not_allow_copy_for_sparepart(): void
    {
        $controller = new JobAssignMaterialIssueController();
        $method = (new ReflectionClass($controller))->getMethod('isPackageConversionMaterial');
        $method->setAccessible(true);

        $product = $this->product('Battery R20 ABC Alkaline', 'BAT-R20', 'Spare Part', 'Spare Part');

        $this->assertFalse($method->invoke($controller, $product));
    }

    public function test_job_assign_material_issue_package_split_must_keep_same_material_family(): void
    {
        $controller = new JobAssignMaterialIssueController();
        $method = (new ReflectionClass($controller))->getMethod('productsHaveSamePackageMaterialFamily');
        $method->setAccessible(true);

        $lemon500 = $this->product('Lemon Squash 500ml', 'LEM-500', 'Aroma Refill', null, null, 'Lemon Squash');
        $lemon500->id = 1;

        $lemon250 = $this->product('Lemon Squash 250ml', 'LEM-250', 'Aroma Refill', null, null, 'Lemon Squash');
        $lemon250->id = 2;

        $whiteRose50 = $this->product('White Rose 50ml', 'WRO-50', 'Aroma Refill', null, null, 'White Rose');
        $whiteRose50->id = 3;

        $cleaner100 = $this->product('Cleaner 100ml', 'CLN-100', 'Cleaner', 'Cleaner', null, 'Cleaner');
        $cleaner100->id = 4;

        $this->assertTrue($method->invoke($controller, $lemon500, $lemon250));
        $this->assertFalse($method->invoke($controller, $lemon500, $whiteRose50));
        $this->assertFalse($method->invoke($controller, $lemon500, $cleaner100));
    }

    public function test_job_assign_material_issue_package_split_allows_same_fragrance_with_empty_variant_name(): void
    {
        $controller = new JobAssignMaterialIssueController();
        $method = (new ReflectionClass($controller))->getMethod('productsHaveSamePackageMaterialFamily');
        $method->setAccessible(true);

        $lemongrass100 = $this->product('Fragrance Lemongrass Mix 100 ml', 'LEM-MIX-100', 'Aroma Refill', null);
        $lemongrass100->id = 11;

        $lemongrass50 = $this->product('Fragrance Lemongrass Mix 50 ml', 'LEM-MIX-50', 'Aroma Refill', null);
        $lemongrass50->id = 12;

        $coffee100 = $this->product('Fragrance Coffee Mix More 100 ml', 'COF-MIX-100', 'Aroma Refill', null);
        $coffee100->id = 13;

        $this->assertTrue($method->invoke($controller, $lemongrass100, $lemongrass50));
        $this->assertFalse($method->invoke($controller, $lemongrass100, $coffee100));
    }

    public function test_job_assign_material_issue_rejects_mixed_fragrance_for_same_room_rental(): void
    {
        $controller = new JobAssignMaterialIssueController();
        $method = (new ReflectionClass($controller))->getMethod('validateMaterialIssueItemsProductConsistency');
        $method->setAccessible(true);

        $lemongrass100 = $this->product('Fragrance Lemongrass Mix 100 ml', 'LEM-MIX-100', 'Aroma Refill', null);
        $lemongrass100->id = 21;

        $coffee100 = $this->product('Fragrance Coffee Mix More 100 ml', 'COF-MIX-100', 'Aroma Refill', null);
        $coffee100->id = 22;

        $errors = $method->invoke($controller, collect([
            $this->materialIssueItem('Ruang VIP', 'ADS W300 300 ml baterai', '9', $lemongrass100),
            $this->materialIssueItem('Ruang VIP', 'ADS W300 300 ml baterai', '9', $coffee100, true),
        ]), 'MI-TEST');

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Ruang VIP', $errors[0]);
        $this->assertStringContainsString('ADS W300 300 ml baterai', $errors[0]);
        $this->assertStringContainsString('Fragrance Lemongrass Mix 100 ml', $errors[0]);
        $this->assertStringContainsString('Fragrance Coffee Mix More 100 ml', $errors[0]);
    }

    public function test_job_assign_material_issue_allows_same_fragrance_different_package_for_same_room_rental(): void
    {
        $controller = new JobAssignMaterialIssueController();
        $method = (new ReflectionClass($controller))->getMethod('validateMaterialIssueItemsProductConsistency');
        $method->setAccessible(true);

        $lemongrass100 = $this->product('Fragrance Lemongrass Mix 100 ml', 'LEM-MIX-100', 'Aroma Refill', null);
        $lemongrass100->id = 31;

        $lemongrass50 = $this->product('Fragrance Lemongrass Mix 50 ml', 'LEM-MIX-50', 'Aroma Refill', null);
        $lemongrass50->id = 32;

        $errors = $method->invoke($controller, collect([
            $this->materialIssueItem('Ruang VIP', 'ADS W300 300 ml baterai', '9', $lemongrass100),
            $this->materialIssueItem('Ruang VIP', 'ADS W300 300 ml baterai', '9', $lemongrass50, true),
        ]), 'MI-TEST');

        $this->assertSame([], $errors);
    }

    public function test_job_schedule_uses_selected_material_list_product_when_detail_has_no_default_product(): void
    {
        $controller = new JobScheduleController();
        $method = (new ReflectionClass($controller))->getMethod('resolvePreferredRentalDetailProduct');
        $method->setAccessible(true);

        $detail = $this->rentalDetail('Cleaner', null);
        $detail->product_category_id = 22;

        $cleaner = $this->product('ADS Cleaner 500ml', 'CLN-500', 'Cleaner', 'Cleaner');
        $cleaner->id = 88;
        $cleaner->product_category_id = 22;
        $cleaner->setRelation('pivot', (object) [
            'is_selected' => true,
            'sort_order' => 1,
        ]);
        $detail->setRelation('allowedProducts', collect([$cleaner]));

        $rental = new MasterRental([
            'rental_name' => 'Wall Diffuser Unit + Refill',
        ]);

        $this->assertSame($cleaner, $method->invoke($controller, $detail, $rental, null));
    }

    private function rentalDetail(?string $categoryName, ?string $typeName): RentalDetail
    {
        $detail = new RentalDetail();

        if ($categoryName) {
            $detail->setRelation('productCategory', new ProductCategory(['name' => $categoryName]));
        } else {
            $detail->setRelation('productCategory', null);
        }

        if ($typeName) {
            $detail->setRelation('productType', new ProductType(['name' => $typeName]));
        } else {
            $detail->setRelation('productType', null);
        }

        return $detail;
    }

    private function materialIssueItem(
        string $roomName,
        string $rentalName,
        string $componentId,
        MasterProduct $product,
        bool $isCopied = false
    ): MaterialIssueItem {
        $item = new MaterialIssueItem([
            'room_name' => $roomName,
            'notes' => "Room: {$roomName}, Rental: {$rentalName}, ComponentID: {$componentId}" . ($isCopied ? ' [Copied]' : ''),
            'is_copied' => $isCopied,
        ]);
        $item->setRelation('product', $product);

        return $item;
    }

    private function product(
        string $name,
        string $sku,
        ?string $categoryName,
        ?string $typeName,
        ?string $brandLine = null,
        ?string $variantName = null
    ): MasterProduct {
        $product = new MasterProduct([
            'name' => $name,
            'sku' => $sku,
            'brand_line' => $brandLine,
            'variant_name' => $variantName,
        ]);

        if ($categoryName) {
            $product->setRelation('productCategory', new ProductCategory([
                'name' => $categoryName,
                'is_unit' => false,
                'has_serial_number' => false,
            ]));
        } else {
            $product->setRelation('productCategory', null);
        }

        if ($typeName) {
            $product->setRelation('productType', new ProductType([
                'name' => $typeName,
                'is_unit' => false,
                'has_serial_number' => false,
            ]));
        } else {
            $product->setRelation('productType', null);
        }

        return $product;
    }
}
