<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;
use App\Models\User;

class RenewalContractAssignment extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $table = 'renewal_contract_assignments';

    protected $fillable = [
        'achievement_period_id',
        'user_id',
        'contract_number_from',
        'contract_number_to',
        'target_amount',
        'is_locked',
        'lock_date',
        'locked_by',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'is_locked' => 'boolean',
        'lock_date' => 'date',
    ];

    // Relationships
    public function achievementPeriod()
    {
        return $this->belongsTo(AchievementPeriod::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
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

    // Scopes
    public function scopeByPeriod($query, $periodId)
    {
        return $query->where('achievement_period_id', $periodId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeLocked($query)
    {
        return $query->where('is_locked', true);
    }

    public function scopeUnlocked($query)
    {
        return $query->where('is_locked', false);
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

    public function isContractInRange($contractNumber)
    {
        if ($this->contract_number_from && $contractNumber < $this->contract_number_from) {
            return false;
        }
        
        if ($this->contract_number_to && $contractNumber > $this->contract_number_to) {
            return false;
        }
        
        return true;
    }
}
