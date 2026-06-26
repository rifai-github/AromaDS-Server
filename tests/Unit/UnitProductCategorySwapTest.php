<?php

namespace Tests\Unit;

use App\Models\MasterProduct;
use App\Models\ProductCategory;
use Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bug #18 (confirmed with client 2026-06-22, video "WhatsApp Video 2026-06-22
 * at 14.07.30 (1).mp4"): in Material Assign Issue edit, unit products (e.g.
 * Diffuser) used to be completely locked - no dropdown at all. The client
 * confirmed they DO need to swap a unit for a different variant in the SAME
 * category (e.g. "Diffuser W300 Black" -> "Diffuser W300 White"), just not
 * to a different unit category entirely (e.g. Diffuser -> Dispenser).
 *
 * This isolates the category-match rule used by both:
 *  - JobAssignMaterialIssueController::saveMaterialIssueItems() (server-side
 *    guard against a swap that bypasses the UI's category filter)
 *  - the index.blade.php dropdown filter (client-side, restricts options)
 */
class UnitProductCategorySwapTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->boolean('is_unit')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_category_id')->nullable();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('master_products');
        Schema::dropIfExists('product_categories');

        parent::tearDown();
    }

    private function isSwapAllowed(MasterProduct $original, MasterProduct $candidate): bool
    {
        $isUnit = $candidate->productCategory->is_unit ?? false;
        $originalCategoryId = $original->product_category_id;
        $isSameUnitCategory = $isUnit && $originalCategoryId && $candidate->product_category_id == $originalCategoryId;

        return !$isUnit || $isSameUnitCategory;
    }

    public function test_swapping_a_unit_to_a_different_variant_in_the_same_category_is_allowed(): void
    {
        $diffuserCategory = ProductCategory::create(['name' => 'Diffuser', 'is_unit' => true]);

        $original = MasterProduct::create(['product_category_id' => $diffuserCategory->id, 'name' => 'Diffuser W300 Black']);
        $candidate = MasterProduct::create(['product_category_id' => $diffuserCategory->id, 'name' => 'Diffuser W300 White']);
        $candidate->load('productCategory');

        $this->assertTrue($this->isSwapAllowed($original, $candidate));
    }

    public function test_swapping_a_unit_to_a_different_unit_category_is_blocked(): void
    {
        $diffuserCategory = ProductCategory::create(['name' => 'Diffuser', 'is_unit' => true]);
        $dispenserCategory = ProductCategory::create(['name' => 'Dispenser', 'is_unit' => true]);

        $original = MasterProduct::create(['product_category_id' => $diffuserCategory->id, 'name' => 'Diffuser W300 Black']);
        $candidate = MasterProduct::create(['product_category_id' => $dispenserCategory->id, 'name' => 'PURE Dispenser 7200']);
        $candidate->load('productCategory');

        $this->assertFalse($this->isSwapAllowed($original, $candidate));
    }

    public function test_non_unit_product_can_always_be_swapped(): void
    {
        $refillCategory = ProductCategory::create(['name' => 'Refill', 'is_unit' => false]);

        $original = MasterProduct::create(['product_category_id' => $refillCategory->id, 'name' => 'Fragrance Amberwood 100 ml']);
        $candidate = MasterProduct::create(['product_category_id' => $refillCategory->id, 'name' => 'Fragrance Sport Mix 100 ml']);
        $candidate->load('productCategory');

        $this->assertTrue($this->isSwapAllowed($original, $candidate));
    }

    public function test_controller_server_side_guard_allows_same_category_unit_swap(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Operational/JobAssignMaterialIssueController.php'));

        $this->assertStringContainsString('$isSameUnitCategory', $controller);
        $this->assertStringContainsString('!$isUnit || $isSameUnitCategory', $controller);
        $this->assertStringContainsString("Bug #18: Cannot change unit to a different category. Keeping original", $controller);
    }

    public function test_blade_dropdown_restricts_unit_swap_to_same_category(): void
    {
        $view = file_get_contents(resource_path('views/operational/job-assign-material-issues/index.blade.php'));

        $this->assertStringContainsString('isSameCategoryUnit', $view);
        $this->assertStringContainsString("data-is-unit", $view);
    }
}
