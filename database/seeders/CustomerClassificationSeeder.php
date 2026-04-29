<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MasterOption;
use App\Models\OptionDetail;

class CustomerClassificationSeeder extends Seeder
{
    public function run()
    {
        // 1. Create or Update Master Option
        $master = MasterOption::updateOrCreate(
            ['name' => 'Customer Classification'],
            [
                'description' => 'Classification for customers (e.g. Private, Public, Government)',
                'is_active' => 1
            ]
        );

        // 2. Create or Update Option Details
        $options = ['Private', 'Public', 'Government'];

        foreach ($options as $optionName) {
            OptionDetail::updateOrCreate(
                [
                    'master_option_id' => $master->id,
                    'option_name' => $optionName
                ],
                [
                    'is_active' => 1
                ]
            );
        }
    }
}
