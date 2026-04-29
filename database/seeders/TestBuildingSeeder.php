<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\Building;
use App\Models\User;

class TestBuildingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first customer and user
        $customer = Customer::first();
        $user = User::first();
        
        if ($customer && $user) {
            // Create test building for this customer
            Building::create([
                'customer_id' => $customer->id,
                'name' => 'Test Building - ' . $customer->name,
                'nama_gedung' => 'Test Building - ' . $customer->name,
                'address' => 'Jl. Test Building No. 123',
                'postal_code' => '12345',
                'kode_pos' => '12345',
                'alamat_1' => 'Jl. Test Building No. 123',
                'alamat_2' => 'Kebayoran Baru, Jakarta Selatan',
                'phone_1' => '021-1234567',
                'phone_2' => '021-7654321',
                'fax' => '021-9876543',
                'total_floors' => 5,
                'total_area' => 1000.50,
                'description' => 'Test building for survey wizard',
                'notes' => 'This is a test building created for wizard testing',
                'created_by' => $user->id,
                'updated_by' => $user->id
            ]);
            
            $this->command->info('Test building created for customer: ' . $customer->name);
        } else {
            $this->command->error('No customer or user found to create test building');
        }
    }
}