<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;

class ContractRenewal extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail;

    protected $fillable = [
        'renewal_number',
        'contract_id',
        'customer_id',
        'current_end_date',
        'proposed_start_date',
        'proposed_end_date',
        'renewal_duration_months',
        'same_terms',
        'previous_total_value',
        'new_total_value',
        'terms_changes',
        'renewal_notes',
        'price_adjustment',
        'price_adjustment_percentage',
        'price_adjustment_reason',
        'reminder_sent',
        'reminder_sent_at',
        'days_before_expiry',
        'status',
        'approval_notes',
        'rejection_reason',
        'new_contract_id',
        'auto_renewal',
        'customer_approved_at',
        'internal_approved_at',
        'completed_at',
        'rejected_at',
        'initiated_by',
        'customer_approved_by',
        'internal_approved_by',
        'rejected_by',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'current_end_date' => 'date',
        'proposed_start_date' => 'date',
        'proposed_end_date' => 'date',
        'renewal_duration_months' => 'integer',
        'same_terms' => 'boolean',
        'previous_total_value' => 'decimal:2',
        'new_total_value' => 'decimal:2',
        'price_adjustment' => 'boolean',
        'price_adjustment_percentage' => 'decimal:2',
        'reminder_sent' => 'boolean',
        'reminder_sent_at' => 'datetime',
        'days_before_expiry' => 'integer',
        'auto_renewal' => 'boolean',
        'customer_approved_at' => 'datetime',
        'internal_approved_at' => 'datetime',
        'completed_at' => 'datetime',
        'rejected_at' => 'datetime'
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_CUSTOMER = 'pending_customer';
    const STATUS_CUSTOMER_APPROVED = 'customer_approved';
    const STATUS_PENDING_INTERNAL = 'pending_internal';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    // Relationships
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function newContract()
    {
        return $this->belongsTo(Contract::class, 'new_contract_id');
    }

    public function initiatedBy()
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function customerApprovedBy()
    {
        return $this->belongsTo(User::class, 'customer_approved_by');
    }

    public function internalApprovedBy()
    {
        return $this->belongsTo(User::class, 'internal_approved_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByContract($query, $contractId)
    {
        return $query->where('contract_id', $contractId);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopePendingCustomer($query)
    {
        return $query->where('status', self::STATUS_PENDING_CUSTOMER);
    }

    public function scopeCustomerApproved($query)
    {
        return $query->where('status', self::STATUS_CUSTOMER_APPROVED);
    }

    public function scopePendingInternal($query)
    {
        return $query->where('status', self::STATUS_PENDING_INTERNAL);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeAutoRenewal($query)
    {
        return $query->where('auto_renewal', true);
    }

    public function scopeExpiringIn($query, $days)
    {
        return $query->whereDate('current_end_date', '<=', now()->addDays($days))
                    ->whereDate('current_end_date', '>=', now());
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        $statuses = [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING_CUSTOMER => 'Pending Customer Approval',
            self::STATUS_CUSTOMER_APPROVED => 'Customer Approved',
            self::STATUS_PENDING_INTERNAL => 'Pending Internal Approval',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled'
        ];
        return $statuses[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            self::STATUS_DRAFT => 'badge-secondary',
            self::STATUS_PENDING_CUSTOMER => 'badge-warning',
            self::STATUS_CUSTOMER_APPROVED => 'badge-info',
            self::STATUS_PENDING_INTERNAL => 'badge-warning',
            self::STATUS_APPROVED => 'badge-success',
            self::STATUS_REJECTED => 'badge-danger',
            self::STATUS_COMPLETED => 'badge-primary',
            self::STATUS_CANCELLED => 'badge-dark'
        ];
        return $badges[$this->status] ?? 'badge-secondary';
    }

    public function getDaysUntilExpiryAttribute()
    {
        if (!$this->current_end_date) {
            return null;
        }
        return now()->diffInDays($this->current_end_date, false);
    }

    // Boolean accessors
    public function getIsDraftAttribute()
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function getIsPendingCustomerAttribute()
    {
        return $this->status === self::STATUS_PENDING_CUSTOMER;
    }

    public function getIsCustomerApprovedAttribute()
    {
        return $this->status === self::STATUS_CUSTOMER_APPROVED;
    }

    public function getIsPendingInternalAttribute()
    {
        return $this->status === self::STATUS_PENDING_INTERNAL;
    }

    public function getIsApprovedAttribute()
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function getIsRejectedAttribute()
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function getIsCompletedAttribute()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function getIsCancelledAttribute()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    // Methods
    public function submitToCustomer()
    {
        $this->update([
            'status' => self::STATUS_PENDING_CUSTOMER
        ]);
        // TODO: Send notification to customer
    }

    public function customerApprove($approvedBy)
    {
        $this->update([
            'status' => self::STATUS_CUSTOMER_APPROVED,
            'customer_approved_at' => now(),
            'customer_approved_by' => $approvedBy
        ]);
        // Auto-submit to internal approval
        $this->submitToInternal();
    }

    public function submitToInternal()
    {
        $this->update([
            'status' => self::STATUS_PENDING_INTERNAL
        ]);
        // TODO: Send notification to internal approver
    }

    public function internalApprove($approvedBy, $notes = null)
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'internal_approved_at' => now(),
            'internal_approved_by' => $approvedBy,
            'approval_notes' => $notes
        ]);
        // TODO: Auto-generate new contract
    }

    public function reject($rejectedBy, $reason)
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'rejected_at' => now(),
            'rejected_by' => $rejectedBy,
            'rejection_reason' => $reason
        ]);
    }

    public function complete($newContractId)
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'new_contract_id' => $newContractId
        ]);
    }

    public function cancel()
    {
        $this->update([
            'status' => self::STATUS_CANCELLED
        ]);
    }

    public function sendReminder()
    {
        $this->update([
            'reminder_sent' => true,
            'reminder_sent_at' => now()
        ]);
        // TODO: Send reminder notification
    }

    /**
     * Calculate new total value with price adjustment
     */
    public function calculateNewTotalValue()
    {
        if (!$this->price_adjustment || !$this->price_adjustment_percentage) {
            return $this->previous_total_value;
        }

        $adjustment = ($this->previous_total_value * $this->price_adjustment_percentage) / 100;
        return $this->previous_total_value + $adjustment;
    }

    /**
     * Generate unique renewal number
     */
    public static function generateRenewalNumber()
    {
        $prefix = 'RNW-' . date('Ymd');
        $count = self::whereDate('created_at', today())->count() + 1;
        return $prefix . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate renewal window days based on contract duration
     * Logic: 
     * - Contract >= 12 months → 90-120 days before (3-4 months)
     * - Contract 6-11 months → 60-90 days before (2-3 months)
     * - Contract 4-5 months → 60 days before (2 months)
     * - Contract 3 months → 30 days before (1 month)
     * - Contract < 3 months → 30 days minimum
     * 
     * Formula: min(120, max(30, contract_days / 3))
     */
    public static function calculateRenewalWindowDays($contractStartDate, $contractEndDate)
    {
        $contractDays = \Carbon\Carbon::parse($contractStartDate)->diffInDays($contractEndDate);
        
        // Minimum 30 days, maximum 120 days
        // Renewal window = approximately 1/3 of contract duration
        $renewalWindowDays = min(120, max(30, intval($contractDays / 3)));
        
        \Log::info("Contract duration: {$contractDays} days, Renewal window: {$renewalWindowDays} days");
        
        return $renewalWindowDays;
    }

    /**
     * Auto-create renewals for expiring contracts (Command/Schedule)
     * Uses dynamic renewal window based on contract duration
     */
    public static function autoCreateForExpiringContracts()
    {
        $activeContracts = Contract::where('contract_status', 'active')
            ->whereDate('end_date', '>=', now())
            ->whereDoesntHave('renewals', function ($query) {
                // Prevent duplicate: no active renewal quotations
                $query->whereIn('status', [
                    self::STATUS_DRAFT,
                    self::STATUS_PENDING_CUSTOMER,
                    self::STATUS_CUSTOMER_APPROVED,
                    self::STATUS_PENDING_INTERNAL,
                    self::STATUS_APPROVED
                ]);
            })
            ->get();

        $created = [];
        foreach ($activeContracts as $contract) {
            $eligibility = self::isEligibleForRenewal($contract->id);

            if ($eligibility['eligible'] && ($eligibility['days_until_expiry'] ?? 0) > 0) {
                $currentEndDate = $contract->actual_end_date ?? $contract->end_date;
                $renewal = self::create([
                    'renewal_number' => self::generateRenewalNumber(),
                    'contract_id' => $contract->id,
                    'customer_id' => $contract->customer_id,
                    'current_end_date' => $currentEndDate,
                    'proposed_start_date' => \Carbon\Carbon::parse($currentEndDate)->addDay(),
                    'proposed_end_date' => \Carbon\Carbon::parse($currentEndDate)->addYear(),
                    'renewal_duration_months' => 12,
                    'same_terms' => true,
                    'previous_total_value' => $contract->total_value,
                    'status' => self::STATUS_DRAFT,
                    'auto_renewal' => true,
                    'days_before_expiry' => $eligibility['days_until_expiry'],
                    'created_by' => 1 // System user
                ]);
                $created[] = $renewal;
                
                \Log::info("Auto-created renewal for Contract {$contract->contract_number}, expires in {$eligibility['days_until_expiry']} days, window: {$eligibility['renewal_window_days']} days");
            }
        }

        \Log::info("Auto-created " . count($created) . " contract renewals");
        return $created;
    }

    /**
     * Check if contract is eligible for renewal (manual check)
     * Uses actual_start_date and actual_end_date (BA date based)
     */
    public static function isEligibleForRenewal($contractId)
    {
        $contract = Contract::find($contractId);
        if (!$contract || $contract->contract_status !== 'active') {
            return ['eligible' => false, 'reason' => 'Contract not active'];
        }

        $blockReason = $contract->getRenewalBlockReason();
        if ($blockReason) {
            return ['eligible' => false, 'reason' => $blockReason];
        }

        // Check if already has active renewal
        $hasActiveRenewal = $contract->renewals()
            ->whereIn('status', [
                self::STATUS_DRAFT,
                self::STATUS_PENDING_CUSTOMER,
                self::STATUS_CUSTOMER_APPROVED,
                self::STATUS_PENDING_INTERNAL,
                self::STATUS_APPROVED
            ])
            ->exists();

        if ($hasActiveRenewal) {
            return ['eligible' => false, 'reason' => 'Active renewal quotation already exists'];
        }

        // Check if contract has actual dates (BA date exists)
        $actualStartDate = $contract->actual_start_date;
        $actualEndDate = $contract->actual_end_date;
        
        if (!$actualStartDate || !$actualEndDate) {
            return [
                'eligible' => false,
                'reason' => "Contract {$contract->contract_number} belum dimulai/belum memiliki BA date. Renewal hanya bisa dibuat setelah seluruh job contract lama selesai.",
                'days_until_expiry' => null,
                'renewal_window_days' => null
            ];
        }

        // Check if within renewal window - use actual dates
        $renewalWindowDays = self::calculateRenewalWindowDays($actualStartDate, $actualEndDate);
    
        // Use date-only comparison (startOfDay) to avoid timezone issues
        // This ensures contracts expiring TODAY are still eligible for renewal
        $today = now()->startOfDay();
        $endDate = \Carbon\Carbon::parse($actualEndDate)->startOfDay();
        $daysUntilExpiry = $today->diffInDays($endDate, false);

        if ($daysUntilExpiry > $renewalWindowDays) {
            return [
                'eligible' => false, 
                'reason' => "Too early. Renewal available {$renewalWindowDays} days before expiry. Days until expiry: {$daysUntilExpiry}"
            ];
        }

        // Contract is expired if end_date is before today (not including today)
        if ($daysUntilExpiry < 0) {
            return ['eligible' => false, 'reason' => 'Contract already expired'];
        }

        return [
            'eligible' => true, 
            'days_until_expiry' => $daysUntilExpiry,
            'renewal_window_days' => $renewalWindowDays
        ];
    }
}

