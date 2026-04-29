<?php

namespace App\Repositories\Company;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerRepository
{
    protected $model;

    public function __construct(Customer $model)
    {
        $this->model = $model;
    }

    /**
     * Get all customers with pagination
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with([
            'company',
            'province',
            'city',
            'district',
            'subdistrict',
            'assignedTo',
            'creditLimits',
            'paymentTerms',
            'createdBy',
            'updatedBy'
        ]);

        // Apply filters
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['customer_type'])) {
            $query->where('customer_type', $filters['customer_type']);
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

        if (!empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
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
     * Get customer by ID
     */
    public function getById(int $id): ?Customer
    {
        return $this->model->with([
            'company',
            'province',
            'city',
            'district',
            'subdistrict',
            'assignedTo',
            'creditLimits',
            'paymentTerms',
            'surveys',
            'quotations',
            'contracts',
            'createdBy',
            'updatedBy'
        ])->find($id);
    }

    /**
     * Get customer by code
     */
    public function getByCode(string $code): ?Customer
    {
        return $this->model->where('customer_code', $code)->first();
    }

    /**
     * Get customers by status
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)
            ->with(['company', 'province', 'city'])
            ->orderBy('customer_name')
            ->get();
    }

    /**
     * Get customers by type
     */
    public function getByType(string $type): Collection
    {
        return $this->model->where('customer_type', $type)
            ->where('status', 'active')
            ->with(['company', 'province', 'city'])
            ->orderBy('customer_name')
            ->get();
    }

    /**
     * Get customers by company
     */
    public function getByCompany(int $companyId): Collection
    {
        return $this->model->where('company_id', $companyId)
            ->where('status', 'active')
            ->with(['company', 'province', 'city'])
            ->orderBy('customer_name')
            ->get();
    }

    /**
     * Get customers by industry
     */
    public function getByIndustry(string $industry): Collection
    {
        return $this->model->where('industry', $industry)
            ->where('status', 'active')
            ->with(['company', 'province', 'city'])
            ->orderBy('customer_name')
            ->get();
    }

    /**
     * Get customers by source
     */
    public function getBySource(string $source): Collection
    {
        return $this->model->where('source', $source)
            ->where('status', 'active')
            ->with(['company', 'province', 'city'])
            ->orderBy('customer_name')
            ->get();
    }

    /**
     * Get customers by assigned user
     */
    public function getByAssignedUser(int $userId): Collection
    {
        return $this->model->where('assigned_to', $userId)
            ->where('status', 'active')
            ->with(['company', 'province', 'city'])
            ->orderBy('customer_name')
            ->get();
    }

    /**
     * Get customers by province
     */
    public function getByProvince(int $provinceId): Collection
    {
        return $this->model->where('province_id', $provinceId)
            ->where('status', 'active')
            ->with(['company', 'province', 'city'])
            ->orderBy('customer_name')
            ->get();
    }

    /**
     * Get customers by city
     */
    public function getByCity(int $cityId): Collection
    {
        return $this->model->where('city_id', $cityId)
            ->where('status', 'active')
            ->with(['company', 'province', 'city'])
            ->orderBy('customer_name')
            ->get();
    }

    /**
     * Search customers
     */
    public function search(string $search, int $limit = 10): Collection
    {
        return $this->model->where(function ($query) use ($search) {
            $query->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
        })
        ->where('status', 'active')
        ->with(['company', 'province', 'city'])
        ->orderBy('customer_name')
        ->limit($limit)
        ->get();
    }

