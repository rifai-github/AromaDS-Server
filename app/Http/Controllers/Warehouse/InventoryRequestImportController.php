<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\InventoryRequest;
use App\Models\MasterProduct;
use App\Models\User;
use App\Services\DocumentNumberService;
use App\Services\Imports\SpreadsheetImportHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Bulk import Inventory Requests from CSV/Excel.
 *
 * Each spreadsheet row is one request item. Rows with the same request_group
 * become a single Inventory Request; blank request_group values are grouped by
 * branch + required_date + reason + notes. Imported requests start as draft,
 * matching the manual create flow.
 */
class InventoryRequestImportController extends Controller
{
    private const TEMPLATE_HEADERS = [
        'request_group',
        'branch_code',
        'branch_name',
        'required_date',
        'reason',
        'notes',
        'product_sku',
        'product_name',
        'quantity',
        'item_notes',
    ];

    public function template(Request $request)
    {
        $format = $request->query('format', 'xlsx');

        $sample = [
            ['REQ-001', 'JKT', '', now()->addDays(7)->format('d-M-Y'), 'Restock kebutuhan service', 'Import contoh', 'REF-001', '', '10', 'Aroma lavender'],
            ['REQ-001', 'JKT', '', now()->addDays(7)->format('d-M-Y'), 'Restock kebutuhan service', 'Import contoh', '', 'Dispenser Aroma X', '2', 'Unit tambahan'],
            ['REQ-002', '', 'Bandung', now()->addDays(10)->format('d-M-Y'), 'Kebutuhan cabang', '', 'CLN-001', '', '5', ''],
        ];

        return SpreadsheetImportHelper::downloadTemplate(
            self::TEMPLATE_HEADERS,
            $sample,
            'template-import-inventory-request',
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
            'request_groups' => 0,
        ];

        $groupSummaries = [];

        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;
            $groupKey = $this->groupKey($row, $index);
            $groupSummaries[$groupKey] ??= [
                'label' => trim($row['request_group'] ?? '') ?: 'Auto group',
                'rows' => 0,
            ];
            $groupSummaries[$groupKey]['rows']++;

            if (count($preview['preview_data']) < 10) {
                $preview['preview_data'][] = [
                    'row' => $rowNo,
                    'group' => $groupSummaries[$groupKey]['label'],
                    'branch' => trim($row['branch_code'] ?? '') ?: trim($row['branch_name'] ?? '') ?: 'Default branch',
                    'product' => trim($row['product_sku'] ?? '') ?: trim($row['product_name'] ?? ''),
                    'quantity' => trim($row['quantity'] ?? ''),
                ];
            }
        }

        foreach ($this->groupRows($rows) as $group) {
            $validation = $this->validateGroup($group);
            foreach ($validation['errors'] as $error) {
                $preview['errors'][] = "Baris {$error['row']}: {$error['error']}";
            }
        }

        $preview['request_groups'] = count($groupSummaries);
        $preview['new'] = count($groupSummaries);

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

        $stats = [
            'total' => count($rows),
            'success' => 0,
            'failed' => 0,
            'requests_created' => 0,
            'errors' => [],
        ];

        $groups = $this->groupRows($rows);
        $userId = Auth::id();
        $documentNumberService = new DocumentNumberService();

