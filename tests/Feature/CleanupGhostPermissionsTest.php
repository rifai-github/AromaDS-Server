<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * UAT 17 Nov B5 + QA 23 Nov 2026: permission hantu di Master Roles dibersihkan; centang role/user
 * dipindah ke permission asli supaya hak akses tidak berubah.
 */
class CleanupGhostPermissionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['role_permissions', 'user_permission', 'permissions'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('permissions', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->boolean('system_reserved')->default(false);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('role_permissions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('role_id');
            $t->unsignedBigInteger('permission_id');
            $t->timestamps();
        });
        Schema::create('user_permission', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('permission_id');
        });

        DB::table('permissions')->insert([
            ['id' => 1, 'name' => 'system.salutations.view'],               // asli (dipakai sidebar)
            ['id' => 2, 'name' => 'marketing.system.salutations.view'],     // NESTED hantu
            ['id' => 3, 'name' => 'marketing.contract_files.approve'],      // asli (dipakai kode)
            ['id' => 4, 'name' => 'marketing.contract-files.approve'],      // TWIN hantu
            ['id' => 5, 'name' => 'zz-tidak-ada.modul-hantu.view'],         // UNUSED
        ]);
        DB::table('role_permissions')->insert([
            ['role_id' => 10, 'permission_id' => 2],  // hanya punya hantu -> dipindah ke #1
            ['role_id' => 11, 'permission_id' => 1],  // sudah punya asli
            ['role_id' => 11, 'permission_id' => 2],  // + hantu -> tak dobel
            ['role_id' => 12, 'permission_id' => 4],
        ]);
        DB::table('user_permission')->insert(['user_id' => 7, 'permission_id' => 4]);
    }

    protected function tearDown(): void
    {
        foreach (['role_permissions', 'user_permission', 'permissions'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_dry_run_reports_without_writing(): void
    {
        $this->artisan('permissions:cleanup-ghosts')
            ->expectsOutputToContain('Mode DRY-RUN')
            ->assertSuccessful();

        $this->assertSame(5, DB::table('permissions')->whereNull('deleted_at')->count());
        $this->assertSame(4, DB::table('role_permissions')->count());
    }

    public function test_apply_moves_links_to_the_real_permission_and_removes_ghosts(): void
    {
        $this->artisan('permissions:cleanup-ghosts', ['--apply' => true])->assertSuccessful();

        $this->assertNotNull(DB::table('permissions')->where('id', 2)->value('deleted_at'));
        $this->assertNotNull(DB::table('permissions')->where('id', 4)->value('deleted_at'));
        $this->assertNull(DB::table('permissions')->where('id', 1)->value('deleted_at'));
        $this->assertNull(DB::table('permissions')->where('id', 3)->value('deleted_at'));

        $this->assertSame([1], DB::table('role_permissions')->where('role_id', 10)->pluck('permission_id')->map(fn ($id) => (int) $id)->all());
        $this->assertSame([1], DB::table('role_permissions')->where('role_id', 11)->pluck('permission_id')->map(fn ($id) => (int) $id)->all());
        $this->assertSame([3], DB::table('role_permissions')->where('role_id', 12)->pluck('permission_id')->map(fn ($id) => (int) $id)->all());
        $this->assertSame([3], DB::table('user_permission')->where('user_id', 7)->pluck('permission_id')->map(fn ($id) => (int) $id)->all());

        // UNUSED tak disentuh tanpa --deactivate-unused.
        $this->assertTrue((bool) DB::table('permissions')->where('id', 5)->value('is_active'));
    }

    public function test_deactivate_unused_only_flips_is_active(): void
    {
        $this->artisan('permissions:cleanup-ghosts', ['--apply' => true, '--deactivate-unused' => true])->assertSuccessful();

        $this->assertFalse((bool) DB::table('permissions')->where('id', 5)->value('is_active'));
        $this->assertNull(DB::table('permissions')->where('id', 5)->value('deleted_at'));
    }
}
