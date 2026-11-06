<?php

namespace App\Console\Commands;

use App\Models\Contract;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off repair for contracts saved before Contract::booted() started keeping the
 * legacy `status` column in step with `contract_status`. Contracts created in the
 * app were left at `status = draft` even after activation, which hid them from
 * every `where('status', 'active')` reader (Billing Group, invoice cron, reports).
 */
class SyncContractLegacyStatus extends Command
{
    protected $signature = 'contracts:sync-legacy-status
        {--contract=* : Limit to these contract numbers (repeatable)}
        {--apply : Write the changes. Default is a dry run}';

    protected $description = 'Align contracts.status with contracts.contract_status for rows that drifted apart';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $numbers = collect($this->option('contract'))->map(fn ($n) => trim((string) $n))->filter()->values();

        if (! $apply) {
            $this->warn('DRY RUN — tidak ada perubahan ke database. Jalankan ulang dengan --apply untuk menyimpan.');
        }

        $mappedStatuses = array_keys(Contract::LEGACY_STATUS_BY_CONTRACT_STATUS);

        $candidates = DB::table('contracts')
            ->whereNull('deleted_at')
            ->whereIn('contract_status', $mappedStatuses)
            ->when($numbers->isNotEmpty(), fn ($q) => $q->whereIn('contract_number', $numbers->all()))
            ->orderBy('id')
            ->get(['id', 'contract_number', 'status', 'contract_status']);

        $rows = [];
        foreach ($candidates as $contract) {
            $target = Contract::legacyStatusFor($contract->contract_status);

            if ($target === null || $contract->status === $target) {
                continue;
            }

            $rows[] = [
                'id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'contract_status' => $contract->contract_status,
                'status' => $contract->status ?? 'NULL',
                'status_baru' => $target,
            ];
        }

        $this->info(sprintf('Diperiksa: %d kontrak. Tidak sinkron: %d.', $candidates->count(), count($rows)));

        if ($rows === []) {
            return self::SUCCESS;
        }

        $this->table(['id', 'contract_number', 'contract_status', 'status', 'status_baru'], $rows);

        if (! $apply) {
            return self::SUCCESS;
        }

        $updated = 0;
        DB::transaction(function () use ($rows, &$updated) {
            foreach ($rows as $row) {
                // Only status: leave updated_at/audit columns alone, this is a column
                // backfill, not a user edit. Re-check the old value so a row changed
                // meanwhile is not overwritten.
                $updated += DB::table('contracts')
                    ->where('id', $row['id'])
                    ->where('status', $row['status'] === 'NULL' ? null : $row['status'])
                    ->update(['status' => $row['status_baru']]);
            }
        });

        $this->info("Diperbarui: {$updated} kontrak.");

        return self::SUCCESS;
    }
}
