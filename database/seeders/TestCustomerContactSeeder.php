<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\User;

class TestCustomerContactSeeder extends Seeder
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
            // Create test contact for this customer
            CustomerContact::create([
                'customer_id' => $customer->id,
                'salutation' => 'Mr.',
                'name' => 'Test Contact',
                'position' => 'Manager',
                'email' => 'test@example.com',
                'phone' => '08123456789',
                'is_active' => true,
                'created_by' => $user->id,
                'updated_by' => $user->id
            ]);
            
            $this->command->info('Test customer contact created for customer: ' . $customer->name);
        } else {
            $this->command->error('No customer or user found to create test contact');
        }
    }
}