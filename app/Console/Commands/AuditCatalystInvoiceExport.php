<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuditCatalystInvoiceExport extends Command
{
    protected $signature = 'catalyst:audit-invoices-export
                            {--headers-file=storage/app/catalyst/invoices/invoice_headers_export.csv : CSV export file for FINCustInvHd}
                            {--details-file=storage/app/catalyst/invoices/invoice_details_export.csv : CSV export file for FINCustInvDt}
                            {--dp-file=storage/app/catalyst/invoices/invoice_dp_export.csv : CSV export file for FINCustInvDP}';

    protected $description = 'Audit readiness of Catalyst invoice exports against imported customer, contract, and billing group mappings.';

    public function handle(): int
    {
        $headersFile = $this->resolveFilePath((string) $this->option('headers-file'));
        $detailsFile = $this->resolveFilePath((string) $this->option('details-file'));
        $dpFile = $this->resolveFilePath((string) $this->option('dp-file'));

        foreach ([$headersFile, $detailsFile, $dpFile] as $file) {
            if (!is_file($file)) {
                $this->error('File export tidak ditemukan: ' . $file);
                return self::FAILURE;
            }
        }

        $headers = collect($this->readCsv($headersFile));
        $details = collect($this->readCsv($detailsFile));
        $dpRows = collect($this->readCsv($dpFile));

        $customerMap = DB::table('source_import_maps')
            ->where('source_system', 'catalyst')
            ->where('source_table', 'MsCustomer')
            ->where('target_table', 'customers')
            ->pluck('target_id', 'source_key')
            ->map(fn ($id) => (int) $id)
            ->all();

        $contractMap = DB::table('source_import_maps')
            ->where('source_system', 'catalyst')
            ->where('source_table', 'MKTContractHd')
            ->where('target_table', 'contracts')
            ->pluck('target_id', 'source_key')
            ->map(fn ($id) => (int) $id)
            ->all();

        $billingGroupMap = DB::table('source_import_maps')
            ->where('source_system', 'catalyst')
            ->where('source_table', 'MKTContractDt_billing')
            ->where('target_table', 'billing_groups')
            ->pluck('target_id', 'source_key')
            ->map(fn ($id) => (int) $id)
            ->all();

        $targetContracts = DB::table('contracts')
            ->select('id', 'contract_number')
            ->get()
            ->keyBy('contract_number');

        $detailsByInvoice = $details->groupBy(fn (array $row) => $this->cleanString($row['transnmbr'] ?? null));
        $dpByInvoice = $dpRows->groupBy(fn (array $row) => $this->cleanString($row['transnmbr'] ?? null));

        $statusCounts = [];
        $summary = [
            'headers' => $headers->count(),
            'details' => $details->count(),
            'dp_rows' => $dpRows->count(),
            'mapped_customers' => 0,
            'missing_customers' => 0,
            'resolved_contracts' => 0,
            'ambiguous_contracts' => 0,
            'missing_contracts' => 0,
            'mapped_billing_groups' => 0,
            'missing_billing_groups' => 0,
            'headers_with_dp' => 0,
        ];

        $missingCustomerSamples = [];
        $missingContractSamples = [];
        $missingBillingSamples = [];

        foreach ($headers as $header) {
            $invoiceNo = $this->cleanString($header['transnmbr'] ?? null);
            if (!$invoiceNo) {
                continue;
            }

            $status = $this->cleanString($header['status'] ?? null) ?? '(blank)';
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;

            $customerCode = $this->cleanString($header['customer'] ?? null);
            if ($customerCode && isset($customerMap[$customerCode])) {
                $summary['mapped_customers']++;
            } else {
                $summary['missing_customers']++;
                if (count($missingCustomerSamples) < 10) {
                    $missingCustomerSamples[] = [
                        'invoice' => $invoiceNo,
                        'customer' => $customerCode ?: '(blank)',
                    ];
                }
            }

            $detailRows = $detailsByInvoice->get($invoiceNo, collect());
            $contractNos = $detailRows
                ->pluck('sono')
                ->map(fn ($value) => $this->cleanString($value))
                ->filter()
                ->unique()
                ->values();

            if ($contractNos->count() === 1 && isset($contractMap[$contractNos->first()])) {
                $summary['resolved_contracts']++;
            } elseif ($contractNos->count() > 1) {
                $summary['ambiguous_contracts']++;
                if (count($missingContractSamples) < 10) {
                    $missingContractSamples[] = [
                        'invoice' => $invoiceNo,
                        'contracts' => $contractNos->implode(', '),
                    ];
                }
            } else {
                $summary['missing_contracts']++;
                if (count($missingContractSamples) < 10) {
                    $missingContractSamples[] = [
                        'invoice' => $invoiceNo,
                        'contracts' => $contractNos->implode(', ') ?: '(blank)',
                    ];
                }
            }

            $billingKeys = $detailRows
                ->map(function (array $row) use ($contractNos) {
                    $contractNo = $this->cleanString($row['sono'] ?? null) ?: ($contractNos->count() === 1 ? $contractNos->first() : null);
                    $billingCode = $this->cleanString($row['billinggroup'] ?? null);

                    return $contractNo && $billingCode ? $contractNo . '||' . $billingCode : null;
                })
                ->filter()
                ->unique()
                ->values();

            if ($billingKeys->isEmpty() && $contractNos->count() === 1) {
                $headerBilling = $this->cleanString($header['billinggroup'] ?? null);
                if ($headerBilling) {
                    $billingKeys = collect([$contractNos->first() . '||' . $headerBilling]);
                }
            }

            if ($billingKeys->isEmpty() && $contractNos->count() === 1) {
                $contractNumber = $contractNos->first();
                $targetContractId = $targetContracts[$contractNumber]->id ?? null;

                if ($targetContractId) {
                    $groupNames = DB::table('billing_groups')
                        ->where('contract_id', $targetContractId)
                        ->pluck('billing_group_name')
                        ->filter()
                        ->unique()
                        ->values();

                    if ($groupNames->count() === 1) {
                        $billingKeys = collect([$contractNumber . '||' . $groupNames->first()]);
                    }
                }
            }

            if ($billingKeys->isEmpty()) {
                $summary['missing_billing_groups']++;
                if (count($missingBillingSamples) < 10) {
                    $missingBillingSamples[] = [
                        'invoice' => $invoiceNo,
                        'billing_key' => '(blank)',
                    ];
                }
            } elseif ($billingKeys->every(fn ($key) => isset($billingGroupMap[$key]))) {
                $summary['mapped_billing_groups']++;
            } else {
                $summary['missing_billing_groups']++;
                if (count($missingBillingSamples) < 10) {
                    $missingBillingSamples[] = [
                        'invoice' => $invoiceNo,
                        'billing_key' => $billingKeys->implode(', '),
                    ];
                }
            }

            if ($dpByInvoice->has($invoiceNo)) {
                $summary['headers_with_dp']++;
            }
        }

        $this->table(
            ['Metric', 'Value'],
            [
                ['Invoice Headers', $summary['headers']],
                ['Invoice Details', $summary['details']],
                ['Invoice DP Rows', $summary['dp_rows']],
                ['Headers With Mapped Customer', $summary['mapped_customers']],
                ['Headers Missing Customer', $summary['missing_customers']],
                ['Headers With Resolved Contract', $summary['resolved_contracts']],
                ['Headers With Ambiguous Contract', $summary['ambiguous_contracts']],
                ['Headers Missing Contract', $summary['missing_contracts']],
                ['Headers With Mapped Billing Group', $summary['mapped_billing_groups']],
                ['Headers Missing Billing Group', $summary['missing_billing_groups']],
                ['Headers With DP Rows', $summary['headers_with_dp']],
            ]
        );

        $statusRows = collect($statusCounts)
            ->sortKeys()
            ->map(fn ($count, $status) => ['Status' => $status, 'Count' => $count])
            ->values()
            ->all();

        if ($statusRows !== []) {
            $this->newLine();
            $this->table(['Status', 'Count'], $statusRows);
        }

        $this->dumpSamples('Missing Customer Samples', $missingCustomerSamples, ['invoice', 'customer']);
        $this->dumpSamples('Missing/Ambiguous Contract Samples', $missingContractSamples, ['invoice', 'contracts']);
        $this->dumpSamples('Missing Billing Group Samples', $missingBillingSamples, ['invoice', 'billing_key']);

        $this->warn('Audit ini hanya cek readiness mapping source PinkAds -> target. Belum menulis invoice ke sistem.');

        return self::SUCCESS;
    }

    private function dumpSamples(string $title, array $rows, array $columns): void
    {
        if ($rows === []) {
            return;
        }

        $this->newLine();
        $this->line($title);
        $this->table($columns, $rows);
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
            return base_path('storage/app/catalyst/invoices/invoice_headers_export.csv');
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
}
