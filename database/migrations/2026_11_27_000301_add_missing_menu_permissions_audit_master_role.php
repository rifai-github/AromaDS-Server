<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Audit Master Role 27 Nov 2026: izin yang sudah DIPERIKSA kode (menu sidebar / route / halaman
 * pertama setelah login) tetapi tidak pernah ada di tabel permissions, sehingga tidak bisa
 * dicentang di Master Role dan menunya hanya terbuka untuk Admin/Management.
 *
 * Sengaja TIDAK diberikan ke role mana pun — klien yang memutuskan lewat Master Role atau
 * `php artisan permissions:grant`.
 */
return new class extends Migration
{
    private const PERMISSIONS = [
        'marketing.stock-view.view' => 'Stock View - lihat (Marketing > Stock View)',
        'system.settings.view' => 'System Settings - lihat (System > System Settings)',
        'operational.dashboard.view' => 'Dashboard Operational - lihat (halaman awal setelah login)',
        'finance.dashboard.view' => 'Dashboard Finance - lihat (halaman awal setelah login)',
        'warehouse.dashboard.view' => 'Dashboard Warehouse - lihat (halaman awal setelah login)',
    ];

    private const RELABEL = [
        'system.dashboard.view' => 'Dashboard Utama - lihat (halaman /dashboard; Admin & Management selalu bisa)',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $now = now();
        $hasReserved = Schema::hasColumn('permissions', 'system_reserved');

        foreach (self::PERMISSIONS as $name => $description) {
            if (DB::table('permissions')->where('name', $name)->exists()) {
                continue;
            }

            $row = [
                'name' => $name,
                'description' => $description,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if ($hasReserved) {
                $row['system_reserved'] = false;
            }
            DB::table('permissions')->insert($row);
        }

        foreach (self::RELABEL as $name => $description) {
            DB::table('permissions')->where('name', $name)->update(['description' => $description, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $ids = DB::table('permissions')->whereIn('name', array_keys(self::PERMISSIONS))->pluck('id');
        if ($ids->isEmpty()) {
            return;
        }

        if (Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        }
        if (Schema::hasTable('user_permission')) {
            DB::table('user_permission')->whereIn('permission_id', $ids)->delete();
        }
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
