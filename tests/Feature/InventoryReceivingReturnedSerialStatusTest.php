<?php

namespace Tests\Feature;

use App\Http\Controllers\Warehouse\InventoryReceivingController;
use App\Models\InventoryReceiving;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
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
            $table->string('status')->nullable();
            $table->timestamp('completed_at')->nullable();
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

        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number')->nullable();
            $table->string('status')->nullable();
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
            'inventory_movements',
            'warehouse_products',
            'unit_on_walls',
            'serial_numbers',
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

    private function seedReceivingWithSerial(string $status): InventoryReceiving
    {
        DB::table('inventory_receivings')->insert([
            'id' => 50,
            'receiving_number' => 'BDG-IRC/26-05/0015',
            'reference_no' => 'BDG-CSR/26-05/0011',
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
}
