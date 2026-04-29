<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditCatalystSyncHealth extends Command
{
    protected $signature = 'catalyst:audit-sync-health';

    protected $description = 'Audit health import Catalyst untuk modul warehouse dan system';

    public function handle(): int
    {
        $importedBranchIds = $this->mappedIds('MsBranch', 'branches');
        $importedDepartmentIds = $this->mappedIds('MsDepartment', 'departments');
        $importedUserIds = $this->mappedIds('MsEmployee', 'users');
        $importedProductIds = $this->mappedIds('MsProduct', 'master_products');
        $importedRentalIds = $this->mappedIds('MsProduct', 'master_rentals');
        $existingImportedUserIds = count($importedUserIds)
            ? DB::table('users')->whereIn('id', $importedUserIds)->whereNull('deleted_at')->pluck('id')->map(fn ($id) => (int) $id)->all()
            : [];
        $activeImportedRentalDetailQuery = DB::table('rental_details')
            ->whereIn('master_rental_id', $importedRentalIds)
            ->whereNull('deleted_at');

        $warehouseRows = [
            ['Imported Product Categories', $this->mappedCount('MsProductCategoryInfo', 'product_categories')],
            ['Imported Product Types', $this->mappedCount('MsProductType', 'product_types')],
            ['Imported Warehouses', count($this->mappedIds('MsWarehouse', 'warehouses'))],
            ['Imported Master Products', count($importedProductIds)],
            ['Imported Products With Category', count($importedProductIds) ? DB::table('master_products')->whereIn('id', $importedProductIds)->whereNotNull('product_category_id')->count() : 0],
            ['Imported Products With Warehouse Link', count($importedProductIds) ? DB::table('warehouse_products')->whereIn('master_product_id', $importedProductIds)->distinct('master_product_id')->count('master_product_id') : 0],
            ['Imported Products With Brand+Variant', count($importedProductIds) ? DB::table('master_products')->whereIn('id', $importedProductIds)->whereNotNull('brand_line')->where('brand_line', '!=', '')->whereNotNull('variant_name')->where('variant_name', '!=', '')->count() : 0],
            ['Imported Master Rentals', count($importedRentalIds)],
            ['Imported Rentals With Service Frequency', count($importedRentalIds) ? DB::table('master_rentals')->whereIn('id', $importedRentalIds)->whereNotNull('service_frequency_id')->count() : 0],
            ['Imported Rental Details', (clone $activeImportedRentalDetailQuery)->count()],
            ['Rental Details With Master Product', (clone $activeImportedRentalDetailQuery)->whereNotNull('master_product_id')->count()],
            ['Rental Details With Material Options', (clone $activeImportedRentalDetailQuery)->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('rental_detail_materials as rdm')
                    ->whereColumn('rdm.rental_detail_id', 'rental_details.id');
            })->count()],
            ['Rental Details With Selected Product', (clone $activeImportedRentalDetailQuery)->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('rental_detail_materials as rdm')
                    ->whereColumn('rdm.rental_detail_id', 'rental_details.id')
                    ->where('rdm.is_selected', 1);
            })->count()],
            ['Rental Details Multi-Option Pending Default', (clone $activeImportedRentalDetailQuery)->whereNull('master_product_id')->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('rental_detail_materials as rdm')
                    ->whereColumn('rdm.rental_detail_id', 'rental_details.id');
            })->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('rental_detail_materials as rdm')
                    ->whereColumn('rdm.rental_detail_id', 'rental_details.id')
                    ->where('rdm.is_selected', 1);
            })->count()],
            ['Rental Details Without Any Material Match', (clone $activeImportedRentalDetailQuery)->whereNull('master_product_id')->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('rental_detail_materials as rdm')
                    ->whereColumn('rdm.rental_detail_id', 'rental_details.id');
            })->count()],
        ];

        $systemRows = [
            ['Imported Branches', count($importedBranchIds)],
            ['Imported Departments', count($importedDepartmentIds)],
            ['Imported Users', count($existingImportedUserIds)],
            ['Users With Branch ID', count($existingImportedUserIds) ? DB::table('users')->whereIn('id', $existingImportedUserIds)->whereNotNull('branch_id')->count() : 0],
            ['Users With Department ID', count($existingImportedUserIds) ? DB::table('users')->whereIn('id', $existingImportedUserIds)->whereNotNull('department_id')->count() : 0],
            ['Users With Branch Pivot', count($existingImportedUserIds) ? DB::table('branch_user')->whereIn('user_id', $existingImportedUserIds)->distinct('user_id')->count('user_id') : 0],
            ['Users With Primary Branch Pivot', count($existingImportedUserIds) ? DB::table('branch_user')->whereIn('user_id', $existingImportedUserIds)->where('is_primary', 1)->distinct('user_id')->count('user_id') : 0],
        ];

        $this->info('Warehouse Audit');
        $this->table(['Metric', 'Value'], $warehouseRows);

        $this->newLine();
        $this->info('System Audit');
        $this->table(['Metric', 'Value'], $systemRows);

        $this->newLine();
        $this->warn('Role tidak diaudit dari Catalyst karena role harus tetap mengikuti system KGI.');

        return self::SUCCESS;
    }

    private function mappedCount(string $sourceTable, string $targetTable): int
    {
        return DB::table('source_import_maps')
            ->where('source_system', 'catalyst')
            ->where('source_table', $sourceTable)
            ->where('target_table', $targetTable)
            ->count();
    }

    private function mappedIds(string $sourceTable, string $targetTable): array
    {
        return DB::table('source_import_maps')
            ->where('source_system', 'catalyst')
            ->where('source_table', $sourceTable)
            ->where('target_table', $targetTable)
            ->pluck('target_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
