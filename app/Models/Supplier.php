<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'city',
        'postal_code',
        'country',
        'contact_person',
        'tax_number',
        'bank_account',
        'bank_name',
        'status',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'status' => 'string'
    ];

    // Relationships
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function creditLimits()
    {
        return $this->hasMany(SupplierCreditLimit::class);
    }

    public function paymentTerms()
    {
        return $this->hasMany(SupplierPaymentTerm::class);
    }

    public function productSuppliers()
    {
        return $this->hasMany(ProductSupplier::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function bankPayments()
    {
        return $this->hasMany(BankPayment::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeBySupplierName($query, $supplierName)
    {
        return $query->where('name', 'like', "%{$supplierName}%");
    }

    public function scopeByEmail($query, $email)
    {
        return $query->where('email', 'like', "%{$email}%");
    }

    public function scopeByPhone($query, $phone)
    {
        return $query->where('phone', 'like', "%{$phone}%");
    }

    public function scopeByTaxNumber($query, $taxNumber)
    {
        return $query->where('tax_number', $taxNumber);
    }

    public function scopeByCity($query, $city)
    {
        return $query->where('city', 'like', "%{$city}%");
    }

    public function scopeByCountry($query, $country)
    {
        return $query->where('country', 'like', "%{$country}%");
    }

    public function scopeByContactPerson($query, $contactPerson)
    {
        return $query->where('contact_person', 'like', "%{$contactPerson}%");
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        return ucfirst($this->status);
    }

    public function getFormattedAddressAttribute()
    {
        $address = $this->address;
        if ($this->city) {
            $address .= ', ' . $this->city;
        }
        if ($this->postal_code) {
            $address .= ' ' . $this->postal_code;
        }
        if ($this->country && $this->country !== 'Indonesia') {
            $address .= ', ' . $this->country;
        }
        return $address;
    }

    public function getFormattedBankInfoAttribute()
    {
        if ($this->bank_name && $this->bank_account) {
            return $this->bank_name . ' - ' . $this->bank_account;
        }
        return '-';
    }

    // Mutators
    public function setTaxNumberAttribute($value)
    {
        $this->attributes['tax_number'] = $value ? strtoupper($value) : null;
    }

    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = $value ? strtolower($value) : null;
    }

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = ucwords($value);
    }

    public function setContactPersonAttribute($value)
    {
        $this->attributes['contact_person'] = $value ? ucwords($value) : null;
    }

    public function setCityAttribute($value)
    {
        $this->attributes['city'] = $value ? ucwords($value) : null;
    }

    public function setCountryAttribute($value)
    {
        $this->attributes['country'] = $value ? ucwords($value) : 'Indonesia';
    }

    // Business Logic Methods
    public function getAvailableCredit()
    {
        $creditLimit = $this->creditLimits()->where('is_active', true)->first();
        if (!$creditLimit) {
            return 0;
        }
        
        return $creditLimit->available_credit;
    }

    public function getCreditLimit()
    {
        $creditLimit = $this->creditLimits()->where('is_active', true)->first();
        return $creditLimit ? $creditLimit->credit_limit : 0;
    }

    public function getUsedCredit()
    {
        $creditLimit = $this->creditLimits()->where('is_active', true)->first();
        return $creditLimit ? $creditLimit->used_credit : 0;
    }

    public function updateCreditUsage($amount)
    {
        $creditLimit = $this->creditLimits()->where('is_active', true)->first();
        if ($creditLimit) {
            $creditLimit->used_credit += $amount;
            $creditLimit->available_credit = $creditLimit->credit_limit - $creditLimit->used_credit;
            $creditLimit->save();
        }
    }

    public function getPaymentTermDays()
    {
        $paymentTerm = $this->paymentTerms()->where('is_active', true)->first();
        return $paymentTerm ? $paymentTerm->payment_term_days : 30;
    }

    public function getDiscountPercentage()
    {
        $paymentTerm = $this->paymentTerms()->where('is_active', true)->first();
        return $paymentTerm ? $paymentTerm->discount_percentage : 0;
    }

    public function getTotalPurchaseAmount()
    {
        return $this->purchaseOrders()->sum('total_amount');
    }

    public function getTotalPurchaseCount()
    {
        return $this->purchaseOrders()->count();
    }

    public function getAveragePurchaseAmount()
    {
        $count = $this->getTotalPurchaseCount();
        if ($count === 0) {
            return 0;
        }
        
        return $this->getTotalPurchaseAmount() / $count;
    }

    public function getLastPurchaseDate()
    {
        $lastPurchase = $this->purchaseOrders()->orderBy('po_date', 'desc')->first();
        return $lastPurchase ? $lastPurchase->po_date : null;
    }

    public function getActiveProductsCount()
    {
        return $this->productSuppliers()->where('is_active', true)->count();
    }

    public function getPreferredProductsCount()
    {
        return $this->productSuppliers()->where('is_preferred', true)->count();
    }

    // Validation Methods
    public function hasValidEmail()
    {
        return $this->email && filter_var($this->email, FILTER_VALIDATE_EMAIL);
    }

    public function hasValidPhone()
    {
        return $this->phone && preg_match('/^[0-9+\-\s()]+$/', $this->phone);
    }

    public function hasValidTaxNumber()
    {
        return $this->tax_number && preg_match('/^[0-9]{15}$/', $this->tax_number);
    }

    public function hasValidBankAccount()
    {
        return $this->bank_account && preg_match('/^[0-9]+$/', $this->bank_account);
    }

    // Status Methods
    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isInactive()
    {
        return $this->status === 'inactive';
    }

    public function activate()
    {
        $this->status = 'active';
        $this->save();
    }

    public function deactivate()
    {
        $this->status = 'inactive';
        $this->save();
    }
}