<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('warehouse_types')) {
            return;
        }

        $branchTypeId = $this->ensureWarehouseType(
            'BRANCH',
            'Branch Warehouse',
            'Default type for single warehouse per branch flow.'
        );

        $centerTypeId = $this->ensureWarehouseType(
            'CENTER',
            'Central Warehouse',
            'Default type for central warehouse locations.'
        );

        if (! Schema::hasTable('warehouses') || ! Schema::hasColumn('warehouses', 'warehouse_type_id')) {
            return;
        }

        $conditionTypeIds = DB::table('warehouse_types')
            ->whereIn('name', ['Baru', 'Bekas', 'Rusak', 'Spare Part'])
            ->pluck('id');

        if ($conditionTypeIds->isEmpty()) {
            return;
        }

        if (Schema::hasColumn('warehouses', 'is_center')) {
            DB::table('warehouses')
                ->where('is_center', false)
                ->whereIn('warehouse_type_id', $conditionTypeIds)
                ->update(['warehouse_type_id' => $branchTypeId]);

            DB::table('warehouses')
                ->where('is_center', true)
                ->whereIn('warehouse_type_id', $conditionTypeIds)
                ->update(['warehouse_type_id' => $centerTypeId]);

            return;
        }

        DB::table('warehouses')
            ->whereIn('warehouse_type_id', $conditionTypeIds)
            ->update(['warehouse_type_id' => $branchTypeId]);
    }

    public function down(): void
    {
        // Intentionally keep the neutral warehouse types and backfilled references.
        // Restoring Baru/Bekas/Rusak as warehouse types would reintroduce the old model.
    }

    private function ensureWarehouseType(string $code, string $name, string $description): int
    {
        $query = DB::table('warehouse_types');

        if (Schema::hasColumn('warehouse_types', 'code')) {
            $query->where('code', $code);
        } else {
            $query->where('name', $name);
        }

        $existing = $query->first();

        $values = [
            'name' => $name,
            'is_active' => true,
        ];

        if (Schema::hasColumn('warehouse_types', 'code')) {
            $values['code'] = $code;
        }

        if (Schema::hasColumn('warehouse_types', 'description')) {
            $values['description'] = $description;
        }

        if (Schema::hasColumn('warehouse_types', 'updated_at')) {
            $values['updated_at'] = now();
        }

        if ($existing) {
            DB::table('warehouse_types')->where('id', $existing->id)->update($values);

            return (int) $existing->id;
        }

        if (Schema::hasColumn('warehouse_types', 'created_at')) {
            $values['created_at'] = now();
        }

        return (int) DB::table('warehouse_types')->insertGetId($values);
    }
};
