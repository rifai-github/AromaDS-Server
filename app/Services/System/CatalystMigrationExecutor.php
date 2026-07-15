<?php

namespace App\Services\System;

use App\Services\Imports\Catalyst\CatalystMasterDataImporter;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class CatalystMigrationExecutor
{
    public function __construct(
        protected CatalystMigrationRunService $runService,
        protected CatalystMasterDataImporter $importer,
        protected CatalystTargetBackupService $backupService,
    ) {
    }

    public function execute(int $runId): int
    {
        $run = $this->runService->find($runId);
        if (!$run) {
            throw new RuntimeException('Catalyst migration run tidak ditemukan: #' . $runId);
        }

        $this->runService->markRunning($runId, 'prepare', 'Menyiapkan migrasi Catalyst.');

        $summary = [];
        $outputParts = [];

        try {
            switch ($run->action_key) {
                case 'migration_full_dry_run':
                    $summary['import'] = $this->runFullImport($runId, false, $run->label);
                    $outputParts[] = $this->buildImportSummaryText($summary['import']);
                    break;

                case 'migration_full_apply':
                    $this->runService->heartbeat($runId, 'backup', 'Membuat backup MySQL sebelum apply.');
                    $backup = $this->backupService->createMysqlBackup($run->action_key);
                    $this->runService->attachBackup($runId, $backup);
                    $summary['backup'] = $backup;
                    $outputParts[] = $this->buildBackupSummaryText($backup);

                    $summary['import'] = $this->runFullImport($runId, true, $run->label);
                    $outputParts[] = $this->buildImportSummaryText($summary['import']);

                    $this->runService->heartbeat($runId, 'audit', 'Menjalankan audit health setelah apply.');
                    Artisan::call('catalyst:audit-sync-health');
                    $summary['audit_output'] = Artisan::output();
                    $outputParts[] = trim((string) $summary['audit_output']);
                    break;

                default:
                    throw new RuntimeException('Action Catalyst migration tidak didukung: ' . $run->action_key);
            }

            $this->runService->markCompleted($runId, $summary, trim(implode(PHP_EOL . PHP_EOL, array_filter($outputParts))));

            return 0;
        } catch (Throwable $e) {
            $this->runService->markFailed(
                $runId,
                $e->getMessage(),
                $summary,
                trim(implode(PHP_EOL . PHP_EOL, array_filter($outputParts)))
            );

            throw $e;
        }
    }

    protected function runFullImport(int $runId, bool $apply, string $label): array
    {
        $result = $this->importer->run(
            requestedSteps: [],
            apply: $apply,
            batchName: $label . ' #' . $runId,
            progressCallback: function (string $step, array $summary) use ($runId): void {
                $batchId = $this->detectLatestRunningBatchId();
                $message = $this->formatProgressMessage($summary);
                $this->runService->heartbeat($runId, $step, $message, $batchId);
            }
        );

        $this->runService->attachBatch($runId, (int) $result['batch_id']);
        $this->runService->heartbeat($runId, 'finalize', 'Menyimpan ringkasan batch.', (int) $result['batch_id']);

        return $result;
    }

    protected function detectLatestRunningBatchId(): ?int
    {
        if (!DB::getSchemaBuilder()->hasTable('source_import_batches')) {
            return null;
        }

        $batchId = DB::table('source_import_batches')
            ->where('source_system', 'catalyst')
            ->where('status', 'running')
            ->orderByDesc('id')
            ->value('id');

        return $batchId ? (int) $batchId : null;
    }

    protected function formatProgressMessage(array $summary): string
    {
        $stats = $summary['stats'] ?? [];
        $processed = (int) ($stats['processed'] ?? 0);
        $inserted = (int) ($stats['inserted'] ?? 0);
        $updated = (int) ($stats['updated'] ?? 0);
        $skipped = (int) ($stats['skipped'] ?? 0);
        $failed = (int) ($stats['failed'] ?? 0);

        if (($summary['partial'] ?? false) === true) {
            $total = (int) ($summary['total'] ?? 0);
            return sprintf(
                'Progress %s/%s | inserted=%s updated=%s skipped=%s failed=%s',
                number_format($processed),
                number_format($total),
                number_format($inserted),
                number_format($updated),
                number_format($skipped),
                number_format($failed)
            );
        }

        return sprintf(
            'Processed=%s | inserted=%s updated=%s skipped=%s failed=%s',
            number_format($processed),
            number_format($inserted),
            number_format($updated),
            number_format($skipped),
            number_format($failed)
        );
    }

    protected function buildBackupSummaryText(array $backup): string
    {
        return implode(PHP_EOL, [
            'Backup MySQL dibuat.',
            'Path   : ' . ($backup['path'] ?? '-'),
            'Size   : ' . number_format((int) ($backup['size'] ?? 0)) . ' bytes',
            'SHA256 : ' . ($backup['sha256'] ?? '-'),
        ]);
    }

    protected function buildImportSummaryText(array $result): string
    {
        $totals = $result['summary']['totals'] ?? [];

        return implode(PHP_EOL, [
            'Import Catalyst selesai pada batch #' . ($result['batch_id'] ?? '-'),
            'Processed: ' . number_format((int) ($totals['processed'] ?? 0)),
            'Inserted : ' . number_format((int) ($totals['inserted'] ?? 0)),
            'Updated  : ' . number_format((int) ($totals['updated'] ?? 0)),
            'Skipped  : ' . number_format((int) ($totals['skipped'] ?? 0)),
            'Failed   : ' . number_format((int) ($totals['failed'] ?? 0)),
        ]);
    }
}
