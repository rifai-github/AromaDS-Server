<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Jawaban klien 22 Nov 2026 (#21): PIC Info harus berupa ROLE di Master Role. User yang diberi
 * role "PIC Info" (sebagai role tambahan; user boleh punya banyak role) bisa melihat email &
 * telepon PIC kontrak. Role ini memegang permission marketing.contract-pic-info.view
 * (migration 2026_11_21_000001). Tidak ada user yang otomatis diberi role ini.
 */
return new class extends Migration
{
    private const ROLE = 'PIC Info';

    private const PERMISSION = 'marketing.contract-pic-info.view';

    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('permissions') || ! Schema::hasTable('role_permissions')) {
            return;
        }

        $now = now();

        $permissionId = DB::table('permissions')->where('name', self::PERMISSION)->value('id');
        if (! $permissionId) {
            $permissionValues = [
                'name' => self::PERMISSION,
                'description' => 'View PIC email & phone on contracts (PIC Info)',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if (Schema::hasColumn('permissions', 'system_reserved')) {
                $permissionValues['system_reserved'] = false;
            }
            $permissionId = DB::table('permissions')->insertGetId($permissionValues);
        }

        $role = DB::table('roles')->where('name', self::ROLE)->first();
        if ($role) {
            DB::table('roles')->where('id', $role->id)->update(['deleted_at' => null, 'is_active' => true, 'updated_at' => $now]);
            $roleId = $role->id;
        } else {
            $roleValues = [
                'name' => self::ROLE,
                'description' => 'Role tambahan: boleh melihat email & telepon PIC di kontrak',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if (Schema::hasColumn('roles', 'system_reserved')) {
                $roleValues['system_reserved'] = false;
            }
            $roleId = DB::table('roles')->insertGetId($roleValues);
        }

        $exists = DB::table('role_permissions')
            ->where('role_id', $roleId)
            ->where('permission_id', $permissionId)
            ->exists();

        if (! $exists) {
            DB::table('role_permissions')->insert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $roleId = DB::table('roles')->where('name', self::ROLE)->value('id');
        if (! $roleId) {
            return;
        }

        if (Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')->where('role_id', $roleId)->delete();
        }
        if (Schema::hasTable('user_roles')) {
            DB::table('user_roles')->where('role_id', $roleId)->delete();
        }

        DB::table('roles')->where('id', $roleId)->delete();
    }
};
