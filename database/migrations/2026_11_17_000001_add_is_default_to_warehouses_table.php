<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-warehouse per branch (docs/warehouse-opsi3-implementation-plan.md).
 *
 * A branch may run several equivalent ready-stock warehouses. Exactly one
 * active warehouse per branch is flagged `is_default`: it is the warehouse the
 * system uses whenever it has to pick one on its own (auto-created material
 * issues, receiving without an issuing, ...). Admins can still choose another
 * warehouse of the same branch.
 *
 * Backfill: per branch, the lowest-id active warehouse becomes the default.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('warehouses', 'is_default')) {
            Schema::table('warehouses', function (Blueprint $table) {
                $table->boolean('is_default')->default(false)->after('is_center')->index();
            });
        }

        $branchIds = DB::table('warehouses')
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->whereNotNull('branch_id')
            ->distinct()
            ->pluck('branch_id');

        foreach ($branchIds as $branchId) {
            $active = DB::table('warehouses')
                ->whereNull('deleted_at')
                ->where('is_active', true)
                ->where('branch_id', $branchId);

            if ((clone $active)->where('is_default', true)->count() === 1) {
                continue;
            }

            $defaultId = (clone $active)->min('id');

            DB::table('warehouses')->where('branch_id', $branchId)->update(['is_default' => false]);
            DB::table('warehouses')->where('id', $defaultId)->update(['is_default' => true]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('warehouses', 'is_default')) {
            Schema::table('warehouses', function (Blueprint $table) {
                $table->dropIndex(['is_default']);
                $table->dropColumn('is_default');
            });
        }
    }
};
