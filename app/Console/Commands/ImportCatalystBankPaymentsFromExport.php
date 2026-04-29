<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ImportCatalystBankPaymentsFromExport extends Command
{
    protected $signature = 'catalyst:import-bank-payments-export
                            {--paytypes-file=storage/app/catalyst/payment_types_export.csv : CSV export file for MsPayType}
                            {--customers-file=storage/app/catalyst/customer_paymentto_export.csv : CSV export file for MsCustomer PaymentTo}
                            {--apply : Persist changes to target tables}';

    protected $description = 'Import Catalyst payment types into bank payments and sync customer default bank payment from CSV export';

    public function handle(): int
    {
        $paytypesFile = $this->resolveFilePath((string) $this->option('paytypes-file'));
        $customersFile = $this->resolveFilePath((string) $this->option('customers-file'));
        $apply = (bool) $this->option('apply');
        $mode = $apply ? 'apply' : 'dry-run';

        if (!is_file($paytypesFile)) {
            $this->error('File export payment types tidak ditemukan: ' . $paytypesFile);
            return self::FAILURE;
        }

        if (!is_file($customersFile)) {
            $this->error('File export customer payment tidak ditemukan: ' . $customersFile);
            return self::FAILURE;
        }

        $this->info('Running Catalyst bank payment import from export in ' . $mode . ' mode.');

        $bankColumns = array_flip(Schema::getColumnListing('bank_payments'));
        $customerColumns = array_flip(Schema::getColumnListing('customers'));

        $bankMap = DB::table('source_import_maps')
            ->where('source_system', 'catalyst')
            ->where('source_table', 'MsBank')
            ->where('target_table', 'banks')
            ->pluck('target_id', 'source_key')
            ->map(fn ($id) => (int) $id)
            ->all();

        $customerMap = DB::table('source_import_maps')
            ->where('source_system', 'catalyst')
            ->where('source_table', 'MsCustomer')
            ->where('target_table', 'customers')
            ->pluck('target_id', 'source_key')
            ->map(fn ($id) => (int) $id)
            ->all();

        $stats = [
            'processed' => 0,
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'customers_updated' => 0,
            'customers_skipped' => 0,
            'customers_billto_fallback' => 0,
        ];

        $sourceToTargetBankPayment = [];

        foreach ($this->readCsv($paytypesFile) as $row) {
            $stats['processed']++;

            $sourceKey = $this->cleanString($row['paycode'] ?? null);
            $bankCode = $this->cleanString($row['bank'] ?? null);
            $bankId = $bankCode ? ($bankMap[$bankCode] ?? null) : null;
            $accountNumber = $this->normalizeAccountNumber($row['norekening'] ?? null);
            $accountName = $this->cleanString($row['namarekening'] ?? null);
            $branchName = $this->cleanString($row['bankbranch'] ?? null);
            $payName = $this->cleanString($row['payname'] ?? null);

            if (!$sourceKey) {
                $stats['skipped']++;
                continue;
            }

            if (!$bankId) {
                $stats['skipped']++;
                continue;
            }

            if (!$accountNumber && !$accountName) {
                $stats['skipped']++;
                continue;
            }

            $existingMap = DB::table('source_import_maps')
                ->where('source_system', 'catalyst')
                ->where('source_table', 'MsPayType')
                ->where('source_key', $sourceKey)
                ->where('target_table', 'bank_payments')
                ->first();

            $existing = null;
            if ($existingMap?->target_id) {
                $existing = DB::table('bank_payments')->where('id', $existingMap->target_id)->first();
            }

            if (!$existing) {
                $matchQuery = DB::table('bank_payments')->where('bank_id', $bankId);
                if ($accountNumber) {
                    $matchQuery->where('account_number', $accountNumber);
                } elseif ($accountName) {
                    $matchQuery->where('account_name', $accountName);
                }
                $existing = $matchQuery->first();
            }

            $payload = $this->onlyColumns([
                'bank_id' => $bankId,
                'account_name' => $accountName ?: $payName,
                'account_number' => $accountNumber ?: $sourceKey,
                'branch_name' => $branchName ?: $payName,
                'address' => $this->cleanString($row['bankaddr'] ?? null),
                'phone' => $this->normalizePhone($row['bankphone'] ?? null),
                'fax' => $this->normalizePhone($row['bankfax'] ?? null),
                'bank_va_number' => Str::contains(Str::upper($sourceKey), 'VA') ? ($accountNumber ?: null) : null,
                'is_default_va' => Str::contains(Str::upper($sourceKey), 'VA'),
                'is_active' => $this->yesNoToBool($row['fgactive'] ?? null, true),
                'updated_at' => now(),
                'updated_by' => auth()->id(),
            ], $bankColumns);

            if (!$apply) {
                $stats[$existing ? 'updated' : 'inserted']++;
                $sourceToTargetBankPayment[$sourceKey] = (int) ($existing->id ?? 0);
                continue;
            }

            if ($existing) {
                DB::table('bank_payments')->where('id', $existing->id)->update($payload);
                $targetId = (int) $existing->id;
                $stats['updated']++;
            } else {
                $targetId = (int) DB::table('bank_payments')->insertGetId($this->onlyColumns($payload + [
                    'created_at' => now(),
                    'created_by' => auth()->id(),
                ], $bankColumns));
                $stats['inserted']++;
            }

            DB::table('source_import_maps')->updateOrInsert(
                [
                    'source_system' => 'catalyst',
                    'source_table' => 'MsPayType',
                    'source_key' => $sourceKey,
                    'target_table' => 'bank_payments',
                ],
                [
                    'target_id' => $targetId,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $sourceToTargetBankPayment[$sourceKey] = $targetId;
        }

        if (!$apply) {
            $sourceToTargetBankPayment = DB::table('source_import_maps')
                ->where('source_system', 'catalyst')
                ->where('source_table', 'MsPayType')
                ->where('target_table', 'bank_payments')
                ->pluck('target_id', 'source_key')
                ->map(fn ($id) => (int) $id)
                ->all() + $sourceToTargetBankPayment;
        }

        foreach ($this->readCsv($customersFile) as $row) {
            $customerKey = $this->cleanString($row['custcode'] ?? null);
            $paymentTo = $this->cleanString($row['resolvedpaymentto'] ?? $row['paymentto'] ?? null);
            $paymentSource = $this->cleanString($row['paymentsource'] ?? null);
            $customerId = $customerKey ? ($customerMap[$customerKey] ?? null) : null;

            if (!$customerId || !$paymentTo) {
                $stats['customers_skipped']++;
                continue;
            }

            $bankPaymentId = $sourceToTargetBankPayment[$paymentTo] ?? null;
            if (!$bankPaymentId) {
                $existingPaymentId = DB::table('source_import_maps')
                    ->where('source_system', 'catalyst')
                    ->where('source_table', 'MsPayType')
                    ->where('source_key', $paymentTo)
                    ->where('target_table', 'bank_payments')
                    ->value('target_id');
                $bankPaymentId = $existingPaymentId ? (int) $existingPaymentId : null;
            }

            if (!$bankPaymentId) {
                $stats['customers_skipped']++;
                continue;
            }

            if (!$apply) {
                $stats['customers_updated']++;
                if ($paymentSource === 'billto') {
                    $stats['customers_billto_fallback']++;
                }
                continue;
            }

            DB::table('customers')->where('id', $customerId)->update($this->onlyColumns([
                'default_bank_payment_id' => $bankPaymentId,
                'updated_at' => now(),
            ], $customerColumns));
            $stats['customers_updated']++;
            if ($paymentSource === 'billto') {
                $stats['customers_billto_fallback']++;
            }
        }

        $this->table(
            ['Metric', 'Value'],
            [
                ['Mode', $mode],
                ['Payment Types Processed', $stats['processed']],
                ['Bank Payments Inserted', $stats['inserted']],
                ['Bank Payments Updated', $stats['updated']],
                ['Bank Payments Skipped', $stats['skipped']],
                ['Customers Default Bank Updated', $stats['customers_updated']],
                ['Customers Updated via BillTo Fallback', $stats['customers_billto_fallback']],
                ['Customers Skipped', $stats['customers_skipped']],
                ['Failed', $stats['failed']],
            ]
        );

        $this->warn('Hanya PayCode yang punya bank source yang jelas dari MsPayType yang dipetakan ke default bank payment customer. Fallback bill-to hanya dipakai jika customer source tidak punya PaymentTo langsung.');

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
            return base_path('storage/app/catalyst/payment_types_export.csv');
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

    private function normalizeAccountNumber($value): ?string
    {
        $value = $this->cleanString($value);
        if (!$value) {
            return null;
        }

        $value = preg_replace('/\s+/', '', $value);
        return trim((string) $value);
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
}
