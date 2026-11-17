<?php

namespace App\Services\Warehouse;

use App\Models\Warehouse;
use DomainException;

/**
 * Picks "the" warehouse of a branch whenever the system has to choose one on
 * its own. A branch may run several equivalent warehouses; the one flagged
 * `is_default` (kept unique per branch by Master Warehouse) is used. Callers
 * that received an explicit warehouse from the user should honour that pick
 * instead. See docs/warehouse-opsi3-implementation-plan.md.
 */
class BranchWarehouseResolver
{
    public const NO_ACTIVE_MESSAGE = 'Branch ini belum memiliki warehouse aktif. Hubungi admin untuk setup warehouse cabang.';
    public const NO_DEFAULT_MESSAGE = 'Branch ini memiliki lebih dari 1 warehouse aktif tetapi belum ada Gudang Default. Tentukan Gudang Default di Master Warehouse.';

    public function resolveDefaultForBranch(int|string|null $branchId): Warehouse
    {
        if (! $branchId) {
            throw new DomainException(self::NO_ACTIVE_MESSAGE);
        }

        $warehouses = Warehouse::where('branch_id', $branchId)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get();

        if ($warehouses->isEmpty()) {
            throw new DomainException(self::NO_ACTIVE_MESSAGE);
        }

        $default = $warehouses->first();

        // A lone active warehouse is unambiguous even if its flag was never set
        // (rows created outside Master Warehouse); with several, guessing would
        // silently move stock through the wrong warehouse.
        if (! $default->is_default && $warehouses->count() > 1) {
            throw new DomainException(self::NO_DEFAULT_MESSAGE);
        }

        return $default;
    }

    /**
     * Lenient variant for background/fallback picks that used to take "the
     * first active warehouse" and must not abort the surrounding flow (auto
     * material issue on assign, Remove-job unit receiving, web completion…).
     * Returns the active default; if several are active and none is flagged it
     * logs and falls back to the lowest id; null when the branch has none.
     */
    public function findDefaultForBranch(int|string|null $branchId): ?Warehouse
    {
        if (! $branchId) {
            return null;
        }

        $warehouses = Warehouse::where('branch_id', $branchId)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get();

        $default = $warehouses->first();

        if ($default && ! $default->is_default && $warehouses->count() > 1) {
            \Log::warning('Branch has several active warehouses but no default; using the lowest id', [
                'branch_id' => $branchId,
                'warehouse_ids' => $warehouses->pluck('id')->all(),
            ]);
        }

        return $default;
    }

    /**
     * @deprecated Kept for the existing callers; resolves the branch's default warehouse.
     */
    public function resolveActiveForBranch(int|string|null $branchId): Warehouse
    {
        return $this->resolveDefaultForBranch($branchId);
    }

    /**
     * Number of active default warehouses in a branch, optionally ignoring one
     * warehouse (the one being edited). Master Warehouse keeps this at exactly 1
     * for every branch that has an active warehouse.
     */
    public function activeDefaultCountForBranch(int|string|null $branchId, ?int $exceptWarehouseId = null): int
    {
        if (! $branchId) {
            return 0;
        }

        return Warehouse::where('branch_id', $branchId)
            ->where('is_active', true)
            ->where('is_default', true)
            ->when($exceptWarehouseId, fn ($query) => $query->where('id', '!=', $exceptWarehouseId))
            ->count();
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
