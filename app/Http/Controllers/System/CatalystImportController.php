<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Services\System\CatalystImportConsoleService;
use App\Services\System\CatalystMigrationRunService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CatalystImportController extends Controller
{
    public function __construct(
        protected CatalystImportConsoleService $consoleService,
        protected CatalystMigrationRunService $migrationRunService,
    ) {
    }

    public function index()
    {
        $actions = $this->consoleService->groupedActions();
        $batches = $this->loadBatches();
        $logs = $this->loadLogs();
        $activeRun = $this->normalizeRun($this->migrationRunService->activeRun());
        $recentRuns = $this->migrationRunService->recentRuns()
            ->map(fn ($run) => $this->normalizeRun($run));

        $warehouseExportPath = storage_path('app/catalyst/product_warehouse_links.csv');
        $usersExportPath = storage_path('app/catalyst/users_export.csv');

        $metrics = $this->loadMetrics();

        $sourceConfig = [
            'host' => (string) config('catalyst-import.source.host'),
            'port' => (string) config('catalyst-import.source.port'),
            'database' => (string) config('catalyst-import.source.database'),
            'username' => (string) config('catalyst-import.source.username'),
            'encrypt' => (string) config('catalyst-import.source.encrypt'),
            'warehouse_export_exists' => is_file($warehouseExportPath),
            'warehouse_export_path' => $warehouseExportPath,
            'warehouse_export_size' => is_file($warehouseExportPath) ? filesize($warehouseExportPath) : null,
            'warehouse_export_mtime' => is_file($warehouseExportPath) ? date('Y-m-d H:i:s', filemtime($warehouseExportPath)) : null,
            'users_export_exists' => is_file($usersExportPath),
            'users_export_path' => $usersExportPath,
            'users_export_size' => is_file($usersExportPath) ? filesize($usersExportPath) : null,
            'users_export_mtime' => is_file($usersExportPath) ? date('Y-m-d H:i:s', filemtime($usersExportPath)) : null,
        ];

        $hasActiveRun = $activeRun !== null;

        return view('system.catalyst-import.index', compact(
            'actions',
            'batches',
            'logs',
            'metrics',
            'sourceConfig',
            'activeRun',
            'recentRuns',
            'hasActiveRun'
        ));
    }

    public function run(Request $request): RedirectResponse
    {
        $request->validate([
            'action' => 'required|string',
        ]);

        $action = $request->string('action')->toString();
        $definition = $this->consoleService->definition($action);

        if (($definition['requires_confirmation'] ?? false) === true) {
            $expected = (string) ($definition['confirmation_value'] ?? 'MIGRASI');
            $provided = $request->string('confirmation')->trim()->toString();

            if ($provided !== $expected) {
                return back()->with('error', 'Konfirmasi tidak cocok. Ketik persis `' . $expected . '` untuk menjalankan action ini.');
            }
        }

        try {
            if ($this->consoleService->isBackgroundAction($action)) {
                $result = $this->consoleService->launchBackground($action, $request->user()?->id);

                return back()->with(
                    'success',
                    $result['label'] . ' sudah dijadwalkan di background sebagai run #' . $result['run_id'] . '.'
                );
            }

            set_time_limit(0);
            $result = $this->consoleService->run($action);

            $flashType = $result['successful'] ? 'success' : 'error';
            $message = $result['successful']
                ? $result['label'] . ' selesai dalam ' . number_format($result['duration_ms'] / 1000, 1) . ' detik.'
                : $result['label'] . ' gagal. Cek output command di bawah.';

            return back()
                ->with($flashType, $message)
                ->with('catalyst_command_result', $result);
        } catch (\Throwable $e) {
            return back()
                ->with('error', 'Catalyst import action gagal dijalankan: ' . $e->getMessage());
        }
    }

    public function status()
    {
        return response()->json([
            'has_active_run' => $this->migrationRunService->hasActiveRun(),
            'active_run' => $this->normalizeRun($this->migrationRunService->activeRun()),
            'recent_runs' => $this->migrationRunService->recentRuns()
                ->map(fn ($run) => $this->normalizeRun($run))
                ->values()
                ->all(),
        ]);
    }

    protected function countMaps(string $sourceTable, string $targetTable): int
    {
        if (!Schema::hasTable('source_import_maps')) {
            return 0;
        }

        return DB::table('source_import_maps')
            ->where('source_system', 'catalyst')
            ->where('source_table', $sourceTable)
            ->where('target_table', $targetTable)
            ->count();
    }

    protected function loadBatches()
    {
        if (!Schema::hasTable('source_import_batches')) {
            return collect();
        }

        return DB::table('source_import_batches')
            ->where('source_system', 'catalyst')
            ->orderByDesc('id')
            ->limit(12)
            ->get()
            ->map(function ($batch) {
                $summary = json_decode((string) ($batch->summary ?? ''), true) ?: [];
                $totals = $summary['totals'] ?? [];

                $batch->processed = (int) ($totals['processed'] ?? 0);
                $batch->inserted = (int) ($totals['inserted'] ?? 0);
                $batch->updated = (int) ($totals['updated'] ?? 0);
                $batch->skipped = (int) ($totals['skipped'] ?? 0);
                $batch->failed = (int) ($totals['failed'] ?? 0);

                return $batch;
            });
    }

    protected function loadLogs()
    {
        if (!Schema::hasTable('source_import_logs')) {
            return collect();
        }

        return DB::table('source_import_logs')
            ->where('source_system', 'catalyst')
            ->whereIn('level', ['warning', 'error'])
            ->orderByDesc('id')
            ->limit(20)
            ->get();
    }

    protected function loadMetrics(): array
    {
        $importedRentalIdsQuery = function ($query) {
            $query->from('source_import_maps')
                ->select('target_id')
                ->where('source_system', 'catalyst')
                ->where('source_table', 'MsProduct')
                ->where('target_table', 'master_rentals');
        };

        $importedProductIdsQuery = function ($query) {
            $query->from('source_import_maps')
                ->select('target_id')
                ->where('source_system', 'catalyst')
                ->where('source_table', 'MsProduct')
                ->where('target_table', 'master_products');
        };

        return [
            'imported_products' => $this->countMaps('MsProduct', 'master_products'),
            'imported_rentals' => $this->countMaps('MsProduct', 'master_rentals'),
            'imported_users' => $this->countMaps('MsEmployee', 'users'),
            'rental_details' => Schema::hasTable('rental_details') && Schema::hasTable('source_import_maps')
                ? DB::table('rental_details')->whereIn('master_rental_id', $importedRentalIdsQuery)->count()
                : 0,
            'product_warehouse_links' => Schema::hasTable('warehouse_products') && Schema::hasTable('source_import_maps')
                ? DB::table('warehouse_products')->whereIn('master_product_id', $importedProductIdsQuery)->count()
                : 0,
            'products_with_brand_variant' => Schema::hasTable('master_products') && Schema::hasTable('source_import_maps')
                ? DB::table('master_products')->whereIn('id', $importedProductIdsQuery)
                    ->whereNotNull('brand_line')
                    ->where('brand_line', '!=', '')
                    ->whereNotNull('variant_name')
                    ->where('variant_name', '!=', '')
                    ->count()
                : 0,
        ];
    }

    protected function normalizeRun(?object $run): ?array
    {
        if (!$run) {
            return null;
        }

        $summary = json_decode((string) ($run->summary ?? ''), true) ?: [];
        $totals = $summary['import']['summary']['totals'] ?? [];

        return [
            'id' => (int) $run->id,
            'action_key' => (string) $run->action_key,
            'label' => (string) $run->label,
            'status' => (string) $run->status,
            'mode' => $run->mode ? (string) $run->mode : null,
            'current_step' => $run->current_step ? (string) $run->current_step : null,
            'progress_message' => $run->progress_message ? (string) $run->progress_message : null,
            'pid' => $run->pid ? (int) $run->pid : null,
            'batch_id' => $run->batch_id ? (int) $run->batch_id : null,
            'log_path' => $run->log_path ? (string) $run->log_path : null,
            'backup_path' => $run->backup_path ? (string) $run->backup_path : null,
            'backup_sha256' => $run->backup_sha256 ? (string) $run->backup_sha256 : null,
            'backup_size' => $run->backup_size ? (int) $run->backup_size : null,
            'error_message' => $run->error_message ? (string) $run->error_message : null,
            'output' => $run->output ? (string) $run->output : null,
            'started_at' => $run->started_at ? (string) $run->started_at : null,
            'finished_at' => $run->finished_at ? (string) $run->finished_at : null,
            'last_heartbeat_at' => $run->last_heartbeat_at ? (string) $run->last_heartbeat_at : null,
            'processed' => (int) ($totals['processed'] ?? 0),
            'inserted' => (int) ($totals['inserted'] ?? 0),
            'updated' => (int) ($totals['updated'] ?? 0),
            'skipped' => (int) ($totals['skipped'] ?? 0),
            'failed' => (int) ($totals['failed'] ?? 0),
        ];
    }
}
