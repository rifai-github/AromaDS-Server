<?php

namespace App\Services\Marketing;

use App\Models\Quotation;
use App\Models\QuotationApprovalLevel;
use App\Models\RentalBottomPrice;

class QuotationBottomPriceEvaluator
{
    public function evaluate(Quotation $quotation): array
    {
        $quotation->loadMissing('quotationDetails.masterRental');

        $details = $quotation->quotationDetails;
        $branchId = $quotation->branch_id;
        $offerType = $quotation->rental_unit ?: 'bulan';

        if ($details->isEmpty()) {
            $requiredLevel = QuotationApprovalLevel::highest();

            return [
                'requires_approval' => true,
                'issues' => [$this->withRequiredLevel([
                    'type' => 'missing_details',
                    'message' => 'Quotation has no rental details.',
                ], $requiredLevel)],
                'required_level' => $requiredLevel ? $this->levelPayload($requiredLevel) : null,
                'required_permission' => $requiredLevel?->permission_name,
            ];
        }

        $missingBottomPriceIssues = [];
        $lines = [];

        foreach ($details as $detail) {
            // Free giveaway lines carry no price to discount, so they never
            // trigger bottom-price approval on their own.
            if ((float) $detail->unit_price <= 0) {
                continue;
            }

            $bottomPrice = null;

            if ($branchId && $detail->master_rental_id) {
                $bottomPrice = RentalBottomPrice::active()
                    ->where('master_rental_id', $detail->master_rental_id)
                    ->where('branch_id', $branchId)
                    ->where('offer_type', $offerType)
                    ->first();
            }

            // A missing or zero floor means this line's contribution to the
            // total floor cannot be computed at all, so the whole quotation
            // takes the most senior level regardless of the total comparison.
            if (! $bottomPrice || (float) $bottomPrice->bottom_price <= 0) {
                $missingBottomPriceIssues[] = $this->withRequiredLevel([
                    'type' => 'missing_bottom_price',
                    'quotation_detail_id' => $detail->id,
                    'master_rental_id' => $detail->master_rental_id,
                    'rental_name' => $detail->masterRental?->rental_name,
                    'room_name' => $detail->room_name,
                    'unit_price' => (float) $detail->unit_price,
                    'bottom_price' => null,
                    'discount_percentage' => null,
                    'message' => 'Bottom price is not configured for this rental, branch, and offer type.',
                ], QuotationApprovalLevel::highest());

                continue;
            }

            $lines[] = ['detail' => $detail, 'bottom_price' => (float) $bottomPrice->bottom_price];
        }

        if (! empty($missingBottomPriceIssues)) {
            $requiredLevel = QuotationApprovalLevel::highest();

            return [
                'requires_approval' => true,
                'issues' => $missingBottomPriceIssues,
                'required_level' => $requiredLevel ? $this->levelPayload($requiredLevel) : null,
                'required_permission' => $requiredLevel?->permission_name,
            ];
        }

        if (empty($lines)) {
            // Nothing but free lines - nothing to approve.
            return [
                'requires_approval' => false,
                'issues' => [],
                'required_level' => null,
                'required_permission' => null,
            ];
        }

        // The approval decision is based on the quotation as a whole, not on
        // any single line: sum the paid lines' actual total against the sum
        // of their bottom-price floors (each floor times that line's quantity).
        $totalQuotation = 0.0;
        $totalBottom = 0.0;

        foreach ($lines as $line) {
            $quantity = (float) ($line['detail']->quantity ?: 1);
            $totalQuotation += (float) $line['detail']->total_price;
            $totalBottom += $line['bottom_price'] * $quantity;
        }

        // Total quotation at or above the total floor: no approval needed.
        if ($totalQuotation >= $totalBottom) {
            return [
                'requires_approval' => false,
                'issues' => [],
                'required_level' => null,
                'required_permission' => null,
            ];
        }

        $discount = round((1 - ($totalQuotation / $totalBottom)) * 100, 4);
        $requiredLevel = QuotationApprovalLevel::resolveForDiscount($discount);

        $issues = array_map(fn (array $line) => $this->withRequiredLevel([
            'type' => 'below_bottom_price',
            'quotation_detail_id' => $line['detail']->id,
            'master_rental_id' => $line['detail']->master_rental_id,
            'rental_name' => $line['detail']->masterRental?->rental_name,
            'room_name' => $line['detail']->room_name,
            'unit_price' => (float) $line['detail']->unit_price,
            'bottom_price' => $line['bottom_price'],
            'discount_percentage' => $discount,
            'message' => 'Total quotation price is below total bottom price.',
        ], $requiredLevel), $lines);

        return [
            'requires_approval' => true,
            'issues' => $issues,
            'required_level' => $requiredLevel ? $this->levelPayload($requiredLevel) : null,
            'required_permission' => $requiredLevel?->permission_name,
        ];
    }

    private function withRequiredLevel(array $issue, ?QuotationApprovalLevel $level): array
    {
        return array_merge($issue, [
            'required_level_id' => $level?->id,
            'required_level_code' => $level?->level_code,
            'required_level_name' => $level?->level_name,
            'required_permission' => $level?->permission_name,
        ]);
    }

    private function levelPayload(QuotationApprovalLevel $level): array
    {
        return [
            'id' => $level->id,
            'level_code' => $level->level_code,
            'level_name' => $level->level_name,
            'max_discount_percentage' => (float) $level->max_discount_percentage,
            'permission_name' => $level->permission_name,
        ];
    }
}
