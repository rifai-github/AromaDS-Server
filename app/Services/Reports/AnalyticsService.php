<?php

namespace App\Services\Reports;

use App\Models\AnalyticsEvent;
use App\Models\AnalyticsMetric;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AnalyticsService
{
    /**
     * Track an analytics event
     */
    public function trackEvent(string $eventName, array $properties = [], int $userId = null): AnalyticsEvent
    {
        return AnalyticsEvent::create([
            'event_name' => $eventName,
            'user_id' => $userId ?? Auth::id(),
            'properties' => json_encode($properties),
            'session_id' => session()->getId(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now()
        ]);
    }

    /**
     * Track page view
     */
    public function trackPageView(string $page, array $properties = []): AnalyticsEvent
    {
        return $this->trackEvent('page_view', array_merge([
            'page' => $page,
            'url' => request()->fullUrl(),
            'referrer' => request()->header('referer')
        ], $properties));
    }

    /**
     * Track user action
     */
    public function trackUserAction(string $action, array $properties = []): AnalyticsEvent
    {
        return $this->trackEvent('user_action', array_merge([
            'action' => $action,
            'timestamp' => now()->toISOString()
        ], $properties));
    }

    /**
     * Track report generation
     */
    public function trackReportGeneration(int $reportId, string $reportType, array $properties = []): AnalyticsEvent
    {
        return $this->trackEvent('report_generated', array_merge([
            'report_id' => $reportId,
            'report_type' => $reportType,
            'generation_time' => $properties['generation_time'] ?? null,
            'file_size' => $properties['file_size'] ?? null
        ], $properties));
    }

    /**
     * Track dashboard view
     */
    public function trackDashboardView(int $dashboardId, array $properties = []): AnalyticsEvent
    {
        return $this->trackEvent('dashboard_viewed', array_merge([
            'dashboard_id' => $dashboardId,
            'view_duration' => $properties['view_duration'] ?? null
        ], $properties));
    }

    /**
     * Get analytics metrics
     */
    public function getMetrics(string $metricName, Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $events = AnalyticsEvent::where('event_name', $metricName)
                              ->whereBetween('created_at', [$startDate, $endDate])
                              ->get();

        return [
            'metric_name' => $metricName,
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString()
            ],
            'total_events' => $events->count(),
            'unique_users' => $events->pluck('user_id')->unique()->count(),
            'daily_breakdown' => $this->getDailyBreakdown($events),
            'top_pages' => $this->getTopPages($events),
            'user_activity' => $this->getUserActivity($events)
        ];
    }

    /**
     * Get daily breakdown of events
     */
    private function getDailyBreakdown($events): array
    {
        $breakdown = [];
        
        foreach ($events as $event) {
            $date = $event->created_at->toDateString();
            if (!isset($breakdown[$date])) {
                $breakdown[$date] = 0;
            }
            $breakdown[$date]++;
        }

        return $breakdown;
    }

    /**
     * Get top pages from events
     */
    private function getTopPages($events): array
    {
        $pages = [];
        
        foreach ($events as $event) {
            $properties = json_decode($event->properties, true);
            if (isset($properties['page'])) {
                $page = $properties['page'];
                if (!isset($pages[$page])) {
                    $pages[$page] = 0;
                }
                $pages[$page]++;
            }
        }

        arsort($pages);
        return array_slice($pages, 0, 10, true);
    }

    /**
     * Get user activity summary
     */
    private function getUserActivity($events): array
    {
        $userActivity = [];
        
        foreach ($events as $event) {
            $userId = $event->user_id;
            if (!isset($userActivity[$userId])) {
                $userActivity[$userId] = [
                    'user_id' => $userId,
                    'event_count' => 0,
                    'first_activity' => $event->created_at,
                    'last_activity' => $event->created_at
                ];
            }
            
            $userActivity[$userId]['event_count']++;
            $userActivity[$userId]['last_activity'] = $event->created_at;
        }

        return array_values($userActivity);
    }

    /**
     * Get dashboard analytics data
     */
    public function getDashboardAnalytics(int $dashboardId, Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $events = AnalyticsEvent::where('event_name', 'dashboard_viewed')
                              ->where('properties->dashboard_id', $dashboardId)
                              ->whereBetween('created_at', [$startDate, $endDate])
                              ->get();

        return [
            'dashboard_id' => $dashboardId,
            'total_views' => $events->count(),
            'unique_viewers' => $events->pluck('user_id')->unique()->count(),
            'average_view_duration' => $this->calculateAverageViewDuration($events),
            'daily_views' => $this->getDailyBreakdown($events),
            'top_viewers' => $this->getTopViewers($events)
        ];
    }

    /**
     * Calculate average view duration
     */
    private function calculateAverageViewDuration($events): float
    {
        $totalDuration = 0;
        $count = 0;

        foreach ($events as $event) {
            $properties = json_decode($event->properties, true);
            if (isset($properties['view_duration'])) {
                $totalDuration += $properties['view_duration'];
                $count++;
            }
        }

        return $count > 0 ? $totalDuration / $count : 0;
    }

    /**
     * Get top viewers
     */
    private function getTopViewers($events): array
    {
        $viewers = [];
        
        foreach ($events as $event) {
            $userId = $event->user_id;
            if (!isset($viewers[$userId])) {
                $viewers[$userId] = 0;
            }
            $viewers[$userId]++;
        }

        arsort($viewers);
        return array_slice($viewers, 0, 10, true);
    }

    /**
     * Get report analytics
     */
    public function getReportAnalytics(int $reportId = null, Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $query = AnalyticsEvent::where('event_name', 'report_generated')
                              ->whereBetween('created_at', [$startDate, $endDate]);

        if ($reportId) {
            $query->where('properties->report_id', $reportId);
        }

        $events = $query->get();

        return [
            'report_id' => $reportId,
            'total_generations' => $events->count(),
            'unique_users' => $events->pluck('user_id')->unique()->count(),
            'average_generation_time' => $this->calculateAverageGenerationTime($events),
            'total_file_size' => $this->calculateTotalFileSize($events),
            'daily_generations' => $this->getDailyBreakdown($events),
            'report_types' => $this->getReportTypes($events)
        ];
    }

    /**
     * Calculate average generation time
     */
    private function calculateAverageGenerationTime($events): float
    {
        $totalTime = 0;
        $count = 0;

        foreach ($events as $event) {
            $properties = json_decode($event->properties, true);
            if (isset($properties['generation_time'])) {
                $totalTime += $properties['generation_time'];
                $count++;
            }
        }

        return $count > 0 ? $totalTime / $count : 0;
    }

    /**
     * Calculate total file size
     */
    private function calculateTotalFileSize($events): int
    {
        $totalSize = 0;

        foreach ($events as $event) {
            $properties = json_decode($event->properties, true);
            if (isset($properties['file_size'])) {
                $totalSize += $properties['file_size'];
            }
        }

        return $totalSize;
    }

    /**
     * Get report types
     */
    private function getReportTypes($events): array
    {
        $types = [];
        
        foreach ($events as $event) {
            $properties = json_decode($event->properties, true);
            if (isset($properties['report_type'])) {
                $type = $properties['report_type'];
                if (!isset($types[$type])) {
                    $types[$type] = 0;
                }
                $types[$type]++;
            }
        }

        return $types;
    }

    /**
     * Get user analytics
     */
    public function getUserAnalytics(int $userId, Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $events = AnalyticsEvent::where('user_id', $userId)
                              ->whereBetween('created_at', [$startDate, $endDate])
                              ->get();

        return [
            'user_id' => $userId,
            'total_events' => $events->count(),
            'unique_sessions' => $events->pluck('session_id')->unique()->count(),
            'event_types' => $events->groupBy('event_name')->map->count(),
            'daily_activity' => $this->getDailyBreakdown($events),
            'most_used_features' => $this->getMostUsedFeatures($events)
        ];
    }

    /**
     * Get most used features
     */
    private function getMostUsedFeatures($events): array
    {
        $features = [];
        
        foreach ($events as $event) {
            $properties = json_decode($event->properties, true);
            if (isset($properties['action'])) {
                $action = $properties['action'];
                if (!isset($features[$action])) {
                    $features[$action] = 0;
                }
                $features[$action]++;
            }
        }

        arsort($features);
        return array_slice($features, 0, 10, true);
    }

    /**
     * Get analytics summary
     */
    public function getAnalyticsSummary(Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $totalEvents = AnalyticsEvent::whereBetween('created_at', [$startDate, $endDate])->count();
        $uniqueUsers = AnalyticsEvent::whereBetween('created_at', [$startDate, $endDate])
                                   ->distinct('user_id')
                                   ->count('user_id');
        $uniqueSessions = AnalyticsEvent::whereBetween('created_at', [$startDate, $endDate])
                                       ->distinct('session_id')
                                       ->count('session_id');

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString()
            ],
            'total_events' => $totalEvents,
            'unique_users' => $uniqueUsers,
            'unique_sessions' => $uniqueSessions,
            'average_events_per_user' => $uniqueUsers > 0 ? round($totalEvents / $uniqueUsers, 2) : 0,
            'top_events' => $this->getTopEvents($startDate, $endDate),
            'user_engagement' => $this->getUserEngagement($startDate, $endDate)
        ];
    }

    /**
     * Get top events
     */
    private function getTopEvents(Carbon $startDate, Carbon $endDate): array
    {
        return AnalyticsEvent::whereBetween('created_at', [$startDate, $endDate])
                            ->selectRaw('event_name, COUNT(*) as count')
                            ->groupBy('event_name')
                            ->orderBy('count', 'desc')
                            ->limit(10)
                            ->pluck('count', 'event_name')
                            ->toArray();
    }

    /**
     * Get user engagement
     */
    private function getUserEngagement(Carbon $startDate, Carbon $endDate): array
    {
        $activeUsers = AnalyticsEvent::whereBetween('created_at', [$startDate, $endDate])
                                    ->distinct('user_id')
                                    ->count('user_id');

        $totalUsers = \App\Models\User::count();

        return [
            'active_users' => $activeUsers,
            'total_users' => $totalUsers,
            'engagement_rate' => $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 2) : 0
        ];
    }

    /**
     * Clean up old analytics data
     */
    public function cleanupOldData(int $daysOld = 90): int
    {
        $cutoffDate = now()->subDays($daysOld);
        
        return AnalyticsEvent::where('created_at', '<', $cutoffDate)->delete();
    }
}
