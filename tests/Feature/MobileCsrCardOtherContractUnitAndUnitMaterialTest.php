<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Mobile\JobController;
use App\Http\Controllers\Marketing\JobAdviceController;
use App\Models\JobAdvice;
use App\Models\JobAdviceRoom;
use App\Models\JobSchedule;
use App\Models\JobScheduleRoom;
use App\Models\MasterRental;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

/**
 * QA 27 Nov 2026, customer "Customer Testing Indo":
 *
 * A. ADS-CSR/26-11/0001 (kontrak BLP-CA/26-11/0006, Ruang-4): APK menampilkan SN DIS005S2611230016
 *    milik kontrak BLP-CA/26-11/0004 di samping DIS005S2611230015 milik kontrak ini.
 * B. BLP-CSR/26-11/0005: kartu CSR menampilkan "ADS Dispenser 005S" (produk unit) di daftar material.
 */
class MobileCsrCardOtherContractUnitAndUnitMaterialTest extends TestCase
{
    private const TABLES = [
        'unit_on_wall_histories', 'users', 'master_rooms', 'unit_on_walls', 'job_advice_rooms', 'job_schedules', 'job_advices',
        'contract_rooms', 'contracts', 'quotations',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('job_advices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')->nullable();
            $t->foreignId('contract_id')->nullable();
            $t->foreignId('quotation_id')->nullable();
            $t->string('type')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('job_schedules', function (Blueprint $t) {
            $t->id();
            $t->string('job_number')->nullable();
            $t->string('type')->nullable();
            $t->string('status')->nullable();
            $t->string('quotation_number')->nullable();
            $t->foreignId('job_advice_id')->nullable();
            $t->foreignId('building_id')->nullable();
            $t->foreignId('room_id')->nullable();
            $t->foreignId('assigned_technician_id')->nullable();
            $t->dateTime('completed_at')->nullable();
            $t->date('schedule_date')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('job_advice_rooms', function (Blueprint $t) {
            $t->id();
            $t->foreignId('job_advice_id')->nullable();
            $t->foreignId('contract_room_id')->nullable();
            $t->string('room_name')->nullable();
            $t->foreignId('rental_product_id')->nullable();
            $t->decimal('quantity', 8, 2)->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('contract_rooms', function (Blueprint $t) {
            $t->id();
            $t->foreignId('contract_id')->nullable();
            $t->foreignId('room_id')->nullable();
            $t->timestamps();
        });
        Schema::create('contracts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('quotation_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('quotations', function (Blueprint $t) {
            $t->id();
            $t->string('quotation_number')->nullable();
            $t->foreignId('existing_contract_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('unit_on_walls', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')->nullable();
            $t->foreignId('building_id')->nullable();
            $t->foreignId('room_id')->nullable();
            $t->string('room_name')->nullable();
            $t->foreignId('serial_number_id')->nullable();
            $t->string('serial_number')->nullable();
            $t->foreignId('rental_id')->nullable();
            $t->foreignId('contract_id')->nullable();
            $t->foreignId('install_job_schedule_id')->nullable();
            $t->foreignId('product_id')->nullable();
            $t->string('status')->nullable();
            $t->string('company_name')->nullable();
            $t->date('last_service_date')->nullable();
            $t->foreignId('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('master_rooms', function (Blueprint $t) {
            $t->id();
            $t->string('room_name')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('unit_on_wall_histories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('unit_on_wall_id')->nullable();
            $t->string('action')->nullable();
            $t->foreignId('customer_id')->nullable();
            $t->string('customer_name')->nullable();
            $t->string('location')->nullable();
            $t->date('action_date')->nullable();
            $t->foreignId('technician_id')->nullable();
            $t->string('technician_name')->nullable();
            $t->foreignId('job_schedule_id')->nullable();
            $t->string('job_schedule_number')->nullable();
            $t->text('notes')->nullable();
            $t->text('metadata')->nullable();
            $t->foreignId('created_by')->nullable();
            $t->timestamps();
        });
    }

    protected function tearDown(): void
    {
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    /** @return array{0: JobSchedule, 1: JobAdviceRoom} */
    private function seedRuang4(): array
    {
        DB::table('quotations')->insert([
            ['id' => 29185, 'quotation_number' => 'BLP-SQ/26-11/0004', 'existing_contract_id' => null],
            ['id' => 30577, 'quotation_number' => 'BLP-SQ/26-11/0133', 'existing_contract_id' => null],
        ]);
        DB::table('contracts')->insert([
            ['id' => 21134, 'quotation_id' => 29185], // BLP-CA/26-11/0004
            ['id' => 21792, 'quotation_id' => 30577], // BLP-CA/26-11/0006
        ]);
        DB::table('job_schedules')->insert([
            ['id' => 59861, 'job_number' => 'JKT-IF/26-11/0007', 'type' => 'install_free', 'status' => 'done_job', 'quotation_number' => 'BLP-SQ/26-11/0004'],
            ['id' => 59990, 'job_number' => 'ADS-IF/26-11/0003', 'type' => 'install_free', 'status' => 'done_job', 'quotation_number' => 'BLP-SQ/26-11/0133'],
        ]);
        DB::table('contract_rooms')->insert(['id' => 73495, 'contract_id' => 21792, 'room_id' => 45027]);

        $advice = JobAdvice::create(['customer_id' => 15230, 'contract_id' => 21792, 'type' => 'service']);
        $job = JobSchedule::create([
            'job_number' => 'ADS-CSR/26-11/0001',
            'type' => 'service_first',
            'status' => 'in_progress',
            'job_advice_id' => $advice->id,
            'building_id' => 900,
        ]);
        $room = JobAdviceRoom::create([
            'job_advice_id' => $advice->id,
            'contract_room_id' => 73495,
            'room_name' => 'Ruang-4',
            'rental_product_id' => 384,
            'quantity' => 1,
        ]);

        DB::table('unit_on_walls')->insert([
            [
                'id' => 52, 'customer_id' => 15230, 'building_id' => 900, 'room_id' => 45027, 'room_name' => 'Ruang-4',
                'serial_number' => 'DIS005S2611230016', 'rental_id' => 384, 'contract_id' => 21134,
                'install_job_schedule_id' => 59861, 'status' => 'active',
            ],
            [
                'id' => 63, 'customer_id' => 15230, 'building_id' => 900, 'room_id' => 45027, 'room_name' => 'Ruang-4',
                'serial_number' => 'DIS005S2611230015', 'rental_id' => 384, 'contract_id' => null,
                'install_job_schedule_id' => 59990, 'status' => 'active',
            ],
        ]);

        return [$job, $room];
    }

    public function test_service_card_lists_only_the_unit_of_its_own_contract(): void
    {
        [$job, $room] = $this->seedRuang4();

        $method = new ReflectionMethod(JobController::class, 'wallUnitsForServiceRoom');
        $units = $method->invoke(app(JobController::class), $job, $room);

        $this->assertSame(['DIS005S2611230015'], $units->pluck('serial_number')->all());
    }

    public function test_the_old_contract_csr_still_lists_its_own_unit(): void
    {
        [, $room] = $this->seedRuang4();
        DB::table('contract_rooms')->insert(['id' => 71488, 'contract_id' => 21134, 'room_id' => 45027]);
        $advice = JobAdvice::create(['customer_id' => 15230, 'contract_id' => 21134, 'type' => 'service']);
        $oldJob = JobSchedule::create([
            'job_number' => 'JKT-CSR/26-11/0005', 'type' => 'service_first', 'status' => 'in_progress',
            'job_advice_id' => $advice->id, 'building_id' => 900,
        ]);
        $oldRoom = JobAdviceRoom::create([
            'job_advice_id' => $advice->id, 'contract_room_id' => 71488, 'room_name' => 'Ruang-4',
            'rental_product_id' => 384, 'quantity' => 1,
        ]);

        $method = new ReflectionMethod(JobController::class, 'wallUnitsForServiceRoom');
        $units = $method->invoke(app(JobController::class), $oldJob, $oldRoom);

        $this->assertSame(['DIS005S2611230016'], $units->pluck('serial_number')->all());
    }

    public function test_job_advice_room_links_the_unit_of_its_own_contract_or_quotation(): void
    {
        $this->seedRuang4();
        $method = new ReflectionMethod(JobAdviceController::class, 'existingUnitOnWallForJobAdviceRoom');
        $pick = fn (JobAdvice $advice) => $method->invoke(app(JobAdviceController::class), $advice, 45027, 384)?->id;

        // JA kontrak baru: bukan unit kontrak BLP-CA/26-11/0004.
        $this->assertSame(63, $pick(JobAdvice::create(['contract_id' => 21792, 'quotation_id' => 30577, 'type' => 'install'])));
        // JA kontrak lama tetap mendapat unitnya sendiri.
        $this->assertSame(52, $pick(JobAdvice::create(['contract_id' => 21134, 'quotation_id' => 29185, 'type' => 'service'])));
        // JA trial dari SQ (belum berkontrak): unit yang sudah terikat kontrak lain tak ikut.
        $this->assertSame(63, $pick(JobAdvice::create(['quotation_id' => 30577, 'type' => 'install_free'])));
    }

    public function test_completing_the_csr_records_service_only_on_its_own_contract_unit(): void
    {
        // Prod 27 Nov 2026: ADS-CSR/26-11/0001 menulis riwayat service + last_service_date ke
        // DIS...0016 (kontrak BLP-CA/26-11/0004) juga.
        [$job] = $this->seedRuang4();
        DB::table('users')->insert(['id' => 60, 'name' => 'QA']);
        $job->forceFill(['room_id' => 45027, 'completed_at' => '2026-11-27 16:51:00'])->save();
        $job->setRelation('room', null);
        $job->setRelation('assignedTechnician', null);

        $method = new ReflectionMethod(\App\Http\Controllers\Operational\JobScheduleController::class, 'autoUpdateUnitOnWallLastServiceDate');
        $method->invoke(app(\App\Http\Controllers\Operational\JobScheduleController::class), $job, $job->jobAdvice);

        $this->assertSame([63], DB::table('unit_on_wall_histories')->where('action', 'service')->pluck('unit_on_wall_id')->all());
        $this->assertNull(DB::table('unit_on_walls')->where('id', 52)->value('last_service_date'));
        $this->assertStringStartsWith('2026-11-27', (string) DB::table('unit_on_walls')->where('id', 63)->value('last_service_date'));
    }

    private function product(int $id, string $name, string $source, bool $isUnit, string $serial = ''): array
    {
        return [
            'product_id' => $id,
            'product_name' => $name,
            'source' => $source,
            'is_unit' => $isUnit,
            'serial_number' => $serial,
        ];
    }

    private function cardProducts(): array
    {
        return [
            $this->product(561, 'Liquid ADS Cleaner (Alkohol) - 100 ml', 'inventory_issuing', false),
            $this->product(4, 'Fragrance Airy Ginger 100 ml', 'inventory_issuing', false),
            $this->product(568, 'ADS Dispenser 005S', 'inventory_issuing', true),
            $this->product(568, 'ADS Dispenser 005S', 'material_issue', true),
            $this->product(568, 'ADS Dispenser 005S', 'rental_component', 1),
            $this->product(568, 'ADS Dispenser 005S', 'unit_on_wall', true, 'DIS005S2611230010'),
        ];
    }

    private function filterFor(JobSchedule $job): array
    {
        $method = new ReflectionMethod(JobController::class, 'withoutUnitMaterialsForServiceJob');

        return $method->invoke(app(JobController::class), $job, $this->cardProducts());
    }

    private function jobOfType(string $type, ?string $rentalType = 'unit_refill'): JobSchedule
    {
        $job = new JobSchedule(['type' => $type]);

        $rental = new MasterRental;
        $rental->id = 987654;
        $rental->rental_type = $rentalType;
        $adviceRoom = new JobAdviceRoom;
        $adviceRoom->setRelation('rentalProduct', $rental);
        $scheduleRoom = new JobScheduleRoom;
        $scheduleRoom->setRelation('rentals', collect());
        $scheduleRoom->setRelation('jobAdviceRoom', $adviceRoom);
        $job->setRelation('jobScheduleRooms', collect([$scheduleRoom]));

        return $job;
    }

    public function test_service_job_material_list_drops_unit_products_but_keeps_the_unit_on_the_wall(): void
    {
        foreach (['service', 'service_first', 'service_routine'] as $type) {
            $products = $this->filterFor($this->jobOfType($type));

            $this->assertSame(
                [
                    ['product_id' => 561, 'source' => 'inventory_issuing'],
                    ['product_id' => 4, 'source' => 'inventory_issuing'],
                    ['product_id' => 568, 'source' => 'unit_on_wall'],
                ],
                array_map(fn ($p) => ['product_id' => $p['product_id'], 'source' => $p['source']], $products),
                $type
            );
        }
    }

    public function test_other_job_types_and_job_check_keep_their_list(): void
    {
        foreach (['install', 'install_free', 'change_rental', 'ganti_unit', 'complain', 'remove'] as $type) {
            $this->assertCount(6, $this->filterFor($this->jobOfType($type)), $type);
        }

        $jobCheck = $this->jobOfType('service_routine', 'unit_only');
        $this->assertSame('Job Check', $jobCheck->display_type);
        $this->assertCount(6, $this->filterFor($jobCheck));
    }
}
