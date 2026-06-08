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
            $this->fixedIntervalTerm('1 bulan 1x', 1),
            $this->fixedIntervalTerm('2 bulan 1x', 2),
            $this->fixedIntervalTerm('3 bulan 1x', 3),
            $this->fixedIntervalTerm('4 bulan 1x', 4),
            $this->fixedIntervalTerm('5 bulan 1x', 5),
            $this->fixedIntervalTerm('6 bulan 1x', 6),
            $this->advanceTerm(),
            $this->perContractPeriodTerm(2),
            $this->perContractPeriodTerm(3),
            $this->perContractPeriodTerm(4),
            $this->fixedIntervalTerm('7 bulan 1x', 7),
            $this->fixedIntervalTerm('8 bulan 1x', 8),
            $this->fixedIntervalTerm('9 bulan 1x', 9),
            $this->fixedIntervalTerm('10 bulan 1x', 10),
            $this->fixedIntervalTerm('11 bulan 1x', 11),
            $this->fixedIntervalTerm('13 bulan 1x', 13),
            $this->fixedIntervalTerm('14 bulan 1x', 14),
            $this->fixedIntervalTerm('15 bulan 1x', 15),
            $this->fixedIntervalTerm('16 bulan 1x', 16),
            $this->fixedIntervalTerm('17 bulan 1x', 17),
            $this->fixedIntervalTerm('18 bulan 1x', 18),
            $this->fixedIntervalTerm('19 bulan 1x', 19),
            $this->fixedIntervalTerm('20 bulan 1x', 20),
            $this->fixedIntervalTerm('21 bulan 1x', 21),
            $this->fixedIntervalTerm('22 bulan 1x', 22),
            $this->fixedIntervalTerm('23 bulan 1x', 23),
            $this->fixedIntervalTerm('2 tahunan', 24, 'Pembayaran setiap 2 tahun'),
            $this->fixedIntervalTerm('3 tahunan', 36, 'Pembayaran setiap 3 tahun'),
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

    private function fixedIntervalTerm(string $value, int $months, ?string $description = null): array
    {
        return [
            'value' => $value,
            'label' => $value,
            'code' => (string) $months,
            'description' => json_encode([
                'description' => $description ?? "Pembayaran setiap {$months} bulan",
                'billing_mode' => 'fixed_interval',
                'months' => $months,
                'payment_count' => null,
            ]),
        ];
    }

    private function advanceTerm(): array
    {
        return [
            'value' => 'Tahunan',
            'label' => '1x Advance',
            'code' => 'advance',
            'description' => json_encode([
                'description' => 'Pembayaran 1x advance untuk seluruh periode kontrak',
                'billing_mode' => 'advance',
                'months' => null,
                'payment_count' => null,
            ]),
        ];
    }

    private function perContractPeriodTerm(int $paymentCount): array
    {
        return [
            'value' => "{$paymentCount}x per periode contract",
            'label' => "{$paymentCount}x dalam Periode Contract",
            'code' => 'installments',
            'description' => json_encode([
                'description' => "Pembayaran {$paymentCount}x dibagi rata dalam satu periode kontrak",
                'billing_mode' => 'per_contract_period',
                'months' => null,
                'payment_count' => $paymentCount,
            ]),
        ];
    }
}
