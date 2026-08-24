<?php

namespace App\Console\Commands;

use App\Services\Imports\Catalyst\CatalystMasterDataImporter;
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

        // Product Structure, Produk, dan Rental bersumber dari Master Product.xlsx,
        // bukan Catalyst. Dibuang di sini juga supaya rencana yang dicetak jujur -
        // importer memang sudah menolaknya sendiri lewat DISABLED_STEPS.
        $skipped = array_values(array_intersect($steps, CatalystMasterDataImporter::DISABLED_STEPS));
        $steps = array_values(array_diff($steps, CatalystMasterDataImporter::DISABLED_STEPS));

        if ($skipped !== []) {
            $this->warn('Step dimatikan (sumbernya Master Product.xlsx): ' . implode(', ', $skipped));
        }

        $plan = [];

        if ($this->option('migrate')) {
            $plan[] = ['type' => 'artisan', 'label' => 'Run migrations', 'command' => ['migrate', '--force']];
        }

        $importCommand = ['catalyst:import-masters', '--apply', '--chunk=' . (int) $this->option('chunk')];
        foreach ($steps as $step) {
            $importCommand[] = '--step=' . $step;
        }

        $plan[] = ['type' => 'artisan', 'label' => 'Import master core', 'command' => $importCommand];
        // Backfill produk & rental sengaja tidak diikutkan: semuanya menulis ke
        // master_products / rental_details / rental_detail_materials, yang sekarang
        // isinya berasal dari Master Product.xlsx. Perintahnya masih ada dan bisa
        // dijalankan manual kalau memang sedang menyambung ke Catalyst lagi:
        //   catalyst:backfill-product-relations
        //   catalyst:backfill-rental-details
        //   catalyst:normalize-rental-detail-duplicates
        //   catalyst:backfill-rental-material-options
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
