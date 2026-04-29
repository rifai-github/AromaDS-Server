<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MasterOption;
use App\Models\OptionDetail;

class ContractFileTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or get Master Option for Contract File Type
        $masterOption = MasterOption::firstOrCreate(
            ['name' => 'Contract File Type'],
            [
                'description' => 'Tipe file yang diupload pada kontrak',
                'system_reserved' => true,
                'is_active' => true,
            ]
        );

        $fileTypes = [
            [
                'name' => 'Contract Scan',
                'code' => 'contract_scan',
            ],
            [
                'name' => 'Tax Scan',
                'code' => 'tax_scan',
            ],
            [
                'name' => 'NPWP Scan',
                'code' => 'npwp_scan',
            ],
            [
                'name' => 'Invoice',
                'code' => 'invoice',
            ],
            [
                'name' => 'Payment Proof',
                'code' => 'payment_proof',
            ],
            [
                'name' => 'Other',
                'code' => 'other',
            ],
        ];

        foreach ($fileTypes as $type) {
            OptionDetail::updateOrCreate(
                [
                    'master_option_id' => $masterOption->id,
                    'code' => $type['code'],
                ],
                [
                    'option_name' => $type['name'],
                    'label' => $type['name'],
                    'option_description' => $type['name'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Contract File Types seeded successfully!');
    }
}
