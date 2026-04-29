<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxFileImportDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tax_file_import_id',
        'tax_invoice_number',
        'customer_name',
        'customer_tax_number',
        'customer_address',
        'taxable_amount',
        'tax_amount',
        'total_amount',
        'invoice_date',
        'due_date',
        'status',
        'error_message',
        'notes',
    ];

    protected $casts = [
        'taxable_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'invoice_date' => 'date',
        'due_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function taxFileImport()
    {
        return $this->belongsTo(TaxFileImport::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_name', 'customer_name');
    }

    public function taxInvoice()
    {
        return $this->belongsTo(TaxInvoice::class, 'tax_invoice_number', 'tax_invoice_number');
    }

    // Scopes
    public function scopeByImport($query, $importId)
    {
        return $query->where('tax_file_import_id', $importId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByCustomer($query, $customerName)
    {
        return $query->where('customer_name', 'like', "%{$customerName}%");
    }

    public function scopeByTaxInvoice($query, $taxInvoiceNumber)
    {
        return $query->where('tax_invoice_number', 'like', "%{$taxInvoiceNumber}%");
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('invoice_date', [$startDate, $endDate]);
    }

    public function scopeByAmountRange($query, $minAmount, $maxAmount)
    {
        return $query->whereBetween('total_amount', [$minAmount, $maxAmount]);
    }

    public function scopeValid($query)
    {
        return $query->where('status', 'valid');
    }

    public function scopeInvalid($query)
    {
        return $query->where('status', 'invalid');
    }

    public function scopeDuplicate($query)
    {
        return $query->where('status', 'duplicate');
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    public function scopeWithErrors($query)
    {
        return $query->whereNotNull('error_message');
    }

    // Accessors
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'valid' => 'badge-success',
            'invalid' => 'badge-danger',
            'duplicate' => 'badge-warning',
            'processed' => 'badge-info',
        ];

        return $badges[$this->status] ?? 'badge-secondary';
    }

    public function getStatusTextAttribute()
    {
        return ucfirst($this->status);
    }

    public function getTaxableAmountFormattedAttribute()
    {
        return $this->taxable_amount ? 'Rp ' . number_format($this->taxable_amount, 0, ',', '.') : 'N/A';
    }

    public function getTaxAmountFormattedAttribute()
    {
        return $this->tax_amount ? 'Rp ' . number_format($this->tax_amount, 0, ',', '.') : 'N/A';
    }

    public function getTotalAmountFormattedAttribute()
    {
        return $this->total_amount ? 'Rp ' . number_format($this->total_amount, 0, ',', '.') : 'N/A';
    }

    public function getInvoiceDateFormattedAttribute()
    {
        return $this->invoice_date ? $this->invoice_date->format('d M Y') : 'N/A';
    }

    public function getDueDateFormattedAttribute()
    {
        return $this->due_date ? $this->due_date->format('d M Y') : 'N/A';
    }

    public function getCustomerNameFormattedAttribute()
    {
        return $this->customer_name ? ucwords($this->customer_name) : 'N/A';
    }

    public function getCustomerTaxNumberFormattedAttribute()
    {
        return $this->customer_tax_number ? strtoupper($this->customer_tax_number) : 'N/A';
    }

    public function getTaxInvoiceNumberFormattedAttribute()
    {
        return $this->tax_invoice_number ? strtoupper($this->tax_invoice_number) : 'N/A';
    }

    public function getTaxRateAttribute()
    {
        if ($this->taxable_amount > 0 && $this->tax_amount > 0) {
            return round(($this->tax_amount / $this->taxable_amount) * 100, 2);
        }
        return 0;
    }

    public function getTaxRateFormattedAttribute()
    {
        return $this->tax_rate . '%';
    }

    public function getIsOverdueAttribute()
    {
        return $this->due_date && $this->due_date->isPast();
    }

    public function getDaysOverdueAttribute()
    {
        if ($this->is_overdue) {
            return $this->due_date->diffInDays(now());
        }
        return 0;
    }

    public function getDaysOverdueFormattedAttribute()
    {
        if ($this->is_overdue) {
            return $this->days_overdue . ' days overdue';
        }
        return 'On time';
    }

    // Methods
    public function canProcess()
    {
        return $this->status === 'valid';
    }

    public function canRevalidate()
    {
        return in_array($this->status, ['invalid', 'duplicate']);
    }

    public function markAsValid()
    {
        $this->update(['status' => 'valid', 'error_message' => null]);
    }

    public function markAsInvalid($errorMessage = null)
    {
        $this->update([
            'status' => 'invalid',
            'error_message' => $errorMessage
        ]);
    }

    public function markAsDuplicate($errorMessage = null)
    {
        $this->update([
            'status' => 'duplicate',
            'error_message' => $errorMessage
        ]);
    }

    public function markAsProcessed()
    {
        $this->update(['status' => 'processed']);
    }

    public function getImportInfo()
    {
        return $this->taxFileImport;
    }

    public function getCustomerInfo()
    {
        return $this->customer;
    }

    public function getTaxInvoiceInfo()
    {
        return $this->taxInvoice;
    }

    public function validateData()
    {
        $errors = [];

        // Validate required fields
        if (empty($this->tax_invoice_number)) {
            $errors[] = 'Tax invoice number is required';
        }

        if (empty($this->customer_name)) {
            $errors[] = 'Customer name is required';
        }

        if (empty($this->total_amount) || $this->total_amount <= 0) {
            $errors[] = 'Total amount must be greater than 0';
        }

        if (empty($this->invoice_date)) {
            $errors[] = 'Invoice date is required';
        }

        // Validate tax calculation
        if ($this->taxable_amount && $this->tax_amount && $this->total_amount) {
            $calculatedTotal = $this->taxable_amount + $this->tax_amount;
            if (abs($calculatedTotal - $this->total_amount) > 0.01) {
                $errors[] = 'Total amount does not match taxable amount + tax amount';
            }
        }

        // Check for duplicate tax invoice number
        $existingInvoice = TaxInvoice::where('tax_invoice_number', $this->tax_invoice_number)->first();
        if ($existingInvoice) {
            $errors[] = 'Tax invoice number already exists';
        }

        return $errors;
    }

    public function isDataValid()
    {
        return empty($this->validateData());
    }

    public function hasErrors()
    {
        return !empty($this->error_message);
    }

    public function getErrorMessages()
    {
        return $this->error_message ? explode(';', $this->error_message) : [];
    }

    public function addErrorMessage($message)
    {
        $currentErrors = $this->getErrorMessages();
        $currentErrors[] = $message;
        $this->error_message = implode(';', $currentErrors);
    }
}
