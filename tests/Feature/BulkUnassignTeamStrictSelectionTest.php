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

class BulkUnassignTeamStrictSelectionTest extends TestCase
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

        Schema::create('user_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('permission_id')->nullable();
            $table->timestamps();
        });

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->nullable();
            $table->foreignId('job_advice_id')->nullable();
            $table->foreignId('building_id')->nullable();
            $table->string('type')->nullable();
            $table->integer('period')->nullable();
            $table->string('status')->nullable();
            $table->date('assign_date')->nullable();
            $table->text('internal_notes')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_assign_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->string('status')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedule_room_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('job_schedule_room_id')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('updated_by')->nullable();
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
            $table->foreignId('job_assign_schedule_id')->nullable();
            $table->string('room_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_issuings', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        User::create(['id' => 1, 'name' => 'Admin']);
        Auth::login(User::findOrFail(1));
    }

    protected function tearDown(): void
    {
        foreach ([
            'inventory_issuings',
            'material_issue_items',
            'job_assign_material_issues',
            'material_issues',
            'job_schedule_room_assignments',
            'job_assign_schedules',
            'job_schedules',
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

    public function test_strict_bulk_unassign_team_only_updates_checked_job(): void
    {
        $this->seedSiblingJobs();

        $request = Request::create('/operational/job-schedules/bulk-unassign-team', 'POST', [
            'ids' => [1],
            'strict_selection' => true,
        ]);

        $response = app(JobScheduleController::class)->bulkUnassignTeam($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Successfully unassigned 1 jobs.', $response->getData(true)['message']);

        $this->assertDatabaseHas('job_assign_schedules', [
            'id' => 1,
            'job_schedule_id' => 1,
            'status' => 'cancelled',
        ]);
        $this->assertDatabaseHas('job_schedules', [
            'id' => 1,
            'status' => 'new_job',
            'job_number' => null,
        ]);

        $this->assertDatabaseHas('job_assign_schedules', [
            'id' => 2,
            'job_schedule_id' => 2,
            'status' => 'assigned',
        ]);
        $this->assertDatabaseHas('job_schedules', [
            'id' => 2,
            'status' => 'assign_team',
            'job_number' => 'BDG-CSR/26-05/0002',
        ]);
        $this->assertStringNotContainsString(
            '[UNASSIGNED BULK]',
            (string) DB::table('job_schedules')->where('id', 2)->value('internal_notes')
        );
    }

    private function seedSiblingJobs(): void
    {
        DB::table('job_schedules')->insert([
            [
                'id' => 1,
                'job_number' => 'BDG-CSR/26-05/0001',
                'job_advice_id' => 10,
                'building_id' => 20,
                'type' => 'service',
                'period' => 1,
                'status' => 'assign_team',
                'assign_date' => '2026-06-03',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'job_number' => 'BDG-CSR/26-05/0002',
                'job_advice_id' => 10,
                'building_id' => 20,
                'type' => 'service',
                'period' => 1,
                'status' => 'assign_team',
                'assign_date' => '2026-06-03',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('job_assign_schedules')->insert([
            [
                'id' => 1,
                'job_schedule_id' => 1,
                'team_id' => 3,
                'status' => 'assigned',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'job_schedule_id' => 2,
                'team_id' => 3,
                'status' => 'assigned',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
