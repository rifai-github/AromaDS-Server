<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `is_active` used to be a manual checkbox, so a branch/offer_type could
     * end up with zero or several active rows and every reader (`::active()->first()`,
     * or no filter at all) just picked whichever row sorted first — not
     * necessarily the latest negotiated floor. From now on exactly one row per
     * (master_rental_id, branch_id, offer_type) group stays active, chosen by
     * most recent updated_at. This backfills existing data to that rule
     * without touching updated_at itself.
     */
    public function up(): void
    {
        if (! Schema::hasTable('rental_bottom_prices')) {
            return;
        }

        $groups = DB::table('rental_bottom_prices')
            ->whereNull('deleted_at')
            ->select('master_rental_id', 'branch_id', 'offer_type')
            ->distinct()
            ->get();

        foreach ($groups as $group) {
            $latestId = DB::table('rental_bottom_prices')
                ->whereNull('deleted_at')
                ->where('master_rental_id', $group->master_rental_id)
                ->where('branch_id', $group->branch_id)
                ->where('offer_type', $group->offer_type)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->value('id');

            if (! $latestId) {
                continue;
            }

            DB::table('rental_bottom_prices')
                ->whereNull('deleted_at')
                ->where('master_rental_id', $group->master_rental_id)
                ->where('branch_id', $group->branch_id)
                ->where('offer_type', $group->offer_type)
                ->where('id', '!=', $latestId)
                ->update(['is_active' => false]);

            DB::table('rental_bottom_prices')->where('id', $latestId)->update(['is_active' => true]);
        }
    }

    public function down(): void
    {
        // Not reversible: the prior state (which rows were manually flagged
        // active) is not recoverable.
    }
};
