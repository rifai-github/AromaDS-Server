<?php

namespace Tests\Feature;

use App\Http\Controllers\Warehouse\InventoryController;
use App\Models\InventoryReceiving;
use App\Models\InventoryTransfer;
use App\Models\SerialNumber;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA feedback: SN-tracked units (diffuser/aroma/refill) don't show up at the
 * destination warehouse's SN list after a transfer, because Inventory
 * Transfer only ever moved the WarehouseProduct quantity aggregate and never
 * touched SerialNumber rows. Permanent fix (Opsi A, confirmed with user):
 * when an SN-tracked item leaves the source warehouse (draft -> transferred),
 * auto-pick the oldest 'ready' SN units FIFO and queue them into an
 * auto-created Inventory Receiving at the destination - the same, already
 * correct mechanism used for branch material returns
 * (queueSerialNumberReturnItem() in JobScheduleController). The destination
 * warehouse verifies/finalizes that Receiving through the existing SN flow
 * (InventoryReceivingController::finalize()), which is what actually moves
 * SerialNumber.warehouse_id and credits stock - not Inventory Transfer's own
 * "Received" status.
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

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('code')->nullable();
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
            $table->string('serial_number')->nullable();
            $table->string('status')->nullable();
            $table->string('condition_status')->nullable();
            $table->string('location_type')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->foreignId('inventory_receiving_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
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
            $table->string('approval_status')->default('not_required');
            $table->boolean('is_direct_branch_transfer')->default(false);
            $table->string('delivery_order_file')->nullable();
            $table->unsignedBigInteger('central_approved_by')->nullable();
            $table->timestamp('central_approved_at')->nullable();
            $table->text('central_approval_notes')->nullable();
            $table->unsignedBigInteger('submitted_for_approval_by')->nullable();
            $table->timestamp('submitted_for_approval_at')->nullable();
            $table->unsignedBigInteger('central_rejected_by')->nullable();
            $table->timestamp('central_rejected_at')->nullable();
            $table->text('central_rejection_reason')->nullable();
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

        Schema::create('inventory_transfer_approval_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_transfer_id');
            $table->string('action');
            $table->foreignId('actor_id')->nullable();
            $table->text('notes')->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamps();
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

        Schema::create('inventory_receivings', function (Blueprint $table) {
            $table->id();
            $table->string('receiving_number')->nullable();
            $table->string('reference_no')->nullable();
            $table->foreignId('issuing_id')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->unsignedBigInteger('received_from')->nullable();
            $table->unsignedBigInteger('received_by_old')->nullable();
            $table->date('receive_date')->nullable();
            $table->date('schedule_date')->nullable();
            $table->string('status')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_receiving_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_receiving_id')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('quantity_received', 10, 2)->nullable();
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('users')->insert(['id' => 1, 'name' => 'Admin', 'created_at' => now(), 'updated_at' => now()]);
        Auth::login(User::findOrFail(1));
    }

    private function makeWarehouses(): array
    {
        $branchRow = DB::table('branches')->insertGetId(['name' => 'Cabang Surabaya', 'code' => 'SBY', 'created_at' => now(), 'updated_at' => now()]);
        $branch = Warehouse::create(['name' => 'Gudang Cabang', 'is_center' => false, 'branch_id' => $branchRow]);
        $center = Warehouse::create(['name' => 'Gudang Pusat', 'is_center' => true]);

        return [$branch, $center];
    }

    private function makeSerialNumberProduct(int $branchWarehouseId, int $readyCount): int
    {
        DB::table('product_types')->insert(['id' => 1, 'name' => 'Unit Diffuser', 'has_serial_number' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('master_products')->insert(['id' => 1, 'name' => 'Diffuser W300 White', 'product_type_id' => 1, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('warehouse_products')->insert([
            'warehouse_id' => $branchWarehouseId, 'master_product_id' => 1, 'quantity' => $readyCount,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        for ($i = 0; $i < $readyCount; $i++) {
            SerialNumber::create([
                'master_product_id' => 1,
                'warehouse_id' => $branchWarehouseId,
                'status' => 'ready',
                'serial_number' => 'SN-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
            ]);
        }

        return 1;
    }

    public function test_transferred_deducts_source_and_queues_sn_units_into_a_receiving(): void
    {
        [$branch, $center] = $this->makeWarehouses();
        $productId = $this->makeSerialNumberProduct($branch->id, 5);

        $storeResponse = (new InventoryController)->storeTransfer(Request::create('/warehouse/inventory-transfers/api/store', 'POST', [
            'transfer_date' => now()->toDateString(),
            'from_warehouse_id' => $branch->id,
            'to_warehouse_id' => $center->id,
            'status' => 'draft',
            'items' => [['product_id' => $productId, 'quantity' => 3]],
        ]));
        $this->assertSame(200, $storeResponse->getStatusCode(), json_decode($storeResponse->getContent(), true)['message'] ?? '');
        $transfer = InventoryTransfer::first();

        $response = (new InventoryController)->markTransferAsTransferred($transfer);
        $payload = json_decode($response->getContent(), true);
        $this->assertSame(200, $response->getStatusCode(), $payload['message'] ?? '');
        $this->assertStringContainsString('Serial Number', $payload['message']);
        $this->assertStringContainsString('Inventory Receiving', $payload['message']);

        // Source stock deducted immediately, same as non-SN products.
        $this->assertSame(2.0, (float) DB::table('warehouse_products')->where('warehouse_id', $branch->id)->where('master_product_id', $productId)->value('quantity'));

        // A Receiving was auto-created for the destination, referencing this transfer.
        $receiving = InventoryReceiving::where('reference_no', $transfer->transfer_number)->first();
        $this->assertNotNull($receiving);
        $this->assertSame($center->branch_id, $receiving->branch_id);
        $this->assertSame('pending', $receiving->status);
        $this->assertSame(1, DB::table('inventory_receiving_items')->where('inventory_receiving_id', $receiving->id)->count());
        $this->assertSame(3.0, (float) DB::table('inventory_receiving_items')->where('inventory_receiving_id', $receiving->id)->value('quantity'));

        // Exactly 3 of the 5 available SN units got queued into it, oldest first,
        // tagged to the destination warehouse but not yet 'ready' there.
        $queued = SerialNumber::where('inventory_receiving_id', $receiving->id)->orderBy('serial_number')->get();
        $this->assertCount(3, $queued);
        $this->assertSame(['SN-0000', 'SN-0001', 'SN-0002'], $queued->pluck('serial_number')->all());
        $this->assertTrue($queued->every(fn ($sn) => $sn->status === 'pending' && (int) $sn->warehouse_id === $center->id));

        // The 2 untouched units are still 'ready' at the source.
        $this->assertSame(2, SerialNumber::where('warehouse_id', $branch->id)->where('status', 'ready')->count());
    }

    public function test_received_does_not_credit_destination_stock_for_sn_item_but_does_for_non_sn_item(): void
    {
        [$branch, $center] = $this->makeWarehouses();
        $snProductId = $this->makeSerialNumberProduct($branch->id, 2);

        DB::table('master_products')->insert(['id' => 2, 'name' => 'Fragrance Coffee Mix', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('warehouse_products')->insert([
            'warehouse_id' => $branch->id, 'master_product_id' => 2, 'quantity' => 10,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        (new InventoryController)->storeTransfer(Request::create('/warehouse/inventory-transfers/api/store', 'POST', [
            'transfer_date' => now()->toDateString(),
            'from_warehouse_id' => $branch->id,
            'to_warehouse_id' => $center->id,
            'status' => 'draft',
            'items' => [
                ['product_id' => $snProductId, 'quantity' => 2],
                ['product_id' => 2, 'quantity' => 4],
            ],
        ]));
        $transfer = InventoryTransfer::first();

        (new InventoryController)->markTransferAsTransferred($transfer);

        $response = (new InventoryController)->markTransferAsReceived($transfer->fresh());
        $this->assertSame(200, $response->getStatusCode());

        // Non-SN product credited normally at Received.
        $this->assertSame(4.0, (float) DB::table('warehouse_products')->where('warehouse_id', $center->id)->where('master_product_id', 2)->value('quantity'));

        // SN product NOT credited here - it's still pending finalize on the queued Receiving.
        $centerSnProductRow = DB::table('warehouse_products')->where('warehouse_id', $center->id)->where('master_product_id', $snProductId)->value('quantity');
        $this->assertTrue($centerSnProductRow === null || (float) $centerSnProductRow === 0.0);
    }

    public function test_insufficient_ready_serial_numbers_blocks_the_transferred_transition(): void
    {
        [$branch, $center] = $this->makeWarehouses();
        $productId = $this->makeSerialNumberProduct($branch->id, 2);

        (new InventoryController)->storeTransfer(Request::create('/warehouse/inventory-transfers/api/store', 'POST', [
            'transfer_date' => now()->toDateString(),
            'from_warehouse_id' => $branch->id,
            'to_warehouse_id' => $center->id,
            'status' => 'draft',
            'items' => [['product_id' => $productId, 'quantity' => 2]],
        ]));
        $transfer = InventoryTransfer::first();

        // Simulate 1 of the 2 units getting used elsewhere between drafting and transferring.
        SerialNumber::where('warehouse_id', $branch->id)->first()->update(['status' => 'in_use']);

        $response = (new InventoryController)->markTransferAsTransferred($transfer);
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertStringContainsString('Serial Number', $payload['message']);

        $transfer->refresh();
        $this->assertSame('draft', $transfer->status);
        $this->assertSame(2.0, (float) DB::table('warehouse_products')->where('warehouse_id', $branch->id)->where('master_product_id', $productId)->value('quantity'), 'Source stock must not move when the transition is rejected.');
    }
}
