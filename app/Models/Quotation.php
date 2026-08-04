<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;
use Illuminate\Validation\ValidationException;

class Quotation extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $fillable = [
        'quotation_number',
        'prospect_id',
        'customer_id',
        'survey_id',
        'quotation_date',
        'valid_until',
        'company_name',
        'pic_name',
        'billing_methods',
        'status',
        'rental_period',
        'rental_unit',
        'payment_method',
        'terms_of_payment',
        'marketing_id',
        'branch_id', // Multi-branch support
        'approved_by',
        'date_approved',
        'internal_notes',
        'additional_notes',
        'quotation_type',
        'existing_contract_id',
        'revision_number',
        'is_latest_revision',
        'total_amount',
        'discount_amount',
        'tax_amount',
        'grand_total',
        'terms_conditions',
        'created_by',
        'updated_by',
        // New fields for enhancements
        'price_basis',
        'top_months',
        'goal_sq'
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'valid_until' => 'date',
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'is_latest_revision' => 'boolean',
        'revision_number' => 'integer',
        'date_approved' => 'datetime'
    ];

    protected $appends = [
        'approved_by_display_name',
        'is_auto_approved',
    ];

    // Relationships
    public function prospect()
    {
        return $this->belongsTo(Prospect::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    // Multiple Survey Enhancement relationships
    public function quotationSurveys()
    {
        return $this->hasMany(QuotationSurvey::class);
    }

    public function surveys()
    {
        return $this->belongsToMany(Survey::class, 'quotation_surveys')
            ->withPivot('added_at', 'added_by', 'sort_order')
            ->withTimestamps();
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

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getIsAutoApprovedAttribute(): bool
    {
        return $this->status === 'approved'
            && $this->approved_by === null
            && $this->date_approved !== null;
    }

    public function getApprovedByDisplayNameAttribute(): string
    {
        if ($this->approver) {
            return $this->approver->name;
        }

        if ($this->is_auto_approved) {
            $marketingName = $this->marketing?->name
                ?? $this->creator?->name
                ?? 'System';

            return "{$marketingName} (Auto Approve)";
        }

        return 'N/A';
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class, 'quotation_id');
    }

    public static function formatTermsOfPaymentLabel($value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $value = trim((string) $value);

        if (strcasecmp($value, 'Tahunan') === 0) {
            return '1x Advance';
        }

        return ucwords(str_replace('_', ' ', $value));
    }

    public function getTermsOfPaymentLabelAttribute(): string
    {
        return self::formatTermsOfPaymentLabel($this->terms_of_payment);
    }

    public function existingContract()
    {
        return $this->belongsTo(Contract::class, 'existing_contract_id')
            ->withTrashed()
            ->withoutGlobalScopes();
    }

    public function quotationDetails()
    {
        return $this->hasMany(QuotationDetail::class);
    }

    public function revisions()
    {
        return $this->hasMany(QuotationRevision::class);
    }

    public function latestRevision()
    {
        return $this->hasOne(QuotationRevision::class)->where('is_latest', true);
    }

    public function approvals()
    {
        return $this->hasMany(QuotationApproval::class);
    }

    public function freeTrials()
    {
        return $this->hasMany(FreeTrial::class);
    }

    public function quotationRooms()
    {
        return $this->hasMany(QuotationRoom::class);
    }

    // Alias for quotationRooms for easier access
    public function rooms()
    {
        return $this->quotationRooms();
    }

    public function quotationRentals()
    {
        return $this->hasMany(QuotationRental::class);
    }

    public function getRentalPeriodMonthsAttribute(): int
    {
        $period = (int) $this->rental_period;

        if ($period <= 0) {
            return 0;
        }

        return $this->rental_unit === 'hari'
            ? ($period < 30 ? 1 : (int) ceil($period / 30))
            : $period;
    }

    public function getServiceFrequencyPeriodValidationErrors(): array
    {
        $rentalMonths = $this->rental_period_months;

        if ($rentalMonths <= 0) {
            return [];
        }

        $this->loadMissing([
            'quotationDetails.masterRental.serviceFrequency',
            'quotationRentals.masterRental.serviceFrequency',
        ]);

        $errors = [];
        $seenRentalFrequencyKeys = [];

        foreach ([$this->quotationDetails, $this->quotationRentals] as $items) {
            foreach ($items as $item) {
                $rental = $item->masterRental;
                $serviceFrequency = $rental?->serviceFrequency;
                $frequencyMonths = (int) ($serviceFrequency?->frequency_months ?? 0);

                if (! $rental || ! $serviceFrequency || $frequencyMonths <= 1) {
                    continue;
                }

                $key = ($rental->id ? 'id:'.$rental->id : 'name:'.$rental->rental_name).'|'.$frequencyMonths;

                if (isset($seenRentalFrequencyKeys[$key])) {
                    continue;
                }

                $seenRentalFrequencyKeys[$key] = true;

                if ($rentalMonths % $frequencyMonths === 0) {
                    continue;
                }

                $times = max(1, (int) ($serviceFrequency->frequency_times_per_month ?? 1));
                $errors[] = sprintf(
                    '%s memiliki frekuensi service %dx per %d bulan, tetapi periode sewa %d bulan. Periode sewa harus kelipatan %d bulan agar jadwal service habis rata.',
                    $rental->rental_name ?: 'Rental',
                    $times,
                    $frequencyMonths,
                    $rentalMonths,
                    $frequencyMonths
                );
            }
        }

        return $errors;
    }

    public function ensureServiceFrequencyPeriodCompatible(): void
    {
        $errors = $this->getServiceFrequencyPeriodValidationErrors();

        if ($errors) {
            throw ValidationException::withMessages([
                'rental_period' => $errors,
            ]);
        }
    }

    public function quotationPics()
    {
        return $this->hasMany(QuotationPic::class);
    }

    public function primaryPic()
    {
        return $this->hasOne(QuotationPic::class)->where('is_primary', true);
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByProspect($query, $prospectId)
    {
        return $query->where('prospect_id', $prospectId);
    }

    public function scopeBySurvey($query, $surveyId)
    {
        return $query->where('survey_id', $surveyId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('quotation_date', [$startDate, $endDate]);
    }

    public function scopeValid($query)
    {
        return $query->where('valid_until', '>=', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('valid_until', '<', now());
    }

    /**
     * Scope to filter only the latest revision quotations.
     * Use this for Job Advice and Contract dropdowns.
     */
    public function scopeUsable($query)
    {
        return $query
            ->where('is_latest_revision', true)
            ->whereNotExists(function ($subQuery) {
                $subQuery->selectRaw('1')
                    ->from('quotations as newer_quotations')
                    ->whereColumn('newer_quotations.quotation_number', 'quotations.quotation_number')
                    ->whereColumn('newer_quotations.revision_number', '>', 'quotations.revision_number')
                    ->whereNull('newer_quotations.deleted_at');
            });
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        $statuses = [
            'draft' => 'Draft',
            'sent' => 'Terkirim',
            'waiting_for_approval' => 'Menunggu Persetujuan',
            'approved' => 'Disetujui Manager',
            'accepted' => 'Disetujui Pelanggan',
            'rejected' => 'Ditolak',
            'cancelled' => 'Dibatalkan',
            'expired' => 'Kadaluarsa',
            'contract' => 'Contract'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getIsValidAttribute()
    {
        return $this->valid_until >= now();
    }

    public function getIsExpiredAttribute()
    {
        return $this->valid_until < now();
    }

    public function getFormattedDateAttribute()
    {
        return $this->quotation_date ? $this->quotation_date->format('d/m/Y') : '-';
    }

    public function getFormattedValidUntilAttribute()
    {
        return $this->valid_until ? $this->valid_until->format('d/m/Y') : '-';
    }

    public function getFormattedTotalAmountAttribute()
    {
        return 'Rp ' . number_format($this->total_amount, 0, ',', '.');
    }

    public function getFormattedGrandTotalAttribute()
    {
        return 'Rp ' . number_format($this->grand_total, 0, ',', '.');
    }

    // Approval Methods
    public function requiresApproval()
    {
        // Check if any rental requires approval
        return $this->quotationRentals()->where('requires_approval', true)->exists();
    }

    public function hasBottomPriceIssues()
    {
        // Check if any rental doesn't have bottom price
        return $this->quotationRentals()->where('has_bottom_price', false)->exists();
    }

    public function canSelfApprove()
    {
        // Marketing can self-approve if total >= bottom price total
        $totalAmount = $this->grand_total;
        $bottomPriceTotal = $this->quotationRentals()
            ->where('has_bottom_price', true)
            ->sum('bottom_price');
        
        return $totalAmount >= $bottomPriceTotal;
    }

    public function getPriceSlabSummary()
    {
        $summary = [];
        
        foreach ($this->quotationRentals as $rental) {
            $priceSlabInfo = $rental->getPriceSlabInfo();
            $summary[] = [
                'rental_name' => $rental->masterRental->rental_name,
                'aroma_name' => $rental->aroma_name,
                'quantity' => $rental->quantity,
                'unit_price' => $rental->unit_price,
                'slab_name' => $priceSlabInfo['slab_name'],
                'discount_percentage' => $priceSlabInfo['discount_percentage'],
                'bottom_price' => $priceSlabInfo['bottom_price'],
                'requires_approval' => $rental->requires_approval,
                'has_slab' => $priceSlabInfo['has_slab']
            ];
        }
        
        return $summary;
    }

    public function getApprovalSummary()
    {
        $rentals = $this->quotationRentals;
        $totalRentals = $rentals->count();
        $rentalsWithSlab = $rentals->where('has_bottom_price', true)->count();
        $rentalsRequiringApproval = $rentals->where('requires_approval', true)->count();
        
        return [
            'total_rentals' => $totalRentals,
            'rentals_with_slab' => $rentalsWithSlab,
            'rentals_requiring_approval' => $rentalsRequiringApproval,
            'can_self_approve' => $this->canSelfApprove(),
            'requires_approval' => $this->requiresApproval(),
            'has_bottom_price_issues' => $this->hasBottomPriceIssues()
        ];
    }

    public function createApproval($approvalType, $requestedBy, $data = [])
    {
        return $this->approvals()->create([
            'approval_type' => $approvalType,
            'status' => 'pending',
            'requested_by' => $requestedBy,
            'requested_at' => now(),
            'approval_data' => $data
        ]);
    }

    public function approveQuotation($approvedBy, $notes = null)
    {
        $this->ensureServiceFrequencyPeriodCompatible();

        $this->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'date_approved' => now()
        ]);

        // Update all pending approvals
        $this->approvals()->where('status', 'pending')->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
            'approval_notes' => $notes
        ]);
    }

    public function rejectQuotation($approvedBy, $reason)
    {
        $this->update([
            'status' => 'rejected'
        ]);

        // Update all pending approvals
        $this->approvals()->where('status', 'pending')->update([
            'status' => 'rejected',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
            'rejection_reason' => $reason
        ]);
    }

    public function createRevision($revisionData, $createdBy)
    {
        // Set current revision as not latest
        $this->revisions()->update(['is_latest' => false]);

        // Create new revision
        $revision = $this->revisions()->create(array_merge($revisionData, [
            'revision_number' => $this->generateRevisionNumber(),
            'is_latest' => true,
            'created_by' => $createdBy
        ]));

        return $revision;
    }

    public function generateRevisionNumber()
    {
        $count = $this->revisions()->count() + 1;
        return $this->quotation_number . '-R' . str_pad($count, 2, '0', STR_PAD_LEFT);
    }

    public function getLatestRevision()
    {
        return $this->revisions()->where('is_latest', true)->first();
    }

    public function hasActiveFreeTrials()
    {
        return $this->freeTrials()->whereIn('status', ['approved', 'active'])->exists();
    }

    public function canCreateContract()
    {
        return $this->status === 'approved'
            && !$this->contracts()->exists()
            && !$this->hasActiveFreeTrials()
            && empty($this->getServiceFrequencyPeriodValidationErrors());
    }

    /**
     * Copy this quotation as a new draft revision.
     * - Keeps the same quotation_number
     * - Increments revision_number
     * - Sets the old quotation's is_latest_revision to false
     * - Copies all related data (details, rooms, surveys)
     */
    public function copyAsRevision()
    {
        return \DB::transaction(function () {
            // Mark current as not latest
            $this->update(['is_latest_revision' => false]);

            // Calculate new revision number
            $newRevisionNumber = $this->revision_number + 1;

            // Create the new quotation copy
            $newQuotation = $this->replicate();
            $newQuotation->status = 'draft';
            $newQuotation->revision_number = $newRevisionNumber;
            $newQuotation->is_latest_revision = true;
            $newQuotation->approved_by = null;
            $newQuotation->date_approved = null;
            $newQuotation->created_at = now();
            $newQuotation->updated_at = now();
            $newQuotation->save();

            // Copy quotation details
            foreach ($this->quotationDetails as $detail) {
                $newDetail = $detail->replicate();
                $newDetail->quotation_id = $newQuotation->id;
                $newDetail->save();
            }

            // Copy quotation rooms
            foreach ($this->quotationRooms as $room) {
                $newRoom = $room->replicate();
                $newRoom->quotation_id = $newQuotation->id;
                $newRoom->save();
            }

            // Copy survey relationships
            foreach ($this->surveys as $survey) {
                $newQuotation->surveys()->attach($survey->id, [
                    'added_at' => now(),
                    'added_by' => auth()->id()
                ]);
            }

            return $newQuotation;
        });
    }
}
