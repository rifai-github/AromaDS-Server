<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ImportCatalystContractHistoryFromExport extends Command
{
    protected $signature = 'catalyst:import-contract-history-export
                            {--headers-file=storage/app/catalyst/contracts/contract_headers_export.csv : CSV export file for MKTContractHd}
                            {--details-file=storage/app/catalyst/contracts/contract_details_export.csv : CSV export file for MKTContractDt}
                            {--billing-file=storage/app/catalyst/contracts/billing_groups_lookup_export.csv : CSV export file for MsBillingGroup}
                            {--apply : Persist changes to target tables}';

    protected $description = 'Import historical contracts and billing groups from Catalyst contract CSV exports.';

    public function handle(): int
    {
        $headersFile = $this->resolveFilePath((string) $this->option('headers-file'));
        $detailsFile = $this->resolveFilePath((string) $this->option('details-file'));
        $billingFile = $this->resolveFilePath((string) $this->option('billing-file'));
        $apply = (bool) $this->option('apply');

        foreach ([$headersFile, $detailsFile, $billingFile] as $file) {
            if (!is_file($file)) {
                $this->error('File export tidak ditemukan: ' . $file);
                return self::FAILURE;
            }
        }

        $this->info('Running Catalyst contract history import from export in ' . ($apply ? 'apply' : 'dry-run') . ' mode.');

        $headerRows = $this->readCsv($headersFile);
        $detailRows = $this->readCsv($detailsFile);
        $billingRows = $this->readCsv($billingFile);

        $contractColumns = array_flip(Schema::getColumnListing('contracts'));
        $billingColumns = array_flip(Schema::getColumnListing('billing_groups'));
        $billingGroupBuildingColumns = Schema::hasTable('billing_group_buildings')
            ? array_flip(Schema::getColumnListing('billing_group_buildings'))
            : [];
        $legacyContractBuildingColumns = Schema::hasTable('contract_buildings')
            ? array_flip(Schema::getColumnListing('contract_buildings'))
            : [];

        $customerMap = $this->mappedIds('MsCustomer', 'customers');
        $quotationMap = $this->mappedIds('MKTQuotationHd', 'quotations');
        $marketingMap = $this->mappedIds('MsEmployee', 'users');
        $buildingMap = $this->mappedIds('MsBuilding', 'buildings');
        $resolvedContracts = [];
        $resolvedBillingGroups = [];

        $stats = [
            'contracts_processed' => 0,
            'contracts_inserted' => 0,
            'contracts_updated' => 0,
            'contracts_skipped' => 0,
            'contracts_failed' => 0,
            'billing_groups_processed' => 0,
            'billing_groups_inserted' => 0,
            'billing_groups_updated' => 0,
            'billing_groups_skipped' => 0,
            'billing_groups_failed' => 0,
            'contract_buildings_processed' => 0,
            'contract_buildings_inserted' => 0,
            'contract_buildings_updated' => 0,
            'contract_buildings_skipped' => 0,
            'contract_buildings_failed' => 0,
        ];

        $billingLookup = collect($billingRows)
            ->mapWithKeys(function (array $row) {
                $key = $this->cleanString($row['billingcode'] ?? null);

                return $key ? [$key => $row] : [];
            })
            ->all();

        $actorId = $this->actorId();

        foreach ($headerRows as $row) {
            $stats['contracts_processed']++;

            $contractNo = $this->cleanString($row['transnmbr'] ?? null);
            $customerCode = $this->cleanString($row['customer'] ?? null);

            if (!$contractNo || !$customerCode) {
                $stats['contracts_skipped']++;
                continue;
            }

            $customerId = $customerMap[$customerCode] ?? null;
            if (!$customerId) {
                $stats['contracts_failed']++;
                continue;
            }

            $quotationNo = $this->cleanString($row['sqno'] ?? null);
            $quotationId = $quotationNo ? ($quotationMap[$quotationNo] ?? null) : null;
            $marketingCode = $this->cleanString($row['sales'] ?? null);
            $marketingId = $marketingCode ? ($marketingMap[$marketingCode] ?? null) : null;

            $statusRaw = Str::upper($this->cleanString($row['status'] ?? null) ?? 'P');
            $fgTerminate = Str::upper($this->cleanString($row['fgterminate'] ?? null) ?? '') === 'Y';

            $periodMonths = (int) ($row['contractperiod'] ?? 12);
            if ($periodMonths <= 0) {
                $periodMonths = 12;
            }

            $contractDate = $this->toDate($row['transdate'] ?? null);
            $startDate = $this->toDate($row['startdate'] ?? null) ?: $contractDate;
            $endDate = $this->toDate($row['enddate'] ?? null);
            if (!$endDate && $startDate) {
                $endDate = Carbon::parse($startDate)->addMonths($periodMonths)->subDay()->toDateString();
            }

            $contractStatus = $fgTerminate ? 'terminated' : ($statusRaw === 'X' ? 'inactive' : 'active');

            $payload = $this->onlyColumns([
                'customer_id' => $customerId,
                'quotation_id' => $quotationId,
                'marketing_id' => $marketingId,
                'contract_date' => $contractDate,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'contract_value' => (float) ($row['baseforex'] ?? 0),
                'net_value' => (float) ($row['baseforex'] ?? 0),
                'term_of_payment' => $this->cleanString($row['terms'] ?? null),
                'payment_terms' => $this->normalizePaymentTerms($row['terms'] ?? null),
                'npwp_number' => $this->cleanString($row['npwp'] ?? null),
                'status' => $contractStatus,
                'contract_status' => $contractStatus,
                'contract_type' => $this->normalizeContractType($row['contracttype'] ?? null),
                'is_approved' => true,
                'is_posted' => $statusRaw === 'P',
                'is_contract' => true,
                'virtual_account' => $this->cleanString($row['virtualaccount'] ?? null),
                'created_by' => $actorId,
                'updated_by' => $actorId,
                'updated_at' => now(),
            ], $contractColumns);

            $existingMap = DB::table('source_import_maps')
                ->where('source_system', 'catalyst')
                ->where('source_table', 'MKTContractHd')
                ->where('source_key', $contractNo)
                ->where('target_table', 'contracts')
                ->first();

            $existing = null;
            if ($existingMap?->target_id) {
                $existing = DB::table('contracts')->where('id', $existingMap->target_id)->first();
            }

            if (!$existing) {
                $existing = DB::table('contracts')->where('contract_number', $contractNo)->first();
            }

            if (!$apply) {
                $resolvedContracts[$contractNo] = [
                    'target_id' => $existing ? (int) $existing->id : null,
                    'customer_id' => $customerId,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'virtual_account' => $this->cleanString($row['virtualaccount'] ?? null),
                ];
                $stats[$existing ? 'contracts_updated' : 'contracts_inserted']++;
                continue;
            }

            if ($existing) {
                DB::table('contracts')->where('id', $existing->id)->update($payload);
                $targetId = (int) $existing->id;
                $stats['contracts_updated']++;
            } else {
                $targetId = (int) DB::table('contracts')->insertGetId($this->onlyColumns($payload + [
                    'contract_number' => $contractNo,
                    'created_at' => now(),
                ], $contractColumns));
                $stats['contracts_inserted']++;
            }

            DB::table('source_import_maps')->updateOrInsert(
                [
                    'source_system' => 'catalyst',
                    'source_table' => 'MKTContractHd',
                    'source_key' => $contractNo,
                    'target_table' => 'contracts',
                ],
                [
                    'target_id' => $targetId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $resolvedContracts[$contractNo] = [
                'target_id' => $targetId,
                'customer_id' => $customerId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'virtual_account' => $this->cleanString($row['virtualaccount'] ?? null),
            ];
        }

        $contractMap = $this->mappedIds('MKTContractHd', 'contracts');

        $groupedBilling = [];
        foreach ($detailRows as $row) {
            $contractNo = $this->cleanString($row['transnmbr'] ?? null);
            $billingCode = $this->cleanString($row['billinggroup'] ?? null);
            if (!$contractNo || !$billingCode) {
                continue;
            }

            $key = $contractNo . '||' . $billingCode;
            if (!isset($groupedBilling[$key])) {
                $groupedBilling[$key] = [
                    'row' => $row,
                    'amount' => (float) ($row['amountforex'] ?? 0),
                ];
            } else {
                $groupedBilling[$key]['amount'] += (float) ($row['amountforex'] ?? 0);
            }
        }

        foreach ($groupedBilling as $key => $item) {
            $stats['billing_groups_processed']++;

            [$contractNo, $billingCode] = explode('||', $key, 2);
            $contractId = $contractMap[$contractNo] ?? ($resolvedContracts[$contractNo]['target_id'] ?? null);
            $resolvedContract = $resolvedContracts[$contractNo] ?? null;
            if (!$contractId && !$resolvedContract) {
                $stats['billing_groups_failed']++;
                continue;
            }

            $contract = $contractId ? DB::table('contracts')->where('id', $contractId)->first() : null;
            $customerId = $contract->customer_id ?? ($resolvedContract['customer_id'] ?? null);
            $customer = $customerId ? DB::table('customers')->where('id', $customerId)->first() : null;
            $legacyBilling = $billingLookup[$billingCode] ?? [];

            $taxPayload = $this->resolveBillingGroupTaxPayload(
                $customerId,
                $this->cleanString($item['row']['npwp'] ?? null),
                $legacyBilling
            );

            $payload = $this->onlyColumns(array_filter([
                'customer_id' => $customerId,
                'billing_group_name' => $billingCode,
                'billing_frequency' => 'monthly',
                'billing_start_date' => $contract->start_date ?? ($resolvedContract['start_date'] ?? null),
                'billing_end_date' => $contract->end_date ?? ($resolvedContract['end_date'] ?? null),
                'billing_amount' => $item['amount'],
                'is_active' => true,
                'pic_name' => $this->cleanString($legacyBilling['attn'] ?? null),
                'pic_phone' => $this->normalizePhone($legacyBilling['telp'] ?? $legacyBilling['phone'] ?? null),
                'pic_email' => $this->cleanString($legacyBilling['emailbilling'] ?? null),
                'pic_address' => $this->cleanString($legacyBilling['address'] ?? null),
                'npwp_name' => $this->cleanString($customer->name ?? null),
                'invoice_type' => 'soft_copy',
                'payment_method' => null,
                'virtual_account_number' => $this->cleanString($contract->virtual_account ?? ($resolvedContract['virtual_account'] ?? null)),
                'bank_name' => null,
                'contract_id' => $contractId,
                'created_by' => $actorId,
                'updated_by' => $actorId,
                'updated_at' => now(),
            ] + $taxPayload, fn ($value) => !($value === '' || $value === [] || $value === null)), $billingColumns);

            $existingMap = DB::table('source_import_maps')
                ->where('source_system', 'catalyst')
                ->where('source_table', 'MKTContractDt_billing')
                ->where('source_key', $key)
                ->where('target_table', 'billing_groups')
                ->first();

            $existing = null;
            if ($existingMap?->target_id) {
                $existing = DB::table('billing_groups')->where('id', $existingMap->target_id)->first();
            }

            if (!$existing) {
                $existing = DB::table('billing_groups')
                    ->where('contract_id', $contractId)
                    ->where('billing_group_name', $billingCode)
                    ->first();
            }

            if (!$apply) {
                $resolvedBillingGroups[$key] = [
                    'target_id' => $existing ? (int) $existing->id : null,
                ];
                $stats[$existing ? 'billing_groups_updated' : 'billing_groups_inserted']++;
                continue;
            }

            if ($existing) {
                DB::table('billing_groups')->where('id', $existing->id)->update($payload);
                $targetId = (int) $existing->id;
                $stats['billing_groups_updated']++;
            } else {
                $targetId = (int) DB::table('billing_groups')->insertGetId($this->onlyColumns($payload + [
                    'created_at' => now(),
                ], $billingColumns));
                $stats['billing_groups_inserted']++;
            }

            DB::table('source_import_maps')->updateOrInsert(
                [
                    'source_system' => 'catalyst',
                    'source_table' => 'MKTContractDt_billing',
                    'source_key' => $key,
                    'target_table' => 'billing_groups',
                ],
                [
                    'target_id' => $targetId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $resolvedBillingGroups[$key] = [
                'target_id' => $targetId,
            ];
        }

        $billingGroupMap = $this->mappedIds('MKTContractDt_billing', 'billing_groups');

        $groupedBuildings = [];
        foreach ($detailRows as $row) {
            $contractNo = $this->cleanString($row['transnmbr'] ?? null);
            $billingCode = $this->cleanString($row['billinggroup'] ?? null);
            $buildingCode = $this->cleanString($row['building'] ?? null);

            if (!$contractNo || !$billingCode || !$buildingCode) {
                continue;
            }

            $key = $contractNo . '||' . $billingCode . '||' . $buildingCode;
            $groupedBuildings[$key] = $row;
        }

        foreach ($groupedBuildings as $key => $row) {
            $stats['contract_buildings_processed']++;
            [$contractNo, $billingCode, $buildingCode] = explode('||', $key, 3);

            $billingGroupKey = $contractNo . '||' . $billingCode;
            $billingGroupId = $billingGroupMap[$billingGroupKey] ?? ($resolvedBillingGroups[$billingGroupKey]['target_id'] ?? null);
            $buildingId = $buildingMap[$buildingCode] ?? null;

            if ((!$billingGroupId && !$resolvedBillingGroups[$billingGroupKey]) || !$buildingId) {
                $stats['contract_buildings_failed']++;
                continue;
            }

            if (!$apply) {
                $stats['contract_buildings_inserted']++;
                continue;
            }

            $exists = Schema::hasTable('billing_group_buildings')
                ? DB::table('billing_group_buildings')
                    ->where('billing_group_id', $billingGroupId)
                    ->where('building_id', $buildingId)
                    ->exists()
                : false;

            if (!$exists && Schema::hasTable('billing_group_buildings')) {
                DB::table('billing_group_buildings')->insert($this->onlyColumns([
                    'billing_group_id' => $billingGroupId,
                    'building_id' => $buildingId,
                    'billing_amount' => (float) ($row['amountforex'] ?? 0),
                    'is_active' => true,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $billingGroupBuildingColumns));
            }

            $legacyExists = Schema::hasTable('contract_buildings')
                ? DB::table('contract_buildings')
                    ->where('billing_id', $billingGroupId)
                    ->where('building_id', $buildingId)
                    ->exists()
                : false;

            if (!$legacyExists && Schema::hasTable('contract_buildings')) {
                DB::table('contract_buildings')->insert($this->onlyColumns([
                    'billing_id' => $billingGroupId,
                    'building_id' => $buildingId,
                ], $legacyContractBuildingColumns));
            }

            $stats[$exists || $legacyExists ? 'contract_buildings_updated' : 'contract_buildings_inserted']++;
        }

        $this->table(
            ['Metric', 'Value'],
            [
                ['Mode', $apply ? 'apply' : 'dry-run'],
                ['Contracts Processed', $stats['contracts_processed']],
                ['Contracts Inserted', $stats['contracts_inserted']],
                ['Contracts Updated', $stats['contracts_updated']],
                ['Contracts Skipped', $stats['contracts_skipped']],
                ['Contracts Failed', $stats['contracts_failed']],
                ['Billing Groups Processed', $stats['billing_groups_processed']],
                ['Billing Groups Inserted', $stats['billing_groups_inserted']],
                ['Billing Groups Updated', $stats['billing_groups_updated']],
                ['Billing Groups Skipped', $stats['billing_groups_skipped']],
                ['Billing Groups Failed', $stats['billing_groups_failed']],
                ['Contract Buildings Processed', $stats['contract_buildings_processed']],
                ['Contract Buildings Inserted', $stats['contract_buildings_inserted']],
                ['Contract Buildings Updated', $stats['contract_buildings_updated']],
                ['Contract Buildings Skipped', $stats['contract_buildings_skipped']],
                ['Contract Buildings Failed', $stats['contract_buildings_failed']],
            ]
        );

        $this->warn('Importer ini fokus pada histori contract + billing group dari source PinkAds. Contract rooms/rentals bisa dilanjutkan setelah contract header-nya aman masuk.');

        return self::SUCCESS;
    }

    /**
     * @return array<string, int>
     */
    private function mappedIds(string $sourceTable, string $targetTable): array
    {
        return DB::table('source_import_maps')
            ->where('source_system', 'catalyst')
            ->where('source_table', $sourceTable)
            ->where('target_table', $targetTable)
            ->pluck('target_id', 'source_key')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function actorId(): ?int
    {
        return DB::table('users')->orderBy('id')->value('id');
    }

    private function toDate($value): ?string
    {
        $value = $this->cleanString($value);
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeContractType($value): string
    {
        $value = Str::lower($this->cleanString($value) ?? 'new');

        return match ($value) {
            'renewal' => 'renewal',
            'switching' => 'switching',
            'extend', 'extension' => 'extend',
            default => 'new',
        };
    }

    private function normalizePaymentTerms($value): string
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

        if (preg_match('/30|1\s*bulan|monthly/', $term)) {
            return 'credit_30';
        }

        if (preg_match('/14h|advance|1x|2x|3x|cash|cod|0/', $term)) {
            return 'cash';
        }

        return 'cash';
    }

    private function resolveBillingGroupTaxPayload(?int $customerId, ?string $npwpNumber, array $legacyBillingGroup = []): array
    {
        $taxRow = null;

        if ($customerId) {
            $query = DB::table('customer_tax_settings')->where('customer_id', $customerId);

            if ($npwpNumber && Schema::hasColumn('customer_tax_settings', 'tax_number')) {
                $query->where('tax_number', $npwpNumber);
            }

            $taxRow = $query->orderByDesc('id')->first();
        }

        $taxNumber = $this->cleanString($taxRow->tax_number ?? null) ?: $this->cleanString($legacyBillingGroup['custtaxnpwp'] ?? null) ?: $npwpNumber;
        $taxAddress = $this->cleanString($taxRow->address ?? null) ?: $this->cleanString($legacyBillingGroup['address'] ?? null);

        return array_filter([
            'tax_type' => $taxNumber ? 'npwp' : null,
            'tax_number' => $taxNumber,
            'npwp' => $taxRow->npwp ?? $taxNumber,
            'npwp_number' => $taxRow->npwp_number ?? $taxNumber,
            'npwp_address' => $taxRow->npwp_address ?? $taxAddress,
            'tax_address' => $taxAddress,
            'nitku' => $taxRow->nitku ?? null,
            'nik' => $taxRow->nik ?? null,
            'ppn_code' => $taxRow->ppn_code ?? null,
        ], fn ($value) => !($value === '' || $value === [] || $value === null));
    }

    private function onlyColumns(array $payload, array $columns): array
    {
        return array_intersect_key($payload, $columns);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readCsv(string $file): array
    {
        $handle = fopen($file, 'rb');
        if ($handle === false) {
            return [];
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return [];
        }

        $header = array_map(fn ($value) => $this->normalizeHeader($value), $header);
        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            $row = [];
            foreach ($header as $index => $column) {
                $row[$column] = $data[$index] ?? null;
            }
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function normalizeHeader($value): string
    {
        $value = trim((string) $value);
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);

        return Str::lower(trim((string) $value, "\" \t\n\r\0\x0B"));
    }

    private function resolveFilePath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return base_path('storage/app/catalyst/contracts/contract_headers_export.csv');
        }

        if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1 || str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        }

        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, base_path($path));
    }

    private function cleanString($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || in_array(Str::lower($value), ['null', '\\n'], true)) {
            return null;
        }

        return $value;
    }

    private function normalizePhone($value): ?string
    {
        $value = $this->cleanString($value);
        if ($value === null || Str::contains($value, '@')) {
            return null;
        }

        $value = preg_replace('/[^0-9+\-\s()\/]/', ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);
        $value = trim((string) $value);

        return $value === '' ? null : Str::limit($value, 20, '');
    }
}
