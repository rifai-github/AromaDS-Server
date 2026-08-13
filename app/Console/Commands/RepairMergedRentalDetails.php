<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairMergedRentalDetails extends Command
{
    protected $signature = 'catalyst:repair-merged-rental-details
                            {--apply : Apply the repair (default is dry-run)}
                            {--rental= : Only inspect a specific master_rentals.rental_code}';

    protected $description = 'Find rental_details rows that multiple distinct Catalyst MsRentalBOM components were accidentally merged into (via a too-coarse fallback match on null category/type), and free up the map so a re-import can recreate the missing rows';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        if (!$apply) {
            $this->info('DRY RUN mode. No database changes will be made. Re-run with --apply to persist repairs.');
        }

        $rentalFilter = trim((string) $this->option('rental'));

        $groups = DB::table('source_import_maps')
            ->where('source_system', 'catalyst')
            ->where('source_table', 'MsRentalBOM')
            ->where('target_table', 'rental_details')
            ->select('target_id', DB::raw('COUNT(DISTINCT source_key) as source_key_count'))
            ->groupBy('target_id')
            ->havingRaw('COUNT(DISTINCT source_key) > 1')
            ->pluck('target_id');

        if ($groups->isEmpty()) {
            $this->info('No merged rental_details rows found (every target_id maps from exactly one source_key).');

            return self::SUCCESS;
        }

        $rows = [];
        $plans = [];

        foreach ($groups as $targetId) {
            $detail = DB::table('rental_details as rd')
                ->leftJoin('master_rentals as mr', 'mr.id', '=', 'rd.master_rental_id')
                ->where('rd.id', $targetId)
                ->select('rd.id', 'rd.master_rental_id', 'mr.rental_code', 'mr.rental_name', 'rd.product_category_id', 'rd.product_type_id', 'rd.master_product_id')
                ->first();

            if (!$detail) {
                continue;
            }

            if ($rentalFilter !== '' && stripos((string) $detail->rental_code, $rentalFilter) === false) {
                continue;
            }

            $maps = DB::table('source_import_maps')
                ->where('source_system', 'catalyst')
                ->where('source_table', 'MsRentalBOM')
                ->where('target_table', 'rental_details')
                ->where('target_id', $targetId)
                ->orderBy('id')
                ->get(['id', 'source_key']);

            // Keep the most recently written map (its data is what the row currently
            // reflects); free up the others so they re-insert as separate rows next
            // time "Apply Master Rental Import" runs.
            $keep = $maps->last();
            $toRelease = $maps->filter(fn ($m) => $m->id !== $keep->id);

            foreach ($toRelease as $released) {
                $rows[] = [
                    $apply ? 'RELEASED' : 'WILL RELEASE',
                    $detail->rental_code ?? $detail->master_rental_id,
                    $targetId,
                    $released->source_key,
                    'keeping map id ' . $keep->id . ' (source_key: ' . $keep->source_key . ')',
                ];
                $plans[] = $released->id;
            }
        }

        if ($rows === []) {
            $this->info($rentalFilter !== '' ? 'No merged rows matched that rental filter.' : 'No merged rows to repair.');

            return self::SUCCESS;
        }

        if ($apply) {
            DB::table('source_import_maps')->whereIn('id', $plans)->delete();
        }

        $this->table(
            ['Status', 'Rental Code', 'rental_details.id', 'Released source_key', 'Note'],
            $rows
        );

        $this->line('Merged groups found : ' . $groups->count());
        $this->line('Map rows released   : ' . ($apply ? count($plans) : count($plans) . ' (dry-run)'));
        $this->line('');
        $this->line('The existing rental_details rows above were NOT deleted or changed - only the');
        $this->line('duplicate import-map entries were removed. Re-run "Apply Master Rental Import"');
        $this->line('(catalyst:import-masters --step=master_rentals --step=rental_components --step=rental_details --exact-steps --apply)');
        $this->line('afterwards so the freed-up components get (re)created as their own separate rows.');

        if (!$apply) {
            $this->line('Dry run only. Re-run with --apply after reviewing the rows above.');
        }

        return self::SUCCESS;
    }
}
