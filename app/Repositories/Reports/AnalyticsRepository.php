<?php

namespace App\Repositories\Reports;

use App\Models\AnalyticsEvent;
use App\Models\AnalyticsMetric;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AnalyticsRepository
{
    /**
     * Get all analytics events with pagination
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = AnalyticsEvent::with(['user']);

        // Apply filters
        if (isset($filters['event_name'])) {
            $query->where('event_name', $filters['event_name']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['session_id'])) {
            $query->where('session_id', $filters['session_id']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('event_name', 'like', "%{$filters['search']}%")
                  ->orWhere('properties', 'like', "%{$filters['search']}%");
            });
        }

        return $query->orderBy('created_at', 'desc')
                    ->paginate($perPage);
    }

    /**
     * Get analytics event by ID
     */
    public function getById(int $id): ?AnalyticsEvent
    {
        return AnalyticsEvent::with(['user'])->find($id);
    }

    /**
     * Get events by name
     */
    public function getByName(string $eventName, int $limit = 100): Collection
    {
        return AnalyticsEvent::with(['user'])
                            ->where('event_name', $eventName)
                            ->orderBy('created_at', 'desc')
                            ->limit($limit)
                            ->get();
    }

    /**
     * Get events by user
     */
    public function getByUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return AnalyticsEvent::where('user_id', $userId)
                            ->orderBy('created_at', 'desc')
                            ->paginate($perPage);
    }

    /**
     * Get events by session
     */
    public function getBySession(string $sessionId): Collection
    {
        return AnalyticsEvent::with(['user'])
                            ->where('session_id', $sessionId)
                            ->orderBy('created_at')
                            ->get();
    }

    /**
     * Get events by date range
     */
    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        return AnalyticsEvent::with(['user'])
                            ->whereBetween('created_at', [$startDate, $endDate])
                            ->orderBy('created_at', 'desc')
                            ->get();
    }

    /**
     * Create analytics event
     */
    public function create(array $data): AnalyticsEvent
    {
        return AnalyticsEvent::create($data);
    }

    /**
     * Update analytics event
     */
    public function update(AnalyticsEvent $event, array $data): bool
    {
        return $event->update($data);
    }

    /**
     * Delete analytics event
     */
    public function delete(AnalyticsEvent $event): bool
    {
        return $event->delete();
    }

    /**
     * Get event statistics
     */
    public function getEventStatistics(string $startDate = null, string $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30)->toDateString();
        $endDate = $endDate ?? now()->toDateString();

        $query = AnalyticsEvent::whereBetween('created_at', [$startDate, $endDate]);

        return [
            'total_events' => $query->count(),
            'unique_users' => $query->distinct('user_id')->count('user_id'),
            'unique_sessions' => $query->distinct('session_id')->count('session_id'),
            'events_by_name' => $query->selectRaw('event_name, COUNT(*) as count')
                                     ->groupBy('event_name')
                                     ->orderBy('count', 'desc')
                                     ->pluck('count', 'event_name')
                                     ->toArray(),
            'events_by_user' => $query->selectRaw('user_id, COUNT(*) as count')
                                     ->groupBy('user_id')
                                     ->orderBy('count', 'desc')
                                     ->limit(10)
                                     ->pluck('count', 'user_id')
                                     ->toArray(),
            'daily_events' => $query->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                                   ->groupBy('date')
                                   ->orderBy('date')
                                   ->pluck('count', 'date')
                                   ->toArray(),
        ];
    }

    /**
     * Get user activity statistics
     */
    public function getUserActivityStatistics(int $userId, string $startDate = null, string $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30)->toDateString();
        $endDate = $endDate ?? now()->toDateString();

        $events = AnalyticsEvent::where('user_id', $userId)
                              ->whereBetween('created_at', [$startDate, $endDate])
                              ->get();

        return [
            'user_id' => $userId,
            'total_events' => $events->count(),
            'unique_sessions' => $events->pluck('session_id')->unique()->count(),
            'event_types' => $events->groupBy('event_name')->map->count(),
            'daily_activity' => $events->groupBy(function ($event) {
                return $event->created_at->toDateString();
            })->map->count(),
            'most_active_day' => $events->groupBy(function ($event) {
                return $event->created_at->format('l');
            })->map->count()->sortDesc()->keys()->first(),
            'average_events_per_session' => $events->groupBy('session_id')->map->count()->avg(),
        ];
    }

    /**
     * Get page view statistics
     */
    public function getPageViewStatistics(string $startDate = null, string $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30)->toDateString();
        $endDate = $endDate ?? now()->toDateString();

        $pageViews = AnalyticsEvent::where('event_name', 'page_view')
                                 ->whereBetween('created_at', [$startDate, $endDate])
                                 ->get();

        $pages = [];
        foreach ($pageViews as $event) {
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

        return [
            'total_page_views' => $pageViews->count(),
            'unique_pages' => count($pages),
            'top_pages' => array_slice($pages, 0, 10, true),
            'daily_page_views' => $pageViews->groupBy(function ($event) {
                return $event->created_at->toDateString();
            })->map->count(),
        ];
    }

    /**
     * Get report generation statistics
     */
    public function getReportGenerationStatistics(string $startDate = null, string $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30)->toDateString();
        $endDate = $endDate ?? now()->toDateString();

        $reportEvents = AnalyticsEvent::where('event_name', 'report_generated')
                                    ->whereBetween('created_at', [$startDate, $endDate])
                                    ->get();

        $reportTypes = [];
        $generationTimes = [];
        $fileSizes = [];

        foreach ($reportEvents as $event) {
            $properties = json_decode($event->properties, true);
            
            if (isset($properties['report_type'])) {
                $type = $properties['report_type'];
                if (!isset($reportTypes[$type])) {
                    $reportTypes[$type] = 0;
                }
                $reportTypes[$type]++;
            }

            if (isset($properties['generation_time'])) {
                $generationTimes[] = $properties['generation_time'];
            }

            if (isset($properties['file_size'])) {
                $fileSizes[] = $properties['file_size'];
            }
        }

        return [
            'total_reports_generated' => $reportEvents->count(),
            'unique_report_types' => count($reportTypes),
            'reports_by_type' => $reportTypes,
            'average_generation_time' => count($generationTimes) > 0 ? array_sum($generationTimes) / count($generationTimes) : 0,
            'total_file_size' => array_sum($fileSizes),
            'average_file_size' => count($fileSizes) > 0 ? array_sum($fileSizes) / count($fileSizes) : 0,
            'daily_report_generations' => $reportEvents->groupBy(function ($event) {
                return $event->created_at->toDateString();
            })->map->count(),
        ];
    }

    /**
     * Get dashboard view statistics
     */
    public function getDashboardViewStatistics(int $dashboardId = null, string $startDate = null, string $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30)->toDateString();
        $endDate = $endDate ?? now()->toDateString();

        $query = AnalyticsEvent::where('event_name', 'dashboard_viewed')
                             ->whereBetween('created_at', [$startDate, $endDate]);

        if ($dashboardId) {
            $query->whereRaw("JSON_EXTRACT(properties, '$.dashboard_id') = ?", [$dashboardId]);
        }

        $dashboardEvents = $query->get();

        $dashboards = [];
        $viewDurations = [];

        foreach ($dashboardEvents as $event) {
            $properties = json_decode($event->properties, true);
            
            if (isset($properties['dashboard_id'])) {
                $id = $properties['dashboard_id'];
                if (!isset($dashboards[$id])) {
                    $dashboards[$id] = 0;
                }
                $dashboards[$id]++;
            }

            if (isset($properties['view_duration'])) {
                $viewDurations[] = $properties['view_duration'];
            }
        }

        return [
            'total_dashboard_views' => $dashboardEvents->count(),
            'unique_dashboards' => count($dashboards),
            'dashboards_by_views' => $dashboards,
            'average_view_duration' => count($viewDurations) > 0 ? array_sum($viewDurations) / count($viewDurations) : 0,
            'daily_dashboard_views' => $dashboardEvents->groupBy(function ($event) {
                return $event->created_at->toDateString();
            })->map->count(),
        ];
    }

    /**
     * Get top events
     */
    public function getTopEvents(int $limit = 10, string $startDate = null, string $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30)->toDateString();
        $endDate = $endDate ?? now()->toDateString();

        return AnalyticsEvent::whereBetween('created_at', [$startDate, $endDate])
                            ->selectRaw('event_name, COUNT(*) as count')
                            ->groupBy('event_name')
                            ->orderBy('count', 'desc')
                            ->limit($limit)
                            ->pluck('count', 'event_name')
                            ->toArray();
    }

    /**
     * Get top users
     */
    public function getTopUsers(int $limit = 10, string $startDate = null, string $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30)->toDateString();
        $endDate = $endDate ?? now()->toDateString();

        return AnalyticsEvent::whereBetween('created_at', [$startDate, $endDate])
                            ->selectRaw('user_id, COUNT(*) as count')
                            ->groupBy('user_id')
                            ->orderBy('count', 'desc')
                            ->limit($limit)
                            ->pluck('count', 'user_id')
                            ->toArray();
    }

    /**
     * Get event trends
     */
    public function getEventTrends(string $eventName, int $days = 30): array
    {
        $startDate = now()->subDays($days)->toDateString();
        $endDate = now()->toDateString();

        $trends = AnalyticsEvent::where('event_name', $eventName)
                              ->whereBetween('created_at', [$startDate, $endDate])
                              ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                              ->groupBy('date')
                              ->orderBy('date')
                              ->get();

        return [
            'labels' => $trends->pluck('date')->toArray(),
            'data' => $trends->pluck('count')->toArray(),
        ];
    }

    /**
     * Get user engagement metrics
     */
    public function getUserEngagementMetrics(string $startDate = null, string $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30)->toDateString();
        $endDate = $endDate ?? now()->toDateString();

        $activeUsers = AnalyticsEvent::whereBetween('created_at', [$startDate, $endDate])
                                   ->distinct('user_id')
                                   ->count('user_id');

        $totalUsers = \App\Models\User::count();

        $sessions = AnalyticsEvent::whereBetween('created_at', [$startDate, $endDate])
                                ->distinct('session_id')
                                ->count('session_id');

        return [
            'active_users' => $activeUsers,
            'total_users' => $totalUsers,
            'engagement_rate' => $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 2) : 0,
            'total_sessions' => $sessions,
            'average_sessions_per_user' => $activeUsers > 0 ? round($sessions / $activeUsers, 2) : 0,
        ];
    }

    /**
     * Search events
     */
    public function search(string $search, int $perPage = 15): LengthAwarePaginator
    {
        return AnalyticsEvent::with(['user'])
                           ->where(function ($q) use ($search) {
                               $q->where('event_name', 'like', "%{$search}%")
                                 ->orWhere('properties', 'like', "%{$search}%")
                                 ->orWhere('ip_address', 'like', "%{$search}%");
                           })
                           ->orderBy('created_at', 'desc')
                           ->paginate($perPage);
    }

    /**
     * Clean up old analytics data
     */
    public function cleanupOldData(int $daysOld = 90): int
    {
        $cutoffDate = now()->subDays($daysOld);
        
        return AnalyticsEvent::where('created_at', '<', $cutoffDate)->delete();
    }

    /**
     * Get analytics metrics
     */
    public function getMetrics(string $metricName = null): Collection
    {
        $query = AnalyticsMetric::query();
        
        if ($metricName) {
            $query->where('metric_name', $metricName);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Create analytics metric
     */
    public function createMetric(array $data): AnalyticsMetric
    {
        return AnalyticsMetric::create($data);
    }

    /**
     * Get metric statistics
     */
    public function getMetricStatistics(): array
    {
        return [
            'total_events' => AnalyticsEvent::count(),
            'total_metrics' => AnalyticsMetric::count(),
            'unique_event_names' => AnalyticsEvent::distinct('event_name')->count('event_name'),
            'unique_users' => AnalyticsEvent::distinct('user_id')->count('user_id'),
            'unique_sessions' => AnalyticsEvent::distinct('session_id')->count('session_id'),
            'events_today' => AnalyticsEvent::whereDate('created_at', today())->count(),
            'events_this_week' => AnalyticsEvent::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'events_this_month' => AnalyticsEvent::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
        ];
    }
}
