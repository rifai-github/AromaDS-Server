<?php

namespace App\Services\Finance;

use App\Models\Contract;
use App\Models\Finance\Achievement;
use App\Models\Finance\AchievementPeriod;
use App\Models\Finance\CommissionCalculation;
use App\Models\Finance\CommissionLevel;
use App\Models\Finance\MarketingTarget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Aturan periode komisi dari MOM 17 Nov 2026:
 *  - p3: komisi berasal dari kontrak EFEKTIF; periodenya ditentukan BA Date (bisa diubah lewat
 *        Job Operation), bukan tanggal komisi dihitung.
 *  - p4: target & level komisi per PERIODE. Level ditentukan % pencapaian total periode
 *        terhadap target, lalu berlaku untuk semua kontrak di periode itu. Pencapaian kontrak
 *        bisa dimajukan/dimundurkan antar periode dan ikut pindah saat Switch Marketing.
 *  - p5: Lock Commission per periode; setelah di-LOCK tak ada perubahan/pemindahan.
 */
class CommissionPeriodService
{
    public const AUTOMATIC_TYPES = ['new', 'renewal'];

    /**
     * Tanggal efektif kontrak: BA Date job pertama (install/service) bila ada, lalu Tanggal
     * Install sebagai cadangan untuk kontrak yang belum punya BA.
     */
    public function effectiveDate(Contract $contract): ?Carbon
    {
        $baDate = $this->baDate($contract);
        if ($baDate) {
            return $baDate;
        }

        $installDate = $contract->installed_date ?? $contract->install_date;

        return $installDate ? Carbon::parse($installDate)->startOfDay() : null;
    }

    public function baDate(Contract $contract): ?Carbon
    {
        try {
            if (! Schema::hasTable('job_schedules')) {
                return null;
            }

            $baDate = $contract->actual_start_date;
        } catch (\Throwable $e) {
            // Skema lama/test tanpa relasi job: anggap belum ada BA.
            return null;
        }

        return $baDate ? Carbon::parse($baDate)->startOfDay() : null;
    }

    /**
     * Periode tempat kontrak dihitung: periode aktif yang memuat tanggal efektifnya. Bila tak
     * ada periode itu atau periodenya sudah di-LOCK, dipakai periode berjalan (bila belum LOCK).
     */
    public function periodForContract(Contract $contract): ?AchievementPeriod
    {
        $date = $this->effectiveDate($contract);

        if ($date) {
            $period = AchievementPeriod::query()
                ->where('status', 'active')
                ->whereDate('start_date', '<=', $date->toDateString())
                ->whereDate('end_date', '>=', $date->toDateString())
                ->orderByDesc('start_date')
                ->first();

            if ($period && ! $period->isCommissionLocked()) {
                return $period;
            }

            // Jawaban klien 22 Nov (#5): periode BA Date sudah di-LOCK -> kontrak (yang sudah ada
            // pembayarannya) masuk PERIODE SELANJUTNYA: periode aktif pertama yang belum di-LOCK
            // dan dimulai setelah periode itu.
            if ($period) {
                $next = AchievementPeriod::query()
                    ->where('status', 'active')
                    ->whereDate('start_date', '>', $period->end_date->toDateString())
                    ->orderBy('start_date')
                    ->get()
                    ->first(fn (AchievementPeriod $candidate) => ! $candidate->isCommissionLocked());

                if ($next) {
                    return $next;
                }
            }
        }

        $current = AchievementPeriod::current()->first();

        return $current && ! $current->isCommissionLocked() ? $current : null;
    }

