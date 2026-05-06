<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobAssignMaterialIssueController;
use App\Models\JobAssignMaterialIssue;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class JobAssignMaterialIssueAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->text('roles')->nullable();
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

        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('role_id')->nullable();
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('branch_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        Schema::create('user_access_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('access_type')->nullable();
            $table->json('access_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('team_name')->nullable();
            $table->foreignId('team_head_id')->nullable();
            $table->foreignId('branch_office')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->string('nama_gedung')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_advices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('request_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->nullable();
            $table->foreignId('job_advice_id')->nullable();
            $table->foreignId('building_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_assign_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_issues', function (Blueprint $table) {
            $table->id();
            $table->string('issue_number')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_assign_material_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_assign_schedule_id')->nullable();
            $table->foreignId('material_issue_id')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('job_assign_material_issues');
        Schema::dropIfExists('material_issues');
        Schema::dropIfExists('job_assign_schedules');
        Schema::dropIfExists('job_schedules');
        Schema::dropIfExists('job_advices');
        Schema::dropIfExists('buildings');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('user_access_levels');
        Schema::dropIfExists('branch_user');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_branch_access_can_see_material_assign_issues_from_same_warehouse_branch(): void
    {
        \DB::table('users')->insert([
            [
                'id' => 1,
                'name' => 'Surya Pratama',
                'email' => 'surya@example.test',
                'password' => 'password',
                'branch_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Other User',
                'email' => 'other@example.test',
                'password' => 'password',
                'branch_id' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $viewer = User::findOrFail(1);
        $otherUser = User::findOrFail(2);

        \DB::table('user_access_levels')->insert([
            'user_id' => $viewer->id,
            'access_type' => 'branch',
            'access_config' => json_encode(['allowed_branch_ids' => [2]]),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('branches')->insert([
            ['id' => 2, 'name' => 'Bandung Branch', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Jakarta Branch', 'created_at' => now(), 'updated_at' => now()],
        ]);

        \DB::table('warehouses')->insert([
            ['id' => 10, 'name' => 'Warehouse Bandung', 'branch_id' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'name' => 'Warehouse Jakarta', 'branch_id' => 3, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        \DB::table('job_advices')->insert([
            ['id' => 20, 'created_by' => $otherUser->id, 'request_by' => $otherUser->id, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 21, 'created_by' => $otherUser->id, 'request_by' => $otherUser->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        \DB::table('job_schedules')->insert([
            ['id' => 30, 'job_number' => 'BDG-CSR/26-05/0001', 'job_advice_id' => 20, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 31, 'job_number' => 'JKT-CSR/26-05/0001', 'job_advice_id' => 21, 'created_at' => now(), 'updated_at' => now()],
        ]);

        \DB::table('job_assign_schedules')->insert([
            ['id' => 40, 'job_schedule_id' => 30, 'team_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 41, 'job_schedule_id' => 31, 'team_id' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        \DB::table('material_issues')->insert([
            ['id' => 50, 'issue_number' => 'BDG-MA/26-05/0001', 'warehouse_id' => 10, 'status' => 'approved', 'created_by' => $otherUser->id, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 51, 'issue_number' => 'JKT-MA/26-05/0001', 'warehouse_id' => 11, 'status' => 'approved', 'created_by' => $otherUser->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        \DB::table('job_assign_material_issues')->insert([
            [
                'id' => 60,
                'job_assign_schedule_id' => 40,
                'material_issue_id' => 50,
                'created_by' => $otherUser->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 61,
                'job_assign_schedule_id' => 41,
                'material_issue_id' => 51,
                'created_by' => $otherUser->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $controller = app(JobAssignMaterialIssueController::class);
        $method = new \ReflectionMethod($controller, 'applyMaterialAssignIssueAccessFilter');
        $method->setAccessible(true);

        $filteredQuery = $method->invoke($controller, JobAssignMaterialIssue::query(), $viewer);

        $this->assertSame([60], $filteredQuery->pluck('id')->all());
    }
}
