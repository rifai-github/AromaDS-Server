<?php

namespace App\Services\Company;

use App\Models\Supplier;
use App\Models\SupplierCategory;
use App\Models\SupplierCreditLimit;
use App\Models\SupplierPaymentTerm;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SupplierService
{
    protected $supplierRepository;

    public function __construct(SupplierRepository $supplierRepository)
    {
        $this->supplierRepository = $supplierRepository;
    }

    /**
     * Create a new supplier
     */
    public function createSupplier(array $data): Supplier
    {
        return DB::transaction(function () use ($data) {
            $supplier = Supplier::create([
                'company_id' => $data['company_id'],
                'supplier_code' => $data['supplier_code'],
                'supplier_name' => $data['supplier_name'],
                'supplier_type' => $data['supplier_type'] ?? 'individual',
                'contact_person' => $data['contact_person'] ?? null,
                'email' => $data['email'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'province_id' => $data['province_id'],
                'city_id' => $data['city_id'],
                'district_id' => $data['district_id'] ?? null,
                'subdistrict_id' => $data['subdistrict_id'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'tax_number' => $data['tax_number'] ?? null,
                'website' => $data['website'] ?? null,
                'industry' => $data['industry'] ?? null,
                'company_size' => $data['company_size'] ?? null,
                'annual_revenue' => $data['annual_revenue'] ?? null,
                'source' => $data['source'] ?? null,
                'status' => $data['status'] ?? 'active',
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id()
            ]);

            // Create default credit limit if provided
            if (isset($data['credit_limit']) && $data['credit_limit'] > 0) {
                $this->createCreditLimit($supplier, [
                    'credit_limit' => $data['credit_limit'],
                    'currency' => $data['currency'] ?? 'IDR',
                    'valid_from' => now(),
                    'valid_to' => now()->addYear(),
                    'is_active' => true
                ]);
            }

            // Create default payment terms if provided
            if (isset($data['payment_terms'])) {
                $this->createPaymentTerms($supplier, [
                    'payment_terms' => $data['payment_terms'],
                    'currency' => $data['currency'] ?? 'IDR',
                    'valid_from' => now(),
                    'valid_to' => now()->addYear(),
                    'is_active' => true
                ]);
            }

            return $supplier;
        });
    }

    /**
     * Update supplier information
     */
    public function updateSupplier(Supplier $supplier, array $data): Supplier
    {
        return DB::transaction(function () use ($supplier, $data) {
            $supplier->update([
                'supplier_code' => $data['supplier_code'],
                'supplier_name' => $data['supplier_name'],
                'supplier_type' => $data['supplier_type'] ?? $supplier->supplier_type,
                'contact_person' => $data['contact_person'] ?? $supplier->contact_person,
                'email' => $data['email'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'province_id' => $data['province_id'],
                'city_id' => $data['city_id'],
                'district_id' => $data['district_id'] ?? $supplier->district_id,
                'subdistrict_id' => $data['subdistrict_id'] ?? $supplier->subdistrict_id,
                'postal_code' => $data['postal_code'] ?? $supplier->postal_code,
                'tax_number' => $data['tax_number'] ?? $supplier->tax_number,
                'website' => $data['website'] ?? $supplier->website,
                'industry' => $data['industry'] ?? $supplier->industry,
                'company_size' => $data['company_size'] ?? $supplier->company_size,
                'annual_revenue' => $data['annual_revenue'] ?? $supplier->annual_revenue,
                'source' => $data['source'] ?? $supplier->source,
                'status' => $data['status'] ?? $supplier->status,
                'notes' => $data['notes'] ?? $supplier->notes,
                'updated_by' => Auth::id()
            ]);

            return $supplier;
        });
    }

    /**
     * Delete supplier
     */
    public function deleteSupplier(Supplier $supplier): bool
    {
        return DB::transaction(function () use ($supplier) {
            // Check if supplier can be deleted
            $this->validateSupplierDeletion($supplier);

            // Delete related data
            $supplier->creditLimits()->delete();
            $supplier->paymentTerms()->delete();

            // Delete supplier
            return $supplier->delete();
        });
    }

    /**
     * Create supplier credit limit
     */
    public function createCreditLimit(Supplier $supplier, array $data): SupplierCreditLimit
    {
        return $supplier->creditLimits()->create([
            'credit_limit' => $data['credit_limit'],
            'currency' => $data['currency'],
            'valid_from' => $data['valid_from'],
            'valid_to' => $data['valid_to'],
            'notes' => $data['notes'] ?? null,
            'is_active' => $data['is_active'] ?? true
        ]);
    }

    /**
     * Update supplier credit limit
     */
    public function updateCreditLimit(Supplier $supplier, SupplierCreditLimit $creditLimit, array $data): SupplierCreditLimit
    {
        if ($creditLimit->supplier_id !== $supplier->id) {
            throw new \Exception('Credit limit not found for this supplier.');
        }

        $creditLimit->update([
            'credit_limit' => $data['credit_limit'],
            'currency' => $data['currency'],
            'valid_from' => $data['valid_from'],
            'valid_to' => $data['valid_to'],
            'notes' => $data['notes'] ?? $creditLimit->notes,
            'is_active' => $data['is_active'] ?? $creditLimit->is_active
        ]);

        return $creditLimit;
    }

    /**
     * Delete supplier credit limit
     */
    public function deleteCreditLimit(Supplier $supplier, SupplierCreditLimit $creditLimit): bool
    {
        if ($creditLimit->supplier_id !== $supplier->id) {
            throw new \Exception('Credit limit not found for this supplier.');
        }

        return $creditLimit->delete();
    }

    /**
     * Create supplier payment terms
     */
    public function createPaymentTerms(Supplier $supplier, array $data): SupplierPaymentTerm
    {
        return $supplier->paymentTerms()->create([
            'payment_terms' => $data['payment_terms'],
            'currency' => $data['currency'],
            'valid_from' => $data['valid_from'],
            'valid_to' => $data['valid_to'],
            'notes' => $data['notes'] ?? null,
            'is_active' => $data['is_active'] ?? true
        ]);
    }

    /**
     * Update supplier payment terms
     */
    public function updatePaymentTerms(Supplier $supplier, SupplierPaymentTerm $paymentTerm, array $data): SupplierPaymentTerm
    {
        if ($paymentTerm->supplier_id !== $supplier->id) {
            throw new \Exception('Payment terms not found for this supplier.');
        }

        $paymentTerm->update([
            'payment_terms' => $data['payment_terms'],
            'currency' => $data['currency'],
            'valid_from' => $data['valid_from'],
            'valid_to' => $data['valid_to'],
            'notes' => $data['notes'] ?? $paymentTerm->notes,
            'is_active' => $data['is_active'] ?? $paymentTerm->is_active
        ]);

        return $paymentTerm;
    }

    /**
     * Delete supplier payment terms
     */
    public function deletePaymentTerms(Supplier $supplier, SupplierPaymentTerm $paymentTerm): bool
    {
        if ($paymentTerm->supplier_id !== $supplier->id) {
            throw new \Exception('Payment terms not found for this supplier.');
        }

        return $paymentTerm->delete();
    }

    /**
     * Get supplier dashboard statistics
     */
    public function getDashboardStatistics(Supplier $supplier): array
    {
        return [
            'credit_limits_count' => $supplier->creditLimits()->count(),
            'payment_terms_count' => $supplier->paymentTerms()->count(),
            'active_credit_limits' => $supplier->creditLimits()
                ->where('is_active', true)
                ->where('valid_from', '<=', now())
                ->where('valid_to', '>=', now())
                ->get(),
            'active_payment_terms' => $supplier->paymentTerms()
                ->where('is_active', true)
                ->where('valid_from', '<=', now())
                ->where('valid_to', '>=', now())
                ->get()
        ];
    }

    /**
     * Bulk delete suppliers
     */
    public function bulkDelete(array $supplierIds): array
    {
        $deletedCount = 0;
        $errors = [];

        foreach ($supplierIds as $supplierId) {
            try {
                $supplier = Supplier::find($supplierId);
                
                if ($supplier) {
                    $this->deleteSupplier($supplier);
                    $deletedCount++;
                }
            } catch (\Exception $e) {
                $errors[] = "Supplier ID {$supplierId}: " . $e->getMessage();
            }
        }

        return [
            'deleted_count' => $deletedCount,
            'errors' => $errors
        ];
    }

    /**
     * Bulk update supplier status
     */
    public function bulkUpdateStatus(array $supplierIds, string $status): int
    {
        return Supplier::whereIn('id', $supplierIds)
            ->update(['status' => $status]);
    }

    /**
     * Toggle supplier status
     */
    public function toggleStatus(Supplier $supplier): string
    {
        $newStatus = $supplier->status === 'active' ? 'inactive' : 'active';
        $supplier->update(['status' => $newStatus]);
        
        return $newStatus;
    }

    /**
     * Validate if supplier can be deleted
     */
    protected function validateSupplierDeletion(Supplier $supplier): void
    {
        // Add validation logic here if needed
        // For example, check if supplier has any purchase orders, invoices, etc.
    }

    /**
     * Get supplier statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_suppliers' => Supplier::count(),
            'active_suppliers' => Supplier::where('status', 'active')->count(),
            'suppliers_by_type' => Supplier::selectRaw('supplier_type, COUNT(*) as count')
                ->groupBy('supplier_type')
                ->pluck('count', 'supplier_type')
                ->toArray(),
            'suppliers_by_industry' => Supplier::selectRaw('industry, COUNT(*) as count')
                ->whereNotNull('industry')
                ->groupBy('industry')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'industry')
                ->toArray(),
            'suppliers_by_source' => Supplier::selectRaw('source, COUNT(*) as count')
                ->whereNotNull('source')
                ->groupBy('source')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'source')
                ->toArray(),
            'suppliers_by_company' => Supplier::selectRaw('companies.name as company_name, COUNT(suppliers.id) as count')
                ->join('companies', 'suppliers.company_id', '=', 'companies.id')
                ->groupBy('companies.id', 'companies.name')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'company_name')
                ->toArray()
        ];
    }

    /**
     * Search suppliers
     */
    public function searchSuppliers(string $search, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return Supplier::where('supplier_name', 'like', '%' . $search . '%')
            ->orWhere('supplier_code', 'like', '%' . $search . '%')
            ->orWhere('email', 'like', '%' . $search . '%')
            ->where('status', 'active')
            ->with(['company', 'province', 'city'])
            ->orderBy('supplier_name')
            ->limit($limit)
            ->get();
    }

    /**
     * Get suppliers by company
     */
    public function getSuppliersByCompany(int $companyId): \Illuminate\Database\Eloquent\Collection
    {
        return Supplier::where('company_id', $companyId)
            ->where('status', 'active')
            ->with(['company', 'province', 'city'])
            ->orderBy('supplier_name')
            ->get();
    }

    /**
     * Get suppliers with expiring credit limits
     */
    public function getSuppliersWithExpiringCreditLimits(int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return Supplier::whereHas('creditLimits', function ($query) use ($days) {
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
    public function getSuppliersWithExpiringPaymentTerms(int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return Supplier::whereHas('paymentTerms', function ($query) use ($days) {
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
}
