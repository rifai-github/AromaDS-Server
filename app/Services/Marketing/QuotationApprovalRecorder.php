<?php

namespace App\Services\Marketing;

use App\Models\Quotation;
use App\Models\QuotationApproval;
use Illuminate\Support\Facades\Log;

/**
 * Keeps a QuotationApproval audit row alongside the quotation status.
 *
 * Before tiered approval, approve/reject only flipped `quotations.status` and
 * left no trace of who was asked or why. These helpers are deliberately
 * best-effort: an audit failure must never block the approval itself.
 */
class QuotationApprovalRecorder
{
    public function openPending(Quotation $quotation, array $evaluation, ?int $requestedBy): void
    {
        $this->guard(function () use ($quotation, $evaluation, $requestedBy) {
            $this->closeStalePending($quotation);

            QuotationApproval::create([
                'quotation_id' => $quotation->id,
                'approval_type' => 'bottom_price',
                'required_level_id' => $evaluation['required_level']['id'] ?? null,
                'required_level_code' => $evaluation['required_level']['level_code'] ?? null,
                'status' => 'pending',
                'requested_by' => $requestedBy,
                'requested_at' => now(),
                'approval_data' => $evaluation,
            ]);
        });
    }

    public function markApproved(Quotation $quotation, ?int $approvedBy, ?string $notes = null): void
    {
        $this->guard(function () use ($quotation, $approvedBy, $notes) {
            $pending = $this->latestPending($quotation);

            $pending?->approve($approvedBy, $notes);
        });
    }

    public function markRejected(Quotation $quotation, ?int $approvedBy, string $reason): void
    {
        $this->guard(function () use ($quotation, $approvedBy, $reason) {
            $pending = $this->latestPending($quotation);

            $pending?->reject($approvedBy, $reason);
        });
    }

    private function latestPending(Quotation $quotation): ?QuotationApproval
    {
        return QuotationApproval::query()
            ->where('quotation_id', $quotation->id)
            ->where('approval_type', 'bottom_price')
            ->where('status', 'pending')
            ->latest('id')
            ->first();
    }

    /** A re-finalized quotation supersedes any request still hanging around. */
    private function closeStalePending(Quotation $quotation): void
    {
        QuotationApproval::query()
            ->where('quotation_id', $quotation->id)
            ->where('approval_type', 'bottom_price')
            ->where('status', 'pending')
            ->update([
                'status' => 'rejected',
                'rejection_reason' => 'Superseded by a newer submission.',
                'approved_at' => now(),
            ]);
    }

    private function guard(callable $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            Log::warning('Quotation approval audit row could not be written', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
