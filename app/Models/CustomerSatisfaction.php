<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerSatisfaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'satisfaction_score',
        'feedback',
        'survey_date'
    ];

    protected $casts = [
        'survey_date' => 'date'
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // Scopes
    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByScore($query, $score)
    {
        return $query->where('satisfaction_score', $score);
    }

    public function scopeHighSatisfaction($query, $minScore = 8)
    {
        return $query->where('satisfaction_score', '>=', $minScore);
    }

    public function scopeLowSatisfaction($query, $maxScore = 5)
    {
        return $query->where('satisfaction_score', '<=', $maxScore);
    }

    public function scopeRecent($query, $days = 90)
    {
        return $query->where('survey_date', '>=', now()->subDays($days));
    }

    public function scopeThisYear($query)
    {
        return $query->whereYear('survey_date', now()->year);
    }

    // Accessors
    public function getSatisfactionLevelAttribute()
    {
        if ($this->satisfaction_score >= 9) {
            return 'Very Satisfied';
        } elseif ($this->satisfaction_score >= 7) {
            return 'Satisfied';
        } elseif ($this->satisfaction_score >= 5) {
            return 'Neutral';
        } elseif ($this->satisfaction_score >= 3) {
            return 'Dissatisfied';
        } else {
            return 'Very Dissatisfied';
        }
    }

    public function getSatisfactionColorAttribute()
    {
        if ($this->satisfaction_score >= 8) {
            return 'green';
        } elseif ($this->satisfaction_score >= 6) {
            return 'yellow';
        } else {
            return 'red';
        }
    }

    public function getShortFeedbackAttribute()
    {
        return strlen($this->feedback) > 100 
            ? substr($this->feedback, 0, 100) . '...' 
            : $this->feedback;
    }

    // Static Methods
    public static function getAverageSatisfaction($customerId = null)
    {
        $query = static::query();
        
        if ($customerId) {
            $query->where('customer_id', $customerId);
        }
        
        return $query->avg('satisfaction_score');
    }

    public static function getSatisfactionDistribution()
    {
        return static::selectRaw('
            CASE 
                WHEN satisfaction_score >= 9 THEN "Very Satisfied"
                WHEN satisfaction_score >= 7 THEN "Satisfied"
                WHEN satisfaction_score >= 5 THEN "Neutral"
                WHEN satisfaction_score >= 3 THEN "Dissatisfied"
                ELSE "Very Dissatisfied"
            END as level,
            COUNT(*) as count
        ')
        ->groupBy('level')
        ->pluck('count', 'level');
    }
}
