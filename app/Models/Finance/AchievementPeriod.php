<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;
use App\Models\User;
use App\Models\Contract;
use App\Http\Traits\AutoFilterable;

class AchievementPeriod extends Model
{
    use SoftDeletes, AutoFilterable;

    protected $fillable = [
        'period_name',
        'start_date',
        'end_date',
        'status',
        'description',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date'
    ];

    // Relationships
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

    public function commissionCalculations()
    {
        return $this->hasMany(CommissionCalculation::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate]);
    }

    public function scopeCurrent($query)
    {
        $today = now()->toDateString();
        return $query->where('start_date', '<=', $today)
                    ->where('end_date', '>=', $today)
                    ->where('status', 'active');
    }

    // Accessors
    public function getDurationAttribute()
    {
        return $this->start_date->diffInDays($this->end_date);
    }

    public function getIsActiveAttribute()
    {
        $today = now()->toDateString();
        return $this->status === 'active' && 
               $this->start_date <= $today && 
               $this->end_date >= $today;
    }

    public function getIsExpiredAttribute()
    {
        return $this->end_date < now()->toDateString();
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'active' => 'badge-success',
            'inactive' => 'badge-secondary',
            'completed' => 'badge-info'
        ];

        return $badges[$this->status] ?? 'badge-secondary';
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'completed' => 'Completed'
        ];

        return $labels[$this->status] ?? ucfirst($this->status);
    }

    // Methods
    public function calculateTotalAchievements()
    {
        return $this->achievements()->sum('achieved_amount');
    }

    public function calculateTotalCommissions()
    {
        return $this->commissionCalculations()->sum('final_amount');
    }

    public function getAchievementStats()
    {
        return [
            'total_achievements' => $this->achievements()->count(),
            'achieved_count' => $this->achievements()->where('status', 'achieved')->count(),
            'exceeded_count' => $this->achievements()->where('status', 'exceeded')->count(),
            'failed_count' => $this->achievements()->where('status', 'failed')->count(),
            'total_amount' => $this->calculateTotalAchievements(),
            'total_commissions' => $this->calculateTotalCommissions()
        ];
    }
}
