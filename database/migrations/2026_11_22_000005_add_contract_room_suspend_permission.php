<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MOM 19 Nov 2026 p6: izin Suspend/Unsuspend room di tab Room & Rental detail kontrak.
 * Admin/Management lolos otomatis (CheckPermission); role lain diberi lewat System Role.
 */
return new class extends Migration
{
    private const NAME = 'marketing.contract-room-suspend.update';

    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $now = now();
        $values = [
            'description' => 'Suspend / Unsuspend room kontrak (Room & Rental)',
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
