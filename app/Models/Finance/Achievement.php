<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;
use App\Models\User;
use App\Models\Contract;
use App\Http\Traits\AutoFilterable;

class Achievement extends Model
{
    use SoftDeletes, AutoFilterable;

    protected $fillable = [
        'user_id',
        'achievement_period_id',
        'contract_id',
        'achievement_type',
        'target_amount',
        'achieved_amount',
        'commission_rate',
        'commission_level_id',
        'commission_amount',
        'status',
        'achievement_date',
        'cut_off_start_date',
        'cut_off_end_date',
        'cut_off_tolerance_days',
        'is_installed',
        'installed_date',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'achieved_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'achievement_date' => 'date',
        'cut_off_start_date' => 'integer',
        'cut_off_end_date' => 'integer',
        'cut_off_tolerance_days' => 'integer',
        'is_installed' => 'boolean',
        'installed_date' => 'date'
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

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function commissionCalculations()
    {
        return $this->hasMany(CommissionCalculation::class);
    }

    public function commissionLevel()
    {
        return $this->belongsTo(CommissionLevel::class);
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
        return $query->where('achievement_type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeAchieved($query)
    {
        return $query->where('status', 'achieved');
    }

    public function scopeExceeded($query)
    {
        return $query->where('status', 'exceeded');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('achievement_date', [$startDate, $endDate]);
    }

    // Accessors
    public function getAchievementPercentageAttribute()
    {
        if ($this->target_amount <= 0) {
            return 0;
        }
        return min(100, ($this->achieved_amount / $this->target_amount) * 100);
    }

    public function getIsAchievedAttribute()
    {
        return $this->achieved_amount >= $this->target_amount;
    }

    public function getIsExceededAttribute()
    {
        return $this->achieved_amount > $this->target_amount;
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'achieved' => 'bg-green-100 text-green-800',
            'exceeded' => 'bg-blue-100 text-blue-800',
            'failed' => 'bg-red-100 text-red-800'
        ];

        return $badges[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'Pending',
            'achieved' => 'Achieved',
            'exceeded' => 'Exceeded',
            'failed' => 'Failed'
        ];

        return $labels[$this->status] ?? ucfirst($this->status);
    }

    public function getFormattedTargetAmountAttribute()
    {
        return 'Rp ' . number_format($this->target_amount, 0, ',', '.');
    }

    public function getFormattedAchievedAmountAttribute()
    {
        return 'Rp ' . number_format($this->achieved_amount, 0, ',', '.');
    }

    public function getFormattedCommissionAmountAttribute()
    {
        return 'Rp ' . number_format($this->commission_amount, 0, ',', '.');
    }

    // Methods
    public function calculateCommission()
    {
        $this->commission_amount = $this->achieved_amount * ($this->commission_rate / 100);
        return $this->commission_amount;
    }

    public function updateStatus()
    {
        if ($this->achieved_amount >= $this->target_amount) {
            if ($this->achieved_amount > $this->target_amount) {
                $this->status = 'exceeded';
            } else {
                $this->status = 'achieved';
            }
        } else {
            $this->status = 'failed';
        }
        
        $this->save();
        return $this->status;
    }

    public function getPerformanceLevel()
    {
        $percentage = $this->achievement_percentage;
        
        if ($percentage >= 150) {
            return 'Excellent';
        } elseif ($percentage >= 120) {
            return 'Very Good';
        } elseif ($percentage >= 100) {
            return 'Good';
        } elseif ($percentage >= 80) {
            return 'Fair';
        } else {
            return 'Poor';
        }
    }
}
