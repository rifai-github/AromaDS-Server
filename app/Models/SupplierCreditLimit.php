<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierCreditLimit extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'credit_limit',
        'used_credit',
        'available_credit',
        'is_active'
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'used_credit' => 'decimal:2',
        'available_credit' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBySupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    // Accessors
    public function getFormattedCreditLimitAttribute()
    {
        return number_format($this->credit_limit, 2);
    }

    public function getFormattedUsedCreditAttribute()
    {
        return number_format($this->used_credit, 2);
    }

    public function getFormattedAvailableCreditAttribute()
    {
        return number_format($this->available_credit, 2);
    }

    public function getCreditUtilizationPercentageAttribute()
    {
        if ($this->credit_limit == 0) {
            return 0;
        }
        
        return round(($this->used_credit / $this->credit_limit) * 100, 2);
    }

    public function getIsOverLimitAttribute()
    {
        return $this->used_credit > $this->credit_limit;
    }

    public function getIsNearLimitAttribute()
    {
        $threshold = $this->credit_limit * 0.8; // 80% of credit limit
        return $this->used_credit >= $threshold;
    }

    // Business Logic Methods
    public function updateCreditUsage($amount)
    {
        $this->used_credit += $amount;
        $this->available_credit = $this->credit_limit - $this->used_credit;
        $this->save();
    }

    public function reduceCreditUsage($amount)
    {
        $this->used_credit = max(0, $this->used_credit - $amount);
        $this->available_credit = $this->credit_limit - $this->used_credit;
        $this->save();
    }

    public function setCreditLimit($amount)
    {
        $this->credit_limit = $amount;
        $this->available_credit = $this->credit_limit - $this->used_credit;
        $this->save();
    }

    public function resetCreditUsage()
    {
        $this->used_credit = 0;
        $this->available_credit = $this->credit_limit;
        $this->save();
    }

    public function canUseCredit($amount)
    {
        return $this->available_credit >= $amount;
    }

    public function getRemainingCredit()
    {
        return $this->available_credit;
    }

    public function isOverLimit()
    {
        return $this->used_credit > $this->credit_limit;
    }

    public function isNearLimit($threshold = 0.8)
    {
        $limitThreshold = $this->credit_limit * $threshold;
        return $this->used_credit >= $limitThreshold;
    }

    // Static Methods
    public static function createForSupplier($supplierId, $creditLimit = 0)
    {
        return self::create([
            'supplier_id' => $supplierId,
            'credit_limit' => $creditLimit,
            'used_credit' => 0,
            'available_credit' => $creditLimit,
            'is_active' => true
        ]);
    }

    public static function getActiveCreditLimit($supplierId)
    {
        return self::where('supplier_id', $supplierId)
                  ->where('is_active', true)
                  ->first();
    }
}
