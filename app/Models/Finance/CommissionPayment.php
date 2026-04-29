<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;
use App\Models\User;

class CommissionPayment extends Model
{
    use SoftDeletes, AutoFilterable;

    protected $fillable = [
        'commission_calculation_id',
        'user_id',
        'amount',
        'payment_method',
        'payment_reference',
        'payment_date',
        'status',
        'payment_notes',
        'bank_account',
        'bank_name',
        'processed_by',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date'
    ];

    // Relationships
    public function commissionCalculation()
    {
        return $this->belongsTo(CommissionCalculation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
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
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    public function scopeByPaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    // Accessors
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'badge-info',
            'processing' => 'badge-warning',
            'completed' => 'badge-success',
            'failed' => 'badge-danger',
            'cancelled' => 'badge-secondary'
        ];

        return $badges[$this->status] ?? 'badge-secondary';
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled'
        ];

        return $labels[$this->status] ?? ucfirst($this->status);
    }

    public function getFormattedAmountAttribute()
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    public function getPaymentMethodLabelAttribute()
    {
        $labels = [
            'bank_transfer' => 'Bank Transfer',
            'cash' => 'Cash',
            'check' => 'Check',
            'other' => 'Other'
        ];

        return $labels[$this->payment_method] ?? ucfirst($this->payment_method);
    }

    // Methods
    public function markAsProcessing($processedBy = null)
    {
        $this->update([
            'status' => 'processing',
            'processed_by' => $processedBy
        ]);
    }

    public function markAsCompleted($processedBy = null)
    {
        $this->update([
            'status' => 'completed',
            'processed_by' => $processedBy
        ]);
    }

    public function markAsFailed($reason = null)
    {
        $this->update([
            'status' => 'failed',
            'payment_notes' => $this->payment_notes . "\nFailed: " . $reason
        ]);
    }

    public function cancel($reason = null)
    {
        $this->update([
            'status' => 'cancelled',
            'payment_notes' => $this->payment_notes . "\nCancelled: " . $reason
        ]);
    }

    public function getPaymentDetails()
    {
        return [
            'amount' => $this->amount,
            'payment_method' => $this->payment_method_label,
            'payment_reference' => $this->payment_reference,
            'payment_date' => $this->payment_date->format('d M Y'),
            'status' => $this->status_label,
            'bank_account' => $this->bank_account,
            'bank_name' => $this->bank_name
        ];
    }
}
