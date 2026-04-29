<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\Company\Customer;

class TaxInvoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'invoice_date',
        'subtotal',
        'tax_amount',
        'total_amount',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    // Relationships
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
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('invoice_date', [$startDate, $endDate]);
    }

    // Accessors & Mutators
    public function getFormattedSubtotalAttribute()
    {
        return 'Rp ' . number_format($this->subtotal, 0, ',', '.');
    }

    public function getFormattedTaxAmountAttribute()
    {
        return 'Rp ' . number_format($this->tax_amount, 0, ',', '.');
    }

    public function getFormattedTotalAmountAttribute()
    {
        return 'Rp ' . number_format($this->total_amount, 0, ',', '.');
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'draft' => 'bg-gray-100 text-gray-800',
            'sent' => 'bg-blue-100 text-blue-800',
            'paid' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800',
        ];

        return $badges[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'draft' => 'Draft',
            'sent' => 'Sent',
            'paid' => 'Paid',
            'cancelled' => 'Cancelled',
        ];

        return $labels[$this->status] ?? ucfirst($this->status);
    }

    public function getFormattedInvoiceDateAttribute()
    {
        return $this->invoice_date->format('d M Y');
    }
}
