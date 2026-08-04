<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nobody should lose the ability to approve on the day tiering ships.
     *
     * Every role that already holds the blanket `marketing.quotations.approve`
     * is granted the most senior level, which is exactly the authority they had
     * before. Admins can then demote them deliberately from the master screen.
     */
    public function up(): void
    {
        foreach (['permissions', 'roles', 'role_permissions', 'quotation_approval_levels'] as $table) {
            if (! Schema::hasTable($table)) {
                return;
            }
        }

        $legacyPermissionId = DB::table('permissions')
            ->where('name', 'marketing.quotations.approve')
            ->value('id');

        if (! $legacyPermissionId) {
            return;
        }

        $topLevelPermissionName = DB::table('quotation_approval_levels')
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->orderByDesc('max_discount_percentage')
            ->value('permission_name');

        if (! $topLevelPermissionName) {
            return;
        }

        $topLevelPermissionId = DB::table('permissions')
            ->where('name', $topLevelPermissionName)
            ->value('id');

        if (! $topLevelPermissionId) {
            return;
        }

        $roleIds = DB::table('role_permissions')
            ->where('permission_id', $legacyPermissionId)
            ->pluck('role_id');

        $now = now();

        foreach ($roleIds as $roleId) {
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $roleId, 'permission_id' => $topLevelPermissionId],
                $this->timestamps($now)
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('role_permissions') || ! Schema::hasTable('quotation_approval_levels')) {
            return;
        }

        $levelPermissionNames = DB::table('quotation_approval_levels')->pluck('permission_name');

        $permissionIds = DB::table('permissions')
            ->whereIn('name', $levelPermissionNames)
            ->pluck('id');

        DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
    }

    private function timestamps($now): array
    {
        $timestamps = [];

        foreach (['created_at', 'updated_at'] as $column) {
            if (Schema::hasColumn('role_permissions', $column)) {
                $timestamps[$column] = $now;
            }
        }

        return $timestamps;
    }
};
