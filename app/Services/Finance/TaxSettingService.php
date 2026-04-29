<?php

namespace App\Services\Finance;

use App\Models\Finance\TaxSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaxSettingService
{
    /**
     * Get tax settings with filters
     */
    public function getTaxSettings(array $filters = [])
    {
        $query = TaxSetting::with(['createdBy', 'updatedBy']);

        // Apply search filter
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Apply tax type filter
        if (!empty($filters['tax_type'])) {
            $query->byType($filters['tax_type']);
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply sorting
        $sortField = $filters['sort'] ?? 'created_at';
        $sortDirection = 'desc';
        
        if (in_array($sortField, ['name', 'tax_code', 'tax_type'])) {
            $sortDirection = 'asc';
        }
        
        $query->orderBy($sortField, $sortDirection);

        return $query->paginate(15);
    }

    /**
     * Create a new tax setting
     */
    public function createTaxSetting(array $data): TaxSetting
    {
        DB::beginTransaction();
        
        try {
            // Generate tax code if not provided
            if (empty($data['tax_code'])) {
                $data['tax_code'] = TaxSetting::generateTaxCode($data['name']);
            }

            // Set audit fields
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            $taxSetting = TaxSetting::create($data);

            DB::commit();
            return $taxSetting;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Update an existing tax setting
     */
    public function updateTaxSetting(TaxSetting $taxSetting, array $data): TaxSetting
    {
        DB::beginTransaction();
        
        try {
            // Set audit fields
            $data['updated_by'] = Auth::id();

            $taxSetting->update($data);

            DB::commit();
            return $taxSetting->fresh();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Delete a tax setting
     */
    public function deleteTaxSetting(TaxSetting $taxSetting): bool
    {
        DB::beginTransaction();
        
        try {
            // Check if tax setting can be deleted
            if (!$taxSetting->canBeDeleted()) {
                throw new \Exception('Tax setting cannot be deleted as it is being used in transactions.');
            }

            $taxSetting->delete();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Activate a tax setting
     */
    public function activateTaxSetting(TaxSetting $taxSetting): TaxSetting
    {
        if (!$taxSetting->canBeActivated()) {
            throw new \Exception('Tax setting is already active.');
        }

        $taxSetting->update([
            'status' => 'active',
            'updated_by' => Auth::id(),
        ]);

        return $taxSetting->fresh();
    }

    /**
     * Deactivate a tax setting
     */
    public function deactivateTaxSetting(TaxSetting $taxSetting): TaxSetting
    {
        if (!$taxSetting->canBeDeactivated()) {
            throw new \Exception('Tax setting is already inactive.');
        }

        $taxSetting->update([
            'status' => 'inactive',
            'updated_by' => Auth::id(),
        ]);

        return $taxSetting->fresh();
    }

    /**
     * Bulk activate tax settings
     */
    public function bulkActivateTaxSettings(array $taxSettingIds): int
    {
        $count = 0;
        
        DB::beginTransaction();
        
        try {
            foreach ($taxSettingIds as $id) {
                $taxSetting = TaxSetting::find($id);
                if ($taxSetting && $taxSetting->canBeActivated()) {
                    $taxSetting->update([
                        'status' => 'active',
                        'updated_by' => Auth::id(),
                    ]);
                    $count++;
                }
            }

            DB::commit();
            return $count;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Bulk deactivate tax settings
     */
    public function bulkDeactivateTaxSettings(array $taxSettingIds): int
    {
        $count = 0;
        
        DB::beginTransaction();
        
        try {
            foreach ($taxSettingIds as $id) {
                $taxSetting = TaxSetting::find($id);
                if ($taxSetting && $taxSetting->canBeDeactivated()) {
                    $taxSetting->update([
                        'status' => 'inactive',
                        'updated_by' => Auth::id(),
                    ]);
                    $count++;
                }
            }

            DB::commit();
            return $count;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Bulk delete tax settings
     */
    public function bulkDeleteTaxSettings(array $taxSettingIds): int
    {
        $count = 0;
        
        DB::beginTransaction();
        
        try {
            foreach ($taxSettingIds as $id) {
                $taxSetting = TaxSetting::find($id);
                if ($taxSetting && $taxSetting->canBeDeleted()) {
                    $taxSetting->delete();
                    $count++;
                }
            }

            DB::commit();
            return $count;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Export tax settings to CSV
     */
    public function exportToCsv(array $filters = []): StreamedResponse
    {
        $taxSettings = $this->getTaxSettings($filters);

        $filename = 'tax_settings_' . date('Y-m-d_H-i-s') . '.csv';

        return response()->stream(function () use ($taxSettings) {
            $file = fopen('php://output', 'w');

            // Add headers
            fputcsv($file, [
                'ID', 'Name', 'Tax Code', 'Tax Type', 'Tax Rate (%)', 'Status',
                'Effective Date', 'End Date', 'Compound Tax', 'Calculation Method',
                'Rounding Method', 'Decimal Places', 'Minimum Amount', 'Maximum Amount',
                'Description', 'Notes', 'Created By', 'Created At', 'Updated By', 'Updated At'
            ]);

            // Add data
            foreach ($taxSettings as $taxSetting) {
                fputcsv($file, [
                    $taxSetting->id,
                    $taxSetting->name,
                    $taxSetting->tax_code,
                    $taxSetting->tax_type_label,
                    $taxSetting->tax_rate,
                    ucfirst($taxSetting->status),
                    $taxSetting->effective_date ? $taxSetting->effective_date->format('Y-m-d') : '',
                    $taxSetting->end_date ? $taxSetting->end_date->format('Y-m-d') : '',
                    $taxSetting->is_compound ? 'Yes' : 'No',
                    $taxSetting->calculation_method_label,
                    $taxSetting->rounding_method_label,
                    $taxSetting->decimal_places,
                    $taxSetting->minimum_amount,
                    $taxSetting->maximum_amount,
                    $taxSetting->description,
                    $taxSetting->notes,
                    $taxSetting->createdBy->name ?? 'System',
                    $taxSetting->created_at->format('Y-m-d H:i:s'),
                    $taxSetting->updatedBy->name ?? 'System',
                    $taxSetting->updated_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Get tax settings statistics
     */
    public function getStatistics(): array
    {
        $total = TaxSetting::count();
        $active = TaxSetting::where('status', 'active')->count();
        $inactive = TaxSetting::where('status', 'inactive')->count();
        $expired = TaxSetting::where('end_date', '<', Carbon::now())->count();
        $future = TaxSetting::where('effective_date', '>', Carbon::now())->count();

        $byType = TaxSetting::selectRaw('tax_type, COUNT(*) as count')
            ->groupBy('tax_type')
            ->pluck('count', 'tax_type')
            ->toArray();

        $recentlyCreated = TaxSetting::where('created_at', '>=', Carbon::now()->subDays(30))->count();
        $recentlyUpdated = TaxSetting::where('updated_at', '>=', Carbon::now()->subDays(30))->count();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'expired' => $expired,
            'future' => $future,
            'by_type' => $byType,
            'recently_created' => $recentlyCreated,
            'recently_updated' => $recentlyUpdated,
        ];
    }

    /**
     * Get tax settings trends
     */
    public function getTrends(int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);
        
        $createdTrend = TaxSetting::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        $updatedTrend = TaxSetting::selectRaw('DATE(updated_at) as date, COUNT(*) as count')
            ->where('updated_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        return [
            'created' => $createdTrend,
            'updated' => $updatedTrend,
        ];
    }

    /**
     * Get summary by tax type
     */
    public function getSummaryByType(): array
    {
        return TaxSetting::selectRaw('tax_type, COUNT(*) as count, AVG(tax_rate) as avg_rate')
            ->groupBy('tax_type')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->tax_type => [
                    'count' => $item->count,
                    'avg_rate' => round($item->avg_rate, 2),
                ]];
            })
            ->toArray();
    }

    /**
     * Get summary by status
     */
    public function getSummaryByStatus(): array
    {
        return TaxSetting::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    /**
     * Get effective tax settings for a specific date
     */
    public function getEffectiveTaxSettings(string $date = null): \Illuminate\Database\Eloquent\Collection
    {
        $date = $date ? Carbon::parse($date) : Carbon::now();
        
        return TaxSetting::effective($date)->get();
    }

    /**
     * Calculate tax for a given amount using the appropriate tax setting
     */
    public function calculateTax(float $amount, string $taxType, string $date = null): float
    {
        $date = $date ? Carbon::parse($date) : Carbon::now();
        
        $taxSetting = TaxSetting::effective($date)
            ->byType($taxType)
            ->first();

        if (!$taxSetting) {
            return 0;
        }

        return $taxSetting->calculateTax($amount);
    }

    /**
     * Get tax settings for API
     */
    public function getTaxSettingsForApi(array $filters = [])
    {
        return $this->getTaxSettings($filters);
    }
}
