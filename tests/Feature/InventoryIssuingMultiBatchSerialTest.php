<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Mobile\MaterialVerificationController;
use App\Http\Controllers\Warehouse\InventoryIssuingController;
use App\Models\InventoryIssuing;
use App\Models\InventoryIssuingItem;
use App\Services\Warehouse\InventoryIssuingService;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Jawaban klien #7 (UAT 18 Nov 2026, WI 74): satu baris refill qty 4 boleh diambil dari
 * beberapa batch SN berbeda dengan qty berbeda (batch 1 sisa 1 botol, batch 2 sisa 1, ...).
 */
class InventoryIssuingMultiBatchSerialTest extends TestCase
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
            $table->unsignedInteger('quantity')->nullable();
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

    public function test_client_case_four_batches_of_one_bottle_cover_quantity_four(): void
    {
        // WI 74: Fragrance Alluring Floral 100 ml qty 4, each batch has one bottle left.
        $this->seedRefillProduct(11, 110, 'Fragrance Alluring Floral 100 ml');
        $this->seedItem(200, 110, quantity: 4);
        $this->seedSerial(501, 'RAF100B1', 110);
        $this->seedSerial(502, 'RAF100B2', 110);
        $this->seedSerial(503, 'RAF100B3', 110);
        $this->seedSerial(504, 'RAF100B4', 110);

        $first = $this->scan(['serial_number' => 'RAF100B1']);
        $this->assertSame('success', $first['status']);
        $this->assertSame(1, $first['data']['batch_quantity']);
        $this->assertSame(1, $first['checklist']['total_filled']);
        $this->assertSame(4, $first['checklist']['total_required']);
        $this->assertFalse($first['checklist']['all_complete']);

        // The row keeps taking batches until its quantity is covered.
        $this->scan(['serial_number' => 'RAF100B2']);
        $this->scan(['serial_number' => 'RAF100B3']);
        $last = $this->scan(['serial_number' => 'RAF100B4']);

        $this->assertSame(200, $last['data']['issuing_item_id']);
        $this->assertTrue($last['checklist']['all_complete']);
        $this->assertSame(4, $last['checklist']['total_filled']);
        $this->assertSame(4, DB::table('inventory_issuing_item_serials')->where('inventory_issuing_item_id', 200)->where('quantity', 1)->count());

        $item = InventoryIssuingItem::with(['product.productCategory', 'product.productType', 'serialLinks.serialNumber'])->findOrFail(200);
        $this->assertTrue($item->hasAllRequiredSerials());

        $service = app(InventoryIssuingService::class);
        $service->assertBatchSerialStockIsSufficient(InventoryIssuing::findOrFail(1));

        $allocated = $service->resolveAllocatedSerialNumbersForItems(InventoryIssuingItem::whereKey(200)->get())
            ->pluck('id')->sort()->values()->all();
        $this->assertSame([501, 502, 503, 504], $allocated);

        // Material Taken moves all four bottles, one from each batch.
        $moved = $service->moveSerialNumbersToTechnician(InventoryIssuing::findOrFail(1), 1, 1);
        $this->assertSame(4, $moved);
        $this->assertSame(4, DB::table('serial_numbers')->where('status', 'on_hand')->count());
    }

    public function test_single_batch_with_enough_stock_keeps_the_old_whole_row_link(): void
    {
        $this->seedRefillProduct(11, 110, 'Fragrance Alluring Floral 100 ml');
        $this->seedItem(200, 110, quantity: 4);
        foreach (range(501, 505) as $id) {
            $this->seedSerial($id, 'RAF100B1', 110);
        }

        $payload = $this->scan(['serial_number' => 'RAF100B1']);

        $this->assertTrue($payload['checklist']['all_complete']);
        $this->assertDatabaseCount('inventory_issuing_item_serials', 1);
        $this->assertNull(DB::table('inventory_issuing_item_serials')->value('quantity'));

        $allocated = app(InventoryIssuingService::class)
            ->resolveAllocatedSerialNumbersForItems(InventoryIssuingItem::whereKey(200)->get())
            ->pluck('id')->sort()->values()->all();
        $this->assertSame([501, 502, 503, 504], $allocated);
    }

    public function test_two_batches_with_different_quantities(): void
    {
        $this->seedRefillProduct(11, 110, 'Fragrance Alluring Floral 100 ml');
        $this->seedItem(200, 110, quantity: 4);
        foreach (range(501, 503) as $id) {
            $this->seedSerial($id, 'RAF100B1', 110); // batch 1: 3 bottles left
        }
        foreach (range(511, 515) as $id) {
            $this->seedSerial($id, 'RAF100B2', 110); // batch 2: 5 bottles left
        }

        $first = $this->scan(['serial_number' => 'RAF100B1']);
        $this->assertSame(3, $first['data']['batch_quantity']);
        $this->assertSame(3, $first['checklist']['total_filled']);

        $second = $this->scan(['serial_number' => 'RAF100B2']);
        $this->assertSame(1, $second['data']['batch_quantity']);
        $this->assertTrue($second['checklist']['all_complete']);

        $allocated = app(InventoryIssuingService::class)
            ->resolveAllocatedSerialNumbersForItems(InventoryIssuingItem::whereKey(200)->get())
            ->pluck('id')->sort()->values()->all();
        $this->assertSame([501, 502, 503, 511], $allocated);
    }

    public function test_partially_covered_row_cannot_reach_ready_to_issue(): void
    {
        $this->seedRefillProduct(11, 110, 'Fragrance Alluring Floral 100 ml');
        $this->seedItem(200, 110, quantity: 4);
        $this->seedSerial(501, 'RAF100B1', 110);

        $payload = $this->scan(['serial_number' => 'RAF100B1']);
        $this->assertFalse($payload['checklist']['all_complete']);
        $this->assertFalse(InventoryIssuingItem::with('product.productCategory', 'product.productType', 'serialLinks.serialNumber')->findOrFail(200)->hasAllRequiredSerials());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('belum menutup seluruh qty');

        app(InventoryIssuingService::class)->assertBatchSerialStockIsSufficient(InventoryIssuing::findOrFail(1));
    }

    public function test_operator_can_split_a_batch_and_release_it(): void
    {
        $this->seedRefillProduct(11, 110, 'Fragrance Alluring Floral 100 ml');
        $this->seedItem(200, 110, quantity: 4);
        foreach (range(501, 510) as $id) {
            $this->seedSerial($id, 'RAF100B1', 110);
        }
        foreach (range(511, 515) as $id) {
            $this->seedSerial($id, 'RAF100B2', 110);
        }

        $this->scan(['serial_number' => 'RAF100B1']); // covers all 4
        $linkA = DB::table('inventory_issuing_item_serials')->value('id');

        // Physically 2 bottles from batch 1 and 2 from batch 2.
        $edited = $this->setQuantity($linkA, 2);
        $this->assertSame('success', $edited['status']);
        $this->assertSame(2, $edited['checklist']['total_filled']);

        $second = $this->scan(['serial_number' => 'RAF100B2']);
        $this->assertSame(2, $second['data']['batch_quantity']);
        $this->assertTrue($second['checklist']['all_complete']);

        $linkB = DB::table('inventory_issuing_item_serials')->where('serial_number_id', '>=', 511)->value('id');
        $released = $this->setQuantity($linkB, 0);
        $this->assertSame('success', $released['status']);
        $this->assertSame(2, $released['checklist']['total_filled']);
        $this->assertDatabaseMissing('inventory_issuing_item_serials', ['id' => $linkB]);

        // More than the row's quantity is refused.
        $tooMany = $this->setQuantity($linkA, 5, 422);
        $this->assertStringContainsString('maksimal', $tooMany['message']);
    }

    public function test_same_batch_cannot_take_two_slots_of_one_row(): void
    {
        $this->seedRefillProduct(11, 110, 'Fragrance Alluring Floral 100 ml');
        $this->seedItem(200, 110, quantity: 4);
        $this->seedSerial(501, 'RAF100B1', 110);
        $this->seedSerial(502, 'RAF100B1', 110);

        $this->scan(['serial_number' => 'RAF100B1']);
        $again = $this->scan(['serial_number' => 'RAF100B1'], expectedStatus: 422);

        $this->assertStringContainsString('sudah dipakai di item ini', $again['message']);
        $this->assertDatabaseCount('inventory_issuing_item_serials', 1);
    }

    public function test_later_row_skips_bottles_an_earlier_multi_batch_row_took(): void
    {
        $this->seedRefillProduct(11, 110, 'Fragrance Alluring Floral 100 ml');
        $this->seedItem(200, 110, quantity: 2);
        $this->seedItem(201, 110, quantity: 2);
        foreach ([501, 502, 503] as $id) {
            $this->seedSerial($id, 'RAF100B1', 110);
        }
        $this->seedSerial(511, 'RAF100B2', 110);

        // Row 200: 1 bottle of batch 1 + 1 of batch 2.
        DB::table('inventory_issuing_items')->where('id', 200)->update(['serial_number_id' => 501]);
        $this->linkBatch(200, 501, 1, 1);
        $this->linkBatch(200, 511, 2, 1);
        // Row 201: batch 1 for its whole quantity (old-style link).
        DB::table('inventory_issuing_items')->where('id', 201)->update(['serial_number_id' => 502]);
        $this->linkBatch(201, 502, 1, null);

        $service = app(InventoryIssuingService::class);
        $later = $service->resolveAllocatedSerialNumbersForItems(InventoryIssuingItem::whereKey(201)->get())
            ->pluck('id')->sort()->values()->all();

        // Row 200 holds only ONE bottle of batch 1, so row 201 starts at the second one.
        $this->assertSame([502, 503], $later);
    }

    public function test_mobile_verification_asks_one_scan_per_prepared_batch(): void
    {
        $this->seedRefillProduct(11, 110, 'Fragrance Alluring Floral 100 ml');
        $this->seedItem(200, 110, quantity: 2);
        $this->seedSerial(501, 'RAF100B1', 110);
        $this->seedSerial(511, 'RAF100B2', 110);
        $this->linkBatch(200, 501, 1, 1);
        $this->linkBatch(200, 511, 2, 1);

        $items = InventoryIssuingItem::with(['product.productCategory', 'product.productType', 'serialNumber', 'serialLinks.serialNumber'])
            ->whereKey(200)->get();

        $method = new \ReflectionMethod(MaterialVerificationController::class, 'requiredSerialCountForGroup');
        $method->setAccessible(true);

        $this->assertSame(2, $method->invoke(app(MaterialVerificationController::class), $items));
    }

    public function test_mobile_verification_counts_distinct_batches_across_two_rows_of_one_product(): void
    {
        // Prod JKT-WI/26-11/0001 (23 Nov 2026): ADS Cleaner jadi 2 baris qty 1, tiap baris
        // kode batch berbeda. Kode lama mewajibkan tepat 1 SN, padahal 2 kode disiapkan:
        // scan 1 = kurang, scan 2 = "Terdeteksi 2 dari 1". Teknisi buntu.
        $this->seedRefillProduct(11, 110, 'Liquid ADS Cleaner (Alkohol) - 100 ml');
        $this->seedItem(200, 110, quantity: 1, roomName: 'Test Ruang 261116-2');
        $this->seedItem(201, 110, quantity: 1, roomName: 'Test Ruang 261116-2');
        $this->seedSerial(501, 'TEST2611188', 110);
        $this->seedSerial(502, 'TEST2611189', 110);
        $this->linkBatch(200, 501, 1, null);
        $this->linkBatch(201, 502, 1, null);

        $items = InventoryIssuingItem::with(['product.productCategory', 'product.productType', 'serialNumber', 'serialLinks.serialNumber'])
            ->whereIn('id', [200, 201])->get();

        $method = new \ReflectionMethod(MaterialVerificationController::class, 'requiredSerialCountForGroup');
        $method->setAccessible(true);

        $this->assertSame(2, $method->invoke(app(MaterialVerificationController::class), $items));
    }

    private function setQuantity(int $linkId, int $quantity, int $expectedStatus = 200): array
    {
        $response = app(InventoryIssuingController::class)->updateSerialLinkQuantity(
            Request::create("/warehouse/inventory-issuings/1/serial-links/{$linkId}/quantity", 'POST', ['quantity' => $quantity]),
            1,
            $linkId
        );

        $this->assertSame($expectedStatus, $response->getStatusCode(), $response->getContent());

        return $response->getData(true);
    }

    private function linkBatch(int $itemId, int $serialId, int $unitIndex, ?int $quantity): void
    {
        DB::table('inventory_issuing_item_serials')->insert([
            'inventory_issuing_item_id' => $itemId,
            'serial_number_id' => $serialId,
            'unit_index' => $unitIndex,
            'quantity' => $quantity,
            'created_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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

    private function seedRefillProduct(int $categoryId, int $productId, string $productName): void
    {
        // Serial-bearing but not a unit: the SN names a batch, not one physical machine.
        DB::table('product_categories')->insert([
            'id' => $categoryId,
            'name' => 'Fragrance',
            'has_serial_number' => true,
            'is_unit' => false,
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
