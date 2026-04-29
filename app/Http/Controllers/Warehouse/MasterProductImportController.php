<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\MasterProduct;
use App\Models\ProductType;
use App\Models\ProductCategory;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use App\Models\RentalServiceFrequency;
use App\Models\User;
use App\Helpers\UnitHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MasterProductImportController extends Controller
{
    /**
     * Preview CSV file before import
     */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $file = $request->file('file');
        $rows = $this->parseCsv($file);

        $preview = [
            'total_rows' => count($rows) - 1, // Exclude header
            'existing' => 0,
            'new' => 0,
            'errors' => [],
            'preview_data' => []
        ];

        // Check first 10 rows for preview
        $previewRows = array_slice($rows, 1, 10);
        $existingSkus = MasterProduct::whereIn('sku', array_column($previewRows, 'ProductCode'))
            ->pluck('sku')
            ->toArray();

        foreach ($previewRows as $index => $row) {
            $sku = trim($row['ProductCode'] ?? '');
            if (empty($sku)) {
                $preview['errors'][] = "Row " . ($index + 2) . ": ProductCode is empty";
                continue;
            }

            if (in_array($sku, $existingSkus)) {
                $preview['existing']++;
            } else {
                $preview['new']++;
            }

            $preview['preview_data'][] = [
                'row' => $index + 2,
                'sku' => $sku,
                'name' => $row['ProductName'] ?? '',
                'exists' => in_array($sku, $existingSkus)
            ];
        }

        // Check all rows for existing products
        $allSkus = array_filter(array_column(array_slice($rows, 1), 'ProductCode'));
        $allExistingSkus = MasterProduct::whereIn('sku', $allSkus)->pluck('sku')->toArray();
        $preview['existing'] = count($allExistingSkus);
        $preview['new'] = count($allSkus) - count($allExistingSkus);

        return response()->json([
            'status' => 'success',
            'preview' => $preview
        ]);
    }

    /**
     * Import master products from CSV file
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $file = $request->file('file');
        $rows = $this->parseCsv($file);

        $stats = [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        // Process in batches of 50 rows to avoid timeout
        $batchSize = 50;
        $batches = array_chunk($rows, $batchSize);

        foreach ($batches as $batchIndex => $batch) {
            DB::beginTransaction();

            try {
                foreach ($batch as $index => $row) {
                    $stats['total']++;
                    $actualIndex = ($batchIndex * $batchSize) + $index;

                    // Skip header
                    if ($actualIndex === 0) {
                        continue;
                    }

                    try {
                        // Check if product already exists by SKU
                        $sku = trim($row['ProductCode'] ?? '');
                        if (empty($sku)) {
                            throw new \Exception('ProductCode (SKU) is required');
                        }

                        $existingProduct = MasterProduct::where('sku', $sku)->first();
                        if ($existingProduct) {
                            $stats['failed']++;
                            $stats['errors'][] = [
                                'row' => $actualIndex + 1,
                                'product_code' => $sku,
                                'error' => 'Product with SKU already exists: ' . $sku
                            ];
                            continue; // Skip this row
                        }

                        // Mapping
                        $productTypeId = $this->mapProductType(
                            $row['ProductType'] ?? '',
                            $row['Unit'] ?? '',
                            $row['ProductCategory'] ?? ''
                        );

                        $productCategoryId = $this->mapProductCategory($row['ProductCategory'] ?? '');
                        $createdBy = $this->mapUser($row['UserId'] ?? '');

                        // Transform
                        $dimensions = $this->transformDimensions(
                            $row['Length'] ?? 0,
                            $row['Width'] ?? 0,
                            $row['Height'] ?? 0
                        );

                        $description = $this->transformDescription(
                            $row['Specification'] ?? '',
                            $row['Specification2'] ?? ''
                        );

                        $isActive = $this->transformActiveStatus($row['FgActive'] ?? 'N');
                        $isTrading = $this->transformActiveStatus($row['FgTrading'] ?? 'N');
                        $isStockSubstitute = $this->transformActiveStatus($row['FgStockSubstitute'] ?? 'N');

                        // Get ProductType to get unit
                        $productType = ProductType::find($productTypeId);
                        $productTypeUnit = $productType ? $productType->unit : null;
                        
                        // Use UnitOrder from CSV if provided, otherwise use ProductType unit
                        $unitOrder = $this->normalizeUnit($row['UnitOrder'] ?? '');
                        if (empty($unitOrder) && $productTypeUnit) {
                            $unitOrder = $this->normalizeUnit($productTypeUnit);
                        }
                        
                        // Create MasterProduct
                        $masterProduct = MasterProduct::create([
                            'product_type_id' => $productTypeId,
                            'product_category_id' => $productCategoryId,
                            'name' => $row['ProductName'] ?? '',
                            'sku' => $row['ProductCode'] ?? '',
                            'part_no' => $this->cleanValue($row['PartNo'] ?? ''),
                            'description' => $description,
                            'dimensions' => $dimensions,
                            'unit' => $this->normalizeUnit($row['Unit'] ?? $productTypeUnit ?? ''),
                            'unit_order' => $unitOrder,
                            'net_weight' => $this->parseDecimal($row['NetWeight'] ?? 0),
                            'gross_weight' => $this->parseDecimal($row['GrossWeight'] ?? 0),
                            'lifetime' => $this->parseInteger($row['LifeTime'] ?? 0),
                            'frequency_service' => $this->mapFrequencyService($row['FrequencyService'] ?? ''),
                            'minimum_stock' => $this->parseInteger($row['QtyBuffer'] ?? 0),
                            'maximum_stock' => 0,
                            'is_active' => $isActive,
                            'is_trading' => $isTrading,
                            'is_stock_substitute' => $isStockSubstitute,
                            'created_by' => $createdBy,
                        ]);

                        // Create WarehouseProduct relasi
                        if (!empty($row['Warehouse']) && $row['Warehouse'] !== '\\N') {
                            $warehouse = Warehouse::where('warehouse_code', $row['Warehouse'])->first();
                            if ($warehouse) {
                                WarehouseProduct::create([
                                    'warehouse_id' => $warehouse->id,
                                    'master_product_id' => $masterProduct->id,
                                    'quantity' => 0,
                                    'minimum_stock' => $masterProduct->minimum_stock,
                                    'maximum_stock' => $masterProduct->maximum_stock ?? 0,
                                    'created_by' => $createdBy,
                                ]);
                            }
                        }

                        $stats['success']++;

                    } catch (\Exception $e) {
                        $stats['failed']++;
                        $stats['errors'][] = [
                            'row' => $actualIndex + 1,
                            'product_code' => $row['ProductCode'] ?? 'N/A',
                            'error' => $e->getMessage()
                        ];
                        Log::error("Import error row {$actualIndex}: " . $e->getMessage());
                    }
                }

                DB::commit();

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Batch {$batchIndex} failed: " . $e->getMessage());
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "Import selesai: {$stats['success']} berhasil, {$stats['failed']} gagal",
            'stats' => $stats
        ]);
    }

    /**
     * Map ProductType from CSV
     */
    private function mapProductType($csvProductType, $csvUnit, $csvCategory)
    {
        if (empty($csvProductType)) {
            throw new \Exception('ProductType is required');
        }

        $productType = ProductType::where('sku_prefix', strtoupper(trim($csvProductType)))->first();

        if (!$productType) {
            $nameMap = [
                'DIS' => 'Dispenser',
                'REF' => 'Refill',
                'CLN' => 'Cleaner',
            ];

            $isUnitMap = [
                'DIS' => true,
                'REF' => false,
                'CLN' => false,
            ];

            // Map category
            $categoryId = $this->mapProductCategory($csvCategory);

            $productType = ProductType::create([
                'product_category_id' => $categoryId,
                'name' => $nameMap[strtoupper($csvProductType)] ?? $csvProductType,
                'sku_prefix' => strtoupper(trim($csvProductType)),
                'unit' => $this->normalizeUnit($csvUnit),
                'is_unit' => $isUnitMap[strtoupper($csvProductType)] ?? false,
                'is_active' => true,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
        }

        return $productType->id;
    }

    /**
     * Map ProductCategory from CSV
     */
    private function mapProductCategory($csvCategory)
    {
        if (empty($csvCategory) || $csvCategory === '\\N') {
            return null;
        }

        $category = ProductCategory::where('name', trim($csvCategory))->first();

        if (!$category) {
            $category = ProductCategory::create([
                'name' => trim($csvCategory),
                'code' => strtoupper(substr(trim($csvCategory), 0, 3)),
                'is_active' => true,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
        }

        return $category->id;
    }

    /**
     * Normalize unit from CSV
     */
    private function normalizeUnit($csvUnit)
    {
        if (empty($csvUnit) || $csvUnit === '\\N') {
            return null;
        }

        $unitMap = [
            'UNIT' => 'unit',
            'BTL' => 'bottle',
            'PCS' => 'pcs',
            'ML' => 'ml',
            'KG' => 'kg',
        ];

        $upper = strtoupper(trim($csvUnit));
        return $unitMap[$upper] ?? strtolower(trim($csvUnit));
    }

    /**
     * Map Frequency Service from CSV
     */
    private function mapFrequencyService($csvFrequency)
    {
        if (empty($csvFrequency) || $csvFrequency === '\\N') {
            return null;
        }

        // Cari di rental_service_frequencies
        $frequency = RentalServiceFrequency::where('code', trim($csvFrequency))
            ->orWhere('name', 'like', '%' . trim($csvFrequency) . '%')
            ->first();

        return $frequency ? $frequency->code : trim($csvFrequency);
    }

    /**
     * Map User from CSV
     */
    private function mapUser($csvUserId)
    {
        if (empty($csvUserId) || $csvUserId === '\\N') {
            return Auth::id();
        }

        // Cari berdasarkan username atau email
        $user = User::where('username', trim($csvUserId))
            ->orWhere('email', trim($csvUserId))
            ->first();

        return $user ? $user->id : Auth::id();
    }

    /**
     * Transform dimensions from Length, Width, Height
     */
    private function transformDimensions($length, $width, $height)
    {
        $length = $this->parseDecimal($length);
        $width = $this->parseDecimal($width);
        $height = $this->parseDecimal($height);

        if (($length == 0 || !$length) && ($width == 0 || !$width) && ($height == 0 || !$height)) {
            return null;
        }

        return "{$length} × {$width} × {$height}";
    }

    /**
     * Transform description from Specification and Specification2
     */
    private function transformDescription($specification, $specification2)
    {
        $parts = array_filter([
            $this->cleanValue($specification),
            $this->cleanValue($specification2)
        ], function ($val) {
            return !empty($val) && $val !== '\\N';
        });

        return !empty($parts) ? implode(' | ', $parts) : null;
    }

    /**
     * Transform active status from Y/N to boolean
     */
    private function transformActiveStatus($fgActive)
    {
        return strtoupper(trim($fgActive)) === 'Y';
    }

    /**
     * Parse CSV file
     */
    private function parseCsv($file)
    {
        $rows = [];
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            throw new \Exception('Failed to open CSV file');
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            throw new \Exception('CSV file is empty or invalid');
        }

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === count($header)) {
                $rows[] = array_combine($header, $row);
            }
        }

        fclose($handle);
        return $rows;
    }

    /**
     * Clean value (handle \N and empty)
     */
    private function cleanValue($value)
    {
        $value = trim($value);
        return ($value === '\\N' || empty($value)) ? null : $value;
    }

    /**
     * Parse decimal value
     */
    private function parseDecimal($value)
    {
        $value = $this->cleanValue($value);
        return $value ? (float) $value : null;
    }

    /**
     * Parse integer value
     */
    private function parseInteger($value)
    {
        $value = $this->cleanValue($value);
        return $value ? (int) $value : 0;
    }
}
