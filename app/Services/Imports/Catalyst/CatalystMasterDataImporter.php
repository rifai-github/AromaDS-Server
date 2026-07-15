<?php

namespace App\Services\Imports\Catalyst;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CatalystMasterDataImporter
{
    private const SOURCE_SYSTEM = 'catalyst';

    private array $stepDependencies = [
        'users' => ['branches', 'departments'],
        'warehouse_product_links' => ['warehouses', 'master_products'],
        'master_rentals' => ['master_products'],
        'rental_components' => ['master_rentals'],
        'rental_details' => ['master_rentals'],
        'customer_contacts' => ['customers'],
        'customer_tax_settings' => ['customers'],
        'company_virtual_accounts' => ['customers'],
        'building_customers' => ['customers', 'buildings'],
        'surveys' => ['customers', 'users', 'buildings'],
        'survey_details' => ['surveys'],
        'quotations' => ['customers', 'surveys', 'users', 'branches'],
        'quotation_surveys' => ['quotations', 'surveys'],
        'quotation_rooms' => ['quotations', 'survey_details'],
        'quotation_rentals' => ['quotations', 'quotation_rooms', 'master_rentals'],
        'quotation_details' => ['quotations', 'survey_details', 'master_rentals'],
        'contracts' => ['customers', 'quotations', 'users'],
        'contract_surveys' => ['contracts', 'quotation_surveys'],
        'billing_groups' => ['contracts', 'customers'],
        'contract_buildings' => ['billing_groups', 'buildings'],
        'contract_rooms' => ['contracts', 'buildings'],
        'contract_rentals' => ['contracts', 'master_rentals'],
    ];

    private array $steps = [
        'product_categories',
        'product_types',
        'banks',
        'branches',
        'departments',
        'users',               // depends on: branches, departments
        'customer_categories',
        'customer_types',
        'warehouse_types',
        'warehouses',
        'master_products',
        'warehouse_product_links',
        'master_rentals',
        'rental_components',
        'rental_details',
        'customers',
        'customer_contacts',
        'customer_tax_settings',
        'company_virtual_accounts',
        'buildings',
        'building_customers',
        // ── Transactional data ────────────────────────────────────────────
        'surveys',             // depends on: customers, users, branches, buildings
        'survey_details',      // depends on: surveys
        'quotations',          // depends on: customers, users, branches
        'quotation_surveys',   // depends on: quotations, surveys
        'quotation_rooms',     // depends on: quotations, buildings
        'quotation_rentals',   // depends on: quotations, quotation_rooms, master_rentals
        'quotation_details',   // depends on: quotations, surveys, survey_details, master_rentals
        'contracts',           // depends on: customers, quotations, users
        'contract_surveys',    // depends on: contracts, surveys
        'billing_groups',      // depends on: contracts, customers, buildings
        'contract_buildings',  // depends on: billing_groups, buildings
        'contract_rooms',      // depends on: contracts, buildings
        'contract_rentals',    // depends on: contracts, master_rentals
    ];

    private bool $apply = false;
    private int $batchId;
    private int $chunkSize;
    private int $heartbeatEvery;
    private int $tempIdCounter = -1;
    private array $tableColumns = [];
    private array $runtimeMap = [];
    private array $sourceCityLookup = [];
    private array $sourceAreaLookup = [];
    private array $sourceCustomerTypeLookup = [];
    private array $sourceCustomerAddressLookup = [];
    private ?array $sourceQuotationHeadersByNumber = null;
    private ?array $sourceQuotationRentalRowsCache = null;
    private ?array $sourceQuotationPrimaryBuildingByNumber = null;
    private ?array $sourceQuotationOldContractByNumber = null;
    private ?array $sourceBillingGroupsByCode = null;
    private array $targetCityLookup = [];
    private array $targetRentalServiceFrequencyLookup = [];
    private array $targetProductCategoryLookup = [];
    private array $targetProductTypeLookup = [];
    private array $targetProductTypeCategoryLookup = [];
    private array $targetMasterProductCategoryLookup = [];
    private array $targetMasterProductTypeLookup = [];
    private array $targetMasterProductIdsByTypeLookup = [];
    private array $targetMasterProductIdsByCategoryLookup = [];
    private array $sourceMarketingRentalProductCodes = [];
    private array $postalAdministrativeAreaLookup = [];
    private array $targetRowCache = [];
    private $progressCallback = null;
    private ?int $actorUserId = null;
    private ?int $defaultImportCompanyId = null;

