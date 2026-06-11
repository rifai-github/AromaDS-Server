<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\MasterProduct;
use App\Models\SerialNumber;
use App\Models\Warehouse;
use App\Services\Imports\SpreadsheetImportHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Bulk import of Serial Numbers from CSV/Excel.
 *
 * Follows the same flow as MasterProductImportController (preview -> import in
 * batches with per-row error reporting) and preserves the business logic from
 * SerialNumberController::store(): serial uppercasing, condition_status
 * auto-derivation, created_by/updated_by stamping.
 */
class SerialNumberImportController extends Controller
{
    /** Statuses accepted by SerialNumberController::store(). */
    private const VALID_STATUSES = [
        'ready', 'broken', 'on_service', 'in_use', 'retired',
        'available', 'maintenance', 'damaged', 'on_hand', 'on_hand_remove',
    ];

    private const VALID_CONDITIONS = ['new', 'second_ready', 'damaged'];

    private const TEMPLATE_HEADERS = [
        'serial_number', 'product_sku', 'product_name', 'status', 'condition_status', 'warehouse', 'notes',
    ];

    /**
     * Download a CSV/XLSX template with the accepted columns and a sample row.
     */
    public function template(Request $request)
    {
        $format = $request->query('format', 'xlsx');

        $sample = [
            ['SN-0001', 'PRD0001', '', 'ready', 'new', 'WH-JKT', 'Contoh catatan (opsional)'],
            ['SN-0002', '', 'Dispenser Aroma X', 'on_hand', '', 'WH-JKT', ''],
        ];

        return SpreadsheetImportHelper::downloadTemplate(
            self::TEMPLATE_HEADERS,
            $sample,
            'template-import-serial-number',
            $format
        );
    }

