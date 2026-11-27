<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\System\RoleController;
use App\Http\Middleware\CheckPermission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Audit Master Role 27 Nov 2026 (permintaan klien: "Audit Master Role — role harus benar").
 *
 * - Role yang namanya hanya MENGANDUNG "admin" (Admin Cabang, Marketing Admin) tidak lagi lolos
 *   semua cek izin; yang lolos hanya Admin / super_admin / Management*.
 * - Centang di Master Role benar-benar berlaku: izin aksi khusus yang tersimpan sebagai
 *   "<izin>.view", baris "Job Schedule (Suspend)" dsb., dan izin "hantu" report.reports.*.
 * - Halaman admin System (Master Roles/Users/...) tidak bisa lagi dibuka/diubah lewat URL oleh
 *   user tanpa izin menu tersebut.
 * - Simpan role di Master Role tidak lagi menghapus izin yang tidak tampil di grid, dan satu
 *   centang memberi semua izin di sel itu.
 */
class MasterRoleAuditTest extends TestCase
{
    private const TABLES = ['user_permission', 'role_permissions', 'user_roles', 'permissions', 'roles', 'users'];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nik')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('roles')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->foreignId('update_by_1')->nullable();
            $table->timestamp('update_at_1')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->longText('permissions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('system_reserved')->default(false);
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('system_reserved')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('role_id');
            $table->timestamps();
        });
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id');
            $table->foreignId('permission_id');
            $table->timestamps();
        });
        Schema::create('user_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('permission_id');
        });
    }

    protected function tearDown(): void
    {
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    private function permissionId(string $name): int
    {
        return (int) (DB::table('permissions')->where('name', $name)->value('id')
            ?? DB::table('permissions')->insertGetId(['name' => $name, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]));
    }

    private function role(string $name, array $permissions = []): int
    {
        $roleId = DB::table('roles')->insertGetId(['name' => $name, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        foreach ($permissions as $permission) {
            DB::table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $this->permissionId($permission)]);
        }

        return $roleId;
    }

    private function user(string $roleName, array $permissions = []): User
    {
        $user = User::create([
            'nik' => 'N'.uniqid(),
            'name' => $roleName.' user',
            'email' => uniqid().'@example.test',
            'username' => 'u'.uniqid(),
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        DB::table('user_roles')->insert(['user_id' => $user->id, 'role_id' => $this->role($roleName, $permissions)]);

        return $user->fresh();
    }

    private function passesMiddleware(User $user, string ...$permissions): bool
    {
        Auth::login($user);
        try {
            $response = app(CheckPermission::class)->handle(Request::create('/x'), fn () => response('ok'), ...$permissions);

            return $response->getContent() === 'ok';
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());

            return false;
        } finally {
            Auth::logout();
        }
    }

    public function test_only_exact_admin_or_management_roles_bypass_permission_checks(): void
    {
        $this->assertTrue($this->user('Admin')->bypassesPermissionChecks());
        $this->assertTrue($this->user('super_admin')->bypassesPermissionChecks());
        $this->assertTrue($this->user('Management Manager')->bypassesPermissionChecks());
        $this->assertFalse($this->user('Admin Cabang')->bypassesPermissionChecks());
        $this->assertFalse($this->user('Marketing Admin')->bypassesPermissionChecks());

        $this->assertFalse($this->passesMiddleware($this->user('Admin Cabang'), 'system.roles.*'));
        $this->assertFalse($this->passesMiddleware($this->user('Marketing Admin'), 'finance.invoices.update'));
        $this->assertTrue($this->passesMiddleware($this->user('Management Manager'), 'system.roles.*'));
        $this->assertFalse(DashboardController::canView($this->user('Admin Cabang')));
    }

    public function test_menu_wildcard_accepts_any_permission_of_that_menu_only(): void
    {
        $viewer = $this->user('Operational Staff', ['system.roles.view']);
        $this->assertTrue($this->passesMiddleware($viewer, 'system.roles.*'));
        $this->assertFalse($this->passesMiddleware($viewer, 'system.users.*'));

        $lookalike = $this->user('Lookalike', ['system.roles-extra.view']);
        $this->assertFalse($this->passesMiddleware($lookalike, 'system.roles.*'));

        $nobody = $this->user('Teknisi');
        $this->assertFalse($this->passesMiddleware($nobody, 'system.roles.*'));
    }

    public function test_ticks_saved_by_master_role_satisfy_the_keys_the_code_checks(): void
    {
        $user = $this->user('Operational Manager', [
            'operational.job-schedules.approve-ba.view',       // kolom View baris "Approve BA"
            'operational.job-schedules-suspend.view',          // baris "Job Schedule (Suspend)"
            'report.reports.warehouse.view',                   // izin hantu Report > Warehouse
            'marketing.system.salutations.view',               // izin hantu Master Salutation
            'operational.job-schedules.view',
        ]);

        $this->assertTrue($user->hasPermission('operational.job-schedules.approve-ba'));
        // Baris aksi Job Schedule: View tetap berarti lihat saja (OperationalViewOnlyActionPermissionTest).
        $this->assertFalse($user->hasPermission('operational.job-schedules-suspend.update'));
        $this->assertTrue($user->hasPermission('reports.warehouse'));
        $this->assertTrue($user->hasPermission('system.salutations.view'));
        $this->assertTrue($user->canAccessMenuItem('system.salutations'));

        // Aksi standar TIDAK ikut melebar: View tidak memberi Update/Delete.
        $this->assertFalse($user->hasPermission('operational.job-schedules.update'));
        $this->assertFalse($user->hasPermission('operational.job-schedules.delete'));
        $this->assertFalse($user->hasPermission('operational.job-schedules-dpf.update'));
    }

    public function test_system_admin_pages_and_wrong_keys_are_guarded_by_menu_permissions(): void
    {
        $expect = [
            'system.roles.index' => 'permission:system.roles.*',
            'system.roles.update' => 'permission:system.roles.*',
            'system.roles.assign-users' => 'permission:system.roles.*',
            'system.users.store' => 'permission:system.users.*',
            'system.users.destroy' => 'permission:system.users.*',
            'system.department-roles.bulk-assign' => 'permission:system.roles.*',
            'system.role-groups.add-role' => 'permission:system.roles.*',
            'system.departments.update' => 'permission:system.departments.*',
            'system.positions.update' => 'permission:company.positions.*,system.positions.*',
            'settings.system.update' => 'permission:system.settings.*,settings.system.*',
            'audit-trails.index' => 'permission:system.audit-trails.*',
            'report.warehouse.index' => 'permission:reports.warehouse',
            'access-control.index' => 'permission:access-control.view,system.access-control,system.access-control.view',
            'warehouse.inventory-issuings.send' => 'permission:warehouse.inventory-issuings.update',
            'finance.invoices.upload' => 'permission:finance.invoices.update',
            'finance.finance.invoices.update-discount' => 'permission:finance.invoices.update',
            'finance.tax-settings.activate' => 'permission:tax-settings.edit,finance.tax-settings.update',
            'finance.faktur-pajak.approve' => 'permission:faktur-pajak.edit,finance.faktur-pajak.update',
        ];

        foreach ($expect as $name => $middleware) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, $name);
            $this->assertContains($middleware, $route->gatherMiddleware(), $name);
        }

        // Lookup yang dipakai modul lain tetap terbuka (hanya butuh login).
        foreach (['other.master-options.show', 'system.customer-types.api.list', 'system.provinces.cities'] as $name) {
            $route = Route::getRoutes()->getByName($name);
            $effective = array_diff($route->gatherMiddleware(), $route->excludedMiddleware());
            $guards = array_filter($effective, fn ($m) => str_starts_with((string) $m, 'permission:'));
            $this->assertSame([], array_values($guards), $name);
        }
    }

    public function test_saving_a_role_keeps_hidden_permissions_and_grants_every_permission_of_a_ticked_cell(): void
    {
        $roleId = $this->role('Operational Manager', [
            'other.legacy-thing.view',                       // tidak tampil di grid
            'operational.job-schedules.approve-ba',          // kalah tabrakan dgn ...approve-ba.view
        ]);
        $this->permissionId('operational.job-schedules.approve-ba.view');
        $this->permissionId('marketing.contracts.approve');
        $this->permissionId('marketing.contracts.approve-contract-net');
        $this->permissionId('system.dashboard.view');

        $controller = app(RoleController::class);
        $role = Role::find($roleId);

        $grid = (new \ReflectionMethod($controller, 'getGroupedPermissions'))->invoke($controller, $role, false);
        $this->assertSame('Dashboard Utama (Main Dashboard)', $grid['System']['dashboard']['name']);
        $this->assertSame($this->permissionId('marketing.contracts.approve'), $grid['Marketing']['contracts']['permissions']['ids']['approve']);
        $this->assertSame($this->permissionId('marketing.contracts.approve-contract-net'), $grid['Marketing']['contract-net']['permissions']['ids']['approve']);

        // Form Master Role mengirim id utama sel "Approve BA" + "Dashboard Utama".
        $approveBaPrimary = $grid['Operational']['job-schedules.approve-ba']['permissions']['ids']['view'];
        $submitted = [$approveBaPrimary, $this->permissionId('system.dashboard.view')];

        $final = (new \ReflectionMethod($controller, 'resolveSubmittedPermissionIds'))->invoke($controller, $role, $submitted);
        sort($final);

        $expected = [
            $this->permissionId('other.legacy-thing.view'),
            $this->permissionId('operational.job-schedules.approve-ba'),
            $this->permissionId('operational.job-schedules.approve-ba.view'),
            $this->permissionId('system.dashboard.view'),
        ];
        sort($expected);
        $this->assertSame($expected, $final);
    }

    public function test_grant_command_is_dry_run_by_default_and_applies_with_flag(): void
    {
        $this->permissionId(DashboardController::PERMISSION);
        $this->role('Marketing Manager');

        $this->artisan('permissions:grant', ['permission' => DashboardController::PERMISSION, '--role' => ['Marketing Manager']])
            ->expectsOutputToContain('DRY-RUN')
            ->assertSuccessful();
        $this->assertSame(0, DB::table('role_permissions')->count());

        $this->artisan('permissions:grant', ['permission' => DashboardController::PERMISSION, '--role' => ['Marketing Manager'], '--apply' => true])
            ->assertSuccessful();
        $this->assertSame(1, DB::table('role_permissions')->count());

        $this->artisan('permissions:grant', ['permission' => DashboardController::PERMISSION, '--role' => ['Tidak Ada'], '--apply' => true])
            ->assertFailed();

        $this->artisan('permissions:grant', ['permission' => DashboardController::PERMISSION, '--role' => ['Marketing Manager'], '--revoke' => true, '--apply' => true])
            ->assertSuccessful();
        $this->assertSame(0, DB::table('role_permissions')->count());
    }
}
