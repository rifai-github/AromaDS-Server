<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobScheduleController;
use App\Models\JobSchedule;
use App\Models\MaterialReturn;
use App\Models\SerialNumber;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA (06 Sep 2026, job SBY-CSR/26-10/0015): leaving a job unfinished auto-creates a
 * MaterialReturn plus a pending InventoryReceiving that already holds the returned
 * item and its Serial Number. Completing that same return then opened a SECOND
 * receiving and moved the SN over to it - the SN "disappeared" from the first
 * receiving (SBY-IRC/26-09/0004), and both pending receivings would each credit the
 * warehouse for the one unit that actually came back.
 *
 * completeMaterialReturn() must reuse the receiving that is already waiting for the
 * goods: no second document, the SN stays put, and no direct stock credit for a line
 * the warehouse will finalize later.
 */
class MaterialReturnReusesPendingReceivingTest extends TestCase
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

    private function seedJobWarehouseAndProduct(): JobSchedule
    {
        DB::table('job_schedules')->insert(['id' => 10, 'job_number' => 'SBY-CSR/26-10/0015', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('warehouses')->insert(['id' => 1, 'name' => 'Gudang Surabaya', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('job_assign_schedules')->insert(['id' => 1, 'job_schedule_id' => 10, 'created_at' => now(), 'updated_at' => now()]);

        // Refill carrying a (batch) Serial Number, like the Amberwood fragrance QA used.
        DB::table('product_categories')->insert(['id' => 1, 'name' => 'Fragrance', 'has_serial_number' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('master_products')->insert(['id' => 1, 'name' => 'Fragrance Amberwood Sport Mix 1 100 ml', 'product_category_id' => 1, 'created_at' => now(), 'updated_at' => now()]);

        DB::table('material_issue_items')->insert([
            'id' => 693, 'job_assign_schedule_id' => 1, 'product_id' => 1, 'room_name' => 'Ruang Ganti Rental 1 Room',
            'quantity' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('inventory_issuings')->insert(['id' => 1, 'status' => 'sent', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('inventory_issuing_items')->insert([
            'inventory_issuing_id' => 1, 'job_assign_schedule_id' => 1, 'room_name' => 'Ruang Ganti Rental 1 Room',
            'product_id' => 1, 'serial_number_id' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return JobSchedule::findOrFail(10);
    }

    /**
     * Recreate what JobWebCompletionService::processPartialCompletionMaterialReturnItems()
     * leaves behind when the technician leaves the job unfinished.
     */
    private function seedAutoReturnQueue(): MaterialReturn
    {
        DB::table('inventory_receivings')->insert([
            'id' => 83,
            'receiving_number' => 'SBY-IRC/26-09/0004',
            'reference_no' => 'SBY-CSR/26-10/0015',
            'branch_id' => 2,
            'schedule_date' => now()->toDateString(),
            'status' => 'pending',
            'notes' => 'Auto-return dari Job SBY-CSR/26-10/0015 (Pekerjaan tidak selesai). Room: Ruang Ganti Rental 1 Room',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('inventory_receiving_items')->insert([
            'inventory_receiving_id' => 83,
            'master_product_id' => 1,
            'quantity' => 1,
            'quantity_received' => 0,
            'notes' => 'Auto-return dari Room Ruang Ganti Rental 1 Room (MI Item 693)',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('serial_numbers')->insert([
            'id' => 1, 'serial_number' => 'AS1002606002', 'status' => 'pending', 'master_product_id' => 1,
            'warehouse_id' => 1, 'location_type' => 'technician', 'inventory_receiving_id' => 83,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $materialReturn = MaterialReturn::create([
            'return_number' => 'SBY-ADS-RTR/26-09/0001',
            'job_schedule_id' => 10,
            'warehouse_id' => 1,
            'status' => MaterialReturn::STATUS_APPROVED,
            'return_date' => now()->toDateString(),
        ]);

        DB::table('material_return_items')->insert([
            'material_return_id' => $materialReturn->id,
            'material_issue_item_id' => 693,
            'product_id' => 1,
            'room_name' => 'Ruang Ganti Rental 1 Room',
            'quantity' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $materialReturn;
    }

    private function complete(JobSchedule $job, MaterialReturn $materialReturn)
    {
        return (new JobScheduleController)->completeMaterialReturn(
            Request::create('/operational/job-schedules/10/material-returns/'.$materialReturn->id.'/complete', 'POST'),
            $job,
            $materialReturn->id
        );
    }

    public function test_completing_an_auto_return_reuses_the_pending_receiving_and_keeps_its_serial_number(): void
    {
        $job = $this->seedJobWarehouseAndProduct();
        $materialReturn = $this->seedAutoReturnQueue();

        $response = $this->complete($job, $materialReturn);
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode(), $payload['message'] ?? 'no message');

        // No second receiving document.
        $this->assertSame(1, DB::table('inventory_receivings')->count());
        $this->assertStringContainsString('SBY-IRC/26-09/0004', $payload['message']);

        // The SN stays on the receiving that is already waiting for it.
        $this->assertSame(83, SerialNumber::find(1)->inventory_receiving_id);

        // The line is not duplicated inside that receiving either.
        $this->assertSame(1, DB::table('inventory_receiving_items')->where('inventory_receiving_id', 83)->count());

        // And stock is left to the warehouse finalize - not credited here as well.
        $this->assertSame(0, DB::table('warehouse_products')->count());

        $this->assertSame(MaterialReturn::STATUS_RETURNED, $materialReturn->fresh()->status);
    }

    public function test_completing_the_same_return_twice_does_not_duplicate_the_queue(): void
    {
        $job = $this->seedJobWarehouseAndProduct();
        $materialReturn = $this->seedAutoReturnQueue();

        $this->complete($job, $materialReturn);

        // Re-approve and complete again (QA retry / double click).
        $materialReturn->update(['status' => MaterialReturn::STATUS_APPROVED]);
        $this->complete($job, $materialReturn);

        $this->assertSame(1, DB::table('inventory_receivings')->count());
        $this->assertSame(1, DB::table('inventory_receiving_items')->count());
        $this->assertSame(0, DB::table('warehouse_products')->count());
    }

    public function test_return_without_a_pending_receiving_still_opens_one_for_the_serial_number(): void
    {
        $job = $this->seedJobWarehouseAndProduct();

        DB::table('serial_numbers')->insert([
            'id' => 1, 'serial_number' => 'AS1002606002', 'status' => 'on_hand', 'master_product_id' => 1,
            'location_type' => 'technician', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $materialReturn = MaterialReturn::create([
            'return_number' => 'SBY-ADS-RTR/26-09/0002',
            'job_schedule_id' => 10,
            'warehouse_id' => 1,
            'status' => MaterialReturn::STATUS_APPROVED,
            'return_date' => now()->toDateString(),
        ]);
        DB::table('material_return_items')->insert([
            'material_return_id' => $materialReturn->id,
            'material_issue_item_id' => 693,
            'product_id' => 1,
            'room_name' => 'Ruang Ganti Rental 1 Room',
            'quantity' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->complete($job, $materialReturn);
        $this->assertSame(200, $response->getStatusCode());

        $sn = SerialNumber::find(1);
        $this->assertNotNull($sn->inventory_receiving_id);
        $this->assertSame('pending', $sn->status);
        $this->assertSame(1, DB::table('inventory_receivings')->count());
        $this->assertSame(0, DB::table('warehouse_products')->count());
    }

    public function test_completing_a_return_whose_receiving_was_already_finalized_does_not_credit_again(): void
    {
        $job = $this->seedJobWarehouseAndProduct();
        $materialReturn = $this->seedAutoReturnQueue();

        // Warehouse already received the goods back: stock credited, SN back to ready.
        DB::table('inventory_receivings')->where('id', 83)->update(['status' => 'received']);
        DB::table('inventory_receiving_items')->where('inventory_receiving_id', 83)->update(['quantity_received' => 1]);
        DB::table('serial_numbers')->where('id', 1)->update(['status' => 'ready', 'location_type' => 'warehouse']);
        DB::table('warehouse_products')->insert([
            'warehouse_id' => 1, 'master_product_id' => 1, 'quantity' => 54,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->complete($job, $materialReturn);
        $this->assertSame(200, $response->getStatusCode());

        // No extra receiving, no second credit, and the SN is not dragged back to pending.
        $this->assertSame(1, DB::table('inventory_receivings')->count());
        $this->assertSame(54.0, (float) DB::table('warehouse_products')->where('master_product_id', 1)->value('quantity'));

        $sn = SerialNumber::find(1);
        $this->assertSame('ready', $sn->status);
        $this->assertSame(83, $sn->inventory_receiving_id);
    }
}
