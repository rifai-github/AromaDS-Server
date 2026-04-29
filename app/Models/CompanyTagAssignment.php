<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyTagAssignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'tag_id',
        'assigned_by',
        'assigned_at'
    ];

    protected $casts = [
        'assigned_at' => 'datetime'
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function tag()
    {
        return $this->belongsTo(CompanyTag::class, 'tag_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    // Scopes
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByTag($query, $tagId)
    {
        return $query->where('tag_id', $tagId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('assigned_by', $userId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('assigned_at', '>=', now()->subDays($days));
    }

    public function scopeToday($query)
    {
        return $query->whereDate('assigned_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('assigned_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('assigned_at', now()->month)
                    ->whereYear('assigned_at', now()->year);
    }

    // Accessors
    public function getFormattedAssignedAtAttribute()
    {
        return $this->assigned_at->format('d M Y H:i');
    }

    public function getAssignedAtHumanAttribute()
    {
        return $this->assigned_at->diffForHumans();
    }

    public function getAssignedAtDateAttribute()
    {
        return $this->assigned_at->format('d M Y');
    }

    public function getAssignedAtTimeAttribute()
    {
        return $this->assigned_at->format('H:i');
    }

    // Business Logic Methods
    public function isRecent($days = 7)
    {
        return $this->assigned_at->isAfter(now()->subDays($days));
    }

    public function isToday()
    {
        return $this->assigned_at->isToday();
    }

    public function isThisWeek()
    {
        return $this->assigned_at->isCurrentWeek();
    }

    public function isThisMonth()
    {
        return $this->assigned_at->isCurrentMonth();
    }

    public function getAgeInDays()
    {
        return $this->assigned_at->diffInDays(now());
    }

    public function getAgeInHours()
    {
        return $this->assigned_at->diffInHours(now());
    }

    public function getAgeInMinutes()
    {
        return $this->assigned_at->diffInMinutes(now());
    }

    // Static Methods
    public static function getCountByCompany($companyId)
    {
        return self::where('company_id', $companyId)->count();
    }

    public static function getCountByTag($tagId)
    {
        return self::where('tag_id', $tagId)->count();
    }

    public static function getCountByUser($userId)
    {
        return self::where('assigned_by', $userId)->count();
    }

    public static function getRecentCount($days = 7)
    {
        return self::where('assigned_at', '>=', now()->subDays($days))->count();
    }

    public static function getTodayCount()
    {
        return self::whereDate('assigned_at', today())->count();
    }

    public static function getThisWeekCount()
    {
        return self::whereBetween('assigned_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ])->count();
    }

    public static function getThisMonthCount()
    {
        return self::whereMonth('assigned_at', now()->month)
                  ->whereYear('assigned_at', now()->year)
                  ->count();
    }

    public static function getMostAssignedTags($limit = 10)
    {
        return self::selectRaw('tag_id, COUNT(*) as assignment_count')
                  ->with('tag')
                  ->groupBy('tag_id')
                  ->orderBy('assignment_count', 'desc')
                  ->limit($limit)
                  ->get();
    }

    public static function getMostActiveUsers($limit = 10)
    {
        return self::selectRaw('assigned_by, COUNT(*) as assignment_count')
                  ->with('assignedBy')
                  ->groupBy('assigned_by')
                  ->orderBy('assignment_count', 'desc')
                  ->limit($limit)
                  ->get();
    }

    public static function getAssignmentsByDateRange($startDate, $endDate)
    {
        return self::whereBetween('assigned_at', [$startDate, $endDate])
                  ->with(['company', 'tag', 'assignedBy'])
                  ->orderBy('assigned_at', 'desc')
                  ->get();
    }

    public static function getDailyAssignments($days = 30)
    {
        return self::selectRaw('DATE(assigned_at) as date, COUNT(*) as count')
                  ->where('assigned_at', '>=', now()->subDays($days))
                  ->groupBy('date')
                  ->orderBy('date', 'desc')
                  ->get();
    }

    public static function getWeeklyAssignments($weeks = 12)
    {
        return self::selectRaw('YEARWEEK(assigned_at) as week, COUNT(*) as count')
                  ->where('assigned_at', '>=', now()->subWeeks($weeks))
                  ->groupBy('week')
                  ->orderBy('week', 'desc')
                  ->get();
    }

    public static function getMonthlyAssignments($months = 12)
    {
        return self::selectRaw('YEAR(assigned_at) as year, MONTH(assigned_at) as month, COUNT(*) as count')
                  ->where('assigned_at', '>=', now()->subMonths($months))
                  ->groupBy('year', 'month')
                  ->orderBy('year', 'desc')
                  ->orderBy('month', 'desc')
                  ->get();
    }

    public static function getAssignmentStatistics()
    {
        return [
            'total' => self::count(),
            'today' => self::getTodayCount(),
            'this_week' => self::getThisWeekCount(),
            'this_month' => self::getThisMonthCount(),
            'recent' => self::getRecentCount(7),
            'most_assigned_tag' => self::getMostAssignedTags(1)->first(),
            'most_active_user' => self::getMostActiveUsers(1)->first()
        ];
    }

    public static function getCompanyTagStatistics($companyId)
    {
        return [
            'total_tags' => self::getCountByCompany($companyId),
            'recent_tags' => self::where('company_id', $companyId)
                              ->where('assigned_at', '>=', now()->subDays(7))
                              ->count(),
            'tags_this_month' => self::where('company_id', $companyId)
                                   ->whereMonth('assigned_at', now()->month)
                                   ->whereYear('assigned_at', now()->year)
                                   ->count()
        ];
    }

    public static function getTagUsageStatistics($tagId)
    {
        return [
            'total_assignments' => self::getCountByTag($tagId),
            'recent_assignments' => self::where('tag_id', $tagId)
                                     ->where('assigned_at', '>=', now()->subDays(7))
                                     ->count(),
            'assignments_this_month' => self::where('tag_id', $tagId)
                                          ->whereMonth('assigned_at', now()->month)
                                          ->whereYear('assigned_at', now()->year)
                                          ->count()
        ];
    }
}
