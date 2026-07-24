<?php

namespace Tests\Feature;

use App\Http\Controllers\Warehouse\WarehouseController;
use App\Models\InventoryMovement;
use App\Models\MasterProduct;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA bug: the "Stock Movement History" table on a warehouse's stock detail
 * page showed a Stock Adjustment DECREASE as "+1" instead of "-1".
 * InventoryMovement.quantity is stored unsigned (magnitude only) everywhere
 * it's written - movement_type ('in'/'out'/'return') carries direction, and
 * 'out' is the only type that decrements WarehouseProduct.quantity
 * (StockAdjustment::approve(), InventoryIssuingService, etc.). The stock
 * count itself was correct; detailStock() just forgot to sign the quantity
 * by movement_type before handing it to the blade, which trusts the sign
 * ($movement['adjustment'] >= 0 ? '+' : '').
 */
class WarehouseStockMovementSignTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
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

        Schema::create('warehouse_admins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->timestamps();
        });

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('sku')->nullable();
            $table->unsignedBigInteger('packaging_size_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouse_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('master_product_id')->nullable();
            $table->integer('quantity')->default(0);
            $table->integer('minimum_stock')->default(0);
            $table->integer('maximum_stock')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number')->nullable();
            $table->string('status')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('master_product_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->string('movement_no')->nullable();
            $table->string('movement_type')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('master_product_id')->nullable();
            $table->integer('quantity')->default(0);
            $table->date('movement_date')->nullable();
            $table->string('reference_no')->nullable();
            $table->string('reference_type')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('serial_numbers');
        Schema::dropIfExists('warehouse_products');
        Schema::dropIfExists('master_products');
        Schema::dropIfExists('warehouse_admins');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_out_movements_show_as_negative_and_in_movements_stay_positive(): void
    {
        $warehouse = Warehouse::create(['warehouse_code' => 'WH01', 'name' => 'Gudang Pusat Jakbar', 'is_active' => true]);
        $product = MasterProduct::create(['name' => 'Battery R6 ABC Super Power', 'sku' => 'BATR6']);

        WarehouseProduct::create([
            'warehouse_id' => $warehouse->id,
            'master_product_id' => $product->id,
            'quantity' => 6,
        ]);

        InventoryMovement::create([
            'movement_no' => 'PUS-ADJ/26-07/0002',
            'movement_type' => 'out',
            'warehouse_id' => $warehouse->id,
            'master_product_id' => $product->id,
            'quantity' => 1,
            'movement_date' => now()->toDateString(),
            'reference_no' => 'PUS-ADJ/26-07/0002',
            'reference_type' => 'Stock Adjustment',
            'notes' => 'Adjustment from Stock Opname PSTJB-SO/26-07/0001',
        ]);

        InventoryMovement::create([
            'movement_no' => 'TR-20260724-0003',
            'movement_type' => 'in',
            'warehouse_id' => $warehouse->id,
            'master_product_id' => $product->id,
            'quantity' => 6,
            'movement_date' => now()->toDateString(),
            'reference_no' => 'TR-20260724-0003',
            'reference_type' => 'inventory_transfer',
            'notes' => 'Transfer masuk langsung',
        ]);

        $response = app(WarehouseController::class)->detailStock($warehouse, $product->id);
        $movements = $response->getData()['formattedMovements'];

        $outRow = $movements->firstWhere('description', 'Adjustment from Stock Opname PSTJB-SO/26-07/0001');
        $inRow = $movements->firstWhere('description', 'Transfer masuk langsung');

        $this->assertSame(-1, $outRow['adjustment'], 'A decrease/out movement must display as negative.');
        $this->assertSame(6, $inRow['adjustment'], 'An increase/in movement must stay positive.');
    }
}
