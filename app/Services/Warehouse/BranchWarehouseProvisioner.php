<?php

namespace App\Services\Warehouse;

use App\Models\Branch;
use App\Models\Warehouse;
use App\Models\WarehouseType;

/**
 * Creates the default warehouse that every branch is expected to have.
 *
 * The branch master is the source of truth: name, address and phone are copied
 * from the branch, a head office (Branch Pusat) gets a Central Warehouse and a
 * regular branch gets a Branch Warehouse.
 *
 * Provisioning only ever fills the gap — a branch that already has any
 * warehouse is left alone, so extra warehouses added by hand are never
 * touched, renamed or duplicated.
 *
 * Used by both BranchController (on branch create/update) and the
 * `warehouses:sync-from-branches` command so the two stay in sync.
 */
class BranchWarehouseProvisioner
{
    /**
     * Create the default warehouse for a branch. Returns null when the branch
     * already has one (or more) — provisioning is idempotent.
     */
    public function provisionForBranch(Branch $branch, ?int $userId = null): ?Warehouse
    {
        if ($branch->warehouses()->exists()) {
            return null;
        }

        $isCenter = (bool) $branch->is_head_office;

        return Warehouse::create([
            'warehouse_code' => $this->generateWarehouseCode(),
            'name' => $this->defaultWarehouseName($branch),
            'branch_id' => $branch->id,
            'warehouse_type_id' => $this->resolveWarehouseTypeId($isCenter),
            'address' => $branch->address_1,
            'phone' => $branch->phone_1,
            'is_active' => true,
            'is_center' => $isCenter,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    public function defaultWarehouseName(Branch $branch): string
    {
        return 'Gudang '.$branch->name;
    }

    /**
     * Generate a unique warehouse code (same convention as WarehouseController).
     */
    public function generateWarehouseCode(): string
    {
        $prefix = 'WH';
        $year = date('Y');
        $month = date('m');

        $lastWarehouse = Warehouse::withTrashed()
            ->where('warehouse_code', 'like', $prefix.$year.$month.'%')
            ->orderBy('warehouse_code', 'desc')
            ->first();

        $newNumber = $lastWarehouse
            ? intval(substr($lastWarehouse->warehouse_code, -3)) + 1
            : 1;

        return $prefix.$year.$month.str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Resolve (or create) the default "Branch Warehouse" / "Central Warehouse" type id.
     */
    public function resolveWarehouseTypeId(bool $isCenter = false): int
    {
        $code = $isCenter ? 'CENTER' : 'BRANCH';
        $name = $isCenter ? 'Central Warehouse' : 'Branch Warehouse';

        $type = WarehouseType::where('code', $code)->first()
            ?: WarehouseType::where('name', $name)->first();

        if (! $type) {
            $type = WarehouseType::create([
                'code' => $code,
                'name' => $name,
                'description' => $isCenter
                    ? 'Default type for central warehouse locations.'
                    : 'Default type for single warehouse per branch flow.',
                'is_active' => true,
            ]);
        }

        return $type->id;
    }
}