    public function run(array $requestedSteps = [], bool $apply = false, ?string $batchName = null, ?callable $progressCallback = null): array
    {
        if (!extension_loaded('sqlsrv') && !extension_loaded('pdo_sqlsrv')) {
            throw new RuntimeException('PHP extension sqlsrv / pdo_sqlsrv belum aktif di CLI.');
        }

        $this->apply = $apply;
        $this->chunkSize = max(1, (int) config('catalyst-import.chunk_size', 250));
        $this->heartbeatEvery = max(250, $this->chunkSize);
        $this->progressCallback = $progressCallback;

        $steps = $this->resolveSteps($requestedSteps);
        $this->source()->getPdo();
        $this->ensureImportMapIndexes();
        $this->loadSourceLookups();
        $this->loadTargetCityLookup();
        $this->loadTargetRentalLookups();

        $this->batchId = DB::table('source_import_batches')->insertGetId([
            'source_system' => self::SOURCE_SYSTEM,
            'source_database' => (string) config('catalyst-import.source.database'),
            'batch_name' => $batchName ?: 'Catalyst Master Data Import',
            'mode' => $apply ? 'apply' : 'dry-run',
            'status' => 'running',
            'steps' => json_encode($steps),
            'options' => json_encode(['chunk_size' => $this->chunkSize]),
            'started_at' => now(),
            'created_by' => $this->actorId(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->warmRuntimeMap();

        $summary = [
            'mode' => $apply ? 'apply' : 'dry-run',
            'steps' => [],
            'totals' => $this->blankStats(),
        ];

        try {
            foreach ($steps as $step) {
                $stepSummary = $this->{$step}();
                $summary['steps'][$step] = $stepSummary;
                $summary['totals'] = $this->mergeStats($summary['totals'], $stepSummary['stats']);
                $this->notifyProgress($step, $stepSummary);
            }

            DB::table('source_import_batches')->where('id', $this->batchId)->update([
                'status' => 'completed',
                'summary' => json_encode($summary),
                'finished_at' => now(),
                'updated_at' => now(),
            ]);

            return ['batch_id' => $this->batchId, 'summary' => $summary];
        } catch (Throwable $e) {
            $this->log('batch', 'error', 'Import batch failed unexpectedly.', ['exception' => $e->getMessage()]);

            DB::table('source_import_batches')->where('id', $this->batchId)->update([
                'status' => 'failed',
                'summary' => json_encode($summary),
                'finished_at' => now(),
                'updated_at' => now(),
            ]);

            throw $e;
        }
    }

    protected function product_categories(): array
    {
        return $this->runStep('product_categories', 'MsProductCategoryInfo', 'CategoryCode', function (array $row) {
            $sourceKey = $this->makeKey($row['CategoryCode'] ?? null);
            $name = $this->cleanString($row['CategoryName'] ?? null);

            if (!$sourceKey || !$name) {
                return $this->failedRow('Source category code or name is empty.');
            }

            return $this->syncRecord('product_categories', 'MsProductCategoryInfo', $sourceKey, 'product_categories', [
                'name' => $name,
            ], [
                'code' => $sourceKey,
                'description' => $this->buildSourceDescription(['CategoryCode' => $sourceKey]),
                'is_active' => $this->yesNoToBool($row['FgActive'] ?? null, true),
            ], $row);
        });
    }

    protected function product_types(): array
    {
        return $this->runStep('product_types', 'MsProductType', 'ProductTypeCode', function (array $row) {
            $sourceKey = $this->makeKey($row['ProductTypeCode'] ?? null);
            $name = $this->cleanString($row['ProductTypeName'] ?? null);

            if (!$sourceKey || !$name) {
                return $this->failedRow('Source product type code or name is empty.');
            }

            return $this->syncRecord('product_types', 'MsProductType', $sourceKey, 'product_types', [
                'sku_prefix' => $sourceKey,
            ], [
                'product_category_id' => $this->resolveCatalystProductCategoryIdForType($sourceKey, $row),
                'name' => $name,
                'description' => $this->buildSourceDescription([
                    'SourceCode' => $sourceKey,
                    'ProductCategory' => $this->cleanString($row['ProductCategory'] ?? null),
                ]),
                'unit' => 'UNIT',
                'has_serial_number' => $this->yesNoToBool($row['SNScanRequired'] ?? null, false),
                'is_unit' => in_array($this->cleanString($row['ProductCategory'] ?? null), ['Rental', 'Fixed Asset'], true),
                'is_active' => $this->yesNoToBool($row['FgActive'] ?? null, true),
            ], $row);
        });
    }

    protected function banks(): array
    {
        return $this->runStep('banks', 'MsBank', 'BankCode', function (array $row) {
            $sourceKey = $this->makeKey($row['BankCode'] ?? null);
            $name = $this->cleanString($row['BankName'] ?? null);

            if (!$sourceKey || !$name) {
                return $this->failedRow('Source bank code or name is empty.');
            }

            return $this->syncRecord('banks', 'MsBank', $sourceKey, 'banks', [
                'bank_code' => $sourceKey,
            ], [
                'bank_name' => $name,
                'name' => $name,
                'is_active' => $this->yesNoToBool($row['FgActive'] ?? null, true),
            ], $row);
        });
    }
    protected function branches(): array
    {
        return $this->runStep('branches', 'MsBranch', 'BranchCode', function (array $row) {
            $sourceKey = $this->makeKey($row['BranchCode'] ?? null);
            $name = $this->cleanString($row['BranchName'] ?? null);
            $city = $this->resolveSourceCity($row['City'] ?? null);

            if (!$sourceKey || !$name) {
                return $this->failedRow('Source branch code or name is empty.');
            }

            return $this->syncRecord('branches', 'MsBranch', $sourceKey, 'branches', [
                'code' => $sourceKey,
            ], [
                'name' => $name,
                'address_type' => 'office',
                'address_1' => $this->cleanString($row['Address'] ?? null),
                'phone_1' => $this->cleanString($row['Telephone'] ?? null),
                'fax' => $this->cleanString($row['Fax'] ?? null),
                'postal_code' => null,
                'city_id' => $city['target_city_id'] ?? null,
                'province_id' => $city['target_province_id'] ?? null,
                'has_warehouse' => !blank($this->cleanString($row['WarehouseRR'] ?? null)),
                'is_taxable' => false,
                'is_active' => $this->yesNoToBool($row['FgActive'] ?? null, true),
                'description' => $this->buildSourceDescription([
                    'CityCode' => $this->cleanString($row['City'] ?? null),
                    'CityName' => $city['name'] ?? null,
                    'WarehouseRR' => $this->cleanString($row['WarehouseRR'] ?? null),
                ]),
            ], $row);
        });
    }

    protected function departments(): array
    {
        return $this->runStep('departments', 'MsDepartment', 'DeptCode', function (array $row) {
            $sourceKey = $this->makeKey($row['DeptCode'] ?? null);
            $name = $this->cleanString($row['DeptName'] ?? null);

            if (!$sourceKey || !$name) {
                return $this->failedRow('Source department code or name is empty.');
            }

            return $this->syncRecord('departments', 'MsDepartment', $sourceKey, 'departments', [
                'name' => $name,
            ], [
                'sub_department' => $this->cleanString($row['DeptGroup'] ?? null),
                'description' => $this->buildSourceDescription([
                    'DeptCode' => $sourceKey,
                    'DeptLevel' => $row['DeptLevel'] ?? null,
                ]),
                'system_reserved' => false,
                'is_active' => $this->yesNoToBool($row['FgActive'] ?? null, true),
            ], $row);
        });
    }

    protected function customer_categories(): array
    {
        return $this->runStep('customer_categories', 'MsCustGroup', 'CustGroupCode', function (array $row) {
            $sourceKey = $this->makeKey($row['CustGroupCode'] ?? null);
            $name = $this->cleanString($row['CustGroupName'] ?? null);

            if (!$sourceKey || !$name) {
                return $this->failedRow('Source customer category code or name is empty.');
            }

            return $this->syncRecord('customer_categories', 'MsCustGroup', $sourceKey, 'customer_categories', [
                'name' => $name,
            ], [
                'description' => $this->buildSourceDescription([
                    'CustGroupCode' => $sourceKey,
                    'CustGroupType' => $this->cleanString($row['CustGroupType'] ?? null),
                    'FgPKP' => $this->cleanString($row['FgPKP'] ?? null),
                ]),
                'is_active' => $this->yesNoToBool($row['FgActive'] ?? null, true),
            ], $row);
        });
    }

    protected function customer_types(): array
    {
        return $this->runStep('customer_types', 'MsCustType', 'CustTypeCode', function (array $row) {
            $sourceKey = $this->makeKey($row['CustTypeCode'] ?? null);
            $name = $this->cleanString($row['CustTypeName'] ?? null);

            if (!$sourceKey || !$name) {
                return $this->failedRow('Source customer type code or name is empty.');
            }

            return $this->syncRecord('customer_types', 'MsCustType', $sourceKey, 'customer_types', [
                'name' => $name,
            ], [
                'description' => $this->buildSourceDescription(['CustTypeCode' => $sourceKey]),
                'is_active' => $this->yesNoToBool($row['FgActive'] ?? null, true),
            ], $row);
        });
    }

    protected function warehouse_types(): array
    {
        $rows = $this->source()->table('MsWarehouse')
            ->select('WrhsType', DB::raw('MAX(FgActive) as FgActive'))
            ->whereNotNull('WrhsType')
            ->groupBy('WrhsType')
            ->orderBy('WrhsType')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        return $this->runRows('warehouse_types', 'MsWarehouse', $rows, function (array $row) {
            $name = $this->cleanString($row['WrhsType'] ?? null);

            if (!$name) {
                return $this->failedRow('Source warehouse type is empty.');
            }

            $sourceKey = $this->makeKey($name);

            return $this->syncRecord('warehouse_types', 'MsWarehouse', $sourceKey, 'warehouse_types', [
                'code' => $this->warehouseTypeCode($name),
            ], [
                'name' => $name,
                'description' => $this->buildSourceDescription(['WrhsType' => $name]),
                'is_active' => $this->yesNoToBool($row['FgActive'] ?? null, true),
            ], $row);
        });
    }

    protected function warehouses(): array
    {
        return $this->runStep('warehouses', 'MsWarehouse', 'WrhsCode', function (array $row) {
            $sourceKey = $this->makeKey($row['WrhsCode'] ?? null);
            $name = $this->cleanString($row['WrhsName'] ?? null);

            if (!$sourceKey || !$name) {
                return $this->failedRow('Source warehouse code or name is empty.');
            }

            $branchId = $this->findMappedTargetId('MsBranch', $this->makeKey($row['Branch'] ?? null), 'branches');
            $typeId = $this->findMappedTargetId('MsWarehouse', $this->makeKey($row['WrhsType'] ?? null), 'warehouse_types');

            if (!$branchId || !$typeId) {
                return $this->skippedRow('Warehouse branch or type could not be resolved.', $sourceKey);
            }

            return $this->syncRecord('warehouses', 'MsWarehouse', $sourceKey, 'warehouses', [
                'warehouse_code' => $sourceKey,
            ], [
                'name' => $name,
                'branch_id' => $branchId,
                'warehouse_type_id' => $typeId,
                'address' => null,
                'phone' => null,
                'is_active' => $this->yesNoToBool($row['FgActive'] ?? null, true),
                'is_center' => false,
            ], $row);
        });
    }

    protected function master_products(): array
    {
        return $this->runStep('master_products', 'MsProduct', 'ProductCode', function (array $row) {
            $sourceKey = $this->makeKey($row['ProductCode'] ?? null);
            $name = $this->cleanString($row['ProductName'] ?? null);
            $productTypeId = $this->findMappedTargetId('MsProductType', $this->makeKey($row['ProductType'] ?? null), 'product_types');
            $productCategoryId = $this->resolveCatalystProductCategoryIdForProduct($row, $productTypeId);

            if (!$sourceKey || !$name || !$productTypeId) {
                return $this->failedRow('Product code, name, or type mapping is missing.');
            }

            return $this->syncRecord('master_products', 'MsProduct', $sourceKey, 'master_products', [
                'sku' => $sourceKey,
            ], [
                'product_type_id' => $productTypeId,
                'product_category_id' => $productCategoryId,
                'name' => $name,
                'part_no' => $this->cleanString($row['PartNo'] ?? null),
                'description' => $this->combineLines($row['Specification'] ?? null, $row['Specification2'] ?? null),
                'description_2' => $this->cleanString($row['Specification2'] ?? null),
                'unit' => $this->cleanString($row['Unit'] ?? null) ?: 'UNIT',
                'unit_order' => $this->cleanString($row['UnitOrder'] ?? null),
                'net_weight' => $row['NetWeight'] ?? null,
                'gross_weight' => $row['GrossWeight'] ?? null,
                'lifetime' => $row['LifeTime'] ?? null,
                'minimum_stock' => 0,
                'maximum_stock' => 0,
                'bom_quantity' => $row['QtyBuffer'] ?? 0,
                'is_trading' => $this->yesNoToBool($row['FgTrading'] ?? null, false),
                'is_stock_substitute' => $this->yesNoToBool($row['FgStockSubstitute'] ?? null, false),
                'is_active' => $this->yesNoToBool($row['FgActive'] ?? null, true),
            ], $row);
        });
    }

    protected function warehouse_product_links(): array
    {
        return $this->runStep('warehouse_product_links', 'MsProduct', 'ProductCode', function (array $row) {
            $productKey = $this->makeKey($row['ProductCode'] ?? null);
            $warehouseKey = $this->makeKey($row['Warehouse'] ?? null);
            $sourceKey = $this->makeKey([$productKey, $warehouseKey]);

            if (!$productKey || !$warehouseKey) {
                return $this->skippedRow('Product warehouse source code is empty.', $sourceKey ?: $productKey);
            }

            $masterProductId = $this->findMappedTargetId('MsProduct', $productKey, 'master_products');
            $warehouseId = $this->findMappedTargetId('MsWarehouse', $warehouseKey, 'warehouses');

            if (!$masterProductId || !$warehouseId) {
                return $this->skippedRow('Product warehouse relation could not resolve its product or warehouse target.', $sourceKey);
            }

            return $this->syncRecord('warehouse_product_links', 'MsProduct', $sourceKey, 'warehouse_products', [
                'warehouse_id' => $warehouseId,
                'master_product_id' => $masterProductId,
            ], [
                // Presence marker only; actual stock remains managed per warehouse movement.
                'quantity' => 0,
                'minimum_stock' => 0,
                'maximum_stock' => 0,
                'deleted_at' => null,
            ], $row);
        }, fn ($query) => $query->whereNotNull('Warehouse')->where('Warehouse', '!=', ''));
    }

    protected function master_rentals(): array
    {
        $sourceCodes = $this->sourceMarketingRentalProductCodes();

        $rows = $this->source()->table('MsProduct')
            ->when(!empty($sourceCodes), function ($query) use ($sourceCodes) {
                $query->where(function ($inner) use ($sourceCodes) {
                    $inner->where('ProductType', 'RNT')
                        ->orWhereIn('ProductCode', $sourceCodes);
                });
            }, function ($query) {
                $query->where('ProductType', 'RNT');
            })
            ->orderBy('ProductCode')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        return $this->runRows('master_rentals', 'MsProduct', $rows, function (array $row) {
            $sourceKey = $this->makeKey($row['ProductCode'] ?? null);
            $name = $this->cleanString($row['ProductName'] ?? null);

            if (!$sourceKey || !$name) {
                return $this->failedRow('Rental source product code or name is empty.');
            }

            return $this->syncRecord('master_rentals', 'MsProduct', $sourceKey, 'master_rentals', [
                'rental_code' => $sourceKey,
            ], [
                'rental_name' => $name,
                'description' => $this->combineLines($row['Specification'] ?? null, $row['Specification2'] ?? null),
                'service_frequency' => $this->cleanString($row['FrequencyService'] ?? null),
                'service_frequency_id' => $this->resolveRentalServiceFrequencyId($row['FrequencyService'] ?? null),
                'category' => $this->cleanString($row['ProductCategory'] ?? null)
                    ?: $this->cleanString($row['CategoryInfo'] ?? null)
                    ?: 'Rental',
                'daily_price' => 0,
                'monthly_price' => 0,
                'unit' => $this->cleanString($row['UnitOrder'] ?? null) ?: $this->cleanString($row['Unit'] ?? null) ?: 'UNIT',
                'is_active' => $this->yesNoToBool($row['FgActive'] ?? null, true),
            ], $row);
        });
    }

    protected function rental_components(): array
    {
        return $this->runStep('rental_components', 'MsRentalBOM', 'ProductRental', function (array $row) {
            $rentalKey = $this->makeKey($row['ProductRental'] ?? null);
            $component = $this->cleanString($row['MaterialType'] ?? null);
            $sourceKey = $this->makeKey([$rentalKey, $component]);
            $masterRentalId = $this->findMappedTargetId('MsProduct', $rentalKey, 'master_rentals');

            if (!$rentalKey || !$component || !$masterRentalId) {
                return $this->skippedRow('Rental component could not resolve its rental or material type.', $sourceKey);
            }

            return $this->syncRecord('rental_components', 'MsRentalBOM', $sourceKey, 'rental_components', [
                'master_rental_id' => $masterRentalId,
                'component_name' => $component,
            ], [
                'description' => $this->buildSourceDescription([
                    'Material' => $this->cleanString($row['Material'] ?? null),
                    'TopLevel' => $row['TopLevel'] ?? null,
                ]),
                'quantity' => (int) round((float) ($row['Qty'] ?? 0)),
                'unit' => 'UNIT',
                'replacement_frequency_months' => (int) ($row['XFreqService'] ?? 0),
                'replacement_price' => 0,
                'is_activation_component' => false,
                'is_active' => $this->yesNoToBool($row['FgActive'] ?? null, true),
                'sort_order' => 0,
            ], $row);
        });
    }

    protected function rental_details(): array
    {
        return $this->runStep('rental_details', 'MsRentalBOM', 'ProductRental', function (array $row) {
            $rentalKey = $this->makeKey($row['ProductRental'] ?? null);
            $component = $this->cleanString($row['MaterialType'] ?? null);
            $materialKey = $this->makeKey($row['Material'] ?? null);
            $bomQty = (float) ($row['Qty'] ?? 1);
            $frequencyMultiplier = $this->resolveRentalDetailFrequencyMultiplier($row['XFreqService'] ?? null);
            // Group repeated source rows for the same rental/component into one detail row.
            $sourceKey = $this->makeKey([$rentalKey, $component, $frequencyMultiplier, number_format($bomQty, 4, '.', '')]);
            $masterRentalId = $this->findMappedTargetId('MsProduct', $rentalKey, 'master_rentals');

            if (!$rentalKey || !$component || !$masterRentalId) {
                return $this->skippedRow('Rental detail could not resolve its rental or component type.', $sourceKey);
            }

            if (!$this->yesNoToBool($row['FgActive'] ?? null, true)) {
                return $this->skippedRow('Inactive rental detail source row skipped.', $sourceKey);
            }

            $masterProductId = $this->resolveRentalDetailProductId($materialKey, $rentalKey, $component);
            $productCategoryId = $this->resolveRentalDetailCategoryId($component, $masterProductId);
            $productTypeId = $this->resolveRentalDetailProductTypeId($component);
            $selectedProductIds = $this->resolveRentalDetailSelectedProductIds(
                $rentalKey,
                $component,
                $masterProductId,
                $productTypeId,
                $productCategoryId
            );
            $itemType = $masterProductId
                ? 'product'
                : ($productTypeId ? 'product_type' : ($productCategoryId ? 'product_category' : 'product'));
            $itemId = $masterProductId ?: ($productTypeId ?: $productCategoryId);

            $result = $this->syncRecord('rental_details', 'MsRentalBOM', $sourceKey, 'rental_details', [
                'master_rental_id' => $masterRentalId,
                'product_category_id' => $productCategoryId,
                'product_type_id' => $productTypeId,
                'service_frequency_multiplier' => $frequencyMultiplier,
                'bom_rental_qty' => $bomQty,
            ], [
                'master_product_id' => $masterProductId,
                'item_type' => $itemType,
                'item_id' => $itemId,
                'quantity' => $bomQty,
                'bom_rental_qty' => $bomQty,
                'auto_expand' => !$masterProductId && (bool) ($productTypeId || $productCategoryId),
                'unit' => 'UNIT',
            ], $row);

            if ($this->apply && ($result['target_id'] ?? 0) > 0) {
                $this->syncRentalDetailMaterials((int) $result['target_id'], $selectedProductIds);
            }

            return $result;
        });
    }

    protected function customers(): array
    {
        return $this->runStep('customers', 'MsCustomer', 'CustCode', function (array $row) {
            $sourceKey = $this->makeKey($row['CustCode'] ?? null);
            $name = $this->cleanString($row['CustName'] ?? null);
            $categoryId = $this->findMappedTargetId('MsCustGroup', $this->makeKey($row['CustGroup'] ?? null), 'customer_categories');
            $companyTypeName = $this->sourceCustomerTypeLookup[$this->makeKey($row['CustType'] ?? null)] ?? null;
            $fallbackAddress = $this->sourceCustomerAddressLookup[$sourceKey] ?? [];
            $city = $this->resolveSourceCity($row['City'] ?? null);
            if (($city['target_city_id'] ?? null) === null && !empty($fallbackAddress['city_code'])) {
                $city = $this->resolveSourceCity($fallbackAddress['city_code']);
            }
            $postalCode = $this->cleanString($row['ZipCode'] ?? null) ?: ($fallbackAddress['postal_code'] ?? null);
            $resolvedAddress = $this->combineLines(
                $row['Address1'] ?? null,
                $row['Address2'] ?? null,
                $fallbackAddress['address_1'] ?? null,
                $fallbackAddress['address_2'] ?? null,
            );
            $location = $this->resolveAdministrativeAreaByPostalCode(
                $postalCode,
                $city['name'] ?? null,
                $city['target_province_id'] ?? null
            );
            $customerGroupName = $categoryId
                ? DB::table('customer_categories')->where('id', $categoryId)->value('name')
                : null;

            if (!$sourceKey || !$name) {
                return $this->failedRow('Customer code or name is empty.');
            }

            return $this->syncRecord('customers', 'MsCustomer', $sourceKey, 'customers', [
                'customer_code' => $sourceKey,
            ], [
                'name' => $name,
                'status' => 'customer',
                'customer_type' => 'company',
                'company_type' => $this->resolveCompanyType($companyTypeName),
                'tax_code' => $this->cleanString($row['NPWP'] ?? null),
                'ppn_code' => $this->cleanString($row['KodePPn'] ?? null),
                'customer_group' => $customerGroupName,
                'npwp' => $this->cleanString($row['NPWP'] ?? null),
                'nib' => $this->cleanString($row['NoNIB'] ?? null),
                'nib_number' => $this->cleanString($row['NoNIB'] ?? null),
                'is_pkp' => $this->yesNoToBool($row['FgPPN'] ?? null, false),
                'is_active' => $this->yesNoToBool($row['FgActive'] ?? null, true),
                'grace_period_days' => $row['GracePeriod'] ?? null,
                'default_payment' => 'credit',
                'member_since' => $this->toDate($row['UserDate'] ?? null),
                'email' => $this->cleanString($row['Email'] ?? null),
                'phone' => $this->normalizePhoneLike($row['Phone'] ?? null) ?: ($fallbackAddress['phone'] ?? null),
                'address' => $resolvedAddress,
                'city' => $city['name'] ?? null,
                'postal_code' => $postalCode,
                'customer_category_id' => $categoryId,
                'province_id' => $city['target_province_id'] ?? null,
                'district_id' => $location['district_id'] ?? null,
                'subdistrict_id' => $location['subdistrict_id'] ?? null,
                'description' => $this->buildSourceDescription([
                    'CustGroup' => $this->cleanString($row['CustGroup'] ?? null),
                    'CustType' => $this->cleanString($row['CustType'] ?? null),
                    'PaymentTo' => $this->cleanString($row['PaymentTo'] ?? null),
                    'Term' => $this->cleanString($row['Term'] ?? null),
                    'Branch' => $this->cleanString($row['Branch'] ?? null),
                    'FallbackDeliveryCode' => $fallbackAddress['delivery_code'] ?? null,
                    'FallbackDeliveryType' => $fallbackAddress['delivery_type'] ?? null,
                ]),
                'notes' => $this->cleanString($row['FPCustKode'] ?? null),
            ], $row);
        });
    }

    protected function customer_contacts(): array
    {
        $rows = $this->source()->table('MsCustContact')
            ->orderBy('CustCode')
            ->orderBy('ItemNo')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        return $this->runRows('customer_contacts', 'MsCustContact', $rows, function (array $row) {
            $customerKey = $this->makeKey($row['CustCode'] ?? null);
            $customerId = $this->findMappedTargetId('MsCustomer', $customerKey, 'customers');
            $name = $this->cleanString($row['ContactName'] ?? null);
            $position = $this->cleanString($row['ContactTitle'] ?? null) ?: $this->cleanString($row['Type'] ?? null);
            $phone = $this->normalizePhoneLike($row['Handphone'] ?? null) ?: $this->normalizePhoneLike($row['Phone'] ?? null);
            $email = $this->normalizeEmailLike($row['Email'] ?? null);
            $itemNo = (int) ($row['ItemNo'] ?? 0);

            if (!$customerId) {
                return $this->skippedRow('Customer contact could not resolve its customer.', $customerKey);
            }

            if (!$name && !$phone && !$email) {
                return $this->skippedRow('Customer has no contact information in source.', $customerKey);
            }

            $name = $name ?: 'Primary Contact';
            $sourceKey = $this->makeKey([$customerKey, $itemNo ?: null, $name]);

            $result = $this->syncRecord('customer_contacts', 'MsCustContact', $sourceKey, 'customer_contacts', [
                'customer_id' => $customerId,
                'name' => $name,
            ], [
                'salutation' => $this->cleanString($row['ContactType'] ?? null),
                'position' => $position,
                'email' => $email,
                'phone' => $phone,
                'is_active' => $this->yesNoToBool($row['FgActive'] ?? null, true),
                'created_by' => $this->actorId(),
                'updated_by' => $this->actorId(),
            ], $row);

            if ($this->apply && ($result['target_id'] ?? 0) > 0 && Schema::hasTable('customer_customer_contact')) {
                DB::table('customer_customer_contact')->updateOrInsert(
                    [
                        'customer_id' => $customerId,
                        'customer_contact_id' => $result['target_id'],
                    ],
                    [
                        'is_primary' => $itemNo === 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                if ($itemNo === 1 && Schema::hasColumn('customers', 'assigned_to')) {
                    DB::table('customers')
                        ->where('id', $customerId)
                        ->update($this->filterPayload('customers', [
                            'assigned_to' => $result['target_id'],
                            'updated_at' => now(),
                        ]));
                }
            }

            return $result;
        });
    }

    protected function customer_tax_settings(): array
    {
        return $this->runStep('customer_tax_settings', 'MsCustTaxAddress', 'CustCode', function (array $row) {
            $customerKey = $this->makeKey($row['CustCode'] ?? null);
            $customerId = $this->findMappedTargetId('MsCustomer', $customerKey, 'customers');
            $sourceKey = $this->makeKey([$customerKey, $row['ItemNo'] ?? null, $row['CustTaxAddress'] ?? null]);
            $taxNumber = $this->cleanString($row['CustTaxNPWP'] ?? null);

            if (!$customerId || !$sourceKey) {
                return $this->skippedRow('Customer tax setting could not resolve its customer.', $sourceKey);
            }

            if (!$taxNumber) {
                return $this->skippedRow('Customer tax setting has no tax number.', $sourceKey);
            }

            $customerName = DB::table('customers')->where('id', $customerId)->value('name');
            $customerPpnCode = DB::table('customers')->where('id', $customerId)->value('ppn_code');

            $result = $this->syncRecord('customer_tax_settings', 'MsCustTaxAddress', $sourceKey, 'customer_tax_settings', [
                'customer_id' => $customerId,
                'tax_number' => $taxNumber,
                'tax_address' => $this->cleanString($row['CustTaxAddress'] ?? null),
            ], [
                'label' => ((int) ($row['ItemNo'] ?? 1)) === 1 ? 'Primary Tax' : 'Tax Address ' . (int) ($row['ItemNo'] ?? 1),
                'nitku' => $this->cleanString($row['NITKU'] ?? null),
                'tax_name' => $customerName,
                'tax_type' => 'npwp',
                'ppn_code' => $customerPpnCode,
                'effective_date' => $this->toDate($row['ExpectedPeriod'] ?? null),
                'description' => $this->buildSourceDescription([
                    'ItemNo' => $row['ItemNo'] ?? null,
                    'CustTaxNIK' => $this->cleanString($row['CustTaxNIK'] ?? null),
                    'NITKU' => $this->cleanString($row['NITKU'] ?? null),
                    'NPWPcortex' => $this->cleanString($row['NPWPcortex'] ?? null),
                ]),
                'status' => $this->yesNoToBool($row['FgActive'] ?? null, true) ? 'active' : 'inactive',
                'is_active' => $this->yesNoToBool($row['FgActive'] ?? null, true),
            ], $row);

            if ($this->apply && ($result['target_id'] ?? 0) > 0) {
                DB::table('customers')->where('id', $customerId)->update($this->filterPayload('customers', array_filter([
                    'npwp' => $taxNumber,
                    'npwp_address' => $this->cleanString($row['CustTaxAddress'] ?? null),
                    'npwp_registration_date' => $this->toDate($row['ExpectedPeriod'] ?? null),
                    'nik' => $this->cleanString($row['CustTaxNIK'] ?? null),
                    'nitku' => $this->cleanString($row['NITKU'] ?? null),
                    'updated_at' => now(),
                ], fn ($value) => $value !== null)));
            }

            return $result;
        });
    }

    protected function company_virtual_accounts(): array
    {
        if (!Schema::hasTable('company_virtual_accounts')) {
            return ['stats' => $this->blankStats()];
        }

        return $this->runStep('company_virtual_accounts', 'MsVirtualAccount', 'VirtualAccount', function (array $row) {
            $accountNumber = $this->cleanString($row['VirtualAccount'] ?? null);
            $customerKey = $this->makeKey($row['Customer'] ?? null);
            $customerId = $customerKey ? $this->findMappedTargetId('MsCustomer', $customerKey, 'customers') : null;
            $bankId = $this->findMappedTargetId('MsBank', $this->makeKey($row['Bank'] ?? null), 'banks');
            $companyId = $this->resolveDefaultImportCompanyId();
            $sourceKey = $this->makeKey($accountNumber);

            if (!$accountNumber || !$bankId || !$companyId) {
                return $this->skippedRow('Virtual account could not resolve account number, bank, or company.', $sourceKey);
            }

            return $this->syncRecord('company_virtual_accounts', 'MsVirtualAccount', $sourceKey, 'company_virtual_accounts', [
                'account_number' => $accountNumber,
            ], [
                'company_id' => $companyId,
                'customer_id' => $customerId,
                'bank_payment_id' => $bankId,
                'account_name' => $customerId ? DB::table('customers')->where('id', $customerId)->value('name') : null,
                'description' => $this->buildSourceDescription([
                    'BankCode' => $this->cleanString($row['Bank'] ?? null),
                    'BranchCode' => $this->cleanString($row['BranchCode'] ?? null),
                    'ContractNo' => $this->cleanString($row['ContractNo'] ?? null),
                ]),
                'is_active' => $this->yesNoToBool($row['FgActive'] ?? null, true),
                'notes' => $this->buildSourceDescription([
                    'CustomerCode' => $customerKey,
                    'FgGetVA' => $this->cleanString($row['FgGetVA'] ?? null),
                ]),
            ], $row);
        });
    }

    protected function buildings(): array
    {
        return $this->runStep('buildings', 'MsBuilding', 'BuildingCode', function (array $row) {
            $sourceKey = $this->makeKey($row['BuildingCode'] ?? null);
            $name = $this->cleanString($row['BuildingName'] ?? null);
            $city = $this->resolveSourceCity($row['City'] ?? null);
            $area = $this->resolveSourceArea($row['AreaService'] ?? null);
            $address = $this->cleanString($row['Address'] ?? null) ?: $name;
            $phone = $this->normalizePhoneLike($row['Phone'] ?? null);
            $fax = $this->normalizePhoneLike($row['Fax'] ?? null);

            if (!$sourceKey || !$name) {
                return $this->failedRow('Building code or name is empty.');
            }

            return $this->syncRecord('buildings', 'MsBuilding', $sourceKey, 'buildings', [
                'nama_gedung' => $name,
                'alamat_1' => $address,
            ], [
                'name' => $name,
                'address' => $address,
                'postal_code' => $this->cleanString($row['ZipCode'] ?? null),
                'kode_pos' => $this->cleanString($row['ZipCode'] ?? null),
                'city_id' => $city['target_city_id'] ?? null,
                'province_id' => $city['target_province_id'] ?? null,
                'phone_1' => $phone,
                'fax' => $fax,
                'description' => $this->buildSourceDescription([
                    'BuildingCode' => $sourceKey,
                    'Branch' => $this->cleanString($row['Branch'] ?? null),
                    'AreaServiceCode' => $this->cleanString($row['AreaService'] ?? null),
                    'AreaServiceName' => $area['name'] ?? null,
                ]),
                'notes' => $this->buildSourceDescription([
                    'CityName' => $city['name'] ?? null,
                    'AreaCity' => $area['city_name'] ?? null,
                ]),
                'status_update' => $this->yesNoToBool($row['FgActive'] ?? null, true),
            ], $row);
        });
    }

    protected function building_customers(): array
    {
        return $this->runStep('building_customers', 'MsCustomerBuilding', 'CustCode', function (array $row) {
            $customerKey = $this->makeKey($row['CustCode'] ?? null);
            $buildingKey = $this->makeKey($row['BuildingCode'] ?? null);
            $sourceKey = $this->makeKey([$customerKey, $buildingKey]);
            $customerId = $this->findMappedTargetId('MsCustomer', $customerKey, 'customers');
            $buildingId = $this->findMappedTargetId('MsBuilding', $buildingKey, 'buildings');

            if (!$customerId || !$buildingId) {
                return $this->skippedRow('Building customer relation has unresolved customer or building.', $sourceKey);
            }

            return $this->syncRecord('building_customers', 'MsCustomerBuilding', $sourceKey, 'building_customers', [
                'building_id' => $buildingId,
                'customer_id' => $customerId,
            ], [
                'unit_number' => null,
                'floor' => null,
                'notes' => $this->buildSourceDescription(['SourceBuildingCode' => $buildingKey]),
                'is_active' => $this->yesNoToBool($row['FgActive'] ?? null, true),
            ], $row);
        });
    }

    protected function runStep(string $step, string $sourceTable, string $orderBy, callable $mapper, ?callable $scope = null): array
    {
        $query = $this->source()->table($sourceTable)->orderBy($orderBy);
        if ($scope) {
            $query = $scope($query) ?? $query;
        }

        return $this->runRows($step, $sourceTable, $query->get()->map(fn ($row) => (array) $row)->all(), $mapper);
    }

    protected function runRows(string $step, string $sourceTable, array $rows, callable $mapper): array
    {
        $stats = $this->blankStats();
        $totalRows = count($rows);

        foreach ($rows as $row) {
            $stats['processed']++;

            try {
                $result = $mapper($row);
            } catch (Throwable $e) {
                $result = $this->failedRow($e->getMessage());
            }

            $action = $result['action'] ?? 'skipped';
            $stats[$action] = ($stats[$action] ?? 0) + 1;

            if (in_array($action, ['failed', 'skipped'], true) && !empty($result['message'])) {
                $this->log($step, 'warning', $result['message'] ?? 'Import row failed.', [
                    'source_table' => $sourceTable,
                    'source_key' => $result['source_key'] ?? null,
                    'row' => $row,
                ]);
            }

            if ($stats['processed'] % $this->heartbeatEvery === 0) {
                $this->heartbeat($step, $stats, $totalRows);
            }
        }

        $this->heartbeat($step, $stats, $totalRows, true);

        return ['stats' => $stats];
    }

    protected function syncRecord(string $step, string $sourceTable, string $sourceKey, string $targetTable, array $match, array $payload, array $row): array
    {
        $payload = $this->filterPayload($targetTable, array_merge($match, $payload));
        $match = Arr::only($payload, array_keys($match));

        if ($payload === [] || $match === []) {
            return $this->failedRow('Target payload or match columns are empty after filtering.', $sourceKey);
        }

        $existing = null;
        $targetId = $this->findMappedTargetId($sourceTable, $sourceKey, $targetTable);

        if ($targetId && $targetId > 0) {
            $existing = DB::table($targetTable)->where('id', $targetId)->first();
        }

        if (!$existing) {
            $existing = $this->findTargetRecord($targetTable, $match);
            $targetId = $existing->id ?? null;
        }

        if (!$existing) {
            $targetId = $this->apply ? $this->insertTargetRecord($targetTable, $payload) : $this->nextTempId();
            $this->rememberMap($sourceTable, $sourceKey, $targetTable, $targetId, $row);
            return ['action' => 'inserted', 'target_id' => $targetId];
        }

        if (!$this->payloadDiffers((array) $existing, $payload)) {
            $this->rememberMap($sourceTable, $sourceKey, $targetTable, (int) $existing->id, $row);
            return ['action' => 'skipped', 'target_id' => (int) $existing->id];
        }

        if ($this->apply) {
            $update = Arr::except($payload, ['created_at', 'created_by']);
            if (in_array('updated_at', $this->columns($targetTable), true)) {
                $update['updated_at'] = now();
            }
            if (in_array('updated_by', $this->columns($targetTable), true)) {
                $update['updated_by'] = $this->actorId();
            }
            DB::table($targetTable)->where('id', $existing->id)->update($update);
        }

        $this->rememberMap($sourceTable, $sourceKey, $targetTable, (int) $existing->id, $row);
        return ['action' => 'updated', 'target_id' => (int) $existing->id];
    }

    protected function insertTargetRecord(string $targetTable, array $payload): int
    {
        $columns = $this->columns($targetTable);
        if (in_array('created_at', $columns, true) && !array_key_exists('created_at', $payload)) {
            $payload['created_at'] = now();
        }
        if (in_array('updated_at', $columns, true) && !array_key_exists('updated_at', $payload)) {
            $payload['updated_at'] = now();
        }
        if (in_array('created_by', $columns, true) && !array_key_exists('created_by', $payload)) {
            $payload['created_by'] = $this->actorId();
        }
        if (in_array('updated_by', $columns, true) && !array_key_exists('updated_by', $payload)) {
            $payload['updated_by'] = $this->actorId();
        }

        return (int) DB::table($targetTable)->insertGetId($payload);
    }

    protected function findMappedTargetId(string $sourceTable, ?string $sourceKey, string $targetTable): ?int
    {
        if (!$sourceKey) {
            return null;
        }

        $runtime = $this->runtimeMap[$sourceTable][$targetTable][$sourceKey] ?? null;
        if ($runtime !== null) {
            return $runtime;
        }

        return DB::table('source_import_maps')
            ->where('source_system', self::SOURCE_SYSTEM)
            ->where('source_table', $sourceTable)
            ->where('source_key', $sourceKey)
            ->where('target_table', $targetTable)
            ->value('target_id');
    }

    protected function ensureImportMapIndexes(): void
    {
        if (!Schema::hasTable('source_import_maps')) {
            return;
        }

        $requiredIndexes = [
            'source_import_maps_source_target_unique',
            'source_import_maps_target_table_target_id_index',
        ];

        $existingIndexes = collect(DB::select('SHOW INDEX FROM source_import_maps'))
            ->pluck('Key_name')
            ->unique()
            ->values()
            ->all();

        foreach ($requiredIndexes as $indexName) {
            if (!in_array($indexName, $existingIndexes, true)) {
                throw new RuntimeException(
                    'source_import_maps belum punya index lookup yang dibutuhkan. ' .
                    'Jalankan php artisan migrate untuk apply migration terbaru, lalu ulangi import.'
                );
            }
        }
    }

    protected function warmRuntimeMap(): void
    {
        if (!Schema::hasTable('source_import_maps')) {
            return;
        }

        DB::table('source_import_maps')
            ->where('source_system', self::SOURCE_SYSTEM)
            ->select('id', 'source_table', 'source_key', 'target_table', 'target_id')
            ->orderBy('id')
            ->chunkById(5000, function ($rows): void {
                foreach ($rows as $row) {
                    $this->runtimeMap[$row->source_table][$row->target_table][$row->source_key] = (int) $row->target_id;
                }
            });
    }

    protected function heartbeat(string $step, array $stats, int $totalRows, bool $final = false): void
    {
        DB::table('source_import_batches')->where('id', $this->batchId)->update([
            'updated_at' => now(),
        ]);

        if (!$final && is_callable($this->progressCallback)) {
            ($this->progressCallback)($step, [
                'partial' => true,
                'total' => $totalRows,
                'stats' => $stats,
            ]);
        }
    }

    protected function rememberMap(string $sourceTable, string $sourceKey, string $targetTable, int $targetId, array $row): void
    {
        $this->runtimeMap[$sourceTable][$targetTable][$sourceKey] = $targetId;

        if (!$this->apply || $targetId < 1) {
            return;
        }

        DB::table('source_import_maps')->updateOrInsert(
            [
                'source_system' => self::SOURCE_SYSTEM,
                'source_table' => $sourceTable,
                'source_key' => $sourceKey,
                'target_table' => $targetTable,
            ],
            [
                'target_id' => $targetId,
                'source_hash' => sha1(json_encode($row)),
                'last_batch_id' => $this->batchId,
                'last_imported_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    protected function findTargetRecord(string $targetTable, array $match): ?object
    {
        $query = DB::table($targetTable);
        foreach ($match as $column => $value) {
            $value === null ? $query->whereNull($column) : $query->where($column, $value);
        }
        return $query->first();
    }

    protected function filterPayload(string $targetTable, array $payload): array
    {
        $allowed = array_flip($this->columns($targetTable));
        return array_filter(
            Arr::where($payload, fn ($value, $key) => isset($allowed[$key])),
            fn ($value) => !($value === '' || $value === [])
        );
    }

    protected function columns(string $targetTable): array
    {
        return $this->tableColumns[$targetTable] ??= Schema::getColumnListing($targetTable);
    }

    protected function payloadDiffers(array $existing, array $payload): bool
    {
        foreach ($payload as $column => $value) {
            if ($this->normalizeCompare($existing[$column] ?? null) !== $this->normalizeCompare($value)) {
                return true;
            }
        }
        return false;
    }

    protected function source()
    {
        $name = config('catalyst-import.connection_name', 'catalyst_import');

        if (!config("database.connections.$name")) {
            $connection = [
                'driver' => config('catalyst-import.source.driver', 'sqlsrv'),
                'host' => config('catalyst-import.source.host'),
                'port' => config('catalyst-import.source.port'),
                'database' => config('catalyst-import.source.database'),
                'charset' => config('catalyst-import.source.charset', 'utf8'),
                'prefix' => config('catalyst-import.source.prefix', ''),
                'prefix_indexes' => true,
                'encrypt' => config('catalyst-import.source.encrypt', false),
                'trust_server_certificate' => config('catalyst-import.source.trust_server_certificate', true),
                'trusted_connection' => config('catalyst-import.source.trusted_connection', false),
            ];

            $username = config('catalyst-import.source.username');
            $password = config('catalyst-import.source.password');

            if (!blank($username)) {
                $connection['username'] = $username;
            }

            if (!blank($password)) {
                $connection['password'] = $password;
            }

            config([
                "database.connections.$name" => $connection,
            ]);
        }

        return DB::connection($name);
    }

    protected function loadSourceLookups(): void
    {
        $this->sourceCityLookup = $this->source()->table('MsCity')
            ->get()
            ->mapWithKeys(fn ($row) => [$this->makeKey($row->CityCode ?? null) => ['name' => $this->cleanString($row->CityName ?? null)]])
            ->all();

        $this->sourceAreaLookup = $this->source()->table('V_MsAreaService')
            ->get()
            ->mapWithKeys(fn ($row) => [$this->makeKey($row->AreaService ?? null) => [
                'name' => $this->cleanString($row->AreaServiceName ?? null),
                'city_name' => $this->cleanString($row->CityName ?? null),
            ]])
            ->all();

        $this->sourceCustomerTypeLookup = $this->source()->table('MsCustType')
            ->get()
            ->mapWithKeys(fn ($row) => [$this->makeKey($row->CustTypeCode ?? null) => $this->cleanString($row->CustTypeName ?? null)])
            ->all();

        $this->sourceCustomerAddressLookup = [];
        $addressRows = $this->source()->table('MsCustAddress')
            ->orderBy('CustCode')
            ->orderBy('DeliveryCode')
            ->get([
                'CustCode',
                'DeliveryCode',
                'DeliveryName',
                'DeliveryType',
                'DeliveryAddr1',
                'DeliveryAddr2',
                'City',
                'ZipCode',
                'PhoneNo',
                'Fax',
                'ContactPerson',
                'FgActive',
            ]);

        foreach ($addressRows as $row) {
            $customerKey = $this->makeKey($row->CustCode ?? null);
            if (!$customerKey) {
                continue;
            }

            $candidate = [
                'delivery_code' => $this->cleanString($row->DeliveryCode ?? null),
                'delivery_name' => $this->cleanString($row->DeliveryName ?? null),
                'delivery_type' => $this->cleanString($row->DeliveryType ?? null),
                'address_1' => $this->cleanString($row->DeliveryAddr1 ?? null),
                'address_2' => $this->cleanString($row->DeliveryAddr2 ?? null),
                'city_code' => $this->cleanString($row->City ?? null),
                'postal_code' => $this->cleanString($row->ZipCode ?? null),
                'phone' => $this->normalizePhoneLike($row->PhoneNo ?? null),
                'fax' => $this->normalizePhoneLike($row->Fax ?? null),
                'contact_person' => $this->cleanString($row->ContactPerson ?? null),
                'is_active' => $this->yesNoToBool($row->FgActive ?? null, true),
            ];

            if (!isset($this->sourceCustomerAddressLookup[$customerKey])) {
                $this->sourceCustomerAddressLookup[$customerKey] = $candidate;
                continue;
            }

            if ($this->scoreSourceCustomerAddress($candidate) > $this->scoreSourceCustomerAddress($this->sourceCustomerAddressLookup[$customerKey])) {
                $this->sourceCustomerAddressLookup[$customerKey] = $candidate;
            }
        }
    }

    protected function loadTargetCityLookup(): void
    {
        if (!Schema::hasTable('cities')) {
            return;
        }

        $rows = DB::table('cities')
            ->leftJoin('provinces', 'provinces.id', '=', 'cities.province_id')
            ->select('cities.id', 'cities.name', 'cities.province_id', 'provinces.name as province_name')
            ->get();

        foreach ($rows as $row) {
            $key = $this->normalizePlace($row->name);
            if ($key && !isset($this->targetCityLookup[$key])) {
                $this->targetCityLookup[$key] = [
                    'id' => (int) $row->id,
                    'province_id' => $row->province_id ? (int) $row->province_id : null,
                    'province_name' => $this->cleanString($row->province_name),
                ];
            }
        }
    }

    protected function loadTargetRentalLookups(): void
    {
        if (Schema::hasTable('rental_service_frequencies')) {
            $rows = DB::table('rental_service_frequencies')
                ->whereNull('deleted_at')
                ->get(['id', 'code', 'name', 'frequency_months', 'frequency_times_per_month']);

            foreach ($rows as $row) {
                $months = (int) ($row->frequency_months ?? 0);
                $times = (int) ($row->frequency_times_per_month ?? 0);

                if ($months > 0 && $times > 0) {
                    $this->targetRentalServiceFrequencyLookup[$months . 'x' . $times] = (int) $row->id;
                }

                $code = $this->makeKey($row->code ?? null);
                if ($code) {
                    $this->targetRentalServiceFrequencyLookup['code:' . Str::upper($code)] = (int) $row->id;
                }

                $name = $this->normalizeLookupKey($row->name ?? null);
                if ($name) {
                    $this->targetRentalServiceFrequencyLookup['name:' . $name] = (int) $row->id;
                }
            }
        }

        if (Schema::hasTable('product_categories')) {
            $rows = DB::table('product_categories')
                ->whereNull('deleted_at')
                ->get(['id', 'code', 'name', 'sku_prefix']);

            foreach ($rows as $row) {
                foreach (array_filter([
                    $this->normalizeLookupKey($row->name ?? null),
                    $this->normalizeLookupKey($row->code ?? null),
                    $this->normalizeLookupKey($row->sku_prefix ?? null),
                ]) as $key) {
                    $this->targetProductCategoryLookup[$key] ??= (int) $row->id;
                }
            }
        }

        if (Schema::hasTable('product_types')) {
            $rows = DB::table('product_types')
                ->whereNull('deleted_at')
                ->get(['id', 'sku_prefix', 'name', 'product_category_id']);

            foreach ($rows as $row) {
                foreach (array_filter([
                    $this->normalizeLookupKey($row->name ?? null),
                    $this->normalizeLookupKey($row->sku_prefix ?? null),
                ]) as $key) {
                $this->targetProductTypeLookup[$key] ??= (int) $row->id;
            }

            $this->targetProductTypeCategoryLookup[(int) $row->id] = $row->product_category_id ? (int) $row->product_category_id : null;
        }
        }

        if (Schema::hasTable('master_products')) {
            DB::table('master_products')
                ->whereNull('deleted_at')
                ->where('is_active', true)
                ->select('id', 'product_category_id', 'product_type_id')
                ->orderBy('id')
                ->chunkById(5000, function ($rows): void {
                    foreach ($rows as $row) {
                        $this->targetMasterProductCategoryLookup[(int) $row->id] = $row->product_category_id ? (int) $row->product_category_id : null;
                        $this->targetMasterProductTypeLookup[(int) $row->id] = $row->product_type_id ? (int) $row->product_type_id : null;

                        if ($row->product_type_id) {
                            $this->targetMasterProductIdsByTypeLookup[(int) $row->product_type_id][] = (int) $row->id;
                        }

                        if ($row->product_category_id) {
                            $this->targetMasterProductIdsByCategoryLookup[(int) $row->product_category_id][] = (int) $row->id;
                        }
                    }
                });
        }
    }

    protected function sourceMarketingRentalProductCodes(): array
    {
        if ($this->sourceMarketingRentalProductCodes !== []) {
            return $this->sourceMarketingRentalProductCodes;
        }

        $quotationCodes = $this->source()->table('MKTQuotationRental')
            ->whereNotNull('Product')
            ->pluck('Product')
            ->map(fn ($value) => $this->cleanString($value))
            ->filter()
            ->all();

        $contractCodes = $this->source()->table('MKTContractDt')
            ->whereNotNull('Product')
            ->pluck('Product')
            ->map(fn ($value) => $this->cleanString($value))
            ->filter()
            ->all();

        $this->sourceMarketingRentalProductCodes = array_values(array_unique([
            ...$quotationCodes,
            ...$contractCodes,
        ]));

        return $this->sourceMarketingRentalProductCodes;
    }

    protected function resolveSourceCity($code): array
    {
        $key = $this->makeKey($code);
        $name = $this->sourceCityLookup[$key]['name'] ?? null;
        $target = $name ? ($this->targetCityLookup[$this->normalizePlace($name)] ?? null) : null;

        return [
            'code' => $key,
            'name' => $name,
            'target_city_id' => $target['id'] ?? null,
            'target_province_id' => $target['province_id'] ?? null,
        ];
    }

    protected function resolveSourceArea($code): array
    {
        return $this->sourceAreaLookup[$this->makeKey($code)] ?? ['name' => null, 'city_name' => null];
    }

    protected function scoreSourceCustomerAddress(array $address): int
    {
        $score = 0;

        if (($address['is_active'] ?? false) === true) {
            $score += 50;
        }

        $deliveryType = Str::lower($this->cleanString($address['delivery_type'] ?? null) ?? '');
        $deliveryName = Str::lower($this->cleanString($address['delivery_name'] ?? null) ?? '');

        if (Str::contains($deliveryType, 'invoice') || Str::contains($deliveryName, 'invoice')) {
            $score += 25;
        }

        if (($address['delivery_code'] ?? null) === '01') {
            $score += 10;
        }

        foreach (['address_1', 'postal_code', 'city_code', 'phone'] as $column) {
            if (!empty($address[$column])) {
                $score += 2;
            }
        }

        return $score;
    }

    protected function resolveAdministrativeAreaByPostalCode(?string $postalCode, ?string $cityName = null, ?int $provinceId = null): array
    {
        $postalCode = $this->cleanString($postalCode);
        $cityName = $this->normalizePlace($cityName);
        $cacheKey = implode('|', [$postalCode, $cityName, $provinceId]);

        if (isset($this->postalAdministrativeAreaLookup[$cacheKey])) {
            return $this->postalAdministrativeAreaLookup[$cacheKey];
        }

        if (!$postalCode || !Schema::hasTable('subdistricts') || !Schema::hasTable('districts') || !Schema::hasTable('cities')) {
            return $this->postalAdministrativeAreaLookup[$cacheKey] = [];
        }

        $candidates = DB::table('subdistricts')
            ->join('districts', 'districts.id', '=', 'subdistricts.district_id')
            ->join('cities', 'cities.id', '=', 'districts.city_id')
            ->where('subdistricts.postal_code', $postalCode)
            ->when($provinceId, fn ($query) => $query->where('cities.province_id', $provinceId))
            ->get([
                'subdistricts.id as subdistrict_id',
                'districts.id as district_id',
                'cities.id as city_id',
                'cities.name as city_name',
                'cities.province_id',
            ]);

        if ($candidates->isEmpty()) {
            return $this->postalAdministrativeAreaLookup[$cacheKey] = [];
        }

        if ($cityName) {
            $filtered = $candidates->filter(fn ($row) => $this->normalizePlace($row->city_name ?? null) === $cityName)->values();
            if ($filtered->count() === 1) {
                $row = $filtered->first();
                return $this->postalAdministrativeAreaLookup[$cacheKey] = [
                    'city_id' => (int) $row->city_id,
                    'district_id' => (int) $row->district_id,
                    'subdistrict_id' => (int) $row->subdistrict_id,
                ];
            }
            if ($filtered->isNotEmpty()) {
                $candidates = $filtered;
            }
        }

        if ($candidates->count() === 1) {
            $row = $candidates->first();
            return $this->postalAdministrativeAreaLookup[$cacheKey] = [
                'city_id' => (int) $row->city_id,
                'district_id' => (int) $row->district_id,
                'subdistrict_id' => (int) $row->subdistrict_id,
            ];
        }

        return $this->postalAdministrativeAreaLookup[$cacheKey] = [];
    }

    protected function resolveDefaultImportCompanyId(): ?int
    {
        if ($this->defaultImportCompanyId !== null) {
            return $this->defaultImportCompanyId;
        }

        $preferred = DB::table('companies')->where('id', 7)->value('id');
        if ($preferred) {
            return $this->defaultImportCompanyId = (int) $preferred;
        }

        $firstActive = DB::table('companies')
            ->where(function ($query) {
                if (Schema::hasColumn('companies', 'is_active')) {
                    $query->where('is_active', true);
                }

                if (Schema::hasColumn('companies', 'status')) {
                    $query->orWhere('status', 'active');
                }
            })
            ->orderBy('id')
            ->value('id');

        return $this->defaultImportCompanyId = $firstActive ? (int) $firstActive : null;
    }

    protected function resolveRentalServiceFrequencyId($value): ?int
    {
        $value = $this->cleanString($value);
        if (!$value) {
            return null;
        }

        $upper = Str::upper($value);

        $parsed = match ($upper) {
            '1XM' => [1, 1],
            '2XM' => [1, 2],
            '3XM' => [1, 3],
            '2B1X', '2B1X ' => [2, 1],
            default => null,
        };

        if ($parsed) {
            return $this->targetRentalServiceFrequencyLookup[$parsed[0] . 'x' . $parsed[1]] ?? null;
        }

        return $this->targetRentalServiceFrequencyLookup['code:' . $upper]
            ?? $this->targetRentalServiceFrequencyLookup['name:' . $this->normalizeLookupKey($value)]
            ?? null;
    }

    protected function resolveCatalystProductCategoryIdForProduct(array $row, ?int $productTypeId): ?int
    {
        $categoryInfoId = $this->findMappedTargetId('MsProductCategoryInfo', $this->makeKey($row['CategoryInfo'] ?? null), 'product_categories');
        if ($categoryInfoId) {
            return $categoryInfoId;
        }

        if ($productTypeId && array_key_exists($productTypeId, $this->targetProductTypeCategoryLookup)) {
            return $this->targetProductTypeCategoryLookup[$productTypeId];
        }

        return $this->resolveCatalystProductCategoryIdForType($this->makeKey($row['ProductType'] ?? null), [
            'ProductTypeName' => $row['ProductType'] ?? null,
            'ProductCategory' => $row['ProductCategory'] ?? null,
        ]);
    }

    protected function resolveCatalystProductCategoryIdForType(?string $sourceCode, array $row): ?int
    {
        $sourceCode = Str::upper($this->cleanString($sourceCode) ?? '');
        $typeName = Str::upper($this->cleanString($row['ProductTypeName'] ?? null) ?? '');
        $sourceCategory = Str::upper($this->cleanString($row['ProductCategory'] ?? null) ?? '');

        $candidates = match (true) {
            in_array($sourceCode, ['REF', 'REFD'], true) || Str::contains($typeName, 'REFILL') => ['aroma refill', 'refill'],
            in_array($sourceCode, ['HSR'], true) || Str::contains($typeName, 'HAND SANITIZER REFILL') => ['hs refill', 'refill'],
            in_array($sourceCode, ['DIS'], true) || Str::contains($typeName, 'DIFFUSER') => ['diffuser', 'ads diffuser', 'ads'],
            in_array($sourceCode, ['DSP'], true) || Str::contains($typeName, 'DISPENSER') => ['dispenser'],
            in_array($sourceCode, ['PART', 'SP'], true) || Str::contains($typeName, 'PART') => ['spare part', 'pump'],
            in_array($sourceCode, ['BTR'], true) || Str::contains($typeName, 'BATTERY') => ['battery set'],
            in_array($sourceCode, ['AF', 'JAF'], true) || Str::contains($typeName, 'FILTER') => ['aroma filter', 'air filter'],
            in_array($sourceCode, ['TRM', 'THM'], true) || Str::contains($typeName, 'THERMAL') => ['ads thermal', 'thermal251219'],
            in_array($sourceCode, ['RNT', 'RNNQR'], true) || $sourceCategory === 'RENTAL' => ['aroma delivery sys svc'],
            in_array($sourceCode, ['FA'], true) || Str::contains($typeName, 'FIXED ASSET') => ['equipment'],
            in_array($sourceCode, ['AK', 'BATK', 'BSS', 'CON', 'PK', 'PNLTY', 'PPP', 'PRL', 'PRM'], true) || $sourceCategory === 'OTHER' => ['uncategorized'],
            default => [],
        };

        foreach ($candidates as $candidate) {
            $key = $this->normalizeLookupKey($candidate);
            if ($key && isset($this->targetProductCategoryLookup[$key])) {
                return $this->targetProductCategoryLookup[$key];
            }
        }

        return null;
    }

    protected function resolveRentalDetailFrequencyMultiplier($value): int
    {
        $value = (int) ($value ?? 0);
        return $value > 0 ? $value : 1;
    }

    protected function resolveRentalDetailProductId(?string $materialKey, ?string $rentalKey, ?string $component): ?int
    {
        if ($materialKey) {
            $mapped = $this->findMappedTargetId('MsProduct', $materialKey, 'master_products');
            if ($mapped) {
                return $mapped;
            }
        }

        $componentKey = Str::upper($this->cleanString($component) ?? '');
        if (Str::contains($componentKey, ['DIFFUSER', 'DISPENSER']) && $rentalKey) {
            return $this->findMappedTargetId('MsProduct', $rentalKey, 'master_products');
        }

        return null;
    }

    protected function resolveRentalDetailCategoryId(?string $component, ?int $masterProductId): ?int
    {
        if ($masterProductId && array_key_exists($masterProductId, $this->targetMasterProductCategoryLookup)) {
            return $this->targetMasterProductCategoryLookup[$masterProductId];
        }

        $component = Str::upper($this->cleanString($component) ?? '');
        if ($component === '') {
            return null;
        }

        $candidates = match (true) {
            Str::contains($component, 'DIFFUSER') => ['diffuser', 'ads diffuser', 'ads'],
            Str::contains($component, 'DISPENSER') => ['dispenser'],
            Str::contains($component, 'REFILL') => ['refill', 'aroma refill', 'hs refill'],
            Str::contains($component, 'BATTERY') => ['battery set', 'battery'],
            Str::contains($component, 'PART') => ['spare part', 'part'],
            Str::contains($component, 'CLEAN') => ['cleaner'],
            default => [$component],
        };

        foreach ($candidates as $candidate) {
            $key = $this->normalizeLookupKey($candidate);
            if ($key && isset($this->targetProductCategoryLookup[$key])) {
                return $this->targetProductCategoryLookup[$key];
            }
        }

        return null;
    }

    protected function resolveRentalDetailProductTypeId(?string $component): ?int
    {
        $component = Str::upper($this->cleanString($component) ?? '');
        if ($component === '') {
            return null;
        }

        $candidates = match (true) {
            Str::contains($component, 'DIFFUSER') => ['diffuser', 'diff'],
            Str::contains($component, 'DISPENSER') => ['dispenser', 'dsp', 'hsd'],
            Str::contains($component, 'REFILL') => ['refill', 'ref', 'ar', 'hsr'],
            Str::contains($component, 'BATTERY') => ['battery', 'btr'],
            Str::contains($component, 'PART') => ['part'],
            Str::contains($component, 'CLEAN') => ['cleaner', 'clean'],
            default => [$component],
        };

        foreach ($candidates as $candidate) {
            $key = $this->normalizeLookupKey($candidate);
            if ($key && isset($this->targetProductTypeLookup[$key])) {
                return $this->targetProductTypeLookup[$key];
            }
        }

        return null;
    }

    protected function resolveRentalDetailSelectedProductIds(
        ?string $rentalKey,
        ?string $component,
        ?int $masterProductId,
        ?int $productTypeId,
        ?int $productCategoryId
    ): array
    {
        $productId = $this->resolveRentalDetailDefaultProductId(
            $rentalKey,
            $component,
            $masterProductId,
            $productTypeId,
            $productCategoryId
        );

        return $productId ? [$productId] : [];
    }

    protected function resolveRentalDetailDefaultProductId(
        ?string $rentalKey,
        ?string $component,
        ?int $masterProductId,
        ?int $productTypeId,
        ?int $productCategoryId
    ): ?int
    {
        if ($masterProductId) {
            return $masterProductId;
        }

        $component = Str::upper($this->cleanString($component) ?? '');
        $rentalProductId = $rentalKey ? $this->findMappedTargetId('MsProduct', $rentalKey, 'master_products') : null;

        if (
            $rentalProductId
            && Str::contains($component, ['DIFFUSER', 'DISPENSER', 'REFILL'])
            && $this->matchesRentalDetailProductContext($rentalProductId, $productTypeId, $productCategoryId)
        ) {
            return $rentalProductId;
        }

        $candidates = [];

        if ($productTypeId) {
            $candidates = $this->targetMasterProductIdsByTypeLookup[$productTypeId] ?? [];
        }

        if ($candidates === [] && $productCategoryId) {
            $candidates = $this->targetMasterProductIdsByCategoryLookup[$productCategoryId] ?? [];
        }

        $candidates = array_values(array_unique(array_filter(array_map('intval', $candidates))));

        return count($candidates) === 1 ? $candidates[0] : null;
    }

    protected function matchesRentalDetailProductContext(int $productId, ?int $productTypeId, ?int $productCategoryId): bool
    {
        if ($productTypeId && ($this->targetMasterProductTypeLookup[$productId] ?? null) !== $productTypeId) {
            return false;
        }

        if ($productCategoryId && ($this->targetMasterProductCategoryLookup[$productId] ?? null) !== $productCategoryId) {
            return false;
        }

        return true;
    }

    protected function syncRentalDetailMaterials(int $detailId, array $productIds): void
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        DB::table('rental_detail_materials')
            ->where('rental_detail_id', $detailId)
            ->delete();

        foreach ($productIds as $index => $productId) {
            DB::table('rental_detail_materials')->updateOrInsert(
                [
                    'rental_detail_id' => $detailId,
                    'master_product_id' => $productId,
                ],
                [
                    'is_selected' => true,
                    'sort_order' => $index,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    protected function resolveCompanyType(?string $value): string
    {
        $value = Str::lower($this->cleanString($value) ?? '');
        return str_contains($value, 'pt') ? 'pt'
            : (str_contains($value, 'cv') ? 'cv'
            : (str_contains($value, 'ud') ? 'ud' : 'other'));
    }

    protected function resolveSteps(array $requestedSteps): array
    {
        if ($requestedSteps === []) {
            return $this->steps;
        }

        $resolved = [];
        foreach ($requestedSteps as $step) {
            $this->collectStepDependencies($step, $resolved);
        }

        $steps = array_values(array_intersect($this->steps, $resolved));
        if ($steps === []) {
            throw new RuntimeException('No valid import steps were requested.');
        }

        return $steps;
    }

    protected function collectStepDependencies(string $step, array &$resolved): void
    {
        if (!in_array($step, $this->steps, true)) {
            return;
        }

        foreach ($this->stepDependencies[$step] ?? [] as $dependency) {
            $this->collectStepDependencies($dependency, $resolved);
        }

        if (!in_array($step, $resolved, true)) {
            $resolved[] = $step;
        }
    }

    protected function blankStats(): array
    {
        return ['processed' => 0, 'inserted' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];
    }

    protected function mergeStats(array $carry, array $stats): array
    {
        foreach ($stats as $key => $value) {
            $carry[$key] = ($carry[$key] ?? 0) + $value;
        }
        return $carry;
    }

    protected function notifyProgress(string $step, array $summary): void
    {
        if (is_callable($this->progressCallback)) {
            ($this->progressCallback)($step, $summary);
        }
    }

    protected function log(string $step, string $level, string $message, array $context = []): void
    {
        $encodedContext = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

        if ($encodedContext === false) {
            $encodedContext = json_encode([
                'context_encoding_error' => json_last_error_msg(),
                'context_preview' => Str::limit(print_r($context, true), 5000, ''),
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        }

        DB::table('source_import_logs')->insert([
            'batch_id' => $this->batchId,
            'source_system' => self::SOURCE_SYSTEM,
            'step' => $step,
            'level' => $level,
            'source_table' => $context['source_table'] ?? null,
            'source_key' => $context['source_key'] ?? null,
            'target_table' => $context['target_table'] ?? null,
            'target_id' => $context['target_id'] ?? null,
            'message' => $message,
            'context' => $encodedContext,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function failedRow(string $message, ?string $sourceKey = null): array
    {
        return ['action' => 'failed', 'message' => $message, 'source_key' => $sourceKey];
    }

    protected function skippedRow(string $message, ?string $sourceKey = null): array
    {
        return ['action' => 'skipped', 'message' => $message, 'source_key' => $sourceKey];
    }

    protected function cleanString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        return $value === '' || in_array(Str::lower($value), ['null', '\\n'], true) ? null : $value;
    }

    protected function normalizeEmailLike($value): ?string
    {
        $value = $this->cleanString($value);

        if ($value === null || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return Str::lower($value);
    }

    protected function normalizeDocumentNumber($value): ?string
    {
        $value = $this->cleanString($value);

        if ($value === null) {
            return null;
        }

        $value = preg_replace('#/+#', '/', $value);
        $value = preg_replace('/\s+/', ' ', (string) $value);
        $value = trim((string) $value);

        return $value === '' ? null : Str::upper($value);
    }

    protected function normalizePhoneLike($value): ?string
    {
        $value = $this->cleanString($value);

        if ($value === null || Str::contains($value, '@')) {
            return null;
        }

        $value = preg_replace('/\b(hours?|jam)\b\s*:?.*$/i', '', $value);
        $value = preg_replace('/[^0-9+\-\s()\/]/', ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);
        $value = trim((string) $value);

        return $value === '' ? null : Str::limit($value, 20, '');
    }

    protected function yesNoToBool($value, bool $default = false): bool
    {
        $value = Str::upper($this->cleanString($value) ?? '');
        return $value === '' ? $default : in_array($value, ['Y', 'YES', '1', 'TRUE'], true);
    }

    protected function toDate($value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    protected function combineLines(...$values): ?string
    {
        $values = array_values(array_filter(array_map(fn ($value) => $this->cleanString($value), $values)));
        return $values ? implode(PHP_EOL, $values) : null;
    }

    protected function buildSourceDescription(array $pairs): ?string
    {
        $lines = [];
        foreach ($pairs as $label => $value) {
            $value = $this->cleanString($value);
            if ($value !== null) {
                $lines[] = $label . ': ' . $value;
            }
        }
        return $lines ? implode(PHP_EOL, $lines) : null;
    }

    protected function surveyBuildingDisplayName($building): ?string
    {
        return $this->cleanString($building->nama_gedung ?? null)
            ?: $this->cleanString($building->name ?? null);
    }

    protected function surveyBuildingAddress($building): ?string
    {
        return $this->cleanString($building->alamat_1 ?? null)
            ?: $this->cleanString($building->address ?? null);
    }

    protected function surveyBuildingPostalCode($building): ?string
    {
        return $this->cleanString($building->postal_code ?? null)
            ?: $this->cleanString($building->kode_pos ?? null);
    }

    protected function surveyBuildingCity($building): ?string
    {
        return $this->cleanString($building->city ?? null)
            ?: $this->extractLabelValue($building->notes ?? null, 'CityName')
            ?: $this->extractLabelValue($building->notes ?? null, 'AreaCity');
    }

    protected function extractLabelValue($text, string $label): ?string
    {
        $text = trim((string) $text);
        if ($text === '') {
            return null;
        }

        foreach (preg_split('/\r\n|\r|\n/', $text) as $line) {
            if (str_starts_with($line, $label . ':')) {
                return $this->cleanString(trim(substr($line, strlen($label) + 1)));
            }
        }

        return null;
    }

    protected function makeKey($value): ?string
    {
        if (is_array($value)) {
            $value = implode('||', array_map(fn ($item) => $this->cleanString($item) ?? '', $value));
        }

        return $this->cleanString($value);
    }

    protected function normalizeCompare($value): ?string
    {
        if ($value instanceof Carbon) {
            return $value->toDateTimeString();
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_numeric($value)) {
            return number_format((float) $value, 6, '.', '');
        }

        return $this->cleanString($value);
    }

    protected function normalizePlace(?string $value): ?string
    {
        $value = Str::ascii(Str::lower($this->cleanString($value) ?? ''));
        $value = preg_replace('/\b(kota|kabupaten|kab|city)\b/', ' ', $value);
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value);
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    protected function normalizeLookupKey($value): ?string
    {
        $value = Str::ascii(Str::lower($this->cleanString($value) ?? ''));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value);
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    protected function actorId(): ?int
    {
        if ($this->actorUserId !== null) {
            return $this->actorUserId;
        }

        $authenticated = auth()->id();
        if ($authenticated) {
            return $this->actorUserId = (int) $authenticated;
        }

        $firstUserId = DB::table('users')->orderBy('id')->value('id');
        return $this->actorUserId = $firstUserId ? (int) $firstUserId : null;
    }

    protected function quotationSurveySourceKey(string $quotationNumber, string $buildingCode): ?string
    {
        return $this->makeKey([$quotationNumber, $buildingCode]);
    }

    protected function findSourceQuotationHeader(?string $quotationNumber): ?object
    {
        $quotationNumber = $this->normalizeDocumentNumber($quotationNumber);

        if (!$quotationNumber) {
            return null;
        }

        return $this->sourceQuotationHeadersByNumber()[$quotationNumber] ?? null;
    }

    protected function quotationRoomSourceKey(string $quotationNumber, ?string $buildingCode, ?string $floorCode, ?string $roomCode, ?string $roomName): ?string
    {
        return $this->makeKey([$quotationNumber, $buildingCode, $floorCode, $roomCode, $roomName]);
    }

    protected function contractRoomSourceKey(string $contractNumber, ?string $buildingCode, ?string $floorCode, ?string $roomCode, ?string $roomName): ?string
    {
        return $this->makeKey([$contractNumber, $buildingCode, $floorCode, $roomCode, $roomName]);
    }

    protected function sourceQuotationHeadersByNumber(): array
    {
        if ($this->sourceQuotationHeadersByNumber !== null) {
            return $this->sourceQuotationHeadersByNumber;
        }

        $lookup = [];
        $rows = $this->source()->table('MKTQuotationHd')->whereNotNull('TransNmbr')->get();
        foreach ($rows as $row) {
            $number = $this->normalizeDocumentNumber($row->TransNmbr ?? null);
            if ($number && !isset($lookup[$number])) {
                $lookup[$number] = $row;
            }
        }

        return $this->sourceQuotationHeadersByNumber = $lookup;
    }

    protected function sourceQuotationRentalRows(): array
    {
        if ($this->sourceQuotationRentalRowsCache !== null) {
            return $this->sourceQuotationRentalRowsCache;
        }

        return $this->sourceQuotationRentalRowsCache = $this->source()
            ->table('MKTQuotationRental')
            ->whereNotNull('TransNmbr')
            ->get()
            ->all();
    }

    protected function sourceQuotationPrimaryBuildingByNumber(): array
    {
        if ($this->sourceQuotationPrimaryBuildingByNumber !== null) {
            return $this->sourceQuotationPrimaryBuildingByNumber;
        }

        $lookup = [];
        foreach ($this->sourceQuotationRentalRows() as $row) {
            $number = $this->normalizeDocumentNumber($row->TransNmbr ?? null);
            $building = $this->cleanString($row->Building ?? null);

            if ($number && $building && !isset($lookup[$number])) {
                $lookup[$number] = $building;
            }
        }

        return $this->sourceQuotationPrimaryBuildingByNumber = $lookup;
    }

    protected function sourceQuotationOldContractByNumber(): array
    {
        if ($this->sourceQuotationOldContractByNumber !== null) {
            return $this->sourceQuotationOldContractByNumber;
        }

        $lookup = [];
        foreach ($this->sourceQuotationRentalRows() as $row) {
            $number = $this->normalizeDocumentNumber($row->TransNmbr ?? null);
            $oldContract = $this->cleanString($row->OldContractNo ?? null);

            if (!$number || !$oldContract) {
                continue;
            }

            if (!isset($lookup[$number]) || strcasecmp($oldContract, $lookup[$number]) < 0) {
                $lookup[$number] = $oldContract;
            }
        }

        return $this->sourceQuotationOldContractByNumber = $lookup;
    }

    protected function sourceBillingGroupsByCode(): array
    {
        if ($this->sourceBillingGroupsByCode !== null) {
            return $this->sourceBillingGroupsByCode;
        }

        $lookup = [];
        foreach ($this->source()->table('MsBillingGroup')->whereNotNull('BillingCode')->get() as $row) {
            $code = $this->cleanString($row->BillingCode ?? null);
            $key = $this->makeKey($code);

            if ($key && !isset($lookup[$key])) {
                $lookup[$key] = $row;
            }
        }

        return $this->sourceBillingGroupsByCode = $lookup;
    }

    protected function cachedTargetRecord(string $table, ?int $id): ?object
    {
        if (!$id) {
            return null;
        }

        if (array_key_exists($id, $this->targetRowCache[$table] ?? [])) {
            return $this->targetRowCache[$table][$id];
        }

        $record = DB::table($table)->where('id', $id)->first();
        $this->targetRowCache[$table][$id] = $record;

        return $record;
    }

    protected function normalizeQuotationType($value): string
    {
        $value = Str::lower($this->cleanString($value) ?? '');
        return Str::contains($value, 'renew') ? 'renewal' : 'new';
    }

    protected function normalizeContractType($value): string
    {
        $value = Str::lower($this->cleanString($value) ?? '');
        return Str::contains($value, 'renew') ? 'renewal' : 'new';
    }

    protected function normalizeContractPaymentTerms($value): string
    {
        $term = Str::lower($this->cleanString($value) ?? '');

        if ($term === '') {
            return 'cash';
        }

        if (preg_match('/90/', $term)) {
            return 'credit_90';
        }

        if (preg_match('/60/', $term)) {
            return 'credit_60';
        }

        if (preg_match('/30|1\\s*bulan|monthly/', $term)) {
            return 'credit_30';
        }

        if (preg_match('/14h|advance|1x|2x|3x|cash|cod|0/', $term)) {
            return 'cash';
        }

        return 'cash';
    }

    protected function ensureMasterRoomId(
        ?int $buildingId,
        ?int $customerId,
        ?string $roomName,
        ?string $floorCode = null,
        ?string $roomCode = null,
        ?array $dimensions = null
    ): ?int {
        $roomName = $this->cleanString($roomName);
        if (!$buildingId || !$roomName) {
            return null;
        }

        $sourceKey = $this->makeKey([$buildingId, $floorCode, $roomCode, $roomName]);
        $mappedId = $this->findMappedTargetId('CATALYST_ROOM', $sourceKey, 'master_rooms');
        if ($mappedId) {
            return $mappedId;
        }

        $query = DB::table('master_rooms')
            ->where('building_id', $buildingId)
            ->where('room_name', $roomName);

        if (in_array('room_floor', $this->columns('master_rooms'), true) && $this->cleanString($floorCode)) {
            $query->where(function ($sub) use ($floorCode) {
                $sub->where('room_floor', $floorCode)->orWhereNull('room_floor');
            });
        }

        $existing = $query->orderBy('id')->first();
        if ($existing) {
            $this->rememberMap('CATALYST_ROOM', $sourceKey, 'master_rooms', (int) $existing->id, [
                'building_id' => $buildingId,
                'room_name' => $roomName,
                'floor_code' => $floorCode,
                'room_code' => $roomCode,
            ]);
            return (int) $existing->id;
        }

        $payload = [
            'building_id' => $buildingId,
            'customer_id' => $customerId,
            'room_code' => $this->cleanString($roomCode) ?: $this->fallbackRoomCode($roomName, $floorCode),
            'room_name' => $roomName,
            'room_type' => 'service',
            'floor' => $this->cleanString($floorCode),
            'room_floor' => $this->cleanString($floorCode),
            'room_remark' => $this->buildSourceDescription([
                'SourceRoomCode' => $roomCode,
            ]),
            'room_length' => $dimensions['length'] ?? null,
            'room_width' => $dimensions['width'] ?? null,
            'room_height' => $dimensions['height'] ?? null,
            'room_qty' => 1,
            'is_active' => true,
            'created_by' => $this->actorId(),
            'updated_by' => $this->actorId(),
        ];

        $payload = $this->filterPayload('master_rooms', $payload);
        if ($payload === []) {
            return null;
        }

        $id = $this->apply ? $this->insertTargetRecord('master_rooms', $payload) : $this->nextTempId();
        $this->rememberMap('CATALYST_ROOM', $sourceKey, 'master_rooms', $id, [
            'building_id' => $buildingId,
            'room_name' => $roomName,
            'floor_code' => $floorCode,
            'room_code' => $roomCode,
        ]);

        return $id > 0 ? $id : null;
    }

    protected function fallbackRoomCode(?string $roomName, ?string $floorCode = null): string
    {
        $base = strtoupper((string) Str::slug($roomName ?: 'ROOM', ''));
        if ($base === '') {
            $base = 'ROOM';
        }

        $floor = strtoupper($this->cleanString($floorCode) ?? 'GEN');
        return Str::limit($floor . '-' . Str::limit($base, 20, ''), 30, '');
    }

    protected function resolveQuotationExistingContractId(string $quotationNumber): ?int
    {
        $legacyContract = $this->sourceQuotationOldContractByNumber()[$quotationNumber] ?? null;

        if (!$legacyContract) {
            return null;
        }

        return $this->findMappedTargetId('MKTContractHd', $this->makeKey($legacyContract), 'contracts');
    }

    protected function resolveContractQuotationId(?string $sqNo, string $contractNo, ?int $customerId, ?string $contractDate = null): ?int
    {
        $candidates = [];

        foreach ([$sqNo, $contractNo] as $value) {
            $normalized = $this->normalizeDocumentNumber($value);
            if ($normalized) {
                $candidates[] = $normalized;
                $candidates[] = preg_replace('/X$/', '', $normalized);
            }
        }

        $candidates = array_values(array_unique(array_filter($candidates)));

        foreach ($candidates as $candidate) {
            $mappedId = $this->findMappedTargetId('MKTQuotationHd', $this->makeKey($candidate), 'quotations');
            if ($mappedId) {
                return $mappedId;
            }
        }

        foreach ($candidates as $candidate) {
            $query = DB::table('quotations')->where('quotation_number', $candidate);
            if ($customerId) {
                $query->where('customer_id', $customerId);
            }
            if ($contractDate) {
                $query->whereDate('quotation_date', $contractDate);
            }

            $quotationId = $query->value('id');
            if ($quotationId) {
                return (int) $quotationId;
            }
        }

        foreach ($candidates as $candidate) {
            $query = DB::table('quotations')->where('quotation_number', $candidate);
            if ($customerId) {
                $query->where('customer_id', $customerId);
            }

            $quotationId = $query->value('id');
            if ($quotationId) {
                return (int) $quotationId;
            }
        }

        return null;
    }

    protected function resolveBillingGroupTaxPayload(?int $customerId, ?string $npwpNumber, ?object $legacyBillingGroup = null): array
    {
        $npwpNumber = $this->cleanString($npwpNumber);

        $taxRow = null;
        if ($customerId && Schema::hasTable('customer_tax_settings')) {
            $query = DB::table('customer_tax_settings')
                ->where('customer_id', $customerId)
                ->whereNull('deleted_at');

            if ($npwpNumber && in_array('tax_number', $this->columns('customer_tax_settings'), true)) {
                $query->where('tax_number', $npwpNumber);
            }

            $taxRow = $query->orderBy('id')->first()
                ?: DB::table('customer_tax_settings')
                    ->where('customer_id', $customerId)
                    ->whereNull('deleted_at')
                    ->orderBy('id')
                    ->first();
        }

        $taxType = null;
        $taxNumber = null;

        if ($taxRow) {
            if (!blank($taxRow->tax_number ?? null)) {
                $taxType = 'NPWP';
                $taxNumber = $this->cleanString($taxRow->tax_number ?? null);
            } elseif (!blank($taxRow->nik ?? null)) {
                $taxType = 'NIK';
                $taxNumber = $this->cleanString($taxRow->nik ?? null);
            } elseif (!blank($taxRow->nitku ?? null)) {
                $taxType = 'NITKU';
                $taxNumber = $this->cleanString($taxRow->nitku ?? null);
            }
        }

        return [
            'tax_type' => $taxType,
            'tax_number' => $taxNumber,
            'npwp' => $taxRow->tax_number ?? $npwpNumber,
            'nik' => $taxRow->nik ?? null,
            'nitku' => $taxRow->nitku ?? null,
            'npwp_number' => $taxRow->tax_number ?? $npwpNumber,
            'npwp_address' => $taxRow->tax_address ?? $this->cleanString($legacyBillingGroup->Address ?? null),
        ];
    }

    protected function warehouseTypeCode(string $name): string
    {
        return match (Str::lower($name)) {
            'owner' => 'OWNER',
            'supplier' => 'SUPPLIER',
            'deposit out' => 'DEP_OUT',
            default => Str::upper(Str::limit(Str::slug($name, '_'), 20, '')),
        };
    }

    protected function nextTempId(): int
    {
        return $this->tempIdCounter--;
    }

    // =========================================================================
    // STEP: users
    // Source : MsEmployee (HR data) JOIN SAUsers (login credentials)
    // Target : users
    // Depends: branches, departments (must already be imported)
    // =========================================================================

    protected function newImportedUserPassword(): string
    {
        return Hash::make(Str::random(64));
    }

    protected function users(): array
    {
        // Pre-load SAUsers keyed by EmpNumb for quick lookup
        $saUsers = $this->source()->table('SAUsers')
            ->whereNotNull('EmpNumb')
            ->where('EmpNumb', '!=', '')
            ->get();

        $saUsersMap = $saUsers
            ->keyBy(fn ($row) => $this->makeKey($row->EmpNumb))
            ->map(fn ($row) => (array) $row)
            ->all();

        $branchAssignmentsByUserId = [];
        foreach ($this->source()->table('MsBranchUser')->get() as $row) {
            $userIdKey = $this->makeKey($row->UserId ?? null);
            $branchCode = $this->makeKey($row->BranchCode ?? null);

            if (!$userIdKey || !$branchCode || !$this->yesNoToBool($row->FgActive ?? null, true)) {
                continue;
            }

            $branchAssignmentsByUserId[$userIdKey][] = $branchCode;
        }

        $departmentAssignmentsByUserId = [];
        foreach ($this->source()->table('MsDeptUser')->get() as $row) {
            $userIdKey = $this->makeKey($row->UserId ?? null);
            $departmentCode = $this->makeKey($row->Department ?? null);

            if (!$userIdKey || !$departmentCode || !$this->yesNoToBool($row->FgActive ?? null, true)) {
                continue;
            }

            $departmentAssignmentsByUserId[$userIdKey][] = $departmentCode;
        }

        // Pre-load MsJobTitle keyed by JobTtlCode
        $jobTitleMap = $this->source()->table('MsJobTitle')
            ->get()
            ->keyBy(fn ($row) => $this->makeKey($row->JobTtlCode ?? ''))
            ->map(fn ($row) => (array) $row)
            ->all();

        // Pre-load existing usernames from target DB to ensure uniqueness
        $usedUsernames = DB::table('users')
            ->whereNull('deleted_at')
            ->pluck('username')
            ->filter()
            ->flip()
            ->all();

        // Pre-load existing emails to avoid duplicates
        $usedEmails = DB::table('users')
            ->whereNull('deleted_at')
            ->pluck('email')
            ->filter()
            ->map(fn ($e) => strtolower($e))
            ->flip()
            ->all();

        return $this->runStep('users', 'MsEmployee', 'EmpNumb', function (array $row) use (
            $saUsersMap,
            $branchAssignmentsByUserId,
            $departmentAssignmentsByUserId,
            $jobTitleMap,
            &$usedUsernames,
            &$usedEmails,
        ) {
            $empNumb = $this->cleanString($row['EmpNumb'] ?? null);
            $name    = $this->cleanString($row['EmpName'] ?? null);

            if (!$empNumb || !$name) {
                return $this->failedRow('Employee number or name is empty.');
            }

            $sourceKey = $this->makeKey($empNumb);
            $saUser    = $saUsersMap[$sourceKey] ?? null;
            $loginUserIdKey = $this->makeKey($saUser['UserId'] ?? null);
            $existingTargetId = $this->findMappedTargetId('MsEmployee', $sourceKey, 'users');
            $existingTargetUser = $existingTargetId
                ? DB::table('users')->where('id', $existingTargetId)->first()
                : DB::table('users')->where('nik', $empNumb)->first();

            // --- Foreign key resolution ---
            $assignedDepartmentCodes = $loginUserIdKey
                ? array_values(array_unique(array_filter($departmentAssignmentsByUserId[$loginUserIdKey] ?? [])))
                : [];
            $deptCode = $this->makeKey($row['Department'] ?? null);
            if (!$deptCode && $assignedDepartmentCodes !== []) {
                $deptCode = $assignedDepartmentCodes[0];
            }
            $departmentId = $deptCode
                ? $this->findMappedTargetId('MsDepartment', $deptCode, 'departments')
                : null;

            $assignedBranchCodes = $loginUserIdKey
                ? array_values(array_unique(array_filter($branchAssignmentsByUserId[$loginUserIdKey] ?? [])))
                : [];
            $branchCode = $this->makeKey($row['Branch'] ?? null)
                ?: $this->makeKey($saUser['BranchCode'] ?? null);
            if (!$branchCode && $assignedBranchCodes !== []) {
                $branchCode = $assignedBranchCodes[0];
            }
            $branchId   = $branchCode
                ? $this->findMappedTargetId('MsBranch', $branchCode, 'branches')
                : null;

            // --- Position name via MsJobTitle lookup ---
            $jobTitleCode = $this->makeKey($row['JobTitle'] ?? null);
            $positionName = isset($jobTitleMap[$jobTitleCode])
                ? $this->cleanString($jobTitleMap[$jobTitleCode]['JobTtlName'] ?? null)
                : $this->cleanString($row['JobTitle'] ?? null);

            // --- Username (from SAUsers, fallback derived from name) ---
            $baseUsername = $saUser
                ? $this->cleanString($saUser['UserName'] ?? null)
                : null;

            if (!$baseUsername) {
                $baseUsername = strtolower(preg_replace('/[^a-z0-9._]/i', '', str_replace(' ', '.', $name)));
            }
            $baseUsername = strtolower($baseUsername ?: 'emp' . $empNumb);

            // Ensure username is unique in this batch + existing records
            $currentUsername = strtolower($this->cleanString($existingTargetUser->username ?? null) ?? '');
            $username = $currentUsername !== '' ? $currentUsername : $baseUsername;
            if ($username === '') {
                $username = 'emp' . strtolower($empNumb);
            }

            $suffix = 1;
            while (true) {
                $ownerId = DB::table('users')->where('username', $username)->value('id');
                if (!$ownerId || (int) $ownerId === (int) ($existingTargetUser->id ?? 0)) {
                    break;
                }

                if ($currentUsername !== '' && $username === $currentUsername) {
                    $username = $baseUsername;
                } else {
                    $username = $baseUsername . $suffix;
                    $suffix++;
                }
            }
            $usedUsernames[$username] = true;

            // --- Email (validate; placeholder if invalid) ---
            $email = $this->cleanString($row['Email'] ?? null);
            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $email = 'emp' . strtolower($empNumb) . '@pinkads.internal';
            }
            // Ensure email uniqueness
            $currentEmail = strtolower($this->cleanString($existingTargetUser->email ?? null) ?? '');
            $emailLower = strtolower($email);
            $emailOwnerId = DB::table('users')->where('email', $email)->value('id');
            if ($emailOwnerId && (int) $emailOwnerId !== (int) ($existingTargetUser->id ?? 0)) {
                if ($currentEmail !== '') {
                    $email = $currentEmail;
                    $emailLower = strtolower($email);
                } else {
                    $email = 'emp' . strtolower($empNumb) . '@pinkads.internal';
                    $emailLower = strtolower($email);
                }
            }
            $usedEmails[$emailLower] = true;

            // --- Active status (both MsEmployee AND SAUsers must be active) ---
            $isActive = $this->yesNoToBool($row['FgActive'] ?? null, true)
                && ($saUser ? $this->yesNoToBool($saUser['FgActive'] ?? null, true) : true);

            // --- BPJS: only include if FgJamsosTek = Y, skip sentinel date 1900-01-01 ---
            $hasBpjs  = $this->yesNoToBool($row['FgJamsosTek'] ?? null, false);
            $bpjsDate = $hasBpjs ? $this->toDate($row['JamSosTekDate'] ?? null) : null;
            if ($bpjsDate && $bpjsDate < '1950-01-01') {
                $bpjsDate = null; // sentinel date
            }

            $userPayload = [
                'name'             => $name,
                'username'         => $username,
                'email'            => $email,
                'join_date'        => $this->toDate($row['HireDate'] ?? null),
                'phone'            => $this->cleanString($row['ResPhone'] ?? null),
                'handphone_1'      => $this->cleanString($row['HandPhone'] ?? null),
                'address_1'        => $this->cleanString($row['ResAddr'] ?? null),
                'address_2'        => $this->cleanString($row['OriAddr'] ?? null),
                'identity_number'  => $this->cleanString($row['IDCard'] ?? null),
                'npwp_number'      => $this->cleanString($row['NPWPNo'] ?? null),
                'bpjs_number'      => $hasBpjs ? $this->cleanString($row['JamSosTekNo'] ?? null) : null,
                'bpjs_date'        => $bpjsDate,
                'position_name'    => $positionName,
                'department_id'    => $departmentId,
                'department_name'  => $departmentId ? null : $this->cleanString($row['Department'] ?? null),
                'branch_id'        => $branchId,
                'branch_name'      => $branchId ? null : $this->cleanString($row['Branch'] ?? null),
                'is_active'        => $isActive,
                'data_restriction' => 'none',
                'description'      => $this->buildSourceDescription([
                    'EmpNumb'    => $empNumb,
                    'Department' => $this->cleanString($row['Department'] ?? null),
                    'Branch'     => $this->cleanString($row['Branch'] ?? null),
                    'JobTitle'   => $this->cleanString($row['JobTitle'] ?? null),
                    'LoginUserId' => $this->cleanString($saUser['UserId'] ?? null),
                ]),
            ];

            if ($this->apply && !$existingTargetUser) {
                $userPayload['password'] = $this->newImportedUserPassword();
            }

            $result = $this->syncRecord('users', 'MsEmployee', $sourceKey, 'users', [
                'nik' => $empNumb,
            ], $userPayload, $row);

            if ($this->apply && ($result['target_id'] ?? 0) > 0 && Schema::hasTable('branch_user')) {
                $branchIds = collect($assignedBranchCodes)
                    ->prepend($branchCode)
                    ->filter()
                    ->unique()
                    ->map(fn ($code) => $this->findMappedTargetId('MsBranch', $code, 'branches'))
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->values();

                if ($branchIds->isNotEmpty()) {
                    $syncData = [];
                    foreach ($branchIds as $id) {
                        $syncData[$id] = [
                            'is_primary' => $branchId === $id,
                            'updated_by' => $this->actorId(),
                            'created_by' => $this->actorId(),
                        ];
                    }

                    DB::table('branch_user')
                        ->where('user_id', $result['target_id'])
                        ->whereNotIn('branch_id', $branchIds->all())
                        ->delete();

                    foreach ($syncData as $id => $pivotPayload) {
                        DB::table('branch_user')->updateOrInsert(
                            [
                                'user_id' => $result['target_id'],
                                'branch_id' => $id,
                            ],
                            array_merge($pivotPayload, [
                                'created_at' => now(),
                                'updated_at' => now(),
                            ])
                        );
                    }
                }
            }

            return $result;
        });
    }

    // ── Transaksional: Surveys ──────────────────────────────────────────────
    
    protected function surveys(): array
    {
        $rentals = array_filter(
            $this->sourceQuotationRentalRows(),
            fn ($row) => !blank($row->TransNmbr ?? null) && !blank($row->Building ?? null)
        );

        $grouped = [];
        foreach ($rentals as $r) {
            $sq = $this->normalizeDocumentNumber($r->TransNmbr ?? null) ?? '';
            $bd = trim($r->Building ?? '');
            if ($sq && $bd && !isset($grouped["{$sq}||{$bd}"])) {
                $payload = (array) $r;
                $payload['TransNmbr'] = $sq;
                $grouped["{$sq}||{$bd}"] = $payload;
            }
        }

        return $this->runRows('surveys', 'MKTQuotationRental_survey', array_values($grouped), function(array $row) {
            $sqNo = $this->normalizeDocumentNumber($row['TransNmbr'] ?? null) ?? '';
            $buildingCode = trim($row['Building'] ?? '');
            $key = $this->quotationSurveySourceKey($sqNo, $buildingCode);

            $hd = $this->findSourceQuotationHeader($sqNo);
            if (!$hd) {
                return $this->skippedRow('Legacy quotation header not found for ' . $sqNo, $key);
            }

            $custCode = $this->cleanString($hd->Customer ?? null);
            $customerId = $this->findMappedTargetId('MsCustomer', $this->makeKey($custCode), 'customers');
            if (!$customerId) {
                return $this->failedRow('Customer mapping missing for ' . ($custCode ?? 'empty'));
            }

            $salesCode = $this->cleanString($hd->Sales ?? null);
            $marketingId = $salesCode ? $this->findMappedTargetId('MsEmployee', $this->makeKey($salesCode), 'users') : null;

            $buildingId = $this->findMappedTargetId('MsBuilding', $this->makeKey($buildingCode), 'buildings');
            if (!$buildingId) {
                return $this->failedRow('Building mapping missing for ' . $buildingCode);
            }

            $building = $this->cachedTargetRecord('buildings', $buildingId);
            $customer = $this->cachedTargetRecord('customers', $customerId);

            $fgActive = $this->yesNoToBool($hd->FgActive ?? 'Y', true);
            $status = $fgActive ? 'approved' : 'cancelled';

            $existingId = $this->findMappedTargetId('MKTQuotationRental_survey', $key, 'surveys');
            if ($existingId) {
                $surveyNumber = \Illuminate\Support\Facades\DB::table('surveys')->where('id', $existingId)->value('survey_number');
            } else {
                $surveyNumber = app(\App\Services\DocumentNumberService::class)->generate('survey', null, $buildingId);
            }

            $payload = [
                'customer_id' => $customerId,
                'building_id' => $buildingId,
                'survey_date' => $this->toDate($hd->TransDate ?? null),
                'survey_location' => $this->surveyBuildingDisplayName($building) ?: $buildingCode,
                'contact_person' => $this->cleanString($hd->ContactName ?? null),
                'position' => $this->cleanString($hd->ContactTitle ?? null),
                'phone_1' => $this->normalizePhoneLike($hd->ContactPhone ?? null),
                'marketing_id' => $marketingId,
                'surveyor_id' => $marketingId ?: $this->actorId(),
                'status' => $status,
                'recommendations' => $this->cleanString($hd->Remark ?? null),
                'company_name' => $this->cleanString($customer->name ?? null),
                'customer_type' => $this->cleanString($customer->company_type ?? $customer->customer_type ?? null),
                'building_name' => $this->surveyBuildingDisplayName($building),
                'address_1' => $this->surveyBuildingAddress($building),
                'city' => $this->surveyBuildingCity($building),
                'postal_code' => $this->surveyBuildingPostalCode($building),
                'building_location_detail' => $this->buildSourceDescription([
                    'LegacyQuotation' => $sqNo,
                    'LegacyBuildingCode' => $buildingCode,
                ]),
                'created_by' => $this->actorId(),
                'updated_by' => $this->actorId(),
            ];

            return $this->syncRecord('surveys', 'MKTQuotationRental_survey', $key, 'surveys', [
                'survey_number' => $surveyNumber,
            ], $payload, $row);
        });
    }

    protected function survey_details(): array
    {
        $rows = array_filter(
            $this->sourceQuotationRentalRows(),
            fn ($row) => !blank($row->TransNmbr ?? null) && !blank($row->Building ?? null)
        );

        $grouped = [];
        foreach ($rows as $r) {
            $sourceKey = $this->quotationRoomSourceKey(
                $this->normalizeDocumentNumber($r->TransNmbr ?? null) ?? '',
                trim($r->Building ?? ''),
                trim($r->FloorCode ?? ''),
                trim($r->RoomCode ?? ''),
                trim($r->Room ?? '')
            );

            if ($sourceKey && !isset($grouped[$sourceKey])) {
                $payload = (array) $r;
                $payload['TransNmbr'] = $this->normalizeDocumentNumber($r->TransNmbr ?? null);
                $grouped[$sourceKey] = $payload;
            }
        }

        return $this->runRows('survey_details', 'MKTQuotationRental_room', array_values($grouped), function(array $row) {
            $sqNo = $this->normalizeDocumentNumber($row['TransNmbr'] ?? null) ?? '';
            $buildingCode = trim($row['Building'] ?? '');
            $surveyId = $this->findMappedTargetId('MKTQuotationRental_survey', $this->quotationSurveySourceKey($sqNo, $buildingCode), 'surveys');
            if (!$surveyId) {
                return $this->skippedRow('Survey missing', $this->quotationRoomSourceKey(
                    $sqNo,
                    $buildingCode,
                    $row['FloorCode'] ?? null,
                    $row['RoomCode'] ?? null,
                    $row['Room'] ?? null
                ));
            }

            $survey = $this->cachedTargetRecord('surveys', $surveyId);
            $roomName = $this->cleanString($row['Room'] ?? null) ?: 'General';
            $roomId = $this->ensureMasterRoomId(
                $survey->building_id ?? null,
                $survey->customer_id ?? null,
                $roomName,
                $this->cleanString($row['FloorCode'] ?? null),
                $this->cleanString($row['RoomCode'] ?? null),
                [
                    'length' => (float) ($row['Panjang'] ?? 0),
                    'width' => (float) ($row['Lebar'] ?? 0),
                    'height' => (float) ($row['Tinggi'] ?? 0),
                ]
            );

            return $this->syncRecord('survey_details', 'MKTQuotationRental_room', $this->quotationRoomSourceKey(
                $sqNo,
                $buildingCode,
                $row['FloorCode'] ?? null,
                $row['RoomCode'] ?? null,
                $row['Room'] ?? null
            ), 'survey_details', [
                'survey_id' => $surveyId,
                'room_name' => $roomName,
            ], [
                'room_id' => $roomId,
                'room_type' => 'service',
                'quantity_needed' => 1,
                'room_area' => max(0, (float) ($row['Panjang'] ?? 0) * (float) ($row['Lebar'] ?? 0)),
                'specifications' => json_encode([
                    'legacy_floor_code' => $this->cleanString($row['FloorCode'] ?? null),
                    'legacy_room_code' => $this->cleanString($row['RoomCode'] ?? null),
                    'length' => (float) ($row['Panjang'] ?? 0),
                    'width' => (float) ($row['Lebar'] ?? 0),
                    'height' => (float) ($row['Tinggi'] ?? 0),
                ]),
                'created_by' => $this->actorId(),
                'updated_by' => $this->actorId(),
            ], $row);
        });
    }

    protected function quotations(): array
    {
        $headers = array_map(fn ($row) => (array) $row, array_values($this->sourceQuotationHeadersByNumber()));
        $primaryBuildings = $this->sourceQuotationPrimaryBuildingByNumber();

        return $this->runRows('quotations', 'MKTQuotationHd', $headers, function(array $row) use ($primaryBuildings) {
            $sqNo = $this->normalizeDocumentNumber($row['TransNmbr'] ?? null) ?? '';
            $custCode = $this->cleanString($row['Customer'] ?? null);
            if (!$sqNo || !$custCode) {
                return $this->skippedRow('TransNmbr/Customer missing.', $this->makeKey($row['TransNmbr'] ?? null));
            }
            $customerId = $this->findMappedTargetId('MsCustomer', $this->makeKey($custCode), 'customers');
            if (!$customerId) {
                return $this->failedRow('Customer missing');
            }

            $salesCode = $this->cleanString($row['Sales'] ?? null);
            $marketingId = $salesCode ? $this->findMappedTargetId('MsEmployee', $this->makeKey($salesCode), 'users') : null;
            $branchId = $this->findMappedTargetId('MsBranch', $this->makeKey($row['Branch'] ?? ''), 'branches');
            $customer = $this->cachedTargetRecord('customers', $customerId);
            $primaryBuildingCode = $primaryBuildings[$sqNo] ?? null;
            $primarySurveyId = $primaryBuildingCode
                ? $this->findMappedTargetId('MKTQuotationRental_survey', $this->quotationSurveySourceKey($sqNo, $this->cleanString($primaryBuildingCode) ?? ''), 'surveys')
                : null;

            $statusRaw = trim($row['Status'] ?? 'O');
            $fgActive = $this->yesNoToBool($row['FgActive'] ?? 'Y', true);
            $doneContract = $this->yesNoToBool($row['DoneContractPrice'] ?? 'N', false);
            $soContractNo = trim($row['SoContractNo'] ?? '');

            $status = 'draft';
            if ($statusRaw === 'P' && $doneContract && $soContractNo) $status = 'contract';
            elseif ($statusRaw === 'P' && $fgActive) $status = 'approved';
            elseif ($statusRaw === 'X' || !$fgActive) $status = 'expired';

            $transDate = $this->toDate($row['TransDate'] ?? null);
            $validUntil = $transDate ? date('Y-m-d', strtotime($transDate . ' +30 days')) : null;

            return $this->syncRecord('quotations', 'MKTQuotationHd', $this->makeKey($sqNo), 'quotations', [
                'quotation_number' => $sqNo,
            ], [
                'customer_id' => $customerId,
                'prospect_id' => $customerId,
                'survey_id' => $primarySurveyId ?: $this->findMappedTargetId('MKTQuotationRental_survey', $this->quotationSurveySourceKey($sqNo, $this->cleanString($row['BillTo'] ?? null) ?? ''), 'surveys'),
                'company_name' => $this->cleanString($customer->name ?? null),
                'quotation_date' => $transDate,
                'valid_until' => $validUntil,
                'pic_name' => $this->cleanString($row['ContactName'] ?? null),
                'branch_id' => $branchId,
                'marketing_id' => $marketingId,
                'rental_period' => trim(($row['ContractPeriod'] ?? '12') . ' bulan'),
                'rental_unit' => 'bulan',
                'total_amount' => (float)($row['BaseForex'] ?? 0),
                'discount_amount' => (float)($row['DiscForex'] ?? 0),
                'tax_amount' => (float)($row['PpnForex'] ?? 0),
                'grand_total' => (float)($row['TotalForex'] ?? 0),
                'terms_of_payment' => $this->cleanString($row['Term'] ?? null),
                'additional_notes' => $this->cleanString($row['Remark'] ?? null),
                'internal_notes' => $this->cleanString($row['RemarkInternal'] ?? null),
                'quotation_type' => $this->normalizeQuotationType($row['SalesType'] ?? null),
                'existing_contract_id' => $this->resolveQuotationExistingContractId($sqNo),
                'is_latest_revision' => true,
                'status' => $status,
                'created_by' => $this->actorId(),
                'updated_by' => $this->actorId(),
            ], $row);
        });
    }

    protected $quotationSurveyCounter = [];

    protected function quotation_surveys(): array
    {
        // Link all split surveys back to their origin quotation
        $rentals = array_filter(
            $this->sourceQuotationRentalRows(),
            fn ($row) => !blank($row->TransNmbr ?? null) && !blank($row->Building ?? null)
        );

        $grouped = [];
        foreach ($rentals as $r) {
            $sq = $this->normalizeDocumentNumber($r->TransNmbr ?? null) ?? '';
            $bd = trim($r->Building ?? '');
            if ($sq && $bd && !isset($grouped["{$sq}||{$bd}"])) {
                $payload = (array) $r;
                $payload['TransNmbr'] = $sq;
                $grouped["{$sq}||{$bd}"] = $payload;
            }
        }

        $this->quotationSurveyCounter = [];

        return $this->runRows('quotation_surveys', 'MKTQuotationRental_qs', array_values($grouped), function(array $row) {
            $sqNo = $this->normalizeDocumentNumber($row['TransNmbr'] ?? null) ?? '';
            $bd = trim($row['Building'] ?? '');
            $key = $this->quotationSurveySourceKey($sqNo, $bd);

            $quotationId = $this->findMappedTargetId('MKTQuotationHd', $this->makeKey($sqNo), 'quotations');
            $surveyId = $this->findMappedTargetId('MKTQuotationRental_survey', $key, 'surveys');

            if (!$quotationId || !$surveyId) {
                return $this->skippedRow('Q/S ID missing', $key);
            }

            $this->quotationSurveyCounter[$quotationId] = ($this->quotationSurveyCounter[$quotationId] ?? 0) + 1;

            if ($this->quotationSurveyCounter[$quotationId] === 1 && $this->apply) {
                DB::table('quotations')->where('id', $quotationId)->update(['survey_id' => $surveyId]);
            }

            $exists = DB::table('quotation_surveys')->where('quotation_id', $quotationId)->where('survey_id', $surveyId)->exists();
            if (!$exists && $this->apply) {
                DB::table('quotation_surveys')->insert([
                    'quotation_id' => $quotationId,
                    'survey_id' => $surveyId,
                    'added_at' => now(),
                    'added_by' => $this->actorId(),
                    'sort_order' => $this->quotationSurveyCounter[$quotationId] - 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return ['action' => $exists ? 'skipped' : 'inserted'];
        });
    }

    protected function quotation_rooms(): array
    {
        $rentals = array_filter(
            $this->sourceQuotationRentalRows(),
            fn ($row) => !blank($row->TransNmbr ?? null)
        );

        $grouped = [];
        foreach ($rentals as $r) {
            $sq = $this->normalizeDocumentNumber($r->TransNmbr ?? null) ?? '';
            $bd = trim($r->Building ?? '');
            $key = implode('||', [$sq, $bd, trim($r->FloorCode ?? ''), trim($r->RoomCode ?? ''), trim($r->Room ?? '')]);
            if ($sq && !isset($grouped[$key])) {
                $payload = (array) $r;
                $payload['TransNmbr'] = $sq;
                $grouped[$key] = [
                    'key' => $key,
                    'row' => $payload
                ];
            }
        }

        return $this->runRows('quotation_rooms', 'MKTQuotationRental_qr_room', array_values($grouped), function(array $item) {
            $key = $item['key'];
            $row = $item['row'];
            $parts = explode('||', $key);
            $quotationId = $this->findMappedTargetId('MKTQuotationHd', $this->makeKey($parts[0]), 'quotations');
            if (!$quotationId) {
                return $this->skippedRow('Qid missing', $this->makeKey($key));
            }

            $buildingId = $this->findMappedTargetId('MsBuilding', $this->makeKey($parts[1]), 'buildings');
            $surveyId = $this->findMappedTargetId('MKTQuotationRental_survey', $this->quotationSurveySourceKey($parts[0], $parts[1]), 'surveys');
            $survey = $this->cachedTargetRecord('surveys', $surveyId);
            $roomName = $this->cleanString($row['Room'] ?? null) ?: 'General';
            $masterRoomId = $this->ensureMasterRoomId(
                $buildingId,
                $survey->customer_id ?? null,
                $roomName,
                $row['FloorCode'] ?? null,
                $row['RoomCode'] ?? null,
                [
                    'length' => (float) ($row['Panjang'] ?? 0),
                    'width' => (float) ($row['Lebar'] ?? 0),
                    'height' => (float) ($row['Tinggi'] ?? 0),
                ]
            );

            return $this->syncRecord('quotation_rooms', 'MKTQuotationRental_qr_room', $this->makeKey($key), 'quotation_rooms', [
                'quotation_id' => $quotationId,
                'room_id' => $masterRoomId,
            ], [
                'room_name' => $roomName,
                'room_specifications' => $this->buildSourceDescription([
                    'LegacyBuildingCode' => $parts[1] ?? null,
                    'LegacyFloorCode' => $row['FloorCode'] ?? null,
                    'LegacyRoomCode' => $row['RoomCode'] ?? null,
                ]),
                'created_by' => $this->actorId(),
                'updated_by' => $this->actorId(),
            ], $row);
        });
    }

    protected function quotation_rentals(): array
    {
        $rows = array_map(
            fn ($row) => (array) $row,
            array_values(array_filter(
                $this->sourceQuotationRentalRows(),
                fn ($row) => !blank($row->TransNmbr ?? null) && !blank($row->Product ?? null)
            ))
        );
        // Decorate with index because we need a unique index for row mapping
        foreach ($rows as $index => &$row) {
            $row['__index'] = $index;
        }

        return $this->runRows('quotation_rentals', 'MKTQuotationRental_qr', $rows, function(array $row) {
            $sqNo = $this->normalizeDocumentNumber($row['TransNmbr'] ?? null);
            $product = $this->cleanString($row['Product'] ?? null);
            $sourceKey = $this->makeKey("{$sqNo}||" . ($row['__index'] ?? ''));
            
            $quotationId = $this->findMappedTargetId('MKTQuotationHd', $this->makeKey($sqNo), 'quotations');
            if (!$quotationId) {
                return $this->skippedRow('Q missing', $sourceKey);
            }

            $roomKey = implode('||', [$sqNo, trim($row['Building']??''), trim($row['FloorCode']??''), trim($row['RoomCode']??''), trim($row['Room']??'')]);
            $quotationRoomId = $this->findMappedTargetId('MKTQuotationRental_qr_room', $this->makeKey($roomKey), 'quotation_rooms');
            if (!$quotationRoomId) {
                return $this->failedRow('Quotation room missing');
            }

            $rentalId = $this->findMappedTargetId('MsProduct', $this->makeKey($product), 'master_rentals');
            if (!$rentalId) {
                return $this->failedRow('Master rental missing for ' . ($product ?? 'empty'));
            }

            $qty = (float) ($row['QtyTotal'] ?? $row['QtyContract'] ?? 1);
            $price = (float) ($row['PriceForex'] ?? 0);

            $index = $row['__index'];

            return $this->syncRecord('quotation_rentals', 'MKTQuotationRental_qr', $sourceKey, 'quotation_rentals', [
                'quotation_id' => $quotationId,
                'quotation_room_id' => $quotationRoomId,
                'master_rental_id' => $rentalId,
            ], [
                'quantity' => $qty,
                'unit_price' => $price,
                'total_price' => $qty * $price,
                'aroma_name' => $product ?? '',
                'rental_specifications' => $this->buildSourceDescription([
                    'LegacyBuildingCode' => $row['Building'] ?? null,
                    'LegacyFloorCode' => $row['FloorCode'] ?? null,
                    'LegacyRoomCode' => $row['RoomCode'] ?? null,
                    'LegacyFrequency' => $row['FrequencyService'] ?? null,
                ]),
                'created_by' => $this->actorId(),
                'updated_by' => $this->actorId(),
            ], $row);
        });
    }

    protected function quotation_details(): array
    {
        $rows = array_map(
            fn ($row) => (array) $row,
            array_values(array_filter(
                $this->sourceQuotationRentalRows(),
                fn ($row) => !blank($row->TransNmbr ?? null) && !blank($row->Product ?? null)
            ))
        );

        foreach ($rows as $index => &$row) {
            $row['__index'] = $index;
        }

        return $this->runRows('quotation_details', 'MKTQuotationRental_detail', $rows, function(array $row) {
            $sqNo = $this->normalizeDocumentNumber($row['TransNmbr'] ?? null);
            $sourceKey = $this->makeKey([
                $sqNo,
                $row['Building'] ?? null,
                $row['FloorCode'] ?? null,
                $row['RoomCode'] ?? null,
                $row['Room'] ?? null,
                $row['Product'] ?? null,
                $row['__index'] ?? null,
            ]);
            $quotationId = $this->findMappedTargetId('MKTQuotationHd', $this->makeKey($sqNo), 'quotations');
            if (!$quotationId) {
                return $this->skippedRow('Q missing', $sourceKey);
            }

            $surveyId = $this->findMappedTargetId(
                'MKTQuotationRental_survey',
                $this->quotationSurveySourceKey($sqNo, $this->cleanString($row['Building'] ?? null) ?? ''),
                'surveys'
            );
            if (!$surveyId) {
                return $this->skippedRow('Survey missing', $sourceKey);
            }

            $surveyDetailId = $this->findMappedTargetId(
                'MKTQuotationRental_room',
                $this->quotationRoomSourceKey(
                    $sqNo,
                    $row['Building'] ?? null,
                    $row['FloorCode'] ?? null,
                    $row['RoomCode'] ?? null,
                    $row['Room'] ?? null
                ),
                'survey_details'
            );
            if (!$surveyDetailId) {
                return $this->failedRow('Survey detail missing');
            }

            $rentalId = $this->findMappedTargetId('MsProduct', $this->makeKey($row['Product'] ?? null), 'master_rentals');
            if (!$rentalId) {
                return $this->failedRow('Master rental missing');
            }

            $qty = (float) ($row['QtyTotal'] ?? $row['QtyContract'] ?? 1);
            $price = (float) ($row['PriceForex'] ?? 0);

            return $this->syncRecord('quotation_details', 'MKTQuotationRental_detail', $this->makeKey([
                $sqNo,
                $row['Building'] ?? null,
                $row['FloorCode'] ?? null,
                $row['RoomCode'] ?? null,
                $row['Room'] ?? null,
                $row['Product'] ?? null,
                $row['__index'] ?? null,
            ]), 'quotation_details', [
                'quotation_id' => $quotationId,
                'survey_id' => $surveyId,
                'room_id' => $surveyDetailId,
                'master_rental_id' => $rentalId,
            ], [
                'rental_alias' => $this->cleanString($row['Product'] ?? null),
                'room_name' => $this->cleanString($row['Room'] ?? null) ?: 'General',
                'quantity' => $qty,
                'unit_price' => $price,
                'total_price' => $qty * $price,
                'specifications' => json_encode([
                    'legacy_floor_code' => $this->cleanString($row['FloorCode'] ?? null),
                    'legacy_room_code' => $this->cleanString($row['RoomCode'] ?? null),
                    'legacy_building_code' => $this->cleanString($row['Building'] ?? null),
                ]),
                'created_by' => $this->actorId(),
                'updated_by' => $this->actorId(),
            ], $row);
        });
    }

    protected function contracts(): array
    {
        $headers = $this->source()->table('MKTContractHd')->whereNotNull('TransNmbr')->get()->map(fn ($row) => (array) $row)->all();
        return $this->runRows('contracts', 'MKTContractHd', $headers, function(array $row) {
            $no = trim($row['TransNmbr'] ?? '');
            $custCode = $this->cleanString($row['Customer'] ?? null);
            $customerId = $this->findMappedTargetId('MsCustomer', $this->makeKey($custCode), 'customers');
            if (!$customerId) return $this->failedRow('Customer missing');

            $sqNo = $this->cleanString($row['SqNo'] ?? null);
            $contractDate = $this->toDate($row['TransDate'] ?? null);
            $quotationId = $this->resolveContractQuotationId($sqNo, $no, $customerId, $contractDate);
            $salesCode = $this->cleanString($row['Sales'] ?? null);
            $marketingId = $salesCode ? $this->findMappedTargetId('MsEmployee', $this->makeKey($salesCode), 'users') : null;

            $statusRaw = strtoupper(trim($row['Status'] ?? 'P'));
            $fgTerminate = strtoupper(trim($row['FgTerminate'] ?? '')) === 'Y';
            
            $periodMonths = (int) ($row['ContractPeriod'] ?? 12);
            $startDate = $this->toDate($row['StartDate'] ?? $contractDate);
            $endDate = $this->toDate($row['EndDate'] ?? null);
            if (!$endDate && $startDate) {
                $endDate = \Carbon\Carbon::parse($startDate)->addMonths($periodMonths)->subDay()->toDateString();
            }

            return $this->syncRecord('contracts', 'MKTContractHd', $this->makeKey($no), 'contracts', [
                'contract_number' => $no,
            ], [
                'customer_id' => $customerId,
                'quotation_id' => $quotationId,
                'marketing_id' => $marketingId,
                'contract_date' => $contractDate,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'contract_value' => (float) ($row['BaseForex'] ?? 0),
                'net_value' => (float) ($row['BaseForex'] ?? 0),
                'term_of_payment' => $this->cleanString($row['Terms'] ?? null),
                'payment_terms' => $this->normalizeContractPaymentTerms($row['Terms'] ?? null),
                'npwp_number' => $this->cleanString($row['NPWP'] ?? null),
                'status' => $fgTerminate ? 'terminated' : ($statusRaw === 'X' ? 'inactive' : 'active'),
                'contract_status' => $fgTerminate ? 'terminated' : ($statusRaw === 'X' ? 'inactive' : 'active'),
                'contract_type' => $this->normalizeContractType($row['ContractType'] ?? null),
                'is_approved' => true,
                'is_posted' => $statusRaw === 'P',
                'is_contract' => true,
                'created_by' => $this->actorId(),
                'updated_by' => $this->actorId(),
            ], $row);
        });
    }

    protected function contract_surveys(): array
    {
        $contracts = DB::table('contracts')->whereNotNull('quotation_id')->get();
        $items = [];

        foreach ($contracts as $c) {
            $qs = DB::table('quotation_surveys')->where('quotation_id', $c->quotation_id)->get();
            foreach ($qs as $survey) {
                $items[] = [
                    'contract_id' => $c->id,
                    'contract_number' => $c->contract_number,
                    'survey_id' => $survey->survey_id,
                ];
            }
        }

        return $this->runRows('contract_surveys', 'contract_surveys_v', $items, function(array $item) {
            $key = "{$item['contract_number']}||{$item['survey_id']}";
            
            $exists = DB::table('contract_surveys')
                ->where('contract_id', $item['contract_id'])
                ->where('survey_id', $item['survey_id'])
                ->exists();

            if (!$exists && $this->apply) {
                DB::table('contract_surveys')->insert([
                    'contract_id' => $item['contract_id'],
                    'survey_id' => $item['survey_id'],
                    'added_at' => now(),
                    'added_by' => $this->actorId(),
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return ['action' => $exists ? 'skipped' : 'inserted'];
        });
    }

    protected function billing_groups(): array
    {
        $rows = $this->source()->table('MKTContractDt')
            ->whereNotNull('TransNmbr')
            ->whereNotNull('BillingGroup')
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $contractNo = $this->cleanString($row->TransNmbr ?? null);
            $billingCode = $this->cleanString($row->BillingGroup ?? null);
            $groupKey = $this->makeKey([$contractNo, $billingCode]);
            if (!$groupKey) {
                continue;
            }

            $amount = (float) ($row->AmountForex ?? 0);
            if (!isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [
                    'row' => (array) $row,
                    'amount' => $amount,
                ];
            } else {
                $grouped[$groupKey]['amount'] += $amount;
            }
        }

        $legacyBillingGroups = $this->sourceBillingGroupsByCode();

        return $this->runRows('billing_groups', 'MKTContractDt_billing', array_values($grouped), function(array $item) use ($legacyBillingGroups) {
            $row = $item['row'];
            $contractNo = $this->cleanString($row['TransNmbr'] ?? null);
            $billingCode = $this->cleanString($row['BillingGroup'] ?? null);

            $contractId = $this->findMappedTargetId('MKTContractHd', $this->makeKey($contractNo), 'contracts');
            if (!$contractId) {
                return $this->failedRow('Contract missing for billing group');
            }

            $contract = DB::table('contracts')->where('id', $contractId)->first();
            $legacyBilling = $billingCode ? ($legacyBillingGroups[$this->makeKey($billingCode)] ?? null) : null;
            $customer = $contract?->customer_id ? DB::table('customers')->where('id', $contract->customer_id)->first() : null;
            $taxPayload = $this->resolveBillingGroupTaxPayload($contract->customer_id ?? null, $row['NPWP'] ?? null, $legacyBilling);

            return $this->syncRecord('billing_groups', 'MKTContractDt_billing', $this->makeKey([$contractNo, $billingCode]), 'billing_groups', [
                'contract_id' => $contractId,
                'billing_group_name' => $billingCode ?: ('Billing Group ' . $contractNo),
            ], array_filter([
                'customer_id' => $contract->customer_id ?? null,
                'billing_group_name' => $billingCode ?: ('Billing Group ' . $contractNo),
                'billing_frequency' => 'monthly',
                'billing_start_date' => $contract->start_date ?? null,
                'billing_end_date' => $contract->end_date ?? null,
                'billing_amount' => $item['amount'] ?? 0,
                'is_active' => true,
                'pic_name' => $this->cleanString($legacyBilling->Attn ?? null),
                'pic_phone' => $this->normalizePhoneLike($legacyBilling->Telp ?? $legacyBilling->Phone ?? null),
                'pic_email' => $this->cleanString($legacyBilling->EmailBilling ?? null),
                'pic_address' => $this->cleanString($legacyBilling->Address ?? null),
                'npwp_name' => $this->cleanString($customer->name ?? null),
                'invoice_type' => 'soft_copy',
                'payment_method' => null,
                'virtual_account_number' => $this->cleanString($contract->virtual_account ?? null),
                'bank_name' => null,
                'created_by' => $this->actorId(),
                'updated_by' => $this->actorId(),
            ] + $taxPayload, fn ($value) => !($value === '' || $value === [])), $row);
        });
    }

    protected function contract_buildings(): array
    {
        $rows = $this->source()->table('MKTContractDt')
            ->whereNotNull('TransNmbr')
            ->whereNotNull('Building')
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $key = $this->makeKey([$row->TransNmbr ?? null, $row->BillingGroup ?? null, $row->Building ?? null]);
            if ($key && !isset($grouped[$key])) {
                $grouped[$key] = (array) $row;
            }
        }

        return $this->runRows('contract_buildings', 'MKTContractDt_building', array_values($grouped), function(array $row) {
            $contractId = $this->findMappedTargetId('MKTContractHd', $this->makeKey($row['TransNmbr'] ?? null), 'contracts');
            $buildingId = $this->findMappedTargetId('MsBuilding', $this->makeKey($row['Building'] ?? null), 'buildings');
            $billingGroupId = $this->findMappedTargetId('MKTContractDt_billing', $this->makeKey([$row['TransNmbr'] ?? null, $row['BillingGroup'] ?? null]), 'billing_groups');

            if (!$contractId || !$buildingId || !$billingGroupId) {
                return $this->failedRow('Contract/building/billing group mapping missing');
            }

            $exists = DB::table('billing_group_buildings')
                ->where('billing_group_id', $billingGroupId)
                ->where('building_id', $buildingId)
                ->exists();

            if (!$exists && $this->apply) {
                DB::table('billing_group_buildings')->insert([
                    'billing_group_id' => $billingGroupId,
                    'building_id' => $buildingId,
                    'billing_amount' => (float) ($row['AmountForex'] ?? 0),
                    'is_active' => true,
                    'created_by' => $this->actorId(),
                    'updated_by' => $this->actorId(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $legacyExists = DB::table('contract_buildings')
                ->where('billing_id', $billingGroupId)
                ->where('building_id', $buildingId)
                ->exists();

            if (!$legacyExists && $this->apply) {
                DB::table('contract_buildings')->insert([
                    'billing_id' => $billingGroupId,
                    'building_id' => $buildingId,
                ]);
            }

            return ['action' => ($exists || $legacyExists) ? 'updated' : 'inserted'];
        });
    }

    protected function contract_rooms(): array
    {
        $rows = $this->source()->table('MKTContractDt')->whereNotNull('TransNmbr')->get();

        $grouped = [];
        foreach ($rows as $r) {
            $cn = trim($r->TransNmbr ?? '');
            $key = implode('||', [$cn, trim($r->Building ?? ''), trim($r->FloorCode ?? ''), trim($r->RoomCode ?? ''), trim($r->Room ?? '')]);
            if ($cn && !isset($grouped[$key])) {
                $grouped[$key] = [
                    'key' => $key,
                    'row' => (array) $r
                ];
            }
        }

        return $this->runRows('contract_rooms', 'MKTContractDt_room', array_values($grouped), function(array $item) {
            $key = $item['key'];
            $parts = explode('||', $key);
            
            $contractId = $this->findMappedTargetId('MKTContractHd', $this->makeKey($parts[0]), 'contracts');
            if (!$contractId) {
                return $this->failedRow('Contract Missing');
            }

            $row = $item['row'];
            $contract = DB::table('contracts')->where('id', $contractId)->first();
            $buildingId = $this->findMappedTargetId('MsBuilding', $this->makeKey($parts[1] ?? null), 'buildings');
            $masterRoomId = $this->ensureMasterRoomId(
                $buildingId,
                $contract->customer_id ?? null,
                $row['Room'] ?? null,
                $row['FloorCode'] ?? null,
                $row['RoomCode'] ?? null
            );
            $billingGroupId = $this->findMappedTargetId('MKTContractDt_billing', $this->makeKey([$parts[0] ?? null, $row['BillingGroup'] ?? null]), 'billing_groups');

            return $this->syncRecord('contract_rooms', 'MKTContractDt_room', $this->makeKey($key), 'contract_rooms', [
                'contract_id' => $contractId,
                'room_id' => $masterRoomId,
            ], [
                'billing_group_id' => $billingGroupId,
                'created_by' => $this->actorId(),
                'updated_by' => $this->actorId(),
            ], $item['row']);
        });
    }

    protected function contract_rentals(): array
    {
        $rows = $this->source()->table('MKTContractDt')->whereNotNull('TransNmbr')->whereNotNull('Product')->get()->map(fn($r)=>(array)$r)->all();
        foreach ($rows as $index => &$row) {
            $row['__index'] = $index;
        }

        return $this->runRows('contract_rentals', 'MKTContractDt_rental', $rows, function(array $row) {
            $contractNo = $this->cleanString($row['TransNmbr'] ?? null);
            $product = $this->cleanString($row['Product'] ?? null);
            
            $contractId = $this->findMappedTargetId('MKTContractHd', $this->makeKey($contractNo), 'contracts');
            if (!$contractId) {
                return $this->failedRow('C Missing');
            }

            $rentalId = $this->findMappedTargetId('MsProduct', $this->makeKey($product), 'master_rentals');
            if (!$rentalId) {
                return $this->failedRow('Master rental missing');
            }

            $qty = (float) ($row['Qty'] ?? 0);
            $price = (float) ($row['PriceForex'] ?? 0);
            $index = $row['__index'];
            $roomId = $this->findMappedTargetId('CATALYST_ROOM', $this->makeKey([
                $this->findMappedTargetId('MsBuilding', $this->makeKey($row['Building'] ?? null), 'buildings'),
                $row['FloorCode'] ?? null,
                $row['RoomCode'] ?? null,
                $row['Room'] ?? null,
            ]), 'master_rooms');

            return $this->syncRecord('contract_rentals', 'MKTContractDt_rental', $this->makeKey("{$contractNo}||{$index}"), 'contract_rentals', [
                'contract_id' => $contractId,
                'master_rental_id' => $rentalId,
                'room_id' => $roomId,
            ], [
                'rental_alias' => $product,
                'quantity' => $qty,
                'unit_price' => $price,
                'total_price' => $qty * $price,
                'created_by' => $this->actorId(),
                'updated_by' => $this->actorId(),
            ], $row);
        });
    }
}
