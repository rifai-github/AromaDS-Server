<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class MissingPermissionsSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            // Marketing Approve
            ['name' => 'marketing.aroma-changes.approve', 'description' => 'Approve Aroma Switching'],
            ['name' => 'marketing.contract-terminations.approve', 'description' => 'Approve Contract Termination'],
            ['name' => 'marketing.lost-unit-reports.approve', 'description' => 'Approve Lost Unit Report'],
            
            // Marketing Downloads/Prints if needed (Add others here if found missing)
            ['name' => 'marketing.lost-unit-reports.download', 'description' => 'Download Lost Unit Report'],
            ['name' => 'marketing.lost-unit-reports.print', 'description' => 'Print Lost Unit Report'],
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(
                ['name' => $p['name']],
                ['description' => $p['description'], 'is_active' => true]
            );
        }
    }
}
