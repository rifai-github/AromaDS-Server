<?php

namespace App\Services\Finance;

use App\Models\Finance\MarketingTarget;
use App\Models\Finance\CommissionLevel;
use App\Models\Finance\MarketingLevel;
use App\Models\Finance\CrVariable;
use App\Models\Finance\AchievementPeriod;
use App\Models\Finance\CommissionCalculation;
use App\Models\Finance\Achievement;
use App\Models\Contract;
use App\Models\User;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CommissionCalculationService
{
    /**
     * Calculate commission for a contract based on target achievement
     * 
     * Rules:
     * 1. Only calculate for installed and billable contracts (is_installed = true)
     * 2. Based on target achievement percentage (multi-level)
     * 3. Based on CR variable (default 90 days, configurable)
     * 4. Based on net value (if updated, use net_value; else use contract_value)
     * 5. Not accumulated - only count current period
     * 6. Different for new contract vs renewal contract
     */
    public function calculateCommissionForContract(Contract $contract, $cashReceiptDate = null): array
    {
        try {
            DB::beginTransaction();

            // Get contract data
            $marketingUser = $contract->marketing;
            if (!$marketingUser) {
                throw new \Exception("Contract does not have marketing user");
            }

            // Check if contract is installed
            if (!$contract->is_installed) {
                return [
                    'success' => false,
                    'message' => 'Contract is not installed yet. Commission can only be calculated for installed contracts.',
                    'commission' => null
                ];
            }

            // Determine contract type (new or renewal)
            $isRenewal = $this->isRenewalContract($contract);
            $targetType = $isRenewal ? 'renewal' : 'new';

            // Get current achievement period
            $achievementPeriod = $this->getCurrentAchievementPeriod();
            if (!$achievementPeriod) {
                throw new \Exception("No active achievement period found");
            }

            // Get marketing target for this user and period
            $marketingTarget = MarketingTarget::where('user_id', $marketingUser->id)
                ->where('achievement_period_id', $achievementPeriod->id)
                ->where('target_type', $targetType)
                ->where('is_locked', false)
                ->first();

            if (!$marketingTarget) {
                return [
                    'success' => false,
                    'message' => "No active marketing target found for user {$marketingUser->name} for {$targetType} contracts",
                    'commission' => null
                ];
            }

            // Get net value (use net_value if updated, else use contract_value)
            $contractValue = $contract->net_value ?? $contract->contract_value;
            
            // Update marketing target achieved amount
            $marketingTarget->achieved_amount += $contractValue;
            $marketingTarget->save();

            // Calculate achievement percentage
            $achievementPercentage = ($marketingTarget->achieved_amount / $marketingTarget->target_amount) * 100;

            // Get commission level based on achievement percentage
            $commissionLevel = CommissionLevel::getLevelByPercentage($achievementPercentage, $targetType);
            if (!$commissionLevel) {
                return [
                    'success' => false,
                    'message' => "No commission level found for achievement percentage: {$achievementPercentage}%",
                    'commission' => null
                ];
            }

            // Calculate commission amount
            $commissionRate = $commissionLevel->commission_rate; // e.g., 1.00% = 1.00
            $commissionAmount = $contractValue * ($commissionRate / 100);

            // Check CR variable (Cash Receipt period)
            $crVariable = CrVariable::getDefault();
            $crDays = $crVariable ? $crVariable->cr_days : 90; // Default 90 days
            
            // Calculate CR due date (if cash receipt date provided)
            $crDueDate = null;
            $isCrExpired = false;
            if ($cashReceiptDate) {
                $crDueDate = Carbon::parse($cashReceiptDate)->addDays($crDays);
                $isCrExpired = Carbon::now()->gt($crDueDate);
            }

            // If CR expired, commission is void
            if ($isCrExpired) {
                return [
                    'success' => false,
                    'message' => "Cash Receipt period expired. Commission void due to payment received after {$crDays} days.",
                    'commission' => null,
                    'is_cr_expired' => true
                ];
            }

            // Check if commission should go to different user (commission transfer)
            $commissionRecipient = $this->getCommissionRecipient($contract);
            $finalUserId = $commissionRecipient ? $commissionRecipient->id : $marketingUser->id;

            // Get marketing level for the user
            $marketingLevel = $this->getMarketingLevel($finalUserId);

            // Create commission calculation
            $commissionCalculation = CommissionCalculation::create([
                'user_id' => $finalUserId,
                'achievement_period_id' => $achievementPeriod->id,
                'marketing_target_id' => $marketingTarget->id,
                'contract_id' => $contract->id,
                'calculation_type' => $isRenewal ? 'renewal' : 'new',
                'base_amount' => $contract->contract_value,
                'net_value' => $contractValue,
                'commission_rate' => $commissionRate,
                'commission_level_id' => $commissionLevel->id,
                'commission_amount' => $commissionAmount,
                'bonus_amount' => 0,
                'penalty_amount' => 0,
                'final_amount' => $commissionAmount,
                'status' => 'pending',
                'calculation_date' => now(),
                'calculation_notes' => "Auto-calculated for contract {$contract->contract_number}. Achievement: {$achievementPercentage}%",
                'is_installed' => true,
                'cash_receipt_date' => $cashReceiptDate ? Carbon::parse($cashReceiptDate) : null,
                'cr_variable_id' => $crVariable ? $crVariable->id : null,
                'cr_due_date' => $crDueDate,
                'is_cr_expired' => $isCrExpired,
                'is_commission_void' => $isCrExpired,
                'marketing_level_id' => $marketingLevel ? $marketingLevel->id : null,
                'created_by' => auth()->id() ?? 1,
                'updated_by' => auth()->id() ?? 1
            ]);

            // Create achievement record
            Achievement::create([
                'user_id' => $finalUserId,
                'achievement_period_id' => $achievementPeriod->id,
                'contract_id' => $contract->id,
                'achievement_type' => $isRenewal ? 'renewal' : 'new',
                'target_amount' => $marketingTarget->target_amount,
                'achieved_amount' => $marketingTarget->achieved_amount,
                'commission_rate' => $commissionRate,
                'commission_level_id' => $commissionLevel->id,
                'commission_amount' => $commissionAmount,
                'status' => 'pending',
                'achievement_date' => now(),
                'cut_off_start_date' => $achievementPeriod->start_date->day,
                'cut_off_end_date' => $achievementPeriod->end_date->day,
                'cut_off_tolerance_days' => 5, // Default tolerance
                'is_installed' => true,
                'installed_date' => $contract->installed_date ?? now(),
                'created_by' => auth()->id() ?? 1,
                'updated_by' => auth()->id() ?? 1
            ]);

            DB::commit();

            Log::info("Commission calculated for contract {$contract->contract_number}: {$commissionAmount} for user {$finalUserId}");

            return [
                'success' => true,
                'message' => 'Commission calculated successfully',
                'commission' => $commissionCalculation,
                'amount' => $commissionAmount,
                'achievement_percentage' => $achievementPercentage,
                'commission_level' => $commissionLevel->level_name
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Commission calculation failed for contract {$contract->contract_number}: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to calculate commission: ' . $e->getMessage(),
                'commission' => null
            ];
        }
    }

    /**
     * Calculate commission when cash receipt is received
     */
    public function calculateCommissionOnCashReceipt(Invoice $invoice, $cashReceiptDate): array
    {
        try {
            $contract = $invoice->contract;
            if (!$contract) {
                throw new \Exception("Invoice does not have a contract");
            }

            // Check if commission already calculated
            $existingCalculation = CommissionCalculation::where('contract_id', $contract->id)
                ->where('status', '!=', 'void')
                ->first();

            if ($existingCalculation) {
                // Update existing calculation with cash receipt date
                $crVariable = CrVariable::getDefault();
                $crDays = $crVariable ? $crVariable->cr_days : 90;
                $crDueDate = Carbon::parse($cashReceiptDate)->addDays($crDays);
                $isCrExpired = Carbon::now()->gt($crDueDate);

                $existingCalculation->update([
                    'cash_receipt_date' => Carbon::parse($cashReceiptDate),
                    'cr_due_date' => $crDueDate,
                    'is_cr_expired' => $isCrExpired,
                    'is_commission_void' => $isCrExpired,
                    'status' => $isCrExpired ? 'void' : 'approved',
                    'updated_by' => auth()->id()
                ]);

                return [
                    'success' => !$isCrExpired,
                    'message' => $isCrExpired 
                        ? "Commission void: Cash receipt received after {$crDays} days" 
                        : "Commission approved: Cash receipt received within {$crDays} days",
                    'commission' => $existingCalculation,
                    'is_cr_expired' => $isCrExpired
                ];
            } else {
                // Calculate new commission
                return $this->calculateCommissionForContract($contract, $cashReceiptDate);
            }

        } catch (\Exception $e) {
            Log::error("Commission calculation on cash receipt failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to calculate commission: ' . $e->getMessage(),
                'commission' => null
            ];
        }
    }

    /**
     * Recalculate commission when net value is updated
     */
    public function recalculateCommissionForContract(Contract $contract): array
    {
        // Delete existing calculation
        CommissionCalculation::where('contract_id', $contract->id)
            ->where('status', 'pending')
            ->delete();

        // Recalculate
        return $this->calculateCommissionForContract($contract);
    }

    /**
     * Check if contract is renewal
     */
    private function isRenewalContract(Contract $contract): bool
    {
        // Check if contract has existing_contract_id or quotation_type is renewal
        if ($contract->quotation && $contract->quotation->quotation_type === 'renewal') {
            return true;
        }

        // Check if there's a previous contract for same customer
        $previousContract = Contract::where('customer_id', $contract->customer_id)
            ->where('id', '<', $contract->id)
            ->where('status', 'active')
            ->exists();

        return $previousContract;
    }

    /**
     * Get current achievement period
     */
    private function getCurrentAchievementPeriod()
    {
        return AchievementPeriod::current()->first();
    }

    /**
     * Get commission recipient (if transferred)
     */
    private function getCommissionRecipient(Contract $contract): ?User
    {
        // Check if there's a commission transfer for this contract
        $transfer = \App\Models\Finance\CommissionTransfer::where('contract_id', $contract->id)
            ->where('status', 'approved')
            ->latest()
            ->first();

        if ($transfer) {
            return $transfer->toUser;
        }

        // Check contract commission_recipient_id
        if ($contract->commission_recipient_id) {
            return User::find($contract->commission_recipient_id);
        }

        return null;
    }

    /**
     * Get marketing level for user
     */
    private function getMarketingLevel(int $userId): ?MarketingLevel
    {
        // Get user's marketing level from pivot table
        $marketingLevel = MarketingLevel::whereHas('users', function($query) use ($userId) {
            $query->where('users.id', $userId);
        })
        ->active()
        ->ordered()
        ->first();

        // If no level assigned, return default level or null
        if (!$marketingLevel) {
            return MarketingLevel::active()->ordered()->first();
        }

        return $marketingLevel;
    }
}

