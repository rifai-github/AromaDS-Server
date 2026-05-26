<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ImportCatalystInvoicesFromExport extends Command
{
    private int $heartbeatEvery = 1000;

    protected $signature = 'catalyst:import-invoices-export
                            {--headers-file=storage/app/catalyst/invoices/invoice_headers_export.csv : CSV export file for FINCustInvHd}
                            {--details-file=storage/app/catalyst/invoices/invoice_details_export.csv : CSV export file for FINCustInvDt}
                            {--dp-file=storage/app/catalyst/invoices/invoice_dp_export.csv : CSV export file for FINCustInvDP}
                            {--only-rentals : Rebuild invoice_rental_details only, keep invoice headers/details untouched}
                            {--apply : Persist changes to target tables}';

    protected $description = 'Import Catalyst invoices from exported FINCustInvHd/FINCustInvDt/FINCustInvDP CSV files.';

    public function handle(): int
    {
        $headersFile = $this->resolveFilePath((string) $this->option('headers-file'));
        $detailsFile = $this->resolveFilePath((string) $this->option('details-file'));
        $dpFile = $this->resolveFilePath((string) $this->option('dp-file'));
        $apply = (bool) $this->option('apply');
        $onlyRentals = (bool) $this->option('only-rentals');

        foreach ([$headersFile, $detailsFile, $dpFile] as $file) {
            if (!is_file($file)) {
                $this->error('File export tidak ditemukan: ' . $file);
                return self::FAILURE;
            }
        }

        $this->info('Running Catalyst invoice import from export in ' . ($apply ? 'apply' : 'dry-run') . ' mode.');
        if ($onlyRentals) {
            $this->warn('Mode only-rentals aktif: invoice headers dan invoice details tidak akan ditulis ulang.');
        }
        $this->line(sprintf(
            'Files loaded: headers=%s details=%s dp=%s',
            basename($headersFile),
            basename($detailsFile),
            basename($dpFile)
        ));

        $headers = $this->readCsv($headersFile);
        $details = $this->readCsv($detailsFile);
        $dpRows = $this->readCsv($dpFile);

        $this->line(sprintf(
            'Source rows: headers=%d details=%d dp=%d',
            count($headers),
            count($details),
            count($dpRows)
        ));

        $invoiceColumns = array_flip(Schema::getColumnListing('invoices'));
        $invoiceDetailColumns = Schema::hasTable('invoice_details')
            ? array_flip(Schema::getColumnListing('invoice_details'))
            : [];
        $invoiceRentalColumns = Schema::hasTable('invoice_rental_details')
            ? array_flip(Schema::getColumnListing('invoice_rental_details'))
            : [];

        $customerMap = $this->mappedIds('MsCustomer', 'customers');
        $contractMap = $this->mappedIds('MKTContractHd', 'contracts');
        $billingGroupMap = $this->mappedIds('MKTContractDt_billing', 'billing_groups');

        $targetContracts = DB::table('contracts')
            ->select('id', 'contract_number')
            ->get()
            ->keyBy('contract_number');

        $detailsByInvoice = collect($details)->groupBy(fn (array $row) => $this->cleanString($row['transnmbr'] ?? null));
        $dpByInvoice = collect($dpRows)->groupBy(fn (array $row) => $this->cleanString($row['transnmbr'] ?? null));
        $rentalProductMap = $this->mappedIds('MsProduct', 'master_rentals');

        $stats = [
            'headers_processed' => 0,
            'headers_inserted' => 0,
            'headers_updated' => 0,
            'headers_skipped' => 0,
            'headers_failed' => 0,
            'details_processed' => 0,
            'details_inserted' => 0,
            'details_updated' => 0,
            'details_skipped' => 0,
            'details_failed' => 0,
            'rentals_processed' => 0,
            'rentals_inserted' => 0,
            'rentals_updated' => 0,
            'rentals_skipped' => 0,
            'rentals_failed' => 0,
        ];

        $resolvedInvoices = [];
        $actorId = $this->actorId();

        $this->info('Processing invoice headers...');
        foreach ($headers as $header) {
            $stats['headers_processed']++;

            $invoiceNo = $this->cleanString($header['transnmbr'] ?? null);
            $customerCode = $this->cleanString($header['customer'] ?? null);

            if (!$invoiceNo || !$customerCode) {
                $stats['headers_skipped']++;
                continue;
            }

            $customerId = $customerMap[$customerCode] ?? null;
            if (!$customerId) {
                $stats['headers_failed']++;
                continue;
            }

            $detailRows = $detailsByInvoice->get($invoiceNo, collect());
            $contractNumber = $this->resolveInvoiceContractNumber($detailRows, $contractMap);
            if (!$contractNumber) {
                $stats['headers_skipped']++;
                continue;
            }

            $billingGroupId = $this->resolveInvoiceBillingGroupId($header, $detailRows, $contractNumber, $billingGroupMap, $targetContracts);
            if (!$billingGroupId) {
                $stats['headers_skipped']++;
                continue;
            }

            $customer = DB::table('customers')->where('id', $customerId)->first();
            $billingGroup = DB::table('billing_groups')->where('id', $billingGroupId)->first();

            $periodInvoice = $this->resolveInvoicePeriod($detailRows);
            $invoiceDate = $this->toDate($header['transdate'] ?? null);
            $dueDate = $this->toDate($header['duedate'] ?? null);
            $status = $this->mapInvoiceStatus($header['status'] ?? null);

            $payload = $this->onlyColumns([
                'invoice_number' => $invoiceNo,
                'contract_number' => $contractNumber,
                'customer_id' => $customerId,
                'billing_group_id' => $billingGroupId,
                'company_name' => $this->cleanString($customer->name ?? null),
                'billing_address' => $this->cleanString($billingGroup->pic_address ?? null) ?: $this->cleanString($header['custtaxaddress'] ?? null) ?: $this->cleanString($customer->address ?? null),
                'period_invoice' => $periodInvoice,
                'invoice_status' => $status,
                'invoice_date' => $invoiceDate,
                'due_date' => $dueDate,
                'tax_obligation' => $this->yesNoToBool($header['fgppn'] ?? null, (float) ($header['ppnforex'] ?? 0) > 0),
                'tax_code' => $this->cleanString($billingGroup->ppn_code ?? null) ?: $this->cleanString($header['typeppn'] ?? null),
                'tax_number' => $this->cleanString($header['custtaxnpwp'] ?? null),
                'npwp_number' => $this->cleanString($header['custtaxnpwp'] ?? null),
                'tax_address' => $this->cleanString($header['custtaxaddress'] ?? null),
                'subtotal' => (float) ($header['baseforex'] ?? 0),
                'discount_amount' => (float) ($header['discforex'] ?? 0),
                'tax_amount' => (float) ($header['ppnforex'] ?? 0),
                'total_amount' => (float) ($header['totalforex'] ?? 0),
                'grand_total' => (float) ($header['totalforex'] ?? 0),
                'outstanding' => (float) ($header['totalforex'] ?? 0),
                'additional_notes' => $this->cleanString($header['remark'] ?? null),
                'payment_method' => !blank($header['virtualaccount'] ?? null) ? 'virtual_account' : 'bank_transfer',
                'virtual_account_number' => $this->cleanString($header['virtualaccount'] ?? null) ?: $this->cleanString($billingGroup->virtual_account_number ?? null),
                'faktur_pajak' => $this->cleanString($header['ppnno'] ?? null),
                'created_by' => $actorId,
                'updated_by' => $actorId,
                'updated_at' => now(),
            ], $invoiceColumns);

            $existing = DB::table('invoices')->where('invoice_number', $invoiceNo)->first();

            if (!$apply || $onlyRentals) {
                $stats[$existing ? 'headers_updated' : 'headers_inserted']++;
                $resolvedInvoices[$invoiceNo] = [
                    'invoice_id' => $existing?->id ?: (int) (DB::table('source_import_maps')
                        ->where('source_system', 'catalyst')
                        ->where('source_table', 'FINCustInvHd')
                        ->where('source_key', $invoiceNo)
                        ->where('target_table', 'invoices')
                        ->value('target_id') ?: -1),
                    'contract_number' => $contractNumber,
                ];
                continue;
            }

            if ($existing) {
                DB::table('invoices')->where('id', $existing->id)->update($payload);
                $invoiceId = (int) $existing->id;
                $stats['headers_updated']++;
            } else {
                $invoiceId = (int) DB::table('invoices')->insertGetId($this->onlyColumns($payload + [
                    'created_at' => now(),
                ], $invoiceColumns));
                $stats['headers_inserted']++;
            }

            DB::table('source_import_maps')->updateOrInsert(
                [
                    'source_system' => 'catalyst',
                    'source_table' => 'FINCustInvHd',
                    'source_key' => $invoiceNo,
                    'target_table' => 'invoices',
                ],
                [
                    'target_id' => $invoiceId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $resolvedInvoices[$invoiceNo] = [
                'invoice_id' => $invoiceId,
                'contract_number' => $contractNumber,
            ];

            $this->heartbeat('headers', $stats['headers_processed'], count($headers), [
                'inserted' => $stats['headers_inserted'],
                'updated' => $stats['headers_updated'],
                'skipped' => $stats['headers_skipped'],
                'failed' => $stats['headers_failed'],
            ]);
        }

        $this->heartbeat('headers', $stats['headers_processed'], count($headers), [
            'inserted' => $stats['headers_inserted'],
            'updated' => $stats['headers_updated'],
            'skipped' => $stats['headers_skipped'],
            'failed' => $stats['headers_failed'],
        ], true);

        $this->info('Processing invoice details...');
        foreach ($details as $index => $detail) {
            $stats['details_processed']++;

            $invoiceNo = $this->cleanString($detail['transnmbr'] ?? null);
            if (!$invoiceNo) {
                $stats['details_skipped']++;
                continue;
            }

            $invoiceId = $resolvedInvoices[$invoiceNo]['invoice_id'] ?? DB::table('source_import_maps')
                ->where('source_system', 'catalyst')
                ->where('source_table', 'FINCustInvHd')
                ->where('source_key', $invoiceNo)
                ->where('target_table', 'invoices')
                ->value('target_id');

            if (!$invoiceId && !isset($resolvedInvoices[$invoiceNo])) {
                $stats['details_skipped']++;
                continue;
            }

            $sourceKey = $invoiceNo . '||' . $index;
            $existingMap = DB::table('source_import_maps')
                ->where('source_system', 'catalyst')
                ->where('source_table', 'FINCustInvDt')
                ->where('source_key', $sourceKey)
                ->where('target_table', 'invoice_details')
                ->first();

            $existing = null;
            if ($existingMap?->target_id) {
                $existing = DB::table('invoice_details')->where('id', $existingMap->target_id)->first();
            }

            $quantity = $this->resolveDecimal($detail['qty'] ?? null, 1);
            if ($quantity <= 0) {
                $quantity = 1;
            }

            $unitPrice = $this->resolveDecimal($detail['priceforex'] ?? null);
            $lineTotal = $this->resolveDecimal($detail['nettoforex'] ?? null);
            if ($lineTotal <= 0) {
                $lineTotal = $this->resolveDecimal($detail['amountforex'] ?? null);
            }
            if ($unitPrice <= 0 && $lineTotal > 0 && $quantity > 0) {
                $unitPrice = round($lineTotal / $quantity, 2);
            }
            if ($lineTotal <= 0) {
                $lineTotal = round($quantity * $unitPrice, 2);
            }

            $payload = $this->onlyColumns([
                'invoice_id' => $invoiceId,
                'description' => $this->buildInvoiceDetailLabel($detail),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $lineTotal,
                'created_by' => $actorId,
                'updated_by' => $actorId,
                'updated_at' => now(),
            ], $invoiceDetailColumns);

            if (!$apply || $onlyRentals) {
                $stats[$existing ? 'details_updated' : 'details_inserted']++;
                continue;
            }

            if ($existing) {
                DB::table('invoice_details')->where('id', $existing->id)->update($payload);
                $detailId = (int) $existing->id;
                $stats['details_updated']++;
            } else {
                $detailId = (int) DB::table('invoice_details')->insertGetId($this->onlyColumns($payload + [
                    'created_at' => now(),
                ], $invoiceDetailColumns));
                $stats['details_inserted']++;
            }

            DB::table('source_import_maps')->updateOrInsert(
                [
                    'source_system' => 'catalyst',
                    'source_table' => 'FINCustInvDt',
                    'source_key' => $sourceKey,
                    'target_table' => 'invoice_details',
                ],
                [
                    'target_id' => $detailId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $this->heartbeat('details', $stats['details_processed'], count($details), [
                'inserted' => $stats['details_inserted'],
                'updated' => $stats['details_updated'],
                'skipped' => $stats['details_skipped'],
                'failed' => $stats['details_failed'],
            ]);
        }

        $this->heartbeat('details', $stats['details_processed'], count($details), [
            'inserted' => $stats['details_inserted'],
            'updated' => $stats['details_updated'],
            'skipped' => $stats['details_skipped'],
            'failed' => $stats['details_failed'],
        ], true);

        if (!empty($invoiceRentalColumns)) {
            $this->info('Processing invoice rentals from invoice detail rows...');
            $totalRentalRows = count($details);

            if ($apply) {
                foreach ($resolvedInvoices as $invoiceNo => $meta) {
                    $invoiceId = (int) ($meta['invoice_id'] ?? 0);
                    if ($invoiceId <= 0) {
                        continue;
                    }

                    DB::table('invoice_rental_details')->where('invoice_id', $invoiceId)->delete();
                    DB::table('source_import_maps')
                        ->where('source_system', 'catalyst')
                        ->where('target_table', 'invoice_rental_details')
                        ->where(function ($query) use ($invoiceNo) {
                            $query->where('source_key', 'like', $invoiceNo . '||%');
                        })
                        ->delete();
                }
            }

            foreach ($details as $index => $detail) {
                $stats['rentals_processed']++;

                $invoiceNo = $this->cleanString($detail['transnmbr'] ?? null);
                $productCode = $this->cleanString($detail['product'] ?? null);

                if (!$invoiceNo || !$productCode) {
                    $stats['rentals_skipped']++;
                    $this->heartbeat('rentals', $stats['rentals_processed'], $totalRentalRows, [
                        'inserted' => $stats['rentals_inserted'],
                        'updated' => $stats['rentals_updated'],
                        'skipped' => $stats['rentals_skipped'],
                        'failed' => $stats['rentals_failed'],
                    ]);
                    continue;
                }

                $invoiceId = $resolvedInvoices[$invoiceNo]['invoice_id'] ?? DB::table('source_import_maps')
                    ->where('source_system', 'catalyst')
                    ->where('source_table', 'FINCustInvHd')
                    ->where('source_key', $invoiceNo)
                    ->where('target_table', 'invoices')
                    ->value('target_id');

                if (!$invoiceId) {
                    $stats['rentals_skipped']++;
                    $this->heartbeat('rentals', $stats['rentals_processed'], $totalRentalRows, [
                        'inserted' => $stats['rentals_inserted'],
                        'updated' => $stats['rentals_updated'],
                        'skipped' => $stats['rentals_skipped'],
                        'failed' => $stats['rentals_failed'],
                    ]);
                    continue;
                }

                $masterRentalId = $rentalProductMap[$productCode] ?? null;
                if (!$masterRentalId) {
                    $stats['rentals_skipped']++;
                    $this->heartbeat('rentals', $stats['rentals_processed'], $totalRentalRows, [
                        'inserted' => $stats['rentals_inserted'],
                        'updated' => $stats['rentals_updated'],
                        'skipped' => $stats['rentals_skipped'],
                        'failed' => $stats['rentals_failed'],
                    ]);
                    continue;
                }

                $quantity = $this->resolveDecimal($detail['qty'] ?? null, 1);
                if ($quantity <= 0) {
                    $quantity = 1;
                }

                $unitPrice = $this->resolveDecimal($detail['priceforex'] ?? null);
                $lineTotal = $this->resolveDecimal($detail['nettoforex'] ?? null);
                if ($lineTotal <= 0) {
                    $lineTotal = $this->resolveDecimal($detail['amountforex'] ?? null);
                }
                if ($unitPrice <= 0 && $lineTotal > 0 && $quantity > 0) {
                    $unitPrice = round($lineTotal / $quantity, 2);
                }
                if ($lineTotal <= 0) {
                    $lineTotal = round($quantity * $unitPrice, 2);
                }

                $buildingName = $this->cleanString($detail['building'] ?? null);
                $roomName = $this->cleanString($detail['room'] ?? null)
                    ?: $this->cleanString($detail['location'] ?? null)
                    ?: $buildingName
                    ?: $this->cleanString($detail['basno'] ?? null)
                    ?: $productCode
                    ?: 'General';
                $rentalName = $productCode ?: $roomName ?: 'General';

                $sourceKey = $invoiceNo . '||' . $index;
                $existingMap = DB::table('source_import_maps')
                    ->where('source_system', 'catalyst')
                    ->where('source_table', 'FINCustInvDt_rental')
                    ->where('source_key', $sourceKey)
                    ->where('target_table', 'invoice_rental_details')
                    ->first();

                $existing = null;
                if ($existingMap?->target_id) {
                    $existing = DB::table('invoice_rental_details')->where('id', $existingMap->target_id)->first();
                }

                $payload = $this->onlyColumns([
                    'invoice_id' => $invoiceId,
                    'master_rental_id' => $masterRentalId,
                    'job_no' => $this->cleanString($detail['csrno'] ?? null) ?: $this->cleanString($detail['basno'] ?? null),
                    'building_name' => $buildingName ?: $roomName,
                    'rental_name' => $rentalName,
                    'room_name' => $roomName,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $lineTotal,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                    'updated_at' => now(),
                ], $invoiceRentalColumns);

                if (!$apply) {
                    $stats[$existing ? 'rentals_updated' : 'rentals_inserted']++;
                    $this->heartbeat('rentals', $stats['rentals_processed'], $totalRentalRows, [
                        'inserted' => $stats['rentals_inserted'],
                        'updated' => $stats['rentals_updated'],
                        'skipped' => $stats['rentals_skipped'],
                        'failed' => $stats['rentals_failed'],
                    ]);
                    continue;
                }

                if ($existing) {
                    DB::table('invoice_rental_details')->where('id', $existing->id)->update($payload);
                    $rentalDetailId = (int) $existing->id;
                    $stats['rentals_updated']++;
                } else {
                    $rentalDetailId = (int) DB::table('invoice_rental_details')->insertGetId($this->onlyColumns($payload + [
                        'created_at' => now(),
                    ], $invoiceRentalColumns));
                    $stats['rentals_inserted']++;
                }

                DB::table('source_import_maps')->updateOrInsert(
                    [
                        'source_system' => 'catalyst',
                        'source_table' => 'FINCustInvDt_rental',
                        'source_key' => $sourceKey,
                        'target_table' => 'invoice_rental_details',
                    ],
                    [
                        'target_id' => $rentalDetailId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                $this->heartbeat('rentals', $stats['rentals_processed'], $totalRentalRows, [
                    'inserted' => $stats['rentals_inserted'],
                    'updated' => $stats['rentals_updated'],
                    'skipped' => $stats['rentals_skipped'],
                    'failed' => $stats['rentals_failed'],
                ]);
            }

            $this->heartbeat('rentals', $stats['rentals_processed'], $totalRentalRows, [
                'inserted' => $stats['rentals_inserted'],
                'updated' => $stats['rentals_updated'],
                'skipped' => $stats['rentals_skipped'],
                'failed' => $stats['rentals_failed'],
            ], true);
        }

        $this->table(
            ['Metric', 'Value'],
            [
                ['Mode', $apply ? 'apply' : 'dry-run'],
                ['Headers Processed', $stats['headers_processed']],
                ['Headers Inserted', $stats['headers_inserted']],
                ['Headers Updated', $stats['headers_updated']],
                ['Headers Skipped', $stats['headers_skipped']],
                ['Headers Failed', $stats['headers_failed']],
                ['Details Processed', $stats['details_processed']],
                ['Details Inserted', $stats['details_inserted']],
                ['Details Updated', $stats['details_updated']],
                ['Details Skipped', $stats['details_skipped']],
                ['Details Failed', $stats['details_failed']],
                ['Rentals Processed', $stats['rentals_processed']],
                ['Rentals Inserted', $stats['rentals_inserted']],
                ['Rentals Updated', $stats['rentals_updated']],
                ['Rentals Skipped', $stats['rentals_skipped']],
                ['Rentals Failed', $stats['rentals_failed']],
            ]
        );

        $this->warn('Importer invoice hanya memproses invoice yang sudah bisa resolve ke customer + contract + billing group dari data PinkAds yang sudah dimigrasikan.');

        return self::SUCCESS;
    }

    private function resolveInvoiceContractNumber($detailRows, array $contractMap): ?string
    {
        $contractNos = collect($detailRows)
            ->pluck('sono')
            ->map(fn ($value) => $this->cleanString($value))
            ->filter()
            ->unique()
            ->values();

        if ($contractNos->count() !== 1) {
            return null;
        }

        $contractNumber = $contractNos->first();

        return isset($contractMap[$contractNumber]) ? $contractNumber : null;
    }

    private function resolveInvoiceBillingGroupId(array $header, $detailRows, string $contractNumber, array $billingGroupMap, $targetContracts): ?int
    {
        $billingKeys = collect($detailRows)
            ->map(function (array $row) use ($contractNumber) {
                $billingCode = $this->cleanString($row['billinggroup'] ?? null);

                return $billingCode ? $contractNumber . '||' . $billingCode : null;
            })
            ->filter()
            ->unique()
            ->values();

        if ($billingKeys->isEmpty()) {
            $headerBilling = $this->cleanString($header['billinggroup'] ?? null);
            if ($headerBilling) {
                $billingKeys = collect([$contractNumber . '||' . $headerBilling]);
            }
        }

        if ($billingKeys->isEmpty()) {
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

        if ($billingKeys->count() !== 1) {
            return null;
        }

        return $billingGroupMap[$billingKeys->first()] ?? null;
    }

    private function resolveInvoicePeriod($detailRows): ?string
    {
        $periods = collect($detailRows)
            ->pluck('periodinvoice')
            ->map(fn ($value) => $this->cleanString($value))
            ->filter()
            ->unique()
            ->values();

        if ($periods->count() === 1) {
            return $periods->first();
        }

        return null;
    }

    private function heartbeat(string $phase, int $processed, int $total, array $stats, bool $force = false): void
    {
        if (!$force && ($processed === 0 || $processed % $this->heartbeatEvery !== 0)) {
            return;
        }

        $this->line(sprintf(
            '[%s] progress=%d/%d inserted=%d updated=%d skipped=%d failed=%d',
            $phase,
            $processed,
            $total,
            (int) ($stats['inserted'] ?? 0),
            (int) ($stats['updated'] ?? 0),
            (int) ($stats['skipped'] ?? 0),
            (int) ($stats['failed'] ?? 0),
        ));
    }

    private function mapInvoiceStatus($value): string
    {
        $value = Str::upper($this->cleanString($value) ?? 'D');

        return match ($value) {
            'C' => 'cancelled',
            'P', 'G', 'H' => 'approved',
            default => 'draft',
        };
    }

    private function buildDetailDescription(array $detail): ?string
    {
        $parts = array_filter([
            $this->cleanString($detail['building'] ?? null),
            $this->cleanString($detail['location'] ?? null),
            $this->cleanString($detail['room'] ?? null),
            $this->cleanString($detail['basno'] ?? null),
        ]);

        if ($parts === []) {
            return $this->cleanString($detail['remark'] ?? null);
        }

        return implode(' | ', $parts);
    }

    private function buildInvoiceDetailLabel(array $detail): string
    {
        $product = $this->cleanString($detail['product'] ?? null);
        $context = $this->buildDetailDescription($detail);
        $remark = $this->cleanString($detail['remark'] ?? null);

        $parts = array_filter([$product, $context, $remark]);
        $label = implode(' | ', $parts);

        if ($label === '') {
            $label = $this->cleanString($detail['reffnmbr'] ?? null)
                ?: $this->cleanString($detail['sono'] ?? null)
                ?: 'Legacy Invoice Item';
        }

        return Str::limit($label, 255, '');
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

    private function resolveDecimal($value, float $default = 0): float
    {
        $value = $this->cleanString($value);
        if ($value === null) {
            return $default;
        }

        $normalized = str_replace(',', '', $value);

        return is_numeric($normalized) ? (float) $normalized : $default;
    }
}
