<?php

namespace Tests\Feature;

use App\Models\ContractRental;
use App\Models\JobAdvice;
use App\Models\JobAdviceRoom;
use App\Models\JobSchedule;
use App\Models\JobScheduleRoom;
use App\Services\DocumentNumberService;
use App\Services\Operational\ChangeRentalCompletionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

/**
 * QA (SBY-JA/26-08/0033 on contract SBY-CA/26-08/0011): a Change Rental job reached
 * Done Job but the contract still billed the old rental, service periods 3 and 4 still
 * pulled the old rental, and no RV job ever appeared for the replaced unit.
 */
class ChangeRentalCompletionTest extends TestCase
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

        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
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

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contract_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->timestamps();
        });

        Schema::create('master_rentals', function (Blueprint $table) {
            $table->id();
            $table->string('rental_name')->nullable();
            $table->string('rental_type')->nullable();
            $table->decimal('monthly_price', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contract_rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('master_rental_id')->nullable();
            $table->string('rental_alias')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->integer('quantity')->default(1);
            $table->integer('qty_free')->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0);
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('job_advices', function (Blueprint $table) {
            $table->id();
            $table->string('job_advice_number')->nullable();
            $table->string('type')->nullable();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->date('remove_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_advice_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_advice_id')->nullable();
            $table->foreignId('contract_room_id')->nullable();
            $table->foreignId('quotation_room_id')->nullable();
            $table->foreignId('contract_rental_id')->nullable();
            $table->foreignId('quotation_rental_id')->nullable();
            $table->foreignId('quotation_detail_id')->nullable();
            $table->foreignId('rental_product_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->string('room_name')->nullable();
            $table->string('rental_name')->nullable();
            $table->integer('quantity')->default(1);
            $table->integer('qty_free')->default(0);
            $table->string('status')->nullable();
            $table->foreignId('install_job_schedule_id')->nullable();
            $table->foreignId('service_job_schedule_id')->nullable();
            $table->foreignId('remove_job_schedule_id')->nullable();
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
            $table->string('company_name')->nullable();
            $table->string('contract_number')->nullable();
            $table->string('quotation_number')->nullable();
            $table->integer('period')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->string('room_name')->nullable();
            $table->date('schedule_date')->nullable();
            $table->date('expected_date')->nullable();
            $table->boolean('material_checked')->default(false);
            $table->timestamp('material_checked_at')->nullable();
            $table->text('internal_notes')->nullable();
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
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable();
            $table->string('material_return_status')->nullable();
            $table->text('notes')->nullable();
            $table->text('completion_notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('unit_on_walls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('contract_room_id')->nullable();
            $table->foreignId('install_job_schedule_id')->nullable();
            $table->foreignId('building_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->foreignId('rental_id')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->foreignId('serial_number_id')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('install_date')->nullable();
            $table->date('last_service_date')->nullable();
            $table->string('status')->nullable();
            $table->text('notes')->nullable();
            $table->string('company_name')->nullable();
            $table->string('room_name')->nullable();
            $table->string('rental_name')->nullable();
            $table->string('product_name')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $documentNumbers = Mockery::mock(DocumentNumberService::class);
        $documentNumbers->shouldReceive('generate')->andReturn('SBY-RV/26-08/0001');
        $this->instance(DocumentNumberService::class, $documentNumbers);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    /**
     * Builds the QA scenario: an Install JA whose room runs on the old rental with two
     * service periods still pending, plus a completed Change Rental job carrying the new
     * rental.
     */
    private function makeScenario(): array
    {
        DB::table('users')->insert(['id' => 1, 'name' => 'Tester']);
        DB::table('buildings')->insert(['id' => 7, 'name' => 'Gedung Test']);
        DB::table('master_rooms')->insert(['id' => 13, 'building_id' => 7, 'room_name' => 'Ruang Test Ganti Rental']);
        DB::table('contracts')->insert(['id' => 5, 'contract_number' => 'SBY-CA/26-08/0011', 'customer_id' => 9]);
        DB::table('contract_rooms')->insert(['id' => 18, 'contract_id' => 5, 'room_id' => 13]);

        DB::table('master_rentals')->insert([
            ['id' => 10, 'rental_name' => 'Rental07 - 1 x 1 Bulan', 'rental_type' => 'unit_refill', 'monthly_price' => 1500000],
            ['id' => 4, 'rental_name' => 'Rental 1x 1bln', 'rental_type' => 'unit_refill', 'monthly_price' => 1000000],
        ]);

        $contractRental = ContractRental::create([
            'contract_id' => 5,
            'master_rental_id' => 10,
            'rental_alias' => 'Alias Lama',
            'room_id' => 13,
            'quantity' => 1,
            'qty_free' => 0,
            'unit_price' => 1500000,
            'total_price' => 1500000,
        ]);

        $installAdvice = JobAdvice::create([
            'job_advice_number' => 'SBY-JA/26-08/0030',
            'type' => 'Install',
            'contract_id' => 5,
            'customer_id' => 9,
        ]);

        $oldRoom = JobAdviceRoom::create([
            'job_advice_id' => $installAdvice->id,
            'contract_room_id' => 18,
            'contract_rental_id' => $contractRental->id,
            'rental_product_id' => 10,
            'room_name' => 'Ruang Test Ganti Rental',
            'rental_name' => 'Rental07 - 1 x 1 Bulan',
            'quantity' => 1,
            'status' => JobAdviceRoom::STATUS_COMPLETED,
        ]);

        $installJob = JobSchedule::create([
            'job_number' => 'SBY-IR/26-08/0010',
            'type' => 'install',
            'status' => 'done_job',
            'job_advice_id' => $installAdvice->id,
            'building_id' => 7,
            'room_id' => 13,
            'schedule_date' => '2026-08-19',
        ]);
        $oldRoom->update(['install_job_schedule_id' => $installJob->id]);

        // Period 3 and 4: still open, still hanging off the old rental's JA room.
        $pendingServiceJobs = [];
        foreach ([3 => '2026-10-19', 4 => '2026-11-19'] as $period => $date) {
            $serviceJob = JobSchedule::create([
                'type' => 'service',
                'status' => $period === 3 ? 'new_job' : 'scheduled',
                'job_advice_id' => $installAdvice->id,
                'building_id' => 7,
                'period' => $period,
                'room_id' => 13,
                'schedule_date' => $date,
            ]);

            JobScheduleRoom::create([
                'job_schedule_id' => $serviceJob->id,
                'job_advice_room_id' => $oldRoom->id,
                'room_id' => 13,
                'room_name' => 'Ruang Test Ganti Rental',
                'status' => JobScheduleRoom::STATUS_PENDING,
            ]);

            $pendingServiceJobs[$period] = $serviceJob;
        }

        DB::table('unit_on_walls')->insert([
            'contract_id' => 5,
            'customer_id' => 9,
            'contract_room_id' => 18,
            'install_job_schedule_id' => $installJob->id,
            'building_id' => 7,
            'room_id' => 13,
            'rental_id' => 10,
            'serial_number' => 'DIFF3030005',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $changeAdvice = JobAdvice::create([
            'job_advice_number' => 'SBY-JA/26-08/0033',
            'type' => 'change_rental',
            'contract_id' => 5,
            'customer_id' => 9,
        ]);

        $newRoom = JobAdviceRoom::create([
            'job_advice_id' => $changeAdvice->id,
            'contract_room_id' => 18,
            'rental_product_id' => 4,
            'room_name' => 'Ruang Test Ganti Rental',
            'rental_name' => 'Rental 1x 1bln',
            'quantity' => 1,
            'status' => JobAdviceRoom::STATUS_COMPLETED,
        ]);

        $changeJob = JobSchedule::create([
            'job_number' => 'SBY-EXT/26-08/0007',
            'type' => 'change',
            'status' => 'done_job',
            'job_advice_id' => $changeAdvice->id,
            'building_id' => 7,
            'room_id' => 13,
            'room_name' => 'Ruang Test Ganti Rental',
            'contract_number' => 'SBY-CA/26-08/0011',
            'schedule_date' => '2026-08-24',
        ]);

        JobScheduleRoom::create([
            'job_schedule_id' => $changeJob->id,
            'job_advice_room_id' => $newRoom->id,
            'room_id' => 13,
            'room_name' => 'Ruang Test Ganti Rental',
            'status' => JobScheduleRoom::STATUS_COMPLETED,
        ]);

        return compact('contractRental', 'oldRoom', 'newRoom', 'changeJob', 'changeAdvice', 'installJob', 'pendingServiceJobs');
    }

    private function service(): ChangeRentalCompletionService
    {
        return app(ChangeRentalCompletionService::class);
    }

    public function test_completed_change_rental_moves_the_contract_onto_the_new_rental(): void
    {
        $scenario = $this->makeScenario();

        $this->service()->handleCompletedJob($scenario['changeJob']);

        $contractRental = $scenario['contractRental']->fresh();

        $this->assertSame(4, (int) $contractRental->master_rental_id);
        $this->assertSame(1000000.0, (float) $contractRental->unit_price);
        $this->assertSame(1000000.0, (float) $contractRental->total_price);
        $this->assertNull($contractRental->rental_alias, 'The replaced rental alias must not survive the change.');
    }

    public function test_completed_change_rental_moves_remaining_service_periods_onto_the_new_rental(): void
    {
        $scenario = $this->makeScenario();

        $this->service()->handleCompletedJob($scenario['changeJob']);

        $oldRoom = $scenario['oldRoom']->fresh();

        $this->assertSame(4, (int) $oldRoom->rental_product_id);
        $this->assertSame('Rental 1x 1bln', $oldRoom->rental_name);

        // Both pending periods read their rental through this row, so both follow the change.
        foreach ($scenario['pendingServiceJobs'] as $serviceJob) {
            $scheduleRoom = JobScheduleRoom::where('job_schedule_id', $serviceJob->id)->first();
            $this->assertSame(
                4,
                (int) JobAdviceRoom::find($scheduleRoom->job_advice_room_id)->rental_product_id
            );
        }
    }

    public function test_completed_change_rental_raises_a_remove_job_for_the_replaced_rental(): void
    {
        $scenario = $this->makeScenario();

        $this->service()->handleCompletedJob($scenario['changeJob']);

        $removeJob = JobSchedule::where('type', 'remove')->first();

        $this->assertNotNull($removeJob, 'A Change Rental must return the replaced unit via an RV job.');
        $this->assertSame('new_job', $removeJob->status);
        $this->assertSame('2026-08-24', $removeJob->schedule_date->toDateString());
        $this->assertTrue((bool) $removeJob->material_checked);

        // The RV job must hold the OLD rental, even though the room itself has already been
        // moved onto the new one — autoRemoveUnitOnWall() matches units by that rental id.
        $removeScheduleRoom = JobScheduleRoom::where('job_schedule_id', $removeJob->id)->first();
        $this->assertNotNull($removeScheduleRoom);

        $removeAdviceRoom = JobAdviceRoom::find($removeScheduleRoom->job_advice_room_id);
        $this->assertSame(10, (int) $removeAdviceRoom->rental_product_id);
        $this->assertSame('Rental07 - 1 x 1 Bulan', $removeAdviceRoom->rental_name);
        $this->assertSame((int) $removeJob->id, (int) $removeAdviceRoom->remove_job_schedule_id);

        // Never NULL: a NULL pointer means "belongs to every job of this JA" and would leak
        // the replaced rental back into the Change job's own room list.
        $this->assertSame((int) $removeJob->id, (int) $removeAdviceRoom->install_job_schedule_id);
        $this->assertSame((int) $removeJob->id, (int) $removeAdviceRoom->service_job_schedule_id);
    }

    public function test_change_rental_completion_is_idempotent(): void
    {
        $scenario = $this->makeScenario();

        $this->service()->handleCompletedJob($scenario['changeJob']);
        $this->service()->handleCompletedJob($scenario['changeJob']->fresh());

        $this->assertSame(1, JobSchedule::where('type', 'remove')->count());
    }

    public function test_change_unit_job_advice_never_swaps_the_rental(): void
    {
        $scenario = $this->makeScenario();

        // Same schedule type ('change'), different Job Advice intent: the physical unit is
        // swapped but the rental stays put.
        $scenario['changeAdvice']->update(['type' => 'Change Unit']);

        $this->service()->handleCompletedJob($scenario['changeJob']->fresh());

        $this->assertSame(10, (int) $scenario['contractRental']->fresh()->master_rental_id);
        $this->assertSame(10, (int) $scenario['oldRoom']->fresh()->rental_product_id);
        $this->assertSame(0, JobSchedule::where('type', 'remove')->count());
    }

    public function test_unfinished_change_rental_job_changes_nothing(): void
    {
        $scenario = $this->makeScenario();
        $scenario['changeJob']->update(['status' => 'teknisi_sedang_pengerjaan']);

        $this->service()->handleCompletedJob($scenario['changeJob']->fresh());

        $this->assertSame(10, (int) $scenario['contractRental']->fresh()->master_rental_id);
        $this->assertSame(0, JobSchedule::where('type', 'remove')->count());
    }
}
