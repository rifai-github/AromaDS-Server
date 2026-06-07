<?php

namespace App\Services\Warehouse;

use App\Models\Warehouse;
use DomainException;

class BranchWarehouseResolver
{
    public const NO_ACTIVE_MESSAGE = 'Branch ini belum memiliki warehouse aktif. Hubungi admin untuk setup warehouse cabang.';
    public const MULTIPLE_ACTIVE_MESSAGE = 'Branch ini memiliki lebih dari 1 warehouse aktif. Rapikan master warehouse terlebih dahulu.';

    public function resolveActiveForBranch(int|string|null $branchId): Warehouse
    {
        if (! $branchId) {
            throw new DomainException(self::NO_ACTIVE_MESSAGE);
        }

        $warehouses = Warehouse::where('branch_id', $branchId)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        if ($warehouses->isEmpty()) {
            throw new DomainException(self::NO_ACTIVE_MESSAGE);
        }

        if ($warehouses->count() > 1) {
            throw new DomainException(self::MULTIPLE_ACTIVE_MESSAGE);
        }

        return $warehouses->first();
    }

    public function activeCountForBranch(int|string|null $branchId, ?int $exceptWarehouseId = null): int
    {
        if (! $branchId) {
            return 0;
        }

        return Warehouse::where('branch_id', $branchId)
            ->where('is_active', true)
            ->when($exceptWarehouseId, fn ($query) => $query->where('id', '!=', $exceptWarehouseId))
            ->count();
    }
}
