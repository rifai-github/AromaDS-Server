<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;

class BankReceipt extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $fillable = [
        'receipt_number',
        'receipt_date',
        'customer_id',
        'invoice_reference',
        'bank_id',
        'account_number',
        'account_holder_name',
        'amount',
        'payment_date',
        'payment_method',
        'status',
        'receipt_image',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'receipt_date' => 'date',
        'payment_date' => 'date',
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function bank()
    {
        return $this->belongsTo(\App\Models\Finance\Bank::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
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
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByBank($query, $bankId)
    {
        return $query->where('bank_id', $bankId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('receipt_date', [$startDate, $endDate]);
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2);
    }

    public function getFormattedReceiptDateAttribute()
    {
        return $this->receipt_date ? $this->receipt_date->format('d/m/Y') : '-';
    }
}
