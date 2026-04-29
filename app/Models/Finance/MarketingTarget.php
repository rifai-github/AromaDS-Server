<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;
use App\Models\User;

class MarketingTarget extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $table = 'marketing_targets';

    protected $fillable = [
        'user_id',
        'achievement_period_id',
        'target_type',
        'target_amount',
        'achieved_amount',
        'is_locked',
        'lock_date',
        'locked_by',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'achieved_amount' => 'decimal:2',
        'is_locked' => 'boolean',
        'lock_date' => 'date',
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

    public function lockedBy()
    {
        return $this->belongsTo(User::class, 'locked_by');
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
        return $query->where('target_type', $type);
    }

    public function scopeNewContracts($query)
    {
        return $query->where('target_type', 'new');
    }

    public function scopeRenewalContracts($query)
    {
        return $query->where('target_type', 'renewal');
    }

    public function scopeLocked($query)
    {
        return $query->where('is_locked', true);
    }

    public function scopeUnlocked($query)
    {
        return $query->where('is_locked', false);
    }

    // Accessors
    public function getAchievementPercentageAttribute()
    {
        if ($this->target_amount <= 0) {
            return 0;
        }
        return min(100, ($this->achieved_amount / $this->target_amount) * 100);
    }

    public function getFormattedTargetAmountAttribute()
    {
        return 'Rp ' . number_format($this->target_amount, 0, ',', '.');
    }

    public function getFormattedAchievedAmountAttribute()
    {
        return 'Rp ' . number_format($this->achieved_amount, 0, ',', '.');
    }

    // Methods
    public function lock($lockedBy)
    {
        $this->update([
            'is_locked' => true,
            'lock_date' => now(),
            'locked_by' => $lockedBy
        ]);
    }

    public function unlock()
    {
        $this->update([
            'is_locked' => false,
            'lock_date' => null,
            'locked_by' => null
        ]);
    }

    public function updateAchievedAmount($amount)
    {
        $this->update(['achieved_amount' => $amount]);
    }
}
