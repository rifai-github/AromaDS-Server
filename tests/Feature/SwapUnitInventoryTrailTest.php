<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobScheduleController;
use App\Models\InventoryIssuing;
use App\Models\InventoryIssuingItem;
use App\Models\JobAdvice;
use App\Models\JobSchedule;
use App\Models\SerialNumber;
use App\Models\Warehouse;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Regression test for a bug reported live 17 Aug 2026: the mobile "Ganti Unit" (swap serial
 * number) action flipped SerialNumber/UnitOnWall status directly with zero Inventory
 * Issuing/Receiving trail — the replacement unit appeared "installed" with no record of ever
 * leaving the warehouse, and admins could not backfill one manually afterwards because
 * InventoryIssuingController requires the serial to still be 'ready' to issue it.
 *
 * See JobScheduleController::queueSwappedUnitIssuing() (new) and its reuse of the existing
 * queueRemovedUnitReceiving() (previously only called from the web remove-job flow), both now
 * invoked from Api\Mobile\JobController::swapSerialNumber().
 */
class SwapUnitInventoryTrailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('job_advices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable();
            $table->string('type')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('job_advice_id')->nullable();
            $table->foreignId('building_id')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->foreignId('assigned_technician_id')->nullable();
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

        DB::table('branches')->insert([
            'id' => 1,
            'code' => 'SBY',
            'name' => 'Surabaya',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('warehouse_code')->nullable();
            $table->string('name')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_center')->default(false);
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
            $table->unsignedBigInteger('master_product_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('inventory_receiving_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_issuings', function (Blueprint $table) {
            $table->id();
            $table->string('issuing_number')->nullable();
            $table->foreignId('inventory_request_id')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->date('issue_date')->nullable();
            $table->string('reference_no')->nullable();
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->unsignedBigInteger('received_by')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->string('status')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
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
            $table->integer('quantity_requested')->nullable();
            $table->integer('quantity_issued')->nullable();
            $table->integer('quantity_received')->nullable();
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->decimal('total_price', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('inventory_issuing_items');
        Schema::dropIfExists('inventory_issuings');
        Schema::dropIfExists('serial_numbers');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('job_schedules');
        Schema::dropIfExists('job_advices');

        parent::tearDown();
    }

    public function test_queue_swapped_unit_issuing_creates_issued_inventory_record(): void
    {
        $jobAdvice = JobAdvice::create(['customer_id' => 10, 'type' => 'service']);

        $job = JobSchedule::create([
            'job_number' => 'SBY-CSR/26-08/0016',
            'type' => 'service',
            'status' => 'in_progress',
            'job_advice_id' => $jobAdvice->id,
            'branch_id' => 1,
        ]);

        $warehouse = Warehouse::create([
            'warehouse_code' => 'WH-SBY',
            'name' => 'Gudang Surabaya',
            'branch_id' => 1,
            'is_active' => true,
        ]);

        $newSerialNumber = SerialNumber::create([
            'serial_number' => 'ADSW10026080005',
            'status' => 'in_use',
            'master_product_id' => 55,
            'warehouse_id' => $warehouse->id,
        ]);

        $controller = new JobScheduleController;
        $method = new \ReflectionMethod($controller, 'queueSwappedUnitIssuing');
        $method->setAccessible(true);
        $method->invoke($controller, $job, $newSerialNumber);

        $issuing = InventoryIssuing::where('reference_no', $job->job_number)->first();

        $this->assertNotNull($issuing, 'Swap Unit must create an Inventory Issuing record for the new serial number.');
        $this->assertSame('sent', $issuing->status);
        $this->assertSame($warehouse->id, $issuing->warehouse_id);
        $this->assertNotNull($issuing->issued_at);
        $this->assertNotNull($issuing->received_at);

        $item = InventoryIssuingItem::where('inventory_issuing_id', $issuing->id)->first();
        $this->assertNotNull($item);
        $this->assertSame($newSerialNumber->id, $item->serial_number_id);
        $this->assertSame(55, $item->product_id);
        $this->assertSame(1, $item->quantity_issued);
    }

    public function test_queue_swapped_unit_issuing_is_idempotent_on_retry(): void
    {
        $jobAdvice = JobAdvice::create(['customer_id' => 10, 'type' => 'service']);

        $job = JobSchedule::create([
            'job_number' => 'SBY-CSR/26-08/0017',
            'type' => 'service',
            'status' => 'in_progress',
            'job_advice_id' => $jobAdvice->id,
            'branch_id' => 1,
        ]);

        $warehouse = Warehouse::create([
            'warehouse_code' => 'WH-SBY',
            'name' => 'Gudang Surabaya',
            'branch_id' => 1,
            'is_active' => true,
        ]);

        $newSerialNumber = SerialNumber::create([
            'serial_number' => 'ADSW10026080005',
            'status' => 'in_use',
            'master_product_id' => 55,
            'warehouse_id' => $warehouse->id,
        ]);

        $controller = new JobScheduleController;
        $method = new \ReflectionMethod($controller, 'queueSwappedUnitIssuing');
        $method->setAccessible(true);

        $method->invoke($controller, $job, $newSerialNumber);
        $method->invoke($controller, $job, $newSerialNumber);

        $this->assertSame(
            1,
            InventoryIssuing::where('reference_no', $job->job_number)->count(),
            'Re-running the swap-unit issuing helper for the same job/serial must not create duplicate records.'
        );
    }
}
