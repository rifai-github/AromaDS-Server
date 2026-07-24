<?php

namespace Tests\Feature;

use App\Http\Controllers\Warehouse\StockAdjustmentController;
use App\Models\StockAdjustment;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA bug: creating a Stock Adjustment via the "Create New Stock Adjustment"
 * modal always submits a specific warehouse_id (it's a required <select>
 * populated from every active warehouse), but resolveWarehouseFromRequest()
 * discarded that pick and re-derived "the" warehouse from the branch via
 * BranchWarehouseResolver::resolveActiveForBranch(), which throws unless the
 * branch has exactly one active warehouse. Branches routinely have several
 * (e.g. separate barang-baru/bekas/rusak/spare-part/on-wall warehouses), so
 * this failed on every submission for those branches - confirmed on QA for
 * branch_id=2 (Gudang Surabaya + 5 sibling warehouses), reproducing exactly
 * the "Terjadi kesalahan. Gagal" the user saw.
 */
class StockAdjustmentWarehousePickerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('code')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('warehouse_code')->nullable();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_no')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->date('adjustment_date')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_adjustment_id')->nullable();
            $table->unsignedBigInteger('master_product_id')->nullable();
            $table->integer('adjustment_qty')->default(0);
            $table->string('adjustment_type')->nullable();
            $table->string('notes')->nullable();
            $table->json('serial_numbers')->nullable();
            $table->timestamps();
        });

        DB::table('users')->insert(['id' => 1, 'name' => 'Warehouse Manager']);
        Auth::login(User::findOrFail(1));
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('stock_adjustment_items');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_store_uses_the_exact_picked_warehouse_when_the_branch_has_several(): void
    {
        $branchId = DB::table('branches')->insertGetId(['name' => 'Cabang Surabaya', 'code' => 'SBY']);
        $main = Warehouse::create(['warehouse_code' => 'WH01', 'name' => 'Gudang Surabaya', 'branch_id' => $branchId, 'is_active' => true]);
        Warehouse::create(['warehouse_code' => 'WH02', 'name' => 'Gudang 1 (Barang Baru) - Surabaya', 'branch_id' => $branchId, 'is_active' => true]);
        Warehouse::create(['warehouse_code' => 'WH03', 'name' => 'Gudang 2 (Barang Bekas) - Surabaya', 'branch_id' => $branchId, 'is_active' => true]);

        $response = app(StockAdjustmentController::class)->store(Request::create('/warehouse/stock-adjustments', 'POST', [
            'warehouse_id' => $main->id,
            'reason' => 'test adjustment',
            'adjustment_date' => now()->toDateString(),
        ]));

        $payload = json_decode($response->getContent(), true);
        $this->assertSame(200, $response->getStatusCode(), $payload['message'] ?? json_encode($payload));

        $adjustment = StockAdjustment::first();
        $this->assertNotNull($adjustment);
        $this->assertSame($main->id, $adjustment->warehouse_id, 'Must use the exact warehouse the user picked, not a re-derived one.');
    }

    public function test_store_still_works_by_branch_fallback_when_the_branch_has_exactly_one_warehouse(): void
    {
        $branchId = DB::table('branches')->insertGetId(['name' => 'Cabang Bandung', 'code' => 'BDG']);
        $only = Warehouse::create(['warehouse_code' => 'WH10', 'name' => 'Gudang Bandung', 'branch_id' => $branchId, 'is_active' => true]);

        $response = app(StockAdjustmentController::class)->store(Request::create('/warehouse/stock-adjustments', 'POST', [
            'branch_id' => $branchId,
            'reason' => 'no explicit warehouse_id',
            'adjustment_date' => now()->toDateString(),
        ]));

        $payload = json_decode($response->getContent(), true);
        $this->assertSame(200, $response->getStatusCode(), $payload['message'] ?? json_encode($payload));
        $this->assertSame($only->id, StockAdjustment::first()->warehouse_id);
    }

    public function test_branch_fallback_still_rejects_an_ambiguous_branch_when_no_warehouse_was_picked(): void
    {
        $branchId = DB::table('branches')->insertGetId(['name' => 'Cabang Bali', 'code' => 'BAL']);
        Warehouse::create(['warehouse_code' => 'WH20', 'name' => 'Gudang 1 - Bali', 'branch_id' => $branchId, 'is_active' => true]);
        Warehouse::create(['warehouse_code' => 'WH21', 'name' => 'Gudang 2 - Bali', 'branch_id' => $branchId, 'is_active' => true]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Branch ini memiliki lebih dari 1 warehouse aktif. Rapikan master warehouse terlebih dahulu.');

        app(StockAdjustmentController::class)->store(Request::create('/warehouse/stock-adjustments', 'POST', [
            'branch_id' => $branchId,
            'reason' => 'ambiguous branch, no warehouse picked',
            'adjustment_date' => now()->toDateString(),
        ]));
    }
}
