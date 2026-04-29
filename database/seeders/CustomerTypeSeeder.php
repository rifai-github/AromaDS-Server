<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CustomerType;

class CustomerTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customerTypes = [
            [
                'name' => 'Hotel',
                'description' => 'Hotel and accommodation business',
                'is_active' => true
            ],
            [
                'name' => 'Hospital',
                'description' => 'Healthcare and medical facilities',
                'is_active' => true
            ],
            [
                'name' => 'Restaurant',
                'description' => 'Food and beverage establishments',
                'is_active' => true
            ],
            [
                'name' => 'Office',
                'description' => 'Corporate office buildings',
                'is_active' => true
            ],
            [
                'name' => 'Mall',
                'description' => 'Shopping centers and malls',
                'is_active' => true
            ],
            [
                'name' => 'Automotive',
                'description' => 'Automotive dealerships and showrooms',
                'is_active' => true
            ],
            [
                'name' => 'Warehouse',
                'description' => 'Storage and logistics facilities',
                'is_active' => true
            ],
            [
                'name' => 'Residential',
                'description' => 'Residential buildings and apartments',
                'is_active' => true
            ],
            [
                'name' => 'Educational',
                'description' => 'Schools, universities, and educational institutions',
                'is_active' => true
            ],
            [
                'name' => 'Government',
                'description' => 'Government offices and facilities',
                'is_active' => true
            ]
        ];

        foreach ($customerTypes as $type) {
            CustomerType::create($type);
        }
    }
}