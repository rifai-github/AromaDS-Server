<?php

namespace App\Models;

use App\Http\Traits\AutoFilterable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class TaxSetting extends Model
{
    use AutoFilterable, HasFactory, SoftDeletes;

    /**
     * Rate used when no effective default VAT setting is configured. Matches
     * the value that was previously hardcoded across the invoicing services.
     */
    public const FALLBACK_PPN_RATE = 0.11;

    protected $fillable = [
        'name',
        'tax_code',
        'tax_type',
        'tax_rate',
        'is_default',
        'description',
        'effective_date',
        'end_date',
        'status',
        'is_compound',
        'calculation_method',
        'rounding_method',
        'decimal_places',
        'minimum_amount',
        'maximum_amount',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'end_date' => 'date',
        'tax_rate' => 'decimal:2',
        'is_default' => 'boolean',
        'minimum_amount' => 'decimal:2',
        'maximum_amount' => 'decimal:2',
        'is_compound' => 'boolean',
        'decimal_places' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    // Note: taxInvoices relationship removed as tax_invoices table doesn't have tax_setting_id column

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeByTaxType($query, $type)
    {
        return $query->where('tax_type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeEffective($query, $date = null)
    {
        $date = $date ?? now();

        return $query->where('effective_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $date);
            });
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('tax_code', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('notes', 'like', "%{$search}%");
        });
    }

    // Accessors
    public function getFormattedEffectiveDateAttribute()
    {
        return $this->effective_date ? $this->effective_date->format('d/M/Y') : '-';
    }

    public function getFormattedEndDateAttribute()
    {
        return $this->end_date ? $this->end_date->format('d/M/Y') : '-';
    }

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format('d/M/Y H:i');
    }

    public function getFormattedUpdatedAtAttribute()
    {
        return $this->updated_at->format('d/M/Y H:i');
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'active' => '<span class="status-badge status-active">Active</span>',
            'inactive' => '<span class="status-badge status-inactive">Inactive</span>',
        ];

        return $badges[$this->status] ?? '<span class="status-badge status-inactive">Unknown</span>';
    }

    public function getTaxTypeLabelAttribute()
    {
        $labels = [
            'income' => 'Income Tax',
            'sales' => 'Sales Tax',
            'vat' => 'VAT',
            'withholding' => 'Withholding Tax',
            'other' => 'Other',
        ];

        return $labels[$this->tax_type] ?? ucfirst($this->tax_type);
    }

    public function getCalculationMethodLabelAttribute()
    {
        $labels = [
            'percentage' => 'Percentage',
            'fixed' => 'Fixed Amount',
            'tiered' => 'Tiered',
        ];

        return $labels[$this->calculation_method] ?? ucfirst($this->calculation_method);
    }

    public function getRoundingMethodLabelAttribute()
    {
        $labels = [
            'nearest' => 'Nearest',
            'up' => 'Round Up',
            'down' => 'Round Down',
            'none' => 'No Rounding',
        ];

        return $labels[$this->rounding_method] ?? ucfirst($this->rounding_method);
    }

    public function getFormattedTaxRateAttribute()
    {
        return number_format($this->tax_rate, 2).'%';
    }

    public function getFormattedMinimumAmountAttribute()
    {
        return $this->minimum_amount ? 'Rp '.number_format($this->minimum_amount, 0, ',', '.') : '-';
    }

    public function getFormattedMaximumAmountAttribute()
    {
        return $this->maximum_amount ? 'Rp '.number_format($this->maximum_amount, 0, ',', '.') : '-';
    }

    // Mutators
    public function setEffectiveDateAttribute($value)
    {
        $this->attributes['effective_date'] = $value ? Carbon::parse($value)->format('Y-m-d') : null;
    }

    public function setEndDateAttribute($value)
    {
        $this->attributes['end_date'] = $value ? Carbon::parse($value)->format('Y-m-d') : null;
    }

    public function setTaxRateAttribute($value)
    {
        $this->attributes['tax_rate'] = $value ? (float) $value : 0;
    }

    public function setMinimumAmountAttribute($value)
    {
        $this->attributes['minimum_amount'] = $value ? (float) $value : null;
    }

    public function setMaximumAmountAttribute($value)
    {
        $this->attributes['maximum_amount'] = $value ? (float) $value : null;
    }

    // Helper methods
    public function isActive()
    {
        return $this->status === 'active';
    }

    public static function getDefaultPpnSetting($date = null): ?self
    {
        return static::query()
            ->active()
            ->default()
            ->where('tax_type', 'vat')
            ->effective($date)
            ->orderBy('effective_date', 'desc')
            ->first();
    }

    /**
     * PPN rate as a multiplier (e.g. 0.11 for 11%) taken from the effective
     * default VAT setting. Falls back to the long-standing 11% when no
     * effective setting exists so invoicing never silently drops tax.
     */
    public static function getEffectivePpnRate($date = null): float
    {
        $setting = static::getDefaultPpnSetting($date);

        if (! $setting) {
            Log::warning('No effective default PPN tax setting found; falling back to 11%.', [
                'date' => $date ? (string) $date : 'now',
            ]);

            return self::FALLBACK_PPN_RATE;
        }

        return (float) $setting->tax_rate / 100;
    }

    public function isEffective($date = null)
    {
        $date = $date ?? now();

        return $this->effective_date <= $date &&
               ($this->end_date === null || $this->end_date >= $date);
    }

    public function canBeDeleted()
    {
        // Check if this tax setting is being used by any invoices
        $invoiceCount = \App\Models\Invoice::where('tax_setting_id', $this->id)->count();

        return $invoiceCount === 0;
    }

    public function getTaxAmount($baseAmount)
    {
        if (! $this->isEffective()) {
            return 0;
        }

        // Apply minimum/maximum thresholds
        if ($this->minimum_amount && $baseAmount < $this->minimum_amount) {
            return 0;
        }

        if ($this->maximum_amount && $baseAmount > $this->maximum_amount) {
            $baseAmount = $this->maximum_amount;
        }

        // Calculate tax based on method
        switch ($this->calculation_method) {
            case 'percentage':
                $taxAmount = $baseAmount * ($this->tax_rate / 100);
                break;
            case 'fixed':
                $taxAmount = $this->tax_rate;
                break;
            case 'tiered':
                // Implement tiered calculation logic here
                $taxAmount = $baseAmount * ($this->tax_rate / 100);
                break;
            default:
                $taxAmount = $baseAmount * ($this->tax_rate / 100);
        }

        // Apply rounding
        switch ($this->rounding_method) {
            case 'up':
                $taxAmount = ceil($taxAmount);
                break;
            case 'down':
                $taxAmount = floor($taxAmount);
                break;
            case 'nearest':
                $taxAmount = round($taxAmount, $this->decimal_places);
                break;
            case 'none':
                // No rounding
                break;
        }

        return $taxAmount;
    }
}
