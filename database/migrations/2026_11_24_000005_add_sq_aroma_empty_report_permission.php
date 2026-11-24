<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Klien 24 Nov 2026: laporan SQ Aroma Kosong di menu Report. Admin/Management lolos otomatis
 * (CheckPermission); role lain diberi lewat System Role.
 */
return new class extends Migration
{
    private const NAME = 'reports.sq-aroma-empty';

    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $values = ['description' => 'Report: SQ Aroma Kosong', 'is_active' => true, 'updated_at' => now()];
        if (Schema::hasColumn('permissions', 'system_reserved')) {
            $values['system_reserved'] = false;
        }

        if (DB::table('permissions')->where('name', self::NAME)->exists()) {
            DB::table('permissions')->where('name', self::NAME)->update($values);

            return;
        }

        DB::table('permissions')->insert(array_merge($values, ['name' => self::NAME, 'created_at' => now()]));
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $id = DB::table('permissions')->where('name', self::NAME)->value('id');
        if ($id && Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')->where('permission_id', $id)->delete();
        }
        DB::table('permissions')->where('name', self::NAME)->delete();
    }
};
