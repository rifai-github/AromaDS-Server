<?php

namespace App\Models\Finance;

use App\Http\Traits\AutoFilterable;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\JobSchedule;
use App\Models\TaxSetting;
use App\Models\User;
use App\Services\DocumentNumberService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class Invoice extends Model
{
    use AutoFilterable, HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'po_number',
        'contract_id',
        'contract_number',
        // What an ad-hoc invoice was raised for: a Lost Unit Report number, or the job
        // number of an Extra. Period invoices leave it null - they are keyed by period_invoice.
        'reference_number',
        'customer_id',
        'billing_address',
        'period_invoice',
        'invoice_status',
        'invoice_date',
        'due_date',
        'tax_obligation',
        'tax_setting_id',
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
        'coretax_faktur_number',
        'coretax_faktur_date',
        'coretax_status',
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

    /** Value CoreTax reports in TaxInvoiceStatus once a faktur pajak is issued. */
    const CORETAX_STATUS_APPROVED = 'APPROVED';

    /** Written by us when the user revokes the faktur pajak from the invoice screen. */
    const CORETAX_STATUS_CANCELLED = 'CANCELLED';

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'coretax_faktur_date' => 'date',
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

    public function contractById()
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function resolvedContract(): ?Contract
    {
        if ($this->relationLoaded('contractById') && $this->contractById) {
            return $this->contractById;
        }

        if ($this->relationLoaded('contract') && $this->contract) {
            return $this->contract;
        }

        if ($this->contract_id) {
            return Contract::with('branch.invoiceAuthorizedByUser')->find($this->contract_id);
        }

        if ($this->contract_number) {
            return Contract::with('branch.invoiceAuthorizedByUser')
                ->where('contract_number', $this->contract_number)
                ->first();
        }

        return null;
    }

    public function resolvedInvoiceBranch(): ?\App\Models\Branch
    {
        $contract = $this->resolvedContract();

        if (! $contract) {
            return null;
        }

        if ($contract->relationLoaded('branch')) {
            return $contract->branch;
        }

        return $contract->branch()->with('invoiceAuthorizedByUser')->first();
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

    public function logActivity(string $type, string $notes, ?int $userId = null): InvoiceActivity
    {
        if (! Schema::hasTable('invoice_activities')) {
            return new InvoiceActivity([
                'invoice_id' => $this->id,
                'activity_type' => $type,
                'notes' => $notes,
                'created_by' => $userId ?? auth()->id() ?? $this->updated_by ?? $this->created_by ?? 1,
            ]);
        }

        return $this->invoiceActivities()->create([
            'activity_type' => $type,
            'notes' => $notes,
            'created_by' => $userId ?? auth()->id() ?? $this->updated_by ?? $this->created_by ?? 1,
        ]);
    }

    public function getActivityTimelineAttribute(): Collection
    {
        $this->loadMissing([
            'creator',
            'invoiceActivities.creator',
            'invoiceFollowUps.creator',
            'invoiceFollowUps.updater',
            'bankReceipts.creator',
        ]);

        $timeline = collect();

        $hasCreatedActivity = $this->invoiceActivities->contains('activity_type', 'created');

        if (! $hasCreatedActivity) {
            $timeline->push([
                'source' => 'invoice',
                'type' => 'created',
                'title' => 'Invoice Created',
                'notes' => 'Invoice record created',
                'status' => $this->invoice_status,
                'icon' => 'fas fa-file-invoice',
                'color' => 'primary',
                'performed_by' => $this->creator->name ?? 'System',
                'occurred_at' => $this->created_at,
            ]);
        }

        foreach ($this->invoiceActivities as $activity) {
            $timeline->push([
                'source' => 'activity',
                'type' => $activity->activity_type,
                'title' => $activity->activity_type_label,
                'notes' => $activity->notes,
                'status' => null,
                'icon' => $this->timelineIconForActivity($activity->activity_type),
                'color' => $this->timelineColorForActivity($activity->activity_type),
                'performed_by' => $activity->creator->name ?? 'System',
                'occurred_at' => $activity->created_at,
            ]);
        }

        foreach ($this->invoiceFollowUps as $followUp) {
            $timeline->push([
                'source' => 'follow_up',
                'type' => $followUp->follow_up_type,
                'title' => 'Follow Up - '.$followUp->follow_up_type_label,
                'notes' => $followUp->notes,
                'status' => $followUp->status,
                'icon' => $this->timelineIconForFollowUp($followUp->follow_up_type),
                'color' => 'primary',
                'performed_by' => $followUp->creator->name ?? 'System',
                'occurred_at' => $followUp->created_at,
            ]);

            if ($followUp->updated_by && $followUp->updated_at && $followUp->updated_at->gt($followUp->created_at)) {
                $timeline->push([
                    'source' => 'follow_up',
                    'type' => 'updated',
                    'title' => 'Follow Up Updated',
                    'notes' => $followUp->notes,
                    'status' => $followUp->status,
                    'icon' => 'fas fa-edit',
                    'color' => 'secondary',
                    'performed_by' => $followUp->updater->name ?? 'System',
                    'occurred_at' => $followUp->updated_at,
                ]);
            }
        }

        foreach ($this->bankReceipts as $receipt) {
            $timeline->push([
                'source' => 'receipt',
                'type' => 'paid',
                'title' => 'Payment Receipt',
                'notes' => trim(($receipt->receipt_number ? "Receipt: {$receipt->receipt_number}. " : '').($receipt->notes ?? '')),
                'status' => $receipt->status,
                'icon' => 'fas fa-receipt',
                'color' => $receipt->status === 'verified' ? 'success' : 'info',
                'performed_by' => $receipt->creator->name ?? 'System',
                'occurred_at' => $receipt->payment_date ?? $receipt->created_at,
            ]);
        }

        return $timeline
            ->filter(fn ($item) => $item['occurred_at'])
            ->sortByDesc('occurred_at')
            ->values();
    }

    private function timelineIconForActivity(?string $type): string
    {
        return [
            'created' => 'fas fa-file-invoice',
            'sent' => 'fas fa-paper-plane',
            'viewed' => 'fas fa-eye',
            'paid' => 'fas fa-check-circle',
            'overdue' => 'fas fa-exclamation-triangle',
            'updated' => 'fas fa-edit',
            'cancelled' => 'fas fa-ban',
        ][$type] ?? 'fas fa-history';
    }

    private function timelineColorForActivity(?string $type): string
    {
        return [
            'created' => 'primary',
            'sent' => 'info',
            'viewed' => 'secondary',
            'paid' => 'success',
            'overdue' => 'danger',
            'updated' => 'warning',
            'cancelled' => 'danger',
        ][$type] ?? 'secondary';
    }

    private function timelineIconForFollowUp(?string $type): string
    {
        return [
            'email' => 'fas fa-envelope',
            'phone' => 'fas fa-phone',
            'visit' => 'fas fa-user-friends',
            'letter' => 'fas fa-file-alt',
        ][$type] ?? 'fas fa-comment-dots';
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
        return 'Rp '.number_format($this->subtotal, 0, ',', '.');
    }

    public function getFormattedTaxAmountAttribute()
    {
        return 'Rp '.number_format($this->tax_amount, 0, ',', '.');
    }

    public function getFormattedTotalAmountAttribute()
    {
        return 'Rp '.number_format($this->total_amount, 0, ',', '.');
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
        return $this->due_date < now()->toDateString() && ! in_array($this->invoice_status, ['paid', 'cancelled']);
    }

    public function getDaysOverdueAttribute()
    {
        if (! $this->is_overdue) {
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
     * Rule 43: an invoice may not be cancelled while a faktur pajak is live at DJP.
     *
     * This used to key off `faktur_pajak`, which holds the name of the file someone
     * attached on the FILE(S) tab — so an invoice was locked merely because a file
     * had been uploaded, while an invoice with a genuinely issued faktur stayed
     * cancellable. The CoreTax number is the real record, so that is what locks it.
     */
    public function canCancel()
    {
        return ! $this->hasValidCoreTaxFaktur();
    }

    /**
     * True once CoreTax has issued a faktur pajak for this invoice and has not
     * revoked it. Set by the CoreTax CSV import, never by hand.
     */
    public function hasValidCoreTaxFaktur(): bool
    {
        return filled($this->coretax_faktur_number)
            && strtoupper((string) $this->coretax_status) === self::CORETAX_STATUS_APPROVED;
    }

    /**
     * Revoke the faktur pajak issued by CoreTax. The invoice drops back to
     * approved so it can be exported to CoreTax again for a fresh number, and
     * its documents lock until that arrives.
     */
    public function cancelCoreTaxFaktur(): void
    {
        $this->deleteArchivedFakturPajakFile();

        $this->update([
            'coretax_status' => self::CORETAX_STATUS_CANCELLED,
            'faktur_pajak_status' => 'cancelled',
            'invoice_status' => $this->invoice_status === self::STATUS_TAX_APPROVED
                ? self::STATUS_APPROVED
                : $this->invoice_status,
        ]);
    }

    /**
     * The faktur pajak PDF that CoreTaxFakturPdfImportService::archiveOnInvoice()
     * copies onto this invoice's FILE(S) tab is only valid for as long as the
     * faktur itself is — once cancelled here, a re-export gets a brand new
     * number, so the stale copy must go rather than sit in FILE(S)/the BA
     * bundle looking like a still-valid document.
     */
    private function deleteArchivedFakturPajakFile(): void
    {
        if (blank($this->coretax_faktur_number)) {
            return;
        }

        $marker = 'Faktur Pajak '.$this->coretax_faktur_number;

        $this->invoiceFiles()
            ->where('description', 'like', '%'.$marker.'%')
            ->get()
            ->each(function (InvoiceFile $file) {
                if ($file->file_path && file_exists(public_path($file->file_path))) {
                    @unlink(public_path($file->file_path));
                }

                $file->delete();
            });
    }

    /**
     * Apply a faktur pajak CoreTax issued for this invoice: stamp the number,
     * promote approved -> tax_approved, and log the activity. Shared by both
     * the CSV/XLSX result import and the individual PDF import so the two
     * paths cannot silently diverge in what counts as "issued".
     *
     * A cancelled invoice is left untouched — a faktur that arrives after the
     * invoice was already cancelled here should not resurrect it.
     *
     * @return array{applied: bool, promoted: bool, note: string}
     */
    public function applyCoreTaxFaktur(string $fakturNumber, ?Carbon $fakturDate, string $status, string $source): array
    {
        if ($this->invoice_status === self::STATUS_CANCELLED) {
            return [
                'applied' => false,
                'promoted' => false,
                'note' => 'Invoice sudah dibatalkan, tidak diubah.',
            ];
        }

        $promoted = $this->invoice_status === self::STATUS_APPROVED;

        $this->update(array_merge([
            'coretax_faktur_number' => $fakturNumber,
            'coretax_faktur_date' => $fakturDate,
            'coretax_status' => $status,
        ], $promoted ? ['invoice_status' => self::STATUS_TAX_APPROVED] : []));

        $note = $promoted
            ? 'Faktur Pajak '.$fakturNumber.' diterima dari CoreTax; invoice terbit (Tax Approved).'
            : 'Faktur Pajak '.$fakturNumber.' diterima dari CoreTax; status invoice tetap '.$this->invoice_status.'.';

        $this->invoiceActivities()->create([
            'activity_type' => 'updated',
            'notes' => $note.' Sumber: '.$source.'.',
            'created_by' => auth()->id(),
        ]);

        return ['applied' => true, 'promoted' => $promoted, 'note' => $note];
    }

    /**
     * Invoices that carry PPN must wait for their faktur pajak before any
     * document leaves the building. Invoices with no PPN never get one, so they
     * are exempt — otherwise they could never be printed at all.
     *
     * The `tax_obligation` flag alone is not trustworthy: on QA, 49 of the 55
     * invoices that actually bill PPN have it switched off. The tax actually
     * charged is the reliable signal, so either one is enough to require a faktur.
     */
    public function requiresFakturPajak(): bool
    {
        return (bool) $this->tax_obligation || (float) $this->tax_amount > 0;
    }

    public function canPrintDocuments(): bool
    {
        return ! $this->requiresFakturPajak()
            || $this->hasValidCoreTaxFaktur()
            || $this->wasTaxApprovedBeforeCoreTax();
    }

    /**
     * Invoices cleared by the old manual TAX APPROVE button carry no CoreTax
     * number and never will: that button is gone, and the CoreTax import is now
     * the only thing that sets this status — always writing the number with it.
     * So this combination can only be pre-CoreTax data, and blocking it would
     * strand documents that printed fine before.
     */
    private function wasTaxApprovedBeforeCoreTax(): bool
    {
        return $this->invoice_status === self::STATUS_TAX_APPROVED
            && blank($this->coretax_faktur_number);
    }

    public function documentBlockReason(): ?string
    {
        if ($this->canPrintDocuments()) {
            return null;
        }

        return filled($this->coretax_faktur_number)
            ? 'Faktur Pajak invoice ini sudah dibatalkan di CoreTax.'
            : 'Invoice ini belum punya Faktur Pajak dari CoreTax.';
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            $invoice->created_by = auth()->id();

            // Auto-generate invoice_number if not provided
            if (empty($invoice->invoice_number)) {
                $contractId = null;

                if (! empty($invoice->contract_number)) {
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
            if ($invoice->invoice_status && ! $invoice->status) {
                $invoice->status = $invoice->invoice_status;
            } elseif ($invoice->status && ! $invoice->invoice_status) {
                $invoice->invoice_status = $invoice->status;
            }

            // Auto-detect contract number if missing - Fixed to check current contract_number
            if ((! $invoice->contract_number || trim($invoice->contract_number) === '')) {
                // No change needed if we don't have a way to find it,
                // but at least we don't check for non-existent contract_id column
            }
        });

        static::updating(function ($invoice) {
            $invoice->updated_by = auth()->id();
        });
    }
}
