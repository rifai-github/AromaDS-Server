<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

class ReportCacheService
{
    /**
     * Cache duration in minutes
     */
    const CACHE_DURATION = 60;
    const LONG_CACHE_DURATION = 1440; // 24 hours
    const SHORT_CACHE_DURATION = 5; // 5 minutes

    /**
     * Cache keys
     */
    const DASHBOARD_KEY = 'dashboard:';
    const KPI_KEY = 'kpi:';
    const ANALYTICS_KEY = 'analytics:';
    const REPORT_KEY = 'report:';
    const TEMPLATE_KEY = 'template:';
    const EXPORT_KEY = 'export:';

    /**
     * Cache dashboard data
     */
    public function cacheDashboard(int $dashboardId, array $data, int $duration = null): void
    {
        $key = self::DASHBOARD_KEY . $dashboardId;
        $duration = $duration ?? self::CACHE_DURATION;
        
        Cache::put($key, $data, $duration);
    }

    /**
     * Get cached dashboard data
     */
    public function getCachedDashboard(int $dashboardId): ?array
    {
        $key = self::DASHBOARD_KEY . $dashboardId;
        return Cache::get($key);
    }

    /**
     * Cache KPI data
     */
    public function cacheKpi(int $kpiId, array $data, int $duration = null): void
    {
        $key = self::KPI_KEY . $kpiId;
        $duration = $duration ?? self::CACHE_DURATION;
        
        Cache::put($key, $data, $duration);
    }

    /**
     * Get cached KPI data
     */
    public function getCachedKpi(int $kpiId): ?array
    {
        $key = self::KPI_KEY . $kpiId;
        return Cache::get($key);
    }

    /**
     * Cache analytics data
     */
    public function cacheAnalytics(string $analyticsType, array $data, int $duration = null): void
    {
        $key = self::ANALYTICS_KEY . $analyticsType;
        $duration = $duration ?? self::CACHE_DURATION;
        
        Cache::put($key, $data, $duration);
    }

    /**
     * Get cached analytics data
     */
    public function getCachedAnalytics(string $analyticsType): ?array
    {
        $key = self::ANALYTICS_KEY . $analyticsType;
        return Cache::get($key);
    }

    /**
     * Cache report data
     */
    public function cacheReport(int $reportId, array $parameters, array $data, int $duration = null): void
    {
        $key = self::REPORT_KEY . $reportId . ':' . md5(serialize($parameters));
        $duration = $duration ?? self::LONG_CACHE_DURATION;
        
        Cache::put($key, $data, $duration);
    }

    /**
     * Get cached report data
     */
    public function getCachedReport(int $reportId, array $parameters): ?array
    {
        $key = self::REPORT_KEY . $reportId . ':' . md5(serialize($parameters));
        return Cache::get($key);
    }

    /**
     * Cache template data
     */
    public function cacheTemplate(int $templateId, array $data, int $duration = null): void
    {
        $key = self::TEMPLATE_KEY . $templateId;
        $duration = $duration ?? self::LONG_CACHE_DURATION;
        
        Cache::put($key, $data, $duration);
    }

    /**
     * Get cached template data
     */
    public function getCachedTemplate(int $templateId): ?array
    {
        $key = self::TEMPLATE_KEY . $templateId;
        return Cache::get($key);
    }

    /**
     * Cache export data
     */
    public function cacheExport(int $exportId, array $data, int $duration = null): void
    {
        $key = self::EXPORT_KEY . $exportId;
        $duration = $duration ?? self::SHORT_CACHE_DURATION;
        
        Cache::put($key, $data, $duration);
    }

    /**
     * Get cached export data
     */
    public function getCachedExport(int $exportId): ?array
    {
        $key = self::EXPORT_KEY . $exportId;
        return Cache::get($key);
    }

    /**
     * Cache dashboard widget data
     */
    public function cacheWidget(int $widgetId, array $data, int $duration = null): void
    {
        $key = 'widget:' . $widgetId;
        $duration = $duration ?? self::CACHE_DURATION;
        
        Cache::put($key, $data, $duration);
    }

    /**
     * Get cached widget data
     */
    public function getCachedWidget(int $widgetId): ?array
    {
        $key = 'widget:' . $widgetId;
        return Cache::get($key);
    }

    /**
     * Cache KPI trend data
     */
    public function cacheKpiTrend(int $kpiId, int $days, array $data, int $duration = null): void
    {
        $key = 'kpi_trend:' . $kpiId . ':' . $days;
        $duration = $duration ?? self::CACHE_DURATION;
        
        Cache::put($key, $data, $duration);
    }

    /**
     * Get cached KPI trend data
     */
    public function getCachedKpiTrend(int $kpiId, int $days): ?array
    {
        $key = 'kpi_trend:' . $kpiId . ':' . $days;
        return Cache::get($key);
    }

    /**
     * Cache analytics summary
     */
    public function cacheAnalyticsSummary(string $period, array $data, int $duration = null): void
    {
        $key = 'analytics_summary:' . $period;
        $duration = $duration ?? self::CACHE_DURATION;
        
        Cache::put($key, $data, $duration);
    }

    /**
     * Get cached analytics summary
     */
    public function getCachedAnalyticsSummary(string $period): ?array
    {
        $key = 'analytics_summary:' . $period;
        return Cache::get($key);
    }

    /**
     * Cache user dashboard preferences
     */
    public function cacheUserDashboardPreferences(int $userId, array $preferences, int $duration = null): void
    {
        $key = 'user_dashboard_prefs:' . $userId;
        $duration = $duration ?? self::LONG_CACHE_DURATION;
        
        Cache::put($key, $preferences, $duration);
    }

