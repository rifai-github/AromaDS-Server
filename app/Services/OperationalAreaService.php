<?php

namespace App\Services;

use App\Models\OperationalArea;
use App\Models\Building;

/**
 * Service for Operational Area validation
 * Used to check if building's city is registered in any operational area
 */
class OperationalAreaService
{
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
