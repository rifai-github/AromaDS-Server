<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;
use Illuminate\Support\Facades\Schema;

class Invoice extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail;

    const STATUS_DRAFT = 'draft';
    const STATUS_APPROVED = 'approved';
    const STATUS_TAX_APPROVED = 'tax_approved';
    const STATUS_PAID = 'paid';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_OVERDUE = 'overdue';

    protected $fillable = [
        'invoice_number',
        'contract_id',
        'contract_number',
        'po_number',
        'customer_id',
        'billing_address',
        'period_invoice',
        'invoice_status',
        'invoice_date',
        'due_date',
        'tax_obligation',
        'gedung',
        'alamat_1',
        'alamat_2',
        'catatan_internal',
        'catatan_customer',
        'pic_finance',
        'email',
        'umur_invoice',
        'outstanding',
        'subtotal',
        'tax_amount',
        'total_amount',
        'grand_total',
        'total_paid',
        'discount_amount',
        'paid_amount',
        'balance_amount',
        'notes',
        'status',
        'terms_conditions',
        'created_by',
        'updated_by',
        'faktur_pajak',
        'tax_code',
        'npwp_number',
        'tax_address',
        'kirim', // Now used for Copy Type (Method)
        'dikirim_oleh', // New: Sender Name
        'dikirim_pada',
        'diterima_oleh',
        'pada',
        'catatan_pengiriman',
        'faktur_pajak_status',
        'ba_date',
        'is_emailed',
        'emailed_at',
        'billing_group_id',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'tax_obligation' => 'boolean',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'outstanding' => 'decimal:2',
        'dikirim_pada' => 'datetime',
        'pada' => 'datetime',
        'ba_date' => 'date',
        'emailed_at' => 'datetime',
        'is_tax_exported' => 'boolean',
        'is_emailed' => 'boolean',
    ];

    // Relationships
    public function contract()
    {
        return $this->belongsTo(Contract::class, 'contract_number', 'contract_number');
    }

    public function picFinance()
    {
        return $this->belongsTo(User::class, 'pic_finance', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function invoiceDetails()
    {
        return $this->hasMany(\App\Models\Finance\InvoiceDetail::class);
    }

    public function invoiceRentalDetails()
    {
        return $this->hasMany(\App\Models\Finance\InvoiceRentalDetail::class);
    }

    public function invoiceFiles()
    {
        return $this->hasMany(InvoiceFile::class);
    }

    public function bankReceipts()
    {
        return $this->hasMany(BankReceipt::class);
    }

    public function invoiceActivities()
    {
        return $this->hasMany(InvoiceActivity::class);
    }

    public function logActivity(string $type, string $notes, ?int $userId = null): \App\Models\Finance\InvoiceActivity
    {
        $payload = [
            'invoice_id' => $this->id,
            'activity_type' => $type,
            'notes' => $notes,
            'created_by' => $userId ?? auth()->id() ?? $this->updated_by ?? $this->created_by ?? 1,
        ];

        if (! Schema::hasTable('invoice_activities')) {
            return new \App\Models\Finance\InvoiceActivity($payload);
        }

        return \App\Models\Finance\InvoiceActivity::create($payload);
    }

    public function jobSchedules()
    {
        return $this->hasMany(JobSchedule::class, 'contract_number', 'contract_number');
    }

    public function taxInvoice()
    {
        return $this->belongsTo(TaxInvoice::class, 'faktur_pajak', 'no_faktur');
    }

    public function taxSetting()
    {
        return $this->belongsTo(TaxSetting::class);
    }

    public function billingGroup()
    {
        return $this->belongsTo(\App\Models\Finance\BillingGroup::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('invoice_status', $status);
    }

    public function scopeByContract($query, $contractNo)
    {
        return $query->where('contract_number', $contractNo);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('invoice_date', [$startDate, $endDate]);
    }

    public function scopeByDueDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('due_date', [$startDate, $endDate]);
    }

    public function scopeOutstanding($query)
    {
        return $query->where('outstanding', '>', 0);
    }

    public function scopePaid($query)
    {
        return $query->where('outstanding', 0);
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())->where('outstanding', '>', 0);
    }

    public function scopeWajibPungut($query)
    {
        return $query->where('tax_obligation', true);
    }

    public function scopeNonWajibPungut($query)
    {
        return $query->where('tax_obligation', false);
    }

    /**
     * Rule 43: Cancellation only possible if tax factor number is empty or cancelled.
     */
    public function canCancel()
    {
        return empty($this->faktur_pajak) || $this->faktur_pajak_status === 'cancelled';
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->invoice_status));
    }

    public function getIsOutstandingAttribute()
    {
        return $this->outstanding > 0;
    }

    public function getIsOverdueAttribute()
    {
        return $this->due_date && $this->due_date < now() && $this->outstanding > 0;
    }

    public function getFormattedInvoiceDateAttribute()
    {
        return $this->invoice_date ? $this->invoice_date->format('d/m/Y') : '-';
    }

    public function getFormattedDueDateAttribute()
    {
        return $this->due_date ? $this->due_date->format('d/m/Y') : '-';
    }

    public function getFormattedTotalInvoiceAttribute()
    {
        return number_format($this->total_invoice, 0, ',', '.');
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

    public function getFormattedOutstandingAttribute()
    {
        return number_format($this->outstanding, 0, ',', '.');
    }

    protected static function boot()
    {
        parent::boot();

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

            // Auto-detect contract number if missing - Fixed
            if ((!$invoice->contract_number || trim($invoice->contract_number) === '')) {
                // No change needed if we don't have a way to find it
            }
        });
    }

    /**
     * Rule 46: Get previous invoice for the same contract to determine TOP.
     */
    public function getPreviousInvoice()
    {
        return self::where('contract_number', $this->contract_number)
            ->where('id', '<', $this->id)
            ->orderBy('id', 'desc')
            ->first();
    }
}
