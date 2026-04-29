<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MasterProduct;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StockDistributionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "=== STOCK DISTRIBUTION SEEDER ===\n";
        
        // Get first user for created_by/updated_by
        $user = User::first();
        if (!$user) {
            echo "❌ No user found! Please run user seeder first.\n";
            return;
        }
        
        // Get all warehouses
        $warehouses = Warehouse::where('is_active', true)->get();
        if ($warehouses->count() == 0) {
            echo "❌ No active warehouses found!\n";
            return;
        }
        
        echo "📦 Found {$warehouses->count()} active warehouses\n";
        
        // Get all master products
        $masterProducts = MasterProduct::where('is_active', true)->get();
        echo "🛍️ Found {$masterProducts->count()} active master products\n";
        
        $stockCreated = 0;
        $stockUpdated = 0;
        
        DB::beginTransaction();
        
        try {
            foreach ($masterProducts as $product) {
                echo "Processing: {$product->name}\n";
                
                // Check if product already has stock in any warehouse
                $existingStock = WarehouseProduct::where('master_product_id', $product->id)->exists();
                
                if (!$existingStock) {
                    // Distribute stock to warehouses based on product type
                    $stockDistribution = $this->getStockDistribution($product, $warehouses);
                    
                    foreach ($stockDistribution as $warehouseId => $stockData) {
                        WarehouseProduct::create([
                            'warehouse_id' => $warehouseId,
                            'master_product_id' => $product->id,
                            'quantity' => $stockData['quantity'],
                            'minimum_stock' => $stockData['minimum_stock'],
                            'maximum_stock' => $stockData['maximum_stock'],
                            'created_by' => $user->id,
                            'updated_by' => $user->id,
                        ]);
                        
                        $stockCreated++;
                        echo "  ✅ Added {$stockData['quantity']} units to warehouse {$warehouseId}\n";
                    }
                } else {
                    echo "  ⚠️ Stock already exists, skipping\n";
                }
            }
            
            DB::commit();
            echo "\n✅ STOCK DISTRIBUTION COMPLETED!\n";
            echo "📊 Stock records created: {$stockCreated}\n";
            echo "📊 Stock records updated: {$stockUpdated}\n";
            
        } catch (\Exception $e) {
            DB::rollback();
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Get stock distribution for a product across warehouses
     */
    private function getStockDistribution($product, $warehouses)
    {
        $distribution = [];
        
        // Base stock quantities based on product name patterns
        $baseQuantity = $this->getBaseQuantity($product);
        $minimumStock = max(5, $baseQuantity * 0.2); // 20% of base quantity, minimum 5
        $maximumStock = $baseQuantity * 2; // 2x base quantity
        
        foreach ($warehouses as $warehouse) {
            // Adjust quantity based on warehouse type/location
            $quantity = $this->adjustQuantityForWarehouse($baseQuantity, $warehouse);
            
            $distribution[$warehouse->id] = [
                'quantity' => $quantity,
                'minimum_stock' => $minimumStock,
                'maximum_stock' => $maximumStock,
            ];
        }
        
        return $distribution;
    }
    
    /**
     * Get base quantity for a product based on its name/type
     */
    private function getBaseQuantity($product)
    {
        $name = strtolower($product->name);
        
        // Diffuser units - lower quantity
        if (strpos($name, 'diffuser') !== false) {
            return rand(15, 25);
        }
        
        // Aroma oils - medium quantity
        if (strpos($name, 'oil') !== false || strpos($name, 'aroma') !== false) {
            return rand(25, 45);
        }
        
        // Refills - higher quantity
        if (strpos($name, 'refill') !== false) {
            return rand(40, 60);
        }
        
        // Cartridges - medium-high quantity
        if (strpos($name, 'cartridge') !== false) {
            return rand(30, 50);
        }
        
        // Filters - medium quantity
        if (strpos($name, 'filter') !== false) {
            return rand(20, 35);
        }
        
        // Cleaners - medium quantity
        if (strpos($name, 'cleaner') !== false) {
            return rand(15, 30);
        }
        
        // Default quantity
        return rand(20, 40);
    }
    
    /**
     * Adjust quantity based on warehouse location
     */
    private function adjustQuantityForWarehouse($baseQuantity, $warehouse)
    {
        $name = strtolower($warehouse->name);
        
        // Jakarta Pusat (Headquarters) - highest stock
        if (strpos($name, 'jakarta') !== false && strpos($name, 'pusat') !== false) {
            return $baseQuantity;
        }
        
        // Surabaya - high stock
        if (strpos($name, 'surabaya') !== false) {
            return (int)($baseQuantity * 0.8);
        }
        
        // Bandung - medium stock
        if (strpos($name, 'bandung') !== false) {
            return (int)($baseQuantity * 0.7);
        }
        
        // Other warehouses - lower stock
        return (int)($baseQuantity * 0.6);
    }
}
