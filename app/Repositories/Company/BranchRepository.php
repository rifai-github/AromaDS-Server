<?php

namespace App\Repositories\Company;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class BranchRepository
{
    protected $model;

    public function __construct(Branch $model)
    {
        $this->model = $model;
    }

    /**
     * Get all branches with pagination
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with([
            'company',
            'province',
            'city',
            'district',
            'subdistrict',
            'settings',
            'warehouses.warehouse',
            'teams',
            'createdBy',
            'updatedBy'
        ]);

        // Apply filters
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('branch_name', 'like', "%{$search}%")
                  ->orWhere('branch_code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['branch_type'])) {
            $query->where('branch_type', $filters['branch_type']);
        }

        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (!empty($filters['province_id'])) {
            $query->where('province_id', $filters['province_id']);
        }

        if (!empty($filters['city_id'])) {
            $query->where('city_id', $filters['city_id']);
        }

        if (!empty($filters['is_24_hours'])) {
            $query->where('is_24_hours', $filters['is_24_hours']);
        }

        if (!empty($filters['created_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }

        if (!empty($filters['created_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_to']);
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Get branch by ID
     */
    public function getById(int $id): ?Branch
    {
        return $this->model->with([
            'company',
            'province',
            'city',
            'district',
            'subdistrict',
            'settings',
            'warehouses.warehouse',
            'teams',
            'createdBy',
            'updatedBy'
        ])->find($id);
    }

    /**
     * Get branch by code
     */
    public function getByCode(string $code): ?Branch
    {
        return $this->model->where('branch_code', $code)->first();
    }

    /**
     * Get branches by status
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)
            ->with(['company', 'province', 'city'])
            ->orderBy('branch_name')
            ->get();
    }

    /**
     * Get branches by type
     */
    public function getByType(string $type): Collection
    {
        return $this->model->where('branch_type', $type)
            ->where('status', 'active')
            ->with(['company', 'province', 'city'])
            ->orderBy('branch_name')
            ->get();
    }

    /**
     * Get branches by company
     */
    public function getByCompany(int $companyId): Collection
    {
        return $this->model->where('company_id', $companyId)
            ->where('status', 'active')
            ->with(['company', 'province', 'city'])
            ->orderBy('branch_name')
            ->get();
    }

    /**
     * Get branches by province
     */
    public function getByProvince(int $provinceId): Collection
    {
        return $this->model->where('province_id', $provinceId)
            ->where('status', 'active')
            ->with(['company', 'province', 'city'])
            ->orderBy('branch_name')
            ->get();
    }

    /**
     * Get branches by city
     */
    public function getByCity(int $cityId): Collection
    {
        return $this->model->where('city_id', $cityId)
            ->where('status', 'active')
            ->with(['company', 'province', 'city'])
            ->orderBy('branch_name')
            ->get();
    }

