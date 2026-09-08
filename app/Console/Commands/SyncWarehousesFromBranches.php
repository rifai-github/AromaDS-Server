<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Services\Warehouse\BranchWarehouseProvisioner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Fills the warehouse master from the branch master: every branch that has no
 * warehouse yet gets one default warehouse derived from its own data.
 *
 * Intended for environments where `warehouses` is still empty (fresh bootstrap)
 * while `branches` is already filled. Branches that already have a warehouse are
 * skipped, so extra warehouses added manually afterwards are never disturbed and
 * the command can be re-run safely.
 *
 * Dry-run by default; pass --apply to write.
 */
class SyncWarehousesFromBranches extends Command
{
    protected $signature = 'warehouses:sync-from-branches
                            {--apply : Apply the changes. Default is dry-run}
                            {--include-inactive : Also provision warehouses for inactive branches}
                            {--only-flagged : Only branches with "Has Warehouse" checked (has_warehouse = 1)}';

    protected $description = 'Create the default warehouse for every branch that does not have one yet (data taken from the branch master)';

    public function handle(BranchWarehouseProvisioner $provisioner): int
    {
        $apply = (bool) $this->option('apply');

        $branches = Branch::query()
            ->when(! $this->option('include-inactive'), fn ($q) => $q->where('is_active', true))
            ->when($this->option('only-flagged'), fn ($q) => $q->where('has_warehouse', true))
            ->orderBy('code')
            ->get();

        if ($branches->isEmpty()) {
            $this->warn('Tidak ada branch yang cocok dengan filter. Isi Master Branch terlebih dahulu.');

            return self::SUCCESS;
        }

        $rows = [];
        $created = 0;
        $skipped = 0;
        $flagged = 0;

        foreach ($branches as $branch) {
            $existing = $branch->warehouses()->count();

            if ($existing > 0) {
                $skipped++;
                $rows[] = [
                    $branch->code,
                    $branch->name,
                    'skip',
                    "sudah punya {$existing} warehouse",
                ];

                continue;
            }

            if (! $apply) {
                $created++;
                $rows[] = [
                    $branch->code,
                    $branch->name,
                    'akan dibuat',
                    $provisioner->defaultWarehouseName($branch)
                        .($branch->is_head_office ? ' (Central)' : ' (Branch)'),
                ];

                continue;
            }

            DB::transaction(function () use ($branch, $provisioner, &$rows, &$created, &$flagged) {
                $warehouse = $provisioner->provisionForBranch($branch);

                if (! $warehouse) {
                    return;
                }

                $created++;

                // Keep the branch master consistent with reality: the "Has
                // Warehouse" checkbox now has a warehouse behind it.
                if (! $branch->has_warehouse) {
                    $branch->forceFill(['has_warehouse' => true])->save();
                    $flagged++;
                }

                $rows[] = [
                    $branch->code,
                    $branch->name,
                    'dibuat',
                    $warehouse->warehouse_code.' — '.$warehouse->name,
                ];
            });
        }

        $this->table(['Branch Code', 'Branch', 'Status', 'Warehouse'], $rows);

        $this->info(sprintf(
            '%s: %d branch %s warehouse, %d branch dilewati (sudah punya).',
            $apply ? 'APPLIED' : 'DRY-RUN',
            $created,
            $apply ? 'dibuatkan' : 'akan dibuatkan',
            $skipped
        ));

        if ($apply && $flagged > 0) {
            $this->line("   {$flagged} branch ikut di-set has_warehouse = 1.");
        }

        if (! $apply) {
            $this->line('   Jalankan ulang dengan --apply untuk menyimpan.');
        }

        return self::SUCCESS;
    }
}
