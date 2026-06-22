<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Mobile\JobController;
use App\Models\InventoryIssuing;
use App\Models\InventoryIssuingItem;
use App\Models\JobAdvice;
use App\Models\JobAssignMaterialIssue;
use App\Models\JobAssignSchedule;
use App\Models\JobSchedule;
use App\Models\MasterProduct;
use App\Models\MaterialIssue;
use App\Models\ProductCategory;
use App\Models\SerialNumber;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Covers the bug reported by the client: a room with multiple serialized
 * products (e.g. unit + refill) only required ONE serial number scan from
 * the technician before the whole room was marked "completed". The unit's
 * SN was never actually verified in the field, yet autoCreateUnitOnWall()
 * later treated it as installed based on the warehouse issuing record alone.
 *
 * getMissingUnitSerialNumbersForRoom() is the gate added to completeRoom()
 * to close that gap: it diffs is_unit products issued for the room against
 * what's actually been scanned into job_schedule_units.
 */
class CompleteRoomMissingUnitSerialNumberTest extends TestCase
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
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedule_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('job_advice_room_id')->nullable();
            $table->foreignId('unit_id')->nullable();
            $table->foreignId('unit_on_wall_id')->nullable();
            $table->string('mac')->nullable();
            $table->string('device_type')->nullable();
            $table->string('device_name')->nullable();
            $table->json('device_snapshot')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->string('photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number')->nullable();
            $table->string('status')->nullable();
            $table->string('location_type')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('master_product_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->boolean('is_unit')->default(false);
            $table->boolean('has_serial_number')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_category_id')->nullable();
            $table->foreignId('product_type_id')->nullable();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_issues', function (Blueprint $table) {
            $table->id();
            $table->string('issue_number')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_assign_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_assign_material_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_assign_schedule_id')->nullable();
            $table->foreignId('material_issue_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_issuings', function (Blueprint $table) {
            $table->id();
            $table->string('issuing_number')->nullable();
            $table->string('reference_no')->nullable();
            $table->string('status')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_issuing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_issuing_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('serial_number_id')->nullable();
            $table->string('room_name')->nullable();
            $table->integer('quantity_requested')->default(0);
            $table->integer('quantity_issued')->default(0);
            $table->integer('quantity_received')->default(0);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('inventory_issuing_items');
        Schema::dropIfExists('inventory_issuings');
        Schema::dropIfExists('job_assign_material_issues');
        Schema::dropIfExists('job_assign_schedules');
        Schema::dropIfExists('material_issues');
        Schema::dropIfExists('master_products');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('serial_numbers');
        Schema::dropIfExists('job_schedule_units');
        Schema::dropIfExists('job_schedules');
        Schema::dropIfExists('job_advices');

        parent::tearDown();
    }

    private function invokeGetMissingUnitSerialNumbersForRoom(JobSchedule $job, int $roomId, ?string $roomName): array
    {
        $controller = app(JobController::class);
        $method = new \ReflectionMethod($controller, 'getMissingUnitSerialNumbersForRoom');
        $method->setAccessible(true);

        return $method->invoke($controller, $job, $roomId, $roomName);
    }

    public function test_room_with_unit_and_refill_reports_missing_unit_when_only_refill_was_scanned(): void
    {
        $jobAdvice = JobAdvice::create(['customer_id' => 7, 'type' => 'install']);

        $job = JobSchedule::create([
            'job_number' => 'JKT-IF/26-06/0001',
            'type' => 'install_free',
            'status' => 'in_progress',
            'job_advice_id' => $jobAdvice->id,
        ]);

        $unitCategory = ProductCategory::create(['code' => 'UNIT', 'name' => 'Diffuser', 'is_unit' => true]);
        $refillCategory = ProductCategory::create(['code' => 'REF', 'name' => 'Refill', 'is_unit' => false]);

        $unitProduct = MasterProduct::create(['product_category_id' => $unitCategory->id, 'name' => 'Diffuser W300 Black']);
        $refillProduct = MasterProduct::create(['product_category_id' => $refillCategory->id, 'name' => 'Fragrance Amberwood Sport Mix 100 ml']);

        $unitSn = SerialNumber::create(['serial_number' => 'DW300B2606039', 'master_product_id' => $unitProduct->id, 'status' => 'pending']);
        $refillSn = SerialNumber::create(['serial_number' => 'AMBERW100001', 'master_product_id' => $refillProduct->id, 'status' => 'pending']);

        $materialIssue = MaterialIssue::create(['issue_number' => 'JKT-MI/26-06/0008', 'status' => 'issued']);

        $jobAssignSchedule = JobAssignSchedule::create(['job_schedule_id' => $job->id, 'status' => 'assigned']);
        JobAssignMaterialIssue::create([
            'job_assign_schedule_id' => $jobAssignSchedule->id,
            'material_issue_id' => $materialIssue->id,
        ]);

        $inventoryIssuing = InventoryIssuing::create([
            'issuing_number' => 'JKT-WI/26-06/0007',
            'reference_no' => $materialIssue->issue_number,
            'status' => 'sent',
            'warehouse_id' => 1,
        ]);

        InventoryIssuingItem::create([
            'inventory_issuing_id' => $inventoryIssuing->id,
            'product_id' => $unitProduct->id,
            'serial_number_id' => $unitSn->id,
            'room_name' => 'Studio 1',
        ]);
        InventoryIssuingItem::create([
            'inventory_issuing_id' => $inventoryIssuing->id,
            'product_id' => $refillProduct->id,
            'serial_number_id' => $refillSn->id,
            'room_name' => 'Studio 1',
        ]);

        // Technician only scanned the refill's SN, never the unit's.
        \DB::table('job_schedule_units')->insert([
            'job_schedule_id' => $job->id,
            'job_advice_room_id' => 410,
            'mac' => 'AMBERW100001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $missing = $this->invokeGetMissingUnitSerialNumbersForRoom($job, 410, 'Studio 1');

        $this->assertArrayHasKey('DW300B2606039', $missing);
        $this->assertArrayNotHasKey('AMBERW100001', $missing);
    }

    public function test_room_reports_no_missing_unit_once_unit_serial_is_scanned(): void
    {
        $jobAdvice = JobAdvice::create(['customer_id' => 7, 'type' => 'install']);

        $job = JobSchedule::create([
            'job_number' => 'JKT-IF/26-06/0001',
            'type' => 'install_free',
            'status' => 'in_progress',
            'job_advice_id' => $jobAdvice->id,
        ]);

        $unitCategory = ProductCategory::create(['code' => 'UNIT', 'name' => 'Diffuser', 'is_unit' => true]);
        $unitProduct = MasterProduct::create(['product_category_id' => $unitCategory->id, 'name' => 'Diffuser W300 Black']);
        $unitSn = SerialNumber::create(['serial_number' => 'DW300B2606039', 'master_product_id' => $unitProduct->id, 'status' => 'pending']);

        $materialIssue = MaterialIssue::create(['issue_number' => 'JKT-MI/26-06/0008', 'status' => 'issued']);
        $jobAssignSchedule = JobAssignSchedule::create(['job_schedule_id' => $job->id, 'status' => 'assigned']);
        JobAssignMaterialIssue::create([
            'job_assign_schedule_id' => $jobAssignSchedule->id,
            'material_issue_id' => $materialIssue->id,
        ]);

        $inventoryIssuing = InventoryIssuing::create([
            'issuing_number' => 'JKT-WI/26-06/0007',
            'reference_no' => $materialIssue->issue_number,
            'status' => 'sent',
            'warehouse_id' => 1,
        ]);

        InventoryIssuingItem::create([
            'inventory_issuing_id' => $inventoryIssuing->id,
            'product_id' => $unitProduct->id,
            'serial_number_id' => $unitSn->id,
            'room_name' => 'Studio 1',
        ]);

        \DB::table('job_schedule_units')->insert([
            'job_schedule_id' => $job->id,
            'job_advice_room_id' => 410,
            'mac' => 'DW300B2606039',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $missing = $this->invokeGetMissingUnitSerialNumbersForRoom($job, 410, 'Studio 1');

        $this->assertSame([], $missing);
    }
}
