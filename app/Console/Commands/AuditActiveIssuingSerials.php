<?php

namespace App\Console\Commands;

use App\Models\SerialNumber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditActiveIssuingSerials extends Command
{
    protected $signature = 'warehouse:audit-active-issuing-serials
        {--serial= : Limit the audit to one serial number}
        {--format=table : Output format: table or json}';

    protected $description = 'Audit serial numbers linked to more than one active inventory issuing (read-only).';

    public function handle(): int
    {
        $format = strtolower((string) $this->option('format'));
        if (! in_array($format, ['table', 'json'], true)) {
            $this->error('Format harus table atau json.');

            return self::INVALID;
        }

        $serialFilter = SerialNumber::normalizeSerialCode((string) $this->option('serial'));
        $activeStatuses = ['pending', 'processed', 'sent'];

        $duplicates = DB::table('inventory_issuing_items as iii')
            ->join('inventory_issuings as ii', 'ii.id', '=', 'iii.inventory_issuing_id')
            ->join('serial_numbers as sn', 'sn.id', '=', 'iii.serial_number_id')
            ->whereNotNull('iii.serial_number_id')
            ->whereIn('ii.status', $activeStatuses)
            ->when($serialFilter !== '', fn ($query) => $query->whereRaw('UPPER(TRIM(sn.serial_number)) = ?', [$serialFilter]))
            ->selectRaw('UPPER(TRIM(sn.serial_number)) as serial_number')
            ->selectRaw('COUNT(DISTINCT sn.id) as master_rows')
            ->selectRaw('COUNT(*) as active_item_links')
            ->selectRaw('COUNT(DISTINCT ii.id) as active_issuings')
            ->groupByRaw('UPPER(TRIM(sn.serial_number))')
            ->havingRaw('COUNT(*) > 1 OR COUNT(DISTINCT sn.id) > 1 OR COUNT(DISTINCT ii.id) > 1')
            ->orderBy('serial_number')
            ->get()
            ->map(function ($duplicate) use ($activeStatuses) {
                $issuings = DB::table('inventory_issuing_items as iii')
                    ->join('inventory_issuings as ii', 'ii.id', '=', 'iii.inventory_issuing_id')
                    ->join('serial_numbers as sn', 'sn.id', '=', 'iii.serial_number_id')
                    ->whereRaw('UPPER(TRIM(sn.serial_number)) = ?', [$duplicate->serial_number])
                    ->whereIn('ii.status', $activeStatuses)
                    ->orderBy('ii.issuing_number')
                    ->distinct()
                    ->get(['ii.id', 'ii.issuing_number', 'ii.status'])
                    ->map(fn ($issuing) => "{$issuing->issuing_number} ({$issuing->status})")
                    ->implode('; ');

                $duplicateSameIssuingLinks = DB::table('inventory_issuing_items as iii')
                    ->join('inventory_issuings as ii', 'ii.id', '=', 'iii.inventory_issuing_id')
                    ->join('serial_numbers as sn', 'sn.id', '=', 'iii.serial_number_id')
                    ->whereRaw('UPPER(TRIM(sn.serial_number)) = ?', [$duplicate->serial_number])
                    ->whereIn('ii.status', $activeStatuses)
                    ->selectRaw('ii.id, COUNT(*) as link_count')
                    ->groupBy('ii.id')
                    ->havingRaw('COUNT(*) > 1')
                    ->get()
                    ->sum(fn ($row) => (int) $row->link_count);

                $issueTypes = collect([
                    ((int) $duplicate->master_rows > 1) ? 'duplicate_master_rows' : null,
                    ((int) $duplicate->active_issuings > 1) ? 'duplicate_active_issuings' : null,
                    ($duplicateSameIssuingLinks > 0) ? 'duplicate_links_same_issuing' : null,
                ])->filter()->values()->all();

                return [
                    'serial_number' => $duplicate->serial_number,
                    'master_rows' => (int) $duplicate->master_rows,
                    'active_item_links' => (int) $duplicate->active_item_links,
                    'active_issuings' => (int) $duplicate->active_issuings,
                    'duplicate_same_issuing_links' => (int) $duplicateSameIssuingLinks,
                    'issue_types' => implode(', ', $issueTypes),
                    'issuings' => $issuings,
                ];
            })
            ->values();

        if ($format === 'json') {
            $this->line($duplicates->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } elseif ($duplicates->isEmpty()) {
            $this->info('Tidak ada duplikasi SN pada Inventory Issuing aktif.');
        } else {
            $this->table([
                'Serial Number',
                'Master Rows',
                'Active Item Links',
                'Active Issuings',
                'Same Issuing Duplicate Links',
                'Issue Types',
                'Inventory Issuing',
            ], $duplicates->all());
        }

        $this->newLine();
        $this->info("Dry-run selesai: {$duplicates->count()} serial konflik. Tidak ada data yang diubah.");

        return self::SUCCESS;
    }
}
