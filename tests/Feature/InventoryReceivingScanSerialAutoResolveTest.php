<?php

namespace Tests\Feature;

use App\Http\Controllers\Warehouse\InventoryReceivingController;
use App\Models\InventoryReceiving;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Locks the continuous-scan behaviour of the Inventory Receiving SN modal.
 *
 * Receiving is split down the middle: a returned SN already exists, so it resolves its own
 * product; goods arriving new from a supplier carry a code that exists nowhere yet, so the
 * product genuinely cannot be derived and is asked for instead of guessed.
 */
class InventoryReceivingScanSerialAutoResolveTest extends TestCase
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
            $table->string('sku')->nullable();
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
            $table->date('receive_date')->nullable();
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
            ['id' => 10, 'name' => 'Unit', 'has_serial_number' => true, 'is_unit' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'name' => 'Refill', 'has_serial_number' => true, 'is_unit' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('master_products')->insert([
            ['id' => 100, 'product_category_id' => 10, 'name' => 'Diffuser W300', 'sku' => 'DIF-W300', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 101, 'product_category_id' => 10, 'name' => 'Diffuser D1000', 'sku' => 'DIF-D1000', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 200, 'product_category_id' => 11, 'name' => 'Refill Lemongrass', 'sku' => 'RFL-LEM', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 201, 'product_category_id' => 11, 'name' => 'Refill Amberwood', 'sku' => 'RFL-AMB', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    protected function tearDown(): void
    {
        foreach ([
            'audit_logs',
            'warehouse_products',
            'unit_on_walls',
            'serial_numbers',
            'inventory_receiving_items',
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

    public function test_returned_unit_serial_finds_its_own_product(): void
    {
        $this->seedReturnReceiving();
        $this->seedItem(100, quantity: 1);
        $this->seedSerial(500, 'DW300-0001', 100, status: 'on_hand');

        $payload = $this->scan(['serial_number' => 'DW300-0001']);

        $this->assertSame('success', $payload['status']);
        $this->assertSame(100, $payload['master_product_id']);
        $this->assertDatabaseHas('serial_numbers', [
            'id' => 500,
            'inventory_receiving_id' => 1,
            'status' => 'pending',
        ]);
    }

    public function test_serial_of_a_product_outside_this_receiving_is_rejected(): void
    {
        $this->seedReturnReceiving();
        $this->seedItem(100, quantity: 1);
        $this->seedSerial(510, 'D1000-0001', 101, status: 'on_hand');

        $payload = $this->scan(['serial_number' => 'D1000-0001'], expectedStatus: 422);

        $this->assertSame('error', $payload['status']);
        $this->assertStringContainsString('tidak ada di receiving ini', $payload['message']);
        $this->assertDatabaseHas('serial_numbers', ['id' => 510, 'inventory_receiving_id' => null]);
    }

    public function test_unregistered_serial_is_asked_not_guessed(): void
    {
        $this->seedFreshReceiving();
        $this->seedItem(100, quantity: 2);

        $payload = $this->scan(['serial_number' => 'BARANGBARU01']);

        $this->assertSame('unknown', $payload['status']);
        $this->assertStringContainsString('belum terdaftar', $payload['message']);
        $this->assertDatabaseCount('serial_numbers', 0);
    }

    public function test_unregistered_serial_is_created_once_the_product_is_picked(): void
    {
        $this->seedFreshReceiving();
        $this->seedItem(100, quantity: 2);

        $payload = $this->scan([
            'serial_number' => 'BARANGBARU01',
            'master_product_id' => 100,
        ]);

        $this->assertSame('success', $payload['status']);
        $this->assertDatabaseHas('serial_numbers', [
            'serial_number' => 'BARANGBARU01',
            'master_product_id' => 100,
            'inventory_receiving_id' => 1,
            'status' => 'pending',
        ]);
    }

    public function test_unit_serial_cannot_be_filed_under_a_different_product(): void
    {
        // The non-unit path always refused a mismatch; units never did, so a scan aimed at
        // the wrong product used to be filed against it silently.
        $this->seedReturnReceiving();
        $this->seedItem(100, quantity: 1);
        $this->seedItem(101, quantity: 1);
        $this->seedSerial(510, 'D1000-0001', 101, status: 'on_hand');

        $payload = $this->scan([
            'serial_number' => 'D1000-0001',
            'master_product_id' => 100,
        ], expectedStatus: 422);

        $this->assertSame('error', $payload['status']);
        $this->assertStringContainsString('Diffuser D1000', $payload['message']);
        $this->assertDatabaseHas('serial_numbers', ['id' => 510, 'inventory_receiving_id' => null]);
    }

    public function test_batch_code_shared_by_two_products_is_asked(): void
    {
        $this->seedFreshReceiving();
        $this->seedItem(200, quantity: 2);
        $this->seedItem(201, quantity: 2);
        $this->seedSerial(600, 'BATCH2609', 200);
        $this->seedSerial(601, 'BATCH2609', 201);

        $payload = $this->scan(['serial_number' => 'BATCH2609']);

        $this->assertSame('ambiguous', $payload['status']);
        $this->assertEqualsCanonicalizing([200, 201], $payload['data']['candidate_product_ids']);
    }

    public function test_shared_batch_code_resolves_when_only_one_product_still_owes_serials(): void
    {
        $this->seedFreshReceiving();
        $this->seedItem(200, quantity: 2);
        $this->seedItem(201, quantity: 1);
        $this->seedSerial(600, 'BATCH2609', 200);
        // Product 201 is already fully registered against this receiving.
        $this->seedSerial(601, 'BATCH2609', 201, receivingId: 1);

        $payload = $this->scan(['serial_number' => 'BATCH2609']);

        $this->assertSame('success', $payload['status']);
        $this->assertSame(200, $payload['master_product_id']);
    }

    public function test_checklist_reports_progress_per_product(): void
    {
        $this->seedReturnReceiving();
        $this->seedItem(100, quantity: 2);
        $this->seedItem(101, quantity: 1);
        $this->seedSerial(500, 'DW300-0001', 100, status: 'on_hand');

        $payload = $this->scan(['serial_number' => 'DW300-0001']);
        $checklist = $payload['checklist'];

        // Quantities are decimals that survive the JSON round trip as ints when whole.
        $this->assertSame(2, $checklist['total_products']);
        $this->assertEqualsWithDelta(3, $checklist['total_requested'], 0.001);
        $this->assertSame(1, $checklist['total_registered']);
        $this->assertFalse($checklist['all_complete']);

        $row = collect($checklist['rows'])->firstWhere('product_id', 100);
        $this->assertSame(1, $row['registered']);
        $this->assertEqualsWithDelta(1, $row['remaining'], 0.001);
        $this->assertSame(['DW300-0001'], $row['serials']);
        $this->assertTrue($row['is_unit']);
    }

    public function test_scan_is_refused_when_receiving_is_not_pending(): void
    {
        $this->seedReturnReceiving(status: 'completed');
        $this->seedItem(100, quantity: 1);
        $this->seedSerial(500, 'DW300-0001', 100, status: 'on_hand');

        $payload = $this->scan(['serial_number' => 'DW300-0001'], expectedStatus: 422);

        $this->assertSame('error', $payload['status']);
        $this->assertStringContainsString('pending', $payload['message']);
        $this->assertDatabaseHas('serial_numbers', ['id' => 500, 'inventory_receiving_id' => null]);
    }

    private function scan(array $body, int $expectedStatus = 200): array
    {
        $response = app(InventoryReceivingController::class)->scanSerialNumber(
            Request::create('/warehouse/inventory-receivings/1/scan-serial-number', 'POST', $body),
            InventoryReceiving::findOrFail(1)
        );

        $payload = $response->getData(true);

        $this->assertSame($expectedStatus, $response->getStatusCode(), $payload['message'] ?? '');

        return $payload;
    }

    private function seedReturnReceiving(string $status = 'pending'): void
    {
        DB::table('inventory_issuings')->insert([
            'id' => 7,
            'warehouse_id' => 5,
            'issuing_number' => 'BDG-WI/26-09/0001',
            'status' => 'sent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventory_receivings')->insert([
            'id' => 1,
            'receiving_number' => 'BDG-RR/26-09/0001',
            'reference_no' => 'BDG-WI/26-09/0001',
            'issuing_id' => 7,
            'branch_id' => 1,
            'status' => $status,
            'receive_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedFreshReceiving(string $status = 'pending'): void
    {
        DB::table('inventory_requests')->insert([
            'id' => 3,
            'request_number' => 'BDG-IR/26-09/0001',
            'warehouse_id' => 5,
            'branch_id' => 1,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventory_receivings')->insert([
            'id' => 1,
            'receiving_number' => 'BDG-RR/26-09/0002',
            'reference_no' => 'BDG-IR/26-09/0001',
            'branch_id' => 1,
            'status' => $status,
            'receive_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedItem(int $productId, float $quantity): void
    {
        DB::table('inventory_receiving_items')->insert([
            'inventory_receiving_id' => 1,
            'master_product_id' => $productId,
            'quantity' => $quantity,
            'quantity_received' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedSerial(int $id, string $serialNumber, int $productId, string $status = 'ready', ?int $receivingId = null): void
    {
        DB::table('serial_numbers')->insert([
            'id' => $id,
            'serial_number' => $serialNumber,
            'master_product_id' => $productId,
            'warehouse_id' => 5,
            'inventory_receiving_id' => $receivingId,
            'status' => $status,
            'location_type' => 'warehouse',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
