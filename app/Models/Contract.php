<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;
use App\Http\Traits\AutoFilterable;

class Contract extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail, AutoFilterable;

    protected $fillable = [
        'contract_number',
        'po_number',
        'customer_id',
        'quotation_id',
        'contract_date',
        'start_date',
        'end_date',
        'contract_value',
        'payment_terms',
        'contract_terms',
        'status',
        'contract_type',
        'marketing_id',
        'notes',
        // New editable fields after approval
        'signatory_name',
        'signatory_position',
        'signatory_npwp',
        'signatory_address',
        'marketing_name',
        'marketing_phone',
        'marketing_email',
        'contract_period_type',
        'staff_digital_signature',
        'customer_digital_signature',
        'staff_signed_at',
        'customer_signed_at',
        'qr_code',
        'is_approved',
        'is_posted',
        'approved_at',
        'posted_at',
        'approved_by',
        'posted_by',
        'schedule_generated',
        'schedule_generated_at',
        // Digital Signature Enhancement
        'digital_signature_data',
        'digital_signature_file',
        'signature_at',
        'signed_by',
        'signature_position',
        // NPWP Integration
        'npwp_number',
        'npwp_verified',
        'npwp_verified_at',
        'npwp_verification_data',
        // Auto-generate Schedule
        'schedule_data',
        // Contract Status Enhancement
        'contract_status',
        'created_by',
        'updated_by',
        // MOM6: Contract Notes
        'notes_operation',
        'notes_finance',
        'notes_sales',
        // Contract Wizard Step 3 fields
        'ppn_code',
        'install_date',
        'first_service_date',
        'internal_remark',
        'external_remark',
        'customer_signing_1_id',
        'customer_signing_2_id',
        'customer_signing_3_id',
        'customer_signing_4_id',
        'internal_signing_id',
        'term_of_payment',
        'pic_service_email',
        // Commission fields
        'net_value',
        'is_installed',
        'installed_date',
        'commission_recipient_id',
        'marketing_level_id',
        'ba_files_supported',
        'hold_invoice',
        'virtual_account',
        'invoice_period_type',
        'invoice_date_preference',
        'is_contract',
        // Contract Merge fields
        'merged_from_ids',
    ];

    protected $casts = [
        'contract_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'install_date' => 'date',
        'first_service_date' => 'date',
        'installed_date' => 'date',
        'contract_value' => 'decimal:2',
        'net_value' => 'decimal:2',
        'is_installed' => 'boolean',
        'staff_signed_at' => 'datetime',
        'customer_signed_at' => 'datetime',
        'approved_at' => 'datetime',
        'posted_at' => 'datetime',
        'schedule_generated_at' => 'datetime',
        'is_approved' => 'boolean',
        'is_posted' => 'boolean',
        'schedule_generated' => 'boolean',
        // Digital Signature Enhancement
        'signature_at' => 'datetime',
        'npwp_verified' => 'boolean',
        'npwp_verified_at' => 'datetime',
        'npwp_verification_data' => 'array',
        'schedule_data' => 'array',
        'ba_files_supported' => 'boolean',
        'hold_invoice' => 'boolean',
        'is_contract' => 'boolean',
        // Contract Merge
        'merged_from_ids' => 'array',
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function marketing()
    {
        return $this->belongsTo(User::class, 'marketing_id');
    }

    public function contractRooms()
    {
        return $this->hasMany(ContractRoom::class);
    }

    // Multiple Survey Enhancement relationships
    public function contractSurveys()
    {
        return $this->hasMany(ContractSurvey::class);
    }

    public function surveys()
    {
        return $this->belongsToMany(Survey::class, 'contract_surveys')
            ->withPivot('added_at', 'added_by', 'sort_order')
            ->withTimestamps();
    }

    // Legacy singular survey relationship (for backward compatibility)
    public function survey()
    {
        return $this->belongsTo(Survey::class, 'survey_id');
    }

    public function contractRentals()
    {
        return $this->hasMany(ContractRental::class);
    }

    public function billingGroups()
    {
        return $this->hasMany(\App\Models\Finance\BillingGroup::class);
    }

    public function billingGroup()
    {
        return $this->hasOne(\App\Models\Finance\BillingGroup::class)->latest();
    }

    public function contractFiles()
    {
        return $this->hasMany(ContractFile::class);
    }

    // Contract Merge Relationships
    public function mergedSources()
    {
        return $this->belongsToMany(Contract::class, 'contract_merges', 'new_contract_id', 'source_contract_id')
            ->withPivot('rooms_copied', 'rentals_copied', 'jobs_cancelled', 'merged_at')
            ->withTimestamps();
    }

    public function mergedInto()
    {
        return $this->hasOneThrough(
            Contract::class,
            \App\Models\ContractMerge::class, // Need to make sure this model exists or use table name
            'source_contract_id', // Foreign key on contract_merges table
            'id', // Foreign key on contracts table
            'id', // Local key on contracts table
            'new_contract_id' // Local key on contract_merges table
        );
    }
    
    // Simpler way for mergedInto if no model:
    public function mergedIntoRecord()
    {
        return \DB::table('contract_merges')->where('source_contract_id', $this->id)->first();
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
    
    // Customer Signing Relationships
    public function customerSigning1()
    {
        return $this->belongsTo(\App\Models\CustomerContact::class, 'customer_signing_1_id');
    }
    
    public function customerSigning2()
    {
        return $this->belongsTo(\App\Models\CustomerContact::class, 'customer_signing_2_id');
    }
    
    public function customerSigning3()
    {
        return $this->belongsTo(\App\Models\CustomerContact::class, 'customer_signing_3_id');
    }
    
    public function customerSigning4()
    {
        return $this->belongsTo(\App\Models\CustomerContact::class, 'customer_signing_4_id');
    }
    
    // Internal Signing Relationship
    public function internalSigning()
    {
        return $this->belongsTo(User::class, 'internal_signing_id');
    }

    // Commission Relationships
    public function commissionRecipient()
    {
        return $this->belongsTo(User::class, 'commission_recipient_id');
    }

    public function marketingLevel()
    {
        return $this->belongsTo(\App\Models\Finance\MarketingLevel::class);
    }

    public function commissionTransfers()
    {
        return $this->hasMany(\App\Models\Finance\CommissionTransfer::class);
    }

    public function commissionCalculations()
    {
        return $this->hasMany(\App\Models\Finance\CommissionCalculation::class);
    }

    // New relationships for enhanced contract management
    public function contractRemarks()
    {
        return $this->hasMany(ContractRemark::class);
    }

    public function contractRevisions()
    {
        return $this->hasMany(ContractRevision::class);
    }

    public function renewals()
    {
        return $this->hasMany(ContractRenewal::class);
    }

    /**
     * Contract baru yang merupakan renewal dari contract ini.
     * Ditemukan via Quotation yang punya existing_contract_id = contract ini.
     */
    public function renewedByContract()
    {
        return $this->hasOneThrough(
            Contract::class,     // Final model (contract baru)
            Quotation::class,    // Through model
            'existing_contract_id', // FK di quotations → mengarah ke contract lama
            'quotation_id',         // FK di contracts → mengarah ke quotation
            'id',                   // Local key di contract lama
            'id'                    // Local key di quotation
        );
    }

    public function terminations()
    {
        return $this->hasMany(ContractTermination::class);
    }

    public function jobAdvices()
    {
        return $this->hasMany(\App\Models\JobAdvice::class);
    }

    /**
     * Contracts yang di-merge INTO contract ini (contract ini adalah hasil merge)
     * Dibaca dari tabel pivot contract_merges
     */
    public function mergeSourceContracts()
    {
        return $this->belongsToMany(
            Contract::class,
            'contract_merges',
            'new_contract_id',
            'source_contract_id'
        )->withPivot('rooms_copied', 'rentals_copied', 'jobs_cancelled', 'merged_by', 'merged_at')
         ->withTimestamps();
    }

    /**
     * Contract baru yang merupakan hasil merge dari contract ini (contract ini adalah source)
     */
    public function mergeTargetContracts()
    {
        return $this->belongsToMany(
            Contract::class,
            'contract_merges',
            'source_contract_id',
            'new_contract_id'
        )->withPivot('rooms_copied', 'rentals_copied', 'jobs_cancelled', 'merged_by', 'merged_at')
         ->withTimestamps();
    }

    /**
     * Audit trail dari semua merge yang melibatkan contract ini sebagai target
     */
    public function contractMerges()
    {
        return $this->hasMany(ContractMerge::class, 'new_contract_id');
    }

    /**
     * Cek apakah contract ini adalah hasil dari merge
     */
    public function getIsMergedContractAttribute()
    {
        return $this->contract_type === 'merge' ||
               (!empty($this->merged_from_ids) && count($this->merged_from_ids) > 0);
    }

    public function getDisplayQuotationNumbersAttribute(): string
    {
        if ($this->quotation?->quotation_number) {
            return $this->quotation->quotation_number;
        }

        $numbers = $this->mergeDisplaySources()
            ->map(fn ($contract) => $contract->quotation?->quotation_number)
            ->filter()
            ->unique()
            ->values();

        return $numbers->isNotEmpty() ? $numbers->implode(', ') : '-';
    }

    public function getDisplayContractTypeAttribute(): string
    {
        return ucfirst($this->quotation?->quotation_type ?? $this->contract_type ?? 'New');
    }

    public function getDisplayContractPeriodAttribute(): string
    {
        $period = $this->quotation?->rental_period;

        if (!$period && $this->start_date && $this->end_date) {
            $months = \Carbon\Carbon::parse($this->start_date)
                ->diffInMonths(\Carbon\Carbon::parse($this->end_date));
            $period = $months > 0 ? (string) $months : null;
        }

        if (!$period) {
            return '-';
        }

        if (preg_match('/\d+/', (string) $period, $matches)) {
            return $matches[0];
        }

        return (string) $period;
    }

    public function getDisplayBranchCodeAttribute(): string
    {
        if ($this->quotation?->branch?->code) {
            return $this->quotation->branch->code;
        }

        $codes = $this->mergeDisplaySources()
            ->map(fn ($contract) => $contract->quotation?->branch?->code)
            ->filter()
            ->unique()
            ->values();

        if ($codes->isNotEmpty()) {
            return $codes->implode(', ');
        }

        if ($this->contract_number && str_contains($this->contract_number, '-')) {
            return explode('-', $this->contract_number, 2)[0];
        }

        return '-';
    }

    public function getDisplayBranchNameAttribute(): string
    {
        if ($this->quotation?->branch?->name) {
            return $this->quotation->branch->name;
        }

        $names = $this->mergeDisplaySources()
            ->map(fn ($contract) => $contract->quotation?->branch?->name)
            ->filter()
            ->unique()
            ->values();

        if ($names->isNotEmpty()) {
            return $names->implode(', ');
        }

        $branch = \App\Models\Branch::where('code', $this->display_branch_code)->first();
        return $branch->name ?? '-';
    }

    public function getDisplayQuotationDateAttribute(): string
    {
        return $this->formatMergeDisplayDates('quotation_date');
    }

    public function getDisplayValidUntilAttribute(): string
    {
        return $this->formatMergeDisplayDates('valid_until');
    }

    public function getDisplayQuotationTotalAmountAttribute(): float
    {
        return $this->quotation
            ? (float) ($this->quotation->total_amount ?? 0)
            : (float) $this->mergeDisplaySources()->sum(fn ($contract) => (float) ($contract->quotation?->total_amount ?? 0));
    }

    public function getDisplayQuotationGrandTotalAttribute(): float
    {
        return $this->quotation
            ? (float) ($this->quotation->grand_total ?? 0)
            : (float) $this->mergeDisplaySources()->sum(fn ($contract) => (float) ($contract->quotation?->grand_total ?? 0));
    }

    public function getDisplayQuotationStatusAttribute(): string
    {
        if ($this->quotation?->status) {
            return ucfirst($this->quotation->status);
        }

        $statuses = $this->mergeDisplaySources()
            ->map(fn ($contract) => $contract->quotation?->status)
            ->filter()
            ->unique()
            ->values();

        return $statuses->isNotEmpty()
            ? $statuses->map(fn ($status) => ucfirst($status))->implode(', ')
            : '-';
    }

    public function getDisplayTermOfPaymentAttribute(): string
    {
        if ($this->quotation?->terms_of_payment_label) {
            return $this->quotation->terms_of_payment_label;
        }

        if ($this->term_of_payment) {
            return $this->term_of_payment;
        }

        if ($this->payment_terms) {
            return $this->payment_terms;
        }

        $terms = $this->mergeDisplaySources()
            ->map(fn ($contract) => $contract->term_of_payment ?: $contract->payment_terms)
            ->filter()
            ->unique()
            ->values();

        return $terms->isNotEmpty() ? $terms->implode(', ') : '-';
    }

    public function mergeDisplaySources()
    {
        if (!$this->is_merged_contract) {
            return collect();
        }

        $this->loadMissing([
            'mergedSources.quotation.branch',
            'mergedSources.quotation.quotationSurveys.survey',
            'mergedSources.quotation.survey',
            'mergedSources.contractSurveys.survey',
        ]);

        return $this->mergedSources;
    }

    private function formatMergeDisplayDates(string $field): string
    {
        if ($this->quotation && $this->quotation->{$field}) {
            return \Carbon\Carbon::parse($this->quotation->{$field})->format('d/M/Y');
        }

        $dates = $this->mergeDisplaySources()
            ->map(fn ($contract) => $contract->quotation?->{$field})
            ->filter()
            ->map(fn ($date) => \Carbon\Carbon::parse($date)->format('d/M/Y'))
            ->unique()
            ->values();

        return $dates->isNotEmpty() ? $dates->implode(', ') : '-';
    }

    public static function findRenewalSource($contractId): ?self
    {
        if (!$contractId) {
            return null;
        }

        $contract = static::withoutGlobalScopes()
            ->whereKey($contractId)
            ->first();

        if ($contract) {
            return $contract;
        }

        $row = \Illuminate\Support\Facades\DB::table((new static)->getTable())
            ->where('id', $contractId)
            ->first();

        return $row ? (new static)->newFromBuilder((array) $row) : null;
    }

    /**
     * Get all job schedules through job advices
     */
    public function jobSchedules()
    {
        return $this->hasManyThrough(
            \App\Models\JobSchedule::class,
            \App\Models\JobAdvice::class,
            'contract_id',      // Foreign key on job_advices table
            'job_advice_id',    // Foreign key on job_schedules table
            'id',               // Local key on contracts table
            'id'                // Local key on job_advices table
        );
    }

    public static function finalJobScheduleStatuses(): array
    {
        return [
            'completed',
            'done_job',
            'done job',
            'selesai',
            'cancelled',
            'canceled',
            'terminated',
        ];
    }

    public function hasRenewalSuccessor(?int $exceptContractId = null): bool
    {
        $renewedByQuery = $this->renewedByContract();
        if ($exceptContractId) {
            $renewedByQuery->where('contracts.id', '!=', $exceptContractId);
        }

        if ($renewedByQuery->exists()) {
            return true;
        }

        $renewalQuery = $this->renewals()
            ->where('status', \App\Models\ContractRenewal::STATUS_COMPLETED)
            ->whereNotNull('new_contract_id');

        if ($exceptContractId) {
            $renewalQuery->where('new_contract_id', '!=', $exceptContractId);
        }

        return $renewalQuery
            ->exists();
    }

    public function blockingOperationalJobsForRenewal()
    {
        $contractNumber = $this->contract_number;
        $finalStatuses = self::finalJobScheduleStatuses();

        return \App\Models\JobSchedule::query()
            ->where(function ($query) use ($contractNumber) {
                $query->whereHas('jobAdvice', function ($jobAdviceQuery) {
                    $jobAdviceQuery->where('contract_id', $this->id);
                });

                if ($contractNumber) {
                    $query->orWhere('contract_number', $contractNumber);
                }
            })
            ->where(function ($query) use ($finalStatuses) {
                $query->whereNull('status')
                    ->orWhereNotIn(\Illuminate\Support\Facades\DB::raw('LOWER(TRIM(status))'), $finalStatuses);
            })
            ->whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(TRIM(type))'), [
                'install',
                'install_free',
                'service_first',
            ]);
    }

    public function hasBlockingOperationalJobsForRenewal(): bool
    {
        return $this->blockingOperationalJobsForRenewal()->exists();
    }

    public function getRenewalBlockReason(?int $exceptSuccessorContractId = null): ?string
    {
        if ($this->hasBlockingOperationalJobsForRenewal()) {
            return "Contract {$this->contract_number} masih memiliki Job Schedule aktif/belum selesai. Selesaikan atau cancel job tersebut sebelum membuat renewal.";
        }

        if ($this->hasRenewalSuccessor($exceptSuccessorContractId)) {
            return "Contract {$this->contract_number} sudah memiliki contract renewal/current contract.";
        }

        if (!$this->actual_start_date || !$this->actual_end_date) {
            return "Contract {$this->contract_number} belum dimulai/belum memiliki BA date. Renewal hanya bisa dibuat setelah seluruh job contract lama selesai.";
        }

        return null;
    }

    public function canBeRenewedSafely(): bool
    {
        return $this->getRenewalBlockReason() === null;
    }

    /**
     * Get actual contract start date from BA date of first completed job schedule
     * Contract period starts after installation/service is completed (done_job with ba_date)
     * Returns null if no BA date exists (contract hasn't started yet)
     */
    public function getActualStartDateAttribute()
    {
        // Get first BA date from completed job schedules (install or service type)
        $firstBaDate = $this->jobSchedules()
            ->whereNotNull('ba_date')
            ->whereIn('job_schedules.type', ['install', 'install_free', 'service'])
            ->orderBy('ba_date', 'asc')
            ->value('ba_date');
        
        // Return BA date ONLY - no fallback (contract hasn't started if no BA date)
        return $firstBaDate;
    }

    /**
     * Get actual contract end date based on actual_start_date + contract duration
     * Returns null if actual_start_date is null
     */
    public function getActualEndDateAttribute()
    {
        $actualStartDate = $this->actual_start_date;
        
        // If no start date (no BA date), end date is also empty
        if (!$actualStartDate) {
            return null;
        }
        
        // Calculate contract duration in months from original start_date and end_date
        $originalStartDate = $this->attributes['start_date'] ?? null;
        $originalEndDate = $this->attributes['end_date'] ?? null;
        
        if ($originalStartDate && $originalEndDate) {
            // Calculate duration from original dates
            $start = \Carbon\Carbon::parse($originalStartDate);
            $end = \Carbon\Carbon::parse($originalEndDate);
            
            $durationMonths = $start->diffInMonths($end);
            $durationDays = $start->diffInDays($end);
            
            $actualStart = \Carbon\Carbon::parse($actualStartDate);
            
            // Apply same duration to actual start date
            if ($durationMonths > 0) {
                return $actualStart->addMonths($durationMonths);
            } else {
                return $actualStart->addDays($durationDays);
            }
        }
        
        // Fallback: return original end_date if can't calculate
        return $originalEndDate ? \Carbon\Carbon::parse($originalEndDate) : null;
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function poster()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }


    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('contract_status', $status);
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByQuotation($query, $quotationId)
    {
        return $query->where('quotation_id', $quotationId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('contract_date', [$startDate, $endDate]);
    }

    public function scopeActive($query)
    {
        return $query->where('contract_status', 'active');
    }

    public function scopeExpired($query)
    {
        return $query->where('contract_status', 'expired');
    }

    public function scopeTerminated($query)
    {
        return $query->where('contract_status', 'terminated');
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        $statuses = [
            'draft' => 'Draft',
            'pending_signature' => 'Menunggu Tanda Tangan',
            'signed' => 'Ditandatangani',
            'active' => 'Aktif',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            'expired' => 'Kadaluarsa',
            'terminated' => 'Dihentikan',
            'waiting_for_approval' => 'Menunggu Persetujuan',
            'rejected' => 'Ditolak'
        ];

        return $statuses[$this->contract_status] ?? $this->contract_status;
    }

    public function getPaymentTermsTextAttribute()
    {
        $terms = [
            'cash' => 'Tunai',
            'credit_30' => 'Kredit 30 Hari',
            'credit_60' => 'Kredit 60 Hari',
            'credit_90' => 'Kredit 90 Hari'
        ];

        return $terms[$this->payment_terms] ?? $this->payment_terms;
    }

    public function getFormattedContractValueAttribute()
    {
        return 'Rp ' . number_format($this->contract_value, 0, ',', '.');
    }

    public function getFormattedDateAttribute()
    {
        return $this->contract_date ? $this->contract_date->format('d/m/Y') : '-';
    }

    public function getFormattedStartDateAttribute()
    {
        return $this->start_date ? $this->start_date->format('d/m/Y') : '-';
    }

    public function getFormattedEndDateAttribute()
    {
        return $this->end_date ? $this->end_date->format('d/m/Y') : '-';
    }

    public function getIsActiveAttribute()
    {
        return $this->status === 'active';
    }

    public function getIsExpiredAttribute()
    {
        return $this->status === 'expired' || ($this->end_date && $this->end_date < now());
    }

    // New accessors for enhanced contract management
    public function getContractPeriodTypeLabelAttribute()
    {
        $types = [
            'job_order' => 'Job Order (Harian)',
            'contract' => 'Contract (Bulanan)'
        ];

        return $types[$this->contract_period_type] ?? $this->contract_period_type;
    }

    /**
     * Get numeric TOP interval in months from term_of_payment string
     * Standard formats: "1 bulan", "2 bulan", "3 bulan", "Bulan", "Monthly"
     * Default returns 1 if not found or "Cash"
     */
    public function getTopIntervalMonthsAttribute()
    {
        $term = strtolower(trim($this->term_of_payment ?? $this->payment_terms ?? ''));
        
        if (empty($term) || $term === 'cash') {
            return 1;
        }

        if ($this->quotation && $this->quotation->top_months) {
            return (int) $this->quotation->top_months;
        }

        if (preg_match('/(\d+)\s*x.*(periode|period|contract|kontrak)/i', $term, $matches)) {
            $paymentCount = (int) $matches[1];
            $contractMonths = $this->getContractDurationMonthsForTop();

            if ($paymentCount > 0 && $contractMonths > 0) {
                return max(1, (int) floor($contractMonths / $paymentCount));
            }
        }

        // Match patterns like "1 bulan", "2 bulan", "12 bulan"
        if (preg_match('/(\d+)\s*(bulan|month)/i', $term, $matches)) {
            return (int) $matches[1];
        }

        // Descriptive terms
        if (strpos($term, 'bulanan') !== false || strpos($term, 'monthly') !== false) {
            return 1;
        }

        if (strpos($term, 'triwulan') !== false || strpos($term, 'quarter') !== false) {
            return 3;
        }

        if (strpos($term, 'semester') !== false) {
            return 6;
        }

        if (strpos($term, 'tahunan') !== false || strpos($term, 'yearly') !== false || strpos($term, 'annual') !== false) {
            return 12;
        }

        return 1;
    }

    private function getContractDurationMonthsForTop(): int
    {
        if ($this->start_date && $this->end_date) {
            $start = \Carbon\Carbon::parse($this->start_date)->startOfDay();
            $end = \Carbon\Carbon::parse($this->end_date)->startOfDay()->addDay();

            return max(1, (int) ceil($start->diffInDays($end) / 30));
        }

        $quotation = $this->quotation;
        if (! $quotation || ! $quotation->rental_period) {
            return 0;
        }

        $period = (int) $quotation->rental_period;
        if ($period <= 0) {
            return 0;
        }

        return $quotation->rental_unit === 'hari'
            ? ($period < 30 ? 1 : (int) ceil($period / 30))
            : $period;
    }

    public function getIsFullySignedAttribute()
    {
        return $this->staff_signed_at && $this->customer_signed_at;
    }

    public function getCanBeEditedAttribute()
    {
        return !$this->is_approved || $this->is_posted;
    }

    public function getHasQrCodeAttribute()
    {
        return !empty($this->qr_code);
    }

    public function getFormattedApprovedAtAttribute()
    {
        return $this->approved_at ? $this->approved_at->format('d/m/Y H:i') : '-';
    }

    public function getFormattedPostedAtAttribute()
    {
        return $this->posted_at ? $this->posted_at->format('d/m/Y H:i') : '-';
    }

    // New methods for enhanced contract management
    public function generateQrCode()
    {
        if (empty($this->qr_code)) {
            $this->qr_code = 'QR-' . $this->contract_number . '-' . time();
            $this->save();
        }
        return $this->qr_code;
    }

    public function approve($approvedBy)
    {
        $this->update([
            'is_approved' => true,
            'approved_at' => now(),
            'approved_by' => $approvedBy
        ]);
        
        // Auto-calculate and set start_date and end_date based on approval date
        $this->calculateAndSetDates();
    }
    
    /**
     * Calculate and set start_date and end_date based on approved_at and rental_period
     */
    public function calculateAndSetDates()
    {
        if ($this->approved_at && $this->quotation) {
            // Load quotation if not already loaded
            if (!$this->relationLoaded('quotation')) {
                $this->load('quotation');
            }
            
            $startDate = \Carbon\Carbon::parse($this->approved_at);
            $endDate = $this->calculateEndDate($startDate, $this->quotation->rental_period);
            
            $this->update([
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate
            ]);
            
            \Log::info('Contract dates auto-calculated', [
                'contract_id' => $this->id,
                'contract_number' => $this->contract_number,
                'approved_at' => $this->approved_at->toDateString(),
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate,
                'rental_period' => $this->quotation->rental_period
            ]);
        }
    }
    
    /**
     * Calculate end date based on start date and rental period
     * Format: "3 bulan", "6 hari", "12 bulan", etc.
     * Returns: start_date + period - 1 day
     */
    private function calculateEndDate($startDate, $rentalPeriod)
    {
        $start = \Carbon\Carbon::parse($startDate);
        
        if (empty($rentalPeriod)) {
            \Log::warning("Empty rental_period, defaulting to 12 months");
            return $start->copy()->addMonths(12)->subDay()->toDateString();
        }
        
        // Normalize rental period string
        $period = strtolower(trim($rentalPeriod));
        
        // Parse format like "3 bulan", "6 hari", "12 bulan"
        // Support both Indonesian and English
        if (preg_match('/(\d+)\s*(bulan|month|months|hari|day|days|tahun|year|years)/i', $period, $matches)) {
            $number = (int) $matches[1];
            $unit = strtolower(trim($matches[2]));
            
            if (in_array($unit, ['hari', 'day', 'days'])) {
                return $start->copy()->addDays($number)->subDay()->toDateString();
            } elseif (in_array($unit, ['bulan', 'month', 'months'])) {
                return $start->copy()->addMonths($number)->subDay()->toDateString();
            } elseif (in_array($unit, ['tahun', 'year', 'years'])) {
                return $start->copy()->addYears($number)->subDay()->toDateString();
            }
        }
        
        // If parsing failed, try to parse as number only (assume months)
        if (preg_match('/^(\d+)$/', $period, $matches)) {
            $number = (int) $matches[1];
            return $start->copy()->addMonths($number)->subDay()->toDateString();
        }
        
        // Default to 12 months if can't parse
        \Log::warning("Could not parse rental_period: {$rentalPeriod}, defaulting to 12 months");
        return $start->copy()->addMonths(12)->subDay()->toDateString();
    }

    public function post($postedBy)
    {
        $this->update([
            'is_posted' => true,
            'posted_at' => now(),
            'posted_by' => $postedBy
        ]);
    }

    public function generateSchedule()
    {
        if (!$this->schedule_generated) {
            // Logic to generate schedule based on contract period and service frequency
            $this->update([
                'schedule_generated' => true,
                'schedule_generated_at' => now()
            ]);
        }
    }

    public function canEditField($fieldName)
    {
        $editableFields = [
            'signatory_name', 'signatory_position', 'signatory_npwp', 'signatory_address',
            'marketing_name', 'marketing_phone', 'marketing_email'
        ];

        return in_array($fieldName, $editableFields) && $this->is_approved;
    }

    public function getRemarksByType($type)
    {
        return $this->contractRemarks()
            ->where('remark_type', $type)
            ->where('is_active', true)
            ->get();
    }

    // Digital Signature Methods
    public function addDigitalSignature($signatureData, $signedBy, $position = null)
    {
        $this->update([
            'digital_signature_data' => $signatureData,
            'signature_at' => now(),
            'signed_by' => $signedBy,
            'signature_position' => $position,
            'contract_status' => 'signed'
        ]);
    }

    public function hasDigitalSignature()
    {
        return !empty($this->digital_signature_data) && !empty($this->signature_at);
    }

    public function getDigitalSignatureStatusAttribute()
    {
        if ($this->hasDigitalSignature()) {
            return 'signed';
        } elseif ($this->contract_status === 'pending_signature') {
            return 'pending';
        }
        return 'not_signed';
    }

    // NPWP Integration Methods
    public function verifyNPWP($npwpNumber, $verificationData = [])
    {
        $this->update([
            'npwp_number' => $npwpNumber,
            'npwp_verified' => true,
            'npwp_verified_at' => now(),
            'npwp_verification_data' => $verificationData
        ]);
    }

    public function isNPWPVerified()
    {
        return $this->npwp_verified && !empty($this->npwp_verified_at);
    }

    public function getNPWPStatusAttribute()
    {
        if ($this->isNPWPVerified()) {
            return 'verified';
        } elseif (!empty($this->npwp_number)) {
            return 'pending_verification';
        }
        return 'not_provided';
    }

    // Auto-generate Schedule Methods
    public function generateScheduleAutomatically()
    {
        if (!$this->schedule_generated && $this->is_posted) {
            $scheduleData = $this->calculateScheduleData();
            
            $this->update([
                'schedule_generated' => true,
                'schedule_generated_at' => now(),
                'schedule_data' => $scheduleData
            ]);
        }
    }

    public function calculateScheduleData()
    {
        $scheduleData = [
            'generated_at' => now()->toISOString(),
            'contract_period' => $this->contract_period_type,
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date->toDateString(),
            'service_frequency' => $this->getServiceFrequency(),
            'schedules' => $this->generateServiceSchedules()
        ];

        return $scheduleData;
    }

    public function getServiceFrequency()
    {
        // Determine service frequency based on contract type and period
        if ($this->contract_period_type === 'job_order') {
            return 'daily';
        } elseif ($this->contract_period_type === 'contract') {
            return 'monthly';
        }
        return 'weekly';
    }

    public function generateServiceSchedules()
    {
        $schedules = [];
        $currentDate = $this->start_date;
        $frequency = $this->getServiceFrequency();

        while ($currentDate <= $this->end_date) {
            $schedules[] = [
                'scheduled_date' => $currentDate->toDateString(),
                'status' => 'pending',
                'service_type' => 'maintenance'
            ];

            // Increment based on frequency
            if ($frequency === 'daily') {
                $currentDate = $currentDate->addDay();
            } elseif ($frequency === 'weekly') {
                $currentDate = $currentDate->addWeek();
            } elseif ($frequency === 'monthly') {
                $currentDate = $currentDate->addMonth();
            }
        }

        return $schedules;
    }

    // Contract Status Methods
    public function updateContractStatus($status)
    {
        $validStatuses = ['draft', 'pending_signature', 'signed', 'active', 'completed', 'cancelled', 'rejected', 'waiting_for_approval'];

        if (in_array($status, $validStatuses)) {
            $this->update(['contract_status' => $status]);

            return true;
        }

        return false;
    }

    public function getContractStatusBadgeAttribute()
    {
        $badges = [
            'draft' => 'secondary',
            'pending_signature' => 'warning',
            'signed' => 'info',
            'active' => 'success',
            'completed' => 'primary',
            'cancelled' => 'danger',
            'rejected' => 'danger',
            'waiting_for_approval' => 'warning'
        ];

        return $badges[$this->contract_status] ?? 'secondary';
    }

    public function getContractStatusTextAttribute()
    {
        $texts = [
            'draft' => 'Draft',
            'pending_signature' => 'Menunggu Tanda Tangan',
            'signed' => 'Ditandatangani',
            'active' => 'Aktif',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            'rejected' => 'Ditolak',
            'waiting_for_approval' => 'Menunggu Persetujuan'
        ];

        return $texts[$this->contract_status] ?? $this->contract_status;
    }

    // Validation Methods
    public function canBeSigned()
    {
        return $this->contract_status === 'pending_signature' && 
               $this->is_approved && 
               !$this->hasDigitalSignature();
    }

    public function canGenerateSchedule()
    {
        return $this->is_posted && 
               !$this->schedule_generated && 
               $this->contract_status === 'active';
    }

    public function isReadyForPosting()
    {
        return $this->is_approved && 
               $this->hasDigitalSignature() && 
               $this->isNPWPVerified() &&
               $this->contract_status === 'signed';
    }
    /**
     * Cancel semua job schedule yang masih aktif (belum selesai) dari contract ini.
     * Dipanggil saat contract di-renewal agar job schedule lama tidak dijadwalkan kembali.
     *
     * @param string|null $reason Alasan pembatalan
     * @return int Jumlah job schedule yang di-cancel
     */
    public function cancelRemainingJobSchedules($reason = 'Contract telah di-renewal')
    {
        $contractNumber = $this->contract_number;
        $finalStatuses = array_unique(array_merge(self::finalJobScheduleStatuses(), ['suspend', 'dpf']));

        $activeJobSchedules = \App\Models\JobSchedule::query()
            ->where(function ($query) use ($contractNumber) {
                $query->whereHas('jobAdvice', function ($jobAdviceQuery) {
                    $jobAdviceQuery->where('contract_id', $this->id);
                });

                if ($contractNumber) {
                    $query->orWhere('contract_number', $contractNumber);
                }
            })
            ->where(function ($query) use ($finalStatuses) {
                $query->whereNull('status')
                    ->orWhereNotIn('status', $finalStatuses);
            })
            ->get();

        $cancelledCount = 0;

        foreach ($activeJobSchedules as $jobSchedule) {
            $jobSchedule->update([
                'status' => 'cancelled',
                'internal_notes' => trim(($jobSchedule->internal_notes ?? '') . "\n[AUTO-CANCELLED] {$reason} pada " . now()->format('d/m/Y H:i')),
                'updated_by' => \Illuminate\Support\Facades\Auth::id() ?? 1
            ]);
            $cancelledCount++;
        }

        \Illuminate\Support\Facades\Log::info("Contract #{$this->contract_number}: {$cancelledCount} job schedule(s) auto-cancelled karena renewal", [
            'contract_id' => $this->id,
            'contract_number' => $this->contract_number,
            'cancelled_count' => $cancelledCount,
            'cancelled_job_ids' => $activeJobSchedules->pluck('id')->toArray()
        ]);

        return $cancelledCount;
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    /**
     * Calculate the next invoice date based on preference.
     *
     * @param string|null $preference
     * @param string|null $manualDate
     * @param string|\Carbon\Carbon|null $baseDate
     * @return \Carbon\Carbon|null
     */
    public function calculateInvoiceDate($preference = null, $manualDate = null, $baseDate = null)
    {
        $preference = $preference ?? $this->invoice_date_preference ?? 'manual';
        $newDate = null;
        $base = $baseDate ? \Carbon\Carbon::parse($baseDate) : \Carbon\Carbon::now();

        switch ($preference) {
            case 'first_service':
                // Option 1: First Service Date (CSR)
                // Try from Contract first, then JobSchedule
                if ($this->first_service_date) {
                    $newDate = \Carbon\Carbon::parse($this->first_service_date);
                } else {
                    // Look for service_first job
                    $firstJob = \App\Models\JobSchedule::where('contract_number', $this->contract_number)
                        ->where('type', 'service_first')
                        ->orderBy('schedule_date', 'asc')
                        ->first();
                    
                    if ($firstJob) {
                        // User Request: "contract datenya yang berasal dari ba date" implies preference for BA Date
                        if ($firstJob->ba_date) {
                            $newDate = \Carbon\Carbon::parse($firstJob->ba_date);
                        } elseif ($firstJob->schedule_date) {
                            $newDate = \Carbon\Carbon::parse($firstJob->schedule_date);
                        }
                    }
                }
                break;

            case 'contract_date':
                // Option 2: Contract Date / Effective Date
                // User Request: Prioritize start_date as it is the effective date
                if ($this->start_date) {
                    $newDate = \Carbon\Carbon::parse($this->start_date);
                } elseif ($this->contract_date) {
                    $newDate = \Carbon\Carbon::parse($this->contract_date);
                }
                break;

            case 'end_of_month':
                // Option 3: End of Month of the base date
                $newDate = $base->copy()->endOfMonth();
                break;

            case 'manual':
                // Option 4: Manual Date
                if ($manualDate) {
                    try {
                        $newDate = \Carbon\Carbon::parse($manualDate);
                    } catch (\Exception $e) {
                        // invalid date, handle gracefully or return null
                    }
                }
                break;
        }

        return $newDate;
    }
}
