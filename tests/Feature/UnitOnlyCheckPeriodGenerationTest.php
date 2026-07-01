<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobScheduleController;
use App\Models\JobAdvice;
use App\Models\JobAdviceRoom;
use App\Models\JobSchedule;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class UnitOnlyCheckPeriodGenerationTest extends TestCase
{
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

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('role_id')->nullable();
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->nullable();
            $table->foreignId('permission_id')->nullable();
            $table->timestamps();
        });

        Schema::create('user_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('permission_id')->nullable();
            $table->timestamps();
        });

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

        Schema::create('unit_on_walls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->foreignId('serial_number_id')->nullable();
            $table->string('status')->nullable();
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
            $table->foreignId('customer_id')->nullable();
            $table->date('expected_date')->nullable();
            $table->date('first_service_date')->nullable();
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
            $table->boolean('unit_already_installed')->default(false);
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
            $table->date('assign_date')->nullable();
            $table->date('ba_date')->nullable();
            $table->integer('period')->nullable();
            $table->integer('service_frequency')->nullable();
            $table->string('service_period_type')->nullable();
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

        Schema::create('job_assign_material_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_assign_schedule_id')->nullable();
            $table->foreignId('material_issue_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        User::create(['id' => 1, 'name' => 'Admin']);
        Auth::login(User::findOrFail(1));
    }

    protected function tearDown(): void
    {
        foreach ([
            'job_assign_material_issues',
            'job_assign_schedules',
            'job_schedule_room_rentals',
            'job_schedule_rooms',
            'job_schedules',
            'job_advice_rooms',
            'job_advices',
            'rental_details',
            'master_rentals',
            'rental_service_frequencies',
            'contract_rooms',
            'master_rooms',
            'buildings',
            'unit_on_walls',
            'quotations',
            'contracts',
            'user_permission',
            'role_permissions',
            'user_roles',
            'permissions',
            'roles',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_existing_first_check_does_not_block_missing_unit_only_check_periods(): void
    {
        $this->seedUnitOnlyInstallWithExistingFirstCheck();

        $method = new ReflectionMethod(JobScheduleController::class, 'generateUnitOnlyCheckSchedulesAfterInstall');
        $method->setAccessible(true);
        $created = $method->invoke(
            new JobScheduleController(),
            JobSchedule::findOrFail(10),
            JobAdvice::findOrFail(1)
        );

        $this->assertCount(11, $created);
        $this->assertSame(range(1, 12), JobSchedule::where('job_advice_id', 1)
            ->whereIn('type', ['service_first', 'service_routine'])
            ->orderBy('period')
            ->pluck('period')
            ->map(fn ($period) => (int) $period)
            ->values()
            ->all());
        $this->assertDatabaseCount('job_schedule_room_rentals', 12);
    }

    public function test_done_install_without_completed_room_status_still_generates_unit_only_checks(): void
    {
        $this->seedUnitOnlyInstallWithExistingFirstCheck();

        DB::table('job_schedule_room_rentals')->where('job_schedule_room_id', 20)->delete();
        DB::table('job_schedule_rooms')->where('job_schedule_id', 20)->delete();
        DB::table('job_schedules')->where('id', 20)->delete();
        DB::table('job_schedule_rooms')->where('job_schedule_id', 10)->update([
            'status' => 'pending',
        ]);

        $method = new ReflectionMethod(JobScheduleController::class, 'generateUnitOnlyCheckSchedulesAfterInstall');
        $method->setAccessible(true);
        $created = $method->invoke(
            new JobScheduleController(),
            JobSchedule::findOrFail(10),
            JobAdvice::findOrFail(1)
        );

        $this->assertCount(12, $created);
        $this->assertSame(range(1, 12), JobSchedule::where('job_advice_id', 1)
            ->whereIn('type', ['service_first', 'service_routine'])
            ->orderBy('period')
            ->pluck('period')
            ->map(fn ($period) => (int) $period)
            ->values()
            ->all());
    }

    public function test_unit_only_generator_counts_existing_check_room_without_rental_pivot(): void
    {
        $this->seedUnitOnlyInstallWithExistingFirstCheck();

        DB::table('job_schedules')->insert([
            'id' => 21,
            'job_number' => null,
            'type' => 'service_routine',
            'status' => 'scheduled',
            'job_advice_id' => 1,
            'building_id' => 1,
            'building_name' => 'Gedung KGI',
            'room_id' => 1,
            'room_name' => 'VIP Room',
            'company_name' => 'KGI',
            'contract_number' => 'BDG-CA/26-05/0006',
            'schedule_date' => '2026-06-02',
            'expected_date' => '2026-06-02',
            'period' => 2,
            'service_frequency' => 1,
            'service_period_type' => 'Monthly',
            'material_checked' => true,
            'material_checked_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('job_schedule_rooms')->insert([
            'id' => 21,
            'job_schedule_id' => 21,
            'job_advice_room_id' => 1,
            'room_name' => 'VIP Room',
            'room_id' => 1,
            'status' => 'pending',
            'material_return_status' => 'not_required',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $method = new ReflectionMethod(JobScheduleController::class, 'generateUnitOnlyCheckSchedulesAfterInstall');
        $method->setAccessible(true);
        $created = $method->invoke(
            new JobScheduleController(),
            JobSchedule::findOrFail(10),
            JobAdvice::findOrFail(1)
        );

        $this->assertCount(10, $created);
        $this->assertSame(range(1, 12), JobSchedule::where('job_advice_id', 1)
            ->whereIn('type', ['service_first', 'service_routine'])
            ->orderBy('period')
            ->pluck('period')
            ->map(fn ($period) => (int) $period)
            ->values()
            ->all());
        $this->assertSame(1, JobSchedule::where('job_advice_id', 1)->where('period', 2)->count());
    }

    public function test_done_refill_first_service_without_completed_room_status_still_generates_next_services(): void
    {
        $this->seedRefillOnlyFirstService();

        $method = new ReflectionMethod(JobScheduleController::class, 'generateAllRemainingServices');
        $method->setAccessible(true);
        $method->invoke(
            new JobScheduleController(),
            JobSchedule::findOrFail(30),
            JobAdvice::findOrFail(2)
        );

        $this->assertSame(range(1, 12), JobSchedule::where('job_advice_id', 2)
            ->whereIn('type', ['service', 'service_first', 'service_routine'])
            ->orderBy('period')
            ->pluck('period')
            ->map(fn ($period) => (int) $period)
            ->values()
            ->all());
    }

    public function test_quarterly_rental_generates_4_services_3_months_apart(): void
    {
        // Regression test: rental_service_frequencies.name is a free-text Indonesian
        // label (e.g. "Freq 1x per 3 bulan"), not 'quarterly'/'monthly'/etc. A rental
        // with frequency_months=3 over a 12-month contract must generate exactly 4
        // services spaced 3 months apart - not 12 monthly ones.
        $this->seedQuarterlyRentalFirstService();

        $method = new ReflectionMethod(JobScheduleController::class, 'generateAllRemainingServices');
        $method->setAccessible(true);
        $method->invoke(
            new JobScheduleController(),
            JobSchedule::findOrFail(40),
            JobAdvice::findOrFail(5)
        );

        $services = JobSchedule::where('job_advice_id', 5)
            ->whereIn('type', ['service', 'service_first', 'service_routine'])
            ->orderBy('period')
            ->get(['period', 'schedule_date']);

        $this->assertSame([1, 2, 3, 4], $services->pluck('period')->map(fn ($p) => (int) $p)->values()->all());

        $dates = $services->pluck('schedule_date')->map(fn ($d) => \Carbon\Carbon::parse($d));
        $this->assertSame(3, (int) $dates[0]->diffInMonths($dates[1]));
        $this->assertSame(3, (int) $dates[1]->diffInMonths($dates[2]));
        $this->assertSame(3, (int) $dates[2]->diffInMonths($dates[3]));
    }

    public function test_done_refill_routine_first_service_fans_out_remaining_services(): void
    {
        $this->seedRefillOnlyFirstService();

        // A standalone Service Job Advice stores the first refill service as service_routine.
        DB::table('job_schedules')->where('id', 30)->update(['type' => 'service_routine']);

        $method = new ReflectionMethod(JobScheduleController::class, 'generateFollowUpServiceSchedules');
        $method->setAccessible(true);
        $method->invoke(
            new JobScheduleController(),
            JobSchedule::findOrFail(30),
            JobAdvice::findOrFail(2)
        );

        $this->assertSame(range(1, 12), JobSchedule::where('job_advice_id', 2)
            ->whereIn('type', ['service', 'service_first', 'service_routine'])
            ->orderBy('period')
            ->pluck('period')
            ->map(fn ($period) => (int) $period)
            ->values()
            ->all());
    }

    public function test_done_unit_only_routine_first_check_fans_out_checks_without_duplicate(): void
    {
        $this->seedUnitOnlyInstallWithExistingFirstCheck();

        // A standalone unit-only Service Job Advice has no install job, only the first check,
        // stored as service_routine (period 1, material already checked).
        DB::table('job_schedule_room_rentals')->where('job_schedule_room_id', 10)->delete();
        DB::table('job_schedule_rooms')->where('job_schedule_id', 10)->delete();
        DB::table('job_schedules')->where('id', 10)->delete();
        DB::table('job_schedules')->where('id', 20)->update([
            'type' => 'service_routine',
            'status' => 'done_job',
        ]);

        $method = new ReflectionMethod(JobScheduleController::class, 'generateFollowUpServiceSchedules');
        $method->setAccessible(true);
        $method->invoke(
            new JobScheduleController(),
            JobSchedule::findOrFail(20),
            JobAdvice::findOrFail(1)
        );

        // Periods 1..12 exist exactly once each — period 1 (the completed check) is not duplicated.
        $this->assertSame(range(1, 12), JobSchedule::where('job_advice_id', 1)
            ->whereIn('type', ['service', 'service_first', 'service_routine'])
            ->orderBy('period')
            ->pluck('period')
            ->map(fn ($period) => (int) $period)
            ->values()
            ->all());
    }

    public function test_mixed_unit_only_and_refill_room_generates_csr_services_independently_from_ir_checks(): void
    {
        $this->seedMixedUnitOnlyAndRefillRoom();

        $controller = new JobScheduleController();

        $method = new ReflectionMethod(JobScheduleController::class, 'generateAllRemainingServices');
        $method->setAccessible(true);
        $method->invoke(
            $controller,
            JobSchedule::findOrFail(50),
            JobAdvice::findOrFail(3)
        );

        $periodTwoJobs = JobSchedule::where('job_advice_id', 3)
            ->whereIn('type', ['service', 'service_first', 'service_routine'])
            ->where('period', 2)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $periodTwoJobs);

        $csrService = $periodTwoJobs->firstWhere('type', 'service');
        $this->assertNotNull($csrService);
        $this->assertNull($csrService->job_number);
        $this->assertSame('scheduled', $csrService->status);

        $linkedJobAdviceRoomIds = DB::table('job_schedule_room_rentals')
            ->join('job_schedule_rooms', 'job_schedule_rooms.id', '=', 'job_schedule_room_rentals.job_schedule_room_id')
            ->where('job_schedule_rooms.job_schedule_id', $csrService->id)
            ->pluck('job_schedule_room_rentals.job_advice_room_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $this->assertSame([4], $linkedJobAdviceRoomIds);

        $docTypeMethod = new ReflectionMethod(JobScheduleController::class, 'documentTypeForJobSchedule');
        $docTypeMethod->setAccessible(true);

        $this->assertSame('installation_report', $docTypeMethod->invoke($controller, JobSchedule::findOrFail(42)));
        $this->assertSame('customer_service_report', $docTypeMethod->invoke($controller, JobSchedule::findOrFail(50)));
    }

    public function test_repair_mixed_rental_follow_ups_generates_missing_csr_and_repairs_old_check_number(): void
    {
        $this->seedMixedUnitOnlyAndRefillRoom();

        DB::table('job_schedule_room_rentals')->where('job_schedule_room_id', 50)->update([
            'job_advice_room_id' => 3,
        ]);
        DB::table('job_schedule_rooms')->where('id', 50)->update([
            'job_advice_room_id' => 3,
        ]);
        DB::table('job_schedules')->where('id', 42)->update([
            'job_number' => 'JKT-CSR/26-07/0002',
            'status' => 'assign_material',
            'internal_notes' => 'Auto-generated Check period 2/2 after install job JKT-IR/26-06/0002',
        ]);
        DB::table('job_assign_schedules')->insert([
            'job_schedule_id' => 42,
            'team_id' => 4,
            'status' => 'assigned',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('operational:repair-mixed-rental-follow-up-schedules', [
            '--job-advice' => ['JKT-JA/26-06/0004'],
        ])->assertSuccessful();

        $this->assertSame(1, JobSchedule::where('job_advice_id', 3)->where('period', 2)->count());
        $this->assertDatabaseHas('job_schedules', [
            'id' => 42,
            'job_number' => 'JKT-CSR/26-07/0002',
            'status' => 'assign_material',
        ]);

        $this->artisan('operational:repair-mixed-rental-follow-up-schedules', [
            '--job-advice' => ['JKT-JA/26-06/0004'],
            '--apply' => true,
        ])->assertSuccessful();

        $this->assertSame(2, JobSchedule::where('job_advice_id', 3)->where('period', 2)->count());
        $this->assertStringStartsWith('JKT-IR/26-07/', JobSchedule::findOrFail(42)->job_number);
        $this->assertDatabaseHas('job_schedules', [
            'id' => 42,
            'status' => 'assign_team',
            'material_checked' => true,
        ]);

        $servicePeriodTwo = JobSchedule::where('job_advice_id', 3)
            ->where('type', 'service')
            ->where('period', 2)
            ->firstOrFail();
        $this->assertDatabaseHas('job_schedule_room_rentals', [
            'job_schedule_room_id' => $servicePeriodTwo->jobScheduleRooms()->firstOrFail()->id,
            'job_advice_room_id' => 4,
        ]);
        $this->assertSame('JKT-JA/26-06/0004', $servicePeriodTwo->reference_number);

        $servicePeriodTwo->update(['reference_number' => null]);
        $this->artisan('operational:repair-mixed-rental-follow-up-schedules', [
            '--job-advice' => ['JKT-JA/26-06/0004'],
            '--apply' => true,
        ])->assertSuccessful();

        $this->assertSame('JKT-JA/26-06/0004', $servicePeriodTwo->fresh()->reference_number);
    }

    public function test_assigning_unit_only_check_does_not_reuse_csr_number_from_same_day_and_team(): void
    {
        $this->seedMixedUnitOnlyAndRefillRoom();

        DB::table('job_schedules')->where('id', 42)->update(['job_number' => null]);
        DB::table('job_schedules')->insert([
            'id' => 51,
            'job_number' => 'JKT-CSR/26-07/0002',
            'type' => 'service_routine',
            'status' => 'assign_team',
            'job_advice_id' => 3,
            'building_id' => 3,
            'building_name' => 'Gedung Mixed',
            'room_id' => 3,
            'room_name' => 'Ruang Delima',
            'company_name' => 'Test 260218 PT',
            'contract_number' => 'JKT-CA/26-06/0004',
            'schedule_date' => '2026-07-02',
            'expected_date' => '2026-07-02',
            'period' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('job_schedule_rooms')->insert([
            'id' => 51,
            'job_schedule_id' => 51,
            'job_advice_room_id' => 4,
            'room_name' => 'Ruang Delima',
            'room_id' => 3,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('job_schedule_room_rentals')->insert([
            'job_schedule_room_id' => 51,
            'job_advice_room_id' => 4,
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('job_assign_schedules')->insert([
            ['job_schedule_id' => 42, 'team_id' => 4, 'status' => 'assigned', 'created_at' => now(), 'updated_at' => now()],
            ['job_schedule_id' => 51, 'team_id' => 4, 'status' => 'assigned', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $method = new ReflectionMethod(JobScheduleController::class, 'ensureAssignedJobNumber');
        $method->setAccessible(true);
        $method->invoke(new JobScheduleController(), JobSchedule::findOrFail(42), 4);

        $this->assertStringStartsWith('JKT-IR/26-07/', JobSchedule::findOrFail(42)->job_number);
        $this->assertSame('JKT-CSR/26-07/0002', JobSchedule::findOrFail(51)->job_number);
    }

    private function seedUnitOnlyInstallWithExistingFirstCheck(): void
    {
        DB::table('contracts')->insert([
            'id' => 1,
            'contract_number' => 'BDG-CA/26-05/0006',
            'start_date' => '2026-05-01',
            'end_date' => '2027-04-30',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('buildings')->insert([
            'id' => 1,
            'building_name' => 'Gedung KGI',
            'name' => 'Gedung KGI',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('master_rooms')->insert([
            'id' => 1,
            'building_id' => 1,
            'room_name' => 'VIP Room',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('contract_rooms')->insert(['id' => 1, 'contract_id' => 1, 'room_id' => 1, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('rental_service_frequencies')->insert([
            'id' => 1,
            'name' => 'Monthly',
            'frequency_times_per_month' => 1,
            'frequency_months' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('master_rentals')->insert([
            'id' => 1,
            'rental_name' => 'Unit Only',
            'rental_type' => 'unit_only',
            'service_frequency_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('job_advices')->insert([
            'id' => 1,
            'job_advice_number' => 'BDG-JA/26-05/0006',
            'type' => 'install',
            'company_name' => 'KGI',
            'contract_id' => 1,
            'expected_date' => '2026-05-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('job_advice_rooms')->insert([
            'id' => 1,
            'job_advice_id' => 1,
            'contract_room_id' => 1,
            'rental_product_id' => 1,
            'room_name' => 'VIP Room',
            'rental_name' => 'Unit Only',
            'install_job_schedule_id' => 10,
            'service_job_schedule_id' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('job_schedules')->insert([
            'id' => 10,
            'job_number' => 'BDG-IR/26-05/0006',
            'type' => 'install',
            'status' => 'done_job',
            'job_advice_id' => 1,
            'building_id' => 1,
            'building_name' => 'Gedung KGI',
            'room_id' => 1,
            'room_name' => 'VIP Room',
            'company_name' => 'KGI',
            'contract_number' => 'BDG-CA/26-05/0006',
            'schedule_date' => '2026-05-01',
            'expected_date' => '2026-05-01',
            'ba_date' => '2026-05-02',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('job_schedules')->insert([
            'id' => 20,
            'job_number' => null,
            'type' => 'service_first',
            'status' => 'scheduled',
            'job_advice_id' => 1,
            'building_id' => 1,
            'building_name' => 'Gedung KGI',
            'room_id' => 1,
            'room_name' => 'VIP Room',
            'company_name' => 'KGI',
            'contract_number' => 'BDG-CA/26-05/0006',
            'schedule_date' => '2026-05-02',
            'expected_date' => '2026-05-02',
            'period' => 1,
            'service_frequency' => 1,
            'service_period_type' => 'Monthly',
            'material_checked' => true,
            'material_checked_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('job_schedule_rooms')->insert([
            'id' => 10,
            'job_schedule_id' => 10,
            'job_advice_room_id' => 1,
            'room_name' => 'VIP Room',
            'room_id' => 1,
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('job_schedule_rooms')->insert([
            'id' => 20,
            'job_schedule_id' => 20,
            'job_advice_room_id' => 1,
            'room_name' => 'VIP Room',
            'room_id' => 1,
            'status' => 'pending',
            'material_return_status' => 'not_required',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('job_schedule_room_rentals')->insert([
            'job_schedule_room_id' => 20,
            'job_advice_room_id' => 1,
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedRefillOnlyFirstService(): void
    {
        DB::table('contracts')->insert([
            'id' => 2,
            'contract_number' => 'BDG-CA/26-06/0001',
            'start_date' => '2026-06-01',
            'end_date' => '2027-05-31',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('buildings')->insert([
            'id' => 2,
            'building_name' => 'Gedung Refill',
            'name' => 'Gedung Refill',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('master_rooms')->insert([
            'id' => 2,
            'building_id' => 2,
            'room_name' => 'Lobby Ground',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('contract_rooms')->insert(['id' => 2, 'contract_id' => 2, 'room_id' => 2, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('rental_service_frequencies')->insert([
            'id' => 2,
            'name' => 'Monthly',
            'frequency_times_per_month' => 1,
            'frequency_months' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('master_rentals')->insert([
            'id' => 2,
            'rental_name' => 'Refill Only',
            'rental_type' => 'refill_only',
            'service_frequency_id' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('job_advices')->insert([
            'id' => 2,
            'job_advice_number' => 'BDG-JA/26-06/0001',
            'type' => 'install',
            'company_name' => 'Refill Customer',
            'contract_id' => 2,
            'expected_date' => '2026-06-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('job_advice_rooms')->insert([
            'id' => 2,
            'job_advice_id' => 2,
            'contract_room_id' => 2,
            'rental_product_id' => 2,
            'room_name' => 'Lobby Ground',
            'rental_name' => 'Refill Only',
            'service_job_schedule_id' => 30,
            'rental_has_service' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('job_schedules')->insert([
            'id' => 30,
            'job_number' => 'BDG-CSR/26-06/0006',
            'type' => 'service_first',
            'status' => 'done_job',
            'job_advice_id' => 2,
            'building_id' => 2,
            'building_name' => 'Gedung Refill',
            'room_id' => 2,
            'room_name' => 'Lobby Ground',
            'company_name' => 'Refill Customer',
            'contract_number' => 'BDG-CA/26-06/0001',
            'schedule_date' => '2026-06-01',
            'expected_date' => '2026-06-01',
            'ba_date' => '2026-06-02',
            'period' => 1,
            'service_frequency' => 1,
            'service_period_type' => 'Monthly',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('job_schedule_rooms')->insert([
            'id' => 30,
            'job_schedule_id' => 30,
            'job_advice_room_id' => 2,
            'room_name' => 'Lobby Ground',
            'room_id' => 2,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('job_schedule_room_rentals')->insert([
            'job_schedule_room_id' => 30,
            'job_advice_room_id' => 2,
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedQuarterlyRentalFirstService(): void
    {
        DB::table('contracts')->insert([
            'id' => 5,
            'contract_number' => 'SBY-CA/26-06/0099',
            'start_date' => '2026-06-20',
            'end_date' => '2027-06-19',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('buildings')->insert([
            'id' => 5,
            'building_name' => 'Gedung PB Games',
            'name' => 'Gedung PB Games',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('master_rooms')->insert([
            'id' => 5,
            'building_id' => 5,
            'room_name' => 'Ruang Games',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('contract_rooms')->insert(['id' => 5, 'contract_id' => 5, 'room_id' => 5, 'created_at' => now(), 'updated_at' => now()]);
        // Real seed data uses an Indonesian free-text name, not a 'quarterly' keyword.
        DB::table('rental_service_frequencies')->insert([
            'id' => 5,
            'name' => 'Freq 1x per 3 bulan',
            'frequency_times_per_month' => 1,
            'frequency_months' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('master_rentals')->insert([
            'id' => 5,
            'rental_name' => 'Rental 1x svc per 3 bulan',
            'rental_type' => 'unit_refill',
            'service_frequency_id' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('job_advices')->insert([
            'id' => 5,
            'job_advice_number' => 'SBY-JA/26-06/0099',
            'type' => 'install',
            'company_name' => 'PB Games',
            'contract_id' => 5,
            'expected_date' => '2026-06-20',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('job_advice_rooms')->insert([
            'id' => 5,
            'job_advice_id' => 5,
            'contract_room_id' => 5,
            'rental_product_id' => 5,
            'room_name' => 'Ruang Games',
            'rental_name' => 'Rental 1x svc per 3 bulan',
            'service_job_schedule_id' => 40,
            'rental_has_service' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('job_schedules')->insert([
            'id' => 40,
            'job_number' => 'SBY-CSR/26-06/0099',
            'type' => 'service_first',
            'status' => 'done_job',
            'job_advice_id' => 5,
            'building_id' => 5,
            'building_name' => 'Gedung PB Games',
            'room_id' => 5,
            'room_name' => 'Ruang Games',
            'company_name' => 'PB Games',
            'contract_number' => 'SBY-CA/26-06/0099',
            'schedule_date' => '2026-06-20',
            'expected_date' => '2026-06-20',
            'ba_date' => '2026-06-20',
            'period' => 1,
            'service_frequency' => 1,
            'service_period_type' => 'Freq 1x per 3 bulan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('job_schedule_rooms')->insert([
            'id' => 40,
            'job_schedule_id' => 40,
            'job_advice_room_id' => 5,
            'room_name' => 'Ruang Games',
            'room_id' => 5,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('job_schedule_room_rentals')->insert([
            'job_schedule_room_id' => 40,
            'job_advice_room_id' => 5,
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_first_service_after_cancelled_remove_free_excludes_unit_only_rental_in_mixed_room(): void
    {
        $this->seedMixedRoomForCancelledRemoveFree();

        $removeJob = JobSchedule::findOrFail(60);

        $method = new ReflectionMethod(JobScheduleController::class, 'ensureFirstServiceAfterCancelledRemoveFree');
        $method->setAccessible(true);
        $created = $method->invoke(new JobScheduleController(), $removeJob);

        $this->assertSame(1, $created);

        $schedule = JobSchedule::where('job_advice_id', 3)
            ->where('type', 'service_first')
            ->whereNotIn('id', [41, 50])
            ->firstOrFail();

        $this->assertFalse((bool) $schedule->material_checked);

        $linkedJobAdviceRoomIds = DB::table('job_schedule_room_rentals')
            ->join('job_schedule_rooms', 'job_schedule_rooms.id', '=', 'job_schedule_room_rentals.job_schedule_room_id')
            ->where('job_schedule_rooms.job_schedule_id', $schedule->id)
            ->pluck('job_schedule_room_rentals.job_advice_room_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        // Only the refill_only jaRoom (id 4) should be linked — the unit_only jaRoom (id 3)
        // must not be pulled into this refill CSR chain.
        $this->assertSame([4], $linkedJobAdviceRoomIds);

        // The unit_only jaRoom's own service_job_schedule_id must be untouched by this
        // refill CSR generation — it still points at its own (now-cancelled) check job.
        $this->assertSame(41, JobAdviceRoom::findOrFail(3)->service_job_schedule_id);
        $this->assertSame($schedule->id, JobAdviceRoom::findOrFail(4)->service_job_schedule_id);
    }

    private function seedMixedRoomForCancelledRemoveFree(): void
    {
        $this->seedMixedUnitOnlyAndRefillRoom();

        DB::table('quotations')->insert([
            'id' => 1,
            'quotation_number' => 'JKT-QT/26-06/0004',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('contracts')->where('id', 3)->update(['quotation_id' => 1]);
        DB::table('job_advices')->where('id', 3)->update([
            'status' => 'approved',
            'customer_id' => 1,
        ]);
        DB::table('unit_on_walls')->insert([
            'id' => 1,
            'customer_id' => 1,
            'room_id' => 3,
            'serial_number_id' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('job_schedules')->insert([
            'id' => 60,
            'job_number' => 'JKT-RF/26-06/0004',
            'type' => 'remove_free',
            'status' => 'cancelled',
            'job_advice_id' => 3,
            'building_id' => 3,
            'building_name' => 'Gedung Mixed',
            'room_id' => 3,
            'room_name' => 'Ruang Delima',
            'company_name' => 'Test 260218 PT',
            'contract_number' => 'JKT-CA/26-06/0004',
            'quotation_number' => 'JKT-QT/26-06/0004',
            'schedule_date' => '2026-07-05',
            'expected_date' => '2026-07-05',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('job_schedule_rooms')->insert([
            'id' => 60,
            'job_schedule_id' => 60,
            'job_advice_room_id' => 3,
            'room_name' => 'Ruang Delima',
            'room_id' => 3,
            'status' => 'cancelled',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Cancel the prior schedules linked to this jaRoom group so the "active service
        // schedule already exists" guard (which checks the whole physical-room group,
        // unit_only included) doesn't skip generation before we can observe the pivot fix.
        DB::table('job_schedules')->whereIn('id', [41, 42, 50])->update(['status' => 'cancelled']);
        DB::table('job_advice_rooms')->where('id', 4)->update(['service_job_schedule_id' => null]);
    }

    private function seedMixedUnitOnlyAndRefillRoom(): void
    {
        DB::table('contracts')->insert([
            'id' => 3,
            'contract_number' => 'JKT-CA/26-06/0004',
            'start_date' => '2026-06-01',
            'end_date' => '2026-07-31',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('buildings')->insert([
            'id' => 3,
            'building_name' => 'Gedung Mixed',
            'name' => 'Gedung Mixed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('master_rooms')->insert([
            'id' => 3,
            'building_id' => 3,
            'room_name' => 'Ruang Delima',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('contract_rooms')->insert(['id' => 3, 'contract_id' => 3, 'room_id' => 3, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('rental_service_frequencies')->insert([
            ['id' => 3, 'name' => 'Monthly', 'frequency_times_per_month' => 1, 'frequency_months' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'Monthly', 'frequency_times_per_month' => 1, 'frequency_months' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('master_rentals')->insert([
            ['id' => 3, 'rental_name' => 'Unit Only', 'rental_type' => 'unit_only', 'service_frequency_id' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'rental_name' => 'Refill Only', 'rental_type' => 'refill_only', 'service_frequency_id' => 4, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('job_advices')->insert([
            'id' => 3,
            'job_advice_number' => 'JKT-JA/26-06/0004',
            'type' => 'install',
            'company_name' => 'Test 260218 PT',
            'contract_id' => 3,
            'expected_date' => '2026-06-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('job_advice_rooms')->insert([
            [
                'id' => 3,
                'job_advice_id' => 3,
                'contract_room_id' => 3,
                'rental_product_id' => 3,
                'room_name' => 'Ruang Delima',
                'rental_name' => 'Unit Only',
                'install_job_schedule_id' => 40,
                'service_job_schedule_id' => 41,
                'rental_has_service' => false,
                'unit_already_installed' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'job_advice_id' => 3,
                'contract_room_id' => 3,
                'rental_product_id' => 4,
                'room_name' => 'Ruang Delima',
                'rental_name' => 'Refill Only',
                'install_job_schedule_id' => null,
                'service_job_schedule_id' => 50,
                'rental_has_service' => true,
                'unit_already_installed' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        DB::table('job_schedules')->insert([
            [
                'id' => 40,
                'job_number' => 'JKT-IR/26-06/0002',
                'type' => 'install',
                'status' => 'done_job',
                'job_advice_id' => 3,
                'building_id' => 3,
                'building_name' => 'Gedung Mixed',
                'room_id' => 3,
                'room_name' => 'Ruang Delima',
                'company_name' => 'Test 260218 PT',
                'contract_number' => 'JKT-CA/26-06/0004',
                'schedule_date' => '2026-06-01',
                'expected_date' => '2026-06-01',
                'ba_date' => '2026-06-02',
                'period' => null,
                'service_frequency' => null,
                'service_period_type' => null,
                'material_checked' => false,
                'material_checked_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 41,
                'job_number' => null,
                'type' => 'service_first',
                'status' => 'done_job',
                'job_advice_id' => 3,
                'building_id' => 3,
                'building_name' => 'Gedung Mixed',
                'room_id' => 3,
                'room_name' => 'Ruang Delima',
                'company_name' => 'Test 260218 PT',
                'contract_number' => 'JKT-CA/26-06/0004',
                'schedule_date' => '2026-06-02',
                'expected_date' => '2026-06-02',
                'ba_date' => null,
                'period' => 1,
                'service_frequency' => 1,
                'service_period_type' => 'Monthly',
                'material_checked' => true,
                'material_checked_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 42,
                'job_number' => null,
                'type' => 'service_routine',
                'status' => 'scheduled',
                'job_advice_id' => 3,
                'building_id' => 3,
                'building_name' => 'Gedung Mixed',
                'room_id' => 3,
                'room_name' => 'Ruang Delima',
                'company_name' => 'Test 260218 PT',
                'contract_number' => 'JKT-CA/26-06/0004',
                'schedule_date' => '2026-07-02',
                'expected_date' => '2026-07-02',
                'ba_date' => null,
                'period' => 2,
                'service_frequency' => 1,
                'service_period_type' => 'Monthly',
                'material_checked' => true,
                'material_checked_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 50,
                'job_number' => 'JKT-CSR/26-06/0004',
                'type' => 'service_first',
                'status' => 'done_job',
                'job_advice_id' => 3,
                'building_id' => 3,
                'building_name' => 'Gedung Mixed',
                'room_id' => 3,
                'room_name' => 'Ruang Delima',
                'company_name' => 'Test 260218 PT',
                'contract_number' => 'JKT-CA/26-06/0004',
                'schedule_date' => '2026-06-03',
                'expected_date' => '2026-06-03',
                'ba_date' => '2026-06-03',
                'period' => 1,
                'service_frequency' => 1,
                'service_period_type' => 'Monthly',
                'material_checked' => false,
                'material_checked_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        DB::table('job_schedule_rooms')->insert([
            ['id' => 40, 'job_schedule_id' => 40, 'job_advice_room_id' => 3, 'room_name' => 'Ruang Delima', 'room_id' => 3, 'status' => 'completed', 'material_return_status' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 41, 'job_schedule_id' => 41, 'job_advice_room_id' => 3, 'room_name' => 'Ruang Delima', 'room_id' => 3, 'status' => 'completed', 'material_return_status' => 'not_required', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 42, 'job_schedule_id' => 42, 'job_advice_room_id' => 3, 'room_name' => 'Ruang Delima', 'room_id' => 3, 'status' => 'pending', 'material_return_status' => 'not_required', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 50, 'job_schedule_id' => 50, 'job_advice_room_id' => 4, 'room_name' => 'Ruang Delima', 'room_id' => 3, 'status' => 'completed', 'material_return_status' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('job_schedule_room_rentals')->insert([
            ['job_schedule_room_id' => 41, 'job_advice_room_id' => 3, 'is_primary' => true, 'created_at' => now(), 'updated_at' => now()],
            ['job_schedule_room_id' => 42, 'job_advice_room_id' => 3, 'is_primary' => true, 'created_at' => now(), 'updated_at' => now()],
            ['job_schedule_room_id' => 50, 'job_advice_room_id' => 4, 'is_primary' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
