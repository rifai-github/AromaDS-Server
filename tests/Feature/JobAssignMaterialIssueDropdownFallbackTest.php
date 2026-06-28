<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobAssignMaterialIssueController;
use App\Models\JobAdvice;
use App\Models\JobAdviceRoom;
use App\Models\JobAssignSchedule;
use App\Models\JobSchedule;
use App\Models\MasterProduct;
use App\Models\ProductCategory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Bug #75 (QA): when admins create a rental — especially unit-only rentals
 * that historically skipped Material Assign — the rental_detail_materials
 * pivot rows often never get is_selected=true set. The old dropdown loader
 * filtered strictly on wherePivot('is_selected', true), producing an empty
 * product dropdown so the technician/operator could not pick anything.
 *
 * Fix: fall back to the full allowed-products list when the curated
 * is_selected list is empty, mirroring the PRIORITY-3 fallback already in
 * JobAssignScheduleController.
 */
class JobAssignMaterialIssueDropdownFallbackTest extends TestCase
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

        Schema::create('job_advice_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_advice_id')->nullable();
            $table->unsignedBigInteger('rental_product_id')->nullable();
            $table->unsignedBigInteger('quotation_room_id')->nullable();
            $table->string('room_name')->nullable();
            $table->integer('quantity')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_assign_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->date('assigned_date')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('team_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->boolean('is_unit')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->boolean('is_unit')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->nullable();
            $table->foreignId('product_category_id')->nullable();
            $table->foreignId('product_type_id')->nullable();
            $table->string('name')->nullable();
            $table->string('variant_name')->nullable();
            $table->decimal('last_unit_price', 12, 2)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_rentals', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('flow_type')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('rental_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('master_rental_id')->nullable();
            $table->string('item_type')->nullable();
            $table->unsignedBigInteger('product_type_id')->nullable();
            $table->unsignedBigInteger('product_category_id')->nullable();
            $table->unsignedBigInteger('master_product_id')->nullable();
            $table->integer('quantity')->nullable();
            $table->integer('bom_rental_qty')->nullable();
            $table->boolean('auto_expand')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('rental_detail_materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rental_detail_id');
            $table->unsignedBigInteger('master_product_id');
            $table->boolean('is_selected')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('quotation_rooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quotation_id')->nullable();
            $table->unsignedBigInteger('aroma_product_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Empty job_schedule_rooms so getAssignedJobAdviceRooms() falls back
        // to all of $jobAdvice->rooms (matches the field reality for a
        // freshly assigned schedule with no per-room split).
        Schema::create('job_schedule_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('job_advice_room_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedule_room_rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_room_id')->nullable();
            $table->foreignId('job_advice_room_id')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('job_schedule_room_rentals');
        Schema::dropIfExists('job_schedule_rooms');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('quotation_rooms');
        Schema::dropIfExists('rental_detail_materials');
        Schema::dropIfExists('rental_details');
        Schema::dropIfExists('master_rentals');
        Schema::dropIfExists('master_products');
        Schema::dropIfExists('product_types');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('job_assign_schedules');
        Schema::dropIfExists('job_advice_rooms');
        Schema::dropIfExists('job_schedules');
        Schema::dropIfExists('job_advices');

        parent::tearDown();
    }

    private function seedRentalWithUnselectedMaterials(): array
    {
        $unitCategory = ProductCategory::create(['code' => 'UNIT', 'name' => 'Diffuser', 'is_unit' => true]);
        $unitProduct = MasterProduct::create([
            'product_category_id' => $unitCategory->id,
            'name'                => 'Diffuser W300 Black',
            'sku'                 => 'DW300B',
            'last_unit_price'     => 1200000,
        ]);

        // Rental with one detail; pivot row exists but is_selected=false (the
        // legacy unit-only situation).
        $rentalId = \DB::table('master_rentals')->insertGetId([
            'name'       => 'Rental Unit Only',
            'flow_type'  => 'unit_only',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $detailId = \DB::table('rental_details')->insertGetId([
            'master_rental_id'    => $rentalId,
            'item_type'           => 'category',
            'product_category_id' => $unitCategory->id,
            'master_product_id'   => null,
            'bom_rental_qty'      => 1,
            'quantity'            => 1,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        \DB::table('rental_detail_materials')->insert([
            'rental_detail_id'  => $detailId,
            'master_product_id' => $unitProduct->id,
            'is_selected'       => false,
            'sort_order'        => 0,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $jobAdvice = JobAdvice::create(['customer_id' => 1, 'type' => 'install']);
        $job = JobSchedule::create([
            'job_number'    => 'JKT-IR/26-06/0500',
            'type'          => 'install',
            'status'        => 'in_progress',
            'job_advice_id' => $jobAdvice->id,
        ]);

        JobAdviceRoom::create([
            'job_advice_id'     => $jobAdvice->id,
            'rental_product_id' => $rentalId,
            'room_name'         => 'Lobby',
            'quantity'          => 1,
        ]);

        $assign = JobAssignSchedule::create([
            'job_schedule_id' => $job->id,
            'team_id'         => null,
            'status'          => 'assigned',
        ]);

        return [$assign->id, $unitProduct->id];
    }

    public function test_dropdown_falls_back_to_all_allowed_products_when_is_selected_pivot_is_empty(): void
    {
        [$assignId, $expectedProductId] = $this->seedRentalWithUnselectedMaterials();

        $response = app(JobAssignMaterialIssueController::class)->getJobAssignScheduleDetails($assignId);
        $payload = json_decode($response->getContent(), true);

        $this->assertSame('success', $payload['status'] ?? null);
        $products = $payload['data']['products'] ?? [];

        $this->assertNotEmpty(
            $products,
            'Bug #75 fix: dropdown must fall back to all allowed products when is_selected pivot is empty.'
        );

        $productIds = array_column($products, 'id');
        $this->assertContains(
            $expectedProductId,
            $productIds,
            'Fallback list must include the rental detail\'s allowed product.'
        );
    }

    public function test_dropdown_uses_curated_selection_when_is_selected_is_set(): void
    {
        [$assignId, $unitProductId] = $this->seedRentalWithUnselectedMaterials();

        // Promote the pivot row to is_selected=true and add a SECOND allowed
        // product that is NOT selected. Curated list must win — only the
        // selected product should appear, not the unselected sibling.
        \DB::table('rental_detail_materials')
            ->where('master_product_id', $unitProductId)
            ->update(['is_selected' => true]);

        $unitCategoryId = MasterProduct::find($unitProductId)->product_category_id;
        $sibling = MasterProduct::create([
            'product_category_id' => $unitCategoryId,
            'name'                => 'Diffuser W300 White (not selected)',
            'sku'                 => 'DW300W',
        ]);

        $detailId = \DB::table('rental_details')->value('id');
        \DB::table('rental_detail_materials')->insert([
            'rental_detail_id'  => $detailId,
            'master_product_id' => $sibling->id,
            'is_selected'       => false,
            'sort_order'        => 1,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $response = app(JobAssignMaterialIssueController::class)->getJobAssignScheduleDetails($assignId);
        $payload = json_decode($response->getContent(), true);

        $productIds = array_column($payload['data']['products'] ?? [], 'id');

        $this->assertContains($unitProductId, $productIds, 'Curated product must appear.');
        $this->assertNotContains(
            $sibling->id,
            $productIds,
            'Unselected sibling must NOT appear when curated list is non-empty (no fallback).'
        );
    }
}
