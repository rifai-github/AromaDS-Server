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
            $table->string('code')->nullable();
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
        $this->assertSame($warehouse->id, SerialNumber::where('serial_number', 'W300-002')->value('warehouse_id'));
        $this->assertSame(2, SerialNumber::where('warehouse_id', $warehouse->id)->where('master_product_id', $product->id)->where('status', 'ready')->count());
    }

    public function test_batch_serial_product_can_be_detected_from_existing_serial_rows(): void
    {
        [$warehouse, $product] = $this->createWarehouseAndLegacyBatchProduct();

        WarehouseProduct::create([
            'warehouse_id' => $warehouse->id,
            'master_product_id' => $product->id,
            'quantity' => 1,
        ]);

        SerialNumber::create([
            'serial_number' => 'RLG1000003',
            'status' => 'ready',
            'condition_status' => SerialNumber::CONDITION_NEW,
            'location_type' => 'warehouse',
            'location_id' => $warehouse->id,
            'warehouse_id' => $warehouse->id,
            'master_product_id' => $product->id,
        ]);

        $this->assertTrue($product->fresh()->requiresSerialNumber());
        $this->assertFalse($product->fresh()->requiresUniqueSerialNumber());

        $adjustment = StockAdjustment::create([
            'adjustment_no' => 'ADJ-BATCH-INC-001',
            'warehouse_id' => $warehouse->id,
            'reason' => 'batch stock increase',
            'adjustment_date' => now()->toDateString(),
            'status' => 'waiting for approval',
        ]);

        StockAdjustmentItem::create([
            'stock_adjustment_id' => $adjustment->id,
            'master_product_id' => $product->id,
            'adjustment_qty' => 2,
            'adjustment_type' => 'increase',
            'serial_numbers' => ['RLG1000003', 'RLG1000003'],
        ]);

        $adjustment->approve(1);

        $this->assertSame(3, WarehouseProduct::where('warehouse_id', $warehouse->id)->where('master_product_id', $product->id)->value('quantity'));
        $this->assertSame(3, SerialNumber::where('warehouse_id', $warehouse->id)->where('master_product_id', $product->id)->where('serial_number', 'RLG1000003')->where('status', 'ready')->count());
    }

    public function test_aroma_and_dispenser_categories_are_mandatory_serial_policy_even_when_flag_is_false(): void
    {
        $aroma = ProductCategory::create([
            'code' => 'AROMA',
            'name' => 'Aroma',
            'has_serial_number' => false,
            'is_unit' => false,
        ]);
        $dispenser = ProductCategory::create([
            'code' => 'DIS',
            'name' => 'Dispenser',
            'has_serial_number' => false,
            'is_unit' => true,
        ]);

        $aromaProduct = MasterProduct::create([
            'product_category_id' => $aroma->id,
            'name' => 'Fragrance Lemongrass Mix 100ml',
            'sku' => 'REFLEMONGRASS100',
            'is_active' => true,
        ]);
        $dispenserProduct = MasterProduct::create([
            'product_category_id' => $dispenser->id,
            'name' => 'PURE Dispenser 7200',
            'sku' => 'DIS7200',
            'is_active' => true,
        ]);

        $this->assertTrue($aroma->fresh()->effective_has_serial_number);
        $this->assertTrue($dispenser->fresh()->effective_has_serial_number);
        $this->assertTrue($aromaProduct->fresh()->requiresSerialNumber());
        $this->assertTrue($dispenserProduct->fresh()->requiresSerialNumber());
        $this->assertTrue($dispenserProduct->fresh()->requiresUniqueSerialNumber());
    }

    public function test_batch_decrease_retires_only_requested_duplicate_rows(): void
    {
        [$warehouse, $product] = $this->createWarehouseAndLegacyBatchProduct();

        WarehouseProduct::create([
            'warehouse_id' => $warehouse->id,
            'master_product_id' => $product->id,
            'quantity' => 3,
        ]);

        foreach (range(1, 3) as $index) {
            SerialNumber::create([
                'serial_number' => 'RLG1000003',
                'status' => 'ready',
                'condition_status' => SerialNumber::CONDITION_NEW,
                'location_type' => 'warehouse',
                'location_id' => $warehouse->id,
                'warehouse_id' => $warehouse->id,
                'master_product_id' => $product->id,
            ]);
        }

        $adjustment = StockAdjustment::create([
            'adjustment_no' => 'ADJ-BATCH-DEC-001',
            'warehouse_id' => $warehouse->id,
            'reason' => 'batch stock decrease',
            'adjustment_date' => now()->toDateString(),
            'status' => 'waiting for approval',
        ]);

        StockAdjustmentItem::create([
            'stock_adjustment_id' => $adjustment->id,
            'master_product_id' => $product->id,
            'adjustment_qty' => 2,
            'adjustment_type' => 'decrease',
            'serial_numbers' => ['RLG1000003', 'RLG1000003'],
        ]);

        $adjustment->approve(1);

        $this->assertSame(1, WarehouseProduct::where('warehouse_id', $warehouse->id)->where('master_product_id', $product->id)->value('quantity'));
        $this->assertSame(2, SerialNumber::where('master_product_id', $product->id)->where('serial_number', 'RLG1000003')->where('status', 'retired')->count());
        $this->assertSame(1, SerialNumber::where('warehouse_id', $warehouse->id)->where('master_product_id', $product->id)->where('serial_number', 'RLG1000003')->where('status', 'ready')->count());
    }

    public function test_rollback_of_increase_deletes_serial_numbers_and_reverses_stock(): void
    {
        [$warehouse, $product] = $this->createWarehouseAndSerialProduct();

        $adjustment = StockAdjustment::create([
            'adjustment_no' => 'ADJ-RB-INC-001',
            'warehouse_id' => $warehouse->id,
            'reason' => 'increase to be rolled back',
            'adjustment_date' => now()->toDateString(),
            'status' => 'waiting for approval',
        ]);

        StockAdjustmentItem::create([
            'stock_adjustment_id' => $adjustment->id,
            'master_product_id' => $product->id,
            'adjustment_qty' => 2,
            'adjustment_type' => 'increase',
            'serial_numbers' => ['W300-RB-001', 'W300-RB-002'],
        ]);

        $adjustment->approve(1);
        $this->assertSame(2, WarehouseProduct::where('warehouse_id', $warehouse->id)->where('master_product_id', $product->id)->value('quantity'));

        $adjustment->fresh()->rollback(1);

        $this->assertSame('draft', $adjustment->fresh()->status);
        $this->assertNull($adjustment->fresh()->approved_by);
        $this->assertSame(0, WarehouseProduct::where('warehouse_id', $warehouse->id)->where('master_product_id', $product->id)->value('quantity'));
        $this->assertSame(0, SerialNumber::where('warehouse_id', $warehouse->id)->where('master_product_id', $product->id)->count());
        $this->assertSame(0, InventoryMovement::where('reference_no', 'ADJ-RB-INC-001')->count());
    }

    public function test_serial_number_can_be_reregistered_after_increase_rollback(): void
    {
        [$warehouse, $product] = $this->createWarehouseAndSerialProduct();

        $adjustment = StockAdjustment::create([
            'adjustment_no' => 'ADJ-RB-INC-002',
            'warehouse_id' => $warehouse->id,
            'reason' => 'increase to be rolled back then re-added',
            'adjustment_date' => now()->toDateString(),
            'status' => 'waiting for approval',
        ]);

        StockAdjustmentItem::create([
            'stock_adjustment_id' => $adjustment->id,
            'master_product_id' => $product->id,
            'adjustment_qty' => 2,
            'adjustment_type' => 'increase',
            'serial_numbers' => ['W300-RR-001', 'W300-RR-002'],
        ]);

        $adjustment->approve(1);
        $adjustment->fresh()->rollback(1);

        // Serial numbers removed by rollback are soft-deleted, not hard-deleted.
        $this->assertSame(0, SerialNumber::where('serial_number', 'W300-RR-001')->count());
        $this->assertSame(1, SerialNumber::withTrashed()->where('serial_number', 'W300-RR-001')->count());

        $secondAdjustment = StockAdjustment::create([
            'adjustment_no' => 'ADJ-RB-INC-003',
            'warehouse_id' => $warehouse->id,
            'reason' => 're-add same serial numbers after rollback',
            'adjustment_date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $secondItem = StockAdjustmentItem::create([
            'stock_adjustment_id' => $secondAdjustment->id,
            'master_product_id' => $product->id,
            'adjustment_qty' => 2,
            'adjustment_type' => 'increase',
            'serial_numbers' => ['W300-RR-001', 'W300-RR-002'],
        ]);

        // Must not throw: previously-registered-but-now-soft-deleted serial
        // numbers are gone for good and should be re-registrable.
        $secondAdjustment->approve(1);

        $this->assertSame('approved', $secondAdjustment->fresh()->status);
        $this->assertSame('ready', SerialNumber::where('serial_number', 'W300-RR-001')->value('status'));
        $this->assertSame(2, SerialNumber::where('warehouse_id', $warehouse->id)->where('master_product_id', $product->id)->count());
    }

    public function test_rollback_of_decrease_restores_serial_numbers_and_stock(): void
    {
        [$warehouse, $product] = $this->createWarehouseAndSerialProduct();

        WarehouseProduct::create([
            'warehouse_id' => $warehouse->id,
            'master_product_id' => $product->id,
            'quantity' => 2,
        ]);

        foreach (['W300-D-001', 'W300-D-002'] as $serialNumber) {
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
            'adjustment_no' => 'ADJ-RB-DEC-001',
            'warehouse_id' => $warehouse->id,
            'reason' => 'decrease to be rolled back',
            'adjustment_date' => now()->toDateString(),
            'status' => 'waiting for approval',
        ]);

        StockAdjustmentItem::create([
            'stock_adjustment_id' => $adjustment->id,
            'master_product_id' => $product->id,
            'adjustment_qty' => 1,
            'adjustment_type' => 'decrease',
            'serial_numbers' => ['W300-D-002'],
        ]);

        $adjustment->approve(1);
        $this->assertSame('retired', SerialNumber::where('serial_number', 'W300-D-002')->value('status'));
        $this->assertSame(1, WarehouseProduct::where('warehouse_id', $warehouse->id)->where('master_product_id', $product->id)->value('quantity'));

        $adjustment->fresh()->rollback(1);

        $this->assertSame('draft', $adjustment->fresh()->status);
        $this->assertSame(2, WarehouseProduct::where('warehouse_id', $warehouse->id)->where('master_product_id', $product->id)->value('quantity'));
        $this->assertSame('ready', SerialNumber::where('serial_number', 'W300-D-002')->value('status'));
        $this->assertSame('warehouse', SerialNumber::where('serial_number', 'W300-D-002')->value('location_type'));
        $this->assertSame(0, InventoryMovement::where('reference_no', 'ADJ-RB-DEC-001')->count());
    }

    public function test_rollback_of_increase_is_blocked_when_serial_number_no_longer_ready(): void
    {
        [$warehouse, $product] = $this->createWarehouseAndSerialProduct();

        $adjustment = StockAdjustment::create([
            'adjustment_no' => 'ADJ-RB-BLK-001',
            'warehouse_id' => $warehouse->id,
            'reason' => 'increase then consumed',
            'adjustment_date' => now()->toDateString(),
            'status' => 'waiting for approval',
        ]);

        StockAdjustmentItem::create([
            'stock_adjustment_id' => $adjustment->id,
            'master_product_id' => $product->id,
            'adjustment_qty' => 1,
            'adjustment_type' => 'increase',
            'serial_numbers' => ['W300-BLK-001'],
        ]);

        $adjustment->approve(1);

        // Simulate the created SN being consumed (e.g. issued out).
        SerialNumber::where('serial_number', 'W300-BLK-001')->update(['status' => 'issued']);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        try {
            $adjustment->fresh()->rollback(1);
        } finally {
            // Nothing should have changed: still approved, SN still present, movement intact.
            $this->assertSame('approved', $adjustment->fresh()->status);
            $this->assertSame(1, SerialNumber::where('serial_number', 'W300-BLK-001')->count());
            $this->assertSame(1, InventoryMovement::where('reference_no', 'ADJ-RB-BLK-001')->count());
        }
    }

    public function test_rollback_is_blocked_when_not_approved(): void
    {
        [$warehouse, $product] = $this->createWarehouseAndSerialProduct();

        $adjustment = StockAdjustment::create([
            'adjustment_no' => 'ADJ-RB-DRAFT-001',
            'warehouse_id' => $warehouse->id,
            'reason' => 'still draft',
            'adjustment_date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $adjustment->rollback(1);
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

    private function createWarehouseAndLegacyBatchProduct(): array
    {
        $warehouse = Warehouse::create(['name' => 'Gudang DKI Jakarta', 'is_active' => true]);
        $category = ProductCategory::create([
            'name' => 'Aroma',
            'has_serial_number' => false,
            'is_unit' => false,
        ]);
        $product = MasterProduct::create([
            'product_category_id' => $category->id,
            'name' => 'Fragrance Lemongrass Mix 100ml',
            'sku' => 'REFLEMONGRASS100',
            'is_active' => true,
        ]);

        return [$warehouse, $product];
    }
}
