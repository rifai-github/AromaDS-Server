<?php

namespace App\Helpers;

class UnitHelper
{
    /**
     * Get all available units for product types
     * Based on BRD requirements and existing data
     * 
     * @return array
     */
    public static function getAvailableUnits(): array
    {
        return [
            'pcs' => 'Pieces (pcs)',
            'kg' => 'Kilogram (kg)',
            'liter' => 'Liter (ltr)',
            'bottle' => 'Bottle',
            'pack' => 'Pack',
            'cartridge' => 'Cartridge',
            'unit' => 'Unit',
            'box' => 'Box',
            'set' => 'Set',
            'roll' => 'Roll',
            'sheet' => 'Sheet',
            'meter' => 'Meter (m)',
            'centimeter' => 'Centimeter (cm)',
            'gram' => 'Gram (g)',
            'milliliter' => 'Milliliter (ml)',
        ];
    }

    /**
     * Get unit options for select dropdown
     * 
     * @return array
     */
    public static function getUnitOptions(): array
    {
        $units = self::getAvailableUnits();
        $options = ['' => 'Select Unit'];
        
        foreach ($units as $value => $label) {
            $options[$value] = $label;
        }
        
        return $options;
    }

    /**
     * Check if unit is valid
     * 
     * @param string $unit
     * @return bool
     */
    public static function isValidUnit(string $unit): bool
    {
        return array_key_exists($unit, self::getAvailableUnits());
    }

    /**
     * Get unit label by value
     * 
     * @param string $unit
     * @return string|null
     */
    public static function getUnitLabel(string $unit): ?string
    {
        return self::getAvailableUnits()[$unit] ?? null;
    }
}
