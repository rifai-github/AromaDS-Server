<?php

namespace App\Repositories\Company;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SupplierRepository
{
    protected $model;

    public function __construct(Supplier $model)
    {
        $this->model = $model;
    }

    /**
     * Get all suppliers with pagination
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with([
            'company',
            'province',
            'city',
            'district',
            'subdistrict',
            'creditLimits',
            'paymentTerms',
            'createdBy',
            'updatedBy'
        ]);

        // Apply filters
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('supplier_name', 'like', "%{$search}%")
                  ->orWhere('supplier_code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['supplier_type'])) {
            $query->where('supplier_type', $filters['supplier_type']);
        }

        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (!empty($filters['industry'])) {
            $query->where('industry', $filters['industry']);
        }

        if (!empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        if (!empty($filters['province_id'])) {
            $query->where('province_id', $filters['province_id']);
        }

        if (!empty($filters['city_id'])) {
            $query->where('city_id', $filters['city_id']);
        }

        if (!empty($filters['company_size'])) {
            $query->where('company_size', $filters['company_size']);
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
     * Get supplier by ID
     */
    public function getById(int $id): ?Supplier
    {
        return $this->model->with([
            'company',
            'province',
            'city',
            'district',
            'subdistrict',
            'creditLimits',
            'paymentTerms',
            'createdBy',
            'updatedBy'
        ])->find($id);
    }

    /**
     * Get supplier by code
     */
    public function getByCode(string $code): ?Supplier
    {
        return $this->model->where('supplier_code', $code)->first();
    }

    /**
     * Get suppliers by status
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)
            ->with(['company', 'province', 'city'])
            ->orderBy('supplier_name')
            ->get();
    }

    /**
     * Get suppliers by type
     */
    public function getByType(string $type): Collection
    {
        return $this->model->where('supplier_type', $type)
            ->where('status', 'active')
            ->with(['company', 'province', 'city'])
            ->orderBy('supplier_name')
            ->get();
    }

    /**
     * Get suppliers by company
     */
    public function getByCompany(int $companyId): Collection
    {
        return $this->model->where('company_id', $companyId)
            ->where('status', 'active')
            ->with(['company', 'province', 'city'])
            ->orderBy('supplier_name')
            ->get();
    }

    /**
     * Get suppliers by industry
     */
    public function getByIndustry(string $industry): Collection
    {
        return $this->model->where('industry', $industry)
            ->where('status', 'active')
            ->with(['company', 'province', 'city'])
            ->orderBy('supplier_name')
            ->get();
    }

    /**
     * Get suppliers by source
     */
    public function getBySource(string $source): Collection
    {
        return $this->model->where('source', $source)
            ->where('status', 'active')
            ->with(['company', 'province', 'city'])
            ->orderBy('supplier_name')
            ->get();
    }

    /**
     * Get suppliers by province
     */
    public function getByProvince(int $provinceId): Collection
    {
        return $this->model->where('province_id', $provinceId)
            ->where('status', 'active')
            ->with(['company', 'province', 'city'])
            ->orderBy('supplier_name')
            ->get();
    }

    /**
     * Get suppliers by city
     */
    public function getByCity(int $cityId): Collection
    {
        return $this->model->where('city_id', $cityId)
            ->where('status', 'active')
            ->with(['company', 'province', 'city'])
            ->orderBy('supplier_name')
            ->get();
    }

