<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;
use App\Traits\HasComprehensiveAuditTrail;
use App\Models\User;
use App\Models\Contract;

class CommissionCalculation extends Model
{
    use SoftDeletes, AutoFilterable;

    protected $fillable = [
        'user_id',
        'achievement_period_id',
        'marketing_target_id',
        'contract_id',
        'calculation_type',
        'base_amount',
        'net_value',
        'commission_rate',
        'commission_level_id',
        'commission_amount',
        'bonus_amount',
        'penalty_amount',
        'final_amount',
        'original_amount',
        'status',
        'calculation_date',
        'payment_date',
        'payout_requested_at',
        'payout_requested_by',
        'calculation_notes',
        'is_installed',
        'cash_receipt_date',
        'cr_variable_id',
        'cr_due_date',
        'is_cr_expired',
        'is_commission_void',
        'commission_transfer_id',
        'approved_by',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'net_value' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'bonus_amount' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'original_amount' => 'decimal:2',
        'calculation_date' => 'date',
        'payment_date' => 'date',
        'payout_requested_at' => 'datetime',
        'is_installed' => 'boolean',
        'cash_receipt_date' => 'date',
        'cr_due_date' => 'date',
        'is_cr_expired' => 'boolean',
        'is_commission_void' => 'boolean'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function achievementPeriod()
    {
        return $this->belongsTo(AchievementPeriod::class);
    }

    public function contract()
    {
        return $this->belongsTo(\App\Models\Contract::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function achievements()
    {
        return $this->hasMany(Achievement::class);
    }

    public function commissionPayments()
    {
        return $this->hasMany(CommissionPayment::class);
    }

    public function marketingTarget()
    {
        return $this->belongsTo(MarketingTarget::class);
    }

    public function commissionLevel()
    {
        return $this->belongsTo(CommissionLevel::class);
    }

    public function crVariable()
    {
        return $this->belongsTo(CrVariable::class);
    }

    public function commissionTransfer()
    {
        return $this->belongsTo(CommissionTransfer::class);
    }

    // Scopes
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByPeriod($query, $periodId)
    {
        return $query->where('achievement_period_id', $periodId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('calculation_type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeCalculated($query)
    {
        return $query->where('status', 'calculated');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('calculation_date', [$startDate, $endDate]);
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', 'calculated');
    }

    public function scopeReadyForPayment($query)
    {
        return $query->payable();
    }

    /**
     * Komisi yang boleh dibayar: Approved, atau Over Period yang nominalnya sudah
     * dikembalikan admin (> 0) atas kebijakan management.
     */
    public function scopePayable($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'approved')
                ->orWhere(fn ($over) => $over->where('status', 'over_period')->where('final_amount', '>', 0));
        });
    }

    public function scopeWithoutActivePayment($query, ?int $exceptPaymentId = null)
    {
        return $query->whereDoesntHave('commissionPayments', fn ($payments) => $payments
            ->whereIn('status', CommissionPayment::ACTIVE_STATUSES)
            ->when($exceptPaymentId, fn ($p) => $p->where('id', '!=', $exceptPaymentId)));
    }

    public function payoutRequestedBy()
    {
        return $this->belongsTo(User::class, 'payout_requested_by');
    }

    // Accessors
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'calculated' => 'badge-warning',
            'approved' => 'badge-success',
            'paid' => 'badge-info',
            'cancelled' => 'badge-danger',
            'over_period' => 'badge-secondary'
        ];

        return $badges[$this->status] ?? 'badge-secondary';
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'calculated' => 'Calculated',
            'approved' => 'Approved',
            'paid' => 'Paid',
            'cancelled' => 'Cancelled',
            'over_period' => 'Over Period'
        ];

        return $labels[$this->status] ?? ucfirst($this->status);
    }

    public function getFormattedBaseAmountAttribute()
    {
        return 'Rp ' . number_format($this->base_amount, 0, ',', '.');
    }

    public function getFormattedCommissionAmountAttribute()
    {
        return 'Rp ' . number_format($this->commission_amount, 0, ',', '.');
    }

    public function getFormattedBonusAmountAttribute()
    {
        return 'Rp ' . number_format($this->bonus_amount, 0, ',', '.');
    }

    public function getFormattedPenaltyAmountAttribute()
    {
        return 'Rp ' . number_format($this->penalty_amount, 0, ',', '.');
    }

    public function getFormattedFinalAmountAttribute()
    {
        return 'Rp ' . number_format($this->final_amount, 0, ',', '.');
    }

    public function getCalculationTypeLabelAttribute()
    {
        $labels = [
            'automatic' => 'Automatic',
            'new' => 'New',
            'renewal' => 'Renewal',
            'manual' => 'Manual',
            'adjustment' => 'Adjustment'
        ];

        return $labels[$this->calculation_type] ?? ucfirst($this->calculation_type);
    }

