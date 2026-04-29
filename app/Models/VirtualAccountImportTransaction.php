<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VirtualAccountImportTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'virtual_account_import_id',
        'transaction_id',
        'virtual_account_number',
        'customer_name',
        'amount',
        'transaction_date',
        'transaction_time',
        'payment_method',
        'bank_code',
        'bank_name',
        'reference_number',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
        'transaction_time' => 'datetime:H:i:s',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function virtualAccountImport()
    {
        return $this->belongsTo(VirtualAccountImport::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_name', 'customer_name');
    }

    public function virtualAccount()
    {
        return $this->belongsTo(VirtualAccount::class, 'virtual_account_number', 'account_number');
    }

    public function bankPayment()
    {
        return $this->belongsTo(BankPayment::class, 'bank_code', 'bank_code');
    }

    // Scopes
    public function scopeByImport($query, $importId)
    {
        return $query->where('virtual_account_import_id', $importId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByCustomer($query, $customerName)
    {
        return $query->where('customer_name', 'like', "%{$customerName}%");
    }

    public function scopeByVirtualAccount($query, $accountNumber)
    {
        return $query->where('virtual_account_number', $accountNumber);
    }

    public function scopeByTransactionId($query, $transactionId)
    {
        return $query->where('transaction_id', 'like', "%{$transactionId}%");
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function scopeByAmountRange($query, $minAmount, $maxAmount)
    {
        return $query->whereBetween('amount', [$minAmount, $maxAmount]);
    }

    public function scopeByBank($query, $bankCode)
    {
        return $query->where('bank_code', $bankCode);
    }

    public function scopeByPaymentMethod($query, $paymentMethod)
    {
        return $query->where('payment_method', $paymentMethod);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeMatched($query)
    {
        return $query->where('status', 'matched');
    }

    public function scopeUnmatched($query)
    {
        return $query->where('status', 'unmatched');
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    // Accessors
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'badge-warning',
            'matched' => 'badge-success',
            'unmatched' => 'badge-danger',
            'processed' => 'badge-info',
        ];

        return $badges[$this->status] ?? 'badge-secondary';
    }

    public function getStatusTextAttribute()
    {
        return ucfirst($this->status);
    }

    public function getAmountFormattedAttribute()
    {
        return $this->amount ? 'Rp ' . number_format($this->amount, 0, ',', '.') : 'N/A';
    }

    public function getTransactionDateFormattedAttribute()
    {
        return $this->transaction_date ? $this->transaction_date->format('d M Y') : 'N/A';
    }

    public function getTransactionTimeFormattedAttribute()
    {
        return $this->transaction_time ? $this->transaction_time->format('H:i:s') : 'N/A';
    }

    public function getTransactionDateTimeFormattedAttribute()
    {
        if ($this->transaction_date && $this->transaction_time) {
            return $this->transaction_date->format('d M Y') . ' ' . $this->transaction_time->format('H:i:s');
        }
        return 'N/A';
    }

    public function getCustomerNameFormattedAttribute()
    {
        return $this->customer_name ? ucwords($this->customer_name) : 'N/A';
    }

    public function getVirtualAccountNumberFormattedAttribute()
    {
        return $this->virtual_account_number ? strtoupper($this->virtual_account_number) : 'N/A';
    }

    public function getTransactionIdFormattedAttribute()
    {
        return $this->transaction_id ? strtoupper($this->transaction_id) : 'N/A';
    }

    public function getBankCodeFormattedAttribute()
    {
        return $this->bank_code ? strtoupper($this->bank_code) : 'N/A';
    }

    public function getBankNameFormattedAttribute()
    {
        return $this->bank_name ? ucwords($this->bank_name) : 'N/A';
    }

    public function getPaymentMethodFormattedAttribute()
    {
        return $this->payment_method ? ucwords($this->payment_method) : 'N/A';
    }

    public function getReferenceNumberFormattedAttribute()
    {
        return $this->reference_number ? strtoupper($this->reference_number) : 'N/A';
    }

    public function getIsMatchedAttribute()
    {
        return $this->status === 'matched';
    }

    public function getIsUnmatchedAttribute()
    {
        return $this->status === 'unmatched';
    }

    public function getIsProcessedAttribute()
    {
        return $this->status === 'processed';
    }

    public function getIsPendingAttribute()
    {
        return $this->status === 'pending';
    }

    // Methods
    public function canMatch()
    {
        return $this->status === 'pending';
    }

    public function canProcess()
    {
        return $this->status === 'matched';
    }

    public function canUnmatch()
    {
        return $this->status === 'matched';
    }

    public function markAsMatched()
    {
        $this->update(['status' => 'matched']);
    }

    public function markAsUnmatched()
    {
        $this->update(['status' => 'unmatched']);
    }

    public function markAsProcessed()
    {
        $this->update(['status' => 'processed']);
    }

    public function markAsPending()
    {
        $this->update(['status' => 'pending']);
    }

    public function getImportInfo()
    {
        return $this->virtualAccountImport;
    }

    public function getCustomerInfo()
    {
        return $this->customer;
    }

    public function getVirtualAccountInfo()
    {
        return $this->virtualAccount;
    }

    public function getBankInfo()
    {
        return $this->bankPayment;
    }

    public function findMatchingInvoice()
    {
        // Find matching invoice based on virtual account number and amount
        return Invoice::where('virtual_account_number', $this->virtual_account_number)
            ->where('total_amount', $this->amount)
            ->where('payment_status', 'pending')
            ->first();
    }

    public function findMatchingCustomer()
    {
        // Find matching customer based on virtual account number
        return Customer::where('virtual_account_number', $this->virtual_account_number)
            ->orWhere('customer_name', $this->customer_name)
            ->first();
    }

    public function validateTransaction()
    {
        $errors = [];

        // Validate required fields
        if (empty($this->transaction_id)) {
            $errors[] = 'Transaction ID is required';
        }

        if (empty($this->virtual_account_number)) {
            $errors[] = 'Virtual account number is required';
        }

        if (empty($this->amount) || $this->amount <= 0) {
            $errors[] = 'Amount must be greater than 0';
        }

        if (empty($this->transaction_date)) {
            $errors[] = 'Transaction date is required';
        }

        // Check for duplicate transaction
        $existingTransaction = self::where('transaction_id', $this->transaction_id)
            ->where('id', '!=', $this->id)
            ->first();
        
        if ($existingTransaction) {
            $errors[] = 'Transaction ID already exists';
        }

        return $errors;
    }

    public function isTransactionValid()
    {
        return empty($this->validateTransaction());
    }

    public function hasMatchingInvoice()
    {
        return $this->findMatchingInvoice() !== null;
    }

    public function hasMatchingCustomer()
    {
        return $this->findMatchingCustomer() !== null;
    }

    public function getMatchingInvoice()
    {
        return $this->findMatchingInvoice();
    }

    public function getMatchingCustomer()
    {
        return $this->findMatchingCustomer();
    }
}