    /**
     * Hitung ulang pencapaian & level satu Marketing Target (user + periode + tipe) dari semua
     * komisi otomatisnya. Setiap kontrak dihitung sekali walau komisinya dipecah oleh Commission
     * Transfer. Baris hasil transfer dan komisi yang sudah Paid tidak diubah nominalnya.
     *
     * @return array{achieved: float, percentage: float, level: ?CommissionLevel, skipped: ?string}
     */
    public function rebalanceTarget(?MarketingTarget $target): array
    {
        if (! $target) {
            return ['achieved' => 0.0, 'percentage' => 0.0, 'level' => null, 'skipped' => 'no_target'];
        }

        $period = $target->achievementPeriod;
        if ($target->is_locked || $period?->isCommissionLocked()) {
            return [
                'achieved' => (float) $target->achieved_amount,
                'percentage' => 0.0,
                'level' => null,
                'skipped' => 'locked',
            ];
        }

        $calculations = CommissionCalculation::query()
            ->where('marketing_target_id', $target->id)
            ->whereIn('calculation_type', self::AUTOMATIC_TYPES)
            ->where('status', '!=', 'cancelled')
            ->orderBy('id')
            ->get();

        $valuePerContract = $calculations
            ->groupBy('contract_id')
            ->map(fn ($rows) => (float) ($rows->first()->net_value ?? $rows->first()->base_amount));

        $achieved = round((float) $valuePerContract->sum(), 2);
        $targetAmount = (float) $target->target_amount;
        $percentage = $targetAmount > 0 ? ($achieved / $targetAmount) * 100 : 0.0;

        $target->achieved_amount = $achieved;
        $target->save();

        $level = CommissionLevel::getLevelByPercentage($percentage, $target->target_type);
        if (! $level) {
            return ['achieved' => $achieved, 'percentage' => $percentage, 'level' => null, 'skipped' => 'no_level'];
        }

        $rate = (float) $level->commission_rate;
        $amountPerContract = [];

        foreach ($calculations as $calculation) {
            $net = (float) ($calculation->net_value ?? $calculation->base_amount);
            $amount = round($net * ($rate / 100), 2);
            $amountPerContract[$calculation->contract_id] ??= $amount;

            if ($calculation->commission_transfer_id || $calculation->status === 'paid') {
                continue;
            }

            $attributes = [
                'commission_rate' => $rate,
                'commission_level_id' => $level->id,
                'commission_amount' => $amount,
            ];

            if ($calculation->status === 'over_period') {
                // Nominal Over Period hanya diubah admin; yang ikut naik/turun hanya batas atasnya.
                $attributes['original_amount'] = $amount;
            } else {
                $attributes['final_amount'] = round($amount + (float) $calculation->bonus_amount - (float) $calculation->penalty_amount, 2);
            }

            $calculation->fill($attributes);
            if ($calculation->isDirty()) {
                $calculation->save();
            }
        }

        $status = CommissionCalculationService::achievementStatus($achieved, $targetAmount);

        foreach ($amountPerContract as $contractId => $amount) {
            Achievement::query()
                ->where('contract_id', $contractId)
                ->where('achievement_period_id', $target->achievement_period_id)
                ->where('achievement_type', $target->target_type)
                ->update([
                    'user_id' => $target->user_id,
                    'target_amount' => $targetAmount,
                    'achieved_amount' => $achieved,
                    'commission_rate' => $rate,
                    'commission_level_id' => $level->id,
                    'commission_amount' => $amount,
                    'status' => $status,
                ]);
        }

        return ['achieved' => $achieved, 'percentage' => $percentage, 'level' => $level, 'skipped' => null];
    }

