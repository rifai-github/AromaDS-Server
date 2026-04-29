<?php

namespace App\Repositories\Reports;

use App\Models\KpiDefinition;
use App\Models\KpiValue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class KpiRepository
{
    /**
     * Get all KPI definitions with pagination
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = KpiDefinition::with(['creator', 'updater']);

        // Apply filters
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['calculation_method'])) {
            $query->where('calculation_method', $filters['calculation_method']);
        }

        if (isset($filters['data_source'])) {
            $query->where('data_source', $filters['data_source']);
        }

        if (isset($filters['frequency'])) {
            $query->where('frequency', $filters['frequency']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        return $query->orderBy('name')
                    ->paginate($perPage);
    }

    /**
     * Get KPI definition by ID
     */
    public function getById(int $id): ?KpiDefinition
    {
        return KpiDefinition::with(['creator', 'updater'])
                           ->find($id);
    }

    /**
     * Get KPI by name
     */
    public function getByName(string $name): ?KpiDefinition
    {
        return KpiDefinition::where('name', $name)->first();
    }

    /**
     * Get KPIs by category
     */
    public function getByCategory(string $category): Collection
    {
        return KpiDefinition::where('category', $category)
                           ->where('is_active', true)
                           ->orderBy('name')
                           ->get();
    }

    /**
     * Get active KPIs
     */
    public function getActive(): Collection
    {
        return KpiDefinition::where('is_active', true)
                           ->orderBy('name')
                           ->get();
    }

    /**
     * Get KPIs by frequency
     */
    public function getByFrequency(string $frequency): Collection
    {
        return KpiDefinition::where('frequency', $frequency)
                           ->where('is_active', true)
                           ->orderBy('name')
                           ->get();
    }

    /**
     * Create KPI definition
     */
    public function create(array $data): KpiDefinition
    {
        return KpiDefinition::create($data);
    }

    /**
     * Update KPI definition
     */
    public function update(KpiDefinition $kpi, array $data): bool
    {
        return $kpi->update($data);
    }

    /**
     * Delete KPI definition
     */
    public function delete(KpiDefinition $kpi): bool
    {
        return $kpi->delete();
    }

    /**
     * Get KPI values
     */
    public function getValues(int $kpiId, string $startDate = null, string $endDate = null): Collection
    {
        $query = KpiValue::where('kpi_id', $kpiId);

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }

        return $query->orderBy('date')
                    ->get();
    }

    /**
     * Get latest KPI value
     */
    public function getLatestValue(int $kpiId): ?KpiValue
    {
        return KpiValue::where('kpi_id', $kpiId)
                      ->orderBy('date', 'desc')
                      ->first();
    }

    /**
     * Get KPI value by date
     */
    public function getValueByDate(int $kpiId, string $date): ?KpiValue
    {
        return KpiValue::where('kpi_id', $kpiId)
                      ->where('date', $date)
                      ->first();
    }

    /**
     * Create KPI value
     */
    public function createValue(array $data): KpiValue
    {
        return KpiValue::create($data);
    }

    /**
     * Update KPI value
     */
    public function updateValue(KpiValue $value, array $data): bool
    {
        return $value->update($data);
    }

    /**
     * Delete KPI value
     */
    public function deleteValue(KpiValue $value): bool
    {
        return $value->delete();
    }

    /**
     * Get KPI categories
     */
    public function getCategories(): array
    {
        return KpiDefinition::distinct()
                           ->pluck('category')
                           ->filter()
                           ->sort()
                           ->values()
                           ->toArray();
    }

    /**
     * Get calculation methods
     */
    public function getCalculationMethods(): array
    {
        return KpiDefinition::distinct()
                           ->pluck('calculation_method')
                           ->filter()
                           ->sort()
                           ->values()
                           ->toArray();
    }

    /**
     * Get data sources
     */
    public function getDataSources(): array
    {
        return KpiDefinition::distinct()
                           ->pluck('data_source')
                           ->filter()
                           ->sort()
                           ->values()
                           ->toArray();
    }

    /**
     * Get frequencies
     */
    public function getFrequencies(): array
    {
        return KpiDefinition::distinct()
                           ->pluck('frequency')
                           ->filter()
                           ->sort()
                           ->values()
                           ->toArray();
    }

    /**
     * Search KPIs
     */
    public function search(string $search, int $perPage = 15): LengthAwarePaginator
    {
        return KpiDefinition::where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('category', 'like', "%{$search}%");
        })
        ->orderBy('name')
        ->paginate($perPage);
    }

    /**
     * Get KPI statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_kpis' => KpiDefinition::count(),
            'active_kpis' => KpiDefinition::where('is_active', true)->count(),
            'inactive_kpis' => KpiDefinition::where('is_active', false)->count(),
            'kpis_by_category' => KpiDefinition::selectRaw('category, COUNT(*) as count')
                                               ->groupBy('category')
                                               ->pluck('count', 'category')
                                               ->toArray(),
            'kpis_by_method' => KpiDefinition::selectRaw('calculation_method, COUNT(*) as count')
                                            ->groupBy('calculation_method')
                                            ->pluck('count', 'calculation_method')
                                            ->toArray(),
            'kpis_by_frequency' => KpiDefinition::selectRaw('frequency, COUNT(*) as count')
                                               ->groupBy('frequency')
                                               ->pluck('count', 'frequency')
                                               ->toArray(),
            'total_values' => KpiValue::count(),
            'calculated_today' => KpiValue::whereDate('calculated_at', today())->count(),
            'failed_calculations' => KpiValue::whereNotNull('error_message')->count(),
        ];
    }

    /**
     * Get recent KPIs
     */
    public function getRecent(int $limit = 10): Collection
    {
        return KpiDefinition::where('is_active', true)
                           ->orderBy('updated_at', 'desc')
                           ->limit($limit)
                           ->get();
    }

    /**
     * Get KPIs with latest values
     */
    public function getWithLatestValues(): Collection
    {
        return KpiDefinition::with(['latestValue'])
                           ->where('is_active', true)
                           ->orderBy('name')
                           ->get();
    }

    /**
     * Get KPI performance summary
     */
    public function getPerformanceSummary(int $kpiId): array
    {
        $kpi = $this->getById($kpiId);
        if (!$kpi) {
            return [];
        }

        $latestValue = $this->getLatestValue($kpiId);
        $values = $this->getValues($kpiId, now()->subDays(30)->toDateString());

        return [
            'kpi' => $kpi,
            'latest_value' => $latestValue?->value,
            'target_value' => $kpi->target_value,
            'achievement_percentage' => $this->calculateAchievementPercentage($latestValue?->value, $kpi->target_value),
            'trend' => $this->calculateTrend($values),
            'values_count' => $values->count(),
            'last_calculated' => $latestValue?->calculated_at,
        ];
    }

    /**
     * Calculate achievement percentage
     */
    private function calculateAchievementPercentage($currentValue, $targetValue): float
    {
        if (!$currentValue || !$targetValue || $targetValue == 0) {
            return 0;
        }

        return round(($currentValue / $targetValue) * 100, 2);
    }

    /**
     * Calculate trend
     */
    private function calculateTrend(Collection $values): string
    {
        if ($values->count() < 2) {
            return 'stable';
        }

        $recent = $values->take(-2);
        $first = $recent->first()->value;
        $last = $recent->last()->value;

        if ($last > $first) {
            return 'increasing';
        } elseif ($last < $first) {
            return 'decreasing';
        } else {
            return 'stable';
        }
    }

    /**
     * Get KPIs due for calculation
     */
    public function getDueForCalculation(): Collection
    {
        $now = now();
        
        return KpiDefinition::where('is_active', true)
                           ->where(function ($query) use ($now) {
                               $query->where('frequency', 'daily')
                                     ->orWhere(function ($q) use ($now) {
                                         $q->where('frequency', 'weekly')
                                           ->whereRaw('DAYOFWEEK(?) = 1', [$now]); // Sunday
                                     })
                                     ->orWhere(function ($q) use ($now) {
                                         $q->where('frequency', 'monthly')
                                           ->whereDay($now, 1); // First day of month
                                     });
                           })
                           ->get();
    }

    /**
     * Get KPI trends
     */
    public function getKpiTrends(int $kpiId, int $days = 30): array
    {
        $startDate = now()->subDays($days)->toDateString();
        $values = $this->getValues($kpiId, $startDate);

        return [
            'labels' => $values->pluck('date')->toArray(),
            'values' => $values->pluck('value')->toArray(),
            'trend_direction' => $this->calculateTrend($values),
        ];
    }

    /**
     * Get KPIs by performance status
     */
    public function getByPerformanceStatus(string $status): Collection
    {
        $kpis = $this->getActive();
        $filtered = collect();

        foreach ($kpis as $kpi) {
            $performance = $this->getPerformanceSummary($kpi->id);
            $achievement = $performance['achievement_percentage'] ?? 0;

            switch ($status) {
                case 'excellent':
                    if ($achievement >= 100) {
                        $filtered->push($kpi);
                    }
                    break;
                case 'good':
                    if ($achievement >= 80 && $achievement < 100) {
                        $filtered->push($kpi);
                    }
                    break;
                case 'fair':
                    if ($achievement >= 60 && $achievement < 80) {
                        $filtered->push($kpi);
                    }
                    break;
                case 'poor':
                    if ($achievement < 60) {
                        $filtered->push($kpi);
                    }
                    break;
            }
        }

        return $filtered;
    }

    /**
     * Clean up old KPI values
     */
    public function cleanupOldValues(int $daysOld = 365): int
    {
        $cutoffDate = now()->subDays($daysOld);
        
        return KpiValue::where('date', '<', $cutoffDate->toDateString())->delete();
    }
}
