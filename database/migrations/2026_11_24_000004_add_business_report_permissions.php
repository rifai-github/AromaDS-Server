<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Spec Halaman Report 24 Nov 2026: satu izin per laporan di menu Report. Admin/Management lolos
 * otomatis (CheckPermission); role lain diberi lewat System Role.
 */
return new class extends Migration
{
    private const PERMISSIONS = [
        'reports.contract-list' => 'Report: List Kontrak',
        'reports.suspend' => 'Report: Report Suspend',
        'reports.lock-service' => 'Report: Potensi Lock Service',
        'reports.agreement-sign' => 'Report: Agreement Sign',
        'reports.rental-service' => 'Report: Rental Report',
        'reports.generate-invoice' => 'Report: Report Generate Invoice',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $now = now();
        $hasReserved = Schema::hasColumn('permissions', 'system_reserved');

        foreach (self::PERMISSIONS as $name => $description) {
            $values = ['description' => $description, 'is_active' => true, 'updated_at' => $now];
            if ($hasReserved) {
                $values['system_reserved'] = false;
            }

            if (DB::table('permissions')->where('name', $name)->exists()) {
                DB::table('permissions')->where('name', $name)->update($values);

                continue;
            }

            DB::table('permissions')->insert(array_merge($values, ['name' => $name, 'created_at' => $now]));
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $ids = DB::table('permissions')->whereIn('name', array_keys(self::PERMISSIONS))->pluck('id');
        if (Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        }
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
