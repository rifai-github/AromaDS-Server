<?php

namespace App\Services\Finance;

use App\Models\Finance\SalesCommission;
use App\Models\Finance\CommissionCondition;
use App\Models\Finance\FinanceLog;
use App\Models\Invoice;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalesCommissionService
{
    /**
     * Calculate commission for a user based on invoice
     */
    public function calculateCommission(int $userId, int $invoiceId, string $commissionType = 'percentage', float $amount = 0): array
    {
        try {
            DB::beginTransaction();

            $user = User::findOrFail($userId);
            $invoice = Invoice::with(['contract'])->findOrFail($invoiceId);
            
            if (!$invoice->contract) {
                throw new \Exception('Invoice must have a contract to calculate commission');
            }

            $contract = $invoice->contract;
            $commissionAmount = 0;

            // Calculate commission based on type
            if ($commissionType === 'percentage') {
                $commissionRate = $this->getCommissionRate($userId, $contract->id);
                $commissionAmount = $invoice->total_amount * ($commissionRate / 100);
            } else {
                $commissionAmount = $amount;
            }

            // Create commission record
            $commission = SalesCommission::create([
                'user_id' => $userId,
                'contract_id' => $contract->id,
                'commission_type' => $commissionType,
                'amount' => $commissionAmount,
                'status' => 'pending',
                'calculated_date' => now(),
            ]);

            // Create commission condition
            CommissionCondition::create([
                'commission_id' => $commission->id,
                'invoice_id' => $invoiceId,
                'payment_date' => $invoice->due_date,
                'days_overdue' => $this->calculateDaysOverdue($invoice->due_date),
                'is_valid' => true,
            ]);

            // Log the transaction
            FinanceLog::create([
                'user_id' => $userId,
                'transaction_type' => 'commission',
                'amount' => $commissionAmount,
                'balance' => $this->getUserBalance($userId) + $commissionAmount,
                'notes' => "Commission calculated for invoice #{$invoice->invoice_number}",
            ]);

            DB::commit();

            return [
                'success' => true,
                'commission' => $commission,
                'amount' => $commissionAmount,
                'message' => 'Commission calculated successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Commission calculation failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to calculate commission: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get commission rate for user and contract
     */
    public function getCommissionRate(int $userId, int $contractId): float
    {
        // Default commission rate - can be customized based on business rules
        $defaultRate = 5.0; // 5%
        
        // You can implement more complex logic here based on:
        // - User role/level
        // - Contract type
        // - Historical performance
        // - Company policies
        
        return $defaultRate;
    }

    /**
     * Calculate days overdue
     */
    private function calculateDaysOverdue($dueDate): int
    {
        $dueDate = \Carbon\Carbon::parse($dueDate);
        $today = \Carbon\Carbon::now();
        
        if ($today->gt($dueDate)) {
            return $today->diffInDays($dueDate);
        }
        
        return 0;
    }

    /**
     * Get user's current balance
     */
    private function getUserBalance(int $userId): float
    {
        $lastLog = FinanceLog::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->first();
            
        return $lastLog ? $lastLog->balance : 0;
    }

    /**
     * Approve commission
     */
    public function approveCommission(int $commissionId, int $approvedBy): array
    {
        try {
            $commission = SalesCommission::findOrFail($commissionId);
            
            if ($commission->status !== 'pending') {
                throw new \Exception('Only pending commissions can be approved');
            }

            $commission->update([
                'status' => 'approved',
                'approved_by' => $approvedBy,
                'approved_at' => now(),
            ]);

            // Update user balance
            $this->updateUserBalance($commission->user_id, $commission->amount);

            return [
                'success' => true,
                'message' => 'Commission approved successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Commission approval failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to approve commission: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Reject commission
     */
    public function rejectCommission(int $commissionId, int $rejectedBy, string $reason = null): array
    {
        try {
            $commission = SalesCommission::findOrFail($commissionId);
            
            if ($commission->status !== 'pending') {
                throw new \Exception('Only pending commissions can be rejected');
            }

            $commission->update([
                'status' => 'rejected',
                'rejected_by' => $rejectedBy,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);

            return [
                'success' => true,
                'message' => 'Commission rejected successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Commission rejection failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to reject commission: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update user balance
     */
    private function updateUserBalance(int $userId, float $amount): void
    {
        $currentBalance = $this->getUserBalance($userId);
        $newBalance = $currentBalance + $amount;

        FinanceLog::create([
            'user_id' => $userId,
            'transaction_type' => 'commission',
            'amount' => $amount,
            'balance' => $newBalance,
            'notes' => 'Commission approved and added to balance',
        ]);
    }

    /**
     * Get commission statistics for user
     */
    public function getCommissionStatistics(int $userId, string $period = 'month'): array
    {
        $startDate = $this->getPeriodStartDate($period);
        
        $commissions = SalesCommission::where('user_id', $userId)
            ->where('created_at', '>=', $startDate)
            ->get();

        $totalAmount = $commissions->sum('amount');
        $approvedAmount = $commissions->where('status', 'approved')->sum('amount');
        $pendingAmount = $commissions->where('status', 'pending')->sum('amount');
        $rejectedAmount = $commissions->where('status', 'rejected')->sum('amount');

        return [
            'total_commissions' => $commissions->count(),
            'total_amount' => $totalAmount,
            'approved_amount' => $approvedAmount,
            'pending_amount' => $pendingAmount,
            'rejected_amount' => $rejectedAmount,
            'approval_rate' => $commissions->count() > 0 ? ($commissions->where('status', 'approved')->count() / $commissions->count()) * 100 : 0,
        ];
    }

    /**
     * Get period start date
     */
    private function getPeriodStartDate(string $period): string
    {
        switch ($period) {
            case 'week':
                return now()->subWeek()->toDateString();
            case 'month':
                return now()->subMonth()->toDateString();
            case 'quarter':
                return now()->subQuarter()->toDateString();
            case 'year':
                return now()->subYear()->toDateString();
            default:
                return now()->subMonth()->toDateString();
        }
    }
}
