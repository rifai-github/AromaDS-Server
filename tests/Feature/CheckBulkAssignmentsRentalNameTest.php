<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobScheduleController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Bug #31 (live QA case: "Toilet Umum" room, job_advice_id 29): a single
 * physical room can host 2+ different rentals (e.g. "ADS W300 100 ml"
 * unit+refill and a separate "Rental Unit Only"), each its own
 * JobScheduleRoom/JobAdviceRoom row sharing the same room_name. The Job
 * Schedule list's checkbox groups those rows under one visual checkbox
 * (data-room-ids), so checking ONE row expands to 2+ room ids when the
 * Material Assign confirmation modal calls check-bulk-assignments — and
 * the modal showed two rows that looked identical ("Toilet Umum" / "-"),
 * with nothing to tell them apart. QA read this as a duplicate-row bug.
 *
 * The fix surfaces each room's rental_name in the response so the rows are
 * distinguishable instead of indistinguishable duplicates.
 */
class CheckBulkAssignmentsRentalNameTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
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

        Schema::create('job_advices', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->nullable();
            $table->foreignId('job_advice_id')->nullable();
            $table->foreignId('building_id')->nullable();
            $table->string('type')->nullable();
            $table->integer('period')->nullable();
            $table->string('status')->nullable();
            $table->boolean('material_checked')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_advice_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_advice_id')->nullable();
            $table->unsignedBigInteger('rental_product_id')->nullable();
            $table->string('room_name')->nullable();
            $table->integer('quantity')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_rentals', function (Blueprint $table) {
            $table->id();
            $table->string('rental_code')->nullable();
            $table->string('rental_name')->nullable();
            $table->string('rental_type')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedule_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('job_advice_room_id')->nullable();
            $table->string('room_name')->nullable();
            $table->string('status')->nullable();
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

        Schema::create('job_schedule_room_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('job_schedule_room_id')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_issues', function (Blueprint $table) {
            $table->id();
            $table->string('issue_number')->nullable();
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

        Schema::create('material_issue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_issue_id')->nullable();
            $table->string('room_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        User::create(['id' => 1, 'name' => 'Admin']);
        Auth::login(User::findOrFail(1));
    }

    protected function tearDown(): void
    {
        foreach ([
            'material_issue_items',
            'job_assign_material_issues',
            'material_issues',
            'job_schedule_room_assignments',
            'job_assign_schedules',
            'job_schedule_rooms',
            'master_rentals',
            'job_advice_rooms',
            'job_schedules',
            'job_advices',
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

    public function test_two_rentals_sharing_a_room_name_are_distinguishable_by_rental_name(): void
    {
        DB::table('job_advices')->insert(['id' => 29, 'created_at' => now(), 'updated_at' => now()]);

        DB::table('master_rentals')->insert([
            [
                'id' => 1,
                'rental_code' => 'w300100',
                'rental_name' => 'ADS W300 100 ml',
                'rental_type' => 'unit_refill',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'rental_code' => 'Rental-1',
                'rental_name' => 'Rental Unit Only',
                'rental_type' => 'unit_only',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('job_advice_rooms')->insert([
            [
                'id' => 49,
                'job_advice_id' => 29,
                'rental_product_id' => 1,
                'room_name' => 'Toilet Umum',
                'quantity' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 50,
                'job_advice_id' => 29,
                'rental_product_id' => 2,
                'room_name' => 'Toilet Umum',
                'quantity' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('job_schedules')->insert([
            'id' => 187,
            'job_number' => 'SBY-IR/26-06/0014',
            'job_advice_id' => 29,
            'status' => 'scheduled',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_schedule_rooms')->insert([
            [
                'id' => 200,
                'job_schedule_id' => 187,
                'job_advice_room_id' => 49,
                'room_name' => 'Toilet Umum',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 207,
                'job_schedule_id' => 187,
                'job_advice_room_id' => 50,
                'room_name' => 'Toilet Umum',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $request = Request::create('/operational/job-schedules/check-bulk-assignments', 'POST', [
            'job_ids' => [187],
            'selected_room_ids' => [200, 207],
            'strict_selection' => true,
        ]);

        $response = app(JobScheduleController::class)->checkBulkAssignments($request);
        $payload = $response->getData(true);

        $this->assertSame('success', $payload['status']);
        $this->assertCount(2, $payload['data']);

        $rentalNames = collect($payload['data'])->pluck('rental_name')->sort()->values()->all();

        $this->assertSame(['ADS W300 100 ml', 'Rental Unit Only'], $rentalNames);
        $this->assertNotSame(
            $payload['data'][0]['rental_name'],
            $payload['data'][1]['rental_name'],
            'The two rooms must be distinguishable by rental name instead of looking like identical duplicates.'
        );
    }

    public function test_selected_assign_material_room_stays_assign_material_without_material_items(): void
    {
        DB::table('job_schedules')->insert([
            'id' => 73,
            'job_number' => 'JKT-IR/26-06/0008',
            'job_advice_id' => 14,
            'building_id' => 199,
            'type' => 'install',
            'period' => 1,
            'status' => 'assign_material',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_schedule_rooms')->insert([
            'id' => 77,
            'job_schedule_id' => 73,
            'job_advice_room_id' => 23,
            'room_name' => 'Toko',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_assign_schedules')->insert([
            'id' => 54,
            'job_schedule_id' => 73,
            'team_id' => null,
            'status' => 'assigned',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/operational/job-schedules/check-bulk-assignments', 'POST', [
            'job_ids' => [73],
            'selected_room_ids' => [77],
            'strict_selection' => true,
        ]);

        $response = app(JobScheduleController::class)->checkBulkAssignments($request);
        $payload = $response->getData(true);

        $this->assertSame('success', $payload['status']);
        $this->assertSame('assign_material', $payload['data'][0]['job_status']);
    }
}
