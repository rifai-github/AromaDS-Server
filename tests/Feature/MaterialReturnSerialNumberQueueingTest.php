<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobScheduleController;
use App\Models\JobSchedule;
use App\Models\MaterialReturn;
use App\Models\SerialNumber;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * completeMaterialReturn() must not silently credit WarehouseProduct.quantity for
 * Serial Number-tracked (unit) products - that leaves the SN pointing at the
 * technician (on_hand) forever (the bug QA reported). Instead it should queue the
 * item into an InventoryReceiving and move the SN to 'pending' + inventory_receiving_id,
 * exactly like JobWebCompletionService::processPartialCompletionMaterialReturnItems()
 * does for partial completion. Stock/SN only finalize to 'ready' when a warehouse
 * staff finalizes that Inventory Receiving. Bulk (non-SN) items are unaffected -
 * they still credit WarehouseProduct.quantity directly on complete.
 */
class MaterialReturnSerialNumberQueueingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_assign_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
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
            $table->boolean('is_unit')->nullable();
            $table->boolean('has_serial_number')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->boolean('is_unit')->nullable();
            $table->boolean('has_serial_number')->default(false);
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
            $table->unsignedBigInteger('inventory_receiving_id')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_issuings', function (Blueprint $table) {
            $table->id();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_issuing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_issuing_id')->nullable();
            $table->foreignId('job_assign_schedule_id')->nullable();
            $table->string('room_name')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('serial_number_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_receivings', function (Blueprint $table) {
            $table->id();
            $table->string('receiving_number')->nullable();
            $table->string('reference_no')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->unsignedBigInteger('received_from')->nullable();
            $table->unsignedBigInteger('received_by_old')->nullable();
            $table->date('schedule_date')->nullable();
            $table->date('receive_date')->nullable();
            $table->string('status')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_receiving_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_receiving_id');
            $table->unsignedBigInteger('master_product_id')->nullable();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('quantity_received', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->nullable();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->string('status')->nullable();
            $table->string('disposition')->default('keep_branch');
            $table->date('return_date')->nullable();
            $table->unsignedBigInteger('inventory_transfer_id')->nullable();
            $table->foreignId('returned_by')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_return_id');
            $table->unsignedBigInteger('material_issue_item_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('room_name')->nullable();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_issue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_issue_id')->nullable();
            $table->foreignId('job_assign_schedule_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('room_name')->nullable();
            $table->decimal('quantity', 10, 2)->default(0);
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

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->string('movement_type')->nullable();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->date('movement_date')->nullable();
            $table->string('reference_no')->nullable();
            $table->string('reference_type')->nullable();
            $table->foreignId('reference_id')->nullable();
            $table->string('movement_no')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
        });
    }

    private function makeJobAndWarehouse(): array
    {
        DB::table('job_schedules')->insert(['id' => 10, 'job_number' => 'BDG-IR/26-05/0008', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('warehouses')->insert(['id' => 1, 'name' => 'Gudang Cabang', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('job_assign_schedules')->insert(['id' => 1, 'job_schedule_id' => 10, 'created_at' => now(), 'updated_at' => now()]);

        return [JobSchedule::findOrFail(10)];
    }

    public function test_serial_number_item_is_queued_to_inventory_receiving_not_credited_directly(): void
    {
        [$job] = $this->makeJobAndWarehouse();

        DB::table('product_categories')->insert(['id' => 1, 'name' => 'Diffuser Unit', 'has_serial_number' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('master_products')->insert(['id' => 1, 'name' => 'Diffuser W300 White', 'product_category_id' => 1, 'created_at' => now(), 'updated_at' => now()]);

        DB::table('serial_numbers')->insert([
            'id' => 1, 'serial_number' => 'SN-0001', 'status' => 'on_hand', 'master_product_id' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('inventory_issuings')->insert(['id' => 1, 'status' => 'sent', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('inventory_issuing_items')->insert([
            'inventory_issuing_id' => 1, 'job_assign_schedule_id' => 1, 'room_name' => 'Ruang Meeting VIP',
            'product_id' => 1, 'serial_number_id' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('material_issue_items')->insert([
            'id' => 1, 'job_assign_schedule_id' => 1, 'product_id' => 1, 'room_name' => 'Ruang Meeting VIP',
            'quantity' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $materialReturn = MaterialReturn::create([
            'return_number' => 'BDG-RTR/26-07/0001',
            'job_schedule_id' => 10,
            'warehouse_id' => 1,
            'status' => MaterialReturn::STATUS_APPROVED,
            'return_date' => now()->toDateString(),
        ]);
        DB::table('material_return_items')->insert([
            'material_return_id' => $materialReturn->id,
            'material_issue_item_id' => 1,
            'product_id' => 1,
            'room_name' => 'Ruang Meeting VIP',
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = (new JobScheduleController())->completeMaterialReturn(
            Request::create('/operational/job-schedules/10/material-returns/'.$materialReturn->id.'/complete', 'POST'),
            $job,
            $materialReturn->id
        );

        $payload = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode(), $payload['message'] ?? 'no message');
        $this->assertStringContainsString('Serial Number', $payload['message']);
        $this->assertStringContainsString('Inventory Receiving', $payload['message']);

        // Stock must NOT be credited directly for the SN item.
        $this->assertDatabaseMissing('warehouse_products', [
            'warehouse_id' => 1,
            'master_product_id' => 1,
        ]);

        // SN queued into a new Inventory Receiving, not left dangling on_hand.
        $sn = SerialNumber::find(1);
        $this->assertNotNull($sn->inventory_receiving_id);
        $this->assertSame('pending', $sn->status);
        $this->assertSame(1, $sn->warehouse_id);

        $this->assertDatabaseHas('inventory_receivings', [
            'id' => $sn->inventory_receiving_id,
            'reference_no' => 'BDG-RTR/26-07/0001',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('inventory_receiving_items', [
            'inventory_receiving_id' => $sn->inventory_receiving_id,
            'master_product_id' => 1,
            'quantity' => 1,
        ]);
    }

    public function test_bulk_item_without_serial_number_still_credits_stock_directly(): void
    {
        [$job] = $this->makeJobAndWarehouse();

        DB::table('product_categories')->insert(['id' => 2, 'name' => 'Refill Bulk', 'has_serial_number' => false, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('master_products')->insert(['id' => 2, 'name' => 'PURE Hand Sanitizer (Gel) 1000 mL', 'product_category_id' => 2, 'created_at' => now(), 'updated_at' => now()]);

        $materialReturn = MaterialReturn::create([
            'return_number' => 'BDG-RTR/26-07/0002',
            'job_schedule_id' => 10,
            'warehouse_id' => 1,
            'status' => MaterialReturn::STATUS_APPROVED,
            'return_date' => now()->toDateString(),
        ]);
        DB::table('material_return_items')->insert([
            'material_return_id' => $materialReturn->id,
            'product_id' => 2,
            'room_name' => 'Ruang Meeting VIP',
            'quantity' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = (new JobScheduleController())->completeMaterialReturn(
            Request::create('/operational/job-schedules/10/material-returns/'.$materialReturn->id.'/complete', 'POST'),
            $job,
            $materialReturn->id
        );

        $payload = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode(), $payload['message'] ?? 'no message');
        $this->assertStringNotContainsString('Serial Number', $payload['message']);
        $this->assertDatabaseHas('warehouse_products', [
            'warehouse_id' => 1,
            'master_product_id' => 2,
            'quantity' => 3,
        ]);
        $this->assertSame(0, DB::table('inventory_receivings')->count());
    }
}
