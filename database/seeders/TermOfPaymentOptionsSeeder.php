<?php

namespace Database\Seeders;

use App\Models\MasterOption;
use App\Models\OptionDetail;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TermOfPaymentOptionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'system.master-term-of-payments' => 'Access Master Term of Payment',
            'system.master-term-of-payments.create' => 'Create Master Term of Payment',
            'system.master-term-of-payments.update' => 'Update Master Term of Payment',
            'system.master-term-of-payments.delete' => 'Delete Master Term of Payment',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name],
                [
                    'description' => $description,
                    'system_reserved' => true,
                    'is_active' => true,
                ]
            );
        }

        $managementRoles = Role::where('name', 'like', 'Management%')
            ->orWhere('name', 'like', 'Admin%')
            ->orWhere('name', 'Admin')
            ->get();

        $permissionIds = Permission::whereIn('name', array_keys($permissions))->pluck('id');
        foreach ($managementRoles as $role) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert([
                    'role_id' => $role->id,
                    'permission_id' => $permissionId,
                ]);
            }
        }

        $masterOption = MasterOption::firstOrCreate(
            ['name' => 'Term of Payment'],
            [
                'description' => 'Pilihan Terms of Payment untuk quotation dan kontrak',
                'system_reserved' => true,
                'is_active' => true,
            ]
        );

        $terms = [
            ['value' => '1 bulan 1x', 'label' => '1 bulan 1x', 'code' => '1', 'description' => 'Pembayaran setiap 1 bulan'],
            ['value' => '2 bulan 1x', 'label' => '2 bulan 1x', 'code' => '2', 'description' => 'Pembayaran setiap 2 bulan'],
            ['value' => '3 bulan 1x', 'label' => '3 bulan 1x', 'code' => '3', 'description' => 'Pembayaran setiap 3 bulan'],
            ['value' => '4 bulan 1x', 'label' => '4 bulan 1x', 'code' => '4', 'description' => 'Pembayaran setiap 4 bulan'],
            ['value' => '5 bulan 1x', 'label' => '5 bulan 1x', 'code' => '5', 'description' => 'Pembayaran setiap 5 bulan'],
            ['value' => '6 bulan 1x', 'label' => '6 bulan 1x', 'code' => '6', 'description' => 'Pembayaran setiap 6 bulan'],
            ['value' => 'Tahunan', 'label' => '1x Advance', 'code' => 'advance', 'description' => 'Pembayaran 1x advance untuk seluruh periode kontrak'],
            ['value' => '7 bulan 1x', 'label' => '7 bulan 1x', 'code' => '7', 'description' => 'Pembayaran setiap 7 bulan'],
            ['value' => '8 bulan 1x', 'label' => '8 bulan 1x', 'code' => '8', 'description' => 'Pembayaran setiap 8 bulan'],
            ['value' => '9 bulan 1x', 'label' => '9 bulan 1x', 'code' => '9', 'description' => 'Pembayaran setiap 9 bulan'],
            ['value' => '10 bulan 1x', 'label' => '10 bulan 1x', 'code' => '10', 'description' => 'Pembayaran setiap 10 bulan'],
            ['value' => '11 bulan 1x', 'label' => '11 bulan 1x', 'code' => '11', 'description' => 'Pembayaran setiap 11 bulan'],
            ['value' => '13 bulan 1x', 'label' => '13 bulan 1x', 'code' => '13', 'description' => 'Pembayaran setiap 13 bulan'],
            ['value' => '14 bulan 1x', 'label' => '14 bulan 1x', 'code' => '14', 'description' => 'Pembayaran setiap 14 bulan'],
            ['value' => '15 bulan 1x', 'label' => '15 bulan 1x', 'code' => '15', 'description' => 'Pembayaran setiap 15 bulan'],
            ['value' => '16 bulan 1x', 'label' => '16 bulan 1x', 'code' => '16', 'description' => 'Pembayaran setiap 16 bulan'],
            ['value' => '17 bulan 1x', 'label' => '17 bulan 1x', 'code' => '17', 'description' => 'Pembayaran setiap 17 bulan'],
            ['value' => '18 bulan 1x', 'label' => '18 bulan 1x', 'code' => '18', 'description' => 'Pembayaran setiap 18 bulan'],
            ['value' => '19 bulan 1x', 'label' => '19 bulan 1x', 'code' => '19', 'description' => 'Pembayaran setiap 19 bulan'],
            ['value' => '20 bulan 1x', 'label' => '20 bulan 1x', 'code' => '20', 'description' => 'Pembayaran setiap 20 bulan'],
            ['value' => '21 bulan 1x', 'label' => '21 bulan 1x', 'code' => '21', 'description' => 'Pembayaran setiap 21 bulan'],
            ['value' => '22 bulan 1x', 'label' => '22 bulan 1x', 'code' => '22', 'description' => 'Pembayaran setiap 22 bulan'],
            ['value' => '23 bulan 1x', 'label' => '23 bulan 1x', 'code' => '23', 'description' => 'Pembayaran setiap 23 bulan'],
            ['value' => '2 tahunan', 'label' => '2 tahunan', 'code' => '24', 'description' => 'Pembayaran setiap 2 tahun'],
            ['value' => '3 tahunan', 'label' => '3 tahunan', 'code' => '36', 'description' => 'Pembayaran setiap 3 tahun'],
        ];

        foreach ($terms as $term) {
            OptionDetail::updateOrCreate(
                [
                    'master_option_id' => $masterOption->id,
                    'option_name' => $term['value'],
                ],
                [
                    'label' => $term['label'],
                    'code' => $term['code'],
                    'option_description' => $term['description'],
                    'is_active' => true,
                ]
            );
        }

        $this->command?->info('Term of Payment options seeded successfully.');
    }
}
