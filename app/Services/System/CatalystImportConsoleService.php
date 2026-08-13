<?php

namespace App\Services\System;

use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Process\Process;

class CatalystImportConsoleService
{
    public function __construct(protected CatalystMigrationRunService $migrationRunService)
    {
    }

    public function actions(): array
    {
        return [
            'check_source_connection' => [
                'label' => 'Check Source Connection',
                'description' => 'Verifikasi koneksi SQL Server Catalyst dan pastikan database source bisa dibaca dari server ini.',
                'group' => 'migration',
                'execution' => 'sync',
                'commands' => [
                    $this->artisanCommand(['catalyst:test-source-connection']),
                ],
            ],
            'migration_full_dry_run' => [
                'label' => 'Full Migration Dry Run',
                'description' => 'Jalankan simulasi full import Catalyst di background tanpa menulis perubahan ke database target. Warehouse tidak diikutkan, akan digenerate ulang manual.',
                'group' => 'migration',
                'execution' => 'background',
                'mode' => 'dry-run',
            ],
            'migration_full_apply' => [
                'label' => 'Backup + Full Migration Apply',
                'description' => 'Backup MySQL target dulu, lalu jalankan full import Catalyst di background. Warehouse tidak diikutkan, akan digenerate ulang manual. Wajib konfirmasi karena akan menulis data staging QA.',
                'group' => 'migration',
                'execution' => 'background',
                'mode' => 'apply',
                'requires_confirmation' => true,
                'confirmation_value' => 'MIGRASI',
            ],
            'migration_audit_health' => [
                'label' => 'Migration Audit Health',
                'description' => 'Audit ringkasan hasil sinkronisasi Catalyst yang sudah masuk ke database target.',
                'group' => 'migration',
                'execution' => 'sync',
                'commands' => [
                    $this->artisanCommand(['catalyst:audit-sync-health']),
                ],
            ],
            'dry_run_job_advices' => [
                'label' => 'Dry Run Job Advice Import',
                'description' => 'Simulasikan pembentukan Job Advice dan room dari Catalyst MKTContractJobOut tanpa menulis ke database target dan tanpa mengulang dependency import.',
                'group' => 'migration',
                'execution' => 'sync',
                'commands' => [
                    $this->artisanCommand([
                        'catalyst:import-masters',
                        '--step=job_advices',
                        '--step=job_advice_rooms',
                        '--exact-steps',
                    ]),
                ],
            ],
            'apply_job_advices' => [
                'label' => 'Apply Job Advice Import',
                'description' => 'Import Job Advice dan room dari Catalyst MKTContractJobOut berdasarkan mapping contract atau quotation yang sudah ada tanpa mengulang dependency import.',
                'group' => 'migration',
                'execution' => 'sync',
                'commands' => [
                    $this->artisanCommand([
                        'catalyst:import-masters',
                        '--step=job_advices',
                        '--step=job_advice_rooms',
                        '--exact-steps',
                        '--apply',
                    ]),
                ],
            ],
            'bootstrap_fresh_database' => [
                'label' => 'Bootstrap Fresh Database',
                'description' => 'One-command flow untuk DB kosong: import core dan rental, import users, lalu audit akhir. Warehouse tidak diimport, akan digenerate ulang manual.',
                'group' => 'warehouse',
                'execution' => 'sync',
                'commands' => [
                    $this->artisanCommand(['catalyst:bootstrap-fresh']),
                ],
            ],
            'dry_run_system_core' => [
                'label' => 'Dry Run System Core',
                'description' => 'Simulasikan import branch dan department dari Catalyst. Role sengaja tidak ikut dari source.',
                'group' => 'system',
                'execution' => 'sync',
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
                'execution' => 'sync',
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
                'execution' => 'sync',
                'commands' => [
                    ['powershell', '-ExecutionPolicy', 'Bypass', '-File', base_path('scripts\export_catalyst_users.ps1')],
                    $this->artisanCommand(['catalyst:import-users-export', '--apply']),
                ],
            ],
            'customer_completeness_refresh' => [
                'label' => 'Customer Completeness Refresh',
                'description' => 'Export PIC customer dan alamat invoice dari PinkAds lalu sinkronkan customer contacts, Multi PIC, assigned_to, serta district/subdistrict customer.',
                'group' => 'system',
                'execution' => 'sync',
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
                'execution' => 'sync',
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
                'execution' => 'sync',
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
                'execution' => 'sync',
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
                'execution' => 'sync',
                'commands' => [
                    ['powershell', '-ExecutionPolicy', 'Bypass', '-File', base_path('scripts\export_catalyst_users.ps1')],
                ],
            ],
            'dry_run_users' => [
                'label' => 'Dry Run User Import',
                'description' => 'Simulasikan import user Catalyst dari CSV export tanpa menulis ke tabel users.',
                'group' => 'users',
                'execution' => 'sync',
                'commands' => [
                    $this->artisanCommand(['catalyst:import-users-export']),
                ],
            ],
            'apply_users' => [
                'label' => 'Apply User Import',
                'description' => 'Import user Catalyst dari CSV export dan sinkronkan branch_id, department_id, serta pivot branch_user.',
                'group' => 'users',
                'execution' => 'sync',
                'commands' => [
                    $this->artisanCommand(['catalyst:import-users-export', '--apply']),
                ],
            ],
            'export_warehouse_links' => [
                'label' => 'Export Product-Warehouse Links',
                'description' => 'Ambil mapping ProductCode ke Warehouse dari SQL Server staging ke CSV lokal.',
                'group' => 'post_import',
                'execution' => 'sync',
                'commands' => [
                    ['powershell', '-ExecutionPolicy', 'Bypass', '-File', base_path('scripts\export_catalyst_product_warehouse_links.ps1')],
                ],
            ],
            'backfill_product_warehouses' => [
                'label' => 'Backfill Product Warehouses',
                'description' => 'Bentuk relasi produk ke warehouse tanpa memindahkan stok ke master product.',
                'group' => 'post_import',
                'execution' => 'sync',
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
                'execution' => 'sync',
                'commands' => [
                    $this->artisanCommand(['catalyst:backfill-product-relations']),
                ],
            ],
            'backfill_rental_details' => [
                'label' => 'Backfill Rental Details',
                'description' => 'Lengkapi rental details dan service frequency untuk master rental hasil import.',
                'group' => 'post_import',
                'execution' => 'sync',
                'commands' => [
                    $this->artisanCommand(['catalyst:backfill-rental-details']),
                ],
            ],
            'dry_run_backfill_building_city' => [
                'label' => 'Dry Run Backfill Building City',
                'description' => 'Simulasikan pengisian city_id/province_id Master Building yang kosong, dicocokkan dari CityName/AreaCity yang sudah tersimpan di notes hasil import. Tidak menulis ke database.',
                'group' => 'post_import',
                'execution' => 'sync',
                'commands' => [
                    $this->artisanCommand(['catalyst:backfill-building-city']),
                ],
            ],
            'apply_backfill_building_city' => [
                'label' => 'Apply Backfill Building City',
                'description' => 'Isi city_id/province_id Master Building yang kosong berdasarkan CityName/AreaCity yang sudah tersimpan di notes hasil import Catalyst.',
                'group' => 'post_import',
                'execution' => 'sync',
                'commands' => [
                    $this->artisanCommand(['catalyst:backfill-building-city', '--apply']),
                ],
            ],
            'post_import_sync' => [
                'label' => 'Run Post-Import Sync',
                'description' => 'Jalankan export warehouse link, export rental material exact, backfill warehouse, product relations, dan rental details sekaligus.',
                'group' => 'tools',
                'execution' => 'sync',
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
                'execution' => 'sync',
                'commands' => [
                    $this->artisanCommand(['catalyst:normalize-rental-detail-duplicates']),
                ],
            ],
            'export_rental_materials' => [
                'label' => 'Export Rental Materials',
                'description' => 'Export exact material options per rental/component dari MsRentalBOMDt ke CSV lokal.',
                'group' => 'tools',
                'execution' => 'sync',
                'commands' => [
                    ['powershell', '-ExecutionPolicy', 'Bypass', '-File', base_path('scripts\export_catalyst_rental_materials.ps1')],
                ],
            ],
            'backfill_rental_material_options' => [
                'label' => 'Backfill Rental Material Options',
                'description' => 'Isi exact material options ke rental detail dari export MsRentalBOMDt tanpa menebak-nebak product default.',
                'group' => 'tools',
                'execution' => 'sync',
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
                'execution' => 'sync',
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

    public function definition(string $action): array
    {
        $definition = $this->actions()[$action] ?? null;

        if (!$definition) {
            throw new InvalidArgumentException('Unsupported Catalyst import action [' . $action . '].');
        }

        return $definition;
    }

    public function isBackgroundAction(string $action): bool
    {
        return ($this->definition($action)['execution'] ?? 'sync') === 'background';
    }

    public function launchBackground(string $action, ?int $requestedBy = null): array
    {
        $definition = $this->definition($action);

        if (($definition['execution'] ?? 'sync') !== 'background') {
            throw new InvalidArgumentException('Action [' . $action . '] bukan background action.');
        }

        $logDirectory = storage_path('logs/catalyst');
        if (!is_dir($logDirectory) && !mkdir($logDirectory, 0775, true) && !is_dir($logDirectory)) {
            throw new RuntimeException('Folder log Catalyst tidak bisa dibuat: ' . $logDirectory);
        }

        $run = $this->migrationRunService->createPendingRun($action, $definition, $requestedBy);
        $logPath = $logDirectory . DIRECTORY_SEPARATOR . 'run-' . $run->id . '.log';
        $pid = $this->startDetachedRun((int) $run->id, $logPath);

        $this->migrationRunService->markSpawned((int) $run->id, $pid, $logPath);

        return [
            'run_id' => (int) $run->id,
            'pid' => $pid,
            'label' => $definition['label'],
            'log_path' => $logPath,
        ];
    }

    public function run(string $action): array
    {
        $definition = $this->definition($action);
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
        return array_merge([$this->phpBinary(), 'artisan'], $arguments);
    }

    protected function startDetachedRun(int $runId, string $logPath): ?int
    {
        $phpBinary = $this->phpBinary();

        if (PHP_OS_FAMILY === 'Windows') {
            $command = sprintf(
                'cd /d %s && start "" /B %s artisan catalyst:run-action %d >> %s 2>&1',
                escapeshellarg(base_path()),
                escapeshellarg($phpBinary),
                $runId,
                escapeshellarg($logPath)
            );

            $process = new Process(['cmd', '/C', $command], base_path());
            $process->run();

            if (!$process->isSuccessful()) {
                throw new RuntimeException('Detached Catalyst migration gagal dijalankan di Windows.');
            }

            return null;
        }

        $command = sprintf(
            'cd %s && nohup %s artisan catalyst:run-action %d >> %s 2>&1 & echo $!',
            escapeshellarg(base_path()),
            escapeshellarg($phpBinary),
            $runId,
            escapeshellarg($logPath)
        );

        $process = new Process(['bash', '-lc', $command], base_path());
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException(trim($process->getErrorOutput()) ?: 'Detached Catalyst migration gagal dijalankan.');
        }

        $pid = trim($process->getOutput());

        return is_numeric($pid) ? (int) $pid : null;
    }

    protected function phpBinary(): string
    {
        $configured = trim((string) config('catalyst-import.php_binary', ''));

        if ($configured !== '') {
            return $configured;
        }

        $binaryName = basename(str_replace('\\', '/', PHP_BINARY));

        if (PHP_OS_FAMILY !== 'Windows' && str_contains($binaryName, 'php-fpm') && is_executable('/usr/bin/php')) {
            return '/usr/bin/php';
        }

        return PHP_BINARY;
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
