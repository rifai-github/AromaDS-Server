<?php

namespace App\Services\Operational;

use App\Models\InventoryIssuing;
use App\Models\JobSchedule;
use App\Models\MaterialIssue;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use App\Services\OperationalAreaService;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Phase 3b of docs/warehouse-opsi3-implementation-plan.md: the admin may move a
 * material issue to another active warehouse of the same branch until it is
 * issued. Nothing is reserved before Submit Issue (stock is checked and the
 * Inventory Issuing — where SNs get scanned — is created only then), so moving
 * it is just repointing material_issues.warehouse_id; once an issuing exists
 * the warehouse is locked.
 */
class MaterialIssueWarehouseService
{
    /** Statuses after which stock/SN are bound to the warehouse. */
    private const LOCKED_STATUSES = ['issued', 'received', 'sent', 'cancelled', 'completed'];

    /**
     * Why the warehouse cannot be changed, or null when it can.
     */
    public function lockReason(MaterialIssue $materialIssue): ?string
    {
        if (in_array(strtolower((string) $materialIssue->status), self::LOCKED_STATUSES, true)) {
            return "Material issue {$materialIssue->issue_number} sudah berstatus {$materialIssue->status}; gudang tidak bisa diganti lagi.";
        }

        $issuing = InventoryIssuing::where('reference_no', $materialIssue->issue_number)
            ->where('status', '!=', 'cancelled')
            ->first();

        if ($issuing) {
            return "Material issue {$materialIssue->issue_number} sudah punya Inventory Issuing {$issuing->issuing_number}; gudang tidak bisa diganti lagi.";
        }

        return null;
    }

    /**
     * The branch whose warehouses are allowed: the branch of the warehouse the
     * system picked for this material issue, else the job's service branch.
     */
    public function allowedBranchId(MaterialIssue $materialIssue, ?JobSchedule $jobSchedule = null): ?int
    {
        $branchId = $materialIssue->warehouse?->branch_id;

        if (! $branchId && $jobSchedule?->building) {
            $branchId = OperationalAreaService::resolveServiceBranchForBuilding($jobSchedule->building)?->id
                ?? $jobSchedule->building->branch_id;
        }

        return $branchId ? (int) $branchId : null;
    }

    /**
     * Active warehouses of the allowed branch with the stock each holds for the
     * products of this material issue (same requirement the Submit Issue check
     * uses: sum of item quantity per product).
     */
    public function options(MaterialIssue $materialIssue, ?JobSchedule $jobSchedule = null): array
    {
        $materialIssue->loadMissing(['warehouse', 'items.product']);
        $branchId = $this->allowedBranchId($materialIssue, $jobSchedule);

        $required = $materialIssue->items
            ->filter(fn ($item) => $item->product_id && (float) $item->quantity > 0)
            ->groupBy('product_id')
            ->map(fn ($items) => [
                'product_id' => (int) $items->first()->product_id,
                'product_name' => $items->first()->product?->name ?? 'Product #'.$items->first()->product_id,
                'required' => (float) $items->sum('quantity'),
            ])
            ->values();

        $warehouses = $branchId
            ? Warehouse::where('branch_id', $branchId)
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get()
            : collect();

        $stock = $warehouses->isNotEmpty() && $required->isNotEmpty()
            ? WarehouseProduct::whereIn('warehouse_id', $warehouses->pluck('id'))
                ->whereIn('master_product_id', $required->pluck('product_id'))
                ->get()
                ->keyBy(fn ($row) => ((int) $row->warehouse_id).':'.((int) $row->master_product_id))
            : collect();

        return [
            'material_issue_id' => $materialIssue->id,
            'issue_number' => $materialIssue->issue_number,
            'current_warehouse_id' => $materialIssue->warehouse_id ? (int) $materialIssue->warehouse_id : null,
            'branch_id' => $branchId,
            'lock_reason' => $this->lockReason($materialIssue),
            'warehouses' => $warehouses->map(function (Warehouse $warehouse) use ($required, $stock, $materialIssue) {
                $items = $required->map(function ($requirement) use ($warehouse, $stock) {
                    $available = (float) ($stock->get($warehouse->id.':'.$requirement['product_id'])?->quantity ?? 0);

                    return $requirement + [
                        'stock' => $available,
                        'sufficient' => $available >= $requirement['required'],
                    ];
                })->values();

                return [
                    'id' => $warehouse->id,
                    'name' => $warehouse->name,
                    'is_default' => (bool) $warehouse->is_default,
                    'is_current' => (int) $warehouse->id === (int) $materialIssue->warehouse_id,
                    'sufficient' => $items->every(fn ($item) => $item['sufficient']),
                    'items' => $items->all(),
                ];
            })->values()->all(),
        ];
    }

    /**
     * Point the material issue at another active warehouse of its branch.
     *
     * @throws DomainException when the change is not allowed
     */
    public function change(MaterialIssue $materialIssue, int $warehouseId, ?JobSchedule $jobSchedule = null, ?int $userId = null): MaterialIssue
    {
        $materialIssue->loadMissing('warehouse');

        if ($reason = $this->lockReason($materialIssue)) {
            throw new DomainException($reason);
        }

        $target = Warehouse::find($warehouseId);
        if (! $target || ! $target->is_active) {
            throw new DomainException('Gudang tujuan tidak ditemukan atau tidak aktif.');
        }

        $branchId = $this->allowedBranchId($materialIssue, $jobSchedule);
        if (! $branchId || (int) $target->branch_id !== $branchId) {
            throw new DomainException("{$target->name} bukan gudang cabang job ini. Pilih gudang aktif di cabang yang sama.");
        }

        if ((int) $materialIssue->warehouse_id === (int) $target->id) {
            return $materialIssue;
        }

        return DB::transaction(function () use ($materialIssue, $target, $userId) {
            $metadata = is_array($materialIssue->metadata) ? $materialIssue->metadata : [];
            $metadata['warehouse_changes'][] = [
                'from_warehouse_id' => $materialIssue->warehouse_id,
                'from_warehouse_name' => $materialIssue->warehouse?->name,
                'to_warehouse_id' => $target->id,
                'to_warehouse_name' => $target->name,
                'changed_by' => $userId,
                'changed_at' => now()->toDateTimeString(),
            ];

            $materialIssue->update([
                'warehouse_id' => $target->id,
                'metadata' => $metadata,
                'updated_by' => $userId,
            ]);

            return $materialIssue->setRelation('warehouse', $target);
        });
    }
}
