<?php

namespace Tests\Feature;

use App\Http\Controllers\Warehouse\MasterProductController;
use App\Http\Controllers\Warehouse\ProductStructureController;
use App\Models\ProductCategory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Jawaban klien 26 Nov 2026: unit Master Product mengikuti Product Category.
 * Category Air Purifier ber-unit pcs -> semua produknya pcs dan tidak bisa
 * diubah di produk; perubahan hanya lewat category. Category tanpa unit ->
 * produk tetap boleh punya unit sendiri.
 */
class MasterProductUnitFollowsCategoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('option_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('master_option_id');
            $table->string('option_name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->string('sku_prefix')->nullable();
            $table->string('unit')->nullable();
            $table->boolean('has_serial_number')->default(false);
            $table->boolean('is_unit')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('product_category_id')->nullable();
            $table->string('unit')->nullable();
            $table->integer('minimum_stock')->default(0);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        foreach (['pcs', 'unit', 'box'] as $unit) {
            DB::table('option_details')->insert(['master_option_id' => 46, 'option_name' => $unit, 'is_active' => true]);
        }

        DB::table('product_categories')->insert([
            ['id' => 1, 'code' => 'AP', 'name' => 'Air Purifier', 'parent_id' => null, 'unit' => 'unit', 'sort_order' => 0, 'is_active' => true],
            ['id' => 2, 'code' => 'MISC', 'name' => 'Lain-lain', 'parent_id' => null, 'unit' => null, 'sort_order' => 0, 'is_active' => true],
            ['id' => 3, 'code' => 'BT', 'name' => 'Battery', 'parent_id' => null, 'unit' => 'box', 'sort_order' => 0, 'is_active' => true],
        ]);

        DB::table('master_products')->insert([
            ['id' => 10, 'name' => 'Air Purifier A', 'product_category_id' => 1, 'unit' => 'unit', 'minimum_stock' => 7, 'deleted_at' => null],
            ['id' => 11, 'name' => 'Air Purifier B', 'product_category_id' => 1, 'unit' => 'box', 'minimum_stock' => 3, 'deleted_at' => null],
            ['id' => 12, 'name' => 'Air Purifier lama', 'product_category_id' => 1, 'unit' => 'unit', 'minimum_stock' => 0, 'deleted_at' => now()],
            ['id' => 20, 'name' => 'Battery AA', 'product_category_id' => 3, 'unit' => 'box', 'minimum_stock' => 5, 'deleted_at' => null],
        ]);
    }

    protected function tearDown(): void
    {
        foreach (['master_products', 'product_categories', 'option_details', 'users'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_enforced_unit_is_the_category_unit_or_null(): void
    {
        $this->assertSame('unit', ProductCategory::enforcedUnitFor(1));
        $this->assertNull(ProductCategory::enforcedUnitFor(2));
        $this->assertNull(ProductCategory::enforcedUnitFor(null));
        $this->assertNull(ProductCategory::enforcedUnitFor(999));
    }

    public function test_product_form_unit_is_replaced_by_the_category_unit(): void
    {
        $request = Request::create('/warehouse/master-products', 'POST', [
            'product_category_id' => 1,
            'unit' => 'box',
        ]);

        $this->invokeApplyCategoryUnit($request);

        $this->assertSame('unit', $request->input('unit'));
    }

    public function test_product_form_unit_is_kept_when_category_has_no_unit(): void
    {
        $request = Request::create('/warehouse/master-products', 'POST', [
            'product_category_id' => 2,
            'unit' => 'box',
        ]);

        $this->invokeApplyCategoryUnit($request);

        $this->assertSame('box', $request->input('unit'));
    }

    public function test_store_and_update_apply_the_category_unit_before_validation(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Warehouse/MasterProductController.php'));

        foreach (['public function store(Request $request)', 'public function update(Request $request'] as $signature) {
            $start = strpos($source, $signature);
            $this->assertNotFalse($start, $signature);
            $body = substr($source, $start, 1200);
            $applyAt = strpos($body, '$this->applyCategoryUnit($request);');
            $validateAt = strpos($body, '$request->validate([');
            $this->assertNotFalse($applyAt, $signature . ' harus memanggil applyCategoryUnit');
            $this->assertLessThan($validateAt, $applyAt, $signature . ' harus menerapkan unit category sebelum validasi');
        }
    }

    public function test_cascade_updates_every_product_of_the_category_only(): void
    {
        $category = ProductCategory::find(1);
        $category->unit = 'pcs';
        $category->saveQuietly();

        $this->assertSame(3, $category->cascadeUnitToProducts());

        $this->assertSame(['pcs'], DB::table('master_products')->where('product_category_id', 1)->distinct()->pluck('unit')->all());
        // Kategori lain tidak tersentuh.
        $this->assertSame('box', DB::table('master_products')->where('id', 20)->value('unit'));
        // Qty tidak dikonversi.
        $this->assertSame(7, (int) DB::table('master_products')->where('id', 10)->value('minimum_stock'));
    }

    public function test_product_structure_update_cascades_and_reports_the_count(): void
    {
        $response = app(ProductStructureController::class)->updateCategory($this->categoryRequest(1, 'pcs'), 1);
        $payload = $response->getData(true);

        $this->assertSame('success', $payload['status'], json_encode($payload));
        $this->assertSame(3, $payload['products_unit_updated']);
        $this->assertStringContainsString('3 produk', $payload['message']);
        $this->assertSame(0, DB::table('master_products')->where('product_category_id', 1)->where('unit', '!=', 'pcs')->count());
    }

    public function test_product_structure_update_without_unit_change_touches_nothing(): void
    {
        $response = app(ProductStructureController::class)->updateCategory($this->categoryRequest(1, 'unit'), 1);
        $payload = $response->getData(true);

        $this->assertSame('success', $payload['status'], json_encode($payload));
        $this->assertSame(0, $payload['products_unit_updated']);
        // Produk yang dulu beda unit tetap tidak diubah tanpa perubahan unit category.
        $this->assertSame('box', DB::table('master_products')->where('id', 11)->value('unit'));
    }

    public function test_clearing_the_category_unit_does_not_blank_product_units(): void
    {
        $response = app(ProductStructureController::class)->updateCategory($this->categoryRequest(3, null), 3);
        $payload = $response->getData(true);

        $this->assertSame('success', $payload['status'], json_encode($payload));
        $this->assertSame(0, $payload['products_unit_updated']);
        $this->assertSame('box', DB::table('master_products')->where('id', 20)->value('unit'));
    }

    private function categoryRequest(int $id, ?string $unit): Request
    {
        $row = DB::table('product_categories')->find($id);

        return Request::create('/warehouse/product-structure/categories/' . $id, 'PUT', [
            'code' => $row->code,
            'name' => $row->name,
            'sort_order' => 0,
            'is_active' => true,
            'unit' => $unit,
        ]);
    }

    private function invokeApplyCategoryUnit(Request $request): void
    {
        $controller = app(MasterProductController::class);
        $method = new \ReflectionMethod($controller, 'applyCategoryUnit');
        $method->setAccessible(true);
        $method->invoke($controller, $request);
    }
}
