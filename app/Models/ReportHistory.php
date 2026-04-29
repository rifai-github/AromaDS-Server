<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'report_id',
        'execution_date',
        'parameters'
    ];

    protected $casts = [
        'parameters' => 'array',
        'execution_date' => 'datetime'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForReport($query, $reportId)
    {
        return $query->where('report_id', $reportId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('execution_date', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('execution_date', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('execution_date', now()->month)
                    ->whereYear('execution_date', now()->year);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('execution_date', 'desc');
    }
}