    // Methods
    public function calculateFinalAmount()
    {
        $this->final_amount = $this->commission_amount + $this->bonus_amount - $this->penalty_amount;
        return $this->final_amount;
    }

    /**
     * Komisi otomatis (kontrak new/renewal) hanya boleh Approved lewat pembayaran invoice
     * (cash receipt). Komisi manual/adjustment tidak punya invoice, jadi tetap boleh disetujui
     * langsung.
     */
    public function isAwaitingCashReceipt(): bool
    {
        return in_array($this->calculation_type, ['new', 'renewal'], true)
            && $this->status === 'calculated'
            && ! $this->cash_receipt_date;
    }

    public function isPayable(): bool
    {
        return $this->status === 'approved'
            || ($this->status === 'over_period' && (float) $this->final_amount > 0);
    }

    /**
     * Komisi ikut terkunci bila Marketing Target-nya sudah di-LOCK: nilai, penerima, dan
     * periodenya tidak boleh berubah lagi (meeting 17 Nov 2026).
     */
    public function isLocked(): bool
    {
        return (bool) $this->marketingTarget?->is_locked;
    }

    /**
     * Nominal komisi sebelum dinolkan karena Over Period: batas atas edit manual admin.
     */
    public function overPeriodCap(): float
    {
        return (float) ($this->original_amount ?? $this->commission_amount);
    }

    /**
     * Penerimaan kas melewati masa CR: nominal jadi 0, nominal asli disimpan.
     */
    public function overPeriodAttributes(): array
    {
        $original = $this->original_amount ?? $this->final_amount ?? $this->commission_amount;

        return [
            'status' => 'over_period',
            'original_amount' => $original,
            'final_amount' => 0,
            'is_cr_expired' => true,
            'is_commission_void' => true,
        ];
    }

    /**
     * Admin mengembalikan nominal komisi Over Period (kebijakan management).
     * Tidak boleh melebihi nominal komisi aslinya; setiap perubahan dicatat di audit log.
     *
     * @throws \InvalidArgumentException
     */
    public function overrideOverPeriodAmount(float $amount, string $reason, ?int $userId): void
    {
        if ($this->status !== 'over_period') {
            throw new \InvalidArgumentException('Nominal hanya bisa diedit untuk komisi berstatus Over Period.');
        }

        $cap = $this->overPeriodCap();
        if ($amount < 0 || $amount > $cap) {
            throw new \InvalidArgumentException(
                'Nominal harus antara Rp 0 dan Rp '.number_format($cap, 0, ',', '.').' (komisi aslinya).'
            );
        }

        $activePayment = $this->commissionPayments()->whereIn('status', CommissionPayment::ACTIVE_STATUSES)->first();
        if ($activePayment) {
            throw new \InvalidArgumentException(
                "Komisi ini sudah punya pembayaran #{$activePayment->id} ({$activePayment->status}). Batalkan pembayarannya dulu."
            );
        }

        $old = (float) $this->final_amount;
        $actor = $userId ? (User::find($userId)?->name ?? "user #{$userId}") : 'system';

        $this->update([
            'original_amount' => $cap,
            'final_amount' => $amount,
            'calculation_notes' => trim(($this->calculation_notes ?? '')."\n".now()->format('Y-m-d H:i')
                ." Over Period: nominal diubah {$actor} dari Rp ".number_format($old, 0, ',', '.')
                .' menjadi Rp '.number_format($amount, 0, ',', '.')." (maks Rp ".number_format($cap, 0, ',', '.')."). Alasan: {$reason}"),
            'updated_by' => $userId ?? $this->updated_by,
        ]);

        \App\Models\AuditLog::log(
            'commission_over_period_amount_override',
            $this,
            ['final_amount' => $old],
            ['final_amount' => $amount, 'original_amount' => $cap, 'reason' => $reason],
            $userId
        );
    }

    public function approve($approvedBy)
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approvedBy
        ]);
    }

    public function markAsPaid($paymentDate = null)
    {
        $this->update([
            'status' => 'paid',
            'payment_date' => $paymentDate ?? now()
        ]);
    }

    public function cancel($reason = null)
    {
        $this->update([
            'status' => 'cancelled',
            'calculation_notes' => $this->calculation_notes . "\nCancelled: " . $reason
        ]);
    }

    public function getNetCommission()
    {
        return $this->final_amount;
    }

    public function getCommissionBreakdown()
    {
        return [
            'base_amount' => $this->base_amount,
            'commission_rate' => $this->commission_rate,
            'commission_amount' => $this->commission_amount,
            'bonus_amount' => $this->bonus_amount,
            'penalty_amount' => $this->penalty_amount,
            'final_amount' => $this->final_amount
        ];
    }
}
