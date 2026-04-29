<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Warehouse;
use App\Models\Branch;
use App\Models\User;

class SimpleInventoryLogisticsSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('Inventory Logistics module is ready!');
        $this->command->info('You can now access:');
        $this->command->info('- Logistics Tracking: /warehouse/inventory-logistics/tracking');
        $this->command->info('- Berita Acara: /warehouse/inventory-logistics/berita-acara');
        $this->command->info('- Purchasing Requests: /warehouse/inventory-logistics/purchasing-requests');
    }
}
