<?php

namespace App\Services\System;

use Symfony\Component\Process\Process;
use InvalidArgumentException;

class CatalystImportConsoleService
{
    public function actions(): array
    {
        return [
            'bootstrap_fresh_database' => [
                'label' => 'Bootstrap Fresh Database',
                'description' => 'One-command flow untuk DB kosong: import core, sync warehouse/rental, import users, lalu audit akhir.',
                'group' => 'warehouse',
                'commands' => [
                    $this->artisanCommand(['catalyst:bootstrap-fresh']),
                ],
            ],
            'dry_run_warehouse_core' => [
                'label' => 'Dry Run Warehouse Core',
                'description' => 'Simulasikan import master warehouse utama: product categories, product types, warehouses, master products, rentals, rental components, dan rental details.',
                'group' => 'warehouse',
                'commands' => [
                    $this->artisanCommand([
                        'catalyst:import-masters',
                        '--step=product_categories',
                        '--step=product_types',
                        '--step=warehouse_types',
                        '--step=warehouses',
                        '--step=master_products',
                        '--step=warehouse_product_links',
                        '--step=master_rentals',
                        '--step=rental_components',
                        '--step=rental_details',
                    ]),
                ],
            ],
            'apply_warehouse_core' => [
                'label' => 'Apply Warehouse Core',
                'description' => 'Tulis import master warehouse utama ke schema KGI tanpa menyentuh role system.',
                'group' => 'warehouse',
                'commands' => [
                    $this->artisanCommand([
                        'catalyst:import-masters',
                        '--step=product_categories',
                        '--step=product_types',
                        '--step=warehouse_types',
                        '--step=warehouses',
                        '--step=master_products',
                        '--step=warehouse_product_links',
                        '--step=master_rentals',
                        '--step=rental_components',
                        '--step=rental_details',
                        '--apply',
                    ]),
                ],
            ],
            'warehouse_full_refresh' => [
                'label' => 'Warehouse Full Refresh',
                'description' => 'Jalankan apply warehouse core lalu sinkronisasi relasi warehouse, product category/brand, dan rental details dalam satu flow.',
                'group' => 'warehouse',
                'commands' => [
                    $this->artisanCommand([
                        'catalyst:import-masters',
                        '--step=product_categories',
                        '--step=product_types',
                        '--step=warehouse_types',
                        '--step=warehouses',
                        '--step=master_products',
                        '--step=warehouse_product_links',
                        '--step=master_rentals',
                        '--step=rental_components',
                        '--step=rental_details',
                        '--apply',
                    ]),
                    ['powershell', '-ExecutionPolicy', 'Bypass', '-File', base_path('scripts\export_catalyst_product_warehouse_links.ps1')],
                    ['powershell', '-ExecutionPolicy', 'Bypass', '-File', base_path('scripts\export_catalyst_rental_materials.ps1')],
                    $this->artisanCommand([
                        'catalyst:backfill-product-warehouses',
                        '--file=storage/app/catalyst/product_warehouse_links.csv',
                    ]),
                    $this->artisanCommand(['catalyst:backfill-product-relations']),
                    $this->artisanCommand(['catalyst:backfill-rental-details']),
                    $this->artisanCommand(['catalyst:normalize-rental-detail-duplicates']),
                    $this->artisanCommand([
                        'catalyst:backfill-rental-material-options',
                        '--file=storage/app/catalyst/rental_materials.csv',
                    ]),
                ],
            ],
            'dry_run_system_core' => [
                'label' => 'Dry Run System Core',
                'description' => 'Simulasikan import branch dan department dari Catalyst. Role sengaja tidak ikut dari source.',
                'group' => 'system',
                'commands' => [
                    $this->artisanCommand([
                        'catalyst:import-masters',
                        '--step=branches',
                        '--step=departments',
                    ]),
                ],
            ],
            'apply_system_core' => [
                'label' => 'Apply System Core',
                'description' => 'Import branch dan department ke system master lokal. Role tetap mengikuti system KGI.',
                'group' => 'system',
                'commands' => [
                    $this->artisanCommand([
                        'catalyst:import-masters',
                        '--step=branches',
                        '--step=departments',
                        '--apply',
                    ]),
                ],
            ],
            'system_users_refresh' => [
                'label' => 'System Users Refresh',
                'description' => 'Export user dari PinkAds lalu import ke users lokal beserta branch_id, department_id, dan pivot branch_user.',
                'group' => 'system',
                'commands' => [
                    ['powershell', '-ExecutionPolicy', 'Bypass', '-File', base_path('scripts\export_catalyst_users.ps1')],
                    $this->artisanCommand(['catalyst:import-users-export', '--apply']),
                ],
            ],
            'customer_completeness_refresh' => [
                'label' => 'Customer Completeness Refresh',
                'description' => 'Export PIC customer dan alamat invoice dari PinkAds lalu sinkronkan customer contacts, Multi PIC, assigned_to, serta district/subdistrict customer.',
                'group' => 'system',
                'commands' => [
                    ['powershell', '-ExecutionPolicy', 'Bypass', '-File', base_path('scripts\export_catalyst_customer_completeness.ps1')],
                    $this->artisanCommand([
                        'catalyst:import-customer-completeness-export',
                        '--contacts-file=storage/app/catalyst/customer_contacts_export.csv',
                        '--addresses-file=storage/app/catalyst/customer_addresses_export.csv',
                        '--apply',
                    ]),
                ],
            ],
            'customer_payment_defaults_refresh' => [
                'label' => 'Customer Payment Defaults Refresh',
                'description' => 'Export MsPayType dan PaymentTo customer dari PinkAds lalu sinkronkan bank payments source-driven dan default bank payment customer.',
                'group' => 'system',
                'commands' => [
                    ['powershell', '-ExecutionPolicy', 'Bypass', '-File', base_path('scripts\export_catalyst_payment_defaults.ps1')],
                    $this->artisanCommand([
                        'catalyst:import-bank-payments-export',
                        '--paytypes-file=storage/app/catalyst/payment_types_export.csv',
                        '--customers-file=storage/app/catalyst/customer_paymentto_export.csv',
                        '--apply',
                    ]),
                ],
            ],
            'dry_run_customer_payment_defaults' => [
                'label' => 'Dry Run Customer Payment Defaults',
                'description' => 'Simulasikan mapping MsPayType ke bank payments dan PaymentTo customer ke default bank payment tanpa menulis ke DB.',
                'group' => 'system',
                'commands' => [
                    ['powershell', '-ExecutionPolicy', 'Bypass', '-File', base_path('scripts\export_catalyst_payment_defaults.ps1')],
                    $this->artisanCommand([
                        'catalyst:import-bank-payments-export',
                        '--paytypes-file=storage/app/catalyst/payment_types_export.csv',
                        '--customers-file=storage/app/catalyst/customer_paymentto_export.csv',
                    ]),
                ],
            ],
            'dry_run_customer_completeness' => [
                'label' => 'Dry Run Customer Completeness',
                'description' => 'Simulasikan sinkronisasi PIC customer dan alamat invoice dari export Catalyst tanpa menulis ke DB lokal.',
                'group' => 'system',
                'commands' => [
                    ['powershell', '-ExecutionPolicy', 'Bypass', '-File', base_path('scripts\export_catalyst_customer_completeness.ps1')],
                    $this->artisanCommand([
                        'catalyst:import-customer-completeness-export',
                        '--contacts-file=storage/app/catalyst/customer_contacts_export.csv',
                        '--addresses-file=storage/app/catalyst/customer_addresses_export.csv',
                    ]),
                ],
            ],
            'export_users' => [
                'label' => 'Export Users',
                'description' => 'Export data user, branch assignment, dan department assignment dari PinkAds ke CSV lokal.',
                'group' => 'users',
                'commands' => [
                    ['powershell', '-ExecutionPolicy', 'Bypass', '-File', base_path('scripts\export_catalyst_users.ps1')],
                ],
            ],
            'dry_run_users' => [
                'label' => 'Dry Run User Import',
                'description' => 'Simulasikan import user Catalyst dari CSV export tanpa menulis ke tabel users.',
                'group' => 'users',
                'commands' => [
                    $this->artisanCommand(['catalyst:import-users-export']),
                ],
            ],
            'apply_users' => [
                'label' => 'Apply User Import',
                'description' => 'Import user Catalyst dari CSV export dan sinkronkan branch_id, department_id, serta pivot branch_user.',
                'group' => 'users',
                'commands' => [
                    $this->artisanCommand(['catalyst:import-users-export', '--apply']),
                ],
            ],
            'export_warehouse_links' => [
                'label' => 'Export Product-Warehouse Links',
                'description' => 'Ambil mapping ProductCode ke Warehouse dari SQL Server staging ke CSV lokal.',
                'group' => 'post_import',
                'commands' => [
                    ['powershell', '-ExecutionPolicy', 'Bypass', '-File', base_path('scripts\export_catalyst_product_warehouse_links.ps1')],
                ],
            ],
            'backfill_product_warehouses' => [
                'label' => 'Backfill Product Warehouses',
                'description' => 'Bentuk relasi produk ke warehouse tanpa memindahkan stok ke master product.',
                'group' => 'post_import',
                'commands' => [
                    $this->artisanCommand([
                        'catalyst:backfill-product-warehouses',
                        '--file=' . base_path('storage\app\catalyst\product_warehouse_links.csv'),
                    ]),
                ],
            ],
            'backfill_product_relations' => [
                'label' => 'Backfill Product Relations',
                'description' => 'Rapikan product category dan brand/variant ber-confidence tinggi untuk produk impor.',
                'group' => 'post_import',
                'commands' => [
                    $this->artisanCommand(['catalyst:backfill-product-relations']),
                ],
            ],
            'backfill_rental_details' => [
                'label' => 'Backfill Rental Details',
                'description' => 'Lengkapi rental details dan service frequency untuk master rental hasil import.',
                'group' => 'post_import',
                'commands' => [
                    $this->artisanCommand(['catalyst:backfill-rental-details']),
                ],
            ],
            'post_import_sync' => [
                'label' => 'Run Post-Import Sync',
                'description' => 'Jalankan export warehouse link, export rental material exact, backfill warehouse, product relations, dan rental details sekaligus.',
                'group' => 'tools',
                'commands' => [
                    ['powershell', '-ExecutionPolicy', 'Bypass', '-File', base_path('scripts\export_catalyst_product_warehouse_links.ps1')],
                    ['powershell', '-ExecutionPolicy', 'Bypass', '-File', base_path('scripts\export_catalyst_rental_materials.ps1')],
                    $this->artisanCommand([
                        'catalyst:backfill-product-warehouses',
                        '--file=' . base_path('storage\app\catalyst\product_warehouse_links.csv'),
                    ]),
                    $this->artisanCommand(['catalyst:backfill-product-relations']),
                    $this->artisanCommand(['catalyst:backfill-rental-details']),
                    $this->artisanCommand(['catalyst:normalize-rental-detail-duplicates']),
                    $this->artisanCommand([
                        'catalyst:backfill-rental-material-options',
                        '--file=storage/app/catalyst/rental_materials.csv',
                    ]),
                ],
            ],
            'normalize_rental_detail_duplicates' => [
                'label' => 'Normalize Rental Detail Duplicates',
                'description' => 'Gabungkan row rental detail impor Catalyst yang dobel secara teknis agar satu component tidak muncul berkali-kali.',
                'group' => 'tools',
                'commands' => [
                    $this->artisanCommand(['catalyst:normalize-rental-detail-duplicates']),
                ],
            ],
            'export_rental_materials' => [
                'label' => 'Export Rental Materials',
                'description' => 'Export exact material options per rental/component dari MsRentalBOMDt ke CSV lokal.',
                'group' => 'tools',
                'commands' => [
                    ['powershell', '-ExecutionPolicy', 'Bypass', '-File', base_path('scripts\export_catalyst_rental_materials.ps1')],
                ],
            ],
            'backfill_rental_material_options' => [
                'label' => 'Backfill Rental Material Options',
                'description' => 'Isi exact material options ke rental detail dari export MsRentalBOMDt tanpa menebak-nebak product default.',
                'group' => 'tools',
                'commands' => [
                    $this->artisanCommand([
                        'catalyst:backfill-rental-material-options',
                        '--file=storage/app/catalyst/rental_materials.csv',
                    ]),
                ],
            ],
            'audit_sync_health' => [
                'label' => 'Audit Sync Health',
                'description' => 'Lihat ringkasan kesehatan modul warehouse dan system setelah import berjalan.',
                'group' => 'tools',
                'commands' => [
                    $this->artisanCommand(['catalyst:audit-sync-health']),
                ],
            ],
        ];
    }