    /**
     * Search branches
     */
    public function search(string $search, int $limit = 10): Collection
    {
        return $this->model->where(function ($query) use ($search) {
            $query->where('branch_name', 'like', "%{$search}%")
                  ->orWhere('branch_code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
        })
        ->where('status', 'active')
        ->with(['company', 'province', 'city'])
        ->orderBy('branch_name')
        ->limit($limit)
        ->get();
    }

    /**
     * Get branch statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_branches' => $this->model->count(),
            'active_branches' => $this->model->where('status', 'active')->count(),
            'inactive_branches' => $this->model->where('status', 'inactive')->count(),
            'branches_by_type' => $this->model->selectRaw('branch_type, COUNT(*) as count')
                ->groupBy('branch_type')
                ->pluck('count', 'branch_type')
                ->toArray(),
            'branches_by_company' => $this->model->selectRaw('companies.name as company_name, COUNT(branches.id) as count')
                ->join('companies', 'branches.company_id', '=', 'companies.id')
                ->groupBy('companies.id', 'companies.name')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'company_name')
                ->toArray(),
            'branches_by_province' => $this->model->selectRaw('provinces.name as province_name, COUNT(branches.id) as count')
                ->join('provinces', 'branches.province_id', '=', 'provinces.id')
                ->groupBy('provinces.id', 'provinces.name')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'province_name')
                ->toArray(),
            'branches_with_warehouses' => $this->model->has('warehouses')->count(),
            'branches_with_teams' => $this->model->has('teams')->count(),
            'branches_24_hours' => $this->model->where('is_24_hours', true)->count()
        ];
    }

    /**
     * Get recent branches
     */
    public function getRecent(int $limit = 10): Collection
    {
        return $this->model->with(['company', 'province', 'city'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get branches with warehouses
     */
    public function getWithWarehouses(): Collection
    {
        return $this->model->has('warehouses')
            ->where('status', 'active')
            ->with(['company', 'province', 'city', 'warehouses.warehouse'])
            ->orderBy('branch_name')
            ->get();
    }

    /**
     * Get branches with teams
     */
    public function getWithTeams(): Collection
    {
        return $this->model->has('teams')
            ->where('status', 'active')
            ->with(['company', 'province', 'city', 'teams'])
            ->orderBy('branch_name')
            ->get();
    }

    /**
     * Get branches by opening hours
     */
    public function getByOpeningHours(string $openingHours): Collection
    {
        return $this->model->where('opening_hours', $openingHours)
            ->where('status', 'active')
            ->with(['company', 'province', 'city'])
            ->orderBy('branch_name')
            ->get();
    }

    /**
     * Get branches by closing hours
     */
    public function getByClosingHours(string $closingHours): Collection
    {
        return $this->model->where('closing_hours', $closingHours)
            ->where('status', 'active')
            ->with(['company', 'province', 'city'])
            ->orderBy('branch_name')
            ->get();
    }

    /**
     * Get 24-hour branches
     */
    public function get24HourBranches(): Collection
    {
        return $this->model->where('is_24_hours', true)
            ->where('status', 'active')
            ->with(['company', 'province', 'city'])
            ->orderBy('branch_name')
            ->get();
    }

    /**
     * Get branches within radius
     */
    public function getWithinRadius(float $latitude, float $longitude, float $radiusKm = 10): Collection
    {
        return $this->model->selectRaw("
                *,
                (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance
            ", [$latitude, $longitude, $latitude])
            ->where('status', 'active')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance')
            ->with(['company', 'province', 'city'])
            ->get();
    }

    /**
     * Create branch
     */
    public function create(array $data): Branch
    {
        return $this->model->create($data);
    }

    /**
     * Update branch
     */
    public function update(Branch $branch, array $data): bool
    {
        return $branch->update($data);
    }

    /**
     * Delete branch
     */
    public function delete(Branch $branch): bool
    {
        return $branch->delete();
    }

    /**
     * Bulk delete branches
     */
    public function bulkDelete(array $ids): int
    {
        return $this->model->whereIn('id', $ids)->delete();
    }

    /**
     * Bulk update status
     */
    public function bulkUpdateStatus(array $ids, string $status): int
    {
        return $this->model->whereIn('id', $ids)->update(['status' => $status]);
    }

    /**
     * Get branches for export
     */
    public function getForExport(array $filters = []): Collection
    {
        $query = $this->model->with([
            'company',
            'province',
            'city',
            'district',
            'subdistrict',
            'settings',
            'warehouses.warehouse',
            'teams'
        ]);

        // Apply same filters as getAll method
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('branch_name', 'like', "%{$search}%")
                  ->orWhere('branch_code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['branch_type'])) {
            $query->where('branch_type', $filters['branch_type']);
        }

        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (!empty($filters['province_id'])) {
            $query->where('province_id', $filters['province_id']);
        }

        if (!empty($filters['city_id'])) {
            $query->where('city_id', $filters['city_id']);
        }

        return $query->orderBy('branch_name')->get();
    }

    /**
     * Check if branch code exists
     */
    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $query = $this->model->where('branch_code', $code);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * Check if branch email exists
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $query = $this->model->where('email', $email);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }
}
