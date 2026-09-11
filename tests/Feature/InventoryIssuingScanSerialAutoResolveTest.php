<?php

namespace Tests\Feature;

use App\Http\Controllers\Warehouse\InventoryIssuingController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Locks the continuous-scan behaviour of the Inventory Issuing SN modal: a scan with no
 * issuing_item_id finds its own row, ambiguity is asked instead of guessed, and one slot
 * of a qty>1 row can be replaced without disturbing its siblings.
 */
class InventoryIssuingScanSerialAutoResolveTest extends TestCase
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
            $table->string('sku')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_product_id')->nullable();
            $table->string('file_path')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
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
            $table->string('reference_no')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('received_by')->nullable();
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
            $table->decimal('quantity_issued', 12, 2)->default(0);
            $table->decimal('quantity_received', 12, 2)->default(0);
            $table->string('room_name')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_issuing_item_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_issuing_item_id');
            $table->foreignId('serial_number_id');
            $table->integer('unit_index')->default(1);
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->string('status')->nullable();
            $table->string('location_type')->nullable();
            $table->foreignId('location_id')->nullable();
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
            'name' => 'Gudang DKI Jakarta',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventory_issuings')->insert([
            'id' => 1,
            'issuing_number' => 'JKT-WI/26-09/0001',
            'warehouse_id' => 1,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs(User::findOrFail(1));
    }

    protected function tearDown(): void
    {
        foreach ([
            'unit_on_walls',
            'serial_numbers',
            'inventory_issuing_item_serials',
            'inventory_issuing_items',
            'inventory_issuings',
            'warehouses',
            'product_photos',
            'master_products',
            'product_types',
            'product_categories',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_scan_without_item_id_resolves_its_own_row(): void
    {
        $this->seedUnitProduct(10, 100, 'ADS Dispenser ADS A1');
        $this->seedItem(200, 100, quantity: 1, roomName: 'Lobby');
        $this->seedSerial(500, 'ADS0012', 100);

        $payload = $this->scan(['serial_number' => 'ADS0012']);

        $this->assertSame('success', $payload['status']);
        $this->assertSame(200, $payload['data']['issuing_item_id']);
        $this->assertSame('Lobby', $payload['data']['room_name']);
        $this->assertDatabaseHas('inventory_issuing_item_serials', [
            'inventory_issuing_item_id' => 200,
            'serial_number_id' => 500,
            'unit_index' => 1,
        ]);
        $this->assertDatabaseHas('inventory_issuing_items', ['id' => 200, 'serial_number_id' => 500]);
    }

    public function test_scan_response_carries_the_checklist_so_the_modal_needs_no_reload(): void
    {
        $this->seedUnitProduct(10, 100, 'ADS Dispenser ADS A1');
        $this->seedItem(200, 100, quantity: 2, roomName: 'Lobby');
        $this->seedSerial(500, 'ADS0012', 100);
        $this->seedSerial(501, 'ADS0013', 100);

        $payload = $this->scan(['serial_number' => 'ADS0012']);

        $checklist = $payload['checklist'];
        $this->assertSame(2, $checklist['total_required']);
        $this->assertSame(1, $checklist['total_filled']);
        $this->assertFalse($checklist['all_complete']);

        $slots = $checklist['rows'][0]['slots'];
        $this->assertCount(2, $slots);
        $this->assertTrue($slots[0]['filled']);
        $this->assertSame('ADS0012', $slots[0]['serial_number']);
        $this->assertFalse($slots[1]['filled']);
    }

    public function test_serial_of_a_product_outside_this_issuing_is_rejected(): void
    {
        $this->seedUnitProduct(10, 100, 'ADS Dispenser ADS A1');
        $this->seedUnitProduct(11, 101, 'ADS Dispenser ADS B2');
        $this->seedItem(200, 100, quantity: 1);
        $this->seedSerial(510, 'BBB0001', 101);

        $payload = $this->scan(['serial_number' => 'BBB0001'], expectedStatus: 422);

        $this->assertSame('error', $payload['status']);
        $this->assertStringContainsString('bukan bagian dari Inventory Issuing ini', $payload['message']);
        $this->assertDatabaseCount('inventory_issuing_item_serials', 0);
    }

    public function test_same_product_in_two_rooms_is_asked_not_guessed(): void
    {
        $this->seedUnitProduct(10, 100, 'ADS Dispenser ADS A1');
        $this->seedItem(200, 100, quantity: 1, roomName: 'Lobby');
        $this->seedItem(201, 100, quantity: 1, roomName: 'Meeting Room');
        $this->seedSerial(500, 'ADS0012', 100);

        $payload = $this->scan(['serial_number' => 'ADS0012']);

        $this->assertSame('ambiguous', $payload['status']);
        $this->assertEqualsCanonicalizing([200, 201], $payload['data']['candidate_item_ids']);

        // Nothing may be written while the question is still open.
        $this->assertDatabaseCount('inventory_issuing_item_serials', 0);
    }

    public function test_ambiguity_disappears_once_the_other_room_is_full(): void
    {
        $this->seedUnitProduct(10, 100, 'ADS Dispenser ADS A1');
        $this->seedItem(200, 100, quantity: 1, roomName: 'Lobby');
        $this->seedItem(201, 100, quantity: 1, roomName: 'Meeting Room');
        $this->seedSerial(500, 'ADS0012', 100);
        $this->seedSerial(501, 'ADS0013', 100);
        $this->linkSerial(200, 500, 1);

        $payload = $this->scan(['serial_number' => 'ADS0013']);

        $this->assertSame('success', $payload['status']);
        $this->assertSame(201, $payload['data']['issuing_item_id']);
    }

    public function test_one_slot_of_a_multi_quantity_row_can_be_replaced(): void
    {
        $this->seedUnitProduct(10, 100, 'ADS Dispenser ADS A1');
        $this->seedItem(200, 100, quantity: 3, roomName: 'Lobby');
        $this->seedSerial(500, 'ADS0012', 100);
        $this->seedSerial(501, 'ADS0013', 100);
        $this->seedSerial(502, 'ADS0019', 100);
        $this->seedSerial(503, 'ADS0020', 100);
        $this->linkSerial(200, 500, 1);
        $this->linkSerial(200, 501, 2);
        $this->linkSerial(200, 502, 3);
        DB::table('inventory_issuing_items')->where('id', 200)->update(['serial_number_id' => 500]);

        $payload = $this->scan([
            'serial_number' => 'ADS0020',
            'issuing_item_id' => 200,
            'unit_index' => 2,
        ]);

        $this->assertSame('success', $payload['status']);
        $this->assertTrue($payload['data']['replaced']);

        // Only slot 2 changed; its siblings and the primary pointer are untouched.
        $this->assertDatabaseMissing('inventory_issuing_item_serials', [
            'inventory_issuing_item_id' => 200,
            'serial_number_id' => 501,
        ]);
        $this->assertDatabaseHas('inventory_issuing_item_serials', [
            'inventory_issuing_item_id' => 200,
            'serial_number_id' => 503,
            'unit_index' => 2,
        ]);
        $this->assertDatabaseHas('inventory_issuing_item_serials', [
            'inventory_issuing_item_id' => 200,
            'serial_number_id' => 500,
            'unit_index' => 1,
        ]);
        $this->assertDatabaseHas('inventory_issuing_item_serials', [
            'inventory_issuing_item_id' => 200,
            'serial_number_id' => 502,
            'unit_index' => 3,
        ]);
        $this->assertDatabaseHas('inventory_issuing_items', ['id' => 200, 'serial_number_id' => 500]);
    }

    public function test_full_multi_quantity_row_refuses_a_scan_that_names_no_slot(): void
    {
        $this->seedUnitProduct(10, 100, 'ADS Dispenser ADS A1');
        $this->seedItem(200, 100, quantity: 2, roomName: 'Lobby');
        $this->seedSerial(500, 'ADS0012', 100);
        $this->seedSerial(501, 'ADS0013', 100);
        $this->seedSerial(502, 'ADS0019', 100);
        $this->linkSerial(200, 500, 1);
        $this->linkSerial(200, 501, 2);

        $payload = $this->scan([
            'serial_number' => 'ADS0019',
            'issuing_item_id' => 200,
        ], expectedStatus: 422);

        $this->assertSame('error', $payload['status']);
        $this->assertStringContainsString('sudah lengkap', $payload['message']);
        $this->assertDatabaseMissing('inventory_issuing_item_serials', [
            'inventory_issuing_item_id' => 200,
            'serial_number_id' => 502,
        ]);
    }

    public function test_full_row_scanned_without_a_target_reports_that_every_slot_is_taken(): void
    {
        $this->seedUnitProduct(10, 100, 'ADS Dispenser ADS A1');
        $this->seedItem(200, 100, quantity: 1, roomName: 'Lobby');
        $this->seedSerial(500, 'ADS0012', 100);
        $this->seedSerial(501, 'ADS0013', 100);
        $this->linkSerial(200, 500, 1);

        $payload = $this->scan(['serial_number' => 'ADS0013'], expectedStatus: 422);

        $this->assertSame('error', $payload['status']);
        $this->assertStringContainsString('sudah terisi', $payload['message']);
    }

    public function test_the_same_serial_cannot_occupy_two_slots_of_one_row(): void
    {
        $this->seedUnitProduct(10, 100, 'ADS Dispenser ADS A1');
        $this->seedItem(200, 100, quantity: 3, roomName: 'Lobby');
        $this->seedSerial(500, 'ADS0012', 100);
        $this->linkSerial(200, 500, 1);

        $payload = $this->scan([
            'serial_number' => 'ADS0012',
            'issuing_item_id' => 200,
            'unit_index' => 2,
        ], expectedStatus: 422);

        $this->assertSame('error', $payload['status']);
        $this->assertStringContainsString('sudah terpasang di slot 1', $payload['message']);
        $this->assertDatabaseCount('inventory_issuing_item_serials', 1);
    }

    private function scan(array $body, int $expectedStatus = 200): array
    {
        $response = app(InventoryIssuingController::class)->scanSerialNumber(
            Request::create('/warehouse/inventory-issuings/1/scan-serial-number', 'POST', $body),
            1
        );

        $this->assertSame($expectedStatus, $response->getStatusCode());

        return $response->getData(true);
    }

    private function seedUnitProduct(int $categoryId, int $productId, string $productName): void
    {
        DB::table('product_categories')->insert([
            'id' => $categoryId,
            'name' => 'Diffuser',
            'has_serial_number' => true,
            'is_unit' => true,
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

    private function seedItem(int $itemId, int $productId, float $quantity = 1, ?string $roomName = null): void
    {
        DB::table('inventory_issuing_items')->insert([
            'id' => $itemId,
            'inventory_issuing_id' => 1,
            'product_id' => $productId,
            'serial_number_id' => null,
            'quantity_requested' => $quantity,
            'quantity_issued' => $quantity,
            'quantity_received' => 0,
            'room_name' => $roomName,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedSerial(int $serialId, string $serialNumber, int $productId): void
    {
        DB::table('serial_numbers')->insert([
            'id' => $serialId,
            'serial_number' => $serialNumber,
            'master_product_id' => $productId,
            'warehouse_id' => 1,
            'status' => 'ready',
            'location_type' => 'warehouse',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function linkSerial(int $itemId, int $serialId, int $unitIndex): void
    {
        DB::table('inventory_issuing_item_serials')->insert([
            'inventory_issuing_item_id' => $itemId,
            'serial_number_id' => $serialId,
            'unit_index' => $unitIndex,
            'created_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
