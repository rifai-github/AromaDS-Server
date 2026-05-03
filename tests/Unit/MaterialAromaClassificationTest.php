<?php

namespace Tests\Unit;

use App\Http\Controllers\Operational\JobAssignMaterialIssueController;
use App\Http\Controllers\Operational\JobScheduleController;
use App\Models\MasterProduct;
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

    public function test_job_assign_material_issue_still_filters_aroma_products_by_quotation_variant(): void
    {
        $controller = new JobAssignMaterialIssueController();
        $method = (new ReflectionClass($controller))->getMethod('isAromaMaterialProduct');
        $method->setAccessible(true);

        $product = $this->product('White Rose 100ml', 'ART-WRO-100', 'Aroma Refill', 'Aroma Refill', 'Artisan');

        $this->assertTrue($method->invoke($controller, $product));
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

    private function product(
        string $name,
        string $sku,
        ?string $categoryName,
        ?string $typeName,
        ?string $brandLine = null
    ): MasterProduct {
        $product = new MasterProduct([
            'name' => $name,
            'sku' => $sku,
            'brand_line' => $brandLine,
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
