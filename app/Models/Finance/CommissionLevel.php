<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;
use App\Models\User;

class CommissionLevel extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $table = 'commission_levels';

    protected $fillable = [
        'name',
        'min_percentage',
        'max_percentage',
        'commission_rate',
        'target_type',
        'sort_order',
        'is_active',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'min_percentage' => 'decimal:2',
        'max_percentage' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
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

    public function commissionCalculations()
    {
        return $this->hasMany(CommissionCalculation::class);
    }

    public function achievements()
    {
        return $this->hasMany(Achievement::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where(function($q) use ($type) {
            $q->where('target_type', $type)
              ->orWhere('target_type', 'both');
        });
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    // Methods
    public function matchesPercentage($percentage)
    {
        if ($percentage < $this->min_percentage) {
            return false;
        }
        
        if ($this->max_percentage !== null && $percentage > $this->max_percentage) {
            return false;
        }
        
        return true;
    }

    public static function getLevelByPercentage($percentage, $targetType = 'new')
    {
        return self::active()
            ->byType($targetType)
            ->ordered()
            ->get()
            ->first(function($level) use ($percentage) {
                return $level->matchesPercentage($percentage);
            });
    }
}
