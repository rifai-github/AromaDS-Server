<?php

namespace Tests\Feature;

use App\Http\Controllers\Warehouse\InventoryController;
use App\Models\InventoryTransfer;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA feedback: Inventory Transfer only moves the WarehouseProduct quantity
 * aggregate - it never touches SerialNumber records - so SN-tracked units
 * (diffuser/aroma/refill) silently vanish from the destination warehouse's SN
 * list even though the quantity total looks fine. Blocks Create and the
 * draft -> transferred/received status transition for SN-tracked products
 * until real SN movement is built, mirroring the guard already in place for
 * the auto-created Return-to-Center transfer.
 */
class InventoryTransferSerialNumberGuardTest extends TestCase
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
            $table->string('name')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_center')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->boolean('has_serial_number')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->boolean('has_serial_number')->default(false);
            $table->boolean('is_unit')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->foreignId('product_category_id')->nullable();
            $table->foreignId('product_type_id')->nullable();
            $table->decimal('minimum_stock', 10, 2)->nullable();
            $table->decimal('maximum_stock', 10, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_product_id')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouse_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('minimum_stock', 10, 2)->default(0);
            $table->decimal('maximum_stock', 10, 2)->default(0);
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number');
            $table->foreignId('from_warehouse_id');
            $table->foreignId('to_warehouse_id');
            $table->date('transfer_date')->nullable();
            $table->string('status')->default('draft');
            $table->boolean('is_direct_branch_transfer')->default(false);
            $table->string('delivery_order_file')->nullable();
            $table->unsignedBigInteger('central_approved_by')->nullable();
            $table->timestamp('central_approved_at')->nullable();
            $table->text('central_approval_notes')->nullable();
            $table->string('submission_letter_file')->nullable();
            $table->unsignedBigInteger('submission_letter_uploaded_by')->nullable();
            $table->timestamp('submission_letter_uploaded_at')->nullable();
            $table->string('delivery_note_file')->nullable();
            $table->unsignedBigInteger('delivery_note_uploaded_by')->nullable();
            $table->timestamp('delivery_note_uploaded_at')->nullable();
            $table->string('source_type', 64)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->text('notes')->nullable();
            $table->text('return_reason')->nullable();
            $table->string('return_reason_category', 64)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_transfer_id');
            $table->foreignId('master_product_id');
            $table->integer('quantity')->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->string('movement_no')->nullable();
            $table->string('movement_type')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->decimal('quantity', 10, 2)->default(0);
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

        DB::table('users')->insert(['id' => 1, 'name' => 'Admin', 'created_at' => now(), 'updated_at' => now()]);
        Auth::login(User::findOrFail(1));
    }

    private function makeWarehouses(): array
    {
        $branch = Warehouse::create(['name' => 'Gudang Cabang', 'is_center' => false]);
        $center = Warehouse::create(['name' => 'Gudang Pusat', 'is_center' => true]);

        return [$branch, $center];
    }

    public function test_store_transfer_rejects_serial_number_tracked_product(): void
    {
        [$branch, $center] = $this->makeWarehouses();

        DB::table('product_types')->insert(['id' => 1, 'name' => 'Unit Diffuser', 'has_serial_number' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('master_products')->insert(['id' => 1, 'name' => 'Diffuser W300 White', 'product_type_id' => 1, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('warehouse_products')->insert([
            'warehouse_id' => $branch->id, 'master_product_id' => 1, 'quantity' => 10,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $request = Request::create('/warehouse/inventory-transfers/api/store', 'POST', [
            'transfer_date' => now()->toDateString(),
            'from_warehouse_id' => $branch->id,
            'to_warehouse_id' => $center->id,
            'status' => 'draft',
            'items' => [['product_id' => 1, 'quantity' => 2]],
        ]);
        $response = (new InventoryController())->storeTransfer($request);
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertStringContainsString('Serial Number', $payload['message']);
        $this->assertSame(0, InventoryTransfer::count(), 'No transfer should have been created.');
    }

    public function test_store_transfer_allows_non_serial_number_product(): void
    {
        [$branch, $center] = $this->makeWarehouses();

        DB::table('master_products')->insert(['id' => 1, 'name' => 'Fragrance Coffee Mix', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('warehouse_products')->insert([
            'warehouse_id' => $branch->id, 'master_product_id' => 1, 'quantity' => 10,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $request = Request::create('/warehouse/inventory-transfers/api/store', 'POST', [
            'transfer_date' => now()->toDateString(),
            'from_warehouse_id' => $branch->id,
            'to_warehouse_id' => $center->id,
            'status' => 'draft',
            'items' => [['product_id' => 1, 'quantity' => 2]],
        ]);
        $response = (new InventoryController())->storeTransfer($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, InventoryTransfer::count());
    }

    public function test_update_transfer_rejects_transitioning_serial_number_item_to_transferred(): void
    {
        [$branch, $center] = $this->makeWarehouses();

        // Product starts non-SN so storeTransfer's own guard doesn't block creation
        // (simulating a transfer that already existed before this guard was added,
        // or a category later marked as requiring SN).
        DB::table('master_products')->insert(['id' => 1, 'name' => 'Diffuser W300 White', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('warehouse_products')->insert([
            'warehouse_id' => $branch->id, 'master_product_id' => 1, 'quantity' => 10,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $storeRequest = Request::create('/warehouse/inventory-transfers/api/store', 'POST', [
            'transfer_date' => now()->toDateString(),
            'from_warehouse_id' => $branch->id,
            'to_warehouse_id' => $center->id,
            'status' => 'draft',
            'items' => [['product_id' => 1, 'quantity' => 2]],
        ]);
        (new InventoryController())->storeTransfer($storeRequest);
        $transfer = InventoryTransfer::first();

        // Now mark the product as SN-tracked and try to move the transfer forward.
        DB::table('product_types')->insert(['id' => 1, 'name' => 'Unit Diffuser', 'has_serial_number' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('master_products')->where('id', 1)->update(['product_type_id' => 1]);

        $updateRequest = Request::create("/warehouse/inventory-transfers/api/{$transfer->id}/update", 'PUT', [
            'transfer_date' => now()->toDateString(),
            'from_warehouse_id' => $branch->id,
            'to_warehouse_id' => $center->id,
            'status' => 'transferred',
        ]);
        $response = (new InventoryController())->updateTransfer($updateRequest, $transfer->id);
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertStringContainsString('Serial Number', $payload['message']);

        $transfer->refresh();
        $this->assertSame('draft', $transfer->status, 'Status should not have moved forward.');
        $this->assertSame(10.0, (float) DB::table('warehouse_products')->where('warehouse_id', $branch->id)->where('master_product_id', 1)->value('quantity'), 'Source stock should be untouched.');
    }
}
