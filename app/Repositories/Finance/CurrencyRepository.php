<?php

namespace App\Repositories\Finance;

use App\Models\Finance\Currency;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class CurrencyRepository
{
    protected $model;

    public function __construct(Currency $model)
    {
        $this->model = $model;
    }

    /**
     * Get all currencies with pagination
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        // Apply filters
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('symbol', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('code')->paginate($perPage);
    }

    /**
     * Get currency by ID
     */
    public function getById(int $id): ?Currency
    {
        return $this->model->find($id);
    }

    /**
     * Create new currency
     */
    public function create(array $data): Currency
    {
        return $this->model->create($data);
    }

    /**
     * Update currency
     */
    public function update(int $id, array $data): bool
    {
        $currency = $this->model->find($id);
        if (!$currency) {
            return false;
        }

        return $currency->update($data);
    }

    /**
     * Delete currency
     */
    public function delete(int $id): bool
    {
        $currency = $this->model->find($id);
        if (!$currency) {
            return false;
        }

        return $currency->delete();
    }

    /**
     * Get currency by code
     */
    public function getByCode(string $code): ?Currency
    {
        return $this->model->where('code', strtoupper($code))->first();
    }

    /**
     * Get active currencies
     */
    public function getActive(): Collection
    {
        return $this->model->where('is_active', true)
            ->orderBy('code')
            ->get();
    }

    /**
     * Get inactive currencies
     */
    public function getInactive(): Collection
    {
        return $this->model->where('is_active', false)
            ->orderBy('code')
            ->get();
    }

    /**
     * Get currency statistics
     */
    public function getStatistics(): array
    {
        $total = $this->model->count();
        $active = $this->model->where('is_active', true)->count();
        $inactive = $this->model->where('is_active', false)->count();

        return [
            'total_currencies' => $total,
            'active_currencies' => $active,
            'inactive_currencies' => $inactive,
            'activation_rate' => $total > 0 ? ($active / $total) * 100 : 0,
        ];
    }

    /**
     * Search currencies
     */
    public function search(string $query, int $limit = 20): Collection
    {
        return $this->model->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('code', 'like', "%{$query}%")
                  ->orWhere('symbol', 'like', "%{$query}%");
            })
            ->orderBy('code')
            ->limit($limit)
            ->get();
    }

    /**
     * Get currencies for dropdown/select
     */
    public function getForSelect(): array
    {
        return $this->model->where('is_active', true)
            ->orderBy('code')
            ->pluck('name', 'code')
            ->toArray();
    }

    /**
     * Get currencies for dropdown/select by ID
     */
    public function getForSelectById(): array
    {
        return $this->model->where('is_active', true)
            ->orderBy('code')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Check if currency code exists
     */
    public function codeExists(string $code, int $excludeId = null): bool
    {
        $query = $this->model->where('code', strtoupper($code));
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * Bulk update currency status
     */
    public function bulkUpdateStatus(array $ids, bool $isActive): int
    {
        return $this->model->whereIn('id', $ids)->update(['is_active' => $isActive]);
    }

    /**
     * Update exchange rates
     */
    public function updateExchangeRates(array $rates): int
    {
        $updated = 0;
        
        foreach ($rates as $code => $rate) {
            $currency = $this->getByCode($code);
            if ($currency && $rate > 0) {
                $currency->update(['exchange_rate' => $rate]);
                $updated++;
            }
        }
        
        return $updated;
    }

    /**
     * Get currencies for export
     */
    public function getForExport(array $filters = []): Collection
    {
        $query = $this->model->newQuery();

        // Apply filters
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('code')->get();
    }

    /**
     * Get currency trends
     */
    public function getTrends(string $period = 'month'): array
    {
        $startDate = $this->getPeriodStartDate($period);
        
        $currencies = $this->model->where('created_at', '>=', $startDate)
            ->get()
            ->groupBy(function ($currency) use ($period) {
                return $currency->created_at->format($this->getDateFormat($period));
            });

        $trends = [];
        foreach ($currencies as $date => $group) {
            $trends[] = [
                'date' => $date,
                'total_currencies' => $group->count(),
                'active_count' => $group->where('is_active', true)->count(),
                'inactive_count' => $group->where('is_active', false)->count(),
            ];
        }

        return $trends;
    }

    /**
     * Activate currency
     */
    public function activate(int $id): bool
    {
        $currency = $this->model->find($id);
        if (!$currency) {
            return false;
        }

        return $currency->update(['is_active' => true]);
    }

    /**
     * Deactivate currency
     */
    public function deactivate(int $id): bool
    {
        $currency = $this->model->find($id);
        if (!$currency) {
            return false;
        }

        return $currency->update(['is_active' => false]);
    }

    /**
     * Get currencies by status
     */
    public function getByStatus(bool $isActive): Collection
    {
        return $this->model->where('is_active', $isActive)
            ->orderBy('code')
            ->get();
    }

    /**
     * Get base currency (USD)
     */
    public function getBaseCurrency(): ?Currency
    {
        return $this->model->where('code', 'USD')->first();
    }

    /**
     * Get currency exchange rate
     */
    public function getExchangeRate(string $code): float
    {
        $currency = $this->getByCode($code);
        return $currency ? $currency->exchange_rate : 0;
    }

    /**
     * Convert amount between currencies
     */
    public function convertAmount(float $amount, string $fromCode, string $toCode): float
    {
        if ($fromCode === $toCode) {
            return $amount;
        }

        $fromCurrency = $this->getByCode($fromCode);
        $toCurrency = $this->getByCode($toCode);

        if (!$fromCurrency || !$toCurrency) {
            return 0;
        }

        // Convert to base currency (USD) first, then to target currency
        $baseAmount = $amount / $fromCurrency->exchange_rate;
        return $baseAmount * $toCurrency->exchange_rate;
    }

    /**
     * Get most used currencies
     */
    public function getMostUsed(int $limit = 10): Collection
    {
        // This would typically join with related tables to get usage count
        // For now, return active currencies ordered by code
        return $this->model->where('is_active', true)
            ->orderBy('code')
            ->limit($limit)
            ->get();
    }

    /**
     * Import currencies
     */
    public function import(array $currenciesData): array
    {
        $imported = 0;
        $errors = [];

        foreach ($currenciesData as $index => $data) {
            try {
                if (!$this->codeExists($data['code'])) {
                    $this->create([
                        'code' => strtoupper($data['code']),
                        'name' => $data['name'],
                        'symbol' => $data['symbol'] ?? '',
                        'exchange_rate' => $data['exchange_rate'] ?? 1.0000,
                        'is_active' => $data['is_active'] ?? true,
                    ]);
                    $imported++;
                } else {
                    $errors[] = "Row " . ($index + 1) . ": Currency code '{$data['code']}' already exists";
                }
            } catch (\Exception $e) {
                $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
            }
        }

        return [
            'imported_count' => $imported,
            'errors' => $errors,
        ];
    }

    /**
     * Get currency dashboard data
     */
    public function getDashboardData(): array
    {
        $total = $this->model->count();
        $active = $this->model->where('is_active', true)->count();
        $inactive = $this->model->where('is_active', false)->count();

        $recentlyCreated = $this->model->where('created_at', '>=', now()->subDays(30))->count();
        $recentlyUpdated = $this->model->where('updated_at', '>=', now()->subDays(30))->count();

        $baseCurrency = $this->getBaseCurrency();

        return [
            'total_currencies' => $total,
            'active_currencies' => $active,
            'inactive_currencies' => $inactive,
            'recently_created' => $recentlyCreated,
            'recently_updated' => $recentlyUpdated,
            'activation_rate' => $total > 0 ? ($active / $total) * 100 : 0,
            'base_currency' => $baseCurrency,
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
                return now()->subMonth()->toDateString();
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
                return 'Y-m';
        }
    }

    /**
     * Get currency exchange rate history (placeholder for future implementation)
     */
    public function getExchangeRateHistory(string $code, int $days = 30): array
    {
        // This would typically fetch from an exchange rate history table
        // For now, return empty array as placeholder
        return [];
    }

    /**
     * Get currency pairs for conversion
     */
    public function getCurrencyPairs(): array
    {
        $currencies = $this->getActive();
        $pairs = [];

        foreach ($currencies as $from) {
            foreach ($currencies as $to) {
                if ($from->code !== $to->code) {
                    $pairs[] = [
                        'from' => $from->code,
                        'to' => $to->code,
                        'from_name' => $from->name,
                        'to_name' => $to->name,
                        'rate' => $this->convertAmount(1, $from->code, $to->code),
                    ];
                }
            }
        }

        return $pairs;
    }
}
