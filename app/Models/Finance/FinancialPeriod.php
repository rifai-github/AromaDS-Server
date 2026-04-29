<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'period_name',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeLocked($query)
    {
        return $query->where('status', 'locked');
    }

    public function scopeCurrent($query)
    {
        $today = now()->toDateString();
        return $query->where('start_date', '<=', $today)
                    ->where('end_date', '>=', $today);
    }

    public function scopeByDate($query, $date)
    {
        return $query->where('start_date', '<=', $date)
                    ->where('end_date', '>=', $date);
    }

    // Accessors & Mutators
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'open' => 'bg-green-100 text-green-800',
            'closed' => 'bg-yellow-100 text-yellow-800',
            'locked' => 'bg-red-100 text-red-800',
        ];

        return $badges[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'open' => 'Open',
            'closed' => 'Closed',
            'locked' => 'Locked',
        ];

        return $labels[$this->status] ?? ucfirst($this->status);
    }

    public function getDurationAttribute()
    {
        return $this->start_date->format('d M Y') . ' - ' . $this->end_date->format('d M Y');
    }

    public function getDurationDaysAttribute()
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    public function getIsCurrentAttribute()
    {
        $today = now()->toDateString();
        return $this->start_date <= $today && $this->end_date >= $today;
    }
}
