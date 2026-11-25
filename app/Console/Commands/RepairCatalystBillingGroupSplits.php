<?php

namespace App\Console\Commands;

use App\Services\Imports\Catalyst\CatalystBillingGroupSplitRepair;
use App\Services\Imports\Catalyst\CatalystMasterDataImporter;
use Illuminate\Console\Command;

/**
 * UAT 24 Nov 2026: billing group Catalyst yang dibuat/dipecah SETELAH import (mis.
 * SMD-AG/26-04/0003: 3 gedung, 3 billing group di Catalyst; 1 di ERP). Dry-run default.
 */
class RepairCatalystBillingGroupSplits extends Command
{
    protected $signature = 'catalyst:repair-billing-group-splits
        {--contract= : Hanya satu nomor kontrak}
        {--apply : Tulis perubahan (default dry-run)}';

    protected $description = 'Buat billing group Catalyst yang belum ada di ERP dan pindahkan gedung/room-nya (dry-run default)';

    public function handle(CatalystMasterDataImporter $importer, CatalystBillingGroupSplitRepair $repair): int
    {
        $apply = (bool) $this->option('apply');
        $source = $importer->sourceConnection();

        $rows = $source->table('MKTContractDt')
            ->whereNotNull('TransNmbr')
            ->whereNotNull('BillingGroup')
            ->when($this->option('contract'), fn ($query, $contract) => $query->where('TransNmbr', trim($contract)))
            ->get(['TransNmbr', 'BillingGroup', 'Building', 'FloorCode', 'RoomCode', 'Room', 'AmountForex']);

        $codes = $rows->pluck('BillingGroup')->map(fn ($code) => trim((string) $code))->filter()->unique()->values();
        $legacy = collect();
        foreach ($codes->chunk(500) as $chunk) {
            $legacy = $legacy->merge(
                $source->table('MsBillingGroup')->whereIn('BillingCode', $chunk->all())->get()
                    ->keyBy(fn ($row) => strtoupper(trim((string) $row->BillingCode)))
            );
        }

        $plans = $repair->plan($rows, $legacy, $this->option('contract'));
        $this->info(($apply ? 'APPLY' : 'DRY-RUN').': '.count($plans).' kontrak punya billing group Catalyst yang belum ada di ERP.');

        $totals = ['groups' => 0, 'building_links' => 0, 'rooms' => 0];
        foreach ($plans as $plan) {
            $this->line('');
            $this->line("<comment>{$plan['contract_number']}</comment> (contract #{$plan['contract_id']}), billing group ERP sekarang: ".implode(', ', $plan['existing_group_ids'])
                .($plan['issued_invoices'] ? " - {$plan['issued_invoices']} invoice sudah ada (tidak diubah)" : ''));
            foreach ($plan['missing'] as $missing) {
                $buildings = collect($missing['buildings'])->map(fn ($id, $code) => $code.($id ? " -> gedung #{$id}" : ' -> (gedung tak berpeta)'))->implode(', ');
                $this->line("  + {$missing['code']}: gedung [{$buildings}], ".count($missing['contract_room_ids']).' room');
            }

            if ($apply) {
                $done = $repair->apply($plan, null);
                foreach ($done as $key => $count) {
                    $totals[$key] += $count;
                }
            }
        }

        if ($apply) {
            $this->info("Selesai: {$totals['groups']} billing group dibuat, {$totals['building_links']} link gedung dipindah, {$totals['rooms']} room diperbarui.");
        } elseif ($plans) {
            $this->comment('Tidak ada yang ditulis. Jalankan ulang dengan --apply untuk menerapkan.');
        }

        return self::SUCCESS;
    }
}
