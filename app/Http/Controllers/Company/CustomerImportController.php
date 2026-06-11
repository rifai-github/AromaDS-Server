<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\District;
use App\Models\Province;
use App\Models\Subdistrict;
use App\Services\Imports\SpreadsheetImportHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Bulk import of Customers from CSV/Excel.
 *
 * Preserves the business logic from CustomerController::store():
 * customer_code auto-generation via Customer::generateCustomerCode(), default
 * company_type 'PT', customer_type 'regular', status derived from is_active,
 * created_by/updated_by stamping.
 *
 * NOTE (deferred): PIC/Contact import (the customer_customer_contact pivot,
 * first contact = primary) is intentionally NOT handled here yet. Customers are
 * imported on their own; contacts will be a follow-up iteration. See plan.
 *
 * FK lookups (customer_category, province/city/district/subdistrict) are
 * resolved by name. A provided-but-unmatched value fails the row rather than
 * silently inserting NULL; regions are never auto-created.
 */
class CustomerImportController extends Controller
{
    private const TEMPLATE_HEADERS = [
        'name', 'phone', 'email', 'address', 'company_type', 'is_pkp',
        'customer_category', 'province', 'city', 'district', 'subdistrict',
        'postal_code', 'npwp', 'customer_group', 'label_alias', 'is_active',
    ];

    public function template(Request $request)
    {
        $format = $request->query('format', 'xlsx');

        $sample = [
            ['PT Contoh Sejahtera', '021-555000', 'info@contoh.co.id', 'Jl. Contoh No. 1, Jakarta', 'PT', 'Y', 'Corporate', 'DKI Jakarta', 'Jakarta Selatan', '', '', '12345', '01.234.567.8-901.000', '', 'Contoh', 'Y'],
            ['Toko Maju Jaya', '0812000000', '', 'Bandung', 'CV', 'N', '', '', '', '', '', '', '', '', '', 'Y'],
        ];

        return SpreadsheetImportHelper::downloadTemplate(
            self::TEMPLATE_HEADERS,
            $sample,
            'template-import-customer',
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
            $name = trim($row['name'] ?? '');

            if ($name === '') {
                $preview['errors'][] = "Baris {$rowNo}: name kosong";

                continue;
            }

            foreach ($this->lookupWarnings($row, $rowNo) as $warning) {
                $preview['errors'][] = $warning;
            }

            // Customers are always created new (no unique key enforced); we just
            // surface a heads-up if an identical name already exists.
            if (Customer::where('name', $name)->exists()) {
                $preview['existing']++;
            } else {
                $preview['new']++;
            }

            if (count($preview['preview_data']) < 10) {
                $preview['preview_data'][] = [
                    'row' => $rowNo,
                    'name' => $name,
                    'email' => trim($row['email'] ?? ''),
                    'phone' => trim($row['phone'] ?? ''),
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
                        $name = trim($row['name'] ?? '');
                        if ($name === '') {
                            throw new \Exception('name wajib diisi');
                        }

                        $categoryId = $this->resolveLookupId(CustomerType::class, $row['customer_category'] ?? '', 'customer_category');
                        $provinceId = $this->resolveLookupId(Province::class, $row['province'] ?? '', 'province');
                        $cityId = $this->resolveLookupId(City::class, $row['city'] ?? '', 'city');
                        $districtId = $this->resolveLookupId(District::class, $row['district'] ?? '', 'district');
                        $subdistrictId = $this->resolveLookupId(Subdistrict::class, $row['subdistrict'] ?? '', 'subdistrict');

                        $isActive = $this->boolFromYn($row['is_active'] ?? 'Y');

                        Customer::create([
                            'customer_code' => Customer::generateCustomerCode($name),
                            'name' => $name,
                            'phone' => trim($row['phone'] ?? '') ?: null,
                            'email' => trim($row['email'] ?? '') ?: null,
                            'address' => trim($row['address'] ?? '') ?: null,
                            'company_type' => trim($row['company_type'] ?? '') ?: 'PT',
                            'customer_category_id' => $categoryId,
                            'status' => $isActive ? 'active' : 'inactive',
                            'customer_group' => trim($row['customer_group'] ?? '') ?: null,
                            'npwp' => trim($row['npwp'] ?? '') ?: null,
                            'province_id' => $provinceId,
                            'city_id' => $cityId,
                            'district_id' => $districtId,
                            'subdistrict_id' => $subdistrictId,
                            'postal_code' => trim($row['postal_code'] ?? '') ?: null,
                            'label_alias' => trim($row['label_alias'] ?? '') ?: null,
                            'is_pkp' => $this->boolFromYn($row['is_pkp'] ?? 'N'),
                            'is_active' => $isActive,
                            'customer_type' => 'regular',
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
                Log::error('Customer import batch failed: '.$e->getMessage());
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "Import selesai: {$stats['success']} berhasil, {$stats['failed']} gagal",
            'stats' => $stats,
        ]);
    }

    /**
     * Build preview warnings for any provided-but-unmatched lookup values.
     *
     * @return array<int, string>
     */
    private function lookupWarnings(array $row, int $rowNo): array
    {
        $warnings = [];
        $checks = [
            'customer_category' => CustomerType::class,
            'province' => Province::class,
            'city' => City::class,
            'district' => District::class,
            'subdistrict' => Subdistrict::class,
        ];

        foreach ($checks as $column => $model) {
            $value = trim($row[$column] ?? '');
            if ($value !== '' && ! $model::where('name', $value)->exists()) {
                $warnings[] = "Baris {$rowNo}: {$column} tidak ditemukan ('{$value}')";
            }
        }

        return $warnings;
    }

    /**
     * Resolve a lookup id by exact name. Blank -> null (optional). A provided
     * value that does not match throws, so the row is reported, not silently
     * nulled.
     *
     * @param  class-string  $model
     */
    private function resolveLookupId(string $model, string $value, string $label): ?int
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $record = $model::where('name', $value)->first();
        if (! $record) {
            throw new \Exception("{$label} tidak ditemukan: '{$value}'");
        }

        return $record->id;
    }

    private function boolFromYn($value): bool
    {
        $value = strtoupper(trim((string) $value));

        return in_array($value, ['Y', 'YES', '1', 'TRUE', 'AKTIF'], true);
    }
}
