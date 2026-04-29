<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\Contract;
use App\Models\Customer;
use Carbon\Carbon;

class InvoiceForm extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'form_number',
        'invoice_number',
        'po_number',
        'contract_number',
        'company_name',
        'billing_address',
        'period_invoice',
        'invoice_status',
        'invoice_date',
        'due_date',
        'tax_obligation',
        'tax_code',
        'tax_number',
        'npwp_number',
        'tax_address',
        'province_name',
        'city_name',
        'district_name',
        'village_name',
        'postal_code',
        'subtotal',
        'discount_amount',
        'subtotal_after_discount',
        'tax_amount',
        'grand_total',
        'total_paid',
        'outstanding',
        'internal_notes',
        'additional_notes',
        'form_type',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'tax_obligation' => 'boolean',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'subtotal_after_discount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'outstanding' => 'decimal:2',
    ];

    // Relationships
    public function contract()
    {
        return $this->belongsTo(Contract::class, 'contract_number', 'contract_number');
    }

    public function customer()
    {
        return $this->hasOneThrough(Customer::class, Contract::class, 'contract_number', 'id', 'contract_number', 'customer_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function invoiceFormDetails()
    {
        return $this->hasMany(InvoiceFormDetail::class);
    }

    public function invoiceFormRentals()
    {
        return $this->hasMany(InvoiceFormRental::class);
    }

    public function invoiceFormFiles()
    {
        return $this->hasMany(InvoiceFormFile::class);
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('form_type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('invoice_status', $status);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('invoice_date', [$startDate, $endDate]);
    }

    // Accessors & Mutators
    public function getFormattedInvoiceDateAttribute()
    {
        return $this->invoice_date ? $this->invoice_date->format('d/m/Y') : '-';
    }

    public function getFormattedInvoiceDateWithMonthAttribute()
    {
        return $this->invoice_date ? $this->invoice_date->format('d/m/Y') : '-';
    }

    public function getFormattedDueDateAttribute()
    {
        return $this->due_date ? $this->due_date->format('d/m/Y') : '-';
    }

    public function getFormattedDueDateWithMonthAttribute()
    {
        return $this->due_date ? $this->due_date->format('d/m/Y') : '-';
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'draft' => 'bg-gray-100 text-gray-800',
            'submitted' => 'bg-blue-100 text-blue-800',
            'approved' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
        ];
        return $badges[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    public function getStatusLabelAttribute()
    {
        return ucfirst($this->status);
    }

    public function getFormTypeBadgeAttribute()
    {
        $badges = [
            'invoice' => 'bg-blue-100 text-blue-800',
            'credit_note' => 'bg-green-100 text-green-800',
            'debit_note' => 'bg-yellow-100 text-yellow-800',
        ];
        return $badges[$this->form_type] ?? 'bg-gray-100 text-gray-800';
    }

    public function getFormTypeLabelAttribute()
    {
        $labels = [
            'invoice' => 'Invoice',
            'credit_note' => 'Credit Note',
            'debit_note' => 'Debit Note',
        ];
        return $labels[$this->form_type] ?? ucfirst($this->form_type);
    }

    public function getInvoiceStatusBadgeAttribute()
    {
        $badges = [
            'draft' => 'bg-gray-100 text-gray-800',
            'sent' => 'bg-blue-100 text-blue-800',
            'paid' => 'bg-green-100 text-green-800',
            'overdue' => 'bg-red-100 text-red-800',
            'cancelled' => 'bg-gray-100 text-gray-800',
        ];
        return $badges[$this->invoice_status] ?? 'bg-gray-100 text-gray-800';
    }

    public function getInvoiceStatusLabelAttribute()
    {
        return ucfirst($this->invoice_status);
    }

    // Methods
    public function submit()
    {
        $this->update(['status' => 'submitted']);
    }

    public function approve()
    {
        $this->update(['status' => 'approved']);
    }

    public function reject()
    {
        $this->update(['status' => 'rejected']);
    }

    public function draft()
    {
        $this->update(['status' => 'draft']);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoiceForm) {
            $invoiceForm->created_by = auth()->id();
            
            // Auto-generate form_number if not provided
            if (empty($invoiceForm->form_number)) {
                $latestForm = static::latest()->first();
                $nextNumber = $latestForm ? (int) substr($latestForm->form_number, -4) + 1 : 1;
                $invoiceForm->form_number = 'IF' . Carbon::now()->format('Ym') . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            }
        });

        static::updating(function ($invoiceForm) {
            $invoiceForm->updated_by = auth()->id();
        });
    }
}
