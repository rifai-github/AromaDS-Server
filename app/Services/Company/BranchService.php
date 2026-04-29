<?php

namespace App\Services\Company;

use App\Models\Branch;
use App\Models\BranchSetting;
use App\Models\BranchWarehouse;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class BranchService
{
    protected $branchRepository;

    public function __construct(BranchRepository $branchRepository)
    {
        $this->branchRepository = $branchRepository;
    }

    /**
     * Create a new branch
     */
    public function createBranch(array $data): Branch
    {
        return DB::transaction(function () use ($data) {
            $branch = Branch::create([
                'company_id' => $data['company_id'],
                'branch_code' => $data['branch_code'],
                'branch_name' => $data['branch_name'],
                'branch_type' => $data['branch_type'] ?? 'main',
                'contact_person' => $data['contact_person'] ?? null,
                'email' => $data['email'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'province_id' => $data['province_id'],
                'city_id' => $data['city_id'],
                'district_id' => $data['district_id'] ?? null,
                'subdistrict_id' => $data['subdistrict_id'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'opening_hours' => $data['opening_hours'] ?? null,
                'closing_hours' => $data['closing_hours'] ?? null,
                'is_24_hours' => $data['is_24_hours'] ?? false,
                'status' => $data['status'] ?? 'active',
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id()
            ]);

            // Create default branch settings
            $this->createDefaultSettings($branch);

            return $branch;
        });
    }

    /**
     * Update branch information
     */
    public function updateBranch(Branch $branch, array $data): Branch
    {
        return DB::transaction(function () use ($branch, $data) {
            $branch->update([
                'branch_code' => $data['branch_code'],
                'branch_name' => $data['branch_name'],
                'branch_type' => $data['branch_type'] ?? $branch->branch_type,
                'contact_person' => $data['contact_person'] ?? $branch->contact_person,
                'email' => $data['email'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'province_id' => $data['province_id'],
                'city_id' => $data['city_id'],
                'district_id' => $data['district_id'] ?? $branch->district_id,
                'subdistrict_id' => $data['subdistrict_id'] ?? $branch->subdistrict_id,
                'postal_code' => $data['postal_code'] ?? $branch->postal_code,
                'latitude' => $data['latitude'] ?? $branch->latitude,
                'longitude' => $data['longitude'] ?? $branch->longitude,
                'opening_hours' => $data['opening_hours'] ?? $branch->opening_hours,
                'closing_hours' => $data['closing_hours'] ?? $branch->closing_hours,
                'is_24_hours' => $data['is_24_hours'] ?? $branch->is_24_hours,
                'status' => $data['status'] ?? $branch->status,
                'notes' => $data['notes'] ?? $branch->notes,
                'updated_by' => Auth::id()
            ]);

            return $branch;
        });
    }

    /**
     * Delete branch
     */
    public function deleteBranch(Branch $branch): bool
    {
        return DB::transaction(function () use ($branch) {
            // Check if branch can be deleted
            $this->validateBranchDeletion($branch);

            // Delete related data
            $branch->settings()->delete();
            $branch->warehouses()->delete();

            // Delete branch
            return $branch->delete();
        });
    }

    /**
     * Create default branch settings
     */
    public function createDefaultSettings(Branch $branch): BranchSetting
    {
        return $branch->settings()->create([
            'default_currency' => 'IDR',
            'default_language' => 'id',
            'timezone' => 'Asia/Jakarta',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'number_format' => '0,0.00',
            'tax_calculation_method' => 'inclusive',
            'invoice_prefix' => 'INV',
            'quotation_prefix' => 'QUO',
            'purchase_order_prefix' => 'PO',
            'receipt_prefix' => 'RCP',
            'payment_prefix' => 'PAY',
            'auto_generate_code' => true,
            'code_length' => 6,
            'send_email_notifications' => true,
            'send_sms_notifications' => false,
            'allow_negative_stock' => false,
            'require_approval_for_purchase' => true,
            'require_approval_for_sale' => false,
            'default_payment_terms' => 30,
            'default_credit_limit' => 0,
            'auto_close_quotation_days' => 30,
            'auto_close_invoice_days' => 90,
            'backup_frequency' => 'daily',
            'data_retention_days' => 2555, // 7 years
            'is_active' => true
        ]);
    }

    /**
     * Update branch settings
     */
    public function updateSettings(Branch $branch, array $data): BranchSetting
    {
        $settings = $branch->settings()->first();
        
        if (!$settings) {
            $settings = $this->createDefaultSettings($branch);
        }

        $settings->update($data);
        
        return $settings;
    }

    /**
     * Assign warehouse to branch
     */
    public function assignWarehouse(Branch $branch, int $warehouseId): BranchWarehouse
    {
        if ($branch->warehouses()->where('warehouse_id', $warehouseId)->exists()) {
            throw new \Exception('Warehouse already assigned to this branch.');
        }

        return $branch->warehouses()->create([
            'warehouse_id' => $warehouseId,
            'is_primary' => false,
            'is_active' => true
        ]);
    }

    /**
     * Remove warehouse from branch
     */
    public function removeWarehouse(Branch $branch, int $warehouseId): bool
    {
        $branchWarehouse = $branch->warehouses()->where('warehouse_id', $warehouseId)->first();
        
        if (!$branchWarehouse) {
            throw new \Exception('Warehouse not assigned to this branch.');
        }

        return $branchWarehouse->delete();
    }

    /**
     * Set primary warehouse for branch
     */
    public function setPrimaryWarehouse(Branch $branch, int $warehouseId): bool
    {
        return DB::transaction(function () use ($branch, $warehouseId) {
            // Remove primary status from all warehouses
            $branch->warehouses()->update(['is_primary' => false]);
            
            // Set new primary warehouse
            $branchWarehouse = $branch->warehouses()->where('warehouse_id', $warehouseId)->first();
            
            if (!$branchWarehouse) {
                throw new \Exception('Warehouse not assigned to this branch.');
            }
            
            $branchWarehouse->update(['is_primary' => true]);
            
            return true;
        });
    }

    /**
     * Get branch dashboard statistics
     */
    public function getDashboardStatistics(Branch $branch): array
    {
        return [
            'warehouses_count' => $branch->warehouses()->count(),
            'teams_count' => $branch->teams()->count(),
            'active_warehouses' => $branch->warehouses()
                ->where('is_active', true)
                ->get(),
            'primary_warehouse' => $branch->warehouses()
                ->where('is_primary', true)
                ->with('warehouse')
                ->first(),
            'recent_teams' => $branch->teams()
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
        ];
    }

    /**
     * Bulk delete branches
     */
    public function bulkDelete(array $branchIds): array
    {
        $deletedCount = 0;
        $errors = [];

        foreach ($branchIds as $branchId) {
            try {
                $branch = Branch::find($branchId);
                
                if ($branch) {
                    $this->deleteBranch($branch);
                    $deletedCount++;
                }
            } catch (\Exception $e) {
                $errors[] = "Branch ID {$branchId}: " . $e->getMessage();
            }
        }

        return [
            'deleted_count' => $deletedCount,
            'errors' => $errors
        ];
    }

    /**
     * Bulk update branch status
     */
    public function bulkUpdateStatus(array $branchIds, string $status): int
    {
        return Branch::whereIn('id', $branchIds)
            ->update(['status' => $status]);
    }

    /**
     * Toggle branch status
     */
    public function toggleStatus(Branch $branch): string
    {
        $newStatus = $branch->status === 'active' ? 'inactive' : 'active';
        $branch->update(['status' => $newStatus]);
        
        return $newStatus;
    }

    /**
     * Validate if branch can be deleted
     */
    protected function validateBranchDeletion(Branch $branch): void
    {
        $hasTeams = $branch->teams()->exists();

        if ($hasTeams) {
            throw new \Exception('Cannot delete branch that still has teams.');
        }
    }

    /**
     * Get branch statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_branches' => Branch::count(),
            'active_branches' => Branch::where('status', 'active')->count(),
            'branches_by_type' => Branch::selectRaw('branch_type, COUNT(*) as count')
                ->groupBy('branch_type')
                ->pluck('count', 'branch_type')
                ->toArray(),
            'branches_by_company' => Branch::selectRaw('companies.name as company_name, COUNT(branches.id) as count')
                ->join('companies', 'branches.company_id', '=', 'companies.id')
                ->groupBy('companies.id', 'companies.name')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'company_name')
                ->toArray(),
            'branches_by_province' => Branch::selectRaw('provinces.name as province_name, COUNT(branches.id) as count')
                ->join('provinces', 'branches.province_id', '=', 'provinces.id')
                ->groupBy('provinces.id', 'provinces.name')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'province_name')
                ->toArray()
        ];
    }

    /**
     * Search branches
     */
    public function searchBranches(string $search, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return Branch::where('branch_name', 'like', '%' . $search . '%')
            ->orWhere('branch_code', 'like', '%' . $search . '%')
            ->orWhere('email', 'like', '%' . $search . '%')
            ->where('status', 'active')
            ->with(['company', 'province', 'city'])
            ->orderBy('branch_name')
            ->limit($limit)
            ->get();
    }

    /**
     * Get branches by company
     */
    public function getBranchesByCompany(int $companyId): \Illuminate\Database\Eloquent\Collection
    {
        return Branch::where('company_id', $companyId)
            ->where('status', 'active')
            ->with(['company', 'province', 'city'])
            ->orderBy('branch_name')
            ->get();
    }

    /**
     * Get branches by province
     */
    public function getBranchesByProvince(int $provinceId): \Illuminate\Database\Eloquent\Collection
    {
        return Branch::where('province_id', $provinceId)
            ->where('status', 'active')
            ->with(['company', 'province', 'city'])
            ->orderBy('branch_name')
            ->get();
    }

    /**
     * Get branches by city
     */
    public function getBranchesByCity(int $cityId): \Illuminate\Database\Eloquent\Collection
    {
        return Branch::where('city_id', $cityId)
            ->where('status', 'active')
            ->with(['company', 'province', 'city'])
            ->orderBy('branch_name')
            ->get();
    }

    /**
     * Get branches with warehouses
     */
    public function getBranchesWithWarehouses(): \Illuminate\Database\Eloquent\Collection
    {
        return Branch::has('warehouses')
            ->where('status', 'active')
            ->with(['company', 'province', 'city', 'warehouses.warehouse'])
            ->orderBy('branch_name')
            ->get();
    }

    /**
     * Get branches with teams
     */
    public function getBranchesWithTeams(): \Illuminate\Database\Eloquent\Collection
    {
        return Branch::has('teams')
            ->where('status', 'active')
            ->with(['company', 'province', 'city', 'teams'])
            ->orderBy('branch_name')
            ->get();
    }
}
