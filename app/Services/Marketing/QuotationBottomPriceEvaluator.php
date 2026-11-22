<?php

namespace App\Services\Marketing;

use App\Models\Branch;
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
            // UAT 18 Nov: free qty ikut dihitung ke total bottom price (qty + qty free), jadi
            // baris free tidak lagi dilewati. Hanya baris tanpa unit sama sekali yang diabaikan.
            if ($this->bottomPriceUnits($detail) <= 0) {
                continue;
            }

            // MOM 18 Nov 2026 p3d: bottom price diambil dari branch building (lokasi pasang), dan
            // yang terbesar dipakai. Pembanding = branch quotation (marketing/customer). Harga sama
            // -> tetap tercatat dari branch building. Tanpa building (renewal tanpa survey) atau
            // branch building belum punya bottom price -> branch quotation saja seperti dulu.
            $bottomPrice = $this->resolveBottomPrice($detail, $branchId, $offerType);

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

            $lines[] = [
                'detail' => $detail,
                'bottom_price' => (float) $bottomPrice->bottom_price,
                'bottom_price_branch_id' => $bottomPrice->branch_id,
            ];
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
            // No line carries any unit - nothing to approve.
            return [
                'requires_approval' => false,
                'issues' => [],
                'required_level' => null,
                'required_permission' => null,
            ];
        }

        // The approval decision is based on the quotation as a whole, not on
        // any single line: sum the lines' actual total against the sum of their
        // bottom-price floors (each floor times that line's qty + free qty).
        $totalQuotation = 0.0;
        $totalBottom = 0.0;

        foreach ($lines as $line) {
            $totalQuotation += (float) $line['detail']->total_price;
            $totalBottom += $line['bottom_price'] * $this->bottomPriceUnits($line['detail']);
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
            'bottom_price_branch_id' => $line['bottom_price_branch_id'],
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

    /** @var array<int, int|null> branch building per survey_id, supaya tiap survey dicari sekali */
    private array $buildingBranchCache = [];

    private function resolveBottomPrice($detail, $quotationBranchId, string $offerType): ?RentalBottomPrice
    {
        if (! $detail->master_rental_id) {
            return null;
        }

        $buildingBranchId = $this->buildingBranchId($detail);

        // Urutan penting: branch building dulu, sehingga bila harga sama yang dipakai tetap dia.
        $branchIds = array_values(array_unique(array_filter([$buildingBranchId, $quotationBranchId])));

        $best = null;

        foreach ($branchIds as $candidateBranchId) {
            $candidate = RentalBottomPrice::active()
                ->where('master_rental_id', $detail->master_rental_id)
                ->where('branch_id', $candidateBranchId)
                ->where('offer_type', $offerType)
                ->first();

            if (! $candidate || (float) $candidate->bottom_price <= 0) {
                continue;
            }

            if (! $best || (float) $candidate->bottom_price > (float) $best->bottom_price) {
                $best = $candidate;
            }
        }

        return $best;
    }

    /**
     * Branch tempat building baris ini berada: kolom buildings.branch_id, atau bila kosong branch
     * aktif yang melayani kota building (aturan sama dengan pencarian harga di wizard).
     */
    private function buildingBranchId($detail): ?int
    {
        $surveyId = $detail->survey_id ?? null;

        if (! $surveyId) {
            return null;
        }

        if (array_key_exists($surveyId, $this->buildingBranchCache)) {
            return $this->buildingBranchCache[$surveyId];
        }

        $building = $detail->survey?->building;
        $branchId = $building?->branch_id ? (int) $building->branch_id : null;

        if (! $branchId && $building?->city_id) {
            $branchId = Branch::query()
                ->where('city_id', $building->city_id)
                ->where('is_active', true)
                ->value('id');
            $branchId = $branchId ? (int) $branchId : null;
        }

        return $this->buildingBranchCache[$surveyId] = $branchId;
    }

    /**
     * Unit yang menanggung bottom price: qty berbayar + qty free. Dulu hanya quantity (dan
     * qty 0 dianggap 1), sehingga unit free tidak ikut menaikkan total bottom.
     */
    private function bottomPriceUnits($detail): float
    {
        return max(0.0, (float) ($detail->quantity ?? 0)) + max(0.0, (float) ($detail->qty_free ?? 0));
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
