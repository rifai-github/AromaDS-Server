<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\Customer;
use App\Models\BankPayment;

class VirtualAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bank_payment_id',
        'customer_id',
        'va_number',
        'va_name',
        'status_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status_active' => 'boolean',
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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('status_active', false);
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByBankPayment($query, $bankPaymentId)
    {
        return $query->where('bank_payment_id', $bankPaymentId);
    }

    public function scopeByVaNumber($query, $vaNumber)
    {
        return $query->where('va_number', $vaNumber);
    }

    // Accessors & Mutators
    public function getStatusBadgeAttribute()
    {
        return $this->status_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
    }

    public function getStatusLabelAttribute()
    {
        return $this->status_active ? 'Active' : 'Inactive';
    }

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format('d M Y H:i');
    }
}
