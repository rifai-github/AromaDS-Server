<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\MasterRental;
use App\Models\RentalServiceFrequency;
use App\Services\Imports\SpreadsheetImportHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Bulk import of Master Rentals from CSV/Excel.
 *
 * Preserves the business logic from MasterRentalController::store():
 * rental_code auto-generation (with the same RTL-Ymd-#### format), price
 * defaults of 0, and created_by stamping. rental_type is validated against the
 * same enum; service_frequency is resolved by code or name.
 */
class MasterRentalImportController extends Controller
{
    private const VALID_RENTAL_TYPES = ['unit_only', 'refill_only', 'unit_refill'];

    private const TEMPLATE_HEADERS = [
        'rental_name', 'rental_code', 'service_frequency', 'category', 'rental_type',
        'daily_price', 'monthly_price', 'lost_unit_price', 'install_duration',
        'service_duration', 'alias', 'description', 'is_active',
    ];

    public function template(Request $request)
    {
        $format = $request->query('format', 'xlsx');

        $sample = [
            ['Sewa Dispenser Bulanan', '', 'Bulanan', 'Dispenser', 'unit_refill', '0', '150000', '500000', '1', '1', '', 'Contoh deskripsi (opsional)', 'Y'],
            ['Refill Aroma 1L', '', 'Bulanan', 'Refill', 'refill_only', '0', '75000', '0', '0', '1', '', '', 'Y'],
        ];

        return SpreadsheetImportHelper::downloadTemplate(
            self::TEMPLATE_HEADERS,
            $sample,
            'template-import-master-rental',
            $format
        );
    }

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

        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;
            $name = trim($row['rental_name'] ?? '');

            if ($name === '') {
                $preview['errors'][] = "Baris {$rowNo}: rental_name kosong";

                continue;
            }

            $type = trim($row['rental_type'] ?? '');
            if (! in_array($type, self::VALID_RENTAL_TYPES, true)) {
                $preview['errors'][] = "Baris {$rowNo}: rental_type tidak valid ('{$type}')";
            }

            if ($this->resolveFrequencyId(trim($row['service_frequency'] ?? '')) === null) {
                $preview['errors'][] = "Baris {$rowNo}: service_frequency tidak ditemukan ('".trim($row['service_frequency'] ?? '')."')";
            }

            $code = trim($row['rental_code'] ?? '');
            if ($code !== '' && MasterRental::withTrashed()->where('rental_code', $code)->exists()) {
                $preview['existing']++;
            } else {
                $preview['new']++;
            }

            if (count($preview['preview_data']) < 10) {
                $preview['preview_data'][] = [
                    'row' => $rowNo,
                    'rental_name' => $name,
                    'rental_type' => $type,
                    'category' => trim($row['category'] ?? ''),
                ];
            }
        }

        return response()->json(['status' => 'success', 'preview' => $preview]);
    }

    public function import(Request $request)
    {
        $request->validate(SpreadsheetImportHelper::validationRules());

        try {
            $rows = SpreadsheetImportHelper::parse($request->file('file'));
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        $stats = ['total' => 0, 'success' => 0, 'failed' => 0, 'errors' => []];
        $userId = Auth::id();

        foreach (array_chunk($rows, 50, true) as $batch) {
            DB::beginTransaction();
            try {
                foreach ($batch as $index => $row) {
                    $stats['total']++;
                    $rowNo = $index + 2;

                    try {
                        $name = trim($row['rental_name'] ?? '');
                        if ($name === '') {
                            throw new \Exception('rental_name wajib diisi');
                        }

                        $category = trim($row['category'] ?? '');
                        if ($category === '') {
                            throw new \Exception('category wajib diisi');
                        }

                        $type = trim($row['rental_type'] ?? '');
                        if (! in_array($type, self::VALID_RENTAL_TYPES, true)) {
                            throw new \Exception("rental_type tidak valid: '{$type}'");
                        }

                        $frequencyId = $this->resolveFrequencyId(trim($row['service_frequency'] ?? ''));
                        if ($frequencyId === null) {
                            throw new \Exception('service_frequency tidak ditemukan: '.trim($row['service_frequency'] ?? ''));
                        }

                        $providedCode = trim($row['rental_code'] ?? '');
                        if ($providedCode !== '' && MasterRental::withTrashed()->where('rental_code', $providedCode)->exists()) {
                            throw new \Exception("rental_code sudah ada: {$providedCode}");
                        }

                        $code = $providedCode !== '' ? $providedCode : $this->generateRentalCode();

                        MasterRental::create([
                            'rental_code' => $code,
                            'rental_name' => $name,
                            'alias' => trim($row['alias'] ?? '') ?: null,
                            'description' => trim($row['description'] ?? '') ?: null,
                            'service_frequency_id' => $frequencyId,
                            'category' => $category,
                            'rental_type' => $type,
                            'daily_price' => $this->num($row['daily_price'] ?? null),
                            'monthly_price' => $this->num($row['monthly_price'] ?? null),
                            'lost_unit_price' => $this->num($row['lost_unit_price'] ?? null),
                            'install_duration' => $this->intOrNull($row['install_duration'] ?? null),
                            'service_duration' => $this->intOrNull($row['service_duration'] ?? null),
                            'unit' => null,
                            'is_active' => $this->boolFromYn($row['is_active'] ?? 'Y'),
                            'created_by' => $userId,
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
                Log::error('Master rental import batch failed: '.$e->getMessage());
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "Import selesai: {$stats['success']} berhasil, {$stats['failed']} gagal",
            'stats' => $stats,
        ]);
    }

    /**
     * Resolve a RentalServiceFrequency id by exact code or name.
     */
    private function resolveFrequencyId(string $value): ?int
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $freq = RentalServiceFrequency::where('code', $value)
            ->orWhere('name', $value)
            ->first();

        return $freq?->id;
    }

    private function num($value): float
    {
        $value = trim((string) $value);

        return $value === '' ? 0 : (float) str_replace([',', ' '], '', $value);
    }

    private function intOrNull($value): ?int
    {
        $value = trim((string) $value);

        return $value === '' ? null : (int) $value;
    }

    private function boolFromYn($value): bool
    {
        $value = strtoupper(trim((string) $value));

        return in_array($value, ['Y', 'YES', '1', 'TRUE', 'AKTIF'], true);
    }

    /**
     * Mirror of MasterRentalController::generateRentalCode() — RTL-Ymd-#### with
     * soft-deleted rows considered to avoid collisions.
     */
    private function generateRentalCode(): string
    {
        $base = 'RTL-'.now()->format('Ymd').'-';

        $maxSequence = MasterRental::withTrashed()
            ->where('rental_code', 'like', $base.'%')
            ->selectRaw('MAX(CAST(SUBSTRING(rental_code, -4) AS UNSIGNED)) as max_seq')
            ->value('max_seq');

        $next = (int) $maxSequence + 1;

        do {
            $code = $base.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $exists = MasterRental::withTrashed()->where('rental_code', $code)->exists();
            if ($exists) {
                $next++;
            }
        } while ($exists);

        return $code;
    }
}
