<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;
use App\Models\User;
use App\Models\Customer;
use App\Models\Finance\Bank;
use App\Models\Finance\Invoice;
use Carbon\Carbon;

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
        'updated_by',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_reference', 'invoice_number');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByBank($query, $bankId)
    {
        return $query->where('bank_id', $bankId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('receipt_date', [$startDate, $endDate]);
    }

    public function scopeByPaymentDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    // Accessors & Mutators
    public function getFormattedAmountAttribute()
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'verified' => 'bg-blue-100 text-blue-800',
            'processed' => 'bg-green-100 text-green-800',
            'failed' => 'bg-red-100 text-red-800',
        ];

        return $badges[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'Pending',
            'verified' => 'Verified',
            'processed' => 'Processed',
            'failed' => 'Failed',
        ];

        return $labels[$this->status] ?? ucfirst($this->status);
    }

    public function getFormattedReceiptDateAttribute()
    {
        return $this->receipt_date ? $this->receipt_date->format('d/m/Y') : '-';
    }

    public function getFormattedPaymentDateAttribute()
    {
        return $this->payment_date ? $this->payment_date->format('d/m/Y') : '-';
    }

    public function getFormattedReceiptDateWithMonthAttribute()
    {
        return $this->receipt_date ? $this->receipt_date->format('d/m/Y') : '-';
    }

    public function getFormattedPaymentDateWithMonthAttribute()
    {
        return $this->payment_date ? $this->payment_date->format('d/m/Y') : '-';
    }

    public function getPaymentMethodBadgeAttribute()
    {
        $badges = [
            'cash' => 'bg-green-100 text-green-800',
            'bank_transfer' => 'bg-blue-100 text-blue-800',
            'credit_card' => 'bg-purple-100 text-purple-800',
            'check' => 'bg-yellow-100 text-yellow-800',
        ];

        return $badges[$this->payment_method] ?? 'bg-gray-100 text-gray-800';
    }

    public function getPaymentMethodLabelAttribute()
    {
        $labels = [
            'cash' => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'credit_card' => 'Credit Card',
            'check' => 'Check',
            'transfer' => 'Transfer',
            'giro' => 'Giro',
        ];

        return $labels[$this->payment_method] ?? ucfirst($this->payment_method);
    }

    // Static methods
    public static function generateReceiptNumber()
    {
        $prefix = 'BR';
        $year = date('Y');
        $month = date('m');
        
        // Get the last receipt number for this month
        $lastReceipt = self::where('receipt_number', 'like', $prefix . $year . $month . '%')
            ->orderBy('receipt_number', 'desc')
            ->first();
        
        if ($lastReceipt) {
            $lastNumber = intval(substr($lastReceipt->receipt_number, -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    // Boot method for auto-generating receipt number
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($bankReceipt) {
            if (empty($bankReceipt->receipt_number)) {
                $bankReceipt->receipt_number = self::generateReceiptNumber();
            }
        });
    }
}