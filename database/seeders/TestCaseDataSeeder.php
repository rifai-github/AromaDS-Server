<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductCategory;
use App\Models\ProductType;
use App\Models\MasterProduct;
use App\Models\MasterRental;
use App\Models\RentalServiceFrequency;
use App\Models\RentalComponent;
use App\Models\RentalComponentProduct;
use App\Models\Branch;
use App\Models\Warehouse;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TestCaseDataSeeder extends Seeder
{
    private $userId;
    
    /**
     * Disable audit trail for seeding
     */
    private function disableAuditTrail()
    {
        // Disable model events temporarily
        ProductCategory::unsetEventDispatcher();
        ProductType::unsetEventDispatcher();
        MasterProduct::unsetEventDispatcher();
        RentalServiceFrequency::unsetEventDispatcher();
        MasterRental::unsetEventDispatcher();
        RentalComponent::unsetEventDispatcher();
        RentalComponentProduct::unsetEventDispatcher();
    }
    
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::beginTransaction();
        
        try {
            // Get first user for created_by and updated_by
            $user = User::first();
            if (!$user) {
                $this->command->error('No users found in database. Please run UserSeeder first.');
                return;
            }
            $this->userId = $user->id;
            
            // Disable audit trail for seeding
            $this->disableAuditTrail();
            
            // 1. Create Product Categories
            $this->createProductCategories();
            
            // 2. Create Product Types
            $this->createProductTypes();
            
            // 3. Create Master Products
            $this->createMasterProducts();
            
            // 4. Create Service Frequencies
            $this->createServiceFrequencies();
            
            // 5. Create Master Rentals
            $this->createMasterRentals();
            
            // 6. Create Rental Components
            $this->createRentalComponents();
            
            DB::commit();
            $this->command->info('Test case data seeded successfully!');
            
        } catch (\Exception $e) {
            DB::rollback();
            $this->command->error('Error seeding data: ' . $e->getMessage());
        }
    }
    
    private function createProductCategories()
    {
        $this->command->info('Creating Product Categories...');
        
        // Root Categories
        $ads = ProductCategory::create([
            'code' => 'ADS',
            'name' => 'ADS',
            'description' => 'Air Diffuser System products',
            'parent_id' => null,
            'sort_order' => 1,
            'icon' => 'fas fa-wind',
            'color' => '#007bff',
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        $handSanitizer = ProductCategory::create([
            'code' => 'HAND_SANITIZER',
            'name' => 'Hand Sanitizer',
            'description' => 'Hand sanitizer products',
            'parent_id' => null,
            'sort_order' => 2,
            'icon' => 'fas fa-hand-paper',
            'color' => '#28a745',
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        $hygiene = ProductCategory::create([
            'code' => 'HYGIENE',
            'name' => 'Hygiene',
            'description' => 'Hygiene products',
            'parent_id' => null,
            'sort_order' => 3,
            'icon' => 'fas fa-shower',
            'color' => '#17a2b8',
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        $airFilter = ProductCategory::create([
            'code' => 'AIR_FILTER',
            'name' => 'Air Filter',
            'description' => 'Air filter products',
            'parent_id' => null,
            'sort_order' => 4,
            'icon' => 'fas fa-filter',
            'color' => '#6c757d',
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        $equipment = ProductCategory::create([
            'code' => 'EQUIPMENT',
            'name' => 'Equipment',
            'description' => 'Equipment and accessories',
            'parent_id' => null,
            'sort_order' => 5,
            'icon' => 'fas fa-tools',
            'color' => '#fd7e14',
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        // ADS Sub Categories
        $diffuser = ProductCategory::create([
            'code' => 'DIFFUSER',
            'name' => 'Diffuser',
            'description' => 'Diffuser products',
            'parent_id' => $ads->id,
            'sort_order' => 1,
            'icon' => 'fas fa-fan',
            'color' => '#007bff',
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        $aroma = ProductCategory::create([
            'code' => 'AROMA',
            'name' => 'Aroma',
            'description' => 'Aroma products',
            'parent_id' => $ads->id,
            'sort_order' => 2,
            'icon' => 'fas fa-leaf',
            'color' => '#28a745',
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        $sparePart = ProductCategory::create([
            'code' => 'SPARE_PART',
            'name' => 'Spare Part',
            'description' => 'Spare parts for ADS',
            'parent_id' => $ads->id,
            'sort_order' => 3,
            'icon' => 'fas fa-cog',
            'color' => '#6c757d',
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        // Diffuser Sub Categories
        $floor = ProductCategory::create([
            'code' => 'FLOOR',
            'name' => 'Floor',
            'description' => 'Floor diffusers',
            'parent_id' => $diffuser->id,
            'sort_order' => 1,
            'icon' => 'fas fa-home',
            'color' => '#007bff',
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        $desk = ProductCategory::create([
            'code' => 'DESK',
            'name' => 'Desk',
            'description' => 'Desk diffusers',
            'parent_id' => $diffuser->id,
            'sort_order' => 2,
            'icon' => 'fas fa-desktop',
            'color' => '#007bff',
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        $ceiling = ProductCategory::create([
            'code' => 'CEILING',
            'name' => 'Ceiling',
            'description' => 'Ceiling diffusers',
            'parent_id' => $diffuser->id,
            'sort_order' => 3,
            'icon' => 'fas fa-building',
            'color' => '#007bff',
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        $wall = ProductCategory::create([
            'code' => 'WALL',
            'name' => 'Wall',
            'description' => 'Wall diffusers',
            'parent_id' => $diffuser->id,
            'sort_order' => 4,
            'icon' => 'fas fa-wall-brick',
            'color' => '#007bff',
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        $ahu = ProductCategory::create([
            'code' => 'AHU',
            'name' => 'AHU',
            'description' => 'Air Handling Unit',
            'parent_id' => $diffuser->id,
            'sort_order' => 5,
            'icon' => 'fas fa-warehouse',
            'color' => '#007bff',
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        // Aroma Sub Categories
        $signature = ProductCategory::create([
            'code' => 'SIGNATURE',
            'name' => 'Signature',
            'description' => 'Signature aroma line',
            'parent_id' => $aroma->id,
            'sort_order' => 1,
            'icon' => 'fas fa-star',
            'color' => '#ffc107',
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        $artisan = ProductCategory::create([
            'code' => 'ARTISAN',
            'name' => 'Artisan',
            'description' => 'Artisan aroma line',
            'parent_id' => $aroma->id,
            'sort_order' => 2,
            'icon' => 'fas fa-palette',
            'color' => '#e83e8c',
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        $luxo = ProductCategory::create([
            'code' => 'LUXO',
            'name' => 'Luxo',
            'description' => 'Luxo aroma line',
            'parent_id' => $aroma->id,
            'sort_order' => 3,
            'icon' => 'fas fa-gem',
            'color' => '#6f42c1',
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        // Hand Sanitizer Sub Categories
        $dispenser = ProductCategory::create([
            'code' => 'DISPENSER',
            'name' => 'Dispenser',
            'description' => 'Hand sanitizer dispensers',
            'parent_id' => $handSanitizer->id,
            'sort_order' => 1,
            'icon' => 'fas fa-hand-holding-water',
            'color' => '#28a745',
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        $refill = ProductCategory::create([
            'code' => 'REFILL',
            'name' => 'Refill',
            'description' => 'Hand sanitizer refills',
            'parent_id' => $handSanitizer->id,
            'sort_order' => 2,
            'icon' => 'fas fa-tint',
            'color' => '#28a745',
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        // Spare Part Sub Categories
        $pump = ProductCategory::create([
            'code' => 'PUMP',
            'name' => 'Pump',
            'description' => 'Pump spare parts',
            'parent_id' => $sparePart->id,
            'sort_order' => 1,
            'icon' => 'fas fa-pump',
            'color' => '#6c757d',
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        $sprayer = ProductCategory::create([
            'code' => 'SPRAYER',
            'name' => 'Sprayer',
            'description' => 'Sprayer spare parts',
            'parent_id' => $sparePart->id,
            'sort_order' => 2,
            'icon' => 'fas fa-spray-can',
            'color' => '#6c757d',
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        $this->command->info('Product Categories created successfully!');
    }
    
    private function createProductTypes()
    {
        $this->command->info('Creating Product Types...');
        
        // ADS Product Types
        ProductType::create([
            'name' => 'ADS Floor',
            'sku_prefix' => 'ADF',
            'unit' => 'pcs',
            'description' => 'Floor diffuser products',
            'has_serial_number' => true,
            'is_unit' => true,
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        ProductType::create([
            'name' => 'ADS Desk',
            'sku_prefix' => 'ADD',
            'unit' => 'pcs',
            'description' => 'Desk diffuser products',
            'has_serial_number' => true,
            'is_unit' => true,
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        ProductType::create([
            'name' => 'ADS Ceiling',
            'sku_prefix' => 'ADC',
            'unit' => 'pcs',
            'description' => 'Ceiling diffuser products',
            'has_serial_number' => true,
            'is_unit' => true,
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        ProductType::create([
            'name' => 'ADS Wall',
            'sku_prefix' => 'ADW',
            'unit' => 'pcs',
            'description' => 'Wall diffuser products',
            'has_serial_number' => true,
            'is_unit' => true,
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        ProductType::create([
            'name' => 'ADS AHU',
            'sku_prefix' => 'ADA',
            'unit' => 'pcs',
            'description' => 'Air Handling Unit products',
            'has_serial_number' => true,
            'is_unit' => true,
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        // Aroma Product Types
        ProductType::create([
            'name' => 'Signature Aroma',
            'sku_prefix' => 'SIG',
            'unit' => 'ml',
            'description' => 'Signature line aromas',
            'has_serial_number' => false,
            'is_unit' => false,
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        ProductType::create([
            'name' => 'Artisan Aroma',
            'sku_prefix' => 'ART',
            'unit' => 'ml',
            'description' => 'Artisan line aromas',
            'has_serial_number' => false,
            'is_unit' => false,
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        ProductType::create([
            'name' => 'Luxo Aroma',
            'sku_prefix' => 'LUX',
            'unit' => 'ml',
            'description' => 'Luxo line aromas',
            'has_serial_number' => false,
            'is_unit' => false,
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        // Hand Sanitizer Product Types
        ProductType::create([
            'name' => 'Hand Sanitizer Dispenser',
            'sku_prefix' => 'HSD',
            'unit' => 'pcs',
            'description' => 'Hand sanitizer dispensers',
            'has_serial_number' => true,
            'is_unit' => true,
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        ProductType::create([
            'name' => 'Hand Sanitizer Refill',
            'sku_prefix' => 'HSR',
            'unit' => 'ml',
            'description' => 'Hand sanitizer refills',
            'has_serial_number' => false,
            'is_unit' => false,
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        // Spare Part Product Type
        ProductType::create([
            'name' => 'Spare Part',
            'sku_prefix' => 'SP',
            'unit' => 'pcs',
            'description' => 'Spare parts and accessories',
            'has_serial_number' => false,
            'is_unit' => false,
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        $this->command->info('Product Types created successfully!');
    }
    
    private function createMasterProducts()
    {
        $this->command->info('Creating Master Products...');
        
        // Get categories and product types
        $floorCategory = ProductCategory::where('code', 'FLOOR')->first();
        $deskCategory = ProductCategory::where('code', 'DESK')->first();
        $ceilingCategory = ProductCategory::where('code', 'CEILING')->first();
        $wallCategory = ProductCategory::where('code', 'WALL')->first();
        $ahuCategory = ProductCategory::where('code', 'AHU')->first();
        $signatureCategory = ProductCategory::where('code', 'SIGNATURE')->first();
        $artisanCategory = ProductCategory::where('code', 'ARTISAN')->first();
        $luxoCategory = ProductCategory::where('code', 'LUXO')->first();
        $dispenserCategory = ProductCategory::where('code', 'DISPENSER')->first();
        $refillCategory = ProductCategory::where('code', 'REFILL')->first();
        $pumpCategory = ProductCategory::where('code', 'PUMP')->first();
        $sprayerCategory = ProductCategory::where('code', 'SPRAYER')->first();
        
        $adsFloorType = ProductType::where('name', 'ADS Floor')->first();
        $adsDeskType = ProductType::where('name', 'ADS Desk')->first();
        $adsCeilingType = ProductType::where('name', 'ADS Ceiling')->first();
        $adsWallType = ProductType::where('name', 'ADS Wall')->first();
        $adsAhuType = ProductType::where('name', 'ADS AHU')->first();
        $signatureType = ProductType::where('name', 'Signature Aroma')->first();
        $artisanType = ProductType::where('name', 'Artisan Aroma')->first();
        $luxoType = ProductType::where('name', 'Luxo Aroma')->first();
        $hsDispenserType = ProductType::where('name', 'Hand Sanitizer Dispenser')->first();
        $hsRefillType = ProductType::where('name', 'Hand Sanitizer Refill')->first();
        $sparePartType = ProductType::where('name', 'Spare Part')->first();
        
        // ADS Floor Products
        MasterProduct::create([
            'name' => 'ADS F',
            'variant_name' => 'ADS F',
            'brand_line' => 'Floor',
            'packaging_size' => 'Standard',
            'sku' => 'ADF-001',
            'product_type_id' => $adsFloorType->id,
            'product_category_id' => $floorCategory->id,
            'description' => 'Floor diffuser ADS F',
            'unit' => 'pcs',
            'minimum_stock' => 10,
            'maximum_stock' => 100,
            'unit_price' => 1500000,
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        // ADS Desk Products
        MasterProduct::create([
            'name' => 'ADS 301',
            'variant_name' => 'ADS 301',
            'brand_line' => 'Desk',
            'packaging_size' => 'Standard',
            'sku' => 'ADD-301',
            'product_type_id' => $adsDeskType->id,
            'product_category_id' => $deskCategory->id,
            'description' => 'Desk diffuser ADS 301',
            'unit' => 'pcs',
            'minimum_stock' => 10,
            'maximum_stock' => 100,
            'unit_price' => 1200000,
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        // ADS Ceiling Products
        MasterProduct::create([
            'name' => 'ADS 305',
            'variant_name' => 'ADS 305',
            'brand_line' => 'Ceiling',
            'packaging_size' => 'Standard',
            'sku' => 'ADC-305',
            'product_type_id' => $adsCeilingType->id,
            'product_category_id' => $ceilingCategory->id,
            'description' => 'Ceiling diffuser ADS 305',
            'unit' => 'pcs',
            'minimum_stock' => 10,
            'maximum_stock' => 100,
            'unit_price' => 1800000,
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        // ADS Wall Products
        MasterProduct::create([
            'name' => 'ADS Atom',
            'variant_name' => 'ADS Atom',
            'brand_line' => 'Wall',
            'packaging_size' => 'Standard',
            'sku' => 'ADW-ATOM',
            'product_type_id' => $adsWallType->id,
            'product_category_id' => $wallCategory->id,
            'description' => 'Wall diffuser ADS Atom',
            'unit' => 'pcs',
            'minimum_stock' => 10,
            'maximum_stock' => 100,
            'unit_price' => 800000,
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        // ADS AHU Products
        MasterProduct::create([
            'name' => 'ADS 5000S',
            'variant_name' => 'ADS 5000S',
            'brand_line' => 'AHU',
            'packaging_size' => 'Standard',
            'sku' => 'ADA-5000S',
            'product_type_id' => $adsAhuType->id,
            'product_category_id' => $ahuCategory->id,
            'description' => 'Air Handling Unit ADS 5000S',
            'unit' => 'pcs',
            'minimum_stock' => 5,
            'maximum_stock' => 50,
            'unit_price' => 5000000,
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        // Signature Aroma Products
        $signatureSizes = ['50ml', '100ml', '250ml', '500ml', '1000ml'];
        $signaturePrices = [25000, 45000, 100000, 180000, 320000];
        
        foreach ($signatureSizes as $index => $size) {
            MasterProduct::create([
                'name' => 'Lemon Squash',
                'variant_name' => 'Lemon Squash',
                'brand_line' => 'Signature',
                'packaging_size' => $size,
                'sku' => 'SIG-LEM-' . str_replace('ml', '', $size),
                'product_type_id' => $signatureType->id,
                'product_category_id' => $signatureCategory->id,
                'description' => "Lemon Squash {$size}",
                'unit' => 'ml',
                'minimum_stock' => 20,
                'maximum_stock' => 500,
                'unit_price' => $signaturePrices[$index],
                'is_active' => true,
                'created_by' => $this->userId,
                'updated_by' => $this->userId
            ]);
        }
        
        // Artisan Aroma Products
        $artisanSizes = ['50ml', '100ml', '250ml', '500ml', '1000ml'];
        $artisanPrices = [30000, 55000, 120000, 220000, 400000];
        
        foreach ($artisanSizes as $index => $size) {
            MasterProduct::create([
                'name' => 'White Rose',
                'variant_name' => 'White Rose',
                'brand_line' => 'Artisan',
                'packaging_size' => $size,
                'sku' => 'ART-WRO-' . str_replace('ml', '', $size),
                'product_type_id' => $artisanType->id,
                'product_category_id' => $artisanCategory->id,
                'description' => "White Rose {$size}",
                'unit' => 'ml',
                'minimum_stock' => 20,
                'maximum_stock' => 500,
                'unit_price' => $artisanPrices[$index],
                'is_active' => true,
                'created_by' => $this->userId,
                'updated_by' => $this->userId
            ]);
        }
        
        // Luxo Aroma Products
        $luxoSizes = ['50ml', '100ml', '250ml', '500ml', '1000ml'];
        $luxoPrices = [35000, 65000, 140000, 260000, 480000];
        
        foreach ($luxoSizes as $index => $size) {
            MasterProduct::create([
                'name' => 'Ginger Blossom',
                'variant_name' => 'Ginger Blossom',
                'brand_line' => 'Luxo',
                'packaging_size' => $size,
                'sku' => 'LUX-GIN-' . str_replace('ml', '', $size),
                'product_type_id' => $luxoType->id,
                'product_category_id' => $luxoCategory->id,
                'description' => "Ginger Blossom {$size}",
                'unit' => 'ml',
                'minimum_stock' => 20,
                'maximum_stock' => 500,
                'unit_price' => $luxoPrices[$index],
                'is_active' => true,
                'created_by' => $this->userId,
                'updated_by' => $this->userId
            ]);
        }
        
        // Hand Sanitizer Dispensers
        $dispenserModels = ['7100', '7200', '7400', '7600', '7840'];
        $dispenserPrices = [500000, 600000, 700000, 800000, 900000];
        
        foreach ($dispenserModels as $index => $model) {
            MasterProduct::create([
                'name' => "Hand Sanitizer {$model}",
                'variant_name' => $model,
                'brand_line' => 'Dispenser',
                'packaging_size' => 'Standard',
                'sku' => "HSD-{$model}",
                'product_type_id' => $hsDispenserType->id,
                'product_category_id' => $dispenserCategory->id,
                'description' => "Hand Sanitizer Dispenser {$model}",
                'unit' => 'pcs',
                'minimum_stock' => 10,
                'maximum_stock' => 100,
                'unit_price' => $dispenserPrices[$index],
                'is_active' => true,
                'created_by' => $this->userId,
                'updated_by' => $this->userId
            ]);
        }
        
        // Hand Sanitizer Refills
        $refillTypes = [
            ['name' => 'Hand Sanitizer Liquid', 'size' => '500ml', 'price' => 25000],
            ['name' => 'Hand Sanitizer Liquid', 'size' => '1000ml', 'price' => 45000],
            ['name' => 'Hand Sanitizer Spray', 'size' => '100ml', 'price' => 15000],
            ['name' => 'Hand Sanitizer Spray', 'size' => '250ml', 'price' => 30000],
            ['name' => 'Hand Sanitizer Spray', 'size' => '500ml', 'price' => 55000],
            ['name' => 'Hand Sanitizer Gel', 'size' => '500ml', 'price' => 30000],
            ['name' => 'Hand Sanitizer Gel', 'size' => '1000ml', 'price' => 55000],
            ['name' => 'Hand Sanitizer Pump', 'size' => '500ml', 'price' => 35000]
        ];
        
        foreach ($refillTypes as $refill) {
            MasterProduct::create([
                'name' => $refill['name'],
                'variant_name' => $refill['name'],
                'brand_line' => 'Refill',
                'packaging_size' => $refill['size'],
                'sku' => 'HSR-' . strtoupper(str_replace([' ', 'ml'], ['-', ''], $refill['name'] . '-' . $refill['size'])),
                'product_type_id' => $hsRefillType->id,
                'product_category_id' => $refillCategory->id,
                'description' => "{$refill['name']} {$refill['size']}",
                'unit' => 'ml',
                'minimum_stock' => 50,
                'maximum_stock' => 1000,
                'unit_price' => $refill['price'],
                'is_active' => true,
                'created_by' => $this->userId,
                'updated_by' => $this->userId
            ]);
        }
        
        // Spare Parts
        MasterProduct::create([
            'name' => 'Pump ADS 301',
            'variant_name' => 'Pump ADS 301',
            'brand_line' => 'Pump',
            'packaging_size' => 'Standard',
            'sku' => 'SP-PUMP-301',
            'product_type_id' => $sparePartType->id,
            'product_category_id' => $pumpCategory->id,
            'description' => 'Pump for ADS 301',
            'unit' => 'pcs',
            'minimum_stock' => 20,
            'maximum_stock' => 200,
            'unit_price' => 150000,
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        MasterProduct::create([
            'name' => 'Sprayer ADS 305',
            'variant_name' => 'Sprayer ADS 305',
            'brand_line' => 'Sprayer',
            'packaging_size' => 'Standard',
            'sku' => 'SP-SPR-305',
            'product_type_id' => $sparePartType->id,
            'product_category_id' => $sprayerCategory->id,
            'description' => 'Sprayer for ADS 305',
            'unit' => 'pcs',
            'minimum_stock' => 20,
            'maximum_stock' => 200,
            'unit_price' => 200000,
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        $this->command->info('Master Products created successfully!');
    }
    
    private function createServiceFrequencies()
    {
        $this->command->info('Creating Service Frequencies...');
        
        $frequencies = [
            ['code' => 'MONTHLY_1', 'name' => 'Monthly 1x', 'months' => 1, 'times' => 1],
            ['code' => 'MONTHLY_2', 'name' => 'Monthly 2x', 'months' => 1, 'times' => 2],
            ['code' => 'MONTHLY_3', 'name' => 'Monthly 3x', 'months' => 1, 'times' => 3],
            ['code' => 'BI_MONTHLY', 'name' => 'Bi-Monthly 1x', 'months' => 2, 'times' => 1],
            ['code' => 'QUARTERLY', 'name' => 'Quarterly 1x', 'months' => 3, 'times' => 1]
        ];
        
        foreach ($frequencies as $index => $freq) {
            RentalServiceFrequency::create([
                'code' => $freq['code'],
                'name' => $freq['name'],
                'description' => "Service frequency: {$freq['name']}",
                'frequency_months' => $freq['months'],
                'frequency_times_per_month' => $freq['times'],
                'sort_order' => $index + 1,
                'is_active' => true,
                'created_by' => $this->userId,
                'updated_by' => $this->userId
            ]);
        }
        
        $this->command->info('Service Frequencies created successfully!');
    }
    
    private function createMasterRentals()
    {
        $this->command->info('Creating Master Rentals...');
        
        $rentals = [
            [
                'rental_code' => 'RTL-ADS-001',
                'rental_name' => 'ADS 301 Complete Package',
                'alias' => 'ADS301-COMP',
                'description' => 'Complete ADS 301 rental package with diffuser, refill, and battery',
                'service_frequency' => 'Monthly 1x',
                'category' => 'ADS',
                'rental_type' => 'unit_refill',
                'daily_price' => 50000,
                'monthly_price' => 1500000,
                'unit' => 'package',
                'has_activation_component' => true
            ],
            [
                'rental_code' => 'RTL-ADS-002',
                'rental_name' => 'ADS 305 Complete Package',
                'alias' => 'ADS305-COMP',
                'description' => 'Complete ADS 305 rental package with diffuser, refill, and battery',
                'service_frequency' => 'Monthly 1x',
                'category' => 'ADS',
                'rental_type' => 'unit_refill',
                'daily_price' => 60000,
                'monthly_price' => 1800000,
                'unit' => 'package',
                'has_activation_component' => true
            ],
            [
                'rental_code' => 'RTL-HS-001',
                'rental_name' => 'Hand Sanitizer Complete Package',
                'alias' => 'HS-COMP',
                'description' => 'Complete hand sanitizer rental package with dispenser and refill',
                'service_frequency' => 'Monthly 2x',
                'category' => 'Hand Sanitizer',
                'rental_type' => 'unit_refill',
                'daily_price' => 30000,
                'monthly_price' => 900000,
                'unit' => 'package',
                'has_activation_component' => true
            ]
        ];
        
        foreach ($rentals as $rental) {
            MasterRental::create([
                'rental_code' => $rental['rental_code'],
                'rental_name' => $rental['rental_name'],
                'alias' => $rental['alias'],
                'description' => $rental['description'],
                'service_frequency' => $rental['service_frequency'],
                'category' => $rental['category'],
                'rental_type' => $rental['rental_type'],
                'daily_price' => $rental['daily_price'],
                'monthly_price' => $rental['monthly_price'],
                'unit' => $rental['unit'],
                'has_activation_component' => $rental['has_activation_component'],
                'is_active' => true,
                'created_by' => $this->userId,
                'updated_by' => $this->userId
            ]);
        }
        
        $this->command->info('Master Rentals created successfully!');
    }
    
    private function createRentalComponents()
    {
        $this->command->info('Creating Rental Components...');
        
        // Get master rentals
        $ads301Rental = MasterRental::where('rental_code', 'RTL-ADS-001')->first();
        $ads305Rental = MasterRental::where('rental_code', 'RTL-ADS-002')->first();
        $hsRental = MasterRental::where('rental_code', 'RTL-HS-001')->first();
        
        // Get products
        $ads301Product = MasterProduct::where('sku', 'ADD-301')->first();
        $ads305Product = MasterProduct::where('sku', 'ADC-305')->first();
        $lemonSquash50ml = MasterProduct::where('sku', 'SIG-LEM-50')->first();
        $lemonSquash100ml = MasterProduct::where('sku', 'SIG-LEM-100')->first();
        $whiteRose50ml = MasterProduct::where('sku', 'ART-WRO-50')->first();
        $hsDispenser = MasterProduct::where('sku', 'HSD-7100')->first();
        $hsLiquid = MasterProduct::where('sku', 'HSR-HAND-SANITIZER-LIQUID-500')->first();
        
        // ADS 301 Components
        $ads301Diffuser = RentalComponent::create([
            'master_rental_id' => $ads301Rental->id,
            'component_name' => 'ADS 301 Diffuser',
            'description' => 'Main diffuser unit',
            'quantity' => 1,
            'unit' => 'pcs',
            'replacement_frequency_months' => 12,
            'replacement_price' => 1200000,
            'is_activation_component' => true,
            'sort_order' => 1,
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        $ads301Refill = RentalComponent::create([
            'master_rental_id' => $ads301Rental->id,
            'component_name' => 'Aroma Refill',
            'description' => 'Aroma refill for diffuser',
            'quantity' => 1,
            'unit' => 'bottle',
            'replacement_frequency_months' => 1,
            'replacement_price' => 25000,
            'is_activation_component' => false,
            'sort_order' => 2,
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        // ADS 305 Components
        $ads305Diffuser = RentalComponent::create([
            'master_rental_id' => $ads305Rental->id,
            'component_name' => 'ADS 305 Diffuser',
            'description' => 'Main diffuser unit',
            'quantity' => 1,
            'unit' => 'pcs',
            'replacement_frequency_months' => 12,
            'replacement_price' => 1800000,
            'is_activation_component' => true,
            'sort_order' => 1,
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        $ads305Refill = RentalComponent::create([
            'master_rental_id' => $ads305Rental->id,
            'component_name' => 'Aroma Refill',
            'description' => 'Aroma refill for diffuser',
            'quantity' => 1,
            'unit' => 'bottle',
            'replacement_frequency_months' => 1,
            'replacement_price' => 25000,
            'is_activation_component' => false,
            'sort_order' => 2,
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        // Hand Sanitizer Components
        $hsDispenserComp = RentalComponent::create([
            'master_rental_id' => $hsRental->id,
            'component_name' => 'Hand Sanitizer Dispenser',
            'description' => 'Main dispenser unit',
            'quantity' => 1,
            'unit' => 'pcs',
            'replacement_frequency_months' => 12,
            'replacement_price' => 500000,
            'is_activation_component' => true,
            'sort_order' => 1,
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        $hsRefillComp = RentalComponent::create([
            'master_rental_id' => $hsRental->id,
            'component_name' => 'Hand Sanitizer Refill',
            'description' => 'Hand sanitizer refill',
            'quantity' => 1,
            'unit' => 'bottle',
            'replacement_frequency_months' => 1,
            'replacement_price' => 25000,
            'is_activation_component' => false,
            'sort_order' => 2,
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        // Assign products to components
        // ADS 301 Diffuser
        RentalComponentProduct::create([
            'rental_component_id' => $ads301Diffuser->id,
            'master_product_id' => $ads301Product->id,
            'is_preferred' => true,
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        // ADS 301 Refill - Multiple options
        RentalComponentProduct::create([
            'rental_component_id' => $ads301Refill->id,
            'master_product_id' => $lemonSquash50ml->id,
            'is_preferred' => true,
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        RentalComponentProduct::create([
            'rental_component_id' => $ads301Refill->id,
            'master_product_id' => $lemonSquash100ml->id,
            'is_preferred' => false,
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        RentalComponentProduct::create([
            'rental_component_id' => $ads301Refill->id,
            'master_product_id' => $whiteRose50ml->id,
            'is_preferred' => false,
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        // ADS 305 Diffuser
        RentalComponentProduct::create([
            'rental_component_id' => $ads305Diffuser->id,
            'master_product_id' => $ads305Product->id,
            'is_preferred' => true,
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        // ADS 305 Refill
        RentalComponentProduct::create([
            'rental_component_id' => $ads305Refill->id,
            'master_product_id' => $lemonSquash50ml->id,
            'is_preferred' => true,
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        // Hand Sanitizer Dispenser
        RentalComponentProduct::create([
            'rental_component_id' => $hsDispenserComp->id,
            'master_product_id' => $hsDispenser->id,
            'is_preferred' => true,
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        // Hand Sanitizer Refill
        RentalComponentProduct::create([
            'rental_component_id' => $hsRefillComp->id,
            'master_product_id' => $hsLiquid->id,
            'is_preferred' => true,
            'is_active' => true,
            'created_by' => $this->userId,
            'updated_by' => $this->userId
        ]);
        
        $this->command->info('Rental Components created successfully!');
    }
}
