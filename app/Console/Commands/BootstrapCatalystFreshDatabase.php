<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class BootstrapCatalystFreshDatabase extends Command
{
    protected $signature = 'catalyst:bootstrap-fresh
                            {--migrate : Run php artisan migrate --force before import}
                            {--skip-users : Skip user export/import}
                            {--skip-audit : Skip final audit}
                            {--chunk=250 : Chunk size for catalyst:import-masters}';

    protected $description = 'Bootstrap a fresh local database from Catalyst staging using the stable import flow';

    public function handle(): int
    {
        $steps = [
            'product_categories',
            'product_types',
            'banks',
            'branches',
            'departments',
            'customer_categories',
            'customer_types',
            'master_products',
            'master_rentals',
            'rental_components',
            'rental_details',
            'customers',
            'customer_tax_settings',
            'buildings',
            'building_customers',
        ];

        $plan = [];

        if ($this->option('migrate')) {
            $plan[] = ['type' => 'artisan', 'label' => 'Run migrations', 'command' => ['migrate', '--force']];
        }

        $importCommand = ['catalyst:import-masters', '--apply', '--chunk=' . (int) $this->option('chunk')];
        foreach ($steps as $step) {
            $importCommand[] = '--step=' . $step;
        }

        $plan[] = ['type' => 'artisan', 'label' => 'Import master core', 'command' => $importCommand];
        $plan[] = ['type' => 'artisan', 'label' => 'Backfill product relations', 'command' => ['catalyst:backfill-product-relations']];
        $plan[] = ['type' => 'artisan', 'label' => 'Backfill rental details', 'command' => ['catalyst:backfill-rental-details']];
        $plan[] = ['type' => 'artisan', 'label' => 'Normalize rental detail duplicates', 'command' => ['catalyst:normalize-rental-detail-duplicates']];
        $plan[] = ['type' => 'process', 'label' => 'Export rental materials', 'command' => ['powershell', '-ExecutionPolicy', 'Bypass', '-File', base_path('scripts\export_catalyst_rental_materials.ps1')]];
        $plan[] = ['type' => 'artisan', 'label' => 'Backfill rental material options', 'command' => ['catalyst:backfill-rental-material-options', '--file=storage/app/catalyst/rental_materials.csv']];
        $plan[] = ['type' => 'process', 'label' => 'Export customer completeness', 'command' => ['powershell', '-ExecutionPolicy', 'Bypass', '-File', base_path('scripts\export_catalyst_customer_completeness.ps1')]];
        $plan[] = ['type' => 'artisan', 'label' => 'Import customer completeness', 'command' => [
            'catalyst:import-customer-completeness-export',
            '--contacts-file=storage/app/catalyst/customer_contacts_export.csv',
            '--addresses-file=storage/app/catalyst/customer_addresses_export.csv',
            '--apply',
        ]];
        $plan[] = ['type' => 'process', 'label' => 'Export payment defaults', 'command' => ['powershell', '-ExecutionPolicy', 'Bypass', '-File', base_path('scripts\export_catalyst_payment_defaults.ps1')]];
        $plan[] = ['type' => 'artisan', 'label' => 'Import bank payments and customer defaults', 'command' => [
            'catalyst:import-bank-payments-export',
            '--paytypes-file=storage/app/catalyst/payment_types_export.csv',
            '--customers-file=storage/app/catalyst/customer_paymentto_export.csv',
            '--apply',
        ]];

        if (!$this->option('skip-users')) {
            $plan[] = ['type' => 'process', 'label' => 'Export users', 'command' => ['powershell', '-ExecutionPolicy', 'Bypass', '-File', base_path('scripts\export_catalyst_users.ps1')]];
            $plan[] = ['type' => 'artisan', 'label' => 'Import users', 'command' => ['catalyst:import-users-export', '--apply']];
        }

        if (!$this->option('skip-audit')) {
            $plan[] = ['type' => 'artisan', 'label' => 'Audit sync health', 'command' => ['catalyst:audit-sync-health']];
        }

        foreach ($plan as $index => $item) {
            $this->newLine();
            $this->info(sprintf('[%d/%d] %s', $index + 1, count($plan), $item['label']));

            $ok = $item['type'] === 'artisan'
                ? $this->runArtisanCommand($item['command'])
                : $this->runProcessCommand($item['command']);

            if (!$ok) {
                $this->error('Bootstrap berhenti di step: ' . $item['label']);
                return self::FAILURE;
            }
        }

        $this->newLine();
        $this->info('Catalyst fresh bootstrap selesai.');
        $this->warn('Role sengaja tidak diimport dari Catalyst. Role tetap mengikuti system KGI.');

        return self::SUCCESS;
    }

    private function runArtisanCommand(array $arguments): bool
    {
        $process = new Process(array_merge([PHP_BINARY, 'artisan'], $arguments), base_path());
        $process->setTimeout(null);
        $process->setIdleTimeout(null);
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        return $process->isSuccessful();
    }

    private function runProcessCommand(array $command): bool
    {
        $process = new Process($command, base_path());
        $process->setTimeout(null);
        $process->setIdleTimeout(null);
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        return $process->isSuccessful();
    }
}
