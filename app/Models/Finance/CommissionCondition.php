<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Finance\SalesCommission;
use App\Models\Finance\Invoice;

class CommissionCondition extends Model
{
    use HasFactory;

    protected $fillable = [
        'commission_id',
        'invoice_id',
        'payment_date',
        'days_overdue',
        'is_valid',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'is_valid' => 'boolean',
    ];

    // Relationships
    public function commission()
    {
        return $this->belongsTo(SalesCommission::class, 'commission_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    // Scopes
    public function scopeValid($query)
    {
        return $query->where('is_valid', true);
    }

    public function scopeInvalid($query)
    {
        return $query->where('is_valid', false);
    }

    public function scopeOverdue($query)
    {
        return $query->where('days_overdue', '>', 0);
    }

    // Accessors & Mutators
    public function getOverdueStatusAttribute()
    {
        if ($this->days_overdue <= 0) {
            return 'On Time';
        } elseif ($this->days_overdue <= 30) {
            return 'Overdue (1-30 days)';
        } elseif ($this->days_overdue <= 60) {
            return 'Overdue (31-60 days)';
        } else {
            return 'Overdue (60+ days)';
        }
    }

    public function getOverdueBadgeAttribute()
    {
        if ($this->days_overdue <= 0) {
            return 'bg-green-100 text-green-800';
        } elseif ($this->days_overdue <= 30) {
            return 'bg-yellow-100 text-yellow-800';
        } elseif ($this->days_overdue <= 60) {
            return 'bg-orange-100 text-orange-800';
        } else {
            return 'bg-red-100 text-red-800';
        }
    }
}