    /**
     * Admin memajukan/memundurkan komisi kontrak ke periode lain ("tabungan komisi").
     * Semua komisi otomatis kontrak itu di target yang sama ikut pindah, lalu kedua target
     * dihitung ulang.
     */
    public function moveToPeriod(CommissionCalculation $calculation, AchievementPeriod $destination): void
    {
        if (! in_array($calculation->calculation_type, self::AUTOMATIC_TYPES, true)) {
            throw new \RuntimeException('Hanya komisi otomatis (kontrak New/Renewal) yang bisa dipindah periode.');
        }

        if ((int) $calculation->achievement_period_id === (int) $destination->id) {
            throw new \RuntimeException('Komisi sudah berada di periode '.$destination->period_name.'.');
        }

        if ($calculation->isLocked()) {
            throw new \RuntimeException('Periode/target asal komisi ini sudah di-LOCK; komisi tidak bisa dipindah.');
        }

        if ($destination->isCommissionLocked()) {
            throw new \RuntimeException('Periode tujuan '.$destination->period_name.' sudah di-LOCK.');
        }

        $sourceTarget = $calculation->marketingTarget;
        $ownerId = (int) ($sourceTarget?->user_id ?? $calculation->user_id);
        $targetType = $sourceTarget?->target_type ?? $calculation->calculation_type;

        $siblings = $this->contractCalculations($calculation->contract_id, $calculation->marketing_target_id);
        $this->guardMovable($siblings);

        $destinationTarget = $this->openTarget($ownerId, $destination->id, $targetType);
        if (! $destinationTarget) {
            $owner = \App\Models\User::find($ownerId);
            throw new \RuntimeException('Belum ada Marketing Target '.ucfirst($targetType).' yang aktif untuk '.($owner?->name ?? 'marketing ini').' di periode '.$destination->period_name.'. Buat targetnya dulu.');
        }

        DB::transaction(function () use ($siblings, $calculation, $destination, $destinationTarget, $sourceTarget) {
            $note = 'Dipindah ke periode '.$destination->period_name.' oleh '.(auth()->user()?->name ?? 'system').' pada '.now()->format('Y-m-d H:i');

            foreach ($siblings as $sibling) {
                $sibling->update([
                    'achievement_period_id' => $destination->id,
                    'marketing_target_id' => $destinationTarget->id,
                    'calculation_notes' => trim(($sibling->calculation_notes ?? '')."\n".$note),
                    'updated_by' => auth()->id() ?? $sibling->updated_by,
                ]);
            }

            Achievement::query()
                ->where('contract_id', $calculation->contract_id)
                ->where('achievement_period_id', $sourceTarget?->achievement_period_id ?? $calculation->getOriginal('achievement_period_id'))
                ->whereIn('achievement_type', self::AUTOMATIC_TYPES)
                ->update(['achievement_period_id' => $destination->id]);

            $this->rebalanceTarget($sourceTarget?->fresh());
            $this->rebalanceTarget($destinationTarget->fresh());
        });
    }

    /**
     * Switch Marketing (MOM 17 Nov 2026 p4): pencapaian kontrak ikut pindah ke marketing baru di
     * periode yang sama, dan level komisi kedua marketing dihitung ulang. Dipanggil di dalam
     * transaksi ContractAssigned::execute, jadi exception membatalkan switch-nya.
     */
    public function reassignMarketing(Contract $contract, int $newUserId): void
    {
        $calculations = CommissionCalculation::query()
            ->where('contract_id', $contract->id)
            ->whereIn('calculation_type', self::AUTOMATIC_TYPES)
            ->where('status', '!=', 'cancelled')
            ->orderBy('id')
            ->get();

        if ($calculations->isEmpty()) {
            return;
        }

        foreach ($calculations as $calculation) {
            if ($calculation->isLocked()) {
                throw new \RuntimeException('Komisi kontrak ini sudah masuk periode/target yang di-LOCK; kontrak tidak bisa dipindah ke marketing lain.');
            }
        }

        $this->guardMovable($calculations);

        $touchedTargets = [];

        foreach ($calculations->groupBy('marketing_target_id') as $targetId => $rows) {
            $sourceTarget = $targetId ? MarketingTarget::find($targetId) : null;
            $first = $rows->first();
            $periodId = (int) ($sourceTarget?->achievement_period_id ?? $first->achievement_period_id);
            $targetType = $sourceTarget?->target_type ?? $first->calculation_type;
            $previousOwnerId = (int) ($sourceTarget?->user_id ?? $first->user_id);

            if ($previousOwnerId === $newUserId) {
                continue;
            }

            $destinationTarget = $this->openTarget($newUserId, $periodId, $targetType);
            if (! $destinationTarget) {
                $newUser = \App\Models\User::find($newUserId);
                $periodName = AchievementPeriod::find($periodId)?->period_name ?? '#'.$periodId;
                throw new \RuntimeException('Belum ada Marketing Target '.ucfirst($targetType).' yang aktif untuk '.($newUser?->name ?? 'marketing baru').' di periode '.$periodName.'. Buat targetnya dulu supaya pencapaian kontrak bisa ikut pindah.');
            }

            foreach ($rows as $row) {
                $attributes = ['marketing_target_id' => $destinationTarget->id, 'updated_by' => auth()->id() ?? $row->updated_by];

                // Komisi yang sudah dialihkan lewat Commission Transfer tetap milik penerimanya.
                if (! $row->commission_transfer_id && (int) $row->user_id === $previousOwnerId) {
                    $attributes['user_id'] = $newUserId;
                }

                $row->update($attributes);
            }

            Achievement::query()
                ->where('contract_id', $contract->id)
                ->where('achievement_period_id', $periodId)
                ->where('achievement_type', $targetType)
                ->update(['user_id' => $newUserId]);

            $touchedTargets[] = $sourceTarget;
            $touchedTargets[] = $destinationTarget;
        }

        foreach ($touchedTargets as $target) {
            $this->rebalanceTarget($target?->fresh());
        }
    }

