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

        Schema::create('inventory_issuing_item_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_issuing_item_id');
            $table->foreignId('serial_number_id');
            $table->integer('unit_index')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('product_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->boolean('is_unit')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_advice_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_advice_id')->nullable();
            $table->unsignedBigInteger('rental_product_id')->nullable();
            $table->string('room_name')->nullable();
            $table->integer('quantity')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('rental_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('master_rental_id')->nullable();
            $table->string('item_type')->nullable();
            $table->unsignedBigInteger('item_id')->nullable();
            $table->unsignedBigInteger('product_type_id')->nullable();
            $table->unsignedBigInteger('product_category_id')->nullable();
            $table->unsignedBigInteger('master_product_id')->nullable();
            $table->integer('service_frequency_multiplier')->nullable();
            $table->integer('quantity')->nullable();
            $table->integer('bom_rental_qty')->nullable();
            $table->boolean('auto_expand')->default(false);
            $table->string('unit')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('rental_details');
        Schema::dropIfExists('job_advice_rooms');
        Schema::dropIfExists('product_types');
        Schema::dropIfExists('inventory_issuing_item_serials');
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

    public function test_qty_two_unit_item_requires_both_serial_links_before_room_can_complete(): void
    {
        $jobAdvice = JobAdvice::create(['customer_id' => 7, 'type' => 'install']);
        $job = JobSchedule::create([
            'job_number' => 'SBY-IR/26-07/0063',
            'type' => 'install',
            'status' => 'in_progress',
            'job_advice_id' => $jobAdvice->id,
        ]);

        $unitCategory = ProductCategory::create(['code' => 'UNIT', 'name' => 'Diffuser', 'is_unit' => true]);
        $unitProduct = MasterProduct::create(['product_category_id' => $unitCategory->id, 'name' => 'Diffuser 303']);
        $rentalId = 6301;

        \DB::table('rental_details')->insert([
            'master_rental_id' => $rentalId,
            'item_type' => 'product',
            'master_product_id' => $unitProduct->id,
            'product_category_id' => $unitCategory->id,
            'bom_rental_qty' => 1,
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $room = \App\Models\JobAdviceRoom::create([
            'job_advice_id' => $jobAdvice->id,
            'rental_product_id' => $rentalId,
            'room_name' => 'Ruang 1 Room 1 Rental QTY 2',
            'quantity' => 2,
        ]);

        $firstSn = SerialNumber::create(['serial_number' => 'DIFF3030021', 'master_product_id' => $unitProduct->id, 'status' => 'pending']);
        $secondSn = SerialNumber::create(['serial_number' => 'DIFF3030022', 'master_product_id' => $unitProduct->id, 'status' => 'pending']);
        $materialIssue = MaterialIssue::create(['issue_number' => 'SBY-MI/26-07/0063', 'status' => 'issued']);
        $jobAssignSchedule = JobAssignSchedule::create(['job_schedule_id' => $job->id, 'status' => 'assigned']);
        JobAssignMaterialIssue::create([
            'job_assign_schedule_id' => $jobAssignSchedule->id,
            'material_issue_id' => $materialIssue->id,
        ]);

        $inventoryIssuing = InventoryIssuing::create([
            'issuing_number' => 'SBY-WI/26-07/0063',
            'reference_no' => $materialIssue->issue_number,
            'status' => 'sent',
            'warehouse_id' => 1,
        ]);

        $item = InventoryIssuingItem::create([
            'inventory_issuing_id' => $inventoryIssuing->id,
            'product_id' => $unitProduct->id,
            'serial_number_id' => $firstSn->id,
            'room_name' => $room->room_name,
            'quantity_requested' => 2,
            'quantity_issued' => 2,
        ]);

        \DB::table('inventory_issuing_item_serials')->insert([
            [
                'inventory_issuing_item_id' => $item->id,
                'serial_number_id' => $firstSn->id,
                'unit_index' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'inventory_issuing_item_id' => $item->id,
                'serial_number_id' => $secondSn->id,
                'unit_index' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        \DB::table('job_schedule_units')->insert([
            'job_schedule_id' => $job->id,
            'job_advice_room_id' => $room->id,
            'mac' => $firstSn->serial_number,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $missingAfterOneScan = $this->invokeGetMissingUnitSerialNumbersForRoom($job, $room->id, $room->room_name);
        $this->assertArrayHasKey('Diffuser (x1)', $missingAfterOneScan);

        \DB::table('job_schedule_units')->insert([
            'job_schedule_id' => $job->id,
            'job_advice_room_id' => $room->id,
            'mac' => $secondSn->serial_number,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame([], $this->invokeGetMissingUnitSerialNumbersForRoom($job, $room->id, $room->room_name));
    }

    /**
     * Bug #25 (live QA case: job_schedule_room 154/155, "Ruang Ballroom" with 3
     * rental links): a single physical room hosts 2 DIFFERENT rentals, each with
     * its own Diffuser unit but the SAME room_name. The old exact-serial-match
     * query pulled in BOTH rentals' units and demanded both serials be scanned
     * under whichever job_advice_room_id was being completed — so finishing the
     * "unit komplit" rental failed because the OTHER rental's diffuser serial
     * (tracked under a different job_advice_room_id) could never match. Scoping
     * by per-rental BOM quantity instead of exact serial fixes this: completing
     * rental A only requires as many scanned units as rental A's own BOM calls
     * for, ignoring rental B's separate unit entirely.
     */
    public function test_room_with_two_different_rentals_does_not_demand_the_other_rentals_unit_serial(): void
    {
        $jobAdvice = JobAdvice::create(['customer_id' => 7, 'type' => 'install']);

        $job = JobSchedule::create([
            'job_number' => 'SBY-IR/26-06/0099',
            'type' => 'install',
            'status' => 'in_progress',
            'job_advice_id' => $jobAdvice->id,
        ]);

        $unitCategory = ProductCategory::create(['code' => 'UNIT', 'name' => 'Diffuser', 'is_unit' => true]);
        $unitProduct = MasterProduct::create(['product_category_id' => $unitCategory->id, 'name' => 'Diffuser W300 Black']);

        $masterRentalA = 501; // rental A's MasterRental id (BOM: 1x Diffuser)
        $masterRentalB = 502; // rental B's MasterRental id (separate BOM: 1x Diffuser)

        \DB::table('rental_details')->insert([
            'master_rental_id' => $masterRentalA,
            'item_type' => 'product',
            'master_product_id' => $unitProduct->id,
            'product_category_id' => $unitCategory->id,
            'bom_rental_qty' => 1,
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \DB::table('rental_details')->insert([
            'master_rental_id' => $masterRentalB,
            'item_type' => 'product',
            'master_product_id' => $unitProduct->id,
            'product_category_id' => $unitCategory->id,
            'bom_rental_qty' => 1,
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Rental A and Rental B are two DIFFERENT JobAdviceRoom rows for the
        // SAME physical room ("Ruang Ballroom"), each with its own unit.
        $roomA = \App\Models\JobAdviceRoom::create([
            'job_advice_id' => $jobAdvice->id,
            'rental_product_id' => $masterRentalA,
            'room_name' => 'Ruang Ballroom',
            'quantity' => 1,
        ]);
        $roomB = \App\Models\JobAdviceRoom::create([
            'job_advice_id' => $jobAdvice->id,
            'rental_product_id' => $masterRentalB,
            'room_name' => 'Ruang Ballroom',
            'quantity' => 1,
        ]);

        $unitSnA = SerialNumber::create(['serial_number' => 'DW300B260001', 'master_product_id' => $unitProduct->id, 'status' => 'pending']);
        $unitSnB = SerialNumber::create(['serial_number' => 'DW300B260002', 'master_product_id' => $unitProduct->id, 'status' => 'pending']);

        $materialIssue = MaterialIssue::create(['issue_number' => 'SBY-MI/26-06/0099', 'status' => 'issued']);
        $jobAssignSchedule = JobAssignSchedule::create(['job_schedule_id' => $job->id, 'status' => 'assigned']);
        JobAssignMaterialIssue::create([
            'job_assign_schedule_id' => $jobAssignSchedule->id,
            'material_issue_id' => $materialIssue->id,
        ]);

        $inventoryIssuing = InventoryIssuing::create([
            'issuing_number' => 'SBY-WI/26-06/0099',
            'reference_no' => $materialIssue->issue_number,
            'status' => 'sent',
            'warehouse_id' => 1,
        ]);

        // Both rentals' units were issued under the SAME room_name.
        InventoryIssuingItem::create([
            'inventory_issuing_id' => $inventoryIssuing->id,
            'product_id' => $unitProduct->id,
            'serial_number_id' => $unitSnA->id,
            'room_name' => 'Ruang Ballroom',
        ]);
        InventoryIssuingItem::create([
            'inventory_issuing_id' => $inventoryIssuing->id,
            'product_id' => $unitProduct->id,
            'serial_number_id' => $unitSnB->id,
            'room_name' => 'Ruang Ballroom',
        ]);

        // Technician only scanned Rental A's own unit serial, under Rental A's
        // job_advice_room_id. Rental B's unit (under a different room id) has
        // not been touched at all — and shouldn't need to be, to finish A.
        \DB::table('job_schedule_units')->insert([
            'job_schedule_id' => $job->id,
            'job_advice_room_id' => $roomA->id,
            'mac' => 'DW300B260001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $missing = $this->invokeGetMissingUnitSerialNumbersForRoom($job, $roomA->id, 'Ruang Ballroom');

        $this->assertSame(
            [],
            $missing,
            'Completing Rental A must not be blocked by Rental B\'s separate, not-yet-scanned unit.'
        );
    }

    /**
     * Bug #25 (round 2, live QA case: job 187 "SBY-IR/26-06/0014",
     * job_advice_room_id 49, "Toilet Umum"): the rental's BOM calls for an
     * exact master_product_id ("Diffuser W300 Black"), but the technician
     * scanned a different product in the same unit category ("Diffuser W300
     * White") — a legitimate variant swap, same flow the system already
     * allows elsewhere (Aroma Switching). The old exact-product_id match
     * never recognized the swapped variant as fulfilling the requirement, so
     * the "unit komplit" rental could never be completed even though an
     * equivalent unit had been scanned. Matching by product_category_id
     * instead of master_product_id fixes this.
     */
    public function test_room_completion_accepts_a_different_product_in_the_same_unit_category(): void
    {
        $jobAdvice = JobAdvice::create(['customer_id' => 7, 'type' => 'install']);

        $job = JobSchedule::create([
            'job_number' => 'SBY-IR/26-06/0014',
            'type' => 'install',
            'status' => 'in_progress',
            'job_advice_id' => $jobAdvice->id,
        ]);

        $unitCategory = ProductCategory::create(['code' => 'UNIT', 'name' => 'Diffuser', 'is_unit' => true]);
        $bomProduct = MasterProduct::create(['product_category_id' => $unitCategory->id, 'name' => 'Diffuser W300 Black']);
        $scannedVariant = MasterProduct::create(['product_category_id' => $unitCategory->id, 'name' => 'Diffuser W300 White']);

        $masterRental = 503; // BOM: 1x Diffuser (specific variant: Black)
        \DB::table('rental_details')->insert([
            'master_rental_id' => $masterRental,
            'item_type' => 'product',
            'master_product_id' => $bomProduct->id,
            'product_category_id' => $unitCategory->id,
            'bom_rental_qty' => 1,
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $room = \App\Models\JobAdviceRoom::create([
            'job_advice_id' => $jobAdvice->id,
            'rental_product_id' => $masterRental,
            'room_name' => 'Toilet Umum',
            'quantity' => 1,
        ]);

        $scannedSn = SerialNumber::create(['serial_number' => 'DW300W2606003', 'master_product_id' => $scannedVariant->id, 'status' => 'pending']);

        $materialIssue = MaterialIssue::create(['issue_number' => 'SBY-MI/26-06/0014', 'status' => 'issued']);
        $jobAssignSchedule = JobAssignSchedule::create(['job_schedule_id' => $job->id, 'status' => 'assigned']);
        JobAssignMaterialIssue::create([
            'job_assign_schedule_id' => $jobAssignSchedule->id,
            'material_issue_id' => $materialIssue->id,
        ]);

        $inventoryIssuing = InventoryIssuing::create([
            'issuing_number' => 'SBY-WI/26-06/0014',
            'reference_no' => $materialIssue->issue_number,
            'status' => 'sent',
            'warehouse_id' => 1,
        ]);

        // Inventory was issued for the BOM's exact variant (Black)...
        InventoryIssuingItem::create([
            'inventory_issuing_id' => $inventoryIssuing->id,
            'product_id' => $bomProduct->id,
            'serial_number_id' => SerialNumber::create(['serial_number' => 'DW300B2606999', 'master_product_id' => $bomProduct->id, 'status' => 'pending'])->id,
            'room_name' => 'Toilet Umum',
        ]);

        // ...but the technician actually scanned a different variant (White) in the field.
        \DB::table('job_schedule_units')->insert([
            'job_schedule_id' => $job->id,
            'job_advice_room_id' => $room->id,
            'mac' => 'DW300W2606003',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $missing = $this->invokeGetMissingUnitSerialNumbersForRoom($job, $room->id, 'Toilet Umum');

        $this->assertSame(
            [],
            $missing,
            'Scanning a different product in the same unit category should fulfill the BOM requirement.'
        );
    }

    /**
     * Bug #72 (QA): the previous validator joined job_schedule_units.mac to
     * serial_numbers.serial_number with an INNER JOIN, silently dropping any
     * scanned MAC that didn't already exist in the serial_numbers master.
     * That made it impossible to complete a room when the technician scanned
     * a brand-new SN not yet registered by the warehouse — the scan vanished
     * from the count, the category stayed reported as "missing", and the
     * technician saw the same "still has unscanned unit" message no matter
     * how many times they re-scanned. Scans not present in serial_numbers
     * should still count toward unmet required categories so the technician
     * is unblocked.
     */
    public function test_room_completion_accepts_scanned_sn_not_yet_in_serial_numbers_master(): void
    {
        $jobAdvice = JobAdvice::create(['customer_id' => 7, 'type' => 'install']);

        $job = JobSchedule::create([
            'job_number' => 'JKT-IR/26-06/0099',
            'type' => 'install',
            'status' => 'in_progress',
            'job_advice_id' => $jobAdvice->id,
        ]);

        $unitCategory = ProductCategory::create(['code' => 'UNIT', 'name' => 'Diffuser', 'is_unit' => true]);
        $unitProduct = MasterProduct::create(['product_category_id' => $unitCategory->id, 'name' => 'Diffuser W300 Black']);

        $masterRental = 510; // BOM: 1x Diffuser
        \DB::table('rental_details')->insert([
            'master_rental_id' => $masterRental,
            'item_type' => 'product',
            'master_product_id' => $unitProduct->id,
            'product_category_id' => $unitCategory->id,
            'bom_rental_qty' => 1,
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $room = \App\Models\JobAdviceRoom::create([
            'job_advice_id' => $jobAdvice->id,
            'rental_product_id' => $masterRental,
            'room_name' => 'Lobby',
            'quantity' => 1,
        ]);

        // Warehouse issued a registered SN, but the technician (per the QA
        // bug report) scans a different, NOT-YET-REGISTERED SN in the field.
        $issuedSn = SerialNumber::create(['serial_number' => 'DW300B2606REG', 'master_product_id' => $unitProduct->id, 'status' => 'pending']);

        $materialIssue = MaterialIssue::create(['issue_number' => 'JKT-MI/26-06/0099', 'status' => 'issued']);
        $jobAssignSchedule = JobAssignSchedule::create(['job_schedule_id' => $job->id, 'status' => 'assigned']);
        JobAssignMaterialIssue::create([
            'job_assign_schedule_id' => $jobAssignSchedule->id,
            'material_issue_id' => $materialIssue->id,
        ]);

        $inventoryIssuing = InventoryIssuing::create([
            'issuing_number' => 'JKT-WI/26-06/0099',
            'reference_no' => $materialIssue->issue_number,
            'status' => 'sent',
            'warehouse_id' => 1,
        ]);

        InventoryIssuingItem::create([
            'inventory_issuing_id' => $inventoryIssuing->id,
            'product_id' => $unitProduct->id,
            'serial_number_id' => $issuedSn->id,
            'room_name' => 'Lobby',
        ]);

        // Technician scans a MAC that has NO row in serial_numbers (typed
        // manually or a brand-new physical unit not yet registered).
        \DB::table('job_schedule_units')->insert([
            'job_schedule_id' => $job->id,
            'job_advice_room_id' => $room->id,
            'mac' => 'DHS2605001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $missing = $this->invokeGetMissingUnitSerialNumbersForRoom($job, $room->id, 'Lobby');

        $this->assertSame(
            [],
            $missing,
            'A scanned SN not yet present in the serial_numbers master should still count toward the required category and unblock completion.'
        );
    }

    /**
     * QA bug (kode IF, job 417 "SBY-IF/26-07/0011", job_advice_room 106 "Ruang
     * 1R1RQ2"): a unit_refill room with quantity=2 whose BOM implies 2 Diffusers
     * (bom_rental_qty 1 × room quantity 2), but the warehouse only issued ONE
     * serialized Diffuser. The technician scanned that single Diffuser, yet the
     * validator kept demanding a second unit scan ("Diffuser (x1)") that no
     * physical unit existed for — permanently blocking room completion. The
     * required count must be capped at the number of units actually issued.
     */
    public function test_required_unit_count_is_capped_at_the_number_of_units_actually_issued(): void
    {
        $jobAdvice = JobAdvice::create(['customer_id' => 7, 'type' => 'install']);

        $job = JobSchedule::create([
            'job_number' => 'SBY-IF/26-07/0011',
            'type' => 'install_free',
            'status' => 'in_progress',
            'job_advice_id' => $jobAdvice->id,
        ]);

        $unitCategory = ProductCategory::create(['code' => 'UNIT', 'name' => 'Diffuser', 'is_unit' => true]);
        $unitProduct = MasterProduct::create(['product_category_id' => $unitCategory->id, 'name' => 'Diffuser 303']);

        $masterRental = 520; // BOM: 1x Diffuser per room-unit
        \DB::table('rental_details')->insert([
            'master_rental_id' => $masterRental,
            'item_type' => 'product',
            'master_product_id' => $unitProduct->id,
            'product_category_id' => $unitCategory->id,
            'bom_rental_qty' => 1,
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Room quantity 2 → BOM-derived requirement is 2 Diffusers...
        $room = \App\Models\JobAdviceRoom::create([
            'job_advice_id' => $jobAdvice->id,
            'rental_product_id' => $masterRental,
            'room_name' => 'Ruang 1R1RQ2',
            'quantity' => 2,
        ]);

        // ...but the warehouse only issued ONE serialized Diffuser.
        $issuedSn = SerialNumber::create(['serial_number' => 'DIFF3030017', 'master_product_id' => $unitProduct->id, 'status' => 'pending']);

        $materialIssue = MaterialIssue::create(['issue_number' => 'SBY-MI/26-07/0053', 'status' => 'issued']);
        $jobAssignSchedule = JobAssignSchedule::create(['job_schedule_id' => $job->id, 'status' => 'assigned']);
        JobAssignMaterialIssue::create([
            'job_assign_schedule_id' => $jobAssignSchedule->id,
            'material_issue_id' => $materialIssue->id,
        ]);

        $inventoryIssuing = InventoryIssuing::create([
            'issuing_number' => 'SBY-WI/26-07/0053',
            'reference_no' => $materialIssue->issue_number,
            'status' => 'sent',
            'warehouse_id' => 1,
        ]);

        InventoryIssuingItem::create([
            'inventory_issuing_id' => $inventoryIssuing->id,
            'product_id' => $unitProduct->id,
            'serial_number_id' => $issuedSn->id,
            'room_name' => 'Ruang 1R1RQ2',
        ]);

        // Technician scanned the one and only Diffuser that exists.
        \DB::table('job_schedule_units')->insert([
            'job_schedule_id' => $job->id,
            'job_advice_room_id' => $room->id,
            'mac' => 'DIFF3030017',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $missing = $this->invokeGetMissingUnitSerialNumbersForRoom($job, $room->id, 'Ruang 1R1RQ2');

        $this->assertSame(
            [],
            $missing,
            'Requirement must be capped at the number of units issued; scanning the only issued Diffuser must complete the room.'
        );
    }

    /**
     * Bug #72 regression guard: validator must still block completion when the
     * technician has scanned nothing at all for the room's required units.
     * The unknown-scan tolerance added in the fix must not turn the validator
     * into a no-op.
     */
    public function test_room_with_unit_required_but_zero_scans_still_blocks_completion(): void
    {
        $jobAdvice = JobAdvice::create(['customer_id' => 7, 'type' => 'install']);

        $job = JobSchedule::create([
            'job_number' => 'JKT-IR/26-06/0100',
            'type' => 'install',
            'status' => 'in_progress',
            'job_advice_id' => $jobAdvice->id,
        ]);

        $unitCategory = ProductCategory::create(['code' => 'UNIT', 'name' => 'Diffuser', 'is_unit' => true]);
        $unitProduct = MasterProduct::create(['product_category_id' => $unitCategory->id, 'name' => 'Diffuser W300 Black']);

        $masterRental = 511;
        \DB::table('rental_details')->insert([
            'master_rental_id' => $masterRental,
            'item_type' => 'product',
            'master_product_id' => $unitProduct->id,
            'product_category_id' => $unitCategory->id,
            'bom_rental_qty' => 1,
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $room = \App\Models\JobAdviceRoom::create([
            'job_advice_id' => $jobAdvice->id,
            'rental_product_id' => $masterRental,
            'room_name' => 'Lobby',
            'quantity' => 1,
        ]);

        $issuedSn = SerialNumber::create(['serial_number' => 'DW300B2606AAA', 'master_product_id' => $unitProduct->id, 'status' => 'pending']);

        $materialIssue = MaterialIssue::create(['issue_number' => 'JKT-MI/26-06/0100', 'status' => 'issued']);
        $jobAssignSchedule = JobAssignSchedule::create(['job_schedule_id' => $job->id, 'status' => 'assigned']);
        JobAssignMaterialIssue::create([
            'job_assign_schedule_id' => $jobAssignSchedule->id,
            'material_issue_id' => $materialIssue->id,
        ]);

        $inventoryIssuing = InventoryIssuing::create([
            'issuing_number' => 'JKT-WI/26-06/0100',
            'reference_no' => $materialIssue->issue_number,
            'status' => 'sent',
            'warehouse_id' => 1,
        ]);

        InventoryIssuingItem::create([
            'inventory_issuing_id' => $inventoryIssuing->id,
            'product_id' => $unitProduct->id,
            'serial_number_id' => $issuedSn->id,
            'room_name' => 'Lobby',
        ]);

        // No scans at all.
        $missing = $this->invokeGetMissingUnitSerialNumbersForRoom($job, $room->id, 'Lobby');

        $this->assertNotEmpty(
            $missing,
            'Validator must still block completion when no unit SN has been scanned.'
        );
    }
}
