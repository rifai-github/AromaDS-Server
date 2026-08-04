<?php

namespace Tests\Feature;

use App\Models\InventoryIssuing;
use App\Models\User;
use App\Services\Warehouse\InventoryIssuingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Ready to Issue posts stock once. Batch/refill rows carry ONE SN code for N units,
 * so the SN-completeness check only demands 1 linked SN no matter the qty -- the only
 * other gate was the aggregate warehouse_products quantity, which counts every code of
 * the product together. A qty=2 row backed by a single physical SN row therefore passed
 * Ready to Issue, deducted 2 from stock but flipped only 1 SN to in_use, and the job
 * carried on all the way to Done Job.
 */
class InventoryIssuingBatchSerialStockGuardTest extends TestCase
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

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
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

        Schema::create('warehouse_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->string('movement_type')->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->date('movement_date')->nullable();
            $table->string('reference_no')->nullable();
            $table->string('reference_type')->nullable();
            $table->string('movement_no')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_issuings', function (Blueprint $table) {
            $table->id();
            $table->string('issuing_number')->nullable();
            $table->string('reference_no')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->foreignId('received_by')->nullable();
            $table->date('issue_date')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_issuing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_issuing_id')->nullable();
            $table->foreignId('job_assign_schedule_id')->nullable();
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

        DB::table('users')->insert(['id' => 1, 'name' => 'putri', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('branches')->insert(['id' => 1, 'name' => 'Surabaya', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('warehouses')->insert(['id' => 2, 'name' => 'Gudang Surabaya', 'created_at' => now(), 'updated_at' => now()]);

        // REFILL: has serial numbers, but is_unit = false -> one batch code per row.
        DB::table('product_categories')->insert([
            'id' => 20, 'name' => 'Refill', 'has_serial_number' => true, 'is_unit' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('master_products')->insert([
            'id' => 24, 'product_category_id' => 20, 'name' => 'Fragrance Ginger Blossom 50 ml',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // 25 physical bottles in the warehouse, but RGB50001 backs only one of them.
        DB::table('warehouse_products')->insert([
            'id' => 1, 'warehouse_id' => 2, 'master_product_id' => 24, 'quantity' => 25,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        foreach ([
            'serial_numbers', 'inventory_issuing_item_serials', 'inventory_issuing_items',
            'inventory_issuings', 'inventory_movements', 'warehouse_products', 'warehouses',
            'branches', 'master_products', 'product_types', 'product_categories', 'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }
        parent::tearDown();
    }

    private function seedSerialRows(int $count): void
    {
        $rows = [];
        for ($i = 0; $i < $count; $i++) {
            $rows[] = [
                'id' => 900 + $i,
                'serial_number' => 'RGB50001',
                'master_product_id' => 24,
                'warehouse_id' => 2,
                'status' => 'ready',
                'location_type' => 'warehouse',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('serial_numbers')->insert($rows);
    }

    private function seedIssuing(int $quantityRequested): InventoryIssuing
    {
        DB::table('inventory_issuings')->insert([
            'id' => 154, 'issuing_number' => 'SBY-WI/26-08/0003', 'reference_no' => 'SBY-MI/26-08/0003',
            'warehouse_id' => 2, 'branch_id' => 1, 'received_by' => 1, 'issue_date' => now()->toDateString(),
            'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('inventory_issuing_items')->insert([
            'id' => 300, 'inventory_issuing_id' => 154, 'product_id' => 24, 'serial_number_id' => 900,
            'quantity_requested' => $quantityRequested, 'room_name' => 'Ruang IF Before Service',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('inventory_issuing_item_serials')->insert([
            'inventory_issuing_item_id' => 300, 'serial_number_id' => 900, 'unit_index' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return InventoryIssuing::findOrFail(154);
    }

    public function test_ready_to_issue_is_blocked_when_batch_serial_has_fewer_rows_than_quantity(): void
    {
        $this->actingAs(User::findOrFail(1));

        $this->seedSerialRows(1);
        $issuing = $this->seedIssuing(2);

        try {
            app(InventoryIssuingService::class)->postReadyStockIfMissing($issuing);
            $this->fail('Expected Ready to Issue to be refused for a qty=2 row backed by a single RGB50001 row.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('RGB50001', $e->getMessage());
            $this->assertStringContainsString('butuh 2, tersedia 1', $e->getMessage());
        }

        $this->assertSame(25.0, (float) DB::table('warehouse_products')->find(1)->quantity, 'Stock must stay untouched when Ready to Issue is refused');
        $this->assertSame(0, DB::table('inventory_movements')->count(), 'No stock movement may be written for a refused Ready to Issue');
    }

    public function test_ready_to_issue_passes_when_batch_serial_has_enough_rows(): void
    {
        $this->actingAs(User::findOrFail(1));

        $this->seedSerialRows(2);
        $issuing = $this->seedIssuing(2);

        app(InventoryIssuingService::class)->postReadyStockIfMissing($issuing);

        $this->assertSame(23.0, (float) DB::table('warehouse_products')->find(1)->quantity);
        $this->assertSame(1, DB::table('inventory_movements')->count());
    }

    public function test_quantity_one_batch_row_is_unaffected(): void
    {
        $this->actingAs(User::findOrFail(1));

        $this->seedSerialRows(1);
        $issuing = $this->seedIssuing(1);

        app(InventoryIssuingService::class)->postReadyStockIfMissing($issuing);

        $this->assertSame(24.0, (float) DB::table('warehouse_products')->find(1)->quantity);
    }

    public function test_units_already_claimed_by_another_open_issuing_do_not_count_as_available(): void
    {
        $this->actingAs(User::findOrFail(1));

        $this->seedSerialRows(2);

        // An earlier, still-open (pending) issuing already reserved 1 unit of this batch code.
        DB::table('inventory_issuings')->insert([
            'id' => 153, 'issuing_number' => 'SBY-WI/26-08/0002', 'reference_no' => 'SBY-MI/26-08/0002',
            'warehouse_id' => 2, 'branch_id' => 1, 'received_by' => 1, 'status' => 'pending',
            'created_at' => now()->subDay(), 'updated_at' => now()->subDay(),
        ]);
        DB::table('inventory_issuing_items')->insert([
            'id' => 299, 'inventory_issuing_id' => 153, 'product_id' => 24, 'serial_number_id' => 901,
            'quantity_requested' => 1, 'created_at' => now()->subDay(), 'updated_at' => now()->subDay(),
        ]);

        $issuing = $this->seedIssuing(2);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/butuh 2, tersedia 1/');

        app(InventoryIssuingService::class)->postReadyStockIfMissing($issuing);
    }

    public function test_unit_products_are_not_affected_by_the_batch_guard(): void
    {
        $this->actingAs(User::findOrFail(1));

        // Unit category: each physical unit gets its own distinct SN, already enforced
        // by requiredSerialCount(). The batch guard must not double-police these rows.
        DB::table('product_categories')->insert([
            'id' => 21, 'name' => 'Diffuser', 'has_serial_number' => true, 'is_unit' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('master_products')->insert([
            'id' => 25, 'product_category_id' => 21, 'name' => 'Diffuser W300 White',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('warehouse_products')->insert([
            'id' => 2, 'warehouse_id' => 2, 'master_product_id' => 25, 'quantity' => 5,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('serial_numbers')->insert([
            ['id' => 950, 'serial_number' => 'DW300W2606013', 'master_product_id' => 25, 'warehouse_id' => 2, 'status' => 'ready', 'location_type' => 'warehouse', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 951, 'serial_number' => 'DW300W2606014', 'master_product_id' => 25, 'warehouse_id' => 2, 'status' => 'ready', 'location_type' => 'warehouse', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('inventory_issuings')->insert([
            'id' => 155, 'issuing_number' => 'SBY-WI/26-08/0004', 'reference_no' => 'SBY-MI/26-08/0004',
            'warehouse_id' => 2, 'branch_id' => 1, 'received_by' => 1, 'issue_date' => now()->toDateString(),
            'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('inventory_issuing_items')->insert([
            'id' => 301, 'inventory_issuing_id' => 155, 'product_id' => 25, 'serial_number_id' => 950,
            'quantity_requested' => 2, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('inventory_issuing_item_serials')->insert([
            ['inventory_issuing_item_id' => 301, 'serial_number_id' => 950, 'unit_index' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['inventory_issuing_item_id' => 301, 'serial_number_id' => 951, 'unit_index' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        app(InventoryIssuingService::class)->postReadyStockIfMissing(InventoryIssuing::findOrFail(155));

        $this->assertSame(3.0, (float) DB::table('warehouse_products')->find(2)->quantity);
    }
}
