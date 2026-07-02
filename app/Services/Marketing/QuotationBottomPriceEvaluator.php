<?php

namespace App\Services\Marketing;

use App\Models\Quotation;
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
            $issues[] = [
                'type' => 'missing_details',
                'message' => 'Quotation has no rental details.',
            ];
        }

        foreach ($details as $detail) {
            $bottomPrice = null;

            if ($branchId && $detail->master_rental_id) {
                $bottomPrice = RentalBottomPrice::active()
                    ->where('master_rental_id', $detail->master_rental_id)
                    ->where('branch_id', $branchId)
                    ->where('offer_type', $offerType)
                    ->first();
            }

            if (! $bottomPrice) {
                $issues[] = [
                    'type' => 'missing_bottom_price',
                    'quotation_detail_id' => $detail->id,
                    'master_rental_id' => $detail->master_rental_id,
                    'rental_name' => $detail->masterRental?->rental_name,
                    'room_name' => $detail->room_name,
                    'unit_price' => (float) $detail->unit_price,
                    'bottom_price' => null,
                    'message' => 'Bottom price is not configured for this rental, branch, and offer type.',
                ];

                continue;
            }

            if ((float) $detail->unit_price < (float) $bottomPrice->bottom_price) {
                $issues[] = [
                    'type' => 'below_bottom_price',
                    'quotation_detail_id' => $detail->id,
                    'master_rental_id' => $detail->master_rental_id,
                    'rental_name' => $detail->masterRental?->rental_name,
                    'room_name' => $detail->room_name,
                    'unit_price' => (float) $detail->unit_price,
                    'bottom_price' => (float) $bottomPrice->bottom_price,
                    'message' => 'Unit price is below bottom price.',
                ];
            }
        }

        return [
            'requires_approval' => ! empty($issues),
            'issues' => $issues,
        ];
    }
}
