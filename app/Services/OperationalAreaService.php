<?php

namespace App\Services;

use App\Models\OperationalArea;
use App\Models\Building;
use App\Models\Branch;
use App\Models\BranchWarehouse;
use App\Models\Warehouse;

/**
 * Service for Operational Area validation
 * Used to check if building's city is registered in any operational area
 */
class OperationalAreaService
{
    private static array $serviceBranchByCity = [];
    private static array $warehouseByBranch = [];

    /**
     * Check if a city is registered in any operational area
     *
     * @param int|null $cityId
     * @return bool
     */
    public static function isCityInOperationalArea(?int $cityId): bool
    {
        if (!$cityId) return false;
        
        return OperationalArea::where('city_id', $cityId)
            ->whereNull('delete_at')
            ->exists();
    }
    
    /**
     * Check if building's city is in any operational area
     *
     * @param Building $building
     * @return bool
     */
    public static function isBuildingInOperationalArea(Building $building): bool
    {
        return self::isCityInOperationalArea($building->city_id);
    }
    
    /**
     * Get the branch that covers this city (if any)
     *
     * @param int $cityId
     * @return \App\Models\Branch|null
     */
    public static function getBranchForCity(int $cityId)
    {
        $operationalArea = OperationalArea::where('city_id', $cityId)
            ->whereNull('delete_at')
            ->with('branch')
            ->first();
            
        return $operationalArea?->branch;
    }

    /**
     * Resolve service branch for job scheduling from a building city.
     * Priority: exact branch city, operational area branch, then explicit building branch fallback.
     */
    public static function resolveServiceBranchForBuilding(?Building $building): ?Branch
    {
        if (!$building) {
            return null;
        }

        $branch = self::resolveServiceBranchForCity($building->city_id);

        if ($branch) {
            return $branch;
        }

        $fallbackBranch = $building->relationLoaded('branch')
            ? $building->branch
            : $building->branch()->first();

        if ($fallbackBranch && !$fallbackBranch->is_active) {
            return null;
        }

        return $fallbackBranch;
    }

    /**
     * Resolve service branch for a city using registered branch data.
     */
    public static function resolveServiceBranchForCity(?int $cityId): ?Branch
    {
        if (!$cityId) {
            return null;
        }

        if (array_key_exists($cityId, self::$serviceBranchByCity)) {
            return self::$serviceBranchByCity[$cityId];
        }

        $branch = Branch::where('city_id', $cityId)
            ->where('is_active', true)
            ->orderByDesc('has_warehouse')
            ->orderBy('id')
            ->first();

        if (!$branch) {
            $branch = OperationalArea::where('city_id', $cityId)
                ->whereNull('delete_at')
                ->where('is_active', true)
                ->whereHas('branch', function ($query) {
                    $query->where('is_active', true);
                })
                ->with('branch')
                ->orderBy('id')
                ->first()?->branch;
        }

        self::$serviceBranchByCity[$cityId] = $branch;

        return $branch;
    }

    /**
     * Resolve the preferred warehouse for a branch.
     * Priority: active primary branch-warehouse mapping, active mapped warehouse, then active warehouse owned by branch.
     */
    public static function resolveWarehouseForBranch(?Branch $branch): ?Warehouse
    {
        if (!$branch) {
            return null;
        }

        if (array_key_exists($branch->id, self::$warehouseByBranch)) {
            return self::$warehouseByBranch[$branch->id];
        }

        $mappedWarehouses = BranchWarehouse::where('branch_id', $branch->id)
            ->where('is_active', true)
            ->with('warehouse')
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get();

        $warehouse = $mappedWarehouses
            ->pluck('warehouse')
            ->first(function ($warehouse) {
                return $warehouse && $warehouse->is_active;
            });

        if (!$warehouse) {
            $warehouse = Warehouse::where('branch_id', $branch->id)
                ->where('is_active', true)
                ->orderBy('id')
                ->first();
        }

        self::$warehouseByBranch[$branch->id] = $warehouse;

        return $warehouse;
    }

    public static function resolveServiceWarehouseForBuilding(?Building $building): ?Warehouse
    {
        return self::resolveWarehouseForBranch(self::resolveServiceBranchForBuilding($building));
    }

    public static function getServiceBranchLabelForBuilding(?Building $building): string
    {
        if (!$building) {
            return '-';
        }

        $branch = self::resolveServiceBranchForBuilding($building);
        $label = $branch?->code ?: $branch?->name;

        if (!$label) {
            $label = $building->city?->name
                ?? $building->branch?->name
                ?? $building->district?->name
                ?? '-';
        }

        return $label;
    }
    
    /**
     * Get validation error message (Indonesian)
     *
     * @return string
     */
    public static function getValidationMessage(): string
    {
        return 'City building tidak terdaftar pada operational area manapun. ' .
               'Harap daftarkan area building di operational area branch terlebih dahulu.';
    }
    
    /**
     * Get validation data for API response
     *
     * @param Building $building
     * @return array
     */
    public static function getValidationData(Building $building): array
    {
        $isValid = self::isBuildingInOperationalArea($building);
        $branch = $isValid ? self::getBranchForCity($building->city_id) : null;
        
        return [
            'is_valid' => $isValid,
            'city_id' => $building->city_id,
            'city_name' => $building->city->name ?? 'Unknown',
            'branch_id' => $branch?->id,
            'branch_name' => $branch?->name,
            'message' => $isValid ? 'OK' : self::getValidationMessage()
        ];
    }

    /**
     * Get validation data for API response by city ID
     *
     * @param int|null $cityId
     * @return array
     */
    public static function getValidationDataByCity(?int $cityId): array
    {
        if (!$cityId) {
            return [
                'is_valid' => false,
                'city_id' => null,
                'city_name' => 'Unknown',
                'branch_id' => null,
                'branch_name' => null,
                'message' => 'City ID tidak valid'
            ];
        }

        $isValid = self::isCityInOperationalArea($cityId);
        $branch = $isValid ? self::getBranchForCity($cityId) : null;
        $city = \App\Models\City::find($cityId);
        
        return [
            'is_valid' => $isValid,
            'city_id' => $cityId,
            'city_name' => $city->name ?? 'Unknown',
            'branch_id' => $branch?->id,
            'branch_name' => $branch?->name,
            'message' => $isValid ? 'OK' : self::getValidationMessage()
        ];
    }
}
