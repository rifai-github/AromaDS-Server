<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobScheduleController;
use App\Models\JobAdvice;
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
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
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
            $table->string('company_name')->nullable();
            $table->foreignId('contract_id')->nullable();
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

        User::create(['id' => 1, 'name' => 'Admin']);
        Auth::login(User::findOrFail(1));
    }

    protected function tearDown(): void
    {
        foreach ([
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
}
