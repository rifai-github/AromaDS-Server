<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Services\Operational\ContractOnWallCsrService;
use App\Services\Operational\InstallFreeContractRoomScope;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA 27 Nov 2026 (Customer Testing Indo, BLP-SQ/26-11/0133 rev 1 → kontrak BLP-CA/26-11/0005):
 * trial Install Free di Ruang-1, Ruang-3 (Unit Only) dan Ruang-6 (Unit + Refill). Remove Free
 * Ruang-1 sudah selesai (ADS-RF/26-11/0001, unit dicabut); Ruang-3 dan Ruang-6 lanjut ke kontrak
 * (Remove Free-nya dibatalkan). Saat kontrak disetujui, Ruang-1 tetap dibuatkan Job Check 2–12
 * padahal unitnya sudah tidak ada. Hanya ruangan yang unitnya masih di lokasi yang boleh lanjut.
 */
class TrialRoomAlreadyRemovedNoContinuationTest extends TestCase
{
    private const SQ = 'BLP-SQ/26-11/0133';

    private const OTHER_SQ = 'BLP-SQ/26-11/0003';

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-27 14:43:00');
        $this->createSchema();
        $this->seedQaCase();
    }

    protected function tearDown(): void
    {
        foreach ([
            'users', 'customers', 'buildings', 'master_rooms', 'surveys', 'quotations', 'quotation_rooms',
            'contracts', 'contract_rooms', 'rental_service_frequencies', 'master_rentals', 'contract_rentals',
            'rental_details', 'unit_on_walls', 'job_advices', 'job_advice_rooms', 'job_schedules',
            'job_schedule_rooms', 'job_schedule_room_rentals',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_removed_trial_room_gets_no_job_check_while_room_still_on_wall_does(): void
    {
        $created = app(ContractOnWallCsrService::class)->createForContract(Contract::findOrFail(21791), 28, 'approved');

        $this->assertSame(1, $created, 'CSR pertama hanya untuk Ruang-6 (Unit + Refill).');
        $this->assertSame(1, DB::table('job_schedules')->where('type', 'service_first')->where('room_id', 6)->count());

        $this->assertSame(
            0,
            DB::table('job_schedules')->whereIn('type', ['service', 'service_first', 'service_routine'])->where('room_id', 1)->count(),
            'Ruang-1 sudah Remove Free: tidak boleh ada Job Check / CSR lanjutan.'
        );
        $this->assertSame(0, DB::table('job_schedule_rooms')->where('room_id', 1)->whereNotIn('job_schedule_id', [59930, 59935])->count());

        $ruang3Checks = DB::table('job_schedules')->where('type', 'service_routine')->where('room_id', 3)->orderBy('period')->pluck('period')->map(fn ($p) => (int) $p)->all();
        $this->assertSame(range(2, 12), $ruang3Checks, 'Ruang-3 masih di lokasi: Job Check 2–12 tetap dibuat.');
    }

    public function test_removed_unit_refill_trial_room_gets_no_csr_from_another_unit_in_the_room(): void
    {
        // Ruang-1 jadi Unit + Refill, dan di ruangan itu masih ada unit aktif milik kontrak lain
        // (trial SQ lain yang sudah jadi kontrak: bukan "trial SQ lain" lagi bagi filter lama).
        DB::table('master_rentals')->where('id', 379)->update(['rental_type' => 'unit_refill']);
        DB::table('unit_on_walls')->where('id', 45)->update(['contract_id' => 21134]);

        $created = app(ContractOnWallCsrService::class)->createForContract(Contract::findOrFail(21791), 28, 'approved');

        $this->assertSame(1, $created);
        $this->assertSame(0, DB::table('job_schedules')->where('room_id', 1)->whereIn('type', ['service', 'service_first', 'service_routine'])->count());
        $this->assertSame(1, DB::table('job_schedules')->where('type', 'service_first')->where('room_id', 6)->count());
    }

    public function test_trial_removed_rooms_are_only_rooms_whose_remove_free_is_done_and_have_no_unit_left(): void
    {
        $scope = app(InstallFreeContractRoomScope::class);

        $this->assertSame([1], $scope->trialRemovedRoomIds(self::SQ)->all());
        $this->assertSame([], $scope->trialRemovedRoomIds(self::OTHER_SQ)->all(), 'Remove Free SQ lain tidak ikut.');
        $this->assertSame([], $scope->trialRemovedRoomIds(null)->all());

        // Dipasang ulang oleh job SQ yang sama → unit kembali di lokasi.
        DB::table('job_schedules')->insert(['id' => 59937, 'type' => 'install_free', 'status' => 'done_job', 'quotation_number' => self::SQ, 'room_id' => 1]);
        DB::table('unit_on_walls')->insert(['id' => 63, 'customer_id' => 1, 'room_id' => 1, 'status' => 'active', 'install_job_schedule_id' => 59937]);
        $this->assertSame([], $scope->trialRemovedRoomIds(self::SQ)->all());
    }

    public function test_remove_free_still_in_progress_counts_only_rooms_already_completed(): void
    {
        DB::table('job_schedules')->where('id', 59935)->update(['status' => 'on_progress']);
        $scope = app(InstallFreeContractRoomScope::class);

        $this->assertSame([1], $scope->trialRemovedRoomIds(self::SQ)->all(), 'Ruangan RF sudah completed: unit sudah dicabut.');

        DB::table('job_schedule_rooms')->where('job_schedule_id', 59935)->update(['status' => 'pending']);
        $this->assertSame([], $scope->trialRemovedRoomIds(self::SQ)->all(), 'RF belum dikerjakan: unit masih di lokasi.');
    }

    private function seedQaCase(): void
    {
        $now = now();
        DB::table('users')->insert(['id' => 28, 'name' => 'QA']);
        DB::table('customers')->insert(['id' => 1, 'name' => 'Customer Testing Indo']);
        DB::table('buildings')->insert(['id' => 1, 'name' => 'Gedung QA', 'building_name' => 'Gedung QA']);
        foreach ([1 => 'Ruang-1', 3 => 'Ruang-3', 6 => 'Ruang-6'] as $id => $name) {
            DB::table('master_rooms')->insert(['id' => $id, 'building_id' => 1, 'room_name' => $name]);
        }
        DB::table('quotations')->insert([
            ['id' => 30576, 'quotation_number' => self::SQ],
            ['id' => 29180, 'quotation_number' => self::OTHER_SQ],
        ]);
        DB::table('quotation_rooms')->insert([
            ['id' => 81004, 'quotation_id' => 30576, 'room_id' => 1, 'room_name' => 'Ruang-1'],
            ['id' => 81005, 'quotation_id' => 30576, 'room_id' => 3, 'room_name' => 'Ruang-3'],
            ['id' => 81007, 'quotation_id' => 30576, 'room_id' => 6, 'room_name' => 'Ruang-6'],
        ]);
        DB::table('contracts')->insert([
            'id' => 21791, 'contract_number' => 'BLP-CA/26-11/0005', 'customer_id' => 1, 'quotation_id' => 30576,
            'contract_status' => 'active', 'first_service_date' => '2026-11-27', 'start_date' => '2026-11-27', 'end_date' => '2027-11-26',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('contract_rooms')->insert([
            ['id' => 73489, 'contract_id' => 21791, 'room_id' => 1],
            ['id' => 73490, 'contract_id' => 21791, 'room_id' => 3],
            ['id' => 73492, 'contract_id' => 21791, 'room_id' => 6],
        ]);
        DB::table('rental_service_frequencies')->insert(['id' => 1, 'name' => 'Monthly', 'frequency_times_per_month' => 1, 'frequency_months' => 1]);
        DB::table('master_rentals')->insert([
            ['id' => 379, 'rental_name' => 'TestUO1b1x', 'rental_type' => 'unit_only', 'service_frequency_id' => 1],
            ['id' => 382, 'rental_name' => 'TestUO1b3x', 'rental_type' => 'unit_only', 'service_frequency_id' => 1],
            ['id' => 387, 'rental_name' => 'TestUR1b2x', 'rental_type' => 'unit_refill', 'service_frequency_id' => 1],
        ]);
        DB::table('contract_rentals')->insert([
            ['id' => 75878, 'contract_id' => 21791, 'room_id' => 1, 'master_rental_id' => 379, 'quantity' => 1],
            ['id' => 75879, 'contract_id' => 21791, 'room_id' => 3, 'master_rental_id' => 382, 'quantity' => 1],
            ['id' => 75882, 'contract_id' => 21791, 'room_id' => 6, 'master_rental_id' => 387, 'quantity' => 1],
        ]);

        DB::table('job_advices')->insert([
            'id' => 27674, 'job_advice_number' => 'ADS-JA/26-11/0001', 'type' => 'install_free', 'status' => 'approved',
            'quotation_id' => 30576, 'customer_id' => 1, 'expected_date' => '2026-11-27',
        ]);
        DB::table('job_advice_rooms')->insert([
            ['id' => 81145, 'job_advice_id' => 27674, 'quotation_room_id' => 81004, 'rental_product_id' => 379, 'room_name' => 'Ruang-1', 'install_job_schedule_id' => 59930, 'remove_job_schedule_id' => 59935],
            ['id' => 81146, 'job_advice_id' => 27674, 'quotation_room_id' => 81005, 'rental_product_id' => 382, 'room_name' => 'Ruang-3', 'install_job_schedule_id' => 59931, 'remove_job_schedule_id' => 59936],
            ['id' => 81149, 'job_advice_id' => 27674, 'quotation_room_id' => 81007, 'rental_product_id' => 387, 'room_name' => 'Ruang-6', 'install_job_schedule_id' => 59933, 'remove_job_schedule_id' => 59934],
        ]);

        $job = fn (int $id, string $number, string $type, string $status, ?int $roomId, string $sq = self::SQ, ?int $ja = 27674) => [
            'id' => $id, 'job_number' => $number, 'type' => $type, 'status' => $status, 'job_advice_id' => $ja,
            'building_id' => 1, 'room_id' => $roomId, 'quotation_number' => $sq, 'schedule_date' => '2026-11-27',
        ];
        DB::table('job_schedules')->insert([
            $job(59930, 'ADS-IF/26-11/0001', 'install_free', 'done_job', 1),
            $job(59931, 'ADS-IF/26-11/0001', 'install_free', 'done_job', 3),
            $job(59933, 'BLP-IF/26-11/0004', 'install_free', 'done_job', 6),
            $job(59934, 'BLP-RF/26-11/0003', 'remove_free', 'cancelled', null),
            $job(59935, 'ADS-RF/26-11/0001', 'remove_free', 'done_job', null),
            $job(59936, 'ADS-RF/26-11/0002', 'remove_free', 'cancelled', null),
            // Trial SQ lain yang unitnya masih aktif di Ruang-1 (data QA memakai ruangan yang sama).
            $job(59848, 'JKT-IF/26-11/0003', 'install_free', 'done_job', 1, self::OTHER_SQ, null),
        ]);
        DB::table('job_schedule_rooms')->insert([
            ['job_schedule_id' => 59930, 'job_advice_room_id' => 81145, 'room_id' => 1, 'room_name' => 'Ruang-1', 'status' => 'completed'],
            ['job_schedule_id' => 59931, 'job_advice_room_id' => 81146, 'room_id' => 3, 'room_name' => 'Ruang-3', 'status' => 'completed'],
            ['job_schedule_id' => 59933, 'job_advice_room_id' => 81149, 'room_id' => 6, 'room_name' => 'Ruang-6', 'status' => 'completed'],
            ['job_schedule_id' => 59934, 'job_advice_room_id' => 81149, 'room_id' => 6, 'room_name' => 'Ruang-6', 'status' => 'pending'],
            ['job_schedule_id' => 59935, 'job_advice_room_id' => 81145, 'room_id' => 1, 'room_name' => 'Ruang-1', 'status' => 'completed'],
            ['job_schedule_id' => 59936, 'job_advice_room_id' => 81146, 'room_id' => 3, 'room_name' => 'Ruang-3', 'status' => 'pending'],
        ]);
        DB::table('unit_on_walls')->insert([
            ['id' => 59, 'customer_id' => 1, 'building_id' => 1, 'room_id' => 1, 'room_name' => 'Ruang-1', 'status' => 'removed', 'install_job_schedule_id' => 59930, 'serial_number' => 'TESTDSP87400A-7'],
            ['id' => 45, 'customer_id' => 1, 'building_id' => 1, 'room_id' => 1, 'room_name' => 'Ruang-1', 'status' => 'active', 'install_job_schedule_id' => 59848, 'serial_number' => 'TESTDSP87400A-OTHER'],
            ['id' => 60, 'customer_id' => 1, 'building_id' => 1, 'room_id' => 3, 'room_name' => 'Ruang-3', 'status' => 'active', 'install_job_schedule_id' => 59931, 'serial_number' => 'TESTDSP87400A-6'],
            ['id' => 58, 'customer_id' => 1, 'building_id' => 1, 'room_id' => 6, 'room_name' => 'Ruang-6', 'status' => 'active', 'install_job_schedule_id' => 59933, 'serial_number' => 'DIS005S2611230010'],
        ]);
    }

    private function createSchema(): void
    {
        $simple = function (string $name, callable $columns, bool $softDeletes = true) {
            Schema::dropIfExists($name);
            Schema::create($name, function (Blueprint $t) use ($columns, $softDeletes) {
                $t->id();
                $columns($t);
                $t->timestamps();
                if ($softDeletes) {
                    $t->softDeletes();
                }
            });
        };

        $simple('users', fn (Blueprint $t) => $t->string('name')->nullable());
        $simple('customers', fn (Blueprint $t) => $t->string('name')->nullable());
        $simple('buildings', function (Blueprint $t) {
            $t->string('name')->nullable();
            $t->string('building_name')->nullable();
            $t->string('kode_pos')->nullable();
            $t->string('postal_code')->nullable();
        });
        $simple('master_rooms', function (Blueprint $t) {
            $t->foreignId('building_id')->nullable();
            $t->string('room_name')->nullable();
        });
        $simple('surveys', fn (Blueprint $t) => $t->foreignId('building_id')->nullable());
        $simple('quotations', function (Blueprint $t) {
            $t->string('quotation_number')->nullable();
            $t->string('quotation_type')->nullable();
            $t->foreignId('existing_contract_id')->nullable();
            $t->foreignId('survey_id')->nullable();
        });
        $simple('quotation_rooms', function (Blueprint $t) {
            $t->foreignId('quotation_id')->nullable();
            $t->foreignId('room_id')->nullable();
            $t->string('room_name')->nullable();
        });
        $simple('contracts', function (Blueprint $t) {
            $t->string('contract_number')->nullable();
            $t->foreignId('customer_id')->nullable();
            $t->foreignId('quotation_id')->nullable();
            $t->string('contract_status')->nullable();
            $t->date('first_service_date')->nullable();
            $t->date('install_date')->nullable();
            $t->date('start_date')->nullable();
            $t->date('end_date')->nullable();
        });
        $simple('contract_rooms', function (Blueprint $t) {
            $t->foreignId('contract_id')->nullable();
            $t->foreignId('room_id')->nullable();
        });
        $simple('rental_service_frequencies', function (Blueprint $t) {
            $t->string('name')->nullable();
            $t->integer('frequency_times_per_month')->nullable();
            $t->integer('frequency_months')->nullable();
        });
        $simple('master_rentals', function (Blueprint $t) {
            $t->string('rental_name')->nullable();
            $t->string('rental_type')->nullable();
            $t->foreignId('service_frequency_id')->nullable();
        });
        $simple('contract_rentals', function (Blueprint $t) {
            $t->foreignId('contract_id')->nullable();
            $t->foreignId('master_rental_id')->nullable();
            $t->foreignId('room_id')->nullable();
            $t->string('rental_alias')->nullable();
            $t->decimal('quantity', 8, 2)->nullable();
        }, false);
        $simple('rental_details', function (Blueprint $t) {
            $t->foreignId('master_rental_id')->nullable();
            $t->foreignId('master_product_id')->nullable();
            $t->foreignId('product_category_id')->nullable();
            $t->foreignId('product_type_id')->nullable();
            $t->decimal('quantity', 8, 2)->nullable();
        });
        $simple('unit_on_walls', function (Blueprint $t) {
            $t->foreignId('customer_id')->nullable();
            $t->foreignId('contract_id')->nullable();
            $t->foreignId('building_id')->nullable();
            $t->foreignId('room_id')->nullable();
            $t->foreignId('serial_number_id')->nullable();
            $t->string('serial_number')->nullable();
            $t->string('status')->nullable();
            $t->string('room_name')->nullable();
            $t->foreignId('install_job_schedule_id')->nullable();
        });
        $simple('job_advices', function (Blueprint $t) {
            foreach (['job_advice_number', 'type', 'company_name', 'status'] as $column) {
                $t->string($column)->nullable();
            }
            foreach (['contract_id', 'quotation_id', 'customer_id', 'request_by', 'submitted_by', 'approved_by', 'created_by', 'updated_by'] as $column) {
                $t->foreignId($column)->nullable();
            }
            $t->dateTime('submitted_at')->nullable();
            $t->dateTime('date_approval')->nullable();
            $t->date('expected_date')->nullable();
            $t->date('first_service_date')->nullable();
            $t->boolean('with_invoicing')->default(false);
            $t->boolean('with_materials')->default(false);
            $t->text('notes')->nullable();
        });
        $simple('job_advice_rooms', function (Blueprint $t) {
            foreach (['job_advice_id', 'contract_room_id', 'quotation_room_id', 'contract_rental_id', 'rental_product_id',
                'install_job_schedule_id', 'service_job_schedule_id', 'remove_job_schedule_id', 'existing_unit_on_wall_id',
                'created_by', 'updated_by'] as $column) {
                $t->foreignId($column)->nullable();
            }
            $t->string('room_name')->nullable();
            $t->string('rental_name')->nullable();
            $t->integer('quantity')->nullable();
            $t->decimal('rental_specification_ml', 10, 2)->nullable();
            $t->boolean('rental_has_installation')->default(false);
            $t->boolean('rental_has_service')->default(false);
            $t->boolean('unit_already_installed')->default(false);
            $t->string('status')->nullable();
            $t->text('notes')->nullable();
        });
        $simple('job_schedules', function (Blueprint $t) {
            foreach (['job_number', 'type', 'status', 'building_name', 'room_name', 'company_name', 'contract_number',
                'quotation_number', 'service_period_type', 'reference_number', 'postal_code', 'district', 'sub_district'] as $column) {
                $t->string($column)->nullable();
            }
            foreach (['job_advice_id', 'building_id', 'room_id', 'created_by', 'updated_by'] as $column) {
                $t->foreignId($column)->nullable();
            }
            $t->date('schedule_date')->nullable();
            $t->date('expected_date')->nullable();
            $t->date('ba_date')->nullable();
            $t->integer('period')->nullable();
            $t->integer('service_frequency')->nullable();
            $t->text('internal_notes')->nullable();
            $t->boolean('material_checked')->default(false);
            $t->dateTime('material_checked_at')->nullable();
        });
        $simple('job_schedule_rooms', function (Blueprint $t) {
            foreach (['job_schedule_id', 'job_advice_room_id', 'room_id', 'created_by', 'updated_by'] as $column) {
                $t->foreignId($column)->nullable();
            }
            $t->string('room_name')->nullable();
            $t->string('status')->nullable();
            $t->string('material_return_status')->nullable();
            $t->text('notes')->nullable();
        });
        $simple('job_schedule_room_rentals', function (Blueprint $t) {
            $t->foreignId('job_schedule_room_id')->nullable();
            $t->foreignId('job_advice_room_id')->nullable();
            $t->boolean('is_primary')->default(false);
        });
    }
}
