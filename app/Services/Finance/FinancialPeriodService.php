<?php

namespace App\Services\Finance;

use App\Models\Finance\FinancialPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FinancialPeriodService
{
    /**
     * Create financial period
     */
    public function createFinancialPeriod(array $data): array
    {
        try {
            DB::beginTransaction();

            // Validate financial period data
            $this->validateFinancialPeriodData($data);

            // Check for overlapping periods
            $this->checkForOverlappingPeriods($data['start_date'], $data['end_date']);

            $financialPeriod = FinancialPeriod::create([
                'period_name' => $data['period_name'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'status' => $data['status'] ?? 'open',
            ]);

            DB::commit();

            return [
                'success' => true,
                'financial_period' => $financialPeriod,
                'message' => 'Financial period created successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Financial period creation failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to create financial period: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update financial period
     */
    public function updateFinancialPeriod(int $financialPeriodId, array $data): array
    {
        try {
            DB::beginTransaction();

            $financialPeriod = FinancialPeriod::findOrFail($financialPeriodId);
            
            // Validate financial period data
            $this->validateFinancialPeriodData($data);

            // Check for overlapping periods (excluding current period)
            $this->checkForOverlappingPeriods($data['start_date'], $data['end_date'], $financialPeriodId);

            $financialPeriod->update($data);

            DB::commit();

            return [
                'success' => true,
                'financial_period' => $financialPeriod,
                'message' => 'Financial period updated successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Financial period update failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to update financial period: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Close financial period
     */
    public function closeFinancialPeriod(int $financialPeriodId): array
    {
        try {
            DB::beginTransaction();

            $financialPeriod = FinancialPeriod::findOrFail($financialPeriodId);
            
            if ($financialPeriod->status !== 'open') {
                throw new \Exception('Only open periods can be closed');
            }

            // Check if there are any open periods after this one
            $hasOpenPeriodsAfter = FinancialPeriod::where('start_date', '>', $financialPeriod->end_date)
                ->where('status', 'open')
                ->exists();

            if ($hasOpenPeriodsAfter) {
                throw new \Exception('Cannot close period when there are open periods after it');
            }

            $financialPeriod->update(['status' => 'closed']);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Financial period closed successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Financial period closure failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to close financial period: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Lock financial period
     */
    public function lockFinancialPeriod(int $financialPeriodId): array
    {
        try {
            DB::beginTransaction();

            $financialPeriod = FinancialPeriod::findOrFail($financialPeriodId);
            
            if ($financialPeriod->status !== 'closed') {
                throw new \Exception('Only closed periods can be locked');
            }

            $financialPeriod->update(['status' => 'locked']);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Financial period locked successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Financial period lock failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to lock financial period: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Reopen financial period
     */
    public function reopenFinancialPeriod(int $financialPeriodId): array
    {
        try {
            DB::beginTransaction();

            $financialPeriod = FinancialPeriod::findOrFail($financialPeriodId);
            
            if ($financialPeriod->status === 'locked') {
                throw new \Exception('Locked periods cannot be reopened');
            }

            // Check if there are any open periods after this one
            $hasOpenPeriodsAfter = FinancialPeriod::where('start_date', '>', $financialPeriod->end_date)
                ->where('status', 'open')
                ->exists();

            if ($hasOpenPeriodsAfter) {
                throw new \Exception('Cannot reopen period when there are open periods after it');
            }

            $financialPeriod->update(['status' => 'open']);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Financial period reopened successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Financial period reopen failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to reopen financial period: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get current financial period
     */
    public function getCurrentFinancialPeriod(): ?FinancialPeriod
    {
        $today = Carbon::now()->toDateString();
        
        return FinancialPeriod::where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->where('status', 'open')
            ->first();
    }

    /**
     * Get financial period for date
     */
    public function getFinancialPeriodForDate(string $date): ?FinancialPeriod
    {
        return FinancialPeriod::where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();
    }

    /**
     * Generate monthly periods for a year
     */
    public function generateMonthlyPeriods(int $year): array
    {
        try {
            DB::beginTransaction();

            $periods = [];
            $errors = [];

            for ($month = 1; $month <= 12; $month++) {
                $startDate = Carbon::create($year, $month, 1)->toDateString();
                $endDate = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();
                $periodName = Carbon::create($year, $month, 1)->format('F Y');

                try {
                    // Check if period already exists
                    if (FinancialPeriod::where('period_name', $periodName)->exists()) {
                        $errors[] = "Period '{$periodName}' already exists";
                        continue;
                    }

                    // Check for overlapping periods
                    $this->checkForOverlappingPeriods($startDate, $endDate);

                    $period = FinancialPeriod::create([
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

            DB::commit();

            return [
                'success' => true,
                'periods' => $periods,
                'created_count' => count($periods),
                'errors' => $errors,
                'message' => "Successfully created " . count($periods) . " monthly periods for {$year}"
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Monthly periods generation failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to generate monthly periods: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate quarterly periods for a year
     */
    public function generateQuarterlyPeriods(int $year): array
    {
        try {
            DB::beginTransaction();

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
                    if (FinancialPeriod::where('period_name', $periodName)->exists()) {
                        $errors[] = "Period '{$periodName}' already exists";
                        continue;
                    }

                    // Check for overlapping periods
                    $this->checkForOverlappingPeriods($startDate, $endDate);

                    $period = FinancialPeriod::create([
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

            DB::commit();

            return [
                'success' => true,
                'periods' => $periods,
                'created_count' => count($periods),
                'errors' => $errors,
                'message' => "Successfully created " . count($periods) . " quarterly periods for {$year}"
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Quarterly periods generation failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to generate quarterly periods: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get financial period statistics
     */
    public function getFinancialPeriodStatistics(): array
    {
        $total = FinancialPeriod::count();
        $open = FinancialPeriod::where('status', 'open')->count();
        $closed = FinancialPeriod::where('status', 'closed')->count();
        $locked = FinancialPeriod::where('status', 'locked')->count();

        $currentPeriod = $this->getCurrentFinancialPeriod();

        return [
            'total_periods' => $total,
            'open_periods' => $open,
            'closed_periods' => $closed,
            'locked_periods' => $locked,
            'current_period' => $currentPeriod,
        ];
    }

    /**
     * Validate financial period data
     */
    private function validateFinancialPeriodData(array $data): void
    {
        if (empty($data['period_name'])) {
            throw new \Exception('Period name is required');
        }

        if (empty($data['start_date'])) {
            throw new \Exception('Start date is required');
        }

        if (empty($data['end_date'])) {
            throw new \Exception('End date is required');
        }

        if (Carbon::parse($data['start_date'])->gte(Carbon::parse($data['end_date']))) {
            throw new \Exception('Start date must be before end date');
        }

        if (isset($data['status']) && !in_array($data['status'], ['open', 'closed', 'locked'])) {
            throw new \Exception('Invalid status. Must be open, closed, or locked');
        }
    }

    /**
     * Check for overlapping periods
     */
    private function checkForOverlappingPeriods(string $startDate, string $endDate, int $excludeId = null): void
    {
        $query = FinancialPeriod::where(function ($q) use ($startDate, $endDate) {
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

        if ($query->exists()) {
            throw new \Exception('Period overlaps with existing financial period');
        }
    }

    /**
     * Get financial periods for dropdown/select
     */
    public function getFinancialPeriodsForSelect(): array
    {
        return FinancialPeriod::orderBy('start_date', 'desc')
            ->pluck('period_name', 'id')
            ->toArray();
    }

    /**
     * Get open financial periods
     */
    public function getOpenFinancialPeriods(): array
    {
        $periods = FinancialPeriod::where('status', 'open')
            ->orderBy('start_date')
            ->get();

        return [
            'success' => true,
            'periods' => $periods
        ];
    }
}
