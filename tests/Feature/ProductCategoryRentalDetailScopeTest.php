<?php

namespace Tests\Feature;

use App\Models\ProductCategory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductCategoryRentalDetailScopeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('parent_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_category_id')->nullable();
            $table->boolean('is_active')->default(true);
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

    public function test_root_category_with_active_products_is_available_for_rental_detail(): void
    {
        $categoryId = $this->insertCategory('Aroma Delivery Sys Svc');
        $this->insertProduct($categoryId, true);

        $this->assertTrue(
            ProductCategory::availableForRentalDetail()->whereKey($categoryId)->exists()
        );
    }

    public function test_child_category_remains_available_without_products(): void
    {
        $parentId = $this->insertCategory('Aroma Delivery System');
        $childId = $this->insertCategory('Dispenser', $parentId);

        $this->assertTrue(
            ProductCategory::availableForRentalDetail()->whereKey($childId)->exists()
        );
    }

    public function test_empty_root_and_root_with_only_inactive_products_are_not_available(): void
    {
        $emptyRootId = $this->insertCategory('Empty Parent');
        $inactiveProductRootId = $this->insertCategory('Inactive Product Parent');
        $this->insertProduct($inactiveProductRootId, false);

        $availableIds = ProductCategory::availableForRentalDetail()->pluck('id');

        $this->assertNotContains($emptyRootId, $availableIds);
        $this->assertNotContains($inactiveProductRootId, $availableIds);
    }

    private function insertCategory(string $name, ?int $parentId = null): int
    {
        return DB::table('product_categories')->insertGetId([
            'name' => $name,
            'parent_id' => $parentId,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertProduct(int $categoryId, bool $isActive): int
    {
        return DB::table('master_products')->insertGetId([
            'product_category_id' => $categoryId,
            'is_active' => $isActive,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
