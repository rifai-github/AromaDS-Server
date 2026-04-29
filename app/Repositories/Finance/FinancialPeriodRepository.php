<?php

namespace App\Repositories\Finance;

use App\Models\Finance\FinancialPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class FinancialPeriodRepository
{
    protected $model;

    public function __construct(FinancialPeriod $model)
    {
        $this->model = $model;
    }

    /**
     * Get all financial periods with pagination
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('start_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('end_date', '<=', $filters['date_to']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where('period_name', 'like', "%{$search}%");
        }

        return $query->orderBy('start_date', 'desc')->paginate($perPage);
    }

    /**
     * Get financial period by ID
     */
    public function getById(int $id): ?FinancialPeriod
    {
        return $this->model->find($id);
    }

    /**
     * Create new financial period
     */
    public function create(array $data): FinancialPeriod
    {
        return $this->model->create($data);
    }

    /**
     * Update financial period
     */
    public function update(int $id, array $data): bool
    {
        $financialPeriod = $this->model->find($id);
        if (!$financialPeriod) {
            return false;
        }

        return $financialPeriod->update($data);
    }

    /**
     * Delete financial period
     */
    public function delete(int $id): bool
    {
        $financialPeriod = $this->model->find($id);
        if (!$financialPeriod) {
            return false;
        }

        return $financialPeriod->delete();
    }

    /**
     * Get current financial period
     */
    public function getCurrent(): ?FinancialPeriod
    {
        $today = Carbon::now()->toDateString();
        
        return $this->model->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->where('status', 'open')
            ->first();
    }

    /**
     * Get financial period for date
     */
    public function getForDate(string $date): ?FinancialPeriod
    {
        return $this->model->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();
    }

    /**
     * Get open financial periods
     */
    public function getOpen(): Collection
    {
        return $this->model->where('status', 'open')
            ->orderBy('start_date', 'asc')
            ->get();
    }

    /**
     * Get closed financial periods
     */
    public function getClosed(): Collection
    {
        return $this->model->where('status', 'closed')
            ->orderBy('start_date', 'desc')
            ->get();
    }

    /**
     * Get locked financial periods
     */
    public function getLocked(): Collection
    {
        return $this->model->where('status', 'locked')
            ->orderBy('start_date', 'desc')
            ->get();
    }

    /**
     * Get financial periods by status
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)
            ->orderBy('start_date', 'desc')
            ->get();
    }

    /**
     * Get financial period statistics
     */
    public function getStatistics(): array
    {
        $total = $this->model->count();
        $open = $this->model->where('status', 'open')->count();
        $closed = $this->model->where('status', 'closed')->count();
        $locked = $this->model->where('status', 'locked')->count();

        $currentPeriod = $this->getCurrent();

        return [
            'total_periods' => $total,
            'open_periods' => $open,
            'closed_periods' => $closed,
            'locked_periods' => $locked,
            'current_period' => $currentPeriod,
        ];
    }

    /**
     * Search financial periods
     */
    public function search(string $query, int $limit = 20): Collection
    {
        return $this->model->where('period_name', 'like', "%{$query}%")
            ->orderBy('start_date', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get financial periods for dropdown/select
     */
    public function getForSelect(): array
    {
        return $this->model->orderBy('start_date', 'desc')
            ->pluck('period_name', 'id')
            ->toArray();
    }

    /**
     * Get open financial periods for dropdown/select
     */
    public function getOpenForSelect(): array
    {
        return $this->model->where('status', 'open')
            ->orderBy('start_date', 'asc')
            ->pluck('period_name', 'id')
            ->toArray();
    }

    /**
     * Check for overlapping periods
     */
    public function hasOverlappingPeriods(string $startDate, string $endDate, int $excludeId = null): bool
    {
        $query = $this->model->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function ($q2) use ($startDate, $endDate) {
                  $q2->where('start_date', '<=', $startDate)
                     ->where('end_date', '>=', $endDate);
              });
        });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get financial periods by date range
     */
    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        return $this->model->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                      ->orWhereBetween('end_date', [$startDate, $endDate])
                      ->orWhere(function ($q) use ($startDate, $endDate) {
                          $q->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                      });
            })
            ->orderBy('start_date', 'asc')
            ->get();
    }

    /**
     * Get financial periods for export
     */
    public function getForExport(array $filters = []): Collection
    {
        $query = $this->model->newQuery();

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('start_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('end_date', '<=', $filters['date_to']);
        }

        return $query->orderBy('start_date', 'desc')->get();
    }

    /**
     * Get financial period trends
     */
    public function getTrends(string $period = 'year'): array
    {
        $startDate = $this->getPeriodStartDate($period);
        
        $financialPeriods = $this->model->where('created_at', '>=', $startDate)
            ->get()
            ->groupBy(function ($financialPeriod) use ($period) {
                return $financialPeriod->created_at->format($this->getDateFormat($period));
            });

        $trends = [];
        foreach ($financialPeriods as $date => $group) {
            $trends[] = [
                'date' => $date,
                'total_periods' => $group->count(),
                'open_count' => $group->where('status', 'open')->count(),
                'closed_count' => $group->where('status', 'closed')->count(),
                'locked_count' => $group->where('status', 'locked')->count(),
            ];
        }

        return $trends;
    }

    /**
     * Generate monthly periods for a year
     */
    public function generateMonthlyPeriods(int $year): array
    {
        $periods = [];
        $errors = [];

        for ($month = 1; $month <= 12; $month++) {
            $startDate = Carbon::create($year, $month, 1)->toDateString();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();
            $periodName = Carbon::create($year, $month, 1)->format('F Y');

            try {
                // Check if period already exists
                if ($this->model->where('period_name', $periodName)->exists()) {
                    $errors[] = "Period '{$periodName}' already exists";
                    continue;
                }

                // Check for overlapping periods
                if ($this->hasOverlappingPeriods($startDate, $endDate)) {
                    $errors[] = "Period '{$periodName}' overlaps with existing period";
                    continue;
                }

                $period = $this->create([
                    'period_name' => $periodName,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'status' => 'open',
                ]);

                $periods[] = $period;

            } catch (\Exception $e) {
                $errors[] = "Failed to create period '{$periodName}': " . $e->getMessage();
            }
        }

        return [
            'periods' => $periods,
            'created_count' => count($periods),
            'errors' => $errors,
        ];
    }

    /**
     * Generate quarterly periods for a year
     */
    public function generateQuarterlyPeriods(int $year): array
    {
        $periods = [];
        $errors = [];
        $quarters = [
            ['start' => 1, 'end' => 3, 'name' => 'Q1'],
            ['start' => 4, 'end' => 6, 'name' => 'Q2'],
            ['start' => 7, 'end' => 9, 'name' => 'Q3'],
            ['start' => 10, 'end' => 12, 'name' => 'Q4'],
        ];

        foreach ($quarters as $quarter) {
            $startDate = Carbon::create($year, $quarter['start'], 1)->toDateString();
            $endDate = Carbon::create($year, $quarter['end'], 1)->endOfMonth()->toDateString();
            $periodName = "{$quarter['name']} {$year}";

            try {
                // Check if period already exists
                if ($this->model->where('period_name', $periodName)->exists()) {
                    $errors[] = "Period '{$periodName}' already exists";
                    continue;
                }

                // Check for overlapping periods
                if ($this->hasOverlappingPeriods($startDate, $endDate)) {
                    $errors[] = "Period '{$periodName}' overlaps with existing period";
                    continue;
                }

                $period = $this->create([
                    'period_name' => $periodName,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'status' => 'open',
                ]);

                $periods[] = $period;

            } catch (\Exception $e) {
                $errors[] = "Failed to create period '{$periodName}': " . $e->getMessage();
            }
        }

        return [
            'periods' => $periods,
            'created_count' => count($periods),
            'errors' => $errors,
        ];
    }

    /**
     * Get period start date
     */
    private function getPeriodStartDate(string $period): string
    {
        switch ($period) {
            case 'week':
                return now()->subWeek()->toDateString();
            case 'month':
                return now()->subMonth()->toDateString();
            case 'quarter':
                return now()->subQuarter()->toDateString();
            case 'year':
                return now()->subYear()->toDateString();
            default:
                return now()->subYear()->toDateString();
        }
    }

    /**
     * Get date format for grouping
     */
    private function getDateFormat(string $period): string
    {
        switch ($period) {
            case 'week':
                return 'Y-W';
            case 'month':
                return 'Y-m';
            case 'quarter':
                return 'Y-Q';
            case 'year':
                return 'Y';
            default:
                return 'Y';
        }
    }

    /**
     * Get financial period dashboard data
     */
    public function getDashboardData(): array
    {
        $total = $this->model->count();
        $open = $this->model->where('status', 'open')->count();
        $closed = $this->model->where('status', 'closed')->count();
        $locked = $this->model->where('status', 'locked')->count();

        $currentPeriod = $this->getCurrent();
        $recentlyCreated = $this->model->where('created_at', '>=', now()->subDays(30))->count();

        return [
            'total_periods' => $total,
            'open_periods' => $open,
            'closed_periods' => $closed,
            'locked_periods' => $locked,
            'current_period' => $currentPeriod,
            'recently_created' => $recentlyCreated,
        ];
    }

    /**
     * Get upcoming periods
     */
    public function getUpcoming(int $limit = 5): Collection
    {
        $today = Carbon::now()->toDateString();
        
        return $this->model->where('start_date', '>', $today)
            ->where('status', 'open')
            ->orderBy('start_date', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent periods
     */
    public function getRecent(int $limit = 5): Collection
    {
        return $this->model->orderBy('start_date', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get periods by year
     */
    public function getByYear(int $year): Collection
    {
        return $this->model->whereYear('start_date', $year)
            ->orderBy('start_date', 'asc')
            ->get();
    }

    /**
     * Get period duration in days
     */
    public function getPeriodDuration(int $id): int
    {
        $period = $this->getById($id);
        if (!$period) {
            return 0;
        }

        $startDate = Carbon::parse($period->start_date);
        $endDate = Carbon::parse($period->end_date);
        
        return $startDate->diffInDays($endDate) + 1;
    }

    /**
     * Get period progress percentage
     */
    public function getPeriodProgress(int $id): float
    {
        $period = $this->getById($id);
        if (!$period) {
            return 0;
        }

        $startDate = Carbon::parse($period->start_date);
        $endDate = Carbon::parse($period->end_date);
        $today = Carbon::now();

        if ($today->lt($startDate)) {
            return 0;
        }

        if ($today->gt($endDate)) {
            return 100;
        }

        $totalDays = $startDate->diffInDays($endDate) + 1;
        $passedDays = $startDate->diffInDays($today) + 1;

        return ($passedDays / $totalDays) * 100;
    }
}
