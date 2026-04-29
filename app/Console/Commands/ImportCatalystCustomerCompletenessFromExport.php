<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ImportCatalystCustomerCompletenessFromExport extends Command
{
    protected $signature = 'catalyst:import-customer-completeness-export
                            {--contacts-file=storage/app/catalyst/customer_contacts_export.csv : CSV export file for MsCustContact}
                            {--addresses-file=storage/app/catalyst/customer_addresses_export.csv : CSV export file for MsCustAddress}
                            {--apply : Persist changes to target tables}';

    protected $description = 'Import Catalyst customer contacts and address completeness from CSV export without relying on PHP sqlsrv connectivity';

    private array $cityLookup = [];
    private array $postalLookup = [];

    public function handle(): int
    {
        $contactsFile = $this->resolveFilePath((string) $this->option('contacts-file'));
        $addressesFile = $this->resolveFilePath((string) $this->option('addresses-file'));
        $apply = (bool) $this->option('apply');
        $mode = $apply ? 'apply' : 'dry-run';

        if (!is_file($contactsFile)) {
            $this->error('File export contact tidak ditemukan: ' . $contactsFile);
            return self::FAILURE;
        }

        if (!is_file($addressesFile)) {
            $this->error('File export address tidak ditemukan: ' . $addressesFile);
            return self::FAILURE;
        }

        $this->info('Running Catalyst customer completeness import from export in ' . $mode . ' mode.');

        $customerMap = DB::table('source_import_maps')
            ->where('source_system', 'catalyst')
            ->where('source_table', 'MsCustomer')
            ->where('target_table', 'customers')
            ->pluck('target_id', 'source_key')
            ->map(fn ($id) => (int) $id)
            ->all();

        $customerColumns = array_flip(Schema::getColumnListing('customers'));
        $contactColumns = array_flip(Schema::getColumnListing('customer_contacts'));
        $pivotColumns = Schema::hasTable('customer_customer_contact')
            ? array_flip(Schema::getColumnListing('customer_customer_contact'))
            : [];

        $contactStats = [
            'processed' => 0,
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        foreach ($this->readCsv($contactsFile) as $row) {
            $contactStats['processed']++;

            $customerKey = $this->cleanString($row['custcode'] ?? null);
            $customerId = $customerKey ? ($customerMap[$customerKey] ?? null) : null;
            $itemNo = (int) ($row['itemno'] ?? 0);
            $name = $this->cleanString($row['contactname'] ?? null);
            $position = $this->cleanString($row['contacttitle'] ?? null) ?: $this->cleanString($row['contactcategory'] ?? null);
            $email = $this->normalizeEmail($row['email'] ?? null);
            $phone = $this->normalizePhone($row['handphone'] ?? null) ?: $this->normalizePhone($row['phone'] ?? null);

            if (!$customerId) {
                $contactStats['skipped']++;
                continue;
            }

            if (!$name && !$email && !$phone) {
                $contactStats['skipped']++;
                continue;
            }

            $name = $name ?: 'Primary Contact';
            $sourceKey = $this->makeKey([$customerKey, $itemNo ?: null, $name]);

            $existingMap = DB::table('source_import_maps')
                ->where('source_system', 'catalyst')
                ->where('source_table', 'MsCustContact')
                ->where('source_key', $sourceKey)
                ->where('target_table', 'customer_contacts')
                ->first();

            $existing = null;
            if ($existingMap?->target_id) {
                $existing = DB::table('customer_contacts')->where('id', $existingMap->target_id)->first();
            }

            if (!$existing) {
                $existing = DB::table('customer_contacts')
                    ->where('customer_id', $customerId)
                    ->where('name', $name)
                    ->first();
            }

            $payload = $this->onlyColumns([
                'customer_id' => $customerId,
                'salutation' => $this->cleanString($row['contacttype'] ?? null),
                'position' => $position,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'is_active' => $this->yesNoToBool($row['fgactive'] ?? null, true),
                'updated_at' => now(),
                'updated_by' => auth()->id(),
            ], $contactColumns);

            if (!$apply) {
                $contactStats[$existing ? 'updated' : 'inserted']++;
                continue;
            }

            if ($existing) {
                DB::table('customer_contacts')->where('id', $existing->id)->update($payload);
                $targetId = (int) $existing->id;
                $contactStats['updated']++;
            } else {
                $targetId = (int) DB::table('customer_contacts')->insertGetId($this->onlyColumns($payload + [
                    'created_at' => now(),
                    'created_by' => auth()->id(),
                ], $contactColumns));
                $contactStats['inserted']++;
            }

            DB::table('source_import_maps')->updateOrInsert(
                [
                    'source_system' => 'catalyst',
                    'source_table' => 'MsCustContact',
                    'source_key' => $sourceKey,
                    'target_table' => 'customer_contacts',
                ],
                [
                    'target_id' => $targetId,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            if (Schema::hasTable('customer_customer_contact')) {
                DB::table('customer_customer_contact')->updateOrInsert(
                    [
                        'customer_id' => $customerId,
                        'customer_contact_id' => $targetId,
                    ],
                    $this->onlyColumns([
                        'is_primary' => $itemNo === 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ], $pivotColumns)
                );
            }

            if ($itemNo === 1 && Schema::hasColumn('customers', 'assigned_to')) {
                DB::table('customers')->where('id', $customerId)->update($this->onlyColumns([
                    'assigned_to' => $targetId,
                    'updated_at' => now(),
                ], $customerColumns));
            }
        }

        $addressStats = [
            'processed' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        $this->buildLocationLookups();

        foreach ($this->readCsv($addressesFile) as $row) {
            $addressStats['processed']++;

            $customerKey = $this->cleanString($row['custcode'] ?? null);
            $customerId = $customerKey ? ($customerMap[$customerKey] ?? null) : null;
            if (!$customerId) {
                $addressStats['skipped']++;
                continue;
            }

            $customer = DB::table('customers')->where('id', $customerId)->first();
            if (!$customer) {
                $addressStats['skipped']++;
                continue;
            }

            $postalCode = $this->cleanString($row['zipcode'] ?? null) ?: $this->cleanString($customer->postal_code ?? null);
            $city = $this->cleanString($row['cityname'] ?? null) ?: $this->cleanString($customer->city ?? null);
            $provinceId = $customer->province_id ?: $this->resolveTargetProvinceIdByCityName($city);
            $location = $this->resolveAdministrativeAreaByPostalCode($postalCode, $city, $provinceId);

            $payload = array_filter($this->onlyColumns([
                'address' => $this->cleanString($customer->address ?? null)
                    ?: $this->combineLines($row['deliveryaddr1'] ?? null, $row['deliveryaddr2'] ?? null),
                'phone' => $this->normalizePhone($customer->phone ?? null) ?: $this->normalizePhone($row['phoneno'] ?? null),
                'city' => $city,
                'postal_code' => $postalCode,
                'province_id' => $provinceId ?: ($location['province_id'] ?? null),
                'district_id' => $location['district_id'] ?? null,
                'subdistrict_id' => $location['subdistrict_id'] ?? null,
                'updated_at' => now(),
            ], $customerColumns), fn ($value) => $value !== null && $value !== '');

            if (count($payload) <= 1) {
                $addressStats['skipped']++;
                continue;
            }

            if (!$apply) {
                $addressStats['updated']++;
                continue;
            }

            DB::table('customers')->where('id', $customerId)->update($payload);
            $addressStats['updated']++;
        }

        $this->table(
            ['Metric', 'Value'],
            [
                ['Mode', $mode],
                ['Contacts Processed', $contactStats['processed']],
                ['Contacts Inserted', $contactStats['inserted']],
                ['Contacts Updated', $contactStats['updated']],
                ['Contacts Skipped', $contactStats['skipped']],
                ['Address Rows Processed', $addressStats['processed']],
                ['Address Rows Updated', $addressStats['updated']],
                ['Address Rows Skipped', $addressStats['skipped']],
                ['Failed', $contactStats['failed'] + $addressStats['failed']],
            ]
        );

        $this->warn('Default bank payment dari PaymentTo belum saya map otomatis karena relasi source-target masih ambigu.');

        return self::SUCCESS;
    }

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

    private function buildLocationLookups(): void
    {
        $this->cityLookup = DB::table('cities')
            ->select('id', 'name', 'province_id')
            ->get()
            ->mapWithKeys(fn ($row) => [$this->normalizePlace($row->name) => [
                'id' => (int) $row->id,
                'province_id' => $row->province_id ? (int) $row->province_id : null,
            ]])
            ->all();

        $this->postalLookup = [];
        if (!Schema::hasTable('subdistricts') || !Schema::hasTable('districts') || !Schema::hasTable('cities')) {
            return;
        }

        $rows = DB::table('subdistricts')
            ->join('districts', 'districts.id', '=', 'subdistricts.district_id')
            ->join('cities', 'cities.id', '=', 'districts.city_id')
            ->select(
                'subdistricts.postal_code',
                'subdistricts.id as subdistrict_id',
                'districts.id as district_id',
                'cities.id as city_id',
                'cities.name as city_name',
                'cities.province_id'
            )
            ->whereNotNull('subdistricts.postal_code')
            ->get();

        foreach ($rows as $row) {
            $postal = trim((string) $row->postal_code);
            if ($postal === '') {
                continue;
            }
            $this->postalLookup[$postal][] = [
                'subdistrict_id' => (int) $row->subdistrict_id,
                'district_id' => (int) $row->district_id,
                'city_id' => (int) $row->city_id,
                'city_name' => (string) $row->city_name,
                'province_id' => $row->province_id ? (int) $row->province_id : null,
            ];
        }
    }

    private function resolveTargetProvinceIdByCityName(?string $cityName): ?int
    {
        $key = $this->normalizePlace($cityName);
        return $this->cityLookup[$key]['province_id'] ?? null;
    }

    private function resolveAdministrativeAreaByPostalCode(?string $postalCode, ?string $cityName = null, ?int $provinceId = null): array
    {
        $postalCode = $this->cleanString($postalCode);
        if (!$postalCode || !isset($this->postalLookup[$postalCode])) {
            return [];
        }

        $candidates = collect($this->postalLookup[$postalCode]);
        if ($provinceId) {
            $provinceFiltered = $candidates->where('province_id', $provinceId)->values();
            if ($provinceFiltered->isNotEmpty()) {
                $candidates = $provinceFiltered;
            }
        }

        $cityKey = $this->normalizePlace($cityName);
        if ($cityKey) {
            $cityFiltered = $candidates->filter(fn ($row) => $this->normalizePlace($row['city_name'] ?? null) === $cityKey)->values();
            if ($cityFiltered->count() === 1) {
                return $cityFiltered->first();
            }
            if ($cityFiltered->isNotEmpty()) {
                $candidates = $cityFiltered;
            }
        }

        return $candidates->count() === 1 ? $candidates->first() : [];
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
            return base_path('storage/app/catalyst/customer_contacts_export.csv');
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

    private function normalizeEmail($value): ?string
    {
        $value = $this->cleanString($value);
        if (!$value || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return Str::lower($value);
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

    private function yesNoToBool($value, bool $default = false): bool
    {
        $value = Str::upper(trim((string) $value));
        return match ($value) {
            'Y', 'YES', '1', 'TRUE' => true,
            'N', 'NO', '0', 'FALSE' => false,
            default => $default,
        };
    }

    private function onlyColumns(array $payload, array $columns): array
    {
        return array_intersect_key($payload, $columns);
    }

    private function makeKey($value): ?string
    {
        if (is_array($value)) {
            $parts = array_map(fn ($item) => $this->cleanString($item) ?? '', $value);
            $joined = implode('||', $parts);
            return $joined === '' ? null : $joined;
        }

        return $this->cleanString($value);
    }

    private function combineLines(...$values): ?string
    {
        $parts = collect($values)
            ->map(fn ($value) => $this->cleanString($value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $parts === [] ? null : implode(PHP_EOL, $parts);
    }

    private function normalizePlace($value): string
    {
        $value = Str::lower($this->cleanString($value) ?? '');
        $value = preg_replace('/[^a-z0-9]+/i', ' ', $value);
        return trim((string) $value);
    }
}
