<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckFrozenAccount;
use App\Http\Middleware\CheckLoginRestriction;
use App\Http\Middleware\CheckMultiLogin;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OperationalViewOnlyActionPermissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckFrozenAccount::class,
            CheckLoginRestriction::class,
            CheckMultiLogin::class,
        ]);

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->text('roles')->nullable();
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

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->nullable();
            $table->foreignId('permission_id')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        foreach ([
            'role_permissions',
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

    public function test_view_only_job_schedule_user_cannot_process_material_assign(): void
    {
        $user = $this->createViewOnlyUser([
            'operational.job-schedules.view',
            'operational.job-schedules-material-assign.view',
        ]);

        $this->actingAs($user)
            ->postJson('/operational/job-schedules/bulk-material-assign', [
                'ids' => [1],
            ])
            ->assertForbidden()
            ->assertJsonPath('error', 'permission_denied');
    }

    public function test_view_only_material_issue_user_cannot_submit_material_prepare(): void
    {
        $user = $this->createViewOnlyUser([
            'operational.job-assign-material-issues.view',
        ]);

        $this->actingAs($user)
            ->postJson('/operational/job-assign-material-issues/submit-issue', [
                'material_issue_ids' => [1],
            ])
            ->assertForbidden()
            ->assertJsonPath('error', 'permission_denied');
    }

    private function createViewOnlyUser(array $permissionNames): User
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Yadi Marketing Manager',
            'email' => 'yadi@gmail.com',
            'password' => 'password',
            'roles' => 'Marketing Manager',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roleId = DB::table('roles')->insertGetId([
            'name' => 'Marketing Manager',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('user_roles')->insert([
            'user_id' => $userId,
            'role_id' => $roleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($permissionNames as $permissionName) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => $permissionName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('role_permissions')->insert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return User::findOrFail($userId);
    }
}