    /**
     * Get cached user dashboard preferences
     */
    public function getCachedUserDashboardPreferences(int $userId): ?array
    {
        $key = 'user_dashboard_prefs:' . $userId;
        return Cache::get($key);
    }

    /**
     * Cache report statistics
     */
    public function cacheReportStatistics(array $data, int $duration = null): void
    {
        $key = 'report_statistics';
        $duration = $duration ?? self::CACHE_DURATION;
        
        Cache::put($key, $data, $duration);
    }

    /**
     * Get cached report statistics
     */
    public function getCachedReportStatistics(): ?array
    {
        $key = 'report_statistics';
        return Cache::get($key);
    }

    /**
     * Cache dashboard list
     */
    public function cacheDashboardList(int $userId, array $dashboards, int $duration = null): void
    {
        $key = 'dashboard_list:' . $userId;
        $duration = $duration ?? self::CACHE_DURATION;
        
        Cache::put($key, $dashboards, $duration);
    }

    /**
     * Get cached dashboard list
     */
    public function getCachedDashboardList(int $userId): ?array
    {
        $key = 'dashboard_list:' . $userId;
        return Cache::get($key);
    }

    /**
     * Cache template list
     */
    public function cacheTemplateList(array $templates, int $duration = null): void
    {
        $key = 'template_list';
        $duration = $duration ?? self::LONG_CACHE_DURATION;
        
        Cache::put($key, $templates, $duration);
    }

    /**
     * Get cached template list
     */
    public function getCachedTemplateList(): ?array
    {
        $key = 'template_list';
        return Cache::get($key);
    }

    /**
     * Clear cache by pattern
     */
    public function clearCacheByPattern(string $pattern): void
    {
        if (config('cache.default') === 'redis') {
            $keys = Redis::keys($pattern);
            if (!empty($keys)) {
                Redis::del($keys);
            }
        } else {
            // For other cache drivers, we need to track keys manually
            // This is a simplified approach
            Cache::flush();
        }
    }

    /**
     * Clear dashboard cache
     */
    public function clearDashboardCache(int $dashboardId = null): void
    {
        if ($dashboardId) {
            $this->clearCacheByPattern(self::DASHBOARD_KEY . $dashboardId . '*');
        } else {
            $this->clearCacheByPattern(self::DASHBOARD_KEY . '*');
        }
    }

    /**
     * Clear KPI cache
     */
    public function clearKpiCache(int $kpiId = null): void
    {
        if ($kpiId) {
            $this->clearCacheByPattern(self::KPI_KEY . $kpiId . '*');
        } else {
            $this->clearCacheByPattern(self::KPI_KEY . '*');
        }
    }

    /**
     * Clear analytics cache
     */
    public function clearAnalyticsCache(string $analyticsType = null): void
    {
        if ($analyticsType) {
            $this->clearCacheByPattern(self::ANALYTICS_KEY . $analyticsType . '*');
        } else {
            $this->clearCacheByPattern(self::ANALYTICS_KEY . '*');
        }
    }

    /**
     * Clear report cache
     */
    public function clearReportCache(int $reportId = null): void
    {
        if ($reportId) {
            $this->clearCacheByPattern(self::REPORT_KEY . $reportId . '*');
        } else {
            $this->clearCacheByPattern(self::REPORT_KEY . '*');
        }
    }

    /**
     * Clear template cache
     */
    public function clearTemplateCache(int $templateId = null): void
    {
        if ($templateId) {
            $this->clearCacheByPattern(self::TEMPLATE_KEY . $templateId . '*');
        } else {
            $this->clearCacheByPattern(self::TEMPLATE_KEY . '*');
        }
    }

    /**
     * Clear export cache
     */
    public function clearExportCache(int $exportId = null): void
    {
        if ($exportId) {
            $this->clearCacheByPattern(self::EXPORT_KEY . $exportId . '*');
        } else {
            $this->clearCacheByPattern(self::EXPORT_KEY . '*');
        }
    }

    /**
     * Clear all report module cache
     */
    public function clearAllCache(): void
    {
        $patterns = [
            self::DASHBOARD_KEY . '*',
            self::KPI_KEY . '*',
            self::ANALYTICS_KEY . '*',
            self::REPORT_KEY . '*',
            self::TEMPLATE_KEY . '*',
            self::EXPORT_KEY . '*',
            'widget:*',
            'kpi_trend:*',
            'analytics_summary:*',
            'user_dashboard_prefs:*',
            'report_statistics',
            'dashboard_list:*',
            'template_list'
        ];

        foreach ($patterns as $pattern) {
            $this->clearCacheByPattern($pattern);
        }
    }

    /**
     * Get cache statistics
     */
    public function getCacheStatistics(): array
    {
        if (config('cache.default') === 'redis') {
            $info = Redis::info();
            return [
                'driver' => 'redis',
                'used_memory' => $info['used_memory_human'] ?? 'N/A',
                'connected_clients' => $info['connected_clients'] ?? 'N/A',
                'total_commands_processed' => $info['total_commands_processed'] ?? 'N/A',
                'keyspace_hits' => $info['keyspace_hits'] ?? 'N/A',
                'keyspace_misses' => $info['keyspace_misses'] ?? 'N/A',
            ];
        }

        return [
            'driver' => config('cache.default'),
            'message' => 'Cache statistics not available for this driver'
        ];
    }

    /**
     * Warm up cache
     */
    public function warmUpCache(): void
    {
        // This method can be called to pre-populate cache with frequently accessed data
        // Implementation depends on specific requirements
    }
}
