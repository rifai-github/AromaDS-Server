<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MasterOption;
use App\Models\OptionDetail;

class ProductUnitOptionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first user for created_by and updated_by
        $user = \App\Models\User::first();
        if (!$user) {
            $this->command->error('No users found. Please run UserSeeder first.');
            return;
        }

        // Create Product Units master option
        $unitOption = MasterOption::updateOrCreate(
            ['name' => 'Product Units'],
            [
                'description' => 'Unit measurement options for products and rentals',
                'is_active' => true,
                'system_reserved' => true,
                'created_by' => $user->id,
                'updated_by' => $user->id
            ]
        );

        // Define product unit options
        $units = [
            'Pcs',           // Pieces
            'Set',           // Set
            'Box',           // Box
            'Pack',          // Pack
            'Bottle',        // Bottle
            'Can',           // Can
            'Tube',          // Tube
            'Roll',          // Roll
            'Sheet',         // Sheet
            'ml',            // Milliliter
            'Liter',         // Liter
            'Kg',            // Kilogram
            'Gram',          // Gram
            'Meter',         // Meter
            'cm',            // Centimeter
            'mm',            // Millimeter
            'Inch',          // Inch
            'Oz',            // Ounce
            'Lb',            // Pound
            'Gallon'         // Gallon
        ];

        // Create option details for each unit
        foreach ($units as $index => $unit) {
            OptionDetail::updateOrCreate(
                [
                    'master_option_id' => $unitOption->id,
                    'option_name' => $unit
                ],
                [
                    'option_description' => $unit . ' unit',
                    'is_active' => true
                ]
            );
        }
    }
}
