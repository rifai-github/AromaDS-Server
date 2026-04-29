<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Bank;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $banks = [
            [
                'bank_code' => 'BCA',
                'bank_name' => 'Bank Central Asia',
                'name' => 'Bank Central Asia',
                'is_active' => true,
            ],
            [
                'bank_code' => 'BNI',
                'bank_name' => 'Bank Negara Indonesia',
                'name' => 'Bank Negara Indonesia',
                'is_active' => true,
            ],
            [
                'bank_code' => 'BRI',
                'bank_name' => 'Bank Rakyat Indonesia',
                'name' => 'Bank Rakyat Indonesia',
                'is_active' => true,
            ],
            [
                'bank_code' => 'MDR',
                'bank_name' => 'Bank Mandiri',
                'name' => 'Bank Mandiri',
                'is_active' => true,
            ],
            [
                'bank_code' => 'CIM',
                'bank_name' => 'Bank CIMB Niaga',
                'name' => 'Bank CIMB Niaga',
                'is_active' => true,
            ],
            [
                'bank_code' => 'DBS',
                'bank_name' => 'Bank DBS Indonesia',
                'name' => 'Bank DBS Indonesia',
                'is_active' => true,
            ],
            [
                'bank_code' => 'HSBC',
                'bank_name' => 'Bank HSBC Indonesia',
                'name' => 'Bank HSBC Indonesia',
                'is_active' => true,
            ],
            [
                'bank_code' => 'OCBC',
                'bank_name' => 'Bank OCBC NISP',
                'name' => 'Bank OCBC NISP',
                'is_active' => true,
            ],
            // Add more banks here
            [
                'bank_code' => 'BTN',
                'bank_name' => 'Bank Tabungan Negara',
                'name' => 'Bank Tabungan Negara',
                'is_active' => true,
            ],
            [
                'bank_code' => 'PERMATA',
                'bank_name' => 'Bank Permata',
                'name' => 'Bank Permata',
                'is_active' => true,
            ],
        ];

        foreach ($banks as $bankData) {
            Bank::updateOrCreate(
                ['bank_code' => $bankData['bank_code']],
                array_merge($bankData, [
                    'created_by' => 28,
                    'updated_by' => 28,
                ])
            );
        }

        $this->command->info('Banks seeded successfully!');
    }
}