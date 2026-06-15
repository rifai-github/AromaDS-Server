<?php

namespace Tests\Feature;

use App\Models\InventoryMovement;
use App\Models\MasterProduct;
use App\Models\ProductCategory;
use App\Models\SerialNumber;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StockAdjustmentSerialNumberTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('warehouse_code')->nullable();
            $table->string('name')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->boolean('has_serial_number')->default(false);
            $table->boolean('is_unit')->default(false);
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_category_id')->nullable();
            $table->string('name')->nullable();
            $table->string('sku')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouse_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->integer('quantity')->default(0);
            $table->integer('minimum_stock')->default(0);
            $table->integer('maximum_stock')->default(0);
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_no')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->date('adjustment_date')->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_adjustment_id')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->integer('adjustment_qty')->default(0);
            $table->string('adjustment_type')->nullable();
            $table->string('notes')->nullable();
            $table->json('serial_numbers')->nullable();
            $table->timestamps();
        });

        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number')->nullable();
            $table->string('status')->nullable();
            $table->string('condition_status')->nullable();
            $table->string('location_type')->nullable();
            $table->foreignId('location_id')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->foreignId('inventory_receiving_id')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('unit_on_walls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('serial_number_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->string('movement_no')->nullable();
            $table->string('movement_type')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->integer('quantity')->default(0);
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->decimal('total_value', 15, 2)->nullable();
            $table->date('movement_date')->nullable();
            $table->string('reference_no')->nullable();
            $table->string('reference_type')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function test_increase_adjustment_creates_ready_serial_numbers(): void
    {
        [$warehouse, $product] = $this->createWarehouseAndSerialProduct();

        $adjustment = StockAdjustment::create([
            'adjustment_no' => 'ADJ-INC-001',
            'warehouse_id' => $warehouse->id,
            'reason' => 'stock opname increase',
            'adjustment_date' => now()->toDateString(),
            'status' => 'waiting for approval',
        ]);

        StockAdjustmentItem::create([
            'stock_adjustment_id' => $adjustment->id,
            'master_product_id' => $product->id,
            'adjustment_qty' => 2,
            'adjustment_type' => 'increase',
            'serial_numbers' => ['W300-NEW-001', 'W300-NEW-002'],
        ]);

        $adjustment->approve(1);

        $this->assertSame(2, WarehouseProduct::where('warehouse_id', $warehouse->id)->where('master_product_id', $product->id)->value('quantity'));
        $this->assertSame(2, SerialNumber::where('warehouse_id', $warehouse->id)->where('master_product_id', $product->id)->where('status', 'ready')->count());
        $this->assertSame('warehouse', SerialNumber::where('serial_number', 'W300-NEW-001')->value('location_type'));
        $this->assertStringContainsString('W300-NEW-001, W300-NEW-002', InventoryMovement::first()->notes);
    }

    public function test_decrease_adjustment_retires_only_selected_serial_number(): void
    {
        [$warehouse, $product] = $this->createWarehouseAndSerialProduct();

        WarehouseProduct::create([
            'warehouse_id' => $warehouse->id,
            'master_product_id' => $product->id,
            'quantity' => 3,
        ]);

        foreach (['W300-001', 'W300-002', 'W300-003'] as $serialNumber) {
            SerialNumber::create([
                'serial_number' => $serialNumber,
                'status' => 'ready',
                'condition_status' => SerialNumber::CONDITION_NEW,
                'location_type' => 'warehouse',
                'location_id' => $warehouse->id,
                'warehouse_id' => $warehouse->id,
                'master_product_id' => $product->id,
            ]);
        }

        $adjustment = StockAdjustment::create([
            'adjustment_no' => 'ADJ-DEC-001',
            'warehouse_id' => $warehouse->id,
            'reason' => 'stock opname decrease',
            'adjustment_date' => now()->toDateString(),
            'status' => 'waiting for approval',
        ]);

        StockAdjustmentItem::create([
            'stock_adjustment_id' => $adjustment->id,
            'master_product_id' => $product->id,
            'adjustment_qty' => 1,
            'adjustment_type' => 'decrease',
            'serial_numbers' => ['W300-002'],
        ]);

        $adjustment->approve(1);

        $this->assertSame(2, WarehouseProduct::where('warehouse_id', $warehouse->id)->where('master_product_id', $product->id)->value('quantity'));
        $this->assertSame('retired', SerialNumber::where('serial_number', 'W300-002')->value('status'));
        $this->assertNull(SerialNumber::where('serial_number', 'W300-002')->value('warehouse_id'));
        $this->assertSame(2, SerialNumber::where('warehouse_id', $warehouse->id)->where('master_product_id', $product->id)->where('status', 'ready')->count());
    }

    private function createWarehouseAndSerialProduct(): array
    {
        $warehouse = Warehouse::create(['name' => 'Gudang Surabaya', 'is_active' => true]);
        $category = ProductCategory::create([
            'name' => 'Unit',
            'has_serial_number' => true,
            'is_unit' => true,
        ]);
        $product = MasterProduct::create([
            'product_category_id' => $category->id,
            'name' => 'Diffuser W300 White',
            'sku' => 'DISW300W',
            'is_active' => true,
        ]);

        return [$warehouse, $product];
    }
}
