<?php

namespace Tests\Feature;

use App\Http\Controllers\Marketing\AromaChangeController;
use App\Models\AromaChange;
use App\Models\MasterProduct;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AromaChangeGradeApprovalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('variant_name')->nullable();
            $table->string('brand_line')->nullable();
            $table->string('sku')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('master_products')->insert([
            ['id' => 1, 'name' => 'Fragrance Ginger Blossom', 'variant_name' => 'Luxo', 'brand_line' => 'Luxo', 'sku' => 'REFGINGER', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Fragrance White Rose', 'variant_name' => 'Artisan', 'brand_line' => 'Artisan', 'sku' => 'REFROSE', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Fragrance Lemongrass Mix', 'variant_name' => 'Signature', 'brand_line' => 'Signature', 'sku' => 'REFLEMONGRASS', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'Fragrance Coffee Mix More', 'variant_name' => 'Signature', 'brand_line' => 'Signature', 'sku' => 'REFCOFFEE', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'Fragrance Unknown Line', 'variant_name' => 'Mystery', 'brand_line' => 'Mystery', 'sku' => 'REFMYSTERY', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('master_products');

        parent::tearDown();
    }

    private function decide(int $previousId, int $newId): array
    {
        $controller = new AromaChangeController();
        $method = new \ReflectionMethod($controller, 'resolveGradeApprovalDecision');
        $method->setAccessible(true);

        return $method->invoke(
            $controller,
            MasterProduct::find($previousId),
            MasterProduct::find($newId)
        );
    }

    public function test_brand_line_grade_order_matches_client_confirmed_ranking(): void
    {
        $this->assertSame(1, AromaChange::brandLineGrade('Luxo'));
        $this->assertSame(2, AromaChange::brandLineGrade('Artisan'));
        $this->assertSame(3, AromaChange::brandLineGrade('Signature'));
        $this->assertNull(AromaChange::brandLineGrade('Mystery'));
        $this->assertNull(AromaChange::brandLineGrade(null));
    }

    public function test_upgrading_grade_is_auto_approved(): void
    {
        // Luxo -> Artisan (up)
        [$status, $isAutoApproved] = $this->decide(1, 2);
        $this->assertSame(AromaChange::STATUS_APPROVED, $status);
        $this->assertTrue($isAutoApproved);

        // Artisan -> Signature (up)
        [$status, $isAutoApproved] = $this->decide(2, 3);
        $this->assertSame(AromaChange::STATUS_APPROVED, $status);
        $this->assertTrue($isAutoApproved);

        // Luxo -> Signature (up, skips a level)
        [$status, $isAutoApproved] = $this->decide(1, 3);
        $this->assertSame(AromaChange::STATUS_APPROVED, $status);
        $this->assertTrue($isAutoApproved);
    }

    public function test_downgrading_grade_requires_approval(): void
    {
        // Signature -> Artisan (down)
        [$status, $isAutoApproved] = $this->decide(3, 2);
        $this->assertSame(AromaChange::STATUS_PENDING, $status);
        $this->assertFalse($isAutoApproved);

        // Artisan -> Luxo (down)
        [$status, $isAutoApproved] = $this->decide(2, 1);
        $this->assertSame(AromaChange::STATUS_PENDING, $status);
        $this->assertFalse($isAutoApproved);

        // Signature -> Luxo (down, skips a level)
        [$status, $isAutoApproved] = $this->decide(3, 1);
        $this->assertSame(AromaChange::STATUS_PENDING, $status);
        $this->assertFalse($isAutoApproved);
    }

    public function test_same_grade_switch_is_auto_approved(): void
    {
        // Signature -> Signature (different variant within same grade)
        [$status, $isAutoApproved] = $this->decide(3, 4);
        $this->assertSame(AromaChange::STATUS_APPROVED, $status);
        $this->assertTrue($isAutoApproved);
    }

    public function test_unknown_brand_line_requires_approval_instead_of_auto_approving(): void
    {
        [$status, $isAutoApproved] = $this->decide(5, 1);
        $this->assertSame(AromaChange::STATUS_PENDING, $status);
        $this->assertFalse($isAutoApproved);

        [$status, $isAutoApproved] = $this->decide(1, 5);
        $this->assertSame(AromaChange::STATUS_PENDING, $status);
        $this->assertFalse($isAutoApproved);
    }
}
