<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;

class TaxCalculation extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail;

    protected $fillable = [
        'tax_invoice_id',
        'tax_setting_id',
        'calculation_type',
        'base_amount',
        'tax_rate',
        'calculated_tax',
        'rounded_tax',
        'rounding_method',
        'calculation_details',
        'is_compound',
        'compound_base',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'calculated_tax' => 'decimal:2',
        'rounded_tax' => 'decimal:2',
        'compound_base' => 'decimal:2',
        'is_compound' => 'boolean',
        'calculation_details' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Relationships
    public function taxInvoice()
    {
        return $this->belongsTo(TaxInvoice::class);
    }

    public function taxSetting()
    {
        return $this->belongsTo(TaxSetting::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeByCalculationType($query, $type)
    {
        return $query->where('calculation_type', $type);
    }

    public function scopeByTaxInvoice($query, $invoiceId)
    {
        return $query->where('tax_invoice_id', $invoiceId);
    }

    public function scopeByTaxSetting($query, $settingId)
    {
        return $query->where('tax_setting_id', $settingId);
    }

    public function scopeCompound($query)
    {
        return $query->where('is_compound', true);
    }

    public function scopeNonCompound($query)
    {
        return $query->where('is_compound', false);
    }

    // Accessors
    public function getFormattedBaseAmountAttribute()
    {
        return 'Rp ' . number_format($this->base_amount, 0, ',', '.');
    }

    public function getFormattedCalculatedTaxAttribute()
    {
        return 'Rp ' . number_format($this->calculated_tax, 0, ',', '.');
    }

    public function getFormattedRoundedTaxAttribute()
    {
        return 'Rp ' . number_format($this->rounded_tax, 0, ',', '.');
    }

    public function getFormattedTaxRateAttribute()
    {
        return number_format($this->tax_rate, 2) . '%';
    }

    public function getCalculationTypeLabelAttribute()
    {
        $labels = [
            'ppn' => 'PPN (VAT)',
            'pph' => 'PPH (Income Tax)',
            'ppnbm' => 'PPNBM',
            'custom' => 'Custom Tax'
        ];
        
        return $labels[$this->calculation_type] ?? ucfirst($this->calculation_type);
    }

    public function getRoundingMethodLabelAttribute()
    {
        $labels = [
            'round' => 'Round',
            'floor' => 'Floor',
            'ceil' => 'Ceiling'
        ];
        
        return $labels[$this->rounding_method] ?? ucfirst($this->rounding_method);
    }

    // Helper methods
    public function calculateTax($baseAmount, $taxRate, $roundingMethod = 'round')
    {
        $calculatedTax = $baseAmount * ($taxRate / 100);
        
        switch ($roundingMethod) {
            case 'floor':
                $roundedTax = floor($calculatedTax);
                break;
            case 'ceil':
                $roundedTax = ceil($calculatedTax);
                break;
            case 'round':
            default:
                $roundedTax = round($calculatedTax, 2);
                break;
        }
        
        return [
            'calculated_tax' => $calculatedTax,
            'rounded_tax' => $roundedTax
        ];
    }

    public function isCompound()
    {
        return $this->is_compound;
    }

    public function getTaxDifference()
    {
        return $this->rounded_tax - $this->calculated_tax;
    }
}