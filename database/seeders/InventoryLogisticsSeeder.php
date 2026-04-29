<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LogisticsTracking;
use App\Models\BeritaAcara;
use App\Models\PurchasingRequest;
use App\Models\Warehouse;
use App\Models\Branch;
use App\Models\User;
use App\Models\InventoryRequest;
use App\Models\InventoryReceiving;
use App\Models\Supplier;

class InventoryLogisticsSeeder extends Seeder
{
    public function run()
    {
        // Get existing data
        $warehouse = Warehouse::first();
        $branch = Branch::first();
        $user = User::first();
        $inventoryRequest = InventoryRequest::first();
        $inventoryReceiving = InventoryReceiving::first();
        $supplier = Supplier::first();

        if (!$warehouse || !$branch || !$user) {
            $this->command->info('Required data not found. Please run other seeders first.');
            return;
        }

        // Create inventory request if not exists
        if (!$inventoryRequest) {
            $inventoryRequestId = \DB::table('inventory_requests')->insertGetId([
                'request_number' => 'IR' . date('Ymd') . '001',
                'warehouse_id' => $warehouse->id,
                'requested_by' => $user->id,
                'request_date' => now()->format('Y-m-d'),
                'priority' => 'medium',
                'reason' => 'Sample inventory request for logistics tracking',
                'required_date' => now()->addDays(7)->format('Y-m-d'),
                'status' => 'approved',
                'processed_date' => now(),
                'notes' => 'Sample notes for inventory request',
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $inventoryRequest = (object)['id' => $inventoryRequestId];
        }

        // Create Logistics Tracking samples
        $trackings = [];
        for ($i = 1; $i <= 5; $i++) {
            $trackingId = \DB::table('logistics_tracking')->insertGetId([
                'tracking_number' => 'TRK' . date('Ymd') . str_pad($i, 4, '0', STR_PAD_LEFT),
                'inventory_request_id' => $inventoryRequest->id,
                'from_warehouse_id' => $warehouse->id,
                'to_branch_id' => $branch->id,
                'status' => ['requested', 'preparing', 'shipped', 'delivered', 'returned'][array_rand(['requested', 'preparing', 'shipped', 'delivered', 'returned'])],
                'resi_number' => 'RESI' . rand(100000, 999999),
                'courier_name' => ['JNE', 'TIKI', 'J&T', 'SiCepat', 'Pos Indonesia'][array_rand(['JNE', 'TIKI', 'J&T', 'SiCepat', 'Pos Indonesia'])],
                'notes' => 'Sample tracking notes ' . $i,
                'requested_at' => now()->subDays(rand(1, 30)),
                'preparing_at' => now()->subDays(rand(1, 25)),
                'shipped_at' => now()->subDays(rand(1, 20)),
                'delivered_at' => now()->subDays(rand(1, 15)),
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $trackings[] = (object)['id' => $trackingId];
        }

        // Create Berita Acara samples
        for ($i = 1; $i <= 3; $i++) {
            \DB::table('berita_acara')->insert([
                'berita_acara_number' => 'BA' . date('Ymd') . str_pad($i, 4, '0', STR_PAD_LEFT),
                'logistics_tracking_id' => $trackings[array_rand($trackings)]->id,
                'inventory_receiving_id' => $inventoryReceiving ? $inventoryReceiving->id : null,
                'type' => ['loss', 'damage', 'discrepancy'][array_rand(['loss', 'damage', 'discrepancy'])],
                'description' => 'Sample berita acara description ' . $i,
                'action_taken' => 'Sample action taken ' . $i,
                'status' => ['draft', 'submitted', 'approved', 'rejected', 'processed'][array_rand(['draft', 'submitted', 'approved', 'rejected', 'processed'])],
                'reported_by' => $user->id,
                'reported_at' => now(),
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create Purchasing Request samples
        for ($i = 1; $i <= 4; $i++) {
            \DB::table('purchasing_requests')->insert([
                'request_number' => 'PR' . date('Ymd') . str_pad($i, 4, '0', STR_PAD_LEFT),
                'logistics_tracking_id' => $trackings[array_rand($trackings)]->id,
                'supplier_id' => $supplier ? $supplier->id : null,
                'status' => ['pending', 'approved', 'rejected', 'completed'][array_rand(['pending', 'approved', 'rejected', 'completed'])],
                'total_amount' => rand(100000, 5000000),
                'notes' => 'Sample purchasing request notes ' . $i,
                'requested_by' => $user->id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('Inventory Logistics sample data created successfully!');
    }
}
