<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $now = now();
        $permissionNames = [
            'warehouse.stock-opnames' => 'Access Stock Opnames',
            'warehouse.stock-opnames.view' => 'View Stock Opnames',
            'warehouse.stock-opnames.create' => 'Create Stock Opnames',
            'warehouse.stock-opnames.update' => 'Update Stock Opnames',
        ];

        foreach ($permissionNames as $name => $description) {
            $values = [
                'description' => $description,
                'is_active' => true,
                'updated_at' => $now,
            ];

            if (Schema::hasColumn('permissions', 'system_reserved')) {
                $values['system_reserved'] = false;
            }

            if (DB::table('permissions')->where('name', $name)->exists()) {
                DB::table('permissions')->where('name', $name)->update($values);

                continue;
            }

            DB::table('permissions')->insert(array_merge($values, [
                'name' => $name,
                'created_at' => $now,
            ]));
        }

        if (! Schema::hasTable('roles') || ! Schema::hasTable('role_permissions')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('name', array_keys($permissionNames))
            ->pluck('id');

        $roleIds = DB::table('roles')
            ->where('name', 'like', 'Warehouse%')
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ], $this->rolePermissionTimestamps($now));
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionId = DB::table('permissions')
            ->where('name', 'warehouse.stock-opnames.view')
            ->value('id');

        if (! $permissionId) {
            return;
        }

        if (Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')->where('permission_id', $permissionId)->delete();
        }

        DB::table('permissions')->where('id', $permissionId)->delete();
    }

    private function rolePermissionTimestamps($now): array
    {
        $timestamps = [];

        if (Schema::hasColumn('role_permissions', 'created_at')) {
            $timestamps['created_at'] = $now;
        }

        if (Schema::hasColumn('role_permissions', 'updated_at')) {
            $timestamps['updated_at'] = $now;
        }

        return $timestamps;
    }
};
