<?php

namespace App\Services\Reports;

use App\Models\KpiDefinition;
use App\Models\KpiValue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class KpiService
{
    /**
     * Create a new KPI definition
     */
    public function createKpi(array $data): KpiDefinition
    {
        return DB::transaction(function () use ($data) {
            $kpi = KpiDefinition::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'category' => $data['category'],
                'calculation_method' => $data['calculation_method'],
                'data_source' => $data['data_source'],
                'query' => $data['query'],
                'target_value' => $data['target_value'] ?? null,
                'unit' => $data['unit'] ?? null,
                'frequency' => $data['frequency'] ?? 'daily',
                'is_active' => $data['is_active'] ?? true,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            return $kpi;
        });
    }

    /**
     * Update KPI definition
     */
    public function updateKpi(KpiDefinition $kpi, array $data): KpiDefinition
    {
        $kpi->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? $kpi->description,
            'category' => $data['category'] ?? $kpi->category,
            'calculation_method' => $data['calculation_method'] ?? $kpi->calculation_method,
            'data_source' => $data['data_source'] ?? $kpi->data_source,
            'query' => $data['query'] ?? $kpi->query,
            'target_value' => $data['target_value'] ?? $kpi->target_value,
            'unit' => $data['unit'] ?? $kpi->unit,
            'frequency' => $data['frequency'] ?? $kpi->frequency,
            'is_active' => $data['is_active'] ?? $kpi->is_active,
            'updated_by' => Auth::id()
        ]);

        return $kpi;
    }

    /**
     * Calculate KPI value
     */
    public function calculateKpi(KpiDefinition $kpi, Carbon $date = null): KpiValue
    {
        $date = $date ?? now();
        
        return DB::transaction(function () use ($kpi, $date) {
            try {
                // Execute calculation based on method
                $value = $this->executeCalculation($kpi, $date);
                
                // Create or update KPI value
                $kpiValue = KpiValue::updateOrCreate(
                    [
                        'kpi_id' => $kpi->id,
                        'date' => $date->toDateString()
                    ],
                    [
                        'value' => $value,
                        'calculated_at' => now(),
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id()
                    ]
                );

                return $kpiValue;

            } catch (\Exception $e) {
                // Log error and create failed value
                KpiValue::updateOrCreate(
                    [
                        'kpi_id' => $kpi->id,
                        'date' => $date->toDateString()
                    ],
                    [
                        'value' => null,
                        'error_message' => $e->getMessage(),
                        'calculated_at' => now(),
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id()
                    ]
                );

                throw $e;
            }
        });
    }

    /**
     * Execute KPI calculation
     */
    private function executeCalculation(KpiDefinition $kpi, Carbon $date): float
    {
        switch ($kpi->calculation_method) {
            case 'sql_query':
                return $this->calculateFromSql($kpi, $date);
            case 'formula':
                return $this->calculateFromFormula($kpi, $date);
            case 'aggregation':
                return $this->calculateFromAggregation($kpi, $date);
            default:
                throw new \InvalidArgumentException("Unsupported calculation method: {$kpi->calculation_method}");
        }
    }

    /**
     * Calculate from SQL query
     */
    private function calculateFromSql(KpiDefinition $kpi, Carbon $date): float
    {
        $query = $kpi->query;
        
        // Replace date parameters
        $query = str_replace(':date', "'{$date->toDateString()}'", $query);
        $query = str_replace(':start_of_month', "'{$date->startOfMonth()->toDateString()}'", $query);
        $query = str_replace(':end_of_month', "'{$date->endOfMonth()->toDateString()}'", $query);
        $query = str_replace(':start_of_year', "'{$date->startOfYear()->toDateString()}'", $query);
        $query = str_replace(':end_of_year', "'{$date->endOfYear()->toDateString()}'", $query);

        $result = DB::select($query);
        
        if (empty($result)) {
            return 0;
        }

        $row = (array) $result[0];
        return (float) reset($row);
    }

    /**
     * Calculate from formula
     */
    private function calculateFromFormula(KpiDefinition $kpi, Carbon $date): float
    {
        // This would require a formula parser
        // For now, return 0
        return 0;
    }

    /**
     * Calculate from aggregation
     */
    private function calculateFromAggregation(KpiDefinition $kpi, Carbon $date): float
    {
        // This would require aggregation logic
        // For now, return 0
        return 0;
    }

    /**
     * Get KPI values for date range
     */
    public function getKpiValues(KpiDefinition $kpi, Carbon $startDate, Carbon $endDate): \Illuminate\Database\Eloquent\Collection
    {
        return KpiValue::where('kpi_id', $kpi->id)
                      ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                      ->orderBy('date')
                      ->get();
    }

    /**
     * Get KPI trend data
     */
    public function getKpiTrend(KpiDefinition $kpi, int $days = 30): array
    {
        $endDate = now();
        $startDate = $endDate->copy()->subDays($days);

        $values = $this->getKpiValues($kpi, $startDate, $endDate);

        $trend = [
            'labels' => [],
            'values' => [],
            'target' => $kpi->target_value,
            'unit' => $kpi->unit,
            'current_value' => null,
            'previous_value' => null,
            'change_percentage' => 0
        ];

        foreach ($values as $value) {
            $trend['labels'][] = $value->date;
            $trend['values'][] = $value->value;
        }

        // Calculate current and previous values
        if (count($trend['values']) >= 2) {
            $trend['current_value'] = end($trend['values']);
            $trend['previous_value'] = $trend['values'][count($trend['values']) - 2];
            
            if ($trend['previous_value'] != 0) {
                $trend['change_percentage'] = (($trend['current_value'] - $trend['previous_value']) / $trend['previous_value']) * 100;
            }
        } elseif (count($trend['values']) == 1) {
            $trend['current_value'] = $trend['values'][0];
        }

        return $trend;
    }

    /**
     * Get KPI performance summary
     */
    public function getKpiPerformance(KpiDefinition $kpi, Carbon $date = null): array
    {
        $date = $date ?? now();
        
        $currentValue = KpiValue::where('kpi_id', $kpi->id)
                               ->where('date', $date->toDateString())
                               ->first();

        $performance = [
            'kpi' => $kpi,
            'current_value' => $currentValue?->value,
            'target_value' => $kpi->target_value,
            'unit' => $kpi->unit,
            'achievement_percentage' => 0,
            'status' => 'unknown',
            'trend' => $this->getKpiTrend($kpi, 7)
        ];

        if ($currentValue && $kpi->target_value) {
            $performance['achievement_percentage'] = ($currentValue->value / $kpi->target_value) * 100;
            
            if ($performance['achievement_percentage'] >= 100) {
                $performance['status'] = 'excellent';
            } elseif ($performance['achievement_percentage'] >= 80) {
                $performance['status'] = 'good';
            } elseif ($performance['achievement_percentage'] >= 60) {
                $performance['status'] = 'fair';
            } else {
                $performance['status'] = 'poor';
            }
        }

        return $performance;
    }

    /**
     * Bulk calculate KPIs
     */
    public function bulkCalculateKpis(array $kpiIds, Carbon $date = null): array
    {
        $date = $date ?? now();
        $results = [];

        foreach ($kpiIds as $kpiId) {
            $kpi = KpiDefinition::find($kpiId);
            if ($kpi) {
                try {
                    $value = $this->calculateKpi($kpi, $date);
                    $results[] = [
                        'kpi_id' => $kpiId,
                        'status' => 'success',
                        'value' => $value->value,
                        'message' => 'KPI calculated successfully'
                    ];
                } catch (\Exception $e) {
                    $results[] = [
                        'kpi_id' => $kpiId,
                        'status' => 'error',
                        'value' => null,
                        'message' => $e->getMessage()
                    ];
                }
            }
        }

        return $results;
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
            'total_values' => KpiValue::count(),
            'calculated_today' => KpiValue::whereDate('calculated_at', today())->count(),
        ];
    }
}
