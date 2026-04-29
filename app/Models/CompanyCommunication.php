<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyCommunication extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'communication_type',
        'subject',
        'content',
        'communication_date',
        'direction',
        'status',
        'priority',
        'related_activity_id',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'communication_date' => 'datetime'
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

    public function relatedActivity()
    {
        return $this->belongsTo(CompanyActivity::class, 'related_activity_id');
    }

    // Scopes
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByCommunicationType($query, $communicationType)
    {
        return $query->where('communication_type', $communicationType);
    }

    public function scopeByDirection($query, $direction)
    {
        return $query->where('direction', $direction);
    }

    public function scopeInbound($query)
    {
        return $query->where('direction', 'inbound');
    }

    public function scopeOutbound($query)
    {
        return $query->where('direction', 'outbound');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
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
        return $query->whereDate('communication_date', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('communication_date', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('communication_date', now()->month)
                    ->whereYear('communication_date', now()->year);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('communication_date', [$startDate, $endDate]);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('subject', 'like', "%{$search}%")
              ->orWhere('content', 'like', "%{$search}%");
        });
    }

    public function scopeUnread($query)
    {
        return $query->where('status', 'unread');
    }

    public function scopeRead($query)
    {
        return $query->where('status', 'read');
    }

    public function scopeReplied($query)
    {
        return $query->where('status', 'replied');
    }

    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    // Accessors
    public function getCommunicationTypeTextAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->communication_type));
    }

    public function getDirectionTextAttribute()
    {
        return ucfirst($this->direction);
    }

    public function getStatusTextAttribute()
    {
        return ucfirst($this->status);
    }

    public function getPriorityTextAttribute()
    {
        return ucfirst($this->priority);
    }

    public function getFormattedCommunicationDateAttribute()
    {
        return $this->communication_date->format('d M Y H:i');
    }

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format('d M Y H:i');
    }

    public function getFormattedUpdatedAtAttribute()
    {
        return $this->updated_at->format('d M Y H:i');
    }

    public function getShortContentAttribute()
    {
        return strlen($this->content) > 100 
            ? substr($this->content, 0, 100) . '...' 
            : $this->content;
    }

    public function getStatusColorAttribute()
    {
        switch ($this->status) {
            case 'unread':
                return 'danger';
            case 'read':
                return 'info';
            case 'replied':
                return 'success';
            case 'archived':
                return 'secondary';
            default:
                return 'primary';
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

    public function getDirectionColorAttribute()
    {
        switch ($this->direction) {
            case 'inbound':
                return 'info';
            case 'outbound':
                return 'primary';
            default:
                return 'secondary';
        }
    }

    // Business Logic Methods
    public function isInbound()
    {
        return $this->direction === 'inbound';
    }

    public function isOutbound()
    {
        return $this->direction === 'outbound';
    }

    public function isUnread()
    {
        return $this->status === 'unread';
    }

    public function isRead()
    {
        return $this->status === 'read';
    }

    public function isReplied()
    {
        return $this->status === 'replied';
    }

    public function isArchived()
    {
        return $this->status === 'archived';
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

    public function markAsRead()
    {
        $this->status = 'read';
        $this->save();
    }

    public function markAsReplied()
    {
        $this->status = 'replied';
        $this->save();
    }

    public function archive()
    {
        $this->status = 'archived';
        $this->save();
    }

    public function unarchive()
    {
        $this->status = 'read';
        $this->save();
    }

    public function setPriority($priority)
    {
        $this->priority = $priority;
        $this->save();
    }

    public function getDaysSinceCommunication()
    {
        return $this->communication_date->diffInDays(now());
    }

    public function getHoursSinceCommunication()
    {
        return $this->communication_date->diffInHours(now());
    }

    public function getMinutesSinceCommunication()
    {
        return $this->communication_date->diffInMinutes(now());
    }

    // Static Methods
    public static function getCommunicationTypes()
    {
        return [
            'email' => 'Email',
            'phone' => 'Phone Call',
            'meeting' => 'Meeting',
            'letter' => 'Letter',
            'fax' => 'Fax',
            'sms' => 'SMS',
            'chat' => 'Chat',
            'video_call' => 'Video Call',
            'other' => 'Other'
        ];
    }

    public static function getDirections()
    {
        return [
            'inbound' => 'Inbound',
            'outbound' => 'Outbound'
        ];
    }

    public static function getStatuses()
    {
        return [
            'unread' => 'Unread',
            'read' => 'Read',
            'replied' => 'Replied',
            'archived' => 'Archived'
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

    public static function getCountByCommunicationType($companyId, $communicationType)
    {
        return self::where('company_id', $companyId)
                  ->where('communication_type', $communicationType)
                  ->count();
    }

    public static function getCountByDirection($companyId, $direction)
    {
        return self::where('company_id', $companyId)
                  ->where('direction', $direction)
                  ->count();
    }

    public static function getCountByStatus($companyId, $status)
    {
        return self::where('company_id', $companyId)
                  ->where('status', $status)
                  ->count();
    }

    public static function getCountByPriority($companyId, $priority)
    {
        return self::where('company_id', $companyId)
                  ->where('priority', $priority)
                  ->count();
    }

    public static function getUnreadCountByCompany($companyId)
    {
        return self::where('company_id', $companyId)
                  ->where('status', 'unread')
                  ->count();
    }

    public static function getTodayCountByCompany($companyId)
    {
        return self::where('company_id', $companyId)
                  ->whereDate('communication_date', today())
                  ->count();
    }

    public static function getThisWeekCountByCompany($companyId)
    {
        return self::where('company_id', $companyId)
                  ->whereBetween('communication_date', [
                      now()->startOfWeek(),
                      now()->endOfWeek()
                  ])
                  ->count();
    }

    public static function getThisMonthCountByCompany($companyId)
    {
        return self::where('company_id', $companyId)
                  ->whereMonth('communication_date', now()->month)
                  ->whereYear('communication_date', now()->year)
                  ->count();
    }

    public static function getCommunicationStatistics()
    {
        return [
            'total' => self::count(),
            'inbound' => self::where('direction', 'inbound')->count(),
            'outbound' => self::where('direction', 'outbound')->count(),
            'unread' => self::where('status', 'unread')->count(),
            'read' => self::where('status', 'read')->count(),
            'replied' => self::where('status', 'replied')->count(),
            'archived' => self::where('status', 'archived')->count(),
            'high_priority' => self::where('priority', 'high')->count(),
            'medium_priority' => self::where('priority', 'medium')->count(),
            'low_priority' => self::where('priority', 'low')->count()
        ];
    }

    public static function getCompanyCommunicationStatistics($companyId)
    {
        return [
            'total_communications' => self::getCountByCompany($companyId),
            'inbound_communications' => self::getCountByDirection($companyId, 'inbound'),
            'outbound_communications' => self::getCountByDirection($companyId, 'outbound'),
            'unread_communications' => self::getUnreadCountByCompany($companyId),
            'today_communications' => self::getTodayCountByCompany($companyId),
            'this_week_communications' => self::getThisWeekCountByCompany($companyId),
            'this_month_communications' => self::getThisMonthCountByCompany($companyId),
            'by_type' => self::where('company_id', $companyId)
                        ->selectRaw('communication_type, COUNT(*) as count')
                        ->groupBy('communication_type')
                        ->pluck('count', 'communication_type')
                        ->toArray(),
            'by_status' => self::where('company_id', $companyId)
                             ->selectRaw('status, COUNT(*) as count')
                             ->groupBy('status')
                             ->pluck('count', 'status')
                             ->toArray(),
            'by_priority' => self::where('company_id', $companyId)
                               ->selectRaw('priority, COUNT(*) as count')
                               ->groupBy('priority')
                               ->pluck('count', 'priority')
                               ->toArray()
        ];
    }

    public static function getUnreadCommunications()
    {
        return self::where('status', 'unread')
                  ->with(['company', 'createdBy'])
                  ->orderBy('communication_date', 'desc')
                  ->get();
    }

    public static function getRecentCommunications($days = 30)
    {
        return self::where('created_at', '>=', now()->subDays($days))
                  ->with(['company', 'createdBy'])
                  ->orderBy('created_at', 'desc')
                  ->get();
    }

    public static function getTodayCommunications()
    {
        return self::whereDate('communication_date', today())
                  ->with(['company', 'createdBy'])
                  ->orderBy('communication_date', 'desc')
                  ->get();
    }

    public static function getHighPriorityCommunications()
    {
        return self::where('priority', 'high')
                  ->where('status', '!=', 'archived')
                  ->with(['company', 'createdBy'])
                  ->orderBy('communication_date', 'desc')
                  ->get();
    }

    public static function getOverdueCommunications($days = 7)
    {
        return self::where('status', 'unread')
                  ->where('communication_date', '<', now()->subDays($days))
                  ->with(['company', 'createdBy'])
                  ->orderBy('communication_date', 'asc')
                  ->get();
    }
}
