<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NormalizeCatalystRentalDetailDuplicates extends Command
{
    protected $signature = 'catalyst:normalize-rental-detail-duplicates';

    protected $description = 'Merge duplicate imported Catalyst rental_details into a single keeper row per exact component context';

    public function handle(): int
    {
        $importedRentalIds = DB::table('source_import_maps')
            ->where('source_system', 'catalyst')
            ->where('source_table', 'MsProduct')
            ->where('target_table', 'master_rentals')
            ->pluck('target_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($importedRentalIds === []) {
            $this->warn('Tidak ada master rental import Catalyst yang ditemukan.');
            return self::SUCCESS;
        }

        $groups = DB::table('rental_details')
            ->select(
                'master_rental_id',
                'product_category_id',
                'product_type_id',
                'service_frequency_multiplier',
                'bom_rental_qty',
                DB::raw('COUNT(*) as duplicate_count')
            )
            ->whereNull('deleted_at')
            ->whereIn('master_rental_id', $importedRentalIds)
            ->groupBy('master_rental_id', 'product_category_id', 'product_type_id', 'service_frequency_multiplier', 'bom_rental_qty')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $stats = [
            'groups_merged' => 0,
            'details_soft_deleted' => 0,
            'materials_merged' => 0,
            'maps_repointed' => 0,
        ];

        foreach ($groups as $group) {
            $details = DB::table('rental_details')
                ->whereNull('deleted_at')
                ->where('master_rental_id', $group->master_rental_id)
                ->where('product_category_id', $group->product_category_id)
                ->where('product_type_id', $group->product_type_id)
                ->where('service_frequency_multiplier', $group->service_frequency_multiplier)
                ->where('bom_rental_qty', $group->bom_rental_qty)
                ->get();

            if ($details->count() < 2) {
                continue;
            }

            $details = $details->map(function ($detail) {
                $selectedCount = DB::table('rental_detail_materials')
                    ->where('rental_detail_id', $detail->id)
                    ->where('is_selected', true)
                    ->count();
                $materialsCount = DB::table('rental_detail_materials')
                    ->where('rental_detail_id', $detail->id)
                    ->count();

                $detail->selected_count = $selectedCount;
                $detail->materials_count = $materialsCount;

                return $detail;
            })->sortBy([
                ['selected_count', 'desc'],
                [fn ($detail) => $detail->master_product_id ? 1 : 0, 'desc'],
                ['materials_count', 'desc'],
                ['id', 'asc'],
            ])->values();

            $keeper = $details->first();
            $duplicateIds = $details->skip(1)->pluck('id')->map(fn ($id) => (int) $id)->all();

            $materialRows = DB::table('rental_detail_materials')
                ->whereIn('rental_detail_id', $details->pluck('id')->all())
                ->orderBy('sort_order')
                ->get();

            $materialMap = [];
            foreach ($materialRows as $row) {
                $productId = (int) $row->master_product_id;
                if (!isset($materialMap[$productId])) {
                    $materialMap[$productId] = [
                        'is_selected' => false,
                        'sort_order' => count($materialMap),
                    ];
                }

                if ((bool) $row->is_selected) {
                    $materialMap[$productId]['is_selected'] = true;
                }
            }

            $selectedIds = collect($materialMap)
                ->filter(fn ($row) => $row['is_selected'])
                ->keys()
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            $keeperProductId = $keeper->master_product_id ? (int) $keeper->master_product_id : null;
            if ($keeperProductId && isset($materialMap[$keeperProductId])) {
                foreach ($materialMap as $productId => &$materialRow) {
                    $materialRow['is_selected'] = ((int) $productId === $keeperProductId);
                }
                unset($materialRow);
                $selectedIds = [$keeperProductId];
            } elseif (count($selectedIds) === 1) {
                $keeperProductId = $selectedIds[0];
            } else {
                $keeperProductId = null;
            }

            DB::table('rental_detail_materials')->where('rental_detail_id', $keeper->id)->delete();

            $sortOrder = 0;
            foreach ($materialMap as $productId => $materialRow) {
                DB::table('rental_detail_materials')->updateOrInsert(
                    [
                        'rental_detail_id' => $keeper->id,
                        'master_product_id' => (int) $productId,
                    ],
                    [
                        'is_selected' => $keeperProductId ? ((int) $productId === $keeperProductId) : (bool) $materialRow['is_selected'],
                        'sort_order' => $sortOrder++,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            $updatePayload = ['updated_at' => now()];
            if ($keeperProductId) {
                $updatePayload['master_product_id'] = $keeperProductId;
                $updatePayload['item_type'] = 'product';
                $updatePayload['item_id'] = $keeperProductId;
            }

            DB::table('rental_details')->where('id', $keeper->id)->update($updatePayload);

            if ($duplicateIds !== []) {
                $stats['maps_repointed'] += DB::table('source_import_maps')
                    ->where('source_system', 'catalyst')
                    ->where('target_table', 'rental_details')
                    ->whereIn('target_id', $duplicateIds)
                    ->update([
                        'target_id' => $keeper->id,
                        'updated_at' => now(),
                    ]);

                DB::table('rental_detail_materials')->whereIn('rental_detail_id', $duplicateIds)->delete();
                DB::table('rental_details')->whereIn('id', $duplicateIds)->update([
                    'deleted_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $stats['groups_merged']++;
            $stats['details_soft_deleted'] += count($duplicateIds);
            $stats['materials_merged'] += count($materialMap);
        }

        $this->table(
            ['Metric', 'Value'],
            [
                ['Groups Merged', $stats['groups_merged']],
                ['Details Soft Deleted', $stats['details_soft_deleted']],
                ['Materials Merged', $stats['materials_merged']],
                ['Source Maps Repointed', $stats['maps_repointed']],
            ]
        );

        return self::SUCCESS;
    }
}
