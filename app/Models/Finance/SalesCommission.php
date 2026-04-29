<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;
use App\Models\User;
use App\Models\Contract;

class SalesCommission extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $fillable = [
        'user_id',
        'contract_id',
        'commission_type',
        'amount',
        'status',
        'calculated_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'calculated_date' => 'date',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function commissionConditions()
    {
        return $this->hasMany(CommissionCondition::class, 'commission_id');
    }

    // Scopes
    public function scopeValid($query)
    {
        return $query->where('status', 'valid');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeVoid($query)
    {
        return $query->where('status', 'void');
    }

    // Accessors & Mutators
    public function getFormattedAmountAttribute()
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'valid' => 'bg-green-100 text-green-800',
            'void' => 'bg-red-100 text-red-800',
            'paid' => 'bg-blue-100 text-blue-800',
        ];

        return $badges[$this->status] ?? 'bg-gray-100 text-gray-800';
    }
}
