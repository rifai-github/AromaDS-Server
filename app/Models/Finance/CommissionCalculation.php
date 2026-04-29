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
        'status',
        'calculation_date',
        'payment_date',
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
        'calculation_date' => 'date',
        'payment_date' => 'date',
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
        return $query->where('status', 'approved');
    }

    // Accessors
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'calculated' => 'badge-warning',
            'approved' => 'badge-success',
            'paid' => 'badge-info',
            'cancelled' => 'badge-danger'
        ];

        return $badges[$this->status] ?? 'badge-secondary';
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'calculated' => 'Calculated',
            'approved' => 'Approved',
            'paid' => 'Paid',
            'cancelled' => 'Cancelled'
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
