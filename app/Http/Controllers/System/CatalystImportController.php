<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Services\System\CatalystImportConsoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalystImportController extends Controller
{
    public function __construct(protected CatalystImportConsoleService $consoleService)
    {
    }

    public function index()
    {
        $actions = $this->consoleService->groupedActions();
        $batches = DB::table('source_import_batches')
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

        $logs = DB::table('source_import_logs')
            ->where('source_system', 'catalyst')
            ->whereIn('level', ['warning', 'error'])
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $warehouseExportPath = storage_path('app/catalyst/product_warehouse_links.csv');
        $usersExportPath = storage_path('app/catalyst/users_export.csv');

        $metrics = [
            'imported_products' => $this->countMaps('MsProduct', 'master_products'),
            'imported_rentals' => $this->countMaps('MsProduct', 'master_rentals'),
            'imported_users' => $this->countMaps('MsEmployee', 'users'),
            'rental_details' => DB::table('rental_details')->whereIn('master_rental_id', function ($query) {
                $query->from('source_import_maps')
                    ->select('target_id')
                    ->where('source_system', 'catalyst')
                    ->where('source_table', 'MsProduct')
                    ->where('target_table', 'master_rentals');
            })->count(),
            'product_warehouse_links' => DB::table('warehouse_products')->whereIn('master_product_id', function ($query) {
                $query->from('source_import_maps')
                    ->select('target_id')
                    ->where('source_system', 'catalyst')
                    ->where('source_table', 'MsProduct')
                    ->where('target_table', 'master_products');
            })->count(),
            'products_with_brand_variant' => DB::table('master_products')->whereIn('id', function ($query) {
                $query->from('source_import_maps')
                    ->select('target_id')
                    ->where('source_system', 'catalyst')
                    ->where('source_table', 'MsProduct')
                    ->where('target_table', 'master_products');
            })->whereNotNull('brand_line')->where('brand_line', '!=', '')
                ->whereNotNull('variant_name')->where('variant_name', '!=', '')
                ->count(),
        ];

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

        return view('system.catalyst-import.index', compact('actions', 'batches', 'logs', 'metrics', 'sourceConfig'));
    }

    public function run(Request $request): RedirectResponse
    {
        $request->validate([
            'action' => 'required|string',
        ]);

        set_time_limit(0);

        try {
            $result = $this->consoleService->run($request->string('action')->toString());

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

    protected function countMaps(string $sourceTable, string $targetTable): int
    {
        return DB::table('source_import_maps')
            ->where('source_system', 'catalyst')
            ->where('source_table', $sourceTable)
            ->where('target_table', $targetTable)
            ->count();
    }
}