    /**
     * BA Date kontrak diisi/diubah (Job Operation, done job web/APK). Komisi yang sudah ada
     * dipindah ke periode BA Date bila periodenya beda dan belum di-LOCK; kontrak aktif yang
     * belum punya komisi otomatis dihitung sekarang.
     */
    public function syncContractToBaDate(Contract $contract): ?string
    {
        $calculation = CommissionCalculation::query()
            ->where('contract_id', $contract->id)
            ->whereIn('calculation_type', self::AUTOMATIC_TYPES)
            ->where('status', '!=', 'cancelled')
            ->orderBy('id')
            ->first();

        if (! $calculation) {
            if ($contract->contract_status !== 'active') {
                return null;
            }

            $result = app(CommissionCalculationService::class)->calculateCommissionForContract($contract);

            return $result['success'] || ! empty($result['already_calculated']) ? null : $result['message'];
        }

        if ($calculation->isLocked() || in_array($calculation->status, ['paid'], true)) {
            return null;
        }

        $destination = $this->periodForContract($contract);
        if (! $destination || (int) $destination->id === (int) $calculation->achievement_period_id) {
            return null;
        }

        try {
            $this->moveToPeriod($calculation, $destination);
        } catch (\RuntimeException $e) {
            Log::warning("Komisi kontrak {$contract->contract_number} tidak ikut pindah ke periode BA Date: ".$e->getMessage());

            return $e->getMessage();
        }

        return null;
    }

    public function openTarget(int $userId, int $periodId, string $targetType): ?MarketingTarget
    {
        return MarketingTarget::query()
            ->where('user_id', $userId)
            ->where('achievement_period_id', $periodId)
            ->where('target_type', $targetType)
            ->where('is_locked', false)
            ->first();
    }

    private function contractCalculations($contractId, $targetId)
    {
        return CommissionCalculation::query()
            ->where('contract_id', $contractId)
            ->whereIn('calculation_type', self::AUTOMATIC_TYPES)
            ->where('status', '!=', 'cancelled')
            ->when($targetId, fn ($query) => $query->where('marketing_target_id', $targetId))
            ->orderBy('id')
            ->get();
    }

    private function guardMovable($calculations): void
    {
        foreach ($calculations as $calculation) {
            if ($calculation->status === 'paid') {
                throw new \RuntimeException("Komisi #{$calculation->id} sudah Paid; pencapaiannya tidak bisa dipindah lagi.");
            }
        }
    }
}
