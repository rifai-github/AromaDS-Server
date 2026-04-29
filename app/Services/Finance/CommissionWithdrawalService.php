<?php

namespace App\Services\Finance;

use App\Models\Finance\CommissionWithdrawal;
use App\Models\Finance\FinanceLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommissionWithdrawalService
{
    /**
     * Create commission withdrawal request
     */
    public function createWithdrawalRequest(array $data): array
    {
        try {
            DB::beginTransaction();

            $userId = $data['user_id'] ?? auth()->id();
            $amount = $data['amount'];

            // Validate withdrawal request
            $this->validateWithdrawalRequest($userId, $amount);

            // Check if user has sufficient balance
            $userBalance = $this->getUserBalance($userId);
            if ($userBalance < $amount) {
                throw new \Exception('Insufficient balance for withdrawal');
            }

            $withdrawal = CommissionWithdrawal::create([
                'user_id' => $userId,
                'amount' => $amount,
                'status' => 'pending',
                'requested_date' => now(),
            ]);

            // Log the withdrawal request
            FinanceLog::create([
                'user_id' => $userId,
                'transaction_type' => 'withdrawal',
                'amount' => -$amount, // Negative amount for withdrawal
                'balance' => $userBalance - $amount,
                'notes' => "Withdrawal request created for amount: {$amount}",
            ]);

            DB::commit();

            return [
                'success' => true,
                'withdrawal' => $withdrawal,
                'message' => 'Withdrawal request created successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Withdrawal request creation failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to create withdrawal request: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Approve withdrawal request
     */
    public function approveWithdrawal(int $withdrawalId, int $approvedBy): array
    {
        try {
            DB::beginTransaction();

            $withdrawal = CommissionWithdrawal::findOrFail($withdrawalId);
            
            if ($withdrawal->status !== 'pending') {
                throw new \Exception('Only pending withdrawals can be approved');
            }

            // Check if user still has sufficient balance
            $userBalance = $this->getUserBalance($withdrawal->user_id);
            if ($userBalance < $withdrawal->amount) {
                throw new \Exception('User no longer has sufficient balance for withdrawal');
            }

            $withdrawal->update([
                'status' => 'approved',
                'approved_date' => now(),
                'approved_by' => $approvedBy,
            ]);

            // Update user balance
            $this->updateUserBalance($withdrawal->user_id, -$withdrawal->amount);

            // Log the approval
            FinanceLog::create([
                'user_id' => $withdrawal->user_id,
                'transaction_type' => 'withdrawal',
                'amount' => -$withdrawal->amount,
                'balance' => $userBalance - $withdrawal->amount,
                'notes' => "Withdrawal approved by user ID: {$approvedBy}",
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Withdrawal approved successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Withdrawal approval failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to approve withdrawal: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Reject withdrawal request
     */
    public function rejectWithdrawal(int $withdrawalId, int $rejectedBy, string $reason = null): array
    {
        try {
            DB::beginTransaction();

            $withdrawal = CommissionWithdrawal::findOrFail($withdrawalId);
            
            if ($withdrawal->status !== 'pending') {
                throw new \Exception('Only pending withdrawals can be rejected');
            }

            $withdrawal->update([
                'status' => 'rejected',
                'rejected_date' => now(),
                'rejected_by' => $rejectedBy,
                'rejection_reason' => $reason,
            ]);

            // Log the rejection
            FinanceLog::create([
                'user_id' => $withdrawal->user_id,
                'transaction_type' => 'withdrawal',
                'amount' => 0,
                'balance' => $this->getUserBalance($withdrawal->user_id),
                'notes' => "Withdrawal rejected by user ID: {$rejectedBy}. Reason: " . ($reason ?? 'No reason provided'),
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Withdrawal rejected successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Withdrawal rejection failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to reject withdrawal: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Process withdrawal (mark as completed)
     */
    public function processWithdrawal(int $withdrawalId, int $processedBy): array
    {
        try {
            DB::beginTransaction();

            $withdrawal = CommissionWithdrawal::findOrFail($withdrawalId);
            
            if ($withdrawal->status !== 'approved') {
                throw new \Exception('Only approved withdrawals can be processed');
            }

            $withdrawal->update([
                'status' => 'completed',
                'processed_date' => now(),
                'processed_by' => $processedBy,
            ]);

            // Log the processing
            FinanceLog::create([
                'user_id' => $withdrawal->user_id,
                'transaction_type' => 'withdrawal',
                'amount' => 0,
                'balance' => $this->getUserBalance($withdrawal->user_id),
                'notes' => "Withdrawal processed by user ID: {$processedBy}",
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Withdrawal processed successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Withdrawal processing failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to process withdrawal: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get user's withdrawal history
     */
    public function getUserWithdrawalHistory(int $userId, int $limit = 20): array
    {
        $withdrawals = CommissionWithdrawal::where('user_id', $userId)
            ->orderBy('requested_date', 'desc')
            ->limit($limit)
            ->get();

        return [
            'success' => true,
            'withdrawals' => $withdrawals
        ];
    }

    /**
     * Get pending withdrawal requests
     */
    public function getPendingWithdrawals(): array
    {
        $withdrawals = CommissionWithdrawal::with('user')
            ->where('status', 'pending')
            ->orderBy('requested_date', 'asc')
            ->get();

        return [
            'success' => true,
            'withdrawals' => $withdrawals
        ];
    }

    /**
     * Get withdrawal statistics
     */
    public function getWithdrawalStatistics(): array
    {
        $total = CommissionWithdrawal::count();
        $pending = CommissionWithdrawal::where('status', 'pending')->count();
        $approved = CommissionWithdrawal::where('status', 'approved')->count();
        $rejected = CommissionWithdrawal::where('status', 'rejected')->count();
        $completed = CommissionWithdrawal::where('status', 'completed')->count();

        $totalAmount = CommissionWithdrawal::sum('amount');
        $pendingAmount = CommissionWithdrawal::where('status', 'pending')->sum('amount');
        $approvedAmount = CommissionWithdrawal::where('status', 'approved')->sum('amount');
        $completedAmount = CommissionWithdrawal::where('status', 'completed')->sum('amount');

        return [
            'total_withdrawals' => $total,
            'pending_withdrawals' => $pending,
            'approved_withdrawals' => $approved,
            'rejected_withdrawals' => $rejected,
            'completed_withdrawals' => $completed,
            'total_amount' => $totalAmount,
            'pending_amount' => $pendingAmount,
            'approved_amount' => $approvedAmount,
            'completed_amount' => $completedAmount,
            'approval_rate' => $total > 0 ? (($approved + $completed) / $total) * 100 : 0,
        ];
    }

    /**
     * Get user's withdrawal statistics
     */
    public function getUserWithdrawalStatistics(int $userId): array
    {
        $withdrawals = CommissionWithdrawal::where('user_id', $userId)->get();

        $total = $withdrawals->count();
        $pending = $withdrawals->where('status', 'pending')->count();
        $approved = $withdrawals->where('status', 'approved')->count();
        $rejected = $withdrawals->where('status', 'rejected')->count();
        $completed = $withdrawals->where('status', 'completed')->count();

        $totalAmount = $withdrawals->sum('amount');
        $completedAmount = $withdrawals->where('status', 'completed')->sum('amount');

        return [
            'user_id' => $userId,
            'total_withdrawals' => $total,
            'pending_withdrawals' => $pending,
            'approved_withdrawals' => $approved,
            'rejected_withdrawals' => $rejected,
            'completed_withdrawals' => $completed,
            'total_amount' => $totalAmount,
            'completed_amount' => $completedAmount,
            'success_rate' => $total > 0 ? ($completed / $total) * 100 : 0,
        ];
    }

    /**
     * Validate withdrawal request
     */
    private function validateWithdrawalRequest(int $userId, float $amount): void
    {
        if ($amount <= 0) {
            throw new \Exception('Withdrawal amount must be greater than 0');
        }

        if ($amount < 100) { // Minimum withdrawal amount
            throw new \Exception('Minimum withdrawal amount is 100');
        }

        $user = User::find($userId);
        if (!$user) {
            throw new \Exception('User not found');
        }

        // Check if user has any pending withdrawals
        $pendingWithdrawals = CommissionWithdrawal::where('user_id', $userId)
            ->where('status', 'pending')
            ->count();

        if ($pendingWithdrawals > 0) {
            throw new \Exception('User has pending withdrawal requests');
        }
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
     * Update user balance
     */
    private function updateUserBalance(int $userId, float $amount): void
    {
        $currentBalance = $this->getUserBalance($userId);
        $newBalance = $currentBalance + $amount;

        FinanceLog::create([
            'user_id' => $userId,
            'transaction_type' => 'withdrawal',
            'amount' => $amount,
            'balance' => $newBalance,
            'notes' => 'Balance updated due to withdrawal',
        ]);
    }

    /**
     * Get withdrawal requests for approval
     */
    public function getWithdrawalRequestsForApproval(): array
    {
        $withdrawals = CommissionWithdrawal::with(['user'])
            ->where('status', 'pending')
            ->orderBy('requested_date', 'asc')
            ->get();

        return [
            'success' => true,
            'withdrawals' => $withdrawals
        ];
    }

    /**
     * Bulk approve withdrawals
     */
    public function bulkApproveWithdrawals(array $withdrawalIds, int $approvedBy): array
    {
        try {
            DB::beginTransaction();

            $approved = 0;
            $errors = [];

            foreach ($withdrawalIds as $withdrawalId) {
                try {
                    $result = $this->approveWithdrawal($withdrawalId, $approvedBy);
                    if ($result['success']) {
                        $approved++;
                    } else {
                        $errors[] = "Withdrawal ID {$withdrawalId}: " . $result['message'];
                    }
                } catch (\Exception $e) {
                    $errors[] = "Withdrawal ID {$withdrawalId}: " . $e->getMessage();
                }
            }

            DB::commit();

            return [
                'success' => true,
                'approved_count' => $approved,
                'errors' => $errors,
                'message' => "Successfully approved {$approved} withdrawals"
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk withdrawal approval failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to approve withdrawals: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get withdrawal trends
     */
    public function getWithdrawalTrends(string $period = 'month'): array
    {
        $startDate = $this->getPeriodStartDate($period);
        
        $withdrawals = CommissionWithdrawal::where('created_at', '>=', $startDate)
            ->get()
            ->groupBy(function ($withdrawal) use ($period) {
                return $withdrawal->created_at->format($this->getDateFormat($period));
            });

        $trends = [];
        foreach ($withdrawals as $date => $group) {
            $trends[] = [
                'date' => $date,
                'count' => $group->count(),
                'total_amount' => $group->sum('amount'),
                'approved_count' => $group->where('status', 'approved')->count(),
                'completed_count' => $group->where('status', 'completed')->count(),
            ];
        }

        return [
            'success' => true,
            'trends' => $trends
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

    /**
     * Get date format for grouping
     */
    private function getDateFormat(string $period): string
    {
        switch ($period) {
            case 'week':
                return 'Y-W';
            case 'month':
                return 'Y-m';
            case 'quarter':
                return 'Y-Q';
            case 'year':
                return 'Y';
            default:
                return 'Y-m';
        }
    }
}
