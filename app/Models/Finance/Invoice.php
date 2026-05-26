<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;
use App\Models\User;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\TaxSetting;
use App\Models\JobSchedule;
use App\Services\DocumentNumberService;
use Carbon\Carbon;

class Invoice extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $fillable = [
        'invoice_number',
        'po_number',
        'contract_id',
        'contract_number',
        'customer_id',
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
        'total_amount',
        'total_paid',
        'outstanding',
        'internal_notes',
        'additional_notes',
        'terms_conditions',
        'payment_method',
        'virtual_account_number',
        'faktur_pajak',
        'created_by',
        'updated_by',
        'faktur_pajak_status',
        'ba_date',
        'is_tax_exported',
        'is_emailed',
        'emailed_at',
        'dikirim_oleh',
        'dikirim_pada',
        'diterima_oleh',
        'pada',
        'catatan_pengiriman',
        'kirim',
        'billing_group_id',
        'is_printed',
        'printed_at',
    ];
    
    // Status Constants
    const STATUS_DRAFT = 'draft';
    const STATUS_APPROVED = 'approved';
    const STATUS_TAX_APPROVED = 'tax_approved';
    const STATUS_SENT = 'sent';
    const STATUS_PAID = 'paid';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_OVERDUE = 'overdue';

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'tax_obligation' => 'boolean',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'subtotal_after_discount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'outstanding' => 'decimal:2',
        'dikirim_pada' => 'datetime',
        'pada' => 'datetime',
        'ba_date' => 'date',
        'emailed_at' => 'datetime',
        'is_tax_exported' => 'boolean',
        'is_emailed' => 'boolean',
        'is_printed' => 'boolean',
        'printed_at' => 'datetime',
    ];

    // Relationships
    public function contract()
    {
        return $this->belongsTo(Contract::class, 'contract_number', 'contract_number');
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

    public function invoiceDetails()
    {
        return $this->hasMany(InvoiceDetail::class);
    }

    public function invoiceRentalDetails()
    {
        return $this->hasMany(InvoiceRentalDetail::class);
    }

    public function invoiceActivities()
    {
        return $this->hasMany(InvoiceActivity::class);
    }

    public function invoiceFiles()
    {
        return $this->hasMany(InvoiceFile::class);
    }

    public function invoiceFollowUps()
    {
        return $this->hasMany(InvoiceFollowUp::class);
    }

    public function bankReceipts()
    {
        return $this->hasMany(BankReceipt::class, 'invoice_reference', 'invoice_number');
    }

    public function billingGroup()
    {
        return $this->belongsTo(BillingGroup::class);
    }

    public function taxSetting()
    {
        return $this->belongsTo(TaxSetting::class);
    }

    public function jobSchedules()
    {
        return $this->hasMany(JobSchedule::class, 'contract_number', 'contract_number');
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('invoice_status', 'draft');
    }

    public function scopeSent($query)
    {
        return $query->where('invoice_status', 'sent');
    }

    public function scopePaid($query)
    {
        return $query->where('invoice_status', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('invoice_status', 'overdue');
    }

    public function scopeCancelled($query)
    {
        return $query->where('invoice_status', 'cancelled');
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByContract($query, $contractId)
    {
        return $query->where('contract_id', $contractId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('invoice_date', [$startDate, $endDate]);
    }

    public function scopeOverdueInvoices($query)
    {
        return $query->where('due_date', '<', now()->toDateString())
                    ->whereIn('status', ['sent', 'draft']);
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

    public function getTotalInvoiceAttribute($value)
    {
        return $value ?? $this->grand_total ?? $this->total_amount ?? 0;
    }

    public function setTotalInvoiceAttribute($value): void
    {
        $this->attributes['total_amount'] = $value;
        $this->attributes['grand_total'] = $value;
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'draft' => 'bg-gray-100 text-gray-800',
            'sent' => 'bg-blue-100 text-blue-800',
            'paid' => 'bg-green-100 text-green-800',
            'overdue' => 'bg-red-100 text-red-800',
            'cancelled' => 'bg-yellow-100 text-yellow-800',
        ];

        return $badges[$this->invoice_status] ?? 'bg-gray-100 text-gray-800';
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'draft' => 'Draft',
            'sent' => 'Sent',
            'paid' => 'Paid',
            'overdue' => 'Overdue',
            'cancelled' => 'Cancelled',
        ];

        return $labels[$this->invoice_status] ?? ucfirst($this->invoice_status);
    }

    public function getKirimLabelAttribute()
    {
        $labels = [
            'hard_copy' => 'Hard Copy',
            'soft_copy' => 'Soft Copy',
            'both' => 'Both',
            'manual' => 'Manual',
        ];

        return $labels[$this->kirim] ?? ucfirst($this->kirim ?? '-');
    }

    public function getIsOverdueAttribute()
    {
        return $this->due_date < now()->toDateString() && !in_array($this->invoice_status, ['paid', 'cancelled']);
    }

    public function getDaysOverdueAttribute()
    {
        if (!$this->is_overdue) {
            return 0;
        }

        return now()->diffInDays($this->due_date);
    }

    public function getFormattedInvoiceDateAttribute()
    {
        return $this->invoice_date ? $this->invoice_date->format('d M Y') : '-';
    }

    public function getFormattedInvoiceDateWithMonthAttribute()
    {
        return $this->invoice_date ? $this->invoice_date->format('d M Y') : '-';
    }

    public function getFormattedDueDateAttribute()
    {
        return $this->due_date ? $this->due_date->format('d M Y') : '-';
    }

    public function getFormattedDueDateWithMonthAttribute()
    {
        return $this->due_date ? $this->due_date->format('d M Y') : '-';
    }

    /**
     * Rule 43: Cancellation only possible if tax factor number is empty or cancelled.
     */
    public function canCancel()
    {
        return empty($this->faktur_pajak) || $this->faktur_pajak_status === 'cancelled';
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            $invoice->created_by = auth()->id();
            
            // Auto-generate invoice_number if not provided
            if (empty($invoice->invoice_number)) {
                $contractId = null;

                if (!empty($invoice->contract_number)) {
                    $contractId = Contract::where('contract_number', $invoice->contract_number)->value('id');
                }

                $invoice->invoice_number = app(DocumentNumberService::class)->generate(
                    'invoice',
                    null,
                    null,
                    $contractId,
                    null,
                    null,
                    null,
                    auth()->user()?->branch_id
                );
            }
        });

        static::saving(function ($invoice) {
            // Ensure grand_total and total_amount are in sync
            if ($invoice->total_amount > 0 && ($invoice->grand_total === null || $invoice->grand_total == 0)) {
                $invoice->grand_total = $invoice->total_amount;
            } elseif ($invoice->grand_total > 0 && ($invoice->total_amount === null || $invoice->total_amount == 0)) {
                $invoice->total_amount = $invoice->grand_total;
            }
            
            // Sync status columns if needed
            if ($invoice->invoice_status && !$invoice->status) {
                $invoice->status = $invoice->invoice_status;
            } elseif ($invoice->status && !$invoice->invoice_status) {
                $invoice->invoice_status = $invoice->status;
            }

            // Auto-detect contract number if missing - Fixed to check current contract_number
            if ((!$invoice->contract_number || trim($invoice->contract_number) === '')) {
                // No change needed if we don't have a way to find it, 
                // but at least we don't check for non-existent contract_id column
            }
        });

        static::updating(function ($invoice) {
            $invoice->updated_by = auth()->id();
        });
    }
}
