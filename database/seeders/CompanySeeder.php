<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Province;
use App\Models\City;
use Illuminate\Support\Facades\DB;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get provinces and cities
        $jakarta = Province::where('province_name', 'LIKE', '%Jakarta%')->first();
        $bandung = Province::where('province_name', 'LIKE', '%Jawa Barat%')->first();
        
        $jakartaCity = City::where('name', 'LIKE', '%Jakarta%')->first();
        $bandungCity = City::where('name', 'LIKE', '%Bandung%')->first();

        $companies = [
            [
                'name' => 'PT Pink Services Indonesia',
                'code' => 'PSI001',
                'label_alias' => 'Pink Services',
                'status' => 'active',
                'company_type' => 'pt',
                'tax_code' => '01.234.567.8-901.000',
                'nib_number' => '0123456789012345',
                'is_pkp' => true,
                'is_active' => true,
                'grace_period_days' => 30,
                'default_payment' => 'credit',
                'member_since' => '1996-01-01',
                'balance' => 0.00,
                'email' => 'info@pinkservices.com',
                'phone' => '021-12345678',
                'website' => 'https://www.pinkservices.com',
                'address' => 'Jl. Sudirman No. 1, Jakarta Pusat',
                'province_id' => $jakarta ? $jakarta->id : 1,
                'city_id' => $jakartaCity ? $jakartaCity->id : 1,
                'postal_code' => '12190',
                'industry' => 'Washroom Hygiene Service',
                'employee_count' => 150,
                'annual_revenue' => 5000000000.00,
                'description' => 'Perusahaan penyedia layanan kebersihan toilet dan sistem pengharum ruangan dengan brand Habitat dan Aroma Delivery System (ADS).',
            ],
            [
                'name' => 'PT Pink Services Indonesia - Bandung',
                'code' => 'PSI002',
                'label_alias' => 'Pink Services Bandung',
                'status' => 'active',
                'company_type' => 'pt',
                'tax_code' => '01.234.567.8-902.000',
                'nib_number' => '0123456789012346',
                'is_pkp' => true,
                'is_active' => true,
                'grace_period_days' => 30,
                'default_payment' => 'credit',
                'member_since' => '2000-01-01',
                'balance' => 0.00,
                'email' => 'bandung@pinkservices.com',
                'phone' => '022-12345678',
                'website' => 'https://www.pinkservices.com',
                'address' => 'Jl. Asia Afrika No. 1, Bandung',
                'province_id' => $bandung ? $bandung->id : 2,
                'city_id' => $bandungCity ? $bandungCity->id : 2,
                'postal_code' => '40111',
                'industry' => 'Washroom Hygiene Service',
                'employee_count' => 50,
                'annual_revenue' => 1500000000.00,
                'description' => 'Cabang Bandung dari PT Pink Services Indonesia untuk melayani wilayah Jawa Barat.',
            ]
        ];

        foreach ($companies as $companyData) {
            Company::updateOrCreate(
                ['code' => $companyData['code']],
                $companyData
            );
        }

        $this->command->info('Company seeder completed successfully!');
    }
}