<?php

namespace Tests\Feature;

use App\Http\Controllers\Warehouse\ProductStructureController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * UAT 17 Nov 2026 (sesi siang): bug yang merusak data.
 */
class Uat17NovDataIntegrityTest extends TestCase
{
    protected function tearDown(): void
    {
        Schema::dropIfExists('product_categories');
        parent::tearDown();
    }

    public function test_inventory_receiving_number_from_complete_shipping_uses_the_request_branch(): void
    {
        // Dulu 7 argumen posisi: branch_id masuk ke slot warehouse_id -> kode cabang IRC
        // diambil dari gudang acak (JKT jadi MAK).
        $source = file_get_contents(app_path('Http/Controllers/Warehouse/InventoryRequestController.php'));

        $this->assertMatchesRegularExpression(
            "/generate\(\s*'inventory_receiving',[^;]*branchId:\s*\\\$inventoryRequest->branch_id\s*\)/s",
            $source
        );
    }

    public function test_inventory_request_import_commits_once_after_all_groups(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Warehouse/InventoryRequestImportController.php'));
        $loopStart = strpos($source, 'foreach ($validatedGroups as $validatedGroup)');
        $this->assertNotFalse($loopStart);
        $catch = strpos($source, 'catch (\Throwable $e)', $loopStart);

        $this->assertSame(1, substr_count(substr($source, $loopStart, $catch - $loopStart), 'DB::commit()'));
    }

    private function seedCategoryTree(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
        // Desktop/Wall/AHU -> Difusser -> ADS (3 level, seperti data seed).
        DB::table('product_categories')->insert([
            ['id' => 1, 'code' => 'DWA', 'name' => 'Desktop/Wall/AHU', 'parent_id' => null],
            ['id' => 2, 'code' => 'DIF', 'name' => 'Difusser', 'parent_id' => 1],
            ['id' => 3, 'code' => 'ADS', 'name' => 'ADS', 'parent_id' => 2],
            ['id' => 4, 'code' => 'PWR', 'name' => 'Power', 'parent_id' => null],
        ]);
    }

    private function callPrivate(string $method, ...$args)
    {
        $controller = app(ProductStructureController::class);
        $reflection = new \ReflectionMethod($controller, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($controller, ...$args);
    }

    public function test_parent_dropdown_lists_every_level_so_a_level_three_category_keeps_its_parent(): void
    {
        $this->seedCategoryTree();

        $options = $this->callPrivate('parentCategoryOptions');

        // Dulu hanya level atas (1, 4): parent kategori ADS (2) tak ada di dropdown, form edit
        // kosong, dan simpan menjadikan ADS kategori level atas.
        $this->assertSame([1, 2, 3, 4], $options->pluck('id')->map(fn ($id) => (int) $id)->all());
        $ads = $options->firstWhere('id', 3);
        $this->assertSame(2, $ads->tree_level);
        $this->assertSame([1, 2], $ads->ancestor_path);
    }

    public function test_category_cannot_be_moved_under_itself_or_its_descendant(): void
    {
        $this->seedCategoryTree();

        $this->assertTrue($this->callPrivate('wouldCreateCategoryCycle', 1, 1));
        $this->assertTrue($this->callPrivate('wouldCreateCategoryCycle', 1, 3));
        $this->assertFalse($this->callPrivate('wouldCreateCategoryCycle', 3, 4));
        $this->assertFalse($this->callPrivate('wouldCreateCategoryCycle', 2, null));
    }
}
