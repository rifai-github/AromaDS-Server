<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Persiapan import ulang Catalyst 29 Nov 2026 ("bersihkan data di KGI, import data dari
 * Catalyst" — jawaban klien no. 20).
 *
 * Reset sebelumnya (3 & 12 Sep) memuat ulang aroma_fresh_bootstrap.sql. Itu menghapus SEMUA
 * tabel, termasuk role, permission, dan user yang sudah diatur di prod, padahal tim meminta
 * "master role dan user jangan sampai kena wipe" (WA 25 Nov). Perintah ini hanya mengosongkan
 * tabel TRANSAKSI yang tercantum eksplisit di CLEAR_TABLES; semua tabel lain (user, role,
 * permission, cabang, departemen, tim, gudang, produk, rental + BOM, Master Company, master
 * option, bank, pajak, wilayah, level komisi/approval) tetap utuh.
 *
 * Tabel yang tidak tercantum di daftar mana pun TIDAK pernah dihapus — dry-run menampilkannya
 * sebagai "belum diklasifikasi" supaya bisa diputuskan manual.
 *
 * Peta import (source_import_maps) hanya dihapus untuk tabel tujuan yang dikosongkan, jadi
 * import berikutnya tetap mengenali user/cabang/departemen yang sudah ada (tidak dobel).
 */
class CleanTransactionalDataForCatalystReimport extends Command
{
    protected $signature = 'catalyst:clean-transactional-data
        {--apply : Benar-benar mengosongkan tabel (default dry-run)}
        {--force : Lewati konfirmasi interaktif saat --apply}';

    protected $description = 'Kosongkan data transaksi (customer, kontrak, job, stok, invoice) sebelum import ulang Catalyst; user, role, dan master tetap (dry-run default)';

    /**
     * Data transaksi / data uji yang dikosongkan. Urutan tidak penting (FK dimatikan sementara).
     */
    public const CLEAR_TABLES = [
        // Customer & lokasi
        'customers', 'customer_contacts', 'customer_customer_contact', 'customer_tax_settings',
        'company_virtual_accounts', 'buildings', 'building_customers', 'master_rooms',
        // Marketing
        'prospects', 'marketing_pipelines',
        'surveys', 'survey_details', 'survey_revisions',
        'quotations', 'quotation_details', 'quotation_rentals', 'quotation_rooms', 'quotation_surveys', 'quotation_approvals',
        'contracts', 'contract_assigned', 'contract_buildings', 'contract_files', 'contract_renewals', 'contract_rentals',
        'contract_room_suspensions', 'contract_rooms', 'contract_signature_requests', 'contract_surveys',
        'contract_switchings', 'contract_terminations', 'renewal_contract_assignments', 'aroma_changes',
        'billing_groups', 'billing_group_buildings',
        // Operasional
        'job_advices', 'job_advice_rooms',
        'job_schedules', 'job_schedule_rooms', 'job_schedule_room_rentals', 'job_schedule_room_assignments',
        'job_schedule_units', 'job_schedule_ba_files', 'job_assign_schedules', 'job_assign_material_issues',
        'job_photos', 'job_reports', 'job_team_locations', 'lost_unit_reports',
        'material_issues', 'material_issue_items',
        'serial_numbers', 'unit_on_walls', 'unit_on_wall_histories',
        // Gudang (stok uji)
        'inventory_issuings', 'inventory_issuing_items', 'inventory_issuing_item_serials', 'inventory_movements',
        'inventory_receivings', 'inventory_receiving_items', 'inventory_requests', 'inventory_request_items',
        'inventory_transfers', 'inventory_transfer_items', 'inventory_transfer_approval_histories',
        'stock_adjustments', 'stock_adjustment_items', 'stock_opnames', 'stock_opname_details', 'warehouse_products',
        // Keuangan
        'invoices', 'invoice_activities', 'invoice_files', 'invoice_rental_details',
        'tax_file_exports', 'tax_file_imports', 'tax_file_import_details',
        'commission_calculations', 'commission_payments', 'commission_transfers', 'achievements',
        // Log teknis
        'mobile_sync_logs', 'api_performance_logs',
        // Jejak import lama (peta untuk tabel di atas dihapus terpisah, lihat handle())
        'source_import_logs', 'source_import_batches', 'catalyst_migration_runs',
    ];

    /**
     * Tabel yang sengaja DIPERTAHANKAN. Hanya dipakai untuk laporan dry-run (semua tabel di luar
     * CLEAR_TABLES memang tidak disentuh).
     */
    public const KEEP_TABLES = [
        'users', 'roles', 'permissions', 'role_permissions', 'user_roles', 'user_permission', 'user_access_levels',
        'department_roles', 'position_roles', 'branch_user', 'user_login_restrictions', 'user_sessions',
        'personal_access_tokens', 'login_histories', 'audit_logs', 'migrations',
        'branches', 'departments', 'companies', 'master_corporates', 'operational_areas', 'teams', 'team_members',
        'warehouses', 'banks', 'bank_payments',
        'master_options', 'option_details', 'master_products', 'master_rentals', 'rental_components', 'rental_details',
        'rental_detail_materials', 'rental_service_frequencies', 'rental_bottom_prices',
        'product_categories', 'product_types', 'product_brand_variants', 'packaging_sizes',
        'customer_categories', 'customer_types', 'tax_settings', 'finance_tax_codes', 'file_categories',
        'emergency_escalation_rules', 'emergency_templates',
        'quotation_approval_levels', 'quotation_approval_level_users',
        'commission_levels', 'marketing_levels', 'marketing_targets', 'achievement_periods', 'cr_variables',
        'cities', 'districts', 'subdistricts', 'provinces', 'indonesia_regions',
        'master_products_cat_backup_20260920', 'rental_details_bom_backup_20260920',
        'source_import_maps',
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $allTables = $this->allTables();

        $clear = array_values(array_filter(self::CLEAR_TABLES, fn ($table) => in_array($table, $allTables, true)));
        $unclassified = array_values(array_diff($allTables, self::CLEAR_TABLES, self::KEEP_TABLES));

        $rows = [];
        foreach ($clear as $table) {
            $rows[] = [$table, number_format(DB::table($table)->count()), 'DIKOSONGKAN'];
        }
        $this->table(['Tabel', 'Baris', 'Aksi'], $rows);

        $mapsToDelete = Schema::hasTable('source_import_maps')
            ? DB::table('source_import_maps')->whereIn('target_table', $clear)->count()
            : 0;
        $this->line('Peta import (source_import_maps) untuk tabel di atas yang ikut dihapus: '.number_format($mapsToDelete));

        $kept = [];
        foreach (self::KEEP_TABLES as $table) {
            if (in_array($table, $allTables, true)) {
                $kept[] = $table.' ('.number_format(DB::table($table)->count()).')';
            }
        }
        $this->info('Dipertahankan: '.implode(', ', $kept));

        $nonEmptyUnclassified = [];
        foreach ($unclassified as $table) {
            $count = DB::table($table)->count();
            if ($count > 0) {
                $nonEmptyUnclassified[] = $table.' ('.number_format($count).')';
            }
        }
        if ($nonEmptyUnclassified !== []) {
            $this->warn('Belum diklasifikasi dan berisi data (TIDAK disentuh, putuskan manual): '.implode(', ', $nonEmptyUnclassified));
        }

        if (! $apply) {
            $this->warn('Dry-run: tidak ada yang dihapus. Jalankan dengan --apply untuk mengosongkan tabel di atas.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Kosongkan '.count($clear).' tabel transaksi di atas? Pastikan sudah backup database.')) {
            $this->warn('Dibatalkan.');

            return self::FAILURE;
        }

        Schema::disableForeignKeyConstraints();
        try {
            if ($mapsToDelete > 0) {
                DB::table('source_import_maps')->whereIn('target_table', $clear)->delete();
            }

            foreach ($clear as $table) {
                DB::table($table)->truncate();
                $this->line("  {$table} dikosongkan");
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->info('Selesai: '.count($clear).' tabel transaksi dikosongkan; user, role, dan master tetap.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function allTables(): array
    {
        return collect(Schema::getTables())
            ->pluck('name')
            ->map(fn ($name) => (string) $name)
            ->reject(fn ($name) => str_starts_with($name, 'sqlite_'))
            ->values()
            ->all();
    }
}
