<?php

namespace Tests\Feature;

use App\Http\Controllers\Warehouse\StockOpnameController;
use App\Models\StockOpname;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Locks the continuous-scan behaviour of the Stock Opname SN modal: a scan finds its own
 * product row, a double scan never inflates physical stock, and codes the system cannot
 * place (unregistered, or shared across products) are asked rather than guessed.
 */
class StockOpnameScanSerialAutoResolveTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('roles')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Permission plumbing touched by userCanViewSystemStock(); left empty so the
        // acting user is a plain one and system stock must stay masked.
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('role_id')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->nullable();
            $table->foreignId('permission_id')->nullable();
        });

        Schema::create('user_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('permission_id')->nullable();
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
            $table->foreignId('branch_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->string('opname_no')->nullable();
            $table->string('opname_number')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('person_responsible')->nullable();
            $table->date('opname_date')->nullable();
            $table->string('status')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('stock_opname_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->integer('system_stock')->default(0);
            $table->integer('physical_stock')->nullable();
            $table->integer('variance')->default(0);
            $table->text('notes')->nullable();
            $table->json('scanned_serial_numbers')->nullable();
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
            'name' => 'Petugas Gudang',
            'email' => 'gudang@example.test',
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

        DB::table('warehouses')->insert([
            'id' => 2,
            'name' => 'Gudang Surabaya',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('stock_opnames')->insert([
            'id' => 1,
            'opname_no' => 'JKT-SO/26-09/0001',
            'opname_number' => 'JKT-SO/26-09/0001',
            'warehouse_id' => 1,
            'status' => 'in-progress',
            'opname_date' => now()->toDateString(),
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
            'stock_opname_details',
            'stock_opnames',
            'warehouses',
            'product_photos',
            'master_products',
            'product_types',
            'product_categories',
            'user_permission',
            'role_permissions',
            'permissions',
            'user_roles',
            'roles',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_scan_finds_its_own_product_row(): void
    {
        $this->seedUnitProduct(10, 100, 'Diffuser W300 White');
        $this->seedDetail(200, 100, systemStock: 3);
        $this->seedSerial(500, 'DW300W2606031', 100);

        $payload = $this->scan(['serial_number' => 'DW300W2606031']);

        $this->assertSame('success', $payload['status']);
        $this->assertSame(200, $payload['data']['detail_id']);
        $this->assertSame(1, $payload['data']['scanned_count']);

        $detail = DB::table('stock_opname_details')->find(200);
        $this->assertSame(['DW300W2606031'], json_decode($detail->scanned_serial_numbers, true));
        $this->assertSame(1, (int) $detail->physical_stock);
        $this->assertSame(-2, (int) $detail->variance);
    }

    public function test_second_scan_of_the_same_unit_does_not_inflate_physical_stock(): void
    {
        $this->seedUnitProduct(10, 100, 'Diffuser W300 White');
        $this->seedDetail(200, 100, systemStock: 3);
        $this->seedSerial(500, 'DW300W2606031', 100);

        $this->scan(['serial_number' => 'DW300W2606031']);
        $payload = $this->scan(['serial_number' => 'DW300W2606031']);

        $this->assertSame('duplicate', $payload['status']);
        $this->assertStringContainsString('Tidak dihitung dua kali', $payload['message']);

        $detail = DB::table('stock_opname_details')->find(200);
        $this->assertSame(1, (int) $detail->physical_stock);
        $this->assertCount(1, json_decode($detail->scanned_serial_numbers, true));
    }

    public function test_batch_code_scanned_twice_counts_two_bottles(): void
    {
        // A refill batch code is printed on every bottle, so the same code twice is two
        // items - the opposite of a unit SN. Locked alongside the duplicate-preserving
        // rule in StockOpnameBlindCountVisibilityTest.
        $this->seedUnitProduct(10, 100, 'Refill Lemongrass 100ml', isUnit: false);
        $this->seedDetail(200, 100, systemStock: 1);
        $this->seedSerial(500, 'BATCH2609', 100);

        $this->scan(['serial_number' => 'BATCH2609']);
        $payload = $this->scan(['serial_number' => 'BATCH2609']);

        $this->assertSame('success', $payload['status']);
        $this->assertSame(2, $payload['data']['scanned_count']);

        $detail = DB::table('stock_opname_details')->find(200);
        $this->assertSame(['BATCH2609', 'BATCH2609'], json_decode($detail->scanned_serial_numbers, true));
        $this->assertSame(2, (int) $detail->physical_stock);
        $this->assertSame(1, (int) $detail->variance);
    }

    public function test_removing_one_batch_chip_leaves_the_other_occurrences_counted(): void
    {
        $this->seedUnitProduct(10, 100, 'Refill Lemongrass 100ml', isUnit: false);
        $this->seedDetail(200, 100, systemStock: 3);
        $this->seedSerial(500, 'BATCH2609', 100);

        $this->scan(['serial_number' => 'BATCH2609']);
        $this->scan(['serial_number' => 'BATCH2609']);
        $this->scan(['serial_number' => 'BATCH2609']);

        $response = app(StockOpnameController::class)->removeSerialNumber(
            Request::create('/warehouse/stock-opnames/1/remove-serial-number', 'POST', [
                'detail_id' => 200,
                'serial_number' => 'BATCH2609',
            ]),
            StockOpname::findOrFail(1)
        );

        $this->assertSame('success', $response->getData(true)['status']);

        $detail = DB::table('stock_opname_details')->find(200);
        $this->assertSame(['BATCH2609', 'BATCH2609'], json_decode($detail->scanned_serial_numbers, true));
        $this->assertSame(2, (int) $detail->physical_stock);
    }

    public function test_unregistered_serial_is_asked_not_guessed(): void
    {
        $this->seedUnitProduct(10, 100, 'Diffuser W300 White');
        $this->seedDetail(200, 100, systemStock: 3);

        $payload = $this->scan(['serial_number' => 'BARANGTEMUAN01']);

        $this->assertSame('unknown', $payload['status']);
        $this->assertStringContainsString('belum terdaftar', $payload['message']);

        $detail = DB::table('stock_opname_details')->find(200);
        $this->assertNull($detail->scanned_serial_numbers);
        $this->assertNull($detail->physical_stock);
    }

    public function test_unregistered_serial_is_recorded_once_the_product_is_picked(): void
    {
        $this->seedUnitProduct(10, 100, 'Diffuser W300 White');
        $this->seedDetail(200, 100, systemStock: 3);

        $payload = $this->scan([
            'serial_number' => 'BARANGTEMUAN01',
            'detail_id' => 200,
        ]);

        $this->assertSame('success', $payload['status']);
        $this->assertSame('SN belum terdaftar - akan dibuat saat adjustment.', $payload['data']['warehouse_note']);

        $detail = DB::table('stock_opname_details')->find(200);
        $this->assertSame(['BARANGTEMUAN01'], json_decode($detail->scanned_serial_numbers, true));
    }

    public function test_serial_shared_by_two_products_is_asked(): void
    {
        $this->seedUnitProduct(10, 100, 'Refill Lemongrass 100ml', isUnit: false);
        $this->seedUnitProduct(11, 101, 'Refill Amberwood 100ml', isUnit: false);
        $this->seedDetail(200, 100, systemStock: 2);
        $this->seedDetail(201, 101, systemStock: 2);
        $this->seedSerial(500, 'BATCH2609', 100);
        $this->seedSerial(501, 'BATCH2609', 101);

        $payload = $this->scan(['serial_number' => 'BATCH2609']);

        $this->assertSame('ambiguous', $payload['status']);
        $this->assertEqualsCanonicalizing([200, 201], $payload['data']['candidate_detail_ids']);
        $this->assertNull(DB::table('stock_opname_details')->find(200)->physical_stock);
        $this->assertNull(DB::table('stock_opname_details')->find(201)->physical_stock);
    }

    public function test_serial_of_a_product_outside_this_opname_is_rejected(): void
    {
        $this->seedUnitProduct(10, 100, 'Diffuser W300 White');
        $this->seedUnitProduct(11, 101, 'Diffuser D1000');
        $this->seedDetail(200, 100, systemStock: 3);
        $this->seedSerial(510, 'D10002606031', 101);

        $payload = $this->scan(['serial_number' => 'D10002606031'], expectedStatus: 422);

        $this->assertSame('error', $payload['status']);
        $this->assertStringContainsString('tidak ada di daftar opname ini', $payload['message']);
    }

    public function test_serial_already_counted_under_another_product_is_rejected(): void
    {
        $this->seedUnitProduct(10, 100, 'Diffuser W300 White');
        $this->seedUnitProduct(11, 101, 'Diffuser D1000');
        $this->seedDetail(200, 100, systemStock: 3);
        $this->seedDetail(201, 101, systemStock: 3);
        $this->seedSerial(500, 'SHARED001', 101);

        // Mis-filed by hand under the wrong product first.
        $this->scan(['serial_number' => 'SHARED001', 'detail_id' => 200]);

        $payload = $this->scan(['serial_number' => 'SHARED001'], expectedStatus: 422);

        $this->assertSame('error', $payload['status']);
        $this->assertStringContainsString('Diffuser W300 White', $payload['message']);
        $this->assertNull(DB::table('stock_opname_details')->find(201)->physical_stock);
    }

    public function test_serial_registered_in_another_warehouse_is_recorded_with_a_note(): void
    {
        $this->seedUnitProduct(10, 100, 'Diffuser W300 White');
        $this->seedDetail(200, 100, systemStock: 3);
        $this->seedSerial(500, 'DW300W2606031', 100, warehouseId: 2);

        $payload = $this->scan(['serial_number' => 'DW300W2606031']);

        $this->assertSame('success', $payload['status']);
        $this->assertStringContainsString('Gudang Surabaya', $payload['data']['warehouse_note']);
        $this->assertSame(1, (int) DB::table('stock_opname_details')->find(200)->physical_stock);
    }

    public function test_removing_a_serial_recounts_physical_stock(): void
    {
        $this->seedUnitProduct(10, 100, 'Diffuser W300 White');
        $this->seedDetail(200, 100, systemStock: 3);
        $this->seedSerial(500, 'DW300W2606031', 100);
        $this->seedSerial(501, 'DW300W2606032', 100);

        $this->scan(['serial_number' => 'DW300W2606031']);
        $this->scan(['serial_number' => 'DW300W2606032']);
        $this->assertSame(2, (int) DB::table('stock_opname_details')->find(200)->physical_stock);

        $response = app(StockOpnameController::class)->removeSerialNumber(
            Request::create('/warehouse/stock-opnames/1/remove-serial-number', 'POST', [
                'detail_id' => 200,
                'serial_number' => 'DW300W2606031',
            ]),
            StockOpname::findOrFail(1)
        );

        $payload = $response->getData(true);
        $this->assertSame('success', $payload['status']);

        $detail = DB::table('stock_opname_details')->find(200);
        $this->assertSame(['DW300W2606032'], json_decode($detail->scanned_serial_numbers, true));
        $this->assertSame(1, (int) $detail->physical_stock);
        $this->assertSame(-2, (int) $detail->variance);
    }

    public function test_scan_is_refused_when_opname_is_not_in_progress(): void
    {
        $this->seedUnitProduct(10, 100, 'Diffuser W300 White');
        $this->seedDetail(200, 100, systemStock: 3);
        $this->seedSerial(500, 'DW300W2606031', 100);
        DB::table('stock_opnames')->where('id', 1)->update(['status' => 'completed']);

        $payload = $this->scan(['serial_number' => 'DW300W2606031'], expectedStatus: 422);

        $this->assertSame('error', $payload['status']);
        $this->assertStringContainsString('In Progress', $payload['message']);
        $this->assertNull(DB::table('stock_opname_details')->find(200)->physical_stock);
    }

    public function test_checklist_masks_system_stock_from_a_user_without_the_permission(): void
    {
        $this->seedUnitProduct(10, 100, 'Diffuser W300 White');
        $this->seedDetail(200, 100, systemStock: 3);
        $this->seedSerial(500, 'DW300W2606031', 100);

        $payload = $this->scan(['serial_number' => 'DW300W2606031']);
        $checklist = $payload['checklist'];

        $this->assertFalse($checklist['can_view_system_stock']);
        $this->assertNull($checklist['rows'][0]['system_stock']);
        $this->assertNull($checklist['rows'][0]['variance']);
        $this->assertSame(1, $checklist['total_scanned']);
        $this->assertSame(1, $checklist['counted_rows']);
    }

    private function scan(array $body, int $expectedStatus = 200): array
    {
        $response = app(StockOpnameController::class)->scanSerialNumber(
            Request::create('/warehouse/stock-opnames/1/scan-serial-number', 'POST', $body),
            StockOpname::findOrFail(1)
        );

        $payload = $response->getData(true);

        $this->assertSame($expectedStatus, $response->getStatusCode(), $payload['message'] ?? '');

        return $payload;
    }

    private function seedUnitProduct(int $categoryId, int $productId, string $productName, bool $isUnit = true): void
    {
        DB::table('product_categories')->insert([
            'id' => $categoryId,
            'name' => $isUnit ? 'Diffuser' : 'Refill',
            'has_serial_number' => true,
            'is_unit' => $isUnit,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('master_products')->insert([
            'id' => $productId,
            'product_category_id' => $categoryId,
            'name' => $productName,
            'sku' => 'SKU-' . $productId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedDetail(int $detailId, int $productId, int $systemStock): void
    {
        DB::table('stock_opname_details')->insert([
            'id' => $detailId,
            'stock_opname_id' => 1,
            'master_product_id' => $productId,
            'system_stock' => $systemStock,
            'physical_stock' => null,
            'variance' => -$systemStock,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedSerial(int $serialId, string $serialNumber, int $productId, int $warehouseId = 1): void
    {
        DB::table('serial_numbers')->insert([
            'id' => $serialId,
            'serial_number' => $serialNumber,
            'master_product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'status' => 'ready',
            'location_type' => 'warehouse',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