        foreach ($groups as $group) {
            $validation = $this->validateGroup($group);

            if (! empty($validation['errors'])) {
                $stats['failed'] += count($group['rows']);
                array_push($stats['errors'], ...$validation['errors']);
                continue;
            }

            DB::beginTransaction();

            try {
                $branch = $validation['branch'];
                $requestNumber = $documentNumberService->generate(
                    'inventory_request',
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    $branch->id
                );

                $inventoryRequest = InventoryRequest::create([
                    'request_number' => $requestNumber,
                    'warehouse_id' => null,
                    'branch_id' => $branch->id,
                    'request_date' => now(),
                    'required_date' => $validation['required_date']->format('Y-m-d'),
                    'priority' => 'medium',
                    'reason' => $validation['reason'],
                    'notes' => $validation['notes'] ?: $validation['reason'],
                    'status' => 'draft',
                    'requested_by' => $userId,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);

                foreach ($validation['items'] as $item) {
                    $inventoryRequest->items()->create([
                        'master_product_id' => $item['master_product_id'],
                        'quantity' => $item['quantity'],
                        'notes' => $item['notes'],
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);
                }

                DB::commit();

                $stats['success'] += count($group['rows']);
                $stats['requests_created']++;
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('Inventory request import group failed: '.$e->getMessage());
                $stats['failed'] += count($group['rows']);
                $stats['errors'][] = [
                    'row' => $group['first_row_no'],
                    'error' => 'Gagal membuat request: '.$e->getMessage(),
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "Import selesai: {$stats['requests_created']} request dibuat, {$stats['failed']} baris gagal",
            'stats' => $stats,
        ]);
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @return array<string, array{first_row_no:int, rows:array<int, array{row_no:int, data:array<string, string>}>}>
     */
    private function groupRows(array $rows): array
    {
        $groups = [];

        foreach ($rows as $index => $row) {
            $key = $this->groupKey($row, $index);
            $groups[$key] ??= [
                'first_row_no' => $index + 2,
                'rows' => [],
            ];
            $groups[$key]['rows'][] = [
                'row_no' => $index + 2,
                'data' => $row,
            ];
        }

        return $groups;
    }

    private function groupKey(array $row, int $index): string
    {
        $provided = trim($row['request_group'] ?? '');
        if ($provided !== '') {
            return 'group:'.mb_strtolower($provided);
        }

        $parts = [
            trim($row['branch_code'] ?? ''),
            trim($row['branch_name'] ?? ''),
            trim($row['required_date'] ?? ''),
            trim($row['reason'] ?? ''),
            trim($row['notes'] ?? ''),
        ];

        $key = implode('|', array_map('mb_strtolower', $parts));

        return $key === '||||' ? 'row:'.$index : 'auto:'.$key;
    }

    /**
     * @param  array{first_row_no:int, rows:array<int, array{row_no:int, data:array<string, string>}>}  $group
     */
    private function validateGroup(array $group): array
    {
        $errors = [];
        $firstRow = $group['rows'][0]['data'] ?? [];
        $branch = $this->resolveBranch($firstRow);
        $requiredDate = $this->parseRequiredDate($firstRow['required_date'] ?? null);
        $reason = trim($firstRow['reason'] ?? '');
        $notes = trim($firstRow['notes'] ?? '');
        $items = [];

        if (! $branch) {
            $errors[] = [
                'row' => $group['first_row_no'],
                'error' => 'branch_code/branch_name tidak ditemukan atau tidak sesuai akses user',
            ];
        }

        if (! $requiredDate) {
            $errors[] = [
                'row' => $group['first_row_no'],
                'error' => 'required_date wajib format tanggal valid',
            ];
        } elseif ($requiredDate->lt(today())) {
            $errors[] = [
                'row' => $group['first_row_no'],
                'error' => 'required_date tidak boleh sebelum hari ini',
            ];
        }

        if ($reason === '') {
            $errors[] = [
                'row' => $group['first_row_no'],
                'error' => 'reason wajib diisi',
            ];
        }

        foreach ($group['rows'] as $entry) {
            $row = $entry['data'];
            $rowNo = $entry['row_no'];

            $rowBranch = $this->resolveBranch($row);
            if ($branch && $rowBranch && $rowBranch->id !== $branch->id) {
                $errors[] = ['row' => $rowNo, 'error' => 'branch dalam satu request_group harus sama'];
            }

            $rowDate = $this->parseRequiredDate($row['required_date'] ?? null);
            if ($requiredDate && $rowDate && ! $rowDate->isSameDay($requiredDate)) {
                $errors[] = ['row' => $rowNo, 'error' => 'required_date dalam satu request_group harus sama'];
            }

            $rowReason = trim($row['reason'] ?? '');
            if ($reason !== '' && $rowReason !== '' && $rowReason !== $reason) {
                $errors[] = ['row' => $rowNo, 'error' => 'reason dalam satu request_group harus sama'];
            }

            $product = $this->resolveProduct($row);
            if (! $product) {
                $errors[] = ['row' => $rowNo, 'error' => 'product_sku/product_name tidak ditemukan di master produk aktif'];
                continue;
            }

            $quantity = trim($row['quantity'] ?? '');
            if ($quantity === '' || ! is_numeric($quantity) || (float) $quantity <= 0) {
                $errors[] = ['row' => $rowNo, 'error' => 'quantity wajib angka lebih dari 0'];
                continue;
            }

            $productId = $product->id;
            $items[$productId] ??= [
                'master_product_id' => $productId,
                'quantity' => 0,
                'notes' => null,
            ];
            $items[$productId]['quantity'] += (float) $quantity;

            $itemNotes = trim($row['item_notes'] ?? '');
            if ($itemNotes !== '') {
                $notesList = array_filter([$items[$productId]['notes'], $itemNotes]);
                $items[$productId]['notes'] = implode("\n", array_unique($notesList));
            }
        }

        if (empty($items)) {
            $errors[] = [
                'row' => $group['first_row_no'],
                'error' => 'minimal satu item valid wajib ada',
            ];
        }

        return [
            'branch' => $branch,
            'required_date' => $requiredDate,
            'reason' => $reason,
            'notes' => $notes,
            'items' => array_values($items),
            'errors' => $errors,
        ];
    }

    private function resolveBranch(array $row): ?Branch
    {
        $branchCode = trim($row['branch_code'] ?? '');
        $branchName = trim($row['branch_name'] ?? '');
        $query = Branch::query()->where('is_active', true);

        if ($branchCode !== '') {
            $branch = (clone $query)->where('code', strtoupper($branchCode))->first();
        } elseif ($branchName !== '') {
            $branch = (clone $query)->where('name', $branchName)->first();
        } else {
            $branch = $this->primaryBranchForUser(Auth::user());
        }

        if (! $branch || ! $this->userCanUseBranch($branch)) {
            return null;
        }

        return $branch;
    }

    private function resolveProduct(array $row): ?MasterProduct
    {
        $sku = trim($row['product_sku'] ?? '');
        if ($sku !== '') {
            $product = MasterProduct::where('is_active', true)
                ->where('sku', $sku)
                ->first();

            if ($product) {
                return $product;
            }
        }

        $name = trim($row['product_name'] ?? '');
        if ($name !== '') {
            return MasterProduct::where('is_active', true)
                ->where('name', $name)
                ->first();
        }

        return null;
    }

    /**
     * Accepts the standard dashboard display format (d-M-Y, e.g. 29-Jun-2026)
     * as the primary format, plus ISO (Y-m-d) and Excel serial dates for
     * backward compatibility with files built before the format was fixed.
     */
    private function parseRequiredDate($value): ?Carbon
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->startOfDay();
            } catch (\Throwable) {
                return null;
            }
        }

        foreach (['d-M-Y', 'd/M/Y', 'Y-m-d', 'd-m-Y', 'd/m/Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->startOfDay();
            } catch (\Throwable) {
                continue;
            }
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function primaryBranchForUser(?User $user): ?Branch
    {
        if (! $user) {
            return null;
        }

        $assigned = $user->assignedBranches()
            ->where('branches.is_active', true)
            ->orderByDesc('branch_user.is_primary')
            ->orderBy('branches.name')
            ->first();

        if ($assigned) {
            return $assigned;
        }

        if ($user->branch_id) {
            return Branch::where('is_active', true)->find($user->branch_id);
        }

        return null;
    }

    private function userCanUseBranch(Branch $branch): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        if ((int) $user->branch_id === (int) $branch->id) {
            return true;
        }

        return $user->assignedBranches()
            ->where('branches.id', $branch->id)
            ->where('branches.is_active', true)
            ->exists();
    }
}
