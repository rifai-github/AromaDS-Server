<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;

class VirtualAccount extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $fillable = [
        'bank_payment_id',
        'customer_id',
        'va_number',
        'va_name',
        'status_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'status_active' => 'boolean'
    ];

    // Relationships
    public function bankPayment()
    {
        return $this->belongsTo(BankPayment::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function virtualAccountImports()
    {
        return $this->hasMany(VirtualAccountImport::class);
    }

    public function virtualAccountExports()
    {
        return $this->hasMany(VirtualAccountExport::class);
    }

    // Scopes
    public function scopeByBankPayment($query, $bankPaymentId)
    {
        return $query->where('bank_payment_id', $bankPaymentId);
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByVaNumber($query, $vaNumber)
    {
        return $query->where('va_number', 'like', "%{$vaNumber}%");
    }

    public function scopeByVaName($query, $vaName)
    {
        return $query->where('va_name', 'like', "%{$vaName}%");
    }

    public function scopeActive($query)
    {
        return $query->where('status_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('status_active', false);
    }

    // Accessors
    public function getIsActiveAttribute()
    {
        return $this->status_active;
    }

    public function getFullVaNumberAttribute()
    {
        return $this->bankPayment->bank_va_number . $this->va_number;
    }

    public function getFullNameAttribute()
    {
        return $this->va_number . ' - ' . $this->va_name;
    }

    // Methods
    public function activate()
    {
        $this->update(['status_active' => true]);
    }

    public function deactivate()
    {
        $this->update(['status_active' => false]);
    }
}
