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

            $target = MarketingTarget::updateOrCreate(
                [
                    'user_id' => $data['user_id'],
                    'achievement_period_id' => $data['achievement_period_id'],
                    'target_type' => $data['target_type']
                ],
                [
                    'target_amount' => $data['target_amount'],
                    'achieved_amount' => $data['achieved_amount'] ?? 0,
                    'is_locked' => $data['is_locked'] ?? false,
                    'lock_date' => $data['is_locked'] ? now() : null,
                    'locked_by' => $data['is_locked'] ? (auth()->id() ?? $data['locked_by'] ?? null) : null,
                    'notes' => $data['notes'] ?? null,
                    'created_by' => auth()->id() ?? 1,
                    'updated_by' => auth()->id() ?? 1
                ]
            );

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

