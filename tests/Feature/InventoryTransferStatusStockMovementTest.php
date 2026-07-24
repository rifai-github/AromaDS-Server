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
 * QA feedback: stock was crediting the destination warehouse as soon as a
 * transfer hit "Transferred", before the goods were actually confirmed
 * received - it should only credit at "Received". The source warehouse
 * deduction at "Transferred" was already correct and stays as-is. Locks down
 * applyStockForTransferStatusChange()'s split (deductSourceStockForTransfer /
 * creditDestinationStockForTransfer) via the real storeTransfer/updateTransfer
 * endpoints.
 */
class InventoryTransferStatusStockMovementTest extends TestCase
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
            $table->foreignId('manager')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_center')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->decimal('minimum_stock', 10, 2)->nullable();
            $table->decimal('maximum_stock', 10, 2)->nullable();
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
            $table->unsignedBigInteger('delivery_order_uploaded_by')->nullable();
            $table->timestamp('delivery_order_uploaded_at')->nullable();
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

        // Needed only because MasterProduct::requiresSerialNumber() (used by the SN
        // guard in storeTransfer/updateTransfer) falls back to querying this table
        // when the product has no productCategory/productType set.
        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_product_id')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('users')->insert(['id' => 1, 'name' => 'Admin', 'created_at' => now(), 'updated_at' => now()]);
        Auth::login(User::findOrFail(1));
    }

    private function makeWarehouses(): array
    {
        $branch = Warehouse::create(['name' => 'Gudang Cabang', 'is_center' => false, 'manager' => 1]);
        $center = Warehouse::create(['name' => 'Gudang Pusat', 'is_center' => true, 'manager' => 1]);

        DB::table('master_products')->insert(['id' => 1, 'name' => 'Diffuser W300 White', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('warehouse_products')->insert([
            'warehouse_id' => $branch->id, 'master_product_id' => 1, 'quantity' => 10,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return [$branch, $center];
    }

    private function warehouseStock(int $warehouseId): float
    {
        return (float) DB::table('warehouse_products')
            ->where('warehouse_id', $warehouseId)
            ->where('master_product_id', 1)
            ->value('quantity');
    }

    public function test_transferred_deducts_source_but_does_not_credit_destination(): void
    {
        [$branch, $center] = $this->makeWarehouses();

        $request = Request::create('/warehouse/inventory-transfers/api/store', 'POST', [
            'transfer_date' => now()->toDateString(),
            'from_warehouse_id' => $branch->id,
            'to_warehouse_id' => $center->id,
            'status' => 'draft',
            'items' => [['product_id' => 1, 'quantity' => 4]],
        ]);
        $response = (new InventoryController)->storeTransfer($request);
        $transfer = InventoryTransfer::first();
        $this->assertSame(200, $response->getStatusCode());

        // Draft: nothing moves yet.
        $this->assertSame(10.0, $this->warehouseStock($branch->id));
        $this->assertSame(0.0, $this->warehouseStock($center->id));

        $updateResponse = (new InventoryController)->markTransferAsTransferred($transfer);
        $payload = json_decode($updateResponse->getContent(), true);
        $this->assertSame(200, $updateResponse->getStatusCode(), $payload['message'] ?? 'no message');

        // Transferred: source loses stock, destination does NOT gain it yet
        // (goods are in transit, not yet confirmed received).
        $this->assertSame(6.0, $this->warehouseStock($branch->id));
        $this->assertSame(0.0, $this->warehouseStock($center->id));
    }

    public function test_received_credits_destination_without_double_deducting_source(): void
    {
        [$branch, $center] = $this->makeWarehouses();

        $request = Request::create('/warehouse/inventory-transfers/api/store', 'POST', [
            'transfer_date' => now()->toDateString(),
            'from_warehouse_id' => $branch->id,
            'to_warehouse_id' => $center->id,
            'status' => 'draft',
            'items' => [['product_id' => 1, 'quantity' => 4]],
        ]);
        (new InventoryController)->storeTransfer($request);
        $transfer = InventoryTransfer::first();

        // draft -> transferred (source deducted, per the other test).
        (new InventoryController)->markTransferAsTransferred($transfer);

        // transferred -> received: destination should now gain the stock, and
        // the source should NOT be deducted a second time.
        $response = (new InventoryController)->markTransferAsReceived($transfer->fresh());
        $payload = json_decode($response->getContent(), true);
        $this->assertSame(200, $response->getStatusCode(), $payload['message'] ?? 'no message');

        $this->assertSame(6.0, $this->warehouseStock($branch->id));
        $this->assertSame(4.0, $this->warehouseStock($center->id));

        $movementTypes = DB::table('inventory_movements')->pluck('movement_type')->all();
        $this->assertSame(['out', 'in'], $movementTypes, 'Expected exactly one out (at transferred) and one in (at received) movement, no duplicates.');
    }

    public function test_creating_directly_as_received_moves_both_source_and_destination(): void
    {
        [$branch, $center] = $this->makeWarehouses();

        $request = Request::create('/warehouse/inventory-transfers/api/store', 'POST', [
            'transfer_date' => now()->toDateString(),
            'from_warehouse_id' => $branch->id,
            'to_warehouse_id' => $center->id,
            'status' => 'received',
            'items' => [['product_id' => 1, 'quantity' => 4]],
        ]);
        $response = (new InventoryController)->storeTransfer($request);
        $payload = json_decode($response->getContent(), true);
        $this->assertSame(200, $response->getStatusCode(), $payload['message'] ?? 'no message');

        $this->assertSame(6.0, $this->warehouseStock($branch->id));
        $this->assertSame(4.0, $this->warehouseStock($center->id));
    }
}
