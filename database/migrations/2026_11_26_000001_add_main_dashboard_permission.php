<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * QA 26 Nov 2026: menu Dashboard utama (/dashboard) tidak ada di Master Role, sehingga tidak bisa
 * disembunyikan untuk non-admin selama sosialisasi & implementasi. Izin ini mengatur Dashboard
 * utama (Master Role > System > Dashboard > View).
 *
 * Sengaja TIDAK otomatis diberikan ke role mana pun: Admin & Management tetap melihatnya, role
 * lain diberi lewat Master Role bila sudah waktunya. User tanpa izin diarahkan ke dashboard
 * modulnya atau halaman profil (DashboardController::landingUrlFor), bukan error.
 */
return new class extends Migration
{
    private const NAME = 'system.dashboard.view';

    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $now = now();
        $values = [
            'description' => 'View main Dashboard',
            'is_active' => true,
            'updated_at' => $now,
        ];

        if (Schema::hasColumn('permissions', 'system_reserved')) {
            $values['system_reserved'] = false;
        }

        if (DB::table('permissions')->where('name', self::NAME)->exists()) {
            DB::table('permissions')->where('name', self::NAME)->update($values);

            return;
        }

        DB::table('permissions')->insert(array_merge($values, [
            'name' => self::NAME,
            'created_at' => $now,
        ]));
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionId = DB::table('permissions')->where('name', self::NAME)->value('id');
        if (! $permissionId) {
            return;
        }

        if (Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')->where('permission_id', $permissionId)->delete();
        }

        DB::table('permissions')->where('id', $permissionId)->delete();
    }
};