    public function groupedActions(): array
    {
        return collect($this->actions())
            ->map(fn (array $action, string $key) => array_merge($action, ['key' => $key]))
            ->groupBy('group')
            ->all();
    }

    public function run(string $action): array
    {
        $actions = $this->actions();
        if (!isset($actions[$action])) {
            throw new InvalidArgumentException('Unsupported Catalyst import action [' . $action . '].');
        }

        $definition = $actions[$action];
        $startedAt = microtime(true);
        $segments = [];
        $success = true;

        foreach ($definition['commands'] as $command) {
            $result = $this->runProcess($command);
            $segments[] = $result;

            if (!$result['successful']) {
                $success = false;
                break;
            }
        }

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
        $combinedOutput = collect($segments)
            ->map(function (array $segment): string {
                $header = '$ ' . $segment['command_line'];
                $body = trim($segment['output']) !== '' ? trim($segment['output']) : '[no output]';
                return $header . PHP_EOL . $body;
            })
            ->implode(PHP_EOL . PHP_EOL . str_repeat('-', 72) . PHP_EOL . PHP_EOL);

        return [
            'action' => $action,
            'label' => $definition['label'],
            'successful' => $success,
            'duration_ms' => $durationMs,
            'output' => $this->trimOutput($combinedOutput),
            'segments' => $segments,
        ];
    }

    protected function runProcess(array $command): array
    {
        $process = new Process($command, base_path());
        $process->setTimeout(null);
        $process->setIdleTimeout(null);
        $process->run();

        return [
            'command' => $command,
            'command_line' => $process->getCommandLine(),
            'exit_code' => $process->getExitCode(),
            'successful' => $process->isSuccessful(),
            'output' => $process->getOutput() . $process->getErrorOutput(),
        ];
    }

    protected function artisanCommand(array $arguments): array
    {
        return array_merge([PHP_BINARY, 'artisan'], $arguments);
    }

    protected function trimOutput(string $output, int $maxLines = 160): string
    {
        $lines = preg_split("/\r\n|\n|\r/", $output) ?: [];
        if (count($lines) <= $maxLines) {
            return trim($output);
        }

        $tail = array_slice($lines, -$maxLines);
        return '[output truncated, showing last ' . $maxLines . ' lines]' . PHP_EOL . implode(PHP_EOL, $tail);
    }
}
