<?php

namespace App\Services\Finance;

use App\Models\Finance\RenewalContractAssignment;
use App\Models\Finance\AchievementPeriod;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RenewalAssignmentService
{
    /**
     * Assign renewal contracts to marketing user
     */
    public function assignRenewalContracts(array $data): array
    {
        try {
            DB::beginTransaction();

            $assignment = RenewalContractAssignment::create([
                'achievement_period_id' => $data['achievement_period_id'],
                'user_id' => $data['user_id'],
                'contract_number_from' => $data['contract_number_from'] ?? null,
                'contract_number_to' => $data['contract_number_to'] ?? null,
                'target_amount' => $data['target_amount'] ?? 0,
                'is_locked' => false,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id() ?? 1,
                'updated_by' => auth()->id() ?? 1
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Renewal contract assignment created successfully',
                'assignment' => $assignment
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to assign renewal contracts: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to assign renewal contracts: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Lock assignment (prevent further changes)
     */
    public function lockAssignment(int $assignmentId, int $lockedBy): array
    {
        try {
            $assignment = RenewalContractAssignment::findOrFail($assignmentId);
            $assignment->lock($lockedBy);

            return [
                'success' => true,
                'message' => 'Assignment locked successfully',
                'assignment' => $assignment
            ];

        } catch (\Exception $e) {
            Log::error("Failed to lock assignment: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to lock assignment: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if contract number is in assigned range
     */
    public function isContractInRange(string $contractNumber, int $periodId, int $userId): bool
    {
        $assignment = RenewalContractAssignment::where('user_id', $userId)
            ->where('achievement_period_id', $periodId)
            ->where('is_locked', true)
            ->first();

        if (!$assignment) {
            return false;
        }

        return $assignment->isContractInRange($contractNumber);
    }

    /**
     * Get assignment for user in period
     */
    public function getAssignmentForUser(int $userId, int $periodId): ?RenewalContractAssignment
    {
        return RenewalContractAssignment::where('user_id', $userId)
            ->where('achievement_period_id', $periodId)
            ->first();
    }
}

