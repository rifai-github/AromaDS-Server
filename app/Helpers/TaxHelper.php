<?php

namespace App\Helpers;

use App\Models\TaxSetting;
use Carbon\Carbon;

class TaxHelper
{
    /**
     * Get applicable tax rate for a given date
     * 
     * @param string $taxType Tax type (vat, withholding, etc.)
     * @param string|Carbon|null $date Date to check (default: today)
     * @return TaxSetting|null
     */
    public static function getTaxRateForDate($taxType = 'vat', $date = null)
    {
        $date = $date ? Carbon::parse($date) : now();
        
        return TaxSetting::where('tax_type', $taxType)
            ->where('status', 'active')
            ->where('effective_date', '<=', $date)
            ->where(function($q) use ($date) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $date);
            })
            ->orderBy('effective_date', 'desc')
            ->first();
    }

    /**
     * Get PPN (VAT) rate for invoice date
     * 
     * @param string|Carbon|null $invoiceDate
     * @return float Tax rate percentage (e.g., 11.00)
     */
    public static function getPPNRate($invoiceDate = null)
    {
        $taxSetting = self::getTaxRateForDate('vat', $invoiceDate);
        return $taxSetting ? $taxSetting->tax_rate : 11.00; // Default to 11%
    }

    /**
     * Calculate PPN amount from subtotal
     * 
     * @param float $subtotal
     * @param string|Carbon|null $invoiceDate
     * @return float
     */
    public static function calculatePPN($subtotal, $invoiceDate = null)
    {
        $taxRate = self::getPPNRate($invoiceDate);
        return $subtotal * ($taxRate / 100);
    }

    /**
     * Get tax setting with full details for invoice
     * Returns snapshot of tax data at specific date
     * 
     * @param string|Carbon|null $invoiceDate
     * @return array
     */
    public static function getTaxSnapshot($invoiceDate = null)
    {
        $taxSetting = self::getTaxRateForDate('vat', $invoiceDate);
        
        if (!$taxSetting) {
            return [
                'tax_setting_id' => null,
                'tax_name' => 'PPN',
                'tax_code' => 'PPN',
                'tax_rate' => 11.00,
                'effective_date' => now()->format('Y-m-d'),
                'calculation_method' => 'percentage'
            ];
        }

        return [
            'tax_setting_id' => $taxSetting->id,
            'tax_name' => $taxSetting->name,
            'tax_code' => $taxSetting->tax_code,
            'tax_rate' => $taxSetting->tax_rate,
            'effective_date' => $taxSetting->effective_date->format('Y-m-d'),
            'calculation_method' => $taxSetting->calculation_method,
            'rounding_method' => $taxSetting->rounding_method,
            'decimal_places' => $taxSetting->decimal_places
        ];
    }
}

