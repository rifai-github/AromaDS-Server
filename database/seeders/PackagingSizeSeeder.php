<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PackagingSize;
use App\Models\User;

class PackagingSizeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first user for created_by and updated_by
        $user = User::first();
        $userId = $user ? $user->id : 1;

        // Disable model events temporarily
        PackagingSize::unsetEventDispatcher();

        $packagingSizes = [
            [
                'name' => '50ml',
                'code' => '50ML',
                'description' => 'Small size packaging - 50 milliliters',
                'sort_order' => 1,
                'is_active' => true,
                'created_by' => $userId,
                'updated_by' => $userId
            ],
            [
                'name' => '100ml',
                'code' => '100ML',
                'description' => 'Medium size packaging - 100 milliliters',
                'sort_order' => 2,
                'is_active' => true,
                'created_by' => $userId,
                'updated_by' => $userId
            ],
            [
                'name' => '250ml',
                'code' => '250ML',
                'description' => 'Large size packaging - 250 milliliters',
                'sort_order' => 3,
                'is_active' => true,
                'created_by' => $userId,
                'updated_by' => $userId
            ],
            [
                'name' => '500ml',
                'code' => '500ML',
                'description' => 'Extra large size packaging - 500 milliliters',
                'sort_order' => 4,
                'is_active' => true,
                'created_by' => $userId,
                'updated_by' => $userId
            ],
            [
                'name' => '1000ml',
                'code' => '1000ML',
                'description' => 'Family size packaging - 1000 milliliters (1 liter)',
                'sort_order' => 5,
                'is_active' => true,
                'created_by' => $userId,
                'updated_by' => $userId
            ],
            [
                'name' => '2000ml',
                'code' => '2000ML',
                'description' => 'Commercial size packaging - 2000 milliliters (2 liters)',
                'sort_order' => 6,
                'is_active' => true,
                'created_by' => $userId,
                'updated_by' => $userId
            ]
        ];

        foreach ($packagingSizes as $packagingSize) {
            PackagingSize::create($packagingSize);
        }

        $this->command->info('Packaging sizes seeded successfully!');
    }
}