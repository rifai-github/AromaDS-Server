<?php

namespace Tests\Feature;

use App\Http\Controllers\Warehouse\InventoryIssuingController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InventoryIssuingRefillSerialReuseTest extends TestCase
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
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_issuings', function (Blueprint $table) {
            $table->id();
            $table->string('issuing_number')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_issuing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_issuing_id')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->foreignId('serial_number_id')->nullable();
            $table->decimal('quantity_requested', 12, 2)->default(1);
            $table->string('room_name')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->string('status')->nullable();
            $table->string('location_type')->nullable();
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

        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Tester',
            'email' => 'tester@example.test',
            'password' => 'password',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('warehouses')->insert([
            'id' => 1,
            'name' => 'Warehouse Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        foreach ([
            'unit_on_walls',
            'serial_numbers',
            'inventory_issuing_items',
            'inventory_issuings',
            'warehouses',
            'master_products',
            'product_types',
            'product_categories',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_refill_serial_can_be_reused_in_another_prepared_inventory_issuing(): void
    {
        $this->seedProduct(10, 100, 'Aroma Diffuser Premium', hasSerialNumber: true, isUnit: false);
        $this->seedIssuingPair(productId: 100, serialNumberId: 500, serialNumber: 'BDG1001');
        $this->actingAs(User::findOrFail(1));

        $response = app(InventoryIssuingController::class)->scanSerialNumber(Request::create(
            '/warehouse/inventory-issuings/2/scan-serial-number',
            'POST',
            ['issuing_item_id' => 200, 'serial_number' => 'BDG1001']
        ), 2);

        $payload = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('success', $payload['status']);
        $this->assertDatabaseHas('inventory_issuing_items', [
            'id' => 200,
            'serial_number_id' => 500,
        ]);
    }

    public function test_unit_serial_still_cannot_be_reused_in_another_prepared_inventory_issuing(): void
    {
        $this->seedProduct(11, 101, 'Premium Diffuser Unit', hasSerialNumber: true, isUnit: true);
        $this->seedIssuingPair(productId: 101, serialNumberId: 501, serialNumber: 'UNIT1001');
        $this->actingAs(User::findOrFail(1));

        $response = app(InventoryIssuingController::class)->scanSerialNumber(Request::create(
            '/warehouse/inventory-issuings/2/scan-serial-number',
            'POST',
            ['issuing_item_id' => 200, 'serial_number' => 'UNIT1001']
        ), 2);

        $payload = $response->getData(true);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('error', $payload['status']);
        $this->assertStringContainsString('masih dipakai di Inventory Issuing', $payload['message']);
    }

    public function test_available_serial_list_keeps_reused_refill_serials_but_excludes_reused_unit_serials(): void
    {
        $this->seedProduct(10, 100, 'Aroma Diffuser Premium', hasSerialNumber: true, isUnit: false);
        $this->seedProduct(11, 101, 'Premium Diffuser Unit', hasSerialNumber: true, isUnit: true);
        $this->seedIssuingPair(productId: 100, serialNumberId: 500, serialNumber: 'BDG1001');
        $this->seedIssuingPair(
            productId: 101,
            serialNumberId: 501,
            serialNumber: 'UNIT1001',
            firstIssuingId: 3,
            secondIssuingId: 4,
            firstItemId: 300,
            secondItemId: 400
        );

        $controller = app(InventoryIssuingController::class);

        $refillPayload = $controller->getAvailableSerials(Request::create(
            '/warehouse/inventory-issuings/products/100/serials',
            'GET',
            ['warehouse_id' => 1]
        ), 100)->getData(true);

        $unitPayload = $controller->getAvailableSerials(Request::create(
            '/warehouse/inventory-issuings/products/101/serials',
            'GET',
            ['warehouse_id' => 1]
        ), 101)->getData(true);

        $this->assertContains('BDG1001', array_column($refillPayload['data'], 'serial_number'));
        $this->assertNotContains('UNIT1001', array_column($unitPayload['data'], 'serial_number'));
    }

    private function seedProduct(int $categoryId, int $productId, string $productName, bool $hasSerialNumber, bool $isUnit): void
    {
        DB::table('product_categories')->insert([
            'id' => $categoryId,
            'name' => $isUnit ? 'Unit' : 'Refill',
            'has_serial_number' => $hasSerialNumber,
            'is_unit' => $isUnit,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('master_products')->insert([
            'id' => $productId,
            'product_category_id' => $categoryId,
            'name' => $productName,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedIssuingPair(
        int $productId,
        int $serialNumberId,
        string $serialNumber,
        int $firstIssuingId = 1,
        int $secondIssuingId = 2,
        int $firstItemId = 100,
        int $secondItemId = 200
    ): void
    {
        DB::table('serial_numbers')->insert([
            'id' => $serialNumberId,
            'serial_number' => $serialNumber,
            'master_product_id' => $productId,
            'warehouse_id' => 1,
            'status' => 'ready',
            'location_type' => 'warehouse',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventory_issuings')->insert([
            ['id' => $firstIssuingId, 'issuing_number' => 'BDG-WI/26-05/0028', 'warehouse_id' => 1, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()],
            ['id' => $secondIssuingId, 'issuing_number' => 'BDG-WI/26-05/0029', 'warehouse_id' => 1, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('inventory_issuing_items')->insert([
            [
                'id' => $firstItemId,
                'inventory_issuing_id' => $firstIssuingId,
                'product_id' => $productId,
                'serial_number_id' => $serialNumberId,
                'quantity_requested' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => $secondItemId,
                'inventory_issuing_id' => $secondIssuingId,
                'product_id' => $productId,
                'serial_number_id' => null,
                'quantity_requested' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
