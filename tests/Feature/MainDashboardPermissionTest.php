<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardController;
use App\Models\User;
use Mockery;
use Tests\TestCase;

/**
 * QA 26 Nov 2026: Dashboard utama disembunyikan untuk non-admin dan diatur lewat Master Role
 * (system.dashboard.view). User tanpa izin tidak boleh terkunci: diarahkan ke dashboard modul
 * yang ia punya, atau profil.
 */
class MainDashboardPermissionTest extends TestCase
{
    private function user(array $roles = [], array $permissions = [], bool $management = false): User
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasRole')->andReturnUsing(fn ($role) => in_array($role, $roles, true));
        $user->shouldReceive('hasRoleStartingWith')->andReturn($management);
        $user->shouldReceive('hasPermission')->andReturnUsing(fn ($permission) => in_array($permission, $permissions, true));

        return $user;
    }

    public function test_admin_management_and_permitted_roles_see_the_main_dashboard(): void
    {
        $this->assertTrue(DashboardController::canView($this->user(['Admin'])));
        $this->assertTrue(DashboardController::canView($this->user([], [], true)));
        $this->assertTrue(DashboardController::canView($this->user([], [DashboardController::PERMISSION])));
        $this->assertFalse(DashboardController::canView($this->user(['Teknisi'])));
        $this->assertFalse(DashboardController::canView(null));
    }

    public function test_users_without_access_land_on_a_page_they_can_open_never_on_dashboard(): void
    {
        $this->assertSame(route('marketing.dashboard'), DashboardController::landingUrlFor($this->user([], ['marketing.dashboard.view'])));
        $this->assertSame(route('profile.index'), DashboardController::landingUrlFor($this->user(['Teknisi'])));
        $this->assertNotSame(route('dashboard'), DashboardController::landingUrlFor($this->user(['Teknisi'])));
    }
}
