<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * UAT 19 Nov 2026: role "PIC Info". Hanya user dengan izin ini (atau Admin/Management) yang
 * melihat email & telepon PIC di detail kontrak; lainnya hanya melihat nama PIC.
 *
 * Sengaja TIDAK otomatis diberikan ke role mana pun: admin memilih role yang boleh melihat
 * lewat System Role (Marketing > Contract Pic Info > View).
 */
return new class extends Migration
{
    private const NAME = 'marketing.contract-pic-info.view';

    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $now = now();
        $values = [
            'description' => 'View PIC email & phone on contracts (PIC Info)',
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
