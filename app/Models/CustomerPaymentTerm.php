<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerPaymentTerm extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'payment_term_days',
        'discount_percentage',
        'is_active'
    ];

    protected $casts = [
        'payment_term_days' => 'integer',
        'discount_percentage' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByPaymentTerm($query, $days)
    {
        return $query->where('payment_term_days', $days);
    }

    public function scopeWithDiscount($query)
    {
        return $query->where('discount_percentage', '>', 0);
    }

    public function scopeWithoutDiscount($query)
    {
        return $query->where('discount_percentage', 0);
    }

    // Accessors
    public function getFormattedDiscountPercentageAttribute()
    {
        return number_format($this->discount_percentage, 2) . '%';
    }

    public function getPaymentTermDescriptionAttribute()
    {
        if ($this->payment_term_days == 0) {
            return 'Cash on Delivery';
        } elseif ($this->payment_term_days == 1) {
            return 'Cash';
        } else {
            return "Net {$this->payment_term_days} Days";
        }
    }

    public function getIsActiveTextAttribute()
    {
        return $this->is_active ? 'Aktif' : 'Nonaktif';
    }

    public function getHasDiscountAttribute()
    {
        return $this->discount_percentage > 0;
    }

    // Business Logic Methods
    public function calculateDiscountAmount($amount)
    {
        return ($amount * $this->discount_percentage) / 100;
    }

    public function calculateNetAmount($amount)
    {
        return $amount - $this->calculateDiscountAmount($amount);
    }

    public function getDueDate($invoiceDate = null)
    {
        $date = $invoiceDate ? \Carbon\Carbon::parse($invoiceDate) : now();
        return $date->addDays($this->payment_term_days);
    }

    public function isCashPayment()
    {
        return $this->payment_term_days == 0 || $this->payment_term_days == 1;
    }

    public function isCreditPayment()
    {
        return $this->payment_term_days > 1;
    }

    public function getPaymentTermType()
    {
        if ($this->payment_term_days == 0 || $this->payment_term_days == 1) {
            return 'cash';
        } elseif ($this->payment_term_days <= 30) {
            return 'short_term';
        } elseif ($this->payment_term_days <= 90) {
            return 'medium_term';
        } else {
            return 'long_term';
        }
    }

    public function getPaymentTermTypeText()
    {
        $type = $this->getPaymentTermType();
        
        switch ($type) {
            case 'cash':
                return 'Cash';
            case 'short_term':
                return 'Short Term';
            case 'medium_term':
                return 'Medium Term';
            case 'long_term':
                return 'Long Term';
            default:
                return 'Unknown';
        }
    }

    // Static Methods
    public static function createForCustomer($customerId, $paymentTermDays = 30, $discountPercentage = 0)
    {
        return self::create([
            'customer_id' => $customerId,
            'payment_term_days' => $paymentTermDays,
            'discount_percentage' => $discountPercentage,
            'is_active' => true
        ]);
    }

    public static function getActivePaymentTerm($customerId)
    {
        return self::where('customer_id', $customerId)
                  ->where('is_active', true)
                  ->first();
    }

    public static function getCommonPaymentTerms()
    {
        return [
            ['days' => 0, 'description' => 'Cash on Delivery'],
            ['days' => 1, 'description' => 'Cash'],
            ['days' => 7, 'description' => 'Net 7 Days'],
            ['days' => 15, 'description' => 'Net 15 Days'],
            ['days' => 30, 'description' => 'Net 30 Days'],
            ['days' => 45, 'description' => 'Net 45 Days'],
            ['days' => 60, 'description' => 'Net 60 Days'],
            ['days' => 90, 'description' => 'Net 90 Days'],
        ];
    }

    public static function getDiscountTiers()
    {
        return [
            ['percentage' => 0, 'description' => 'No Discount'],
            ['percentage' => 2, 'description' => '2% Discount'],
            ['percentage' => 5, 'description' => '5% Discount'],
            ['percentage' => 10, 'description' => '10% Discount'],
            ['percentage' => 15, 'description' => '15% Discount'],
            ['percentage' => 20, 'description' => '20% Discount'],
        ];
    }
}
