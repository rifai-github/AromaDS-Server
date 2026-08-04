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
        $issues = [];

        if ($details->isEmpty()) {
            $issues[] = $this->withRequiredLevel([
                'type' => 'missing_details',
                'message' => 'Quotation has no rental details.',
            ], QuotationApprovalLevel::highest());
        }

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

            // A missing or zero floor means the discount cannot be computed at
            // all, so it takes the most senior level. Also guards division by zero.
            if (! $bottomPrice || (float) $bottomPrice->bottom_price <= 0) {
                $issues[] = $this->withRequiredLevel([
                    'type' => 'missing_bottom_price',
                    'quotation_detail_id' => $detail->id,
                    'master_rental_id' => $detail->master_rental_id,
                    'rental_name' => $detail->masterRental?->rental_name,
                    'room_name' => $detail->room_name,
                    'unit_price' => (float) $detail->unit_price,
                    'bottom_price' => $bottomPrice ? (float) $bottomPrice->bottom_price : null,
                    'discount_percentage' => null,
                    'message' => 'Bottom price is not configured for this rental, branch, and offer type.',
                ], QuotationApprovalLevel::highest());

                continue;
            }

            $floor = (float) $bottomPrice->bottom_price;
            $discount = round((1 - ((float) $detail->unit_price / $floor)) * 100, 4);

            // At or above the floor: no approval needed.
            if ($discount <= 0) {
                continue;
            }

            $issues[] = $this->withRequiredLevel([
                'type' => 'below_bottom_price',
                'quotation_detail_id' => $detail->id,
                'master_rental_id' => $detail->master_rental_id,
                'rental_name' => $detail->masterRental?->rental_name,
                'room_name' => $detail->room_name,
                'unit_price' => (float) $detail->unit_price,
                'bottom_price' => $floor,
                'discount_percentage' => $discount,
                'message' => 'Unit price is below bottom price.',
            ], QuotationApprovalLevel::resolveForDiscount($discount));
        }

        $requiredLevel = $this->deepestRequiredLevel($issues);

        return [
            'requires_approval' => ! empty($issues),
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

    /**
     * The most senior level demanded by any single line wins for the whole
     * quotation. Authority order comes from the model, not from this class.
     */
    private function deepestRequiredLevel(array $issues): ?QuotationApprovalLevel
    {
        $levelIds = array_filter(array_column($issues, 'required_level_id'));

        if (empty($levelIds)) {
            return null;
        }

        return QuotationApprovalLevel::query()
            ->whereIn('id', array_unique($levelIds))
            ->orderByDesc('max_discount_percentage')
            ->orderBy('sort_order')
            ->first();
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
