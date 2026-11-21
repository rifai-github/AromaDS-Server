<?php

namespace App\Services\Finance;

use App\Models\Finance\MarketingTarget;
use App\Models\Finance\AchievementPeriod;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MarketingTargetService
{
    /**
     * Create or update marketing target for user
     */
    public function createOrUpdateTarget(array $data): array
    {
        try {
            DB::beginTransaction();

            // The create form has no lock checkbox, so the key is usually absent.
            $isLocked = (bool) ($data['is_locked'] ?? false);

            $key = [
                'user_id' => $data['user_id'],
                'achievement_period_id' => $data['achievement_period_id'],
                'target_type' => $data['target_type']
            ];

            // Satu target per marketing + periode + tipe (unique key, ikut menghitung baris
            // terhapus). Dulu Create memakai updateOrCreate sehingga membuat target "baru" untuk
            // periode yang sama menimpa target lama: pencapaian kembali 0 dan lock terlepas,
            // padahal komisi/achievement kontraknya masih ada (UAT 17 Nov: Elly 7jt -> 100jt).
            $existing = MarketingTarget::withTrashed()->where($key)->lockForUpdate()->first();
            if ($existing && ! $existing->trashed()) {
                DB::rollBack();
                $existing->loadMissing('user', 'achievementPeriod');

                return [
                    'success' => false,
                    'duplicate' => true,
                    'target' => $existing,
                    'message' => 'Marketing Target '.ucfirst($data['target_type'])
                        .' untuk '.($existing->user->name ?? 'marketing ini')
                        .' periode '.($existing->achievementPeriod->period_name ?? '#'.$existing->achievement_period_id)
                        .' sudah ada (target Rp '.number_format((float) $existing->target_amount, 0, ',', '.')
                        .', tercapai Rp '.number_format((float) $existing->achieved_amount, 0, ',', '.')
                        .'). Ubah nilai target lewat tombol Edit pada target tersebut.',
                ];
            }

            $attributes = [
                'target_amount' => $data['target_amount'],
                'is_locked' => $isLocked,
                'lock_date' => $isLocked ? now() : null,
                'locked_by' => $isLocked ? (auth()->id() ?? $data['locked_by'] ?? null) : null,
                'notes' => $data['notes'] ?? null,
                'updated_by' => auth()->id() ?? 1
            ];

            if ($existing) {
                // Target yang pernah dihapus dipulihkan dengan pencapaiannya tetap: komisi
                // kontrak yang sudah dihitung masih menunjuk baris ini.
                $existing->restore();
                $existing->update($attributes);
                $target = $existing;
            } else {
                $target = MarketingTarget::create($key + $attributes + [
                    'achieved_amount' => $data['achieved_amount'] ?? 0,
                    'created_by' => auth()->id() ?? 1,
                ]);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Marketing target saved successfully',
                'target' => $target
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to save marketing target: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to save marketing target: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Lock target (prevent further changes)
     */
    public function lockTarget(int $targetId, int $lockedBy): array
    {
        try {
            $target = MarketingTarget::findOrFail($targetId);
            $target->lock($lockedBy);

            return [
                'success' => true,
                'message' => 'Target locked successfully',
                'target' => $target
            ];

        } catch (\Exception $e) {
            Log::error("Failed to lock target: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to lock target: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate achievement percentage
     */
    public function calculateAchievement(int $userId, int $periodId, string $targetType): array
    {
        $target = MarketingTarget::where('user_id', $userId)
            ->where('achievement_period_id', $periodId)
            ->where('target_type', $targetType)
            ->first();

        if (!$target) {
            return [
                'success' => false,
                'message' => 'Target not found',
                'achievement_percentage' => 0,
                'achieved_amount' => 0,
                'target_amount' => 0
            ];
        }

        $percentage = $target->achievement_percentage;

        return [
            'success' => true,
            'achievement_percentage' => $percentage,
            'achieved_amount' => $target->achieved_amount,
            'target_amount' => $target->target_amount,
            'target' => $target
        ];
    }

    /**
     * Get target summary for user
     */
    public function getTargetSummary(int $userId, int $periodId): array
    {
        $newTarget = MarketingTarget::where('user_id', $userId)
            ->where('achievement_period_id', $periodId)
            ->where('target_type', 'new')
            ->first();

        $renewalTarget = MarketingTarget::where('user_id', $userId)
            ->where('achievement_period_id', $periodId)
            ->where('target_type', 'renewal')
            ->first();

        return [
            'new_target' => $newTarget ? [
                'target_amount' => $newTarget->target_amount,
                'achieved_amount' => $newTarget->achieved_amount,
                'percentage' => $newTarget->achievement_percentage,
                'is_locked' => $newTarget->is_locked
            ] : null,
            'renewal_target' => $renewalTarget ? [
                'target_amount' => $renewalTarget->target_amount,
                'achieved_amount' => $renewalTarget->achieved_amount,
                'percentage' => $renewalTarget->achievement_percentage,
                'is_locked' => $renewalTarget->is_locked
            ] : null
        ];
    }
}

