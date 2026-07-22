<?php

namespace App\Console\Commands;

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

        $serialFilter = strtoupper(trim((string) $this->option('serial')));
        $activeStatuses = ['pending', 'processed', 'sent'];

        $duplicates = DB::table('inventory_issuing_items as iii')
            ->join('inventory_issuings as ii', 'ii.id', '=', 'iii.inventory_issuing_id')
            ->join('serial_numbers as sn', 'sn.id', '=', 'iii.serial_number_id')
            ->whereNotNull('iii.serial_number_id')
            ->whereIn('ii.status', $activeStatuses)
            ->when($serialFilter !== '', fn ($query) => $query->whereRaw('UPPER(TRIM(sn.serial_number)) = ?', [$serialFilter]))
            ->selectRaw('sn.id as serial_id, sn.serial_number, COUNT(*) as active_links')
            ->groupBy('sn.id', 'sn.serial_number')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('sn.serial_number')
            ->get()
            ->map(function ($duplicate) use ($activeStatuses) {
                $issuings = DB::table('inventory_issuing_items as iii')
                    ->join('inventory_issuings as ii', 'ii.id', '=', 'iii.inventory_issuing_id')
                    ->where('iii.serial_number_id', $duplicate->serial_id)
                    ->whereIn('ii.status', $activeStatuses)
                    ->orderBy('ii.issuing_number')
                    ->get(['ii.issuing_number', 'ii.status'])
                    ->map(fn ($issuing) => "{$issuing->issuing_number} ({$issuing->status})")
                    ->implode('; ');

                return [
                    'serial_number' => $duplicate->serial_number,
                    'active_links' => (int) $duplicate->active_links,
                    'issuings' => $issuings,
                ];
            })
            ->values();

        if ($format === 'json') {
            $this->line($duplicates->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } elseif ($duplicates->isEmpty()) {
            $this->info('Tidak ada duplikasi SN pada Inventory Issuing aktif.');
        } else {
            $this->table(['Serial Number', 'Tautan Aktif', 'Inventory Issuing'], $duplicates->all());
        }

        $this->newLine();
        $this->info("Dry-run selesai: {$duplicates->count()} serial konflik. Tidak ada data yang diubah.");

        return self::SUCCESS;
    }
}
