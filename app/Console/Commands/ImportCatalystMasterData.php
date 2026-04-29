<?php

namespace App\Console\Commands;

use App\Services\Imports\Catalyst\CatalystMasterDataImporter;
use Illuminate\Console\Command;
use Throwable;

class ImportCatalystMasterData extends Command
{
    protected $signature = 'catalyst:import-masters
                            {--step=* : Limit import to one or more steps}
                            {--apply : Persist changes to target tables}
                            {--batch-name= : Optional batch label}
                            {--chunk= : Override chunk size}
                            {--json : Print summary as JSON}';

    protected $description = 'Import Catalyst master data from SQL Server staging into the local KGI schema';

    public function handle(CatalystMasterDataImporter $importer): int
    {
        if ($this->option('chunk')) {
            config(['catalyst-import.chunk_size' => (int) $this->option('chunk')]);
        }

        $mode = $this->option('apply') ? 'apply' : 'dry-run';
        $this->info('Running Catalyst master import in ' . $mode . ' mode.');

        try {
            $result = $importer->run(
                requestedSteps: (array) $this->option('step'),
                apply: (bool) $this->option('apply'),
                batchName: $this->option('batch-name') ?: null,
                progressCallback: function (string $step, array $summary): void {
                    $stats = $summary['stats'] ?? [];

                    if (($summary['partial'] ?? false) === true) {
                        $this->line(sprintf(
                            '[%s] progress=%d/%d inserted=%d updated=%d skipped=%d failed=%d',
                            $step,
                            $stats['processed'] ?? 0,
                            $summary['total'] ?? 0,
                            $stats['inserted'] ?? 0,
                            $stats['updated'] ?? 0,
                            $stats['skipped'] ?? 0,
                            $stats['failed'] ?? 0,
                        ));

                        return;
                    }

                    $this->line(sprintf(
                        '[%s] processed=%d inserted=%d updated=%d skipped=%d failed=%d',
                        $step,
                        $stats['processed'] ?? 0,
                        $stats['inserted'] ?? 0,
                        $stats['updated'] ?? 0,
                        $stats['skipped'] ?? 0,
                        $stats['failed'] ?? 0,
                    ));
                }
            );
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            if (str_contains($e->getMessage(), 'Encryption not supported on the client')) {
                $this->newLine();
                $this->warn('SQL Server lokal kemungkinan belum membuka TCP/IP untuk SQLEXPRESS.');
                $this->line('Jalankan PowerShell as Administrator:');
                $this->line('powershell -ExecutionPolicy Bypass -File .\\scripts\\enable_sqlexpress_tcp.ps1');
            }

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return self::SUCCESS;
        }

        $summary = $result['summary'];
        $totals = $summary['totals'] ?? [];

        $this->newLine();
        $this->info('Batch #' . $result['batch_id'] . ' selesai.');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Mode', $summary['mode'] ?? $mode],
                ['Processed', $totals['processed'] ?? 0],
                ['Inserted', $totals['inserted'] ?? 0],
                ['Updated', $totals['updated'] ?? 0],
                ['Skipped', $totals['skipped'] ?? 0],
                ['Failed', $totals['failed'] ?? 0],
            ]
        );

        if (($totals['failed'] ?? 0) > 0) {
            $this->warn('Ada row yang di-skip / gagal. Cek source_import_logs untuk detail.');
        }

        return self::SUCCESS;
    }
}