    /**
     * Parse the uploaded file and return counts + the first rows for preview.
     */
    public function preview(Request $request)
    {
        $request->validate(SpreadsheetImportHelper::validationRules());

        try {
            $rows = SpreadsheetImportHelper::parse($request->file('file'));
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        $preview = [
            'total_rows' => count($rows),
            'new' => 0,
            'existing' => 0,
            'errors' => [],
            'preview_data' => [],
        ];

        $seen = [];

        foreach ($rows as $index => $row) {
            $rowNo = $index + 2; // +1 for 0-index, +1 for header
            $serial = strtoupper(trim($row['serial_number'] ?? ''));

            if ($serial === '') {
                $preview['errors'][] = "Baris {$rowNo}: serial_number kosong";

                continue;
            }

            $duplicateInFile = isset($seen[$serial]);
            $seen[$serial] = true;
            $existsInDb = SerialNumber::where('serial_number', $serial)->exists();

            if ($duplicateInFile) {
                $preview['errors'][] = "Baris {$rowNo}: serial_number duplikat di dalam file ({$serial})";
            }

            $warehouseVal = trim($row['warehouse'] ?? '');
            if ($warehouseVal === '') {
                $preview['errors'][] = "Baris {$rowNo}: warehouse wajib diisi";
            } elseif (! $this->resolveWarehouse($warehouseVal)) {
                $preview['errors'][] = "Baris {$rowNo}: warehouse tidak ditemukan ('{$warehouseVal}')";
            }

            if ($existsInDb) {
                $preview['existing']++;
            } else {
                $preview['new']++;
            }

            if (count($preview['preview_data']) < 10) {
                $preview['preview_data'][] = [
                    'row' => $rowNo,
                    'serial_number' => $serial,
                    'product' => trim($row['product_sku'] ?? '') ?: trim($row['product_name'] ?? ''),
                    'status' => trim($row['status'] ?? ''),
                    'exists' => $existsInDb,
                ];
            }
        }

        return response()->json(['status' => 'success', 'preview' => $preview]);
    }

    /**
     * Import rows in batches; one bad row is reported and skipped, not fatal.
     */
    public function import(Request $request)
    {
        $request->validate(SpreadsheetImportHelper::validationRules());

        try {
            $rows = SpreadsheetImportHelper::parse($request->file('file'));
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        $stats = ['total' => 0, 'success' => 0, 'failed' => 0, 'errors' => []];
        $seen = [];
        $userId = Auth::id();

        foreach (array_chunk($rows, 50, true) as $batch) {
            DB::beginTransaction();
            try {
                foreach ($batch as $index => $row) {
                    $stats['total']++;
                    $rowNo = $index + 2;

                    try {
                        $serial = strtoupper(trim($row['serial_number'] ?? ''));
                        if ($serial === '') {
                            throw new \Exception('serial_number wajib diisi');
                        }

                        if (isset($seen[$serial])) {
                            throw new \Exception("serial_number duplikat di dalam file: {$serial}");
                        }
                        $seen[$serial] = true;

                        if (SerialNumber::where('serial_number', $serial)->exists()) {
                            throw new \Exception("serial_number sudah ada di sistem: {$serial}");
                        }

                        $status = trim($row['status'] ?? '');
                        if (! in_array($status, self::VALID_STATUSES, true)) {
                            throw new \Exception("status tidak valid: '{$status}'");
                        }

                        $product = $this->resolveProduct($row);
                        if (! $product) {
                            throw new \Exception('product_sku/product_name tidak ditemukan di master produk');
                        }

                        $condition = trim($row['condition_status'] ?? '');
                        if ($condition !== '' && ! in_array($condition, self::VALID_CONDITIONS, true)) {
                            throw new \Exception("condition_status tidak valid: '{$condition}'");
                        }

                        $warehouseValue = trim($row['warehouse'] ?? '');
                        if ($warehouseValue === '') {
                            throw new \Exception('warehouse wajib diisi (kolom warehouse tidak boleh kosong)');
                        }
                        $warehouse = $this->resolveWarehouse($warehouseValue);
                        if (! $warehouse) {
                            throw new \Exception("warehouse tidak ditemukan: '{$warehouseValue}'");
                        }

                        SerialNumber::create([
                            'warehouse_id' => $warehouse->id,
                            'master_product_id' => $product->id,
                            'serial_number' => $serial,
                            'status' => $status,
                            'condition_status' => $condition !== ''
                                ? $condition
                                : (in_array($status, ['broken', 'damaged', 'retired'], true)
                                    ? SerialNumber::CONDITION_DAMAGED
                                    : SerialNumber::CONDITION_NEW),
                            'location_type' => null,
                            'location_id' => null,
                            'notes' => trim($row['notes'] ?? '') ?: null,
                            'created_by' => $userId,
                            'updated_by' => $userId,
                        ]);

                        $stats['success']++;
                    } catch (\Exception $e) {
                        $stats['failed']++;
                        $stats['errors'][] = ['row' => $rowNo, 'error' => $e->getMessage()];
                    }
                }

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('Serial number import batch failed: '.$e->getMessage());
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "Import selesai: {$stats['success']} berhasil, {$stats['failed']} gagal",
            'stats' => $stats,
        ]);
    }

    /**
     * Resolve master product by SKU first, then by exact name.
     */
    private function resolveProduct(array $row): ?MasterProduct
    {
        $sku = trim($row['product_sku'] ?? '');
        if ($sku !== '') {
            $product = MasterProduct::where('sku', $sku)->first();
            if ($product) {
                return $product;
            }
        }

        $name = trim($row['product_name'] ?? '');
        if ($name !== '') {
            return MasterProduct::where('name', $name)->first();
        }

        return null;
    }

    /**
     * Resolve warehouse by code or name; null when blank/not found (optional FK).
     */
    private function resolveWarehouse(string $value): ?Warehouse
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        return Warehouse::where('warehouse_code', $value)
            ->orWhere('name', $value)
            ->first();
    }
}
