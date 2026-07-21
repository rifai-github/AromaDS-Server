<?php

namespace Tests\Feature;

use App\Models\InventoryIssuing;
use App\Models\User;
use App\Services\Warehouse\InventoryIssuingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RefillBatchSerialOffsetTest extends TestCase
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

        Schema::create('unit_on_walls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('serial_number_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('users')->insert(['id' => 1, 'name' => 'Tester', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('warehouses')->insert(['id' => 1, 'name' => 'Gudang Surabaya', 'created_at' => now(), 'updated_at' => now()]);
    }

    protected function tearDown(): void
    {
        foreach ([
            'unit_on_walls', 'serial_numbers', 'inventory_issuing_item_serials',
            'inventory_issuing_items', 'inventory_issuings', 'warehouses',
            'master_products', 'product_types', 'product_categories', 'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }
        parent::tearDown();
    }

    /**
     * Reproduces the Warehouse stock-detail screenshot: 3 physical rows share the
     * batch code LG1002606001 (2 ready, 1 retired). A PRIOR issuing already
     * consumed 1 unit of that batch (issuing status stays 'sent' forever -- there
     * is no "returned" bookkeeping that decrements the offset). Does the batch
     * offset then still correctly land on the 2 remaining ready rows for a NEW
     * qty=2 issuing, or does it overshoot the pool and silently update nothing?
     */
    public function test_batch_offset_after_prior_partial_consumption_of_same_batch_code(): void
    {
        $this->actingAs(User::findOrFail(1));

        DB::table('product_categories')->insert([
            'id' => 20, 'name' => 'Refill', 'has_serial_number' => true, 'is_unit' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('master_products')->insert([
            'id' => 200, 'product_category_id' => 20, 'name' => 'Fragrance Lemongrass Mix 100 ml',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // 3 physical backing rows for the batch code, matching the stock-detail screenshot.
        DB::table('serial_numbers')->insert([
            ['id' => 900, 'serial_number' => 'LG1002606001', 'master_product_id' => 200, 'warehouse_id' => 1, 'status' => 'retired', 'location_type' => 'warehouse', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 901, 'serial_number' => 'LG1002606001', 'master_product_id' => 200, 'warehouse_id' => 1, 'status' => 'ready', 'location_type' => 'warehouse', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 902, 'serial_number' => 'LG1002606001', 'master_product_id' => 200, 'warehouse_id' => 1, 'status' => 'ready', 'location_type' => 'warehouse', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Prior issuing (old job, long since picked up / "sent") that consumed qty=1
        // of this same batch code. Its InventoryIssuing.status stays 'sent' forever.
        DB::table('inventory_issuings')->insert([
            'id' => 1, 'issuing_number' => 'SBY-WI/26-06/0010', 'reference_no' => 'SBY-MI/26-06/0010',
            'warehouse_id' => 1, 'received_by' => 1, 'status' => 'sent', 'created_at' => now()->subMonth(), 'updated_at' => now()->subMonth(),
        ]);
        DB::table('inventory_issuing_items')->insert([
            'id' => 50, 'inventory_issuing_id' => 1, 'job_assign_schedule_id' => 10, 'product_id' => 200,
            'serial_number_id' => 900, 'quantity_requested' => 1, 'quantity_issued' => 1, 'quantity_received' => 1,
            'created_at' => now()->subMonth(), 'updated_at' => now()->subMonth(),
        ]);

        // THIS job's issuing: qty=2, should land on rows 901 + 902 (the 2 ready ones).
        DB::table('inventory_issuings')->insert([
            'id' => 2, 'issuing_number' => 'SBY-WI/26-07/0069', 'reference_no' => 'SBY-MI/26-07/0083',
            'warehouse_id' => 1, 'received_by' => 1, 'status' => 'sent', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('inventory_issuing_items')->insert([
            'id' => 100, 'inventory_issuing_id' => 2, 'job_assign_schedule_id' => 55, 'product_id' => 200,
            'serial_number_id' => 901, 'quantity_requested' => 2, 'quantity_issued' => 2, 'quantity_received' => 2,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $issuing = InventoryIssuing::find(2);

        $updatedCount = app(InventoryIssuingService::class)->moveSerialNumbersToTechnician($issuing, 5, 1);

        $sn901 = DB::table('serial_numbers')->find(901);
        $sn902 = DB::table('serial_numbers')->find(902);

        $this->assertSame(2, $updatedCount, 'Expected both ready rows of this batch code to be updated for the new job');
        $this->assertSame('on_hand', $sn901->status);
        $this->assertSame('on_hand', $sn902->status);
    }
}