    /**
     * Search suppliers
     */
    public function search(string $search, int $limit = 10): Collection
    {
        return $this->model->where(function ($query) use ($search) {
            $query->where('supplier_name', 'like', "%{$search}%")
                  ->orWhere('supplier_code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
        })
        ->where('status', 'active')
        ->with(['company', 'province', 'city'])
        ->orderBy('supplier_name')
        ->limit($limit)
        ->get();
    }

    /**
     * Get supplier statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_suppliers' => $this->model->count(),
            'active_suppliers' => $this->model->where('status', 'active')->count(),
            'inactive_suppliers' => $this->model->where('status', 'inactive')->count(),
            'suppliers_by_type' => $this->model->selectRaw('supplier_type, COUNT(*) as count')
                ->groupBy('supplier_type')
                ->pluck('count', 'supplier_type')
                ->toArray(),
            'suppliers_by_industry' => $this->model->selectRaw('industry, COUNT(*) as count')
                ->whereNotNull('industry')
                ->groupBy('industry')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'industry')
                ->toArray(),
            'suppliers_by_source' => $this->model->selectRaw('source, COUNT(*) as count')
                ->whereNotNull('source')
                ->groupBy('source')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'source')
                ->toArray(),
            'suppliers_by_company' => $this->model->selectRaw('companies.name as company_name, COUNT(suppliers.id) as count')
                ->join('companies', 'suppliers.company_id', '=', 'companies.id')
                ->groupBy('companies.id', 'companies.name')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'company_name')
                ->toArray(),
            'suppliers_by_province' => $this->model->selectRaw('provinces.name as province_name, COUNT(suppliers.id) as count')
                ->join('provinces', 'suppliers.province_id', '=', 'provinces.id')
                ->groupBy('provinces.id', 'provinces.name')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'province_name')
                ->toArray()
        ];
    }

    /**
     * Get recent suppliers
     */
    public function getRecent(int $limit = 10): Collection
    {
        return $this->model->with(['company', 'province', 'city'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get suppliers with credit limits
     */
    public function getWithCreditLimits(): Collection
    {
        return $this->model->has('creditLimits')
            ->where('status', 'active')
            ->with(['company', 'province', 'city', 'creditLimits'])
            ->orderBy('supplier_name')
            ->get();
    }

    /**
     * Get suppliers with payment terms
     */
    public function getWithPaymentTerms(): Collection
    {
        return $this->model->has('paymentTerms')
            ->where('status', 'active')
            ->with(['company', 'province', 'city', 'paymentTerms'])
            ->orderBy('supplier_name')
            ->get();
    }

    /**
     * Get suppliers with expiring credit limits
     */
    public function getWithExpiringCreditLimits(int $days = 30): Collection
    {
        return $this->model->whereHas('creditLimits', function ($query) use ($days) {
            $query->where('is_active', true)
                  ->where('valid_to', '<=', now()->addDays($days))
                  ->where('valid_to', '>=', now());
        })
        ->with(['company', 'province', 'city', 'creditLimits' => function ($query) use ($days) {
            $query->where('is_active', true)
                  ->where('valid_to', '<=', now()->addDays($days))
                  ->where('valid_to', '>=', now())
                  ->orderBy('valid_to', 'asc');
        }])
        ->orderBy('supplier_name')
        ->get();
    }

    /**
     * Get suppliers with expiring payment terms
     */
    public function getWithExpiringPaymentTerms(int $days = 30): Collection
    {
        return $this->model->whereHas('paymentTerms', function ($query) use ($days) {
            $query->where('is_active', true)
                  ->where('valid_to', '<=', now()->addDays($days))
                  ->where('valid_to', '>=', now());
        })
        ->with(['company', 'province', 'city', 'paymentTerms' => function ($query) use ($days) {
            $query->where('is_active', true)
                  ->where('valid_to', '<=', now()->addDays($days))
                  ->where('valid_to', '>=', now())
                  ->orderBy('valid_to', 'asc');
        }])
        ->orderBy('supplier_name')
        ->get();
    }

    /**
     * Create supplier
     */
    public function create(array $data): Supplier
    {
        return $this->model->create($data);
    }

    /**
     * Update supplier
     */
    public function update(Supplier $supplier, array $data): bool
    {
        return $supplier->update($data);
    }

    /**
     * Delete supplier
     */
    public function delete(Supplier $supplier): bool
    {
        return $supplier->delete();
    }

    /**
     * Bulk delete suppliers
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
     * Get suppliers for export
     */
    public function getForExport(array $filters = []): Collection
    {
        $query = $this->model->with([
            'company',
            'province',
            'city',
            'district',
            'subdistrict',
            'creditLimits',
            'paymentTerms'
        ]);

        // Apply same filters as getAll method
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('supplier_name', 'like', "%{$search}%")
                  ->orWhere('supplier_code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['supplier_type'])) {
            $query->where('supplier_type', $filters['supplier_type']);
        }

        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (!empty($filters['industry'])) {
            $query->where('industry', $filters['industry']);
        }

        if (!empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        if (!empty($filters['province_id'])) {
            $query->where('province_id', $filters['province_id']);
        }

        if (!empty($filters['city_id'])) {
            $query->where('city_id', $filters['city_id']);
        }

        return $query->orderBy('supplier_name')->get();
    }

    /**
     * Check if supplier code exists
     */
    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $query = $this->model->where('supplier_code', $code);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * Check if supplier email exists
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
