<?php

namespace Tests\Feature;

use App\Http\Controllers\Warehouse\InventoryReceivingController;
use App\Http\Controllers\Warehouse\SerialNumberController;
use App\Models\InventoryReceiving;
use App\Models\SerialNumber;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InventoryReceivingReturnedSerialStatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable();
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_center')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->boolean('has_serial_number')->default(false);
            $table->boolean('is_unit')->default(false);
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
            $table->foreignId('product_category_id')->nullable();
            $table->foreignId('product_type_id')->nullable();
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_issuings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->nullable();
            $table->string('issuing_number')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_receivings', function (Blueprint $table) {
            $table->id();
            $table->string('receiving_number')->nullable();
            $table->string('reference_no')->nullable();
            $table->foreignId('issuing_id')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->foreignId('received_from')->nullable();
            $table->foreignId('received_by_old')->nullable();
            $table->date('receive_date')->nullable();
            $table->date('schedule_date')->nullable();
            $table->string('status')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_request_id')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('issued_qty', 10, 2)->nullable();
            $table->decimal('received_qty', 10, 2)->nullable();
            $table->decimal('returned_qty', 10, 2)->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_receiving_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_receiving_id')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('quantity_received', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->string('status')->nullable();
            $table->string('return_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_return_id')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->string('return_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
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
            $table->string('movement_no')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('action')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('changed_fields')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('page_name')->nullable();
            $table->string('module_name')->nullable();
            $table->timestamps();
        });

        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Warehouse Admin',
            'email' => 'warehouse@example.test',
            'password' => 'password',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Auth::login(User::findOrFail(1));

        DB::table('branches')->insert([
            'id' => 1,
            'code' => 'BDG',
            'name' => 'Bandung',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('warehouses')->insert([
            'id' => 5,
            'branch_id' => 1,
            'name' => 'Warehouse Bandung',
            'is_active' => true,
            'is_center' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_categories')->insert([
            'id' => 10,
            'name' => 'Unit',
            'has_serial_number' => true,
            'is_unit' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('master_products')->insert([
            'id' => 100,
            'product_category_id' => 10,
            'name' => 'Aroma Diffuser Premium',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        foreach ([
            'audit_logs',
            'inventory_movements',
            'warehouse_products',
            'unit_on_walls',
            'serial_numbers',
            'material_return_items',
            'material_returns',
            'job_schedules',
            'inventory_receiving_items',
            'inventory_request_items',
            'inventory_requests',
            'inventory_receivings',
            'inventory_issuings',
            'master_products',
            'product_types',
            'product_categories',
            'warehouses',
            'branches',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_finalize_receiving_releases_returned_in_use_serial_number_to_warehouse(): void
    {
        $receiving = $this->seedReceivingWithSerial(status: 'in_use');

        app(InventoryReceivingController::class)->finalize($receiving);

        $this->assertDatabaseHas('inventory_receivings', [
            'id' => $receiving->id,
            'status' => 'received',
        ]);
        $this->assertDatabaseHas('serial_numbers', [
            'id' => 200,
            'status' => 'ready',
            'location_type' => 'warehouse',
            'location_id' => 5,
            'warehouse_id' => 5,
            'inventory_receiving_id' => $receiving->id,
            'condition_status' => SerialNumber::CONDITION_SECOND_READY,
        ]);
    }

    public function test_finalize_receiving_does_not_release_serial_number_with_active_unit_on_wall(): void
    {
        $receiving = $this->seedReceivingWithSerial(status: 'in_use');

        DB::table('unit_on_walls')->insert([
            'id' => 300,
            'serial_number_id' => 200,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(InventoryReceivingController::class)->finalize($receiving);

        $this->assertDatabaseHas('serial_numbers', [
            'id' => 200,
            'status' => 'in_use',
            'location_type' => 'customer',
            'warehouse_id' => 5,
            'inventory_receiving_id' => $receiving->id,
        ]);
    }

    public function test_finalize_new_receiving_keeps_single_branch_warehouse_and_marks_condition_new(): void
    {
        $this->seedJakartaConditionWarehouses();
        $receiving = $this->seedReceivingWithSerial(status: 'pending', referenceNo: 'SUP-REC/26-06/0001');
        $receiving->update(['notes' => 'Supplier receiving stok baru.']);

        app(InventoryReceivingController::class)->finalize($receiving);

        $this->assertDatabaseHas('warehouse_products', [
            'warehouse_id' => 5,
            'master_product_id' => 100,
            'quantity' => 1,
        ]);
        $this->assertDatabaseHas('serial_numbers', [
            'id' => 200,
            'warehouse_id' => 5,
            'location_id' => 5,
            'status' => 'ready',
            'condition_status' => SerialNumber::CONDITION_NEW,
        ]);
    }

    public function test_finalize_inventory_request_receiving_uses_requested_warehouse_not_new_stock_warehouse(): void
    {
        $this->seedJakartaConditionWarehouses();
        $receiving = $this->seedReceivingWithSerial(status: 'pending', referenceNo: 'JKT-IRQ/26-06/0002');

        DB::table('inventory_requests')->insert([
            'id' => 70,
            'request_number' => 'JKT-IRQ/26-06/0002',
            'warehouse_id' => 5,
            'branch_id' => 1,
            'status' => 'shipped',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventory_request_items')->insert([
            'id' => 71,
            'inventory_request_id' => 70,
            'master_product_id' => 100,
            'quantity' => 1,
            'issued_qty' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(InventoryReceivingController::class)->finalize($receiving);

        $this->assertDatabaseHas('warehouse_products', [
            'warehouse_id' => 5,
            'master_product_id' => 100,
            'quantity' => 1,
        ]);
        $this->assertDatabaseMissing('warehouse_products', [
            'warehouse_id' => 6,
            'master_product_id' => 100,
            'quantity' => 1,
        ]);
        $this->assertDatabaseHas('serial_numbers', [
            'id' => 200,
            'warehouse_id' => 5,
            'location_id' => 5,
            'status' => 'ready',
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'warehouse_id' => 5,
            'master_product_id' => 100,
            'reference_no' => 'BDG-IRC/26-05/0015',
        ]);
        $this->assertDatabaseHas('inventory_requests', [
            'id' => 70,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('inventory_request_items', [
            'id' => 71,
            'received_qty' => 1,
            'returned_qty' => 0,
        ]);
    }

    public function test_repair_inventory_request_receiving_moves_existing_wrong_warehouse_stock_and_serials(): void
    {
        $this->seedJakartaConditionWarehouses();
        $receiving = $this->seedReceivingWithSerial(status: 'ready', referenceNo: 'JKT-IRQ/26-06/0002');

        $receiving->update([
            'receiving_number' => 'JKT-IRC/26-06/0001',
            'status' => 'received',
        ]);

        DB::table('inventory_requests')->insert([
            'id' => 70,
            'request_number' => 'JKT-IRQ/26-06/0002',
            'warehouse_id' => 5,
            'branch_id' => 1,
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('serial_numbers')->where('id', 200)->update([
            'warehouse_id' => 6,
            'location_type' => 'warehouse',
            'location_id' => 6,
            'status' => 'ready',
        ]);

        DB::table('warehouse_products')->insert([
            [
                'warehouse_id' => 6,
                'master_product_id' => 100,
                'quantity' => 5,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'warehouse_id' => 5,
                'master_product_id' => 100,
                'quantity' => 2,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('inventory_movements')->insert([
            'id' => 900,
            'warehouse_id' => 6,
            'master_product_id' => 100,
            'movement_type' => 'in',
            'quantity' => 1,
            'movement_date' => now()->toDateString(),
            'reference_no' => 'JKT-IRC/26-06/0001',
            'reference_type' => 'inventory_receiving',
            'movement_no' => 'REC-JKT-IRC/26-06/0001',
            'notes' => 'Inventory received. Receiving Number: JKT-IRC/26-06/0001',
            'created_by' => 1,
            'updated_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('warehouse:repair-inventory-request-receiving-warehouse', [
            '--receiving-number' => ['JKT-IRC/26-06/0001'],
        ])->assertSuccessful();

        $this->assertDatabaseHas('serial_numbers', [
            'id' => 200,
            'warehouse_id' => 6,
            'location_id' => 6,
        ]);

        $this->artisan('warehouse:repair-inventory-request-receiving-warehouse', [
            '--receiving-number' => ['JKT-IRC/26-06/0001'],
            '--apply' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('serial_numbers', [
            'id' => 200,
            'warehouse_id' => 5,
            'location_type' => 'warehouse',
            'location_id' => 5,
        ]);
        $this->assertDatabaseHas('warehouse_products', [
            'warehouse_id' => 6,
            'master_product_id' => 100,
            'quantity' => 4,
        ]);
        $this->assertDatabaseHas('warehouse_products', [
            'warehouse_id' => 5,
            'master_product_id' => 100,
            'quantity' => 3,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'id' => 900,
            'warehouse_id' => 5,
            'reference_no' => 'JKT-IRC/26-06/0001',
            'reference_type' => 'inventory_receiving',
        ]);
    }

    public function test_finalize_return_receiving_keeps_single_branch_warehouse_and_marks_second_ready(): void
    {
        $this->seedJakartaConditionWarehouses();
        $receiving = $this->seedReceivingWithSerial(status: 'pending', referenceNo: 'JKT-CSR/26-05/0001');
        $this->seedMaterialReturn('JKT-CSR/26-05/0001', 'returned');

        app(InventoryReceivingController::class)->finalize($receiving);

        $this->assertDatabaseHas('warehouse_products', [
            'warehouse_id' => 5,
            'master_product_id' => 100,
            'quantity' => 1,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'warehouse_id' => 5,
            'master_product_id' => 100,
            'reference_no' => 'BDG-IRC/26-05/0015',
        ]);
        $this->assertDatabaseHas('serial_numbers', [
            'id' => 200,
            'warehouse_id' => 5,
            'location_id' => 5,
            'condition_status' => SerialNumber::CONDITION_SECOND_READY,
        ]);
    }

    public function test_finalize_remove_receiving_keeps_single_branch_warehouse_and_marks_second_ready(): void
    {
        $this->seedJakartaConditionWarehouses();
        $receiving = $this->seedReceivingWithSerial(status: 'pending', referenceNo: 'JKT-RV/26-06/0001');
        $receiving->update([
            'receiving_number' => 'JKT-IRC/26-06/0004',
            'notes' => 'Auto-return dari Remove Job JKT-RV/26-06/0001 (Unit remove menunggu penerimaan gudang).',
        ]);
        DB::table('serial_numbers')->where('id', 200)->update([
            'serial_number' => 'DFJKT024',
            'notes' => 'Queued to RR JKT-IRC/26-06/0004 from Remove Job JKT-RV/26-06/0001.',
        ]);

        app(InventoryReceivingController::class)->finalize($receiving->fresh());

        $this->assertDatabaseHas('warehouse_products', [
            'warehouse_id' => 5,
            'master_product_id' => 100,
            'quantity' => 1,
        ]);
        $this->assertDatabaseHas('serial_numbers', [
            'id' => 200,
            'serial_number' => 'DFJKT024',
            'status' => 'ready',
            'warehouse_id' => 5,
            'location_id' => 5,
            'condition_status' => SerialNumber::CONDITION_SECOND_READY,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'warehouse_id' => 5,
            'master_product_id' => 100,
            'reference_no' => 'JKT-IRC/26-06/0004',
        ]);
    }

    public function test_repair_remove_receiving_no_longer_moves_to_used_warehouse_for_single_branch_flow(): void
    {
        $this->seedJakartaConditionWarehouses();
        $receiving = $this->seedReceivingWithSerial(status: 'ready', referenceNo: 'JKT-RV/26-06/0001');
        $receiving->update([
            'receiving_number' => 'JKT-IRC/26-06/0004',
            'status' => 'received',
            'notes' => 'Auto-return dari Remove Job JKT-RV/26-06/0001.',
        ]);

        DB::table('serial_numbers')->where('id', 200)->update([
            'serial_number' => 'DFJKT024',
            'warehouse_id' => 6,
            'location_type' => 'warehouse',
            'location_id' => 6,
        ]);
        DB::table('warehouse_products')->insert([
            'warehouse_id' => 6,
            'master_product_id' => 100,
            'quantity' => 1,
            'created_by' => 1,
            'updated_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('inventory_movements')->insert([
            'warehouse_id' => 6,
            'master_product_id' => 100,
            'movement_type' => 'return',
            'quantity' => 1,
            'movement_date' => now()->toDateString(),
            'reference_no' => 'JKT-IRC/26-06/0004',
            'reference_type' => 'inventory_receiving',
            'movement_no' => 'REC-JKT-IRC/26-06/0004',
            'notes' => 'Remove receiving',
            'created_by' => 1,
            'updated_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('warehouse:repair-remove-receiving-warehouse', [
            '--receiving-number' => ['JKT-IRC/26-06/0004'],
        ])->assertSuccessful();

        $this->assertDatabaseHas('serial_numbers', ['id' => 200, 'warehouse_id' => 6]);

        $this->artisan('warehouse:repair-remove-receiving-warehouse', [
            '--receiving-number' => ['JKT-IRC/26-06/0004'],
            '--apply' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('serial_numbers', [
            'id' => 200,
            'serial_number' => 'DFJKT024',
            'warehouse_id' => 6,
            'location_type' => 'warehouse',
            'location_id' => 6,
        ]);
        $this->assertDatabaseHas('warehouse_products', [
            'warehouse_id' => 6,
            'master_product_id' => 100,
            'quantity' => 1,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'warehouse_id' => 6,
            'reference_no' => 'JKT-IRC/26-06/0004',
            'reference_type' => 'inventory_receiving',
        ]);
    }

    public function test_finalize_return_receiving_keeps_single_branch_warehouse_and_marks_damaged(): void
    {
        $this->seedJakartaConditionWarehouses();
        $receiving = $this->seedReceivingWithSerial(status: 'pending', referenceNo: 'JKT-CSR/26-05/0002');
        $this->seedMaterialReturn('JKT-CSR/26-05/0002', 'damaged');

        app(InventoryReceivingController::class)->finalize($receiving);

        $this->assertDatabaseHas('warehouse_products', [
            'warehouse_id' => 5,
            'master_product_id' => 100,
            'quantity' => 1,
        ]);
        $this->assertDatabaseHas('serial_numbers', [
            'id' => 200,
            'warehouse_id' => 5,
            'location_id' => 5,
            'status' => 'ready',
            'condition_status' => SerialNumber::CONDITION_DAMAGED,
        ]);
    }

    public function test_updating_serial_status_to_broken_marks_condition_damaged_without_moving_warehouse(): void
    {
        $this->seedJakartaConditionWarehouses();
        $this->seedReceivingWithSerial(status: 'ready');

        DB::table('warehouse_products')->insert([
            'warehouse_id' => 5,
            'master_product_id' => 100,
            'quantity' => 1,
            'created_by' => 1,
            'updated_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/warehouse/serial-numbers/200', 'PUT', [
            'status' => 'broken',
            'notes' => 'Rusak',
        ]);
        $request->headers->set('Accept', 'application/json');

        $response = app(SerialNumberController::class)->update($request, SerialNumber::findOrFail(200));
        $payload = json_decode($response->getContent(), true);

        $this->assertSame('success', $payload['status']);
        $this->assertDatabaseHas('serial_numbers', [
            'id' => 200,
            'status' => 'broken',
            'condition_status' => SerialNumber::CONDITION_DAMAGED,
            'warehouse_id' => 5,
            'location_type' => 'customer',
            'location_id' => 99,
        ]);
        $this->assertDatabaseHas('warehouse_products', [
            'warehouse_id' => 5,
            'master_product_id' => 100,
            'quantity' => 1,
        ]);
    }

    public function test_saving_existing_broken_serial_marks_condition_damaged_without_moving_warehouse(): void
    {
        $this->seedJakartaConditionWarehouses();
        $this->seedReceivingWithSerial(status: 'broken');

        DB::table('warehouse_products')->insert([
            'warehouse_id' => 5,
            'master_product_id' => 100,
            'quantity' => 1,
            'created_by' => 1,
            'updated_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/warehouse/serial-numbers/200', 'PUT', [
            'status' => 'broken',
            'notes' => 'Tetap rusak',
        ]);
        $request->headers->set('Accept', 'application/json');

        $response = app(SerialNumberController::class)->update($request, SerialNumber::findOrFail(200));
        $payload = json_decode($response->getContent(), true);

        $this->assertSame('success', $payload['status']);
        $this->assertDatabaseHas('serial_numbers', [
            'id' => 200,
            'status' => 'broken',
            'condition_status' => SerialNumber::CONDITION_DAMAGED,
            'warehouse_id' => 5,
        ]);
        $this->assertDatabaseHas('warehouse_products', [
            'warehouse_id' => 5,
            'master_product_id' => 100,
            'quantity' => 1,
        ]);
    }

    private function seedReceivingWithSerial(string $status, string $referenceNo = 'BDG-CSR/26-05/0011'): InventoryReceiving
    {
        DB::table('inventory_receivings')->insert([
            'id' => 50,
            'receiving_number' => 'BDG-IRC/26-05/0015',
            'reference_no' => $referenceNo,
            'branch_id' => 1,
            'received_from' => 1,
            'received_by_old' => 1,
            'schedule_date' => now()->toDateString(),
            'status' => 'pending',
            'notes' => 'Auto-return dari Aplikasi teknisi via Job BDG-CSR/26-05/0011 (Pekerjaan tidak selesai).',
            'created_by' => 1,
            'updated_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventory_receiving_items')->insert([
            'id' => 60,
            'inventory_receiving_id' => 50,
            'master_product_id' => 100,
            'quantity' => 1,
            'quantity_received' => 1,
            'notes' => 'Auto-return dari Room Melati',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('serial_numbers')->insert([
            'id' => 200,
            'serial_number' => 'BDG1001',
            'status' => $status,
            'location_type' => 'customer',
            'location_id' => 99,
            'warehouse_id' => 5,
            'master_product_id' => 100,
            'inventory_receiving_id' => 50,
            'notes' => 'Queued to RR BDG-IRC/26-05/0015 from incomplete Job BDG-CSR/26-05/0011.',
            'created_by' => 1,
            'updated_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return InventoryReceiving::findOrFail(50);
    }

    private function seedJakartaConditionWarehouses(): void
    {
        DB::table('warehouses')->insert([
            [
                'id' => 6,
                'branch_id' => 1,
                'name' => 'Warehouse Jakarta Baru',
                'is_active' => true,
                'is_center' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 7,
                'branch_id' => 1,
                'name' => 'Warehouse Jakarta Bekas',
                'is_active' => true,
                'is_center' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 8,
                'branch_id' => 1,
                'name' => 'Warehouse Jakarta Rusak',
                'is_active' => true,
                'is_center' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    private function seedMaterialReturn(string $jobNumber, string $returnReason): void
    {
        DB::table('job_schedules')->insert([
            'id' => 90,
            'job_number' => $jobNumber,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('material_returns')->insert([
            'id' => 91,
            'job_schedule_id' => 90,
            'warehouse_id' => 5,
            'status' => 'approved',
            'return_reason' => $returnReason,
            'notes' => $returnReason,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('material_return_items')->insert([
            'id' => 92,
            'material_return_id' => 91,
            'product_id' => 100,
            'return_reason' => $returnReason,
            'notes' => $returnReason,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
