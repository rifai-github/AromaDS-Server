<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportFavorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'report_id'
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
}
