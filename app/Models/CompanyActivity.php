<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyActivity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'activity_type',
        'title',
        'description',
        'activity_date',
        'duration_minutes',
        'location',
        'is_completed',
        'priority',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'activity_date' => 'datetime',
        'duration_minutes' => 'integer',
        'is_completed' => 'boolean'
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
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
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByActivityType($query, $activityType)
    {
        return $query->where('activity_type', $activityType);
    }

    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_completed', false);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeHighPriority($query)
    {
        return $query->where('priority', 'high');
    }

    public function scopeMediumPriority($query)
    {
        return $query->where('priority', 'medium');
    }

    public function scopeLowPriority($query)
    {
        return $query->where('priority', 'low');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('activity_date', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('activity_date', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('activity_date', now()->month)
                    ->whereYear('activity_date', now()->year);
    }

    public function scopeUpcoming($query, $days = 7)
    {
        return $query->where('activity_date', '>=', now())
                    ->where('activity_date', '<=', now()->addDays($days));
    }

    public function scopeOverdue($query)
    {
        return $query->where('activity_date', '<', now())
                    ->where('is_completed', false);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('activity_date', [$startDate, $endDate]);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('location', 'like', "%{$search}%");
        });
    }

    // Accessors
    public function getActivityTypeTextAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->activity_type));
    }

    public function getIsCompletedTextAttribute()
    {
        return $this->is_completed ? 'Completed' : 'Pending';
    }

    public function getPriorityTextAttribute()
    {
        return ucfirst($this->priority);
    }

    public function getFormattedActivityDateAttribute()
    {
        return $this->activity_date->format('d M Y H:i');
    }

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format('d M Y H:i');
    }

    public function getFormattedUpdatedAtAttribute()
    {
        return $this->updated_at->format('d M Y H:i');
    }

    public function getDurationTextAttribute()
    {
        if (!$this->duration_minutes) {
            return 'N/A';
        }

        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;

        if ($hours > 0) {
            return $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours}h";
        }

        return "{$minutes}m";
    }

    public function getStatusAttribute()
    {
        if ($this->is_completed) {
            return 'Completed';
        }

        if ($this->activity_date->isPast()) {
            return 'Overdue';
        }

        if ($this->activity_date->isToday()) {
            return 'Today';
        }

        if ($this->activity_date->isFuture()) {
            return 'Upcoming';
        }

        return 'Pending';
    }

    public function getStatusColorAttribute()
    {
        switch ($this->status) {
            case 'Completed':
                return 'success';
            case 'Overdue':
                return 'danger';
            case 'Today':
                return 'warning';
            case 'Upcoming':
                return 'info';
            default:
                return 'secondary';
        }
    }

    public function getPriorityColorAttribute()
    {
        switch ($this->priority) {
            case 'high':
                return 'danger';
            case 'medium':
                return 'warning';
            case 'low':
                return 'success';
            default:
                return 'secondary';
        }
    }

    // Business Logic Methods
    public function isCompleted()
    {
        return $this->is_completed;
    }

    public function isPending()
    {
        return !$this->is_completed;
    }

    public function isOverdue()
    {
        return !$this->is_completed && $this->activity_date->isPast();
    }

    public function isToday()
    {
        return $this->activity_date->isToday();
    }

    public function isUpcoming($days = 7)
    {
        return $this->activity_date->isFuture() && 
               $this->activity_date->diffInDays(now()) <= $days;
    }

    public function isHighPriority()
    {
        return $this->priority === 'high';
    }

    public function isMediumPriority()
    {
        return $this->priority === 'medium';
    }

    public function isLowPriority()
    {
        return $this->priority === 'low';
    }

    public function complete()
    {
        $this->is_completed = true;
        $this->save();
    }

    public function uncomplete()
    {
        $this->is_completed = false;
        $this->save();
    }

    public function setPriority($priority)
    {
        $this->priority = $priority;
        $this->save();
    }

    public function getDaysUntilActivity()
    {
        return $this->activity_date->diffInDays(now());
    }

    public function getHoursUntilActivity()
    {
        return $this->activity_date->diffInHours(now());
    }

    public function getMinutesUntilActivity()
    {
        return $this->activity_date->diffInMinutes(now());
    }

    // Static Methods
    public static function getActivityTypes()
    {
        return [
            'meeting' => 'Meeting',
            'call' => 'Phone Call',
            'email' => 'Email',
            'visit' => 'Site Visit',
            'presentation' => 'Presentation',
            'negotiation' => 'Negotiation',
            'follow_up' => 'Follow Up',
            'proposal' => 'Proposal',
            'contract' => 'Contract',
            'other' => 'Other'
        ];
    }

    public static function getPriorities()
    {
        return [
            'high' => 'High',
            'medium' => 'Medium',
            'low' => 'Low'
        ];
    }

    public static function getCountByCompany($companyId)
    {
        return self::where('company_id', $companyId)->count();
    }

    public static function getCompletedCountByCompany($companyId)
    {
        return self::where('company_id', $companyId)
                  ->where('is_completed', true)
                  ->count();
    }

    public static function getPendingCountByCompany($companyId)
    {
        return self::where('company_id', $companyId)
                  ->where('is_completed', false)
                  ->count();
    }

    public static function getOverdueCountByCompany($companyId)
    {
        return self::where('company_id', $companyId)
                  ->where('activity_date', '<', now())
                  ->where('is_completed', false)
                  ->count();
    }

    public static function getTodayCountByCompany($companyId)
    {
        return self::where('company_id', $companyId)
                  ->whereDate('activity_date', today())
                  ->count();
    }

    public static function getUpcomingCountByCompany($companyId, $days = 7)
    {
        return self::where('company_id', $companyId)
                  ->where('activity_date', '>=', now())
                  ->where('activity_date', '<=', now()->addDays($days))
                  ->count();
    }

    public static function getCountByActivityType($companyId, $activityType)
    {
        return self::where('company_id', $companyId)
                  ->where('activity_type', $activityType)
                  ->count();
    }

    public static function getCountByPriority($companyId, $priority)
    {
        return self::where('company_id', $companyId)
                  ->where('priority', $priority)
                  ->count();
    }

    public static function getActivityStatistics()
    {
        return [
            'total' => self::count(),
            'completed' => self::where('is_completed', true)->count(),
            'pending' => self::where('is_completed', false)->count(),
            'overdue' => self::where('activity_date', '<', now())
                        ->where('is_completed', false)
                        ->count(),
            'today' => self::whereDate('activity_date', today())->count(),
            'upcoming' => self::where('activity_date', '>=', now())
                         ->where('activity_date', '<=', now()->addDays(7))
                         ->count()
        ];
    }

    public static function getCompanyActivityStatistics($companyId)
    {
        return [
            'total_activities' => self::getCountByCompany($companyId),
            'completed_activities' => self::getCompletedCountByCompany($companyId),
            'pending_activities' => self::getPendingCountByCompany($companyId),
            'overdue_activities' => self::getOverdueCountByCompany($companyId),
            'today_activities' => self::getTodayCountByCompany($companyId),
            'upcoming_activities' => self::getUpcomingCountByCompany($companyId, 7),
            'by_type' => self::where('company_id', $companyId)
                            ->selectRaw('activity_type, COUNT(*) as count')
                            ->groupBy('activity_type')
                            ->pluck('count', 'activity_type')
                            ->toArray(),
            'by_priority' => self::where('company_id', $companyId)
                               ->selectRaw('priority, COUNT(*) as count')
                               ->groupBy('priority')
                               ->pluck('count', 'priority')
                               ->toArray()
        ];
    }

    public static function getUpcomingActivities($days = 7)
    {
        return self::where('activity_date', '>=', now())
                  ->where('activity_date', '<=', now()->addDays($days))
                  ->where('is_completed', false)
                  ->with(['company', 'createdBy'])
                  ->orderBy('activity_date', 'asc')
                  ->get();
    }

    public static function getOverdueActivities()
    {
        return self::where('activity_date', '<', now())
                  ->where('is_completed', false)
                  ->with(['company', 'createdBy'])
                  ->orderBy('activity_date', 'asc')
                  ->get();
    }

    public static function getTodayActivities()
    {
        return self::whereDate('activity_date', today())
                  ->with(['company', 'createdBy'])
                  ->orderBy('activity_date', 'asc')
                  ->get();
    }

    public static function getRecentActivities($days = 30)
    {
        return self::where('created_at', '>=', now()->subDays($days))
                  ->with(['company', 'createdBy'])
                  ->orderBy('created_at', 'desc')
                  ->get();
    }
}
