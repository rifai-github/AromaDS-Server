<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobAssignMaterialIssueController;
use App\Http\Controllers\Operational\JobScheduleController;
use App\Models\Contract;
use App\Models\JobAdvice;
use App\Models\JobSchedule;
use App\Models\User;
use App\Services\Finance\InvoiceGenerationService;
use App\Services\Operational\JobMaterialCompletionService;
use App\Services\Warehouse\InventoryIssuingService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

/**
 * QA batch 4 (27 Nov 2026): JA ADS-JA/26-11/0007 (id 27680, kontrak BLP-CA/26-11/0006) Ruang-5
 * memegang TestRO2b1x (refill_only, 2 bulan 1x) dan TestVG1b1x (unit_refill, 1 bulan 1x). Setelah
 * service pertama BLP-CSR/26-11/0006 selesai, hanya 5 baris (ritme TestRO2b1x) yang dibuat.
 * Seharusnya 16 baris: TestRO2b1x 5 + TestVG1b1x 11, berurutan per bulan.
 */
class MultiFrequencyRoomServiceSeriesTest extends TestCase
{
    private const JA = 27680;
    private const FIRST_SERVICE = 59998;
    private const JAR_RO = 81162;
    private const JAR_VG = 81163;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->foreignId('update_by_1')->nullable();
            $table->timestamp('update_at_1')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        foreach (['roles', 'permissions'] as $lookup) {
            Schema::create($lookup, function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        foreach (['user_roles' => 'role_id', 'role_permissions' => 'permission_id', 'user_permission' => 'permission_id'] as $pivot => $column) {
            Schema::create($pivot, function (Blueprint $table) use ($pivot, $column) {
                $table->id();
                $table->foreignId($pivot === 'role_permissions' ? 'role_id' : 'user_id')->nullable();
                $table->foreignId($column)->nullable();
                $table->timestamps();
            });
        }

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->nullable();
            $table->foreignId('quotation_id')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->string('building_name')->nullable();
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

        Schema::create('rental_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_rental_id')->nullable();
            $table->foreignId('product_category_id')->nullable();
            $table->foreignId('product_type_id')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_advices', function (Blueprint $table) {
            $table->id();
            $table->string('job_advice_number')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->string('company_name')->nullable();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('quotation_id')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->date('expected_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_advice_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_advice_id')->nullable();
            $table->foreignId('contract_room_id')->nullable();
            $table->foreignId('rental_product_id')->nullable();
            $table->string('room_name')->nullable();
            $table->string('rental_name')->nullable();
            $table->foreignId('install_job_schedule_id')->nullable();
            $table->foreignId('service_job_schedule_id')->nullable();
            $table->boolean('rental_has_service')->default(false);
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
            $table->date('ba_date')->nullable();
            $table->string('period')->nullable();
            $table->integer('service_frequency')->nullable();
            $table->string('service_period_type')->nullable();
            $table->integer('service_interval_days')->nullable();
            $table->text('internal_notes')->nullable();
            $table->string('reference_number')->nullable();
            $table->boolean('material_checked')->default(false);
            $table->timestamp('material_checked_at')->nullable();
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

        Schema::create('job_assign_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        User::create(['id' => 1, 'name' => 'Admin']);
        Auth::login(User::findOrFail(1));

        $this->seedQaRuang5();
    }

    protected function tearDown(): void
    {
        foreach ([
            'job_assign_schedules', 'job_schedule_room_rentals', 'job_schedule_rooms', 'job_schedules',
            'job_advice_rooms', 'job_advices', 'rental_details', 'master_rentals', 'rental_service_frequencies',
            'contract_rooms', 'master_rooms', 'buildings', 'quotations', 'contracts',
            'user_permission', 'role_permissions', 'user_roles', 'permissions', 'roles', 'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_room_with_two_frequencies_gets_one_service_series_per_rental(): void
    {
        $this->generateFollowUps();

        $generated = $this->generatedJobs();
        $this->assertCount(16, $generated, 'TestRO2b1x 5 + TestVG1b1x 11');

        $ro = $generated->filter(fn ($job) => $this->linkedRentals($job) === [self::JAR_RO])->values();
        $vg = $generated->filter(fn ($job) => $this->linkedRentals($job) === [self::JAR_VG])->values();

        $this->assertSame(range(2, 6), $ro->pluck('period')->map(fn ($p) => (int) $p)->sort()->values()->all());
        $this->assertSame(range(2, 12), $vg->pluck('period')->map(fn ($p) => (int) $p)->sort()->values()->all());

        $this->assertSame(
            ['2027-01-27', '2027-03-27', '2027-05-27', '2027-07-27', '2027-09-27'],
            $ro->sortBy('period')->map(fn ($job) => $job->schedule_date->format('Y-m-d'))->values()->all()
        );
        $this->assertSame(
            ['2026-12-27', '2027-01-27', '2027-02-27', '2027-03-27', '2027-04-27', '2027-05-27', '2027-06-27', '2027-07-27', '2027-08-27', '2027-09-27', '2027-10-27'],
            $vg->sortBy('period')->map(fn ($job) => $job->schedule_date->format('Y-m-d'))->values()->all()
        );

        // Setiap job hanya membawa rental miliknya, juga di job_schedule_rooms.job_advice_room_id.
        foreach ($ro as $job) {
            $this->assertSame([self::JAR_RO], $job->jobScheduleRooms->pluck('job_advice_room_id')->map(fn ($id) => (int) $id)->all());
            $this->assertSame(60, (int) $job->service_interval_days);
            $this->assertSame('2 Bln 1X', $job->service_period_type);
        }
        foreach ($vg as $job) {
            $this->assertSame([self::JAR_VG], $job->jobScheduleRooms->pluck('job_advice_room_id')->map(fn ($id) => (int) $id)->all());
            $this->assertSame(30, (int) $job->service_interval_days);
            $this->assertSame('1 Bln 1X', $job->service_period_type);
            $this->assertSame('service', $job->type);
            $this->assertSame('Service', $job->display_type);
        }

        // Urutan pembuatan mengikuti bulan: dua rental di bulan yang sama berdampingan.
        $datesInIdOrder = $generated->sortBy('id')->map(fn ($job) => $job->schedule_date->format('Y-m-d'))->values()->all();
        $sorted = $datesInIdOrder;
        sort($sorted);
        $this->assertSame($sorted, $datesInIdOrder);
    }

    public function test_generation_is_idempotent(): void
    {
        $this->generateFollowUps();
        $this->generateFollowUps();

        $this->assertCount(16, $this->generatedJobs());
    }

    /**
     * Klien 28 Nov 2026: "2 job dengan 1 kali kunjungan". Bulan di mana kedua rental jatuh tempo
     * (Jan, Mar, Mei, Jul, Sep 2027) = 1 baris Job View / 1 batch Material Assign berisi 2 job;
     * bulan lain (hanya TestVG1b1x) tetap kunjungan sendiri.
     */
    public function test_same_month_jobs_of_both_rentals_form_one_visit_other_months_stay_separate(): void
    {
        $this->generateFollowUps();
        $generated = $this->generatedJobs();
        $job = fn (int $jaRoomId, int $period) => $generated->first(fn ($candidate) => $this->linkedRentals($candidate) === [$jaRoomId] && (int) $candidate->period === $period);

        $roP2 = $job(self::JAR_RO, 2);   // 2027-01-27
        $vgP2 = $job(self::JAR_VG, 2);   // 2026-12-27
        $vgP3 = $job(self::JAR_VG, 3);   // 2027-01-27
        $vgP4 = $job(self::JAR_VG, 4);   // 2027-02-27
        $roP6 = $job(self::JAR_RO, 6);   // 2027-09-27
        $vgP11 = $job(self::JAR_VG, 11); // 2027-09-27

        // Tanggal kedua seri persis sama di bulan yang sama (dihitung dari tanggal service pertama).
        $this->assertEquals($roP2->schedule_date, $vgP3->schedule_date);
        $this->assertEquals($roP6->schedule_date, $vgP11->schedule_date);

        foreach (['buildJobGroupingKey', 'materialAssignBatchKey'] as $keyMethod) {
            $this->assertSame($this->controllerCall($keyMethod, $roP2), $this->controllerCall($keyMethod, $vgP3), $keyMethod);
            $this->assertSame($this->controllerCall($keyMethod, $roP6), $this->controllerCall($keyMethod, $vgP11), $keyMethod);
            $this->assertNotSame($this->controllerCall($keyMethod, $roP2), $this->controllerCall($keyMethod, $vgP2), $keyMethod);
            $this->assertNotSame($this->controllerCall($keyMethod, $roP2), $this->controllerCall($keyMethod, $vgP4), $keyMethod);
        }

        // SQL Job View: 11 kunjungan (Des 2026 .. Okt 2027) + service pertama.
        $this->assertCount(12, $this->jobViewRepresentatives());

        // Saudara job (Material Completion, Submit Issue, Inventory Issuing) = kunjungan yang sama.
        $expected = [(int) $roP2->id, (int) $vgP3->id];
        sort($expected);
        $ids = fn ($collection) => $collection->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $this->assertSame($expected, $ids($this->callProtected(app(JobMaterialCompletionService::class), 'resolveRelatedJobs', $roP2)));
        $this->assertSame($expected, $ids($this->callProtected(app(JobAssignMaterialIssueController::class), 'resolveSubmitIssueSiblingJobs', $vgP3)));
        $this->assertSame($expected, $ids($this->callProtected(app(InventoryIssuingService::class), 'expandGroupedJobSchedules', collect([$roP2]))));
        $this->assertSame([(int) $vgP2->id], $ids($this->callProtected(app(JobMaterialCompletionService::class), 'resolveRelatedJobs', $vgP2)));

        // Setelah bernomor sama (satu kunjungan), tetap satu baris Job View.
        DB::table('job_schedules')->whereIn('id', $expected)->update(['job_number' => 'BLP-CSR/27-01/0001', 'status' => 'assign_material']);
        $this->assertCount(12, $this->jobViewRepresentatives());
        $this->assertSame(
            $this->controllerCall('buildJobGroupingKey', JobSchedule::findOrFail($roP2->id)),
            $this->controllerCall('buildJobGroupingKey', JobSchedule::findOrFail($vgP3->id))
        );
    }

    public function test_single_series_and_legacy_rows_still_group_by_period(): void
    {
        $make = function (array $attributes): JobSchedule {
            $job = new JobSchedule();
            $job->forceFill(array_merge(['job_advice_id' => 1, 'building_id' => 1, 'type' => 'service', 'job_number' => null, 'service_interval_days' => null], $attributes));

            return $job;
        };

        // Data lama (NULL) dan seri bulanan (30) periode sama = kunjungan sama; beda periode = beda.
        $legacyP3 = $make(['period' => '3']);
        $monthlyP3 = $make(['period' => '3', 'service_interval_days' => 30]);
        $monthlyP4 = $make(['period' => '4', 'service_interval_days' => 30]);
        $twoMonthlyP2 = $make(['period' => '2', 'service_interval_days' => 60]);
        $twoMonthlyP3 = $make(['period' => '3', 'service_interval_days' => 60]);
        $install = $make(['type' => 'install', 'period' => null, 'service_interval_days' => 60]);
        // Job Check Unit Only (material_checked) tetap terpisah dari CSR di kunjungan yang sama.
        $check = $make(['period' => '3', 'material_checked' => true]);

        $this->assertSame('visit:60', $legacyP3->serviceVisitKey());
        $this->assertSame('visit:60', $monthlyP3->serviceVisitKey());
        $this->assertSame('visit:60', $twoMonthlyP2->serviceVisitKey());
        $this->assertSame('visit:120', $twoMonthlyP3->serviceVisitKey());
        $this->assertSame('period:no-period', $install->serviceVisitKey());

        foreach (['buildJobGroupingKey', 'materialAssignBatchKey'] as $keyMethod) {
            $this->assertSame($this->controllerCall($keyMethod, $legacyP3), $this->controllerCall($keyMethod, $monthlyP3), $keyMethod);
            $this->assertNotSame($this->controllerCall($keyMethod, $monthlyP3), $this->controllerCall($keyMethod, $monthlyP4), $keyMethod);
            $this->assertNotSame($this->controllerCall($keyMethod, $monthlyP3), $this->controllerCall($keyMethod, $twoMonthlyP3), $keyMethod);
            $this->assertNotSame($this->controllerCall($keyMethod, $legacyP3), $this->controllerCall($keyMethod, $check), $keyMethod);
        }

        // Dua ruangan satu periode dengan tanggal sedikit berbeda tetap satu batch Material Assign.
        $roomA = $make(['period' => '2', 'service_interval_days' => 30, 'schedule_date' => '2027-01-27']);
        $roomB = $make(['period' => '2', 'service_interval_days' => 30, 'schedule_date' => '2027-02-03']);
        $this->assertSame($this->controllerCall('materialAssignBatchKey', $roomA), $this->controllerCall('materialAssignBatchKey', $roomB));

        // Bernomor: tetap dikelompokkan per nomor JS.
        $numberedA = $make(['period' => '2', 'job_number' => 'BLP-CSR/26-11/0001', 'material_checked' => true]);
        $numberedB = $make(['period' => '2', 'job_number' => 'BLP-CSR/26-11/0001']);
        $this->assertSame($this->controllerCall('buildJobGroupingKey', $numberedA), $this->controllerCall('buildJobGroupingKey', $numberedB));
    }

    public function test_invoice_period_follows_each_rental_series_month(): void
    {
        $this->generateFollowUps();
        $service = app(InvoiceGenerationService::class);
        $contract = new Contract(['payment_terms' => 30]);
        $generated = $this->generatedJobs();

        foreach ($generated as $job) {
            $months = $this->linkedRentals($job) === [self::JAR_RO] ? 2 : 1;
            $expected = ((int) $job->period - 1) * $months + 1;

            $this->assertSame($expected, $this->callProtected($service, 'computeInvoicePeriodForJob', $job, $contract), "job period {$job->period} interval {$months}");
            $this->assertEquals($expected, $job->invoice_period);
            // Bulan ke-n sejak service pertama = bulan tanggal jadwal job.
            $this->assertSame($expected - 1, (int) Carbon::parse('2026-11-27')->diffInMonths($job->schedule_date));
        }
    }

    public function test_generator_does_not_double_schedule_rental_still_linked_to_legacy_rows(): void
    {
        $this->seedLegacyBuggyFollowUps();

        $this->generateFollowUps();

        // TestRO2b1x sudah lengkap; TestVG1b1x masih menempel di job lama → dilewati, tunggu repair.
        $this->assertCount(5, $this->generatedJobs());
    }

    public function test_repair_command_dry_run_changes_nothing(): void
    {
        $this->seedLegacyBuggyFollowUps();

        $this->artisan('operational:repair-multi-frequency-room-services', ['--contract' => ['BLP-CA/26-11/0006']])
            ->expectsOutputToContain('DRY RUN')
            ->assertSuccessful();

        $this->assertCount(5, $this->generatedJobs());
        $this->assertSame(10, DB::table('job_schedule_room_rentals')->whereIn('job_schedule_room_id', DB::table('job_schedule_rooms')->where('job_schedule_id', '>=', 60047)->pluck('id'))->whereNull('deleted_at')->count());
    }

    public function test_repair_command_detaches_foreign_rental_and_generates_missing_series(): void
    {
        $this->seedLegacyBuggyFollowUps();

        $this->artisan('operational:repair-multi-frequency-room-services', ['--contract' => ['21792'], '--apply' => true])
            ->assertSuccessful();

        $generated = $this->generatedJobs();
        $this->assertCount(16, $generated);

        $legacy = $generated->whereIn('id', range(60047, 60051));
        $this->assertCount(5, $legacy);
        foreach ($legacy as $job) {
            $this->assertSame([self::JAR_RO], $this->linkedRentals($job));
            $this->assertSame(60, (int) $job->service_interval_days);
        }

        $vg = $generated->filter(fn ($job) => $this->linkedRentals($job) === [self::JAR_VG]);
        $this->assertSame(range(2, 12), $vg->pluck('period')->map(fn ($p) => (int) $p)->sort()->values()->all());
        // created_at disamakan dengan baris lama supaya Job View mengurutkan per bulan.
        foreach ($vg as $job) {
            $this->assertSame('2026-11-27 10:00:00', $job->created_at->format('Y-m-d H:i:s'));
        }

        // Jalan ulang tidak membuat apa-apa lagi.
        $this->artisan('operational:repair-multi-frequency-room-services', ['--contract' => ['BLP-CA/26-11/0006'], '--apply' => true])
            ->assertSuccessful();
        $this->assertCount(16, $this->generatedJobs());
    }

    public function test_repair_command_leaves_processed_legacy_rows_for_manual_handling(): void
    {
        $this->seedLegacyBuggyFollowUps();
        DB::table('job_schedules')->where('id', 60047)->update(['job_number' => 'BLP-CSR/27-01/0001', 'status' => 'assign_material']);

        $this->artisan('operational:repair-multi-frequency-room-services', ['--contract' => ['BLP-CA/26-11/0006'], '--apply' => true])
            ->expectsOutputToContain('MANUAL')
            ->assertSuccessful();

        $this->assertCount(5, $this->generatedJobs());
        $this->assertSame([self::JAR_RO, self::JAR_VG], $this->linkedRentals(JobSchedule::with('jobScheduleRooms.rentals')->findOrFail(60048)));
    }

    public function test_repair_command_requires_a_target(): void
    {
        $this->artisan('operational:repair-multi-frequency-room-services')->assertFailed();
    }

    // ---------------------------------------------------------------- helpers

    private function jobViewRepresentatives()
    {
        [$visitPeriodSql, $visitOffsetSql] = JobSchedule::serviceVisitGroupingSql(true);

        return DB::table('job_schedules')
            ->selectRaw('MIN(id) as id')
            ->whereNotNull('job_advice_id')
            ->whereNull('deleted_at')
            ->groupBy('job_advice_id', 'building_id', 'type', 'job_number')
            ->groupByRaw($visitPeriodSql)
            ->groupByRaw($visitOffsetSql)
            ->groupByRaw("CASE WHEN (job_number IS NULL OR job_number = '') AND LOWER(COALESCE(type, '')) IN ('service', 'service_first', 'service_routine') AND COALESCE(material_checked, 0) = 1 THEN 1 ELSE 0 END")
            ->pluck('id');
    }

    private function generateFollowUps(): void
    {
        $method = new ReflectionMethod(JobScheduleController::class, 'generateFollowUpServiceSchedules');
        $method->setAccessible(true);
        $method->invoke(new JobScheduleController(), JobSchedule::findOrFail(self::FIRST_SERVICE), JobAdvice::findOrFail(self::JA));
    }

    private function generatedJobs()
    {
        return JobSchedule::with('jobScheduleRooms.rentals')
            ->where('job_advice_id', self::JA)
            ->where('id', '!=', self::FIRST_SERVICE)
            ->whereIn('type', ['service', 'service_first', 'service_routine'])
            ->orderBy('id')
            ->get();
    }

    private function linkedRentals(JobSchedule $job): array
    {
        return $job->jobScheduleRooms
            ->flatMap(fn ($room) => $room->rentals->pluck('job_advice_room_id'))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function controllerCall(string $method, JobSchedule $job): string
    {
        return $this->callProtected(new JobScheduleController(), $method, $job);
    }

    private function callProtected(object $target, string $method, ...$args)
    {
        $reflection = new ReflectionMethod($target, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($target, ...$args);
    }

    private function seedQaRuang5(): void
    {
        $now = now();
        DB::table('contracts')->insert(['id' => 21792, 'contract_number' => 'BLP-CA/26-11/0006', 'start_date' => '2026-11-27', 'end_date' => '2027-11-26', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('buildings')->insert(['id' => 20027, 'building_name' => 'Gedung Testing', 'name' => 'Gedung Testing', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('master_rooms')->insert(['id' => 45028, 'building_id' => 20027, 'room_name' => 'Ruang-5', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('contract_rooms')->insert(['id' => 73496, 'contract_id' => 21792, 'room_id' => 45028, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('rental_service_frequencies')->insert([
            ['id' => 14, 'name' => '1 Bln 1X', 'frequency_times_per_month' => 1, 'frequency_months' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 17, 'name' => '2 Bln 1X', 'frequency_times_per_month' => 1, 'frequency_months' => 2, 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('master_rentals')->insert([
            ['id' => 385, 'rental_name' => 'TestRO2b1x', 'rental_type' => 'refill_only', 'service_frequency_id' => 17, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 386, 'rental_name' => 'TestVG1b1x', 'rental_type' => 'unit_refill', 'service_frequency_id' => 14, 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('job_advices')->insert(['id' => self::JA, 'job_advice_number' => 'ADS-JA/26-11/0007', 'type' => 'Install', 'company_name' => 'Customer Testing Indo', 'contract_id' => 21792, 'expected_date' => '2026-11-27', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('job_advice_rooms')->insert([
            ['id' => self::JAR_RO, 'job_advice_id' => self::JA, 'contract_room_id' => 73496, 'rental_product_id' => 385, 'room_name' => 'Ruang-5', 'rental_name' => 'TestRO2b1x', 'service_job_schedule_id' => self::FIRST_SERVICE, 'rental_has_service' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => self::JAR_VG, 'job_advice_id' => self::JA, 'contract_room_id' => 73496, 'rental_product_id' => 386, 'room_name' => 'Ruang-5', 'rental_name' => 'TestVG1b1x', 'service_job_schedule_id' => self::FIRST_SERVICE, 'rental_has_service' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('job_schedules')->insert([
            'id' => self::FIRST_SERVICE, 'job_number' => 'BLP-CSR/26-11/0006', 'type' => 'service_first', 'status' => 'done_job',
            'job_advice_id' => self::JA, 'building_id' => 20027, 'room_id' => 45028, 'company_name' => 'Customer Testing Indo',
            'contract_number' => 'BLP-CA/26-11/0006', 'schedule_date' => '2026-11-27', 'expected_date' => '2026-11-27', 'period' => '1',
            'service_frequency' => 1, 'service_period_type' => '2 Bln 1X', 'material_checked' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('job_schedule_rooms')->insert([
            ['id' => 164496, 'job_schedule_id' => self::FIRST_SERVICE, 'job_advice_room_id' => self::JAR_RO, 'room_name' => 'Ruang-5', 'room_id' => 45028, 'status' => 'completed', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 164497, 'job_schedule_id' => self::FIRST_SERVICE, 'job_advice_room_id' => self::JAR_VG, 'room_name' => 'Ruang-5', 'room_id' => 45028, 'status' => 'completed', 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('job_schedule_room_rentals')->insert([
            ['job_schedule_room_id' => 164496, 'job_advice_room_id' => self::JAR_RO, 'is_primary' => true, 'created_at' => $now, 'updated_at' => $now],
            ['job_schedule_room_id' => 164496, 'job_advice_room_id' => self::JAR_VG, 'is_primary' => false, 'created_at' => $now, 'updated_at' => $now],
            ['job_schedule_room_id' => 164497, 'job_advice_room_id' => self::JAR_VG, 'is_primary' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    /** Keadaan prod 27 Nov: job 60047-60051 berritme 2 bulan, menautkan kedua rental. */
    private function seedLegacyBuggyFollowUps(): void
    {
        $dates = [2 => '2027-01-27', 3 => '2027-03-27', 4 => '2027-05-27', 5 => '2027-07-27', 6 => '2027-09-27'];
        $id = 60047;
        foreach ($dates as $period => $date) {
            DB::table('job_schedules')->insert([
                'id' => $id, 'job_number' => null, 'type' => 'service', 'status' => 'scheduled', 'job_advice_id' => self::JA,
                'building_id' => 20027, 'company_name' => 'Customer Testing Indo', 'contract_number' => 'BLP-CA/26-11/0006',
                'schedule_date' => $date, 'expected_date' => $date, 'period' => (string) $period, 'service_frequency' => 1,
                'service_period_type' => '2 Bln 1X', 'material_checked' => false,
                'created_at' => '2026-11-27 10:00:00', 'updated_at' => '2026-11-27 10:00:00',
            ]);
            DB::table('job_schedule_rooms')->insert([
                'id' => $id + 100000, 'job_schedule_id' => $id, 'job_advice_room_id' => self::JAR_RO, 'room_name' => 'Ruang-5',
                'room_id' => 45028, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('job_schedule_room_rentals')->insert([
                ['job_schedule_room_id' => $id + 100000, 'job_advice_room_id' => self::JAR_RO, 'is_primary' => true, 'created_at' => now(), 'updated_at' => now()],
                ['job_schedule_room_id' => $id + 100000, 'job_advice_room_id' => self::JAR_VG, 'is_primary' => false, 'created_at' => now(), 'updated_at' => now()],
            ]);
            $id++;
        }
    }
}
