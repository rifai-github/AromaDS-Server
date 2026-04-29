<?php

namespace App\Services\Finance;

use App\Models\Finance\CostCenter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CostCenterService
{
    /**
     * Create cost center
     */
    public function createCostCenter(array $data): array
    {
        try {
            DB::beginTransaction();

            // Validate cost center data
            $this->validateCostCenterData($data);

            // Check if cost center code already exists
            if (CostCenter::where('code', $data['code'])->exists()) {
                throw new \Exception('Cost center code already exists');
            }

            $costCenter = CostCenter::create([
                'code' => strtoupper($data['code']),
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'is_active' => $data['is_active'] ?? true,
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            return [
                'success' => true,
                'cost_center' => $costCenter,
                'message' => 'Cost center created successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Cost center creation failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to create cost center: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update cost center
     */
    public function updateCostCenter(int $costCenterId, array $data): array
    {
        try {
            DB::beginTransaction();

            $costCenter = CostCenter::findOrFail($costCenterId);
            
            // Validate cost center data
            $this->validateCostCenterData($data);

            // Check if cost center code already exists (excluding current record)
            if (CostCenter::where('code', $data['code'])
                ->where('id', '!=', $costCenterId)
                ->exists()) {
                throw new \Exception('Cost center code already exists');
            }

            $costCenter->update(array_merge($data, [
                'code' => strtoupper($data['code']),
                'updated_by' => auth()->id(),
            ]));

            DB::commit();

            return [
                'success' => true,
                'cost_center' => $costCenter,
                'message' => 'Cost center updated successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Cost center update failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to update cost center: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Activate cost center
     */
    public function activateCostCenter(int $costCenterId): array
    {
        try {
            $costCenter = CostCenter::findOrFail($costCenterId);
            
            $costCenter->update([
                'is_active' => true,
                'updated_by' => auth()->id(),
            ]);

            return [
                'success' => true,
                'message' => 'Cost center activated successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Cost center activation failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to activate cost center: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Deactivate cost center
     */
    public function deactivateCostCenter(int $costCenterId): array
    {
        try {
            $costCenter = CostCenter::findOrFail($costCenterId);
            
            $costCenter->update([
                'is_active' => false,
                'updated_by' => auth()->id(),
            ]);

            return [
                'success' => true,
                'message' => 'Cost center deactivated successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Cost center deactivation failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to deactivate cost center: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Bulk activate cost centers
     */
    public function bulkActivateCostCenters(array $costCenterIds): array
    {
        try {
            DB::beginTransaction();

            $updated = CostCenter::whereIn('id', $costCenterIds)
                ->update([
                    'is_active' => true,
                    'updated_by' => auth()->id(),
                ]);

            DB::commit();

            return [
                'success' => true,
                'updated_count' => $updated,
                'message' => "Successfully activated {$updated} cost centers"
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk cost center activation failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to activate cost centers: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Bulk deactivate cost centers
     */
    public function bulkDeactivateCostCenters(array $costCenterIds): array
    {
        try {
            DB::beginTransaction();

            $updated = CostCenter::whereIn('id', $costCenterIds)
                ->update([
                    'is_active' => false,
                    'updated_by' => auth()->id(),
                ]);

            DB::commit();

            return [
                'success' => true,
                'updated_count' => $updated,
                'message' => "Successfully deactivated {$updated} cost centers"
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk cost center deactivation failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to deactivate cost centers: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get active cost centers
     */
    public function getActiveCostCenters(): array
    {
        $costCenters = CostCenter::where('is_active', true)
            ->orderBy('code')
            ->get();

        return [
            'success' => true,
            'cost_centers' => $costCenters
        ];
    }

    /**
     * Get cost center by code
     */
    public function getCostCenterByCode(string $code): ?CostCenter
    {
        return CostCenter::where('code', strtoupper($code))->first();
    }

    /**
     * Check if cost center is active
     */
    public function isCostCenterActive(string $code): bool
    {
        $costCenter = $this->getCostCenterByCode($code);
        return $costCenter ? $costCenter->is_active : false;
    }

    /**
     * Get cost center statistics
     */
    public function getCostCenterStatistics(): array
    {
        $total = CostCenter::count();
        $active = CostCenter::where('is_active', true)->count();
        $inactive = CostCenter::where('is_active', false)->count();

        return [
            'total_cost_centers' => $total,
            'active_cost_centers' => $active,
            'inactive_cost_centers' => $inactive,
            'activation_rate' => $total > 0 ? ($active / $total) * 100 : 0,
        ];
    }

    /**
     * Validate cost center data
     */
    private function validateCostCenterData(array $data): void
    {
        if (empty($data['code'])) {
            throw new \Exception('Cost center code is required');
        }

        if (strlen($data['code']) < 2 || strlen($data['code']) > 10) {
            throw new \Exception('Cost center code must be between 2 and 10 characters');
        }

        if (!preg_match('/^[A-Z0-9_]+$/', strtoupper($data['code']))) {
            throw new \Exception('Cost center code can only contain uppercase letters, numbers, and underscores');
        }

        if (empty($data['name'])) {
            throw new \Exception('Cost center name is required');
        }

        if (strlen($data['name']) > 255) {
            throw new \Exception('Cost center name cannot exceed 255 characters');
        }
    }

    /**
     * Get cost centers for dropdown/select
     */
    public function getCostCentersForSelect(): array
    {
        return CostCenter::where('is_active', true)
            ->orderBy('code')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Search cost centers
     */
    public function searchCostCenters(string $query, int $limit = 10): array
    {
        $costCenters = CostCenter::where(function ($q) use ($query) {
                $q->where('code', 'like', "%{$query}%")
                  ->orWhere('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->where('is_active', true)
            ->orderBy('code')
            ->limit($limit)
            ->get();

        return [
            'success' => true,
            'cost_centers' => $costCenters
        ];
    }

    /**
     * Import cost centers from array
     */
    public function importCostCenters(array $costCentersData): array
    {
        try {
            DB::beginTransaction();

            $imported = 0;
            $errors = [];

            foreach ($costCentersData as $index => $data) {
                try {
                    $this->validateCostCenterData($data);
                    
                    if (!CostCenter::where('code', strtoupper($data['code']))->exists()) {
                        CostCenter::create([
                            'code' => strtoupper($data['code']),
                            'name' => $data['name'],
                            'description' => $data['description'] ?? '',
                            'is_active' => $data['is_active'] ?? true,
                            'created_by' => auth()->id(),
                        ]);
                        $imported++;
                    } else {
                        $errors[] = "Row " . ($index + 1) . ": Cost center code '{$data['code']}' already exists";
                    }
                } catch (\Exception $e) {
                    $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
                }
            }

            DB::commit();

            return [
                'success' => true,
                'imported_count' => $imported,
                'errors' => $errors,
                'message' => "Successfully imported {$imported} cost centers"
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Cost center import failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to import cost centers: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get cost center hierarchy (if implemented)
     */
    public function getCostCenterHierarchy(): array
    {
        // This can be extended to support hierarchical cost centers
        // For now, return flat structure
        $costCenters = CostCenter::where('is_active', true)
            ->orderBy('code')
            ->get();

        return [
            'success' => true,
            'cost_centers' => $costCenters
        ];
    }

    /**
     * Get cost center usage statistics
     */
    public function getCostCenterUsageStatistics(int $costCenterId): array
    {
        $costCenter = CostCenter::findOrFail($costCenterId);
        
        // This would typically involve counting related records
        // For now, return basic information
        return [
            'cost_center' => $costCenter,
            'usage_count' => 0, // Would be calculated based on actual usage
            'last_used' => null, // Would be calculated based on actual usage
        ];
    }
}
