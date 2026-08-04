<?php

namespace App\Console\Commands;

use App\Models\Branch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Repairs branches whose city_id/province_id were bulk-overwritten with a
 * bogus placeholder (city_id=158, province_id=21 — a soft-deleted, corrupted
 * province row) on 2026-07-15. This breaks DocumentNumberService's building
 * to branch matching (getBranchFromLocation), so every auto-generated
 * document number for the affected branch silently falls back to the
 * default 'JKT' prefix — e.g. an SBY contract's invoice coming out as
 * JKT-INV instead of SBY-INV.
 *
 * The correct city/province mapping below was hand-verified against the
 * `cities`/`provinces` tables on 2026-08-04 (see chat history). Branches not
 * listed here (ADS, BSD, JKP, MJKT1, LPG, SMG) share the same corruption but
 * were intentionally left out — their correct city is ambiguous (shares the
 * JKT branch's exact address) or unresolvable (no matching active city row)
 * and needs a manual decision before scripting a fix.
 */
class FixCorruptedBranchLocations extends Command
{
    protected $signature = 'branches:fix-corrupted-locations
                            {--apply : Apply the fix. Default is dry-run}';

    protected $description = 'Restore correct city_id/province_id for branches corrupted by the 2026-07-15 bad import';

    private const BRANCH_FIXES = [
        'SBY' => ['city_id' => 610, 'province_id' => 29, 'label' => 'KOTA SURABAYA / JAWA TIMUR'],
        'BAL' => ['city_id' => 265, 'province_id' => 31, 'label' => 'DENPASAR / BALI'],
        'BDG' => ['city_id' => 597, 'province_id' => 27, 'label' => 'KOTA BANDUNG / JAWA BARAT'],
        'BTM' => ['city_id' => 553, 'province_id' => 54, 'label' => 'BATAM'],
        'MDN' => ['city_id' => 638, 'province_id' => 23, 'label' => 'KOTA MEDAN / SUMATERA UTARA'],
        'MKS' => ['city_id' => 640, 'province_id' => 41, 'label' => 'KOTA MAKASSAR / SULAWESI SELATAN'],
        'PKU' => ['city_id' => 83,  'province_id' => 24, 'label' => 'PEKANBARU / RIAU'],
        'PLB' => ['city_id' => 639, 'province_id' => 25, 'label' => 'KOTA PALEMBANG / SUMATERA SELATAN'],
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $rows = [];
        $toApply = [];

        foreach (self::BRANCH_FIXES as $code => $fix) {
            $branch = Branch::withTrashed()->where('code', $code)->first();

            if (!$branch) {
                $rows[] = [$code, '-', '-', 'branch not found, skipped'];
                continue;
            }

            if ((int) $branch->city_id === $fix['city_id'] && (int) $branch->province_id === $fix['province_id']) {
                $rows[] = [$code, "{$branch->city_id}/{$branch->province_id}", "{$fix['city_id']}/{$fix['province_id']}", 'already correct'];
                continue;
            }

            $rows[] = [$code, "{$branch->city_id}/{$branch->province_id}", "{$fix['city_id']}/{$fix['province_id']} ({$fix['label']})", $apply ? 'updating' : 'would update'];
            $toApply[$code] = $fix;
        }

        $this->table(['Branch', 'Current city/province', 'Correct city/province', 'Action'], $rows);

        if (!$apply) {
            $this->info(sprintf('DRY-RUN — %d branch(es) would be updated. Re-run with --apply to write.', count($toApply)));
            return self::SUCCESS;
        }

        foreach ($toApply as $code => $fix) {
            Branch::where('code', $code)->update([
                'city_id' => $fix['city_id'],
                'province_id' => $fix['province_id'],
            ]);

            Log::info('FixCorruptedBranchLocations: branch location repaired', [
                'branch_code' => $code,
                'city_id' => $fix['city_id'],
                'province_id' => $fix['province_id'],
            ]);
        }

        $this->info(sprintf('Done — %d branch(es) updated.', count($toApply)));

        return self::SUCCESS;
    }
}
