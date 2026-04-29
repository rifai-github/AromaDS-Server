<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ContractTerminationReasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or get Master Option for Termination Reason
        $masterOption = \App\Models\MasterOption::firstOrCreate(
            ['name' => 'Termination Reason'],
            [
                'description' => 'Alasan pengajuan terminasi kontrak',
                'system_reserved' => false,
                'is_active' => true,
                'created_by' => null,
                'updated_by' => null,
            ]
        );

        $reasons = [
            'TIDAK BAYAR LEBIH DARI 3 BULAN',
            'PERUSAHAAN BANGKRUT',
            'GANTI VENDOR LAIN',
            'PERUSAHAAN PINDAH ALAMAT',
            'CUSTOMER TIDAK ADA NIAT BAIK',
            'CUSTOMER TIDAK PUAS DENGAN SERVICE',
            'PERUBAHAN NAMA PERUSAHAAN',
            'EFISIENSI BIAYA CUSTOMER (PERUSAHAAN)',
            'PENGGANTIAN KONTRAK',
            'TERMINATE JOB ORDER',
        ];

        foreach ($reasons as $reason) {
            \App\Models\OptionDetail::firstOrCreate(
                [
                    'master_option_id' => $masterOption->id,
                    'option_name' => $reason,
                ],
                [
                    'option_description' => $reason,
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Contract Termination Reasons seeded successfully!');
        $this->command->info("Master Option ID: {$masterOption->id}");
        $this->command->info("Total reasons: " . count($reasons));
    }
}
