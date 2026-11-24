<?php

namespace Tests\Feature;

use App\Services\Imports\Catalyst\CatalystServiceScheduleBackfillService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Cek prod 25 Nov 2026: 465 kontrak import Catalyst aktif tak punya job advice (Catalyst tak punya
 * baris MKTContractJobOut-nya), jadi tak pernah dapat jadwal service. --create-missing-job-advice
 * membuatkan JA-CATALYST-* dari rental kontrak lalu menjadwalkannya seperti kontrak lain.
 */
class CatalystMissingJobAdviceScheduleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-25 10:00:00');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable();
            $table->softDeletes();
        });
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
        });
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number');
            $table->string('contract_status')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedBigInteger('quotation_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('marketing_id')->nullable();
            $table->softDeletes();
        });
        Schema::create('source_import_maps', function (Blueprint $table) {
            $table->id();
            $table->string('source_system');
            $table->string('source_table');
            $table->string('source_key')->nullable();
            $table->string('target_table');
            $table->unsignedBigInteger('target_id');
        });
        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('nama_gedung')->nullable();
            $table->string('postal_code')->nullable();
        });
        Schema::create('master_rooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('building_id')->nullable();
            $table->string('room_name')->nullable();
        });
        Schema::create('rental_service_frequencies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->integer('frequency_months')->nullable();
            $table->integer('frequency_times_per_month')->nullable();
        });
        Schema::create('master_rentals', function (Blueprint $table) {
            $table->id();
            $table->string('rental_name')->nullable();
            $table->string('rental_type')->nullable();
            $table->unsignedBigInteger('service_frequency_id')->nullable();
        });
        Schema::create('contract_rooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('room_id')->nullable();
        });
        Schema::create('contract_rentals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('master_rental_id')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->string('rental_alias')->nullable();
            $table->decimal('quantity', 10, 2)->nullable();
            $table->decimal('qty_free', 10, 2)->nullable();
        });
        Schema::create('job_advices', function (Blueprint $table) {
            $table->id();
            $table->string('job_advice_number')->nullable();
            $table->string('type')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('company_name')->nullable();
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->unsignedBigInteger('quotation_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('request_by')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->date('expected_date')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('date_approval')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->boolean('with_invoicing')->default(false);
            $table->boolean('with_materials')->default(false);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('job_advice_rooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_advice_id');
            $table->unsignedBigInteger('contract_room_id')->nullable();
            $table->unsignedBigInteger('contract_rental_id')->nullable();
            $table->unsignedBigInteger('rental_product_id')->nullable();
            $table->string('room_name')->nullable();
            $table->string('rental_name')->nullable();
            $table->integer('quantity')->nullable();
            $table->integer('qty_free')->nullable();
            $table->boolean('rental_has_installation')->default(false);
            $table->boolean('rental_has_service')->default(false);
            $table->string('status')->nullable();
            $table->boolean('is_trial')->default(false);
            $table->boolean('unit_already_installed')->default(false);
            $table->unsignedBigInteger('service_job_schedule_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            foreach (['job_number', 'type', 'status', 'building_name', 'room_name', 'company_name', 'contract_number',
                'service_period_type', 'reference_number', 'postal_code', 'internal_notes'] as $column) {
                $table->string($column)->nullable();
            }
            foreach (['job_advice_id', 'building_id', 'room_id', 'created_by', 'updated_by'] as $column) {
                $table->unsignedBigInteger($column)->nullable();
            }
            $table->date('schedule_date')->nullable();
            $table->date('expected_date')->nullable();
            $table->date('ba_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('catalyst_backfill_at')->nullable();
            $table->boolean('material_checked')->default(false);
            $table->timestamp('material_checked_at')->nullable();
            $table->integer('period')->nullable();
            $table->integer('service_frequency')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('job_schedule_rooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_schedule_id');
            $table->unsignedBigInteger('job_advice_room_id')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->string('room_name')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('material_return_status')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
        Schema::create('job_schedule_room_rentals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_schedule_room_id');
            $table->unsignedBigInteger('job_advice_room_id');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        DB::table('customers')->insert(['id' => 1, 'name' => 'PT Contoh']);
        DB::table('buildings')->insert(['id' => 1, 'nama_gedung' => 'Gedung A', 'postal_code' => '10110']);
        DB::table('master_rooms')->insert([
            ['id' => 10, 'building_id' => 1, 'room_name' => 'Lobby'],
            ['id' => 11, 'building_id' => 1, 'room_name' => 'Toilet'],
        ]);
        DB::table('rental_service_frequencies')->insert(['id' => 1, 'name' => 'Monthly', 'frequency_months' => 1, 'frequency_times_per_month' => 1]);
        DB::table('master_rentals')->insert([
            ['id' => 5, 'rental_name' => 'ADS Refill', 'rental_type' => 'unit_refill', 'service_frequency_id' => 1],
            ['id' => 6, 'rental_name' => 'Tanpa Frekuensi', 'rental_type' => 'unit_refill', 'service_frequency_id' => null],
        ]);

        // Kontrak A: import Catalyst, tanpa JA -> harus dijadwalkan.
        DB::table('contracts')->insert(['id' => 100, 'contract_number' => 'ADS-AG/26-06/0005', 'contract_status' => 'active',
            'start_date' => '2026-09-01', 'end_date' => '2027-02-28', 'quotation_id' => 7, 'customer_id' => 1, 'marketing_id' => 3]);
        DB::table('contract_rooms')->insert([['id' => 200, 'contract_id' => 100, 'room_id' => 10], ['id' => 201, 'contract_id' => 100, 'room_id' => 11]]);
        DB::table('contract_rentals')->insert([
            ['id' => 300, 'contract_id' => 100, 'master_rental_id' => 5, 'room_id' => 10, 'rental_alias' => 'Refill Lobby', 'quantity' => 2, 'qty_free' => 0],
            ['id' => 301, 'contract_id' => 100, 'master_rental_id' => 5, 'room_id' => 11, 'rental_alias' => null, 'quantity' => 1, 'qty_free' => 1],
            ['id' => 302, 'contract_id' => 100, 'master_rental_id' => 6, 'room_id' => 11, 'rental_alias' => null, 'quantity' => 1, 'qty_free' => 0],
        ]);

        // Kontrak B: bukan hasil import Catalyst -> tidak disentuh.
        DB::table('contracts')->insert(['id' => 101, 'contract_number' => 'JKT-AG/26-06/0001', 'contract_status' => 'active',
            'start_date' => '2026-09-01', 'end_date' => '2027-02-28', 'customer_id' => 1]);
        // Kontrak C: import Catalyst tapi sudah punya JA (alur lama) -> bukan kontrak "tanpa JA".
        DB::table('contracts')->insert(['id' => 102, 'contract_number' => 'ADS-AG/26-06/0006', 'contract_status' => 'active',
            'start_date' => '2026-09-01', 'end_date' => '2027-02-28', 'customer_id' => 1]);
        DB::table('job_advices')->insert(['contract_id' => 102, 'job_advice_number' => 'JA-CATALYST-ADS-AG/26-06/0006']);
        // Kontrak D: import Catalyst tanpa JA tapi sudah berakhir.
        DB::table('contracts')->insert(['id' => 103, 'contract_number' => 'ADS-AG/24-06/0001', 'contract_status' => 'active',
            'start_date' => '2024-06-01', 'end_date' => '2025-05-31', 'customer_id' => 1]);

        foreach ([100, 102, 103] as $contractId) {
            DB::table('source_import_maps')->insert(['source_system' => 'catalyst', 'source_table' => 'MKTContractHd',
                'source_key' => (string) $contractId, 'target_table' => 'contracts', 'target_id' => $contractId]);
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_only_active_catalyst_contracts_without_any_job_advice_are_picked(): void
    {
        $service = new CatalystServiceScheduleBackfillService;

        $this->assertSame(['ADS-AG/26-06/0005'], $service->contractsWithoutJobAdvice()->pluck('contract_number')->all());
        $this->assertSame(
            ['ADS-AG/24-06/0001', 'ADS-AG/26-06/0005'],
            $service->contractsWithoutJobAdvice(null, true)->pluck('contract_number')->all()
        );
    }

    public function test_dry_run_plans_from_contract_rentals_without_writing(): void
    {
        $service = new CatalystServiceScheduleBackfillService;
        $candidate = $service->contractsWithoutJobAdvice()->first();

        $plan = $service->plan($candidate);

        $this->assertSame('start_date', $plan['anchor_source']);
        $this->assertSame(1, $plan['skipped_rooms']); // rental tanpa frekuensi service
        $this->assertCount(1, $plan['groups']);
        $this->assertSame(['2026-09-01', '2026-10-01', '2026-11-01', '2026-12-01', '2027-01-01', '2027-02-01'], $plan['groups'][0]['dates']);
        $this->assertSame(3, $plan['groups'][0]['done']);
        $this->assertSame(0, DB::table('job_advices')->where('contract_id', 100)->count());
    }

    public function test_apply_creates_catalyst_job_advice_with_rooms_and_schedules(): void
    {
        $this->artisan('catalyst:generate-service-schedules', [
            '--create-missing-job-advice' => true,
            '--apply' => true,
            '--invoice-details' => 'tidak-ada.csv',
            '--contract-details' => 'tidak-ada.csv',
        ])->assertSuccessful();

        $jobAdvice = DB::table('job_advices')->where('contract_id', 100)->first();
        $this->assertSame('JA-CATALYST-ADS-AG/26-06/0005', $jobAdvice->job_advice_number);
        $this->assertSame('approved', $jobAdvice->status);
        $this->assertSame('PT Contoh', $jobAdvice->company_name);
        $this->assertEquals(7, $jobAdvice->quotation_id);
        $this->assertEquals(0, $jobAdvice->with_invoicing);

        $rooms = DB::table('job_advice_rooms')->where('job_advice_id', $jobAdvice->id)->orderBy('contract_rental_id')->get();
        $this->assertSame([300, 301, 302], $rooms->pluck('contract_rental_id')->map(fn ($id) => (int) $id)->all());
        $this->assertSame(['Lobby', 'Toilet', 'Toilet'], $rooms->pluck('room_name')->all());
        $this->assertSame(['Refill Lobby', 'ADS Refill', 'Tanpa Frekuensi'], $rooms->pluck('rental_name')->all());
        $this->assertEquals([200, 201, 201], $rooms->pluck('contract_room_id')->all());
        $this->assertEquals([2, 1, 1], $rooms->pluck('quantity')->all());

        $jobs = DB::table('job_schedules')->where('job_advice_id', $jobAdvice->id)->orderBy('schedule_date')->get();
        $this->assertCount(6, $jobs);
        $this->assertSame(['done_job', 'done_job', 'done_job', 'scheduled', 'scheduled', 'scheduled'], $jobs->pluck('status')->all());
        $this->assertSame('service_first', $jobs->first()->type);
        $this->assertNotNull($jobs->first()->catalyst_backfill_at);
        $this->assertNull($jobs->last()->catalyst_backfill_at);

        // Lobby + Toilet (rental berfrekuensi) dalam satu job per periode.
        $this->assertSame(2, DB::table('job_schedule_rooms')->where('job_schedule_id', $jobs->first()->id)->count());
        // Rental menunjuk job outstanding pertama (1 Des 2026).
        $this->assertEquals($jobs[3]->id, $rooms[0]->service_job_schedule_id);

        // Kontrak lain tidak disentuh; run kedua tidak membuat apa pun lagi.
        $this->assertSame(0, DB::table('job_advices')->whereIn('contract_id', [101, 103])->count());
        $this->artisan('catalyst:generate-service-schedules', ['--create-missing-job-advice' => true, '--apply' => true])->assertSuccessful();
        $this->assertSame(2, DB::table('job_advices')->count());
        $this->assertSame(6, DB::table('job_schedules')->count());
    }

    public function test_contract_without_schedulable_rentals_gets_no_job_advice(): void
    {
        DB::table('contract_rentals')->where('contract_id', 100)->update(['master_rental_id' => 6]);

        $this->artisan('catalyst:generate-service-schedules', ['--create-missing-job-advice' => true, '--apply' => true])->assertSuccessful();

        $this->assertSame(0, DB::table('job_advices')->where('contract_id', 100)->count());
    }
}