    /**
     * Get customer statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_customers' => $this->model->count(),
            'active_customers' => $this->model->where('status', 'active')->count(),
            'inactive_customers' => $this->model->where('status', 'inactive')->count(),
            'customers_by_type' => $this->model->selectRaw('customer_type, COUNT(*) as count')
                ->groupBy('customer_type')
                ->pluck('count', 'customer_type')
                ->toArray(),
            'customers_by_industry' => $this->model->selectRaw('industry, COUNT(*) as count')
                ->whereNotNull('industry')
                ->groupBy('industry')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'industry')
                ->toArray(),
            'customers_by_source' => $this->model->selectRaw('source, COUNT(*) as count')
                ->whereNotNull('source')
                ->groupBy('source')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'source')
                ->toArray(),
            'customers_by_company' => $this->model->selectRaw('companies.name as company_name, COUNT(customers.id) as count')
                ->join('companies', 'customers.company_id', '=', 'companies.id')
                ->groupBy('companies.id', 'companies.name')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'company_name')
                ->toArray(),
            'customers_by_province' => $this->model->selectRaw('provinces.name as province_name, COUNT(customers.id) as count')
                ->join('provinces', 'customers.province_id', '=', 'provinces.id')
                ->groupBy('provinces.id', 'provinces.name')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'province_name')
                ->toArray()
        ];
    }

    /**
     * Get recent customers
     */
    public function getRecent(int $limit = 10): Collection
    {
        return $this->model->with(['company', 'province', 'city'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get customers with credit limits
     */
    public function getWithCreditLimits(): Collection
    {
        return $this->model->has('creditLimits')
            ->where('status', 'active')
            ->with(['company', 'province', 'city', 'creditLimits'])
            ->orderBy('customer_name')
            ->get();
    }

    /**
     * Get customers with payment terms
     */
    public function getWithPaymentTerms(): Collection
    {
        return $this->model->has('paymentTerms')
            ->where('status', 'active')
            ->with(['company', 'province', 'city', 'paymentTerms'])
            ->orderBy('customer_name')
            ->get();
    }

    /**
     * Get customers with expiring credit limits
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
        ->orderBy('customer_name')
        ->get();
    }

    /**
     * Get customers with expiring payment terms
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
        ->orderBy('customer_name')
        ->get();
    }

    /**
     * Get customers with surveys
     */
    public function getWithSurveys(): Collection
    {
        return $this->model->has('surveys')
            ->where('status', 'active')
            ->with(['company', 'province', 'city', 'surveys'])
            ->orderBy('customer_name')
            ->get();
    }

    /**
     * Get customers with quotations
     */
    public function getWithQuotations(): Collection
    {
        return $this->model->has('quotations')
            ->where('status', 'active')
            ->with(['company', 'province', 'city', 'quotations'])
            ->orderBy('customer_name')
            ->get();
    }

    /**
     * Get customers with contracts
     */
    public function getWithContracts(): Collection
    {
        return $this->model->has('contracts')
            ->where('status', 'active')
            ->with(['company', 'province', 'city', 'contracts'])
            ->orderBy('customer_name')
            ->get();
    }

    /**
     * Create customer
     */
    public function create(array $data): Customer
    {
        return $this->model->create($data);
    }

    /**
     * Update customer
     */
    public function update(Customer $customer, array $data): bool
    {
        return $customer->update($data);
    }

    /**
     * Delete customer
     */
    public function delete(Customer $customer): bool
    {
        return $customer->delete();
    }

    /**
     * Bulk delete customers
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
     * Get customers for export
     */
    public function getForExport(array $filters = []): Collection
    {
        $query = $this->model->with([
            'company',
            'province',
            'city',
            'district',
            'subdistrict',
            'assignedTo',
            'creditLimits',
            'paymentTerms'
        ]);

        // Apply same filters as getAll method
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['customer_type'])) {
            $query->where('customer_type', $filters['customer_type']);
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

        if (!empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (!empty($filters['province_id'])) {
            $query->where('province_id', $filters['province_id']);
        }

        if (!empty($filters['city_id'])) {
            $query->where('city_id', $filters['city_id']);
        }

        return $query->orderBy('customer_name')->get();
    }

    /**
     * Check if customer code exists
     */
    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $query = $this->model->where('customer_code', $code);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * Check if customer email exists
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
