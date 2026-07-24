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

class InventoryTransferBranchApprovalWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('branch_id')->nullable();
            $table->foreignId('manager')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_center')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('minimum_stock', 10, 2)->nullable();
            $table->decimal('maximum_stock', 10, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('warehouse_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id');
            $table->foreignId('master_product_id');
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('minimum_stock', 10, 2)->default(0);
            $table->decimal('maximum_stock', 10, 2)->default(0);
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
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
        Schema::create('inventory_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number');
            $table->foreignId('from_warehouse_id');
            $table->foreignId('to_warehouse_id');
            $table->date('transfer_date');
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
            $table->text('notes')->nullable();
            $table->text('return_reason')->nullable();
            $table->string('return_reason_category')->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('inventory_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_transfer_id');
            $table->foreignId('master_product_id');
            $table->integer('quantity');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
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
            $table->string('movement_type');
            $table->foreignId('warehouse_id');
            $table->foreignId('master_product_id');
            $table->decimal('quantity', 10, 2);
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

        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Cabang Asal', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Approver Pusat', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('master_products')->insert(['id' => 1, 'name' => 'Non-SN Product', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_branch_transfer_requires_separate_central_approval_before_stock_moves(): void
    {
        $source = Warehouse::create(['name' => 'Cabang Jakarta', 'branch_id' => 10, 'is_center' => false]);
        $destination = Warehouse::create(['name' => 'Cabang Bandung', 'branch_id' => 20, 'is_center' => false]);
        Warehouse::create(['name' => 'Gudang Pusat', 'manager' => 2, 'is_center' => true]);
        DB::table('warehouse_products')->insert([
            'warehouse_id' => $source->id,
            'master_product_id' => 1,
            'quantity' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Auth::login(User::findOrFail(1));
        $response = app(InventoryController::class)->storeTransfer(Request::create('/transfer', 'POST', [
            'transfer_date' => now()->toDateString(),
            'from_warehouse_id' => $source->id,
            'to_warehouse_id' => $destination->id,
            'status' => 'received',
            'items' => [['product_id' => 1, 'quantity' => 4]],
        ]));

        $payload = json_decode($response->getContent(), true);
        $this->assertSame(200, $response->getStatusCode(), $payload['message'] ?? 'no message');
        $transfer = InventoryTransfer::firstOrFail();
        $this->assertTrue($transfer->is_direct_branch_transfer);
        $this->assertSame('draft', $transfer->status);
        $this->assertSame('draft', $transfer->approval_status);
        $this->assertSame(10.0, $this->stock($source->id));

        $withoutLetter = app(InventoryController::class)->submitTransferForApproval($transfer);
        $this->assertSame(422, $withoutLetter->getStatusCode());

        $transfer->update(['submission_letter_file' => 'inventory-transfers/submission-letter/request.pdf']);
        $submitted = app(InventoryController::class)->submitTransferForApproval($transfer->fresh());
        $this->assertSame(200, $submitted->getStatusCode());
        $this->assertSame('pending', $transfer->fresh()->approval_status);
        $this->assertSame(10.0, $this->stock($source->id));

        $selfApproval = app(InventoryController::class)->approveTransfer(Request::create('/approve', 'POST'), $transfer->fresh());
        $this->assertSame(403, $selfApproval->getStatusCode());

        Auth::login(User::findOrFail(2));
        $approved = app(InventoryController::class)->approveTransfer(Request::create('/approve', 'POST', ['notes' => 'Dokumen sesuai']), $transfer->fresh());
        $this->assertSame(200, $approved->getStatusCode());
        $this->assertSame('approved', $transfer->fresh()->approval_status);
        $this->assertSame(10.0, $this->stock($source->id));

        $missingDo = app(InventoryController::class)->markTransferAsTransferred($transfer->fresh());
        $this->assertSame(422, $missingDo->getStatusCode());

        $transfer->update(['delivery_order_file' => 'inventory-transfers/do/transfer.pdf']);
        $transferred = app(InventoryController::class)->markTransferAsTransferred($transfer->fresh());
        $this->assertSame(200, $transferred->getStatusCode());
        $this->assertSame(6.0, $this->stock($source->id));
        $this->assertSame(0.0, $this->stock($destination->id));

        $duplicateTransferred = app(InventoryController::class)->markTransferAsTransferred($transfer->fresh());
        $this->assertSame(422, $duplicateTransferred->getStatusCode());
        $this->assertSame(6.0, $this->stock($source->id));

        $received = app(InventoryController::class)->markTransferAsReceived($transfer->fresh());
        $this->assertSame(200, $received->getStatusCode());
        $this->assertSame(6.0, $this->stock($source->id));
        $this->assertSame(4.0, $this->stock($destination->id));

        $duplicateReceived = app(InventoryController::class)->markTransferAsReceived($transfer->fresh());
        $this->assertSame(422, $duplicateReceived->getStatusCode());
        $this->assertSame(4.0, $this->stock($destination->id));
        $this->assertSame(['submitted', 'approved', 'transferred', 'received'], DB::table('inventory_transfer_approval_histories')->orderBy('id')->pluck('action')->all());
    }

    public function test_rejection_requires_reason_and_unlocks_transfer_for_revision(): void
    {
        Auth::login(User::findOrFail(1));
        $transfer = InventoryTransfer::create([
            'transfer_date' => now()->toDateString(),
            'from_warehouse_id' => Warehouse::create(['name' => 'Cabang A', 'is_center' => false])->id,
            'to_warehouse_id' => Warehouse::create(['name' => 'Cabang B', 'is_center' => false])->id,
            'status' => 'draft',
            'approval_status' => 'pending',
            'is_direct_branch_transfer' => true,
            'created_by' => 1,
        ]);

        Auth::login(User::findOrFail(2));
        $response = app(InventoryController::class)->rejectTransfer(
            Request::create('/reject', 'POST', ['reason' => 'Jumlah belum sesuai']),
            $transfer
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('rejected', $transfer->fresh()->approval_status);
        $this->assertSame('Jumlah belum sesuai', $transfer->fresh()->central_rejection_reason);
    }

    private function stock(int $warehouseId): float
    {
        return (float) (DB::table('warehouse_products')
            ->where('warehouse_id', $warehouseId)
            ->where('master_product_id', 1)
            ->value('quantity') ?? 0);
    }
}
