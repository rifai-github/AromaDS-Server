<?php

namespace App\Repositories\Company;

use App\Models\Company;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class CompanyRepository
{
    protected $model;

    public function __construct(Company $model)
    {
        $this->model = $model;
    }

    /**
     * Get all companies with pagination
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with([
            'province',
            'city',
            'district',
            'subdistrict',
            'companyTagAssignments.tag',
            'createdBy',
            'updatedBy'
        ]);

        // Apply filters
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['company_type'])) {
            $query->where('company_type', $filters['company_type']);
        }

        if (!empty($filters['industry'])) {
            $query->where('industry', $filters['industry']);
        }

        if (!empty($filters['province_id'])) {
            $query->where('province_id', $filters['province_id']);
        }

        if (!empty($filters['city_id'])) {
            $query->where('city_id', $filters['city_id']);
        }

        if (!empty($filters['tag_id'])) {
            $query->whereHas('companyTagAssignments', function ($q) use ($filters) {
                $q->where('tag_id', $filters['tag_id']);
            });
        }

        if (!empty($filters['employee_count_min'])) {
            $query->where('employee_count', '>=', $filters['employee_count_min']);
        }

        if (!empty($filters['employee_count_max'])) {
            $query->where('employee_count', '<=', $filters['employee_count_max']);
        }

        if (!empty($filters['annual_revenue_min'])) {
            $query->where('annual_revenue', '>=', $filters['annual_revenue_min']);
        }

        if (!empty($filters['annual_revenue_max'])) {
            $query->where('annual_revenue', '<=', $filters['annual_revenue_max']);
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
     * Get company by ID
     */
    public function getById(int $id): ?Company
    {
        return $this->model->with([
            'province',
            'city',
            'district',
            'subdistrict',
            'companyTagAssignments.tag',
            'settings',
            'documents',
            'notes',
            'relationships.relatedCompany',
            'activities',
            'communications',
            'branches',
            'customers',
            'suppliers',
            'createdBy',
            'updatedBy'
        ])->find($id);
    }

    /**
     * Get company by code
     */
    public function getByCode(string $code): ?Company
    {
        return $this->model->where('code', $code)->first();
    }

    /**
     * Get companies by status
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)
            ->with(['province', 'city'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Get companies by type
     */
    public function getByType(string $type): Collection
    {
        return $this->model->where('company_type', $type)
            ->where('status', 'active')
            ->with(['province', 'city'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Get companies by industry
     */
    public function getByIndustry(string $industry): Collection
    {
        return $this->model->where('industry', $industry)
            ->where('status', 'active')
            ->with(['province', 'city'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Get companies by province
     */
    public function getByProvince(int $provinceId): Collection
    {
        return $this->model->where('province_id', $provinceId)
            ->where('status', 'active')
            ->with(['province', 'city'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Get companies by city
     */
    public function getByCity(int $cityId): Collection
    {
        return $this->model->where('city_id', $cityId)
            ->where('status', 'active')
            ->with(['province', 'city'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Get companies with branches
     */
    public function getWithBranches(): Collection
    {
        return $this->model->has('branches')
            ->where('status', 'active')
            ->with(['province', 'city', 'branches'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Get companies with customers
     */
    public function getWithCustomers(): Collection
    {
        return $this->model->has('customers')
            ->where('status', 'active')
            ->with(['province', 'city', 'customers'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Get companies with suppliers
     */
    public function getWithSuppliers(): Collection
    {
        return $this->model->has('suppliers')
            ->where('status', 'active')
            ->with(['province', 'city', 'suppliers'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Search companies
     */
    public function search(string $search, int $limit = 10): Collection
    {
        return $this->model->where(function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
        })
        ->where('status', 'active')
        ->with(['province', 'city'])
        ->orderBy('name')
        ->limit($limit)
        ->get();
    }

    /**
     * Get company statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_companies' => $this->model->count(),
            'active_companies' => $this->model->where('status', 'active')->count(),
            'inactive_companies' => $this->model->where('status', 'inactive')->count(),
            'companies_with_branches' => $this->model->has('branches')->count(),
            'companies_with_customers' => $this->model->has('customers')->count(),
            'companies_with_suppliers' => $this->model->has('suppliers')->count(),
            'companies_by_type' => $this->model->selectRaw('company_type, COUNT(*) as count')
                ->whereNotNull('company_type')
                ->groupBy('company_type')
                ->pluck('count', 'company_type')
                ->toArray(),
            'companies_by_industry' => $this->model->selectRaw('industry, COUNT(*) as count')
                ->whereNotNull('industry')
                ->groupBy('industry')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'industry')
                ->toArray(),
            'companies_by_province' => $this->model->selectRaw('provinces.name as province_name, COUNT(companies.id) as count')
                ->join('provinces', 'companies.province_id', '=', 'provinces.id')
                ->groupBy('provinces.id', 'provinces.name')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'province_name')
                ->toArray()
        ];
    }

    /**
     * Get recent companies
     */
    public function getRecent(int $limit = 10): Collection
    {
        return $this->model->with(['province', 'city'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get companies by usage (most active)
     */
    public function getByUsage(int $limit = 10): Collection
    {
        return $this->model->withCount(['activities', 'communications', 'notes'])
            ->with(['province', 'city'])
            ->orderBy('activities_count', 'desc')
            ->orderBy('communications_count', 'desc')
            ->orderBy('notes_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get companies with upcoming activities
     */
    public function getWithUpcomingActivities(int $days = 7): Collection
    {
        return $this->model->whereHas('activities', function ($query) use ($days) {
            $query->where('activity_date', '>=', now())
                  ->where('activity_date', '<=', now()->addDays($days))
                  ->where('is_completed', false);
        })
        ->with(['province', 'city', 'activities' => function ($query) use ($days) {
            $query->where('activity_date', '>=', now())
                  ->where('activity_date', '<=', now()->addDays($days))
                  ->where('is_completed', false)
                  ->orderBy('activity_date', 'asc');
        }])
        ->orderBy('name')
        ->get();
    }

    /**
     * Get companies with overdue activities
     */
    public function getWithOverdueActivities(): Collection
    {
        return $this->model->whereHas('activities', function ($query) {
            $query->where('activity_date', '<', now())
                  ->where('is_completed', false);
        })
        ->with(['province', 'city', 'activities' => function ($query) {
            $query->where('activity_date', '<', now())
                  ->where('is_completed', false)
                  ->orderBy('activity_date', 'asc');
        }])
        ->orderBy('name')
        ->get();
    }

    /**
     * Get companies by tag
     */
    public function getByTag(int $tagId): Collection
    {
        return $this->model->whereHas('companyTagAssignments', function ($query) use ($tagId) {
            $query->where('tag_id', $tagId);
        })
        ->where('status', 'active')
        ->with(['province', 'city', 'companyTagAssignments.tag'])
        ->orderBy('name')
        ->get();
    }

    /**
     * Get companies with unread communications
     */
    public function getWithUnreadCommunications(): Collection
    {
        return $this->model->whereHas('communications', function ($query) {
            $query->where('status', 'unread');
        })
        ->with(['province', 'city', 'communications' => function ($query) {
            $query->where('status', 'unread')
                  ->orderBy('communication_date', 'desc');
        }])
        ->orderBy('name')
        ->get();
    }

    /**
     * Create company
     */
    public function create(array $data): Company
    {
        return $this->model->create($data);
    }

    /**
     * Update company
     */
    public function update(Company $company, array $data): bool
    {
        return $company->update($data);
    }

    /**
     * Delete company
     */
    public function delete(Company $company): bool
    {
        return $company->delete();
    }

    /**
     * Bulk delete companies
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
     * Get companies for export
     */
    public function getForExport(array $filters = []): Collection
    {
        $query = $this->model->with([
            'province',
            'city',
            'district',
            'subdistrict',
            'companyTagAssignments.tag',
            'settings',
            'branches',
            'customers',
            'suppliers'
        ]);

        // Apply same filters as getAll method
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['company_type'])) {
            $query->where('company_type', $filters['company_type']);
        }

        if (!empty($filters['industry'])) {
            $query->where('industry', $filters['industry']);
        }

        if (!empty($filters['province_id'])) {
            $query->where('province_id', $filters['province_id']);
        }

        if (!empty($filters['city_id'])) {
            $query->where('city_id', $filters['city_id']);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Check if company code exists
     */
    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $query = $this->model->where('code', $code);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * Check if company email exists
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
