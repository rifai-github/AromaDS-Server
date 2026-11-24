<?php

namespace App\Reports;

/**
 * Daftar laporan menu Report (spec Halaman Report 24 Nov 2026), urut sesuai menu.
 */
class ReportRegistry
{
    /** @var array<int, class-string<BusinessReport>> */
    public const REPORTS = [
        ContractListReport::class,
        SuspendReport::class,
        LockServicePotentialReport::class,
        AgreementSignReport::class,
        RentalServiceReport::class,
        GenerateInvoiceReport::class,
        // Klien 24 Nov 2026 (implementasi): SQ yang aroma ruangannya masih kosong.
        SqAromaEmptyReport::class,
    ];

    /** @return array<int, BusinessReport> */
    public static function all(): array
    {
        return array_map(fn (string $class) => app($class), self::REPORTS);
    }

    /**
     * Laporan yang boleh dilihat user (untuk sidebar). Admin/Management lolos seperti
     * middleware CheckPermission; role lain butuh permission reports.{key}.
     *
     * @return array<int, BusinessReport>
     */
    public static function visibleFor(?\App\Models\User $user): array
    {
        if (! $user) {
            return [];
        }

        $bypass = $user->hasRole('Admin') || $user->hasRole('super_admin') || $user->hasRoleStartingWith('Management');

        return array_values(array_filter(
            self::all(),
            fn (BusinessReport $report) => $bypass || $user->hasPermission($report->permission())
        ));
    }

    public static function find(string $key): ?BusinessReport
    {
        foreach (self::all() as $report) {
            if ($report->key() === $key) {
                return $report;
            }
        }

        return null;
    }
}
