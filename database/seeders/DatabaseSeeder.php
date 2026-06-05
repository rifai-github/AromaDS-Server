<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Department;
use App\Models\Branch;
use App\Models\Province;
use App\Models\City;
use App\Models\District;
use App\Models\Subdistrict;
use App\Models\MasterOption;
use App\Models\Permission;
use App\Models\Team;
use App\Models\ProductType;
use App\Models\MasterProduct;
use App\Models\MasterRental;
use App\Models\TaxSetting;
use App\Models\Bank;
use App\Models\Company;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\Building;
use App\Models\Category;
use App\Models\PriceSlab;
use App\Models\BankPayment;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // 1. Independent Master Data (No dependencies)
            // Import complete Indonesia location data from SQL files
            IndonesiaLocationDataSeeder::class,
            // Old seeders (commented out - using SQL import instead)
            // ProvinceSeeder::class,
            // CitySeeder::class,
            // DistrictSeeder::class,
            // SubdistrictSeeder::class,
            DepartmentSeeder::class,
            PermissionSeeder::class,
            SystemRolesSeeder::class,
            MasterOptionSeeder::class,
            UserSeeder::class,
            
            // 2. Independent Business Master Data
            BankSeeder::class,
            CompanySeeder::class,
            SupplierSeeder::class,
            CategorySeeder::class,
            PriceSlabSeeder::class,
            TaxSettingSeeder::class,
            PackagingSizeSeeder::class,
            
            // 3. Dependent Master Data (Uses data from step 1 & 2)
            BranchSeeder::class,
            CustomerSeeder::class,
            BuildingSeeder::class,
            ProductTypeSeeder::class,
            MasterRentalSeeder::class,
            
            // 4. Dependent Business Data (Uses data from step 1, 2 & 3)
            BankPaymentSeeder::class,
            TeamSeeder::class,
            WarehouseSeeder::class,
            MasterProductSeeder::class,
            
            // 5. Test Case Data (Complete product hierarchy and rental system)
            TestCaseDataSeeder::class,
            BrandLinesAndVariantsSeeder::class,
            ProductUnitOptionsSeeder::class,
            TermOfPaymentOptionsSeeder::class,
            
            // 6. Stock Distribution (After all products and warehouses are created)
            StockDistributionSeeder::class,
            
            // 7. Sample Data (Uses all previous data)
            MarketingPipelineSeeder::class,
            TechnicianSeeder::class,
            BranchAssignmentSeeder::class,
            DepartmentRoleSeeder::class,
            PositionRoleSeeder::class,
            
            // 6. Audit Trail & Access Control Data
            AuditTrailSeeder::class,
        ]);
    }
}
