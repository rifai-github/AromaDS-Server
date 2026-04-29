<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\Building;
use App\Models\BuildingCustomer;
use App\Models\User;

class TestBuildingCustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get customers and user
        $customers = Customer::limit(3)->get();
        $user = User::first();
        
        if ($customers->count() >= 2 && $user) {
            // Create test buildings (malls)
            $mallGrandIndonesia = Building::create([
                'name' => 'Mall Grand Indonesia',
                'nama_gedung' => 'Mall Grand Indonesia',
                'address' => 'Jl. MH Thamrin No. 1',
                'alamat_1' => 'Jl. MH Thamrin No. 1',
                'alamat_2' => 'Menteng, Jakarta Pusat',
                'postal_code' => '10310',
                'kode_pos' => '10310',
                'phone_1' => '021-23580800',
                'phone_2' => '021-23580801',
                'fax' => '021-23580802',
                'total_floors' => 8,
                'total_area' => 50000.00,
                'description' => 'Premium shopping mall in Jakarta',
                'notes' => 'Luxury shopping destination',
                'created_by' => $user->id,
                'updated_by' => $user->id
            ]);

            $mallArthaGafa = Building::create([
                'name' => 'Mall Artha Gafa',
                'nama_gedung' => 'Mall Artha Gafa',
                'address' => 'Jl. Sudirman No. 52-53',
                'alamat_1' => 'Jl. Sudirman No. 52-53',
                'alamat_2' => 'Kebayoran Baru, Jakarta Selatan',
                'postal_code' => '12190',
                'kode_pos' => '12190',
                'phone_1' => '021-5150000',
                'phone_2' => '021-5150001',
                'fax' => '021-5150002',
                'total_floors' => 6,
                'total_area' => 30000.00,
                'description' => 'Business district shopping mall',
                'notes' => 'Corporate shopping center',
                'created_by' => $user->id,
                'updated_by' => $user->id
            ]);

            $mallOfIndonesia = Building::create([
                'name' => 'Mall of Indonesia',
                'nama_gedung' => 'Mall of Indonesia',
                'address' => 'Jl. Boulevard Barat Raya No. 1',
                'alamat_1' => 'Jl. Boulevard Barat Raya No. 1',
                'alamat_2' => 'Kelapa Gading, Jakarta Utara',
                'postal_code' => '14240',
                'kode_pos' => '14240',
                'phone_1' => '021-45850000',
                'phone_2' => '021-45850001',
                'fax' => '021-45850002',
                'total_floors' => 5,
                'total_area' => 40000.00,
                'description' => 'Family shopping mall',
                'notes' => 'Family-friendly shopping destination',
                'created_by' => $user->id,
                'updated_by' => $user->id
            ]);

            // Create many-to-many relationships
            // Customer 1 (Pak Dody) - Resto Jepang di multiple malls
            BuildingCustomer::create([
                'building_id' => $mallGrandIndonesia->id,
                'customer_id' => $customers[0]->id,
                'unit_number' => 'LG-101',
                'floor' => 'LG',
                'notes' => 'Resto Jepang di basement',
                'is_active' => true
            ]);

            BuildingCustomer::create([
                'building_id' => $mallArthaGafa->id,
                'customer_id' => $customers[0]->id,
                'unit_number' => '2F-205',
                'floor' => '2F',
                'notes' => 'Resto Jepang di lantai 2',
                'is_active' => true
            ]);

            // Customer 2 (Pak Wahyu) - Salon di multiple malls
            if ($customers->count() > 1) {
                BuildingCustomer::create([
                    'building_id' => $mallGrandIndonesia->id,
                    'customer_id' => $customers[1]->id,
                    'unit_number' => '3F-301',
                    'floor' => '3F',
                    'notes' => 'Salon di lantai 3',
                    'is_active' => true
                ]);

                BuildingCustomer::create([
                    'building_id' => $mallOfIndonesia->id,
                    'customer_id' => $customers[1]->id,
                    'unit_number' => '1F-105',
                    'floor' => '1F',
                    'notes' => 'Salon di lantai 1',
                    'is_active' => true
                ]);
            }

            // Customer 3 (Pak Yusuf) - Counter HP di multiple malls
            if ($customers->count() > 2) {
                BuildingCustomer::create([
                    'building_id' => $mallArthaGafa->id,
                    'customer_id' => $customers[2]->id,
                    'unit_number' => '1F-120',
                    'floor' => '1F',
                    'notes' => 'Counter HP di lantai 1',
                    'is_active' => true
                ]);

                BuildingCustomer::create([
                    'building_id' => $mallOfIndonesia->id,
                    'customer_id' => $customers[2]->id,
                    'unit_number' => '2F-210',
                    'floor' => '2F',
                    'notes' => 'Counter HP di lantai 2',
                    'is_active' => true
                ]);
            }
            
            $this->command->info('Test building-customer relationships created successfully!');
            $this->command->info('Mall Grand Indonesia: ' . $mallGrandIndonesia->name);
            $this->command->info('Mall Artha Gafa: ' . $mallArthaGafa->name);
            $this->command->info('Mall of Indonesia: ' . $mallOfIndonesia->name);
        } else {
            $this->command->error('Not enough customers or user found to create test relationships');
        }
    }
}