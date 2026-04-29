<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class FinanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'transaction_type',
        'amount',
        'balance',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeCommission($query)
    {
        return $query->where('transaction_type', 'commission');
    }

    public function scopeWithdrawal($query)
    {
        return $query->where('transaction_type', 'withdrawal');
    }

    public function scopePayment($query)
    {
        return $query->where('transaction_type', 'payment');
    }

    public function scopeAdjustment($query)
    {
        return $query->where('transaction_type', 'adjustment');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Accessors & Mutators
    public function getFormattedAmountAttribute()
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    public function getFormattedBalanceAttribute()
    {
        return 'Rp ' . number_format($this->balance, 0, ',', '.');
    }

    public function getTransactionTypeBadgeAttribute()
    {
        $badges = [
            'commission' => 'bg-green-100 text-green-800',
            'withdrawal' => 'bg-blue-100 text-blue-800',
            'payment' => 'bg-purple-100 text-purple-800',
            'adjustment' => 'bg-yellow-100 text-yellow-800',
        ];

        return $badges[$this->transaction_type] ?? 'bg-gray-100 text-gray-800';
    }

    public function getTransactionTypeLabelAttribute()
    {
        $labels = [
            'commission' => 'Commission',
            'withdrawal' => 'Withdrawal',
            'payment' => 'Payment',
            'adjustment' => 'Adjustment',
        ];

        return $labels[$this->transaction_type] ?? ucfirst($this->transaction_type);
    }
}
