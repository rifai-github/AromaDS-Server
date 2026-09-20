<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobScheduleController;
use Carbon\Carbon;
use App\Models\JobAdvice;
use App\Models\JobSchedule;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Dua penjagaan tanggal yang diminta klien:
 * 1. Tanggal Job tidak boleh lebih kecil dari tanggal kontrak (sama hari boleh).
 * 2. BA Date tidak boleh lebih kecil dari tanggal assign tim (sama hari boleh).
 * 3. BA Date tidak boleh bertanggal di masa depan (hari ini boleh).
 */
class JobDateContractAndBaDateGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('roles')->nullable();
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

        Schema::create('user_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('permission_id')->nullable();
            $table->timestamps();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->nullable();
            $table->date('contract_date')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number')->nullable();
            $table->date('quotation_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_advices', function (Blueprint $table) {
            $table->id();
            $table->string('job_advice_number')->nullable();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('quotation_id')->nullable();
            $table->string('type')->nullable();
            $table->date('expected_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->nullable();
            $table->foreignId('job_advice_id')->nullable();
            $table->foreignId('building_id')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->string('contract_number')->nullable();
            $table->date('schedule_date')->nullable();
            $table->date('assign_date')->nullable();
            $table->date('ba_date')->nullable();
            $table->string('ba_number')->nullable();
            $table->text('internal_notes')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_assign_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->date('assigned_date')->nullable();
            $table->string('status')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('assigned_by')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Disentuh hasActiveInvoice() saat job tidak punya Job Advice.
        Schema::create('periodic_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('contract_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        User::create(['id' => 1, 'name' => 'Admin']);
        Auth::login(User::findOrFail(1));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        foreach ([
            'periodic_jobs',
            'teams',
            'job_assign_schedules',
            'job_schedules',
            'job_advices',
            'quotations',
            'contracts',
            'user_permission',
            'user_roles',
            'permissions',
            'roles',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    // ----- Aturan 1: Tanggal Job vs tanggal kontrak -----

    public function test_job_date_before_contract_date_is_rejected(): void
    {
        $jobAdvice = $this->seedContractJobAdvice('2026-09-10');

        $this->assertSame(
            'Tanggal Job tidak boleh lebih kecil dari tanggal Contract (10/09/2026).',
            $this->dateMessage($jobAdvice, '2026-09-09')
        );
    }

    public function test_job_date_equal_to_contract_date_is_allowed(): void
    {
        $jobAdvice = $this->seedContractJobAdvice('2026-09-10');

        $this->assertNull($this->dateMessage($jobAdvice, '2026-09-10'));
        $this->assertNull($this->dateMessage($jobAdvice, '2026-09-11'));
    }

    public function test_job_without_contract_or_quotation_is_not_blocked(): void
    {
        $jobAdvice = JobAdvice::create(['id' => 9, 'type' => 'install']);

        $this->assertNull($this->dateMessage($jobAdvice, '2020-01-01'));
        $this->assertNull($this->dateMessage(null, '2020-01-01'));
    }

    public function test_quotation_sourced_job_uses_quotation_date_as_floor(): void
    {
        DB::table('quotations')->insert([
            'id' => 1,
            'quotation_number' => 'SQ/26-09/0001',
            'quotation_date' => '2026-09-15',
        ]);

        $jobAdvice = JobAdvice::create([
            'id' => 5,
            'quotation_id' => 1,
            'type' => 'install_free',
        ]);

        $this->assertSame(
            'Tanggal Job tidak boleh lebih kecil dari tanggal SQ (15/09/2026).',
            $this->dateMessage($jobAdvice, '2026-09-14')
        );
    }

    public function test_assign_team_is_blocked_when_job_date_is_before_contract_date(): void
    {
        $this->seedContractJobAdvice('2026-09-10');

        DB::table('teams')->insert(['id' => 1, 'name' => 'Team A']);

        $jobSchedule = JobSchedule::create([
            'id' => 1,
            'job_number' => 'JKT-IR/26-09/0001',
            'job_advice_id' => 1,
            'type' => 'install',
            'status' => 'new_job',
            'schedule_date' => '2026-09-05',
        ]);

        $request = Request::create('/operational/job-schedules/1/assign-team', 'POST', [
            'team_id' => 1,
        ]);

        $response = app(JobScheduleController::class)->assignToTeam($request, $jobSchedule);
        $payload = $response->getData(true);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertStringContainsString(
            'Tanggal Job tidak boleh lebih kecil dari tanggal Contract (10/09/2026).',
            $payload['message']
        );
        $this->assertDatabaseCount('job_assign_schedules', 0);
        $this->assertDatabaseHas('job_schedules', ['id' => 1, 'status' => 'new_job']);
    }

    // ----- Aturan 2: BA Date vs tanggal assign -----

    public function test_ba_date_before_assign_date_is_rejected(): void
    {
        $jobSchedule = $this->seedDoneJobWithAssignDate('2026-09-10');
        $this->grantBaDatePermission();

        $request = Request::create('/operational/job-schedules/1/ba-date', 'POST', [
            'ba_date' => '2026-09-09',
        ]);
        $request->headers->set('Accept', 'application/json');

        $response = app(JobScheduleController::class)->updateBaDate($request, $jobSchedule);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(
            'BA Date tidak boleh lebih kecil dari tanggal Assign (10/09/2026).',
            $response->getData(true)['message']
        );
        $this->assertNull($jobSchedule->fresh()->ba_date);
    }

    public function test_ba_date_on_the_same_day_as_assign_date_is_allowed(): void
    {
        $jobSchedule = $this->seedDoneJobWithAssignDate('2026-09-10');
        $this->grantBaDatePermission();

        $request = Request::create('/operational/job-schedules/1/ba-date', 'POST', [
            'ba_date' => '2026-09-10',
        ]);
        $request->headers->set('Accept', 'application/json');

        $response = app(JobScheduleController::class)->updateBaDate($request, $jobSchedule);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('2026-09-10', $jobSchedule->fresh()->ba_date->format('Y-m-d'));
    }

    public function test_assign_date_falls_back_to_job_assign_schedule_when_column_is_empty(): void
    {
        $jobSchedule = $this->seedDoneJobWithAssignDate(null);

        DB::table('job_assign_schedules')->insert([
            'id' => 1,
            'job_schedule_id' => 1,
            'team_id' => 1,
            'assigned_date' => '2026-09-12',
            'status' => 'assigned',
        ]);

        $this->assertSame('2026-09-12', $jobSchedule->resolveAssignDate()->format('Y-m-d'));
    }

    public function test_ba_date_in_the_future_is_rejected(): void
    {
        Carbon::setTestNow('2026-09-20 09:00:00');

        $jobSchedule = $this->seedDoneJobWithAssignDate('2026-09-10');
        $this->grantBaDatePermission();

        $request = Request::create('/operational/job-schedules/1/ba-date', 'POST', [
            'ba_date' => '2026-09-21',
        ]);
        $request->headers->set('Accept', 'application/json');

        $response = app(JobScheduleController::class)->updateBaDate($request, $jobSchedule);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(
            'BA Date tidak boleh melebihi hari ini (20/09/2026).',
            $response->getData(true)['message']
        );
        $this->assertNull($jobSchedule->fresh()->ba_date);
    }

    public function test_ba_date_today_is_allowed(): void
    {
        Carbon::setTestNow('2026-09-20 09:00:00');

        $jobSchedule = $this->seedDoneJobWithAssignDate('2026-09-10');
        $this->grantBaDatePermission();

        $request = Request::create('/operational/job-schedules/1/ba-date', 'POST', [
            'ba_date' => '2026-09-20',
        ]);
        $request->headers->set('Accept', 'application/json');

        $response = app(JobScheduleController::class)->updateBaDate($request, $jobSchedule);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('2026-09-20', $jobSchedule->fresh()->ba_date->format('Y-m-d'));
    }

    // ----- helpers -----

    private function seedContractJobAdvice(string $contractDate): JobAdvice
    {
        DB::table('contracts')->insert([
            'id' => 1,
            'contract_number' => 'JKT-CA/26-09/0001',
            'contract_date' => $contractDate,
            'start_date' => $contractDate,
        ]);

        return JobAdvice::create([
            'id' => 1,
            'job_advice_number' => 'JA/26-09/0001',
            'contract_id' => 1,
            'type' => 'install',
            'expected_date' => $contractDate,
        ]);
    }

    private function seedDoneJobWithAssignDate(?string $assignDate): JobSchedule
    {
        return JobSchedule::create([
            'id' => 1,
            'job_number' => 'JKT-IR/26-09/0001',
            'type' => 'install',
            'status' => 'done_job',
            'schedule_date' => '2026-09-10',
            'assign_date' => $assignDate,
        ]);
    }

    private function grantBaDatePermission(): void
    {
        DB::table('permissions')->insert([
            'id' => 1,
            'name' => 'operational.job-schedules.ba-date.update',
        ]);
        DB::table('user_permission')->insert([
            'user_id' => 1,
            'permission_id' => 1,
        ]);

        Auth::setUser(User::with('permissions')->findOrFail(1));
    }

    private function dateMessage(?JobAdvice $jobAdvice, ?string $scheduleDate): ?string
    {
        $method = new ReflectionMethod(JobScheduleController::class, 'jobScheduleDateBeforeContractMessage');
        $method->setAccessible(true);

        return $method->invoke(app(JobScheduleController::class), $jobAdvice, $scheduleDate);
    }
}
