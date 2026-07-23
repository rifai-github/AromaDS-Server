<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Services\Operational\ContractOnWallCsrService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContractOnWallCsrServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-05-04 10:00:00');

        $this->createSchema();
    }

    protected function tearDown(): void
    {
        foreach ([
            'job_schedule_room_rentals',
            'job_schedule_rooms',
            'job_schedules',
            'job_advice_rooms',
            'job_advices',
            'unit_on_walls',
            'rental_details',
            'contract_rentals',
            'master_rentals',
            'rental_service_frequencies',
            'contract_rooms',
            'contracts',
            'quotations',
            'surveys',
            'master_rooms',
            'buildings',
            'customers',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_creates_first_csr_for_contract_room_with_active_unit_on_wall(): void
    {
        $contract = $this->seedContractWithOnWallRoom();

        $createdCount = app(ContractOnWallCsrService::class)->createForContract($contract, 7, 'approved');

        $this->assertSame(1, $createdCount);
        $this->assertDatabaseHas('job_advices', [
            'contract_id' => $contract->id,
            'type' => 'service',
            'status' => 'approved',
        ]);
        $this->assertDatabaseHas('job_schedules', [
            'contract_number' => 'JKT-CA/26-05/0001',
            'type' => 'service_first',
            'room_id' => 1,
            'material_checked' => false,
        ]);
        $this->assertDatabaseCount('job_schedule_room_rentals', 1);
    }

    public function test_it_creates_first_csr_when_active_unit_on_wall_has_no_serial_number(): void
    {
        $contract = $this->seedContractWithOnWallRoom();

        DB::table('unit_on_walls')->where('id', 1)->update([
            'serial_number_id' => null,
            'serial_number' => null,
        ]);

        $createdCount = app(ContractOnWallCsrService::class)->createForContract($contract->fresh(), 7, 'approved');

        $this->assertSame(1, $createdCount);
        $this->assertDatabaseHas('job_schedules', [
            'contract_number' => 'JKT-CA/26-05/0001',
            'type' => 'service_first',
            'room_id' => 1,
        ]);
    }

    public function test_it_does_not_create_duplicate_csr_for_same_contract_room(): void
    {
        $contract = $this->seedContractWithOnWallRoom();

        app(ContractOnWallCsrService::class)->createForContract($contract, 7, 'approved');
        $createdCount = app(ContractOnWallCsrService::class)->createForContract($contract->fresh(), 7, 'posted');

        $this->assertSame(0, $createdCount);
        $this->assertSame(1, DB::table('job_schedules')->where('type', 'service_first')->count());
        $this->assertSame(1, DB::table('job_advices')->where('type', 'service')->count());
    }

    public function test_it_excludes_unit_only_rentals_from_first_csr_for_mixed_room(): void
    {
        $contract = $this->seedContractWithOnWallRoom(includeUnitOnlyRental: true);

        $createdCount = app(ContractOnWallCsrService::class)->createForContract($contract, 7, 'approved');

        $this->assertSame(1, $createdCount);
        $this->assertDatabaseCount('job_advice_rooms', 1);
        $this->assertDatabaseHas('job_advice_rooms', [
            'contract_rental_id' => 1,
            'rental_product_id' => 1,
            'rental_has_service' => true,
        ]);
        $this->assertDatabaseMissing('job_advice_rooms', [
            'contract_rental_id' => 2,
            'rental_product_id' => 2,
        ]);
        $this->assertDatabaseCount('job_schedule_room_rentals', 1);
    }

    public function test_it_does_not_create_first_csr_from_unit_on_wall_owned_by_another_quotation(): void
    {
        $this->seedContractWithOnWallRoom();

        DB::table('quotations')->insert([
            'id' => 2,
            'quotation_number' => 'JKT-SQ/26-05/0002',
            'survey_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('contracts')->insert([
            'id' => 2,
            'contract_number' => 'JKT-CA/26-05/0002',
            'customer_id' => 1,
            'quotation_id' => 2,
            'contract_status' => 'active',
            'first_service_date' => '2026-05-10',
            'start_date' => '2026-05-04',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('contract_rooms')->insert([
            'id' => 2,
            'contract_id' => 2,
            'room_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('contract_rentals')->insert([
            'id' => 3,
            'contract_id' => 2,
            'master_rental_id' => 1,
            'room_id' => 1,
            'rental_alias' => 'Aroma On Wall',
            'quantity' => 1,
            'unit_price' => 100,
            'total_price' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $createdCount = app(ContractOnWallCsrService::class)->createForContract(Contract::findOrFail(2), 7, 'approved');

        $this->assertSame(0, $createdCount);
        $this->assertDatabaseMissing('job_advices', [
            'contract_id' => 2,
            'type' => 'service',
        ]);
        $this->assertDatabaseMissing('job_schedules', [
            'contract_number' => 'JKT-CA/26-05/0002',
            'type' => 'service_first',
        ]);
    }

    public function test_it_creates_first_csr_for_renewal_contract_from_old_contract_unit_on_wall(): void
    {
        $this->seedContractWithOnWallRoom();

        DB::table('quotations')->insert([
            'id' => 2,
            'quotation_number' => 'JKT-SQ/26-05/0002',
            'quotation_type' => 'renewal',
            'existing_contract_id' => 1,
            'survey_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('contracts')->insert([
            'id' => 2,
            'contract_number' => 'JKT-CA/26-05/0002',
            'customer_id' => 1,
            'quotation_id' => 2,
            'contract_status' => 'active',
            'first_service_date' => '2026-05-15',
            'start_date' => '2026-05-15',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('contract_rooms')->insert([
            'id' => 2,
            'contract_id' => 2,
            'room_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('contract_rentals')->insert([
            'id' => 3,
            'contract_id' => 2,
            'master_rental_id' => 1,
            'room_id' => 1,
            'rental_alias' => 'Aroma On Wall Renewal',
            'quantity' => 1,
            'unit_price' => 100,
            'total_price' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $createdCount = app(ContractOnWallCsrService::class)->createForContract(Contract::findOrFail(2), 7, 'approved');

        $this->assertSame(1, $createdCount);
        $this->assertDatabaseHas('job_advices', [
            'contract_id' => 2,
            'quotation_id' => 2,
            'type' => 'service',
        ]);
        $this->assertDatabaseHas('job_schedules', [
            'contract_number' => 'JKT-CA/26-05/0002',
            'quotation_number' => 'JKT-SQ/26-05/0002',
            'type' => 'service_first',
            'room_id' => 1,
        ]);
    }

    private function createSchema(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('nama_gedung')->nullable();
            $table->string('kode_pos')->nullable();
            $table->string('postal_code')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->nullable();
            $table->string('room_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number')->nullable();
            $table->string('quotation_type')->nullable();
            $table->foreignId('existing_contract_id')->nullable();
            $table->foreignId('survey_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('quotation_id')->nullable();
            $table->string('contract_status')->nullable();
            $table->date('first_service_date')->nullable();
            $table->date('install_date')->nullable();
            $table->date('start_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contract_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('rental_service_frequencies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->integer('frequency_times_per_month')->nullable();
            $table->integer('frequency_months')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_rentals', function (Blueprint $table) {
            $table->id();
            $table->string('rental_name')->nullable();
            $table->string('rental_type')->nullable();
            $table->foreignId('service_frequency_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contract_rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('master_rental_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->string('rental_alias')->nullable();
            $table->decimal('quantity', 8, 2)->nullable();
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->decimal('total_price', 12, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('rental_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_rental_id')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->decimal('quantity', 8, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('unit_on_walls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('building_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->foreignId('serial_number_id')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('status')->nullable();
            $table->string('room_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_advices', function (Blueprint $table) {
            $table->id();
            $table->string('job_advice_number')->nullable();
            $table->string('type')->nullable();
            $table->string('company_name')->nullable();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('quotation_id')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('request_by')->nullable();
            $table->foreignId('submitted_by')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->date('expected_date')->nullable();
            $table->date('first_service_date')->nullable();
            $table->string('status')->nullable();
            $table->dateTime('date_approval')->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->boolean('with_invoicing')->default(false);
            $table->boolean('with_materials')->default(false);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_advice_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_advice_id')->nullable();
            $table->foreignId('contract_room_id')->nullable();
            $table->foreignId('contract_rental_id')->nullable();
            $table->foreignId('rental_product_id')->nullable();
            $table->string('room_name')->nullable();
            $table->string('rental_name')->nullable();
            $table->integer('quantity')->nullable();
            $table->decimal('rental_specification_ml', 10, 2)->nullable();
            $table->boolean('rental_has_installation')->default(false);
            $table->boolean('rental_has_service')->default(false);
            $table->string('status')->nullable();
            $table->foreignId('service_job_schedule_id')->nullable();
            $table->boolean('unit_already_installed')->default(false);
            $table->foreignId('existing_unit_on_wall_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
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
            $table->string('building_name')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->string('room_name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('contract_number')->nullable();
            $table->string('quotation_number')->nullable();
            $table->date('schedule_date')->nullable();
            $table->date('expected_date')->nullable();
            $table->integer('period')->nullable();
            $table->integer('service_frequency')->nullable();
            $table->string('service_period_type')->nullable();
            $table->text('internal_notes')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('district')->nullable();
            $table->string('sub_district')->nullable();
            $table->boolean('material_checked')->default(false);
            $table->dateTime('material_checked_at')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedule_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('job_advice_room_id')->nullable();
            $table->string('room_name')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->string('status')->nullable();
            $table->string('material_return_status')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedule_room_rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_room_id')->nullable();
            $table->foreignId('job_advice_room_id')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function seedContractWithOnWallRoom(bool $includeUnitOnlyRental = false): Contract
    {
        DB::table('customers')->insert([
            'id' => 1,
            'name' => 'Group Hiro',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('buildings')->insert([
            'id' => 1,
            'name' => 'Hiro Building',
            'nama_gedung' => 'Hiro Building',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('master_rooms')->insert([
            'id' => 1,
            'building_id' => 1,
            'room_name' => 'Lobby',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('surveys')->insert([
            'id' => 1,
            'building_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('quotations')->insert([
            'id' => 1,
            'quotation_number' => 'JKT-SQ/26-05/0001',
            'survey_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('contracts')->insert([
            'id' => 1,
            'contract_number' => 'JKT-CA/26-05/0001',
            'customer_id' => 1,
            'quotation_id' => 1,
            'contract_status' => 'active',
            'first_service_date' => '2026-05-10',
            'start_date' => '2026-05-04',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('contract_rooms')->insert([
            'id' => 1,
            'contract_id' => 1,
            'room_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('rental_service_frequencies')->insert([
            'id' => 1,
            'name' => 'monthly',
            'frequency_times_per_month' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('master_rentals')->insert([
            'id' => 1,
            'rental_name' => 'Aroma On Wall',
            'rental_type' => 'unit_refill',
            'service_frequency_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        if ($includeUnitOnlyRental) {
            DB::table('master_rentals')->insert([
                'id' => 2,
                'rental_name' => 'Unit Only',
                'rental_type' => 'unit_only',
                'service_frequency_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        DB::table('contract_rentals')->insert([
            'id' => 1,
            'contract_id' => 1,
            'master_rental_id' => 1,
            'room_id' => 1,
            'rental_alias' => 'Aroma On Wall',
            'quantity' => 1,
            'unit_price' => 100,
            'total_price' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        if ($includeUnitOnlyRental) {
            DB::table('contract_rentals')->insert([
                'id' => 2,
                'contract_id' => 1,
                'master_rental_id' => 2,
                'room_id' => 1,
                'rental_alias' => 'Unit Only',
                'quantity' => 1,
                'unit_price' => 50,
                'total_price' => 50,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        DB::table('unit_on_walls')->insert([
            'id' => 1,
            'customer_id' => 1,
            'building_id' => 1,
            'room_id' => 1,
            'serial_number_id' => 10,
            'serial_number' => 'SN-ONWALL-001',
            'status' => 'active',
            'room_name' => 'Lobby',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('job_advices')->insert([
            'id' => 10,
            'job_advice_number' => 'JKT-JA/26-05/0001',
            'type' => 'install_free',
            'company_name' => 'Group Hiro',
            'quotation_id' => 1,
            'customer_id' => 1,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('job_schedules')->insert([
            'id' => 10,
            'job_number' => 'JKT-IF/26-05/0001',
            'type' => 'install_free',
            'status' => 'done_job',
            'job_advice_id' => 10,
            'building_id' => 1,
            'room_id' => 1,
            'room_name' => 'Lobby',
            'company_name' => 'Group Hiro',
            'quotation_number' => 'JKT-SQ/26-05/0001',
            'schedule_date' => '2026-05-04',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Contract::findOrFail(1);
    }
}
