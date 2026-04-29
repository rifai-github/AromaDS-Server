<?php

namespace App\Services\Company;

use App\Models\Customer;
use App\Models\CustomerCategory;
use App\Models\CustomerCreditLimit;
use App\Models\CustomerPaymentTerm;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CustomerService
{
    protected $customerRepository;

    public function __construct(CustomerRepository $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }

    /**
     * Create a new customer
     */
    public function createCustomer(array $data): Customer
    {
        return DB::transaction(function () use ($data) {
            $customer = Customer::create([
                'company_id' => $data['company_id'],
                'customer_code' => $data['customer_code'],
                'customer_name' => $data['customer_name'],
                'customer_type' => $data['customer_type'] ?? 'individual',
                'company_type' => $data['company_type'] ?? 'PT', // Default company type
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
                'assigned_to' => $data['assigned_to'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id()
            ]);

            // Create default credit limit if provided
            if (isset($data['credit_limit']) && $data['credit_limit'] > 0) {
                $this->createCreditLimit($customer, [
                    'credit_limit' => $data['credit_limit'],
                    'currency' => $data['currency'] ?? 'IDR',
                    'valid_from' => now(),
                    'valid_to' => now()->addYear(),
                    'is_active' => true
                ]);
            }

            // Create default payment terms if provided
            if (isset($data['payment_terms'])) {
                $this->createPaymentTerms($customer, [
                    'payment_terms' => $data['payment_terms'],
                    'currency' => $data['currency'] ?? 'IDR',
                    'valid_from' => now(),
                    'valid_to' => now()->addYear(),
                    'is_active' => true
                ]);
            }

            return $customer;
        });
    }

    /**
     * Update customer information
     */
    public function updateCustomer(Customer $customer, array $data): Customer
    {
        return DB::transaction(function () use ($customer, $data) {
            $customer->update([
                'customer_code' => $data['customer_code'],
                'customer_name' => $data['customer_name'],
                'customer_type' => $data['customer_type'] ?? $customer->customer_type,
                'contact_person' => $data['contact_person'] ?? $customer->contact_person,
                'email' => $data['email'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'province_id' => $data['province_id'],
                'city_id' => $data['city_id'],
                'district_id' => $data['district_id'] ?? $customer->district_id,
                'subdistrict_id' => $data['subdistrict_id'] ?? $customer->subdistrict_id,
                'postal_code' => $data['postal_code'] ?? $customer->postal_code,
                'tax_number' => $data['tax_number'] ?? $customer->tax_number,
                'website' => $data['website'] ?? $customer->website,
                'industry' => $data['industry'] ?? $customer->industry,
                'company_size' => $data['company_size'] ?? $customer->company_size,
                'annual_revenue' => $data['annual_revenue'] ?? $customer->annual_revenue,
                'source' => $data['source'] ?? $customer->source,
                'status' => $data['status'] ?? $customer->status,
                'assigned_to' => $data['assigned_to'] ?? $customer->assigned_to,
                'notes' => $data['notes'] ?? $customer->notes,
                'updated_by' => Auth::id()
            ]);

            return $customer;
        });
    }

    /**
     * Delete customer
     */
    public function deleteCustomer(Customer $customer): bool
    {
        return DB::transaction(function () use ($customer) {
            // Check if customer can be deleted
            $this->validateCustomerDeletion($customer);

            // Delete related data
            $customer->creditLimits()->delete();
            $customer->paymentTerms()->delete();

            // Delete customer
            return $customer->delete();
        });
    }

    /**
     * Create customer credit limit
     */
    public function createCreditLimit(Customer $customer, array $data): CustomerCreditLimit
    {
        return $customer->creditLimits()->create([
            'credit_limit' => $data['credit_limit'],
            'currency' => $data['currency'],
            'valid_from' => $data['valid_from'],
            'valid_to' => $data['valid_to'],
            'notes' => $data['notes'] ?? null,
            'is_active' => $data['is_active'] ?? true
        ]);
    }

    /**
     * Update customer credit limit
     */
    public function updateCreditLimit(Customer $customer, CustomerCreditLimit $creditLimit, array $data): CustomerCreditLimit
    {
        if ($creditLimit->customer_id !== $customer->id) {
            throw new \Exception('Credit limit not found for this customer.');
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
     * Delete customer credit limit
     */
    public function deleteCreditLimit(Customer $customer, CustomerCreditLimit $creditLimit): bool
    {
        if ($creditLimit->customer_id !== $customer->id) {
            throw new \Exception('Credit limit not found for this customer.');
        }

        return $creditLimit->delete();
    }

    /**
     * Create customer payment terms
     */
    public function createPaymentTerms(Customer $customer, array $data): CustomerPaymentTerm
    {
        return $customer->paymentTerms()->create([
            'payment_terms' => $data['payment_terms'],
            'currency' => $data['currency'],
            'valid_from' => $data['valid_from'],
            'valid_to' => $data['valid_to'],
            'notes' => $data['notes'] ?? null,
            'is_active' => $data['is_active'] ?? true
        ]);
    }

    /**
     * Update customer payment terms
     */
    public function updatePaymentTerms(Customer $customer, CustomerPaymentTerm $paymentTerm, array $data): CustomerPaymentTerm
    {
        if ($paymentTerm->customer_id !== $customer->id) {
            throw new \Exception('Payment terms not found for this customer.');
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
     * Delete customer payment terms
     */
    public function deletePaymentTerms(Customer $customer, CustomerPaymentTerm $paymentTerm): bool
    {
        if ($paymentTerm->customer_id !== $customer->id) {
            throw new \Exception('Payment terms not found for this customer.');
        }

        return $paymentTerm->delete();
    }

    /**
     * Get customer dashboard statistics
     */
    public function getDashboardStatistics(Customer $customer): array
    {
        return [
            'credit_limits_count' => $customer->creditLimits()->count(),
            'payment_terms_count' => $customer->paymentTerms()->count(),
            'surveys_count' => $customer->surveys()->count(),
            'quotations_count' => $customer->quotations()->count(),
            'contracts_count' => $customer->contracts()->count(),
            'active_credit_limits' => $customer->creditLimits()
                ->where('is_active', true)
                ->where('valid_from', '<=', now())
                ->where('valid_to', '>=', now())
                ->get(),
            'active_payment_terms' => $customer->paymentTerms()
                ->where('is_active', true)
                ->where('valid_from', '<=', now())
                ->where('valid_to', '>=', now())
                ->get(),
            'recent_surveys' => $customer->surveys()
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
            'recent_quotations' => $customer->quotations()
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
            'recent_contracts' => $customer->contracts()
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
        ];
    }

    /**
     * Bulk delete customers
     */
    public function bulkDelete(array $customerIds): array
    {
        $deletedCount = 0;
        $errors = [];

        foreach ($customerIds as $customerId) {
            try {
                $customer = Customer::find($customerId);
                
                if ($customer) {
                    $this->deleteCustomer($customer);
                    $deletedCount++;
                }
            } catch (\Exception $e) {
                $errors[] = "Customer ID {$customerId}: " . $e->getMessage();
            }
        }

        return [
            'deleted_count' => $deletedCount,
            'errors' => $errors
        ];
    }

    /**
     * Bulk update customer status
     */
    public function bulkUpdateStatus(array $customerIds, string $status): int
    {
        return Customer::whereIn('id', $customerIds)
            ->update(['status' => $status]);
    }

    /**
     * Toggle customer status
     */
    public function toggleStatus(Customer $customer): string
    {
        $newStatus = $customer->status === 'active' ? 'inactive' : 'active';
        $customer->update(['status' => $newStatus]);
        
        return $newStatus;
    }

    /**
     * Validate if customer can be deleted
     */
    protected function validateCustomerDeletion(Customer $customer): void
    {
        $hasSurveys = $customer->surveys()->exists();
        $hasQuotations = $customer->quotations()->exists();
        $hasContracts = $customer->contracts()->exists();

        if ($hasSurveys) {
            throw new \Exception('Cannot delete customer that still has surveys.');
        }

        if ($hasQuotations) {
            throw new \Exception('Cannot delete customer that still has quotations.');
        }

        if ($hasContracts) {
            throw new \Exception('Cannot delete customer that still has contracts.');
        }
    }

    /**
     * Get customer statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_customers' => Customer::count(),
            'active_customers' => Customer::where('status', 'active')->count(),
            'customers_by_type' => Customer::selectRaw('customer_type, COUNT(*) as count')
                ->groupBy('customer_type')
                ->pluck('count', 'customer_type')
                ->toArray(),
            'customers_by_industry' => Customer::selectRaw('industry, COUNT(*) as count')
                ->whereNotNull('industry')
                ->groupBy('industry')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'industry')
                ->toArray(),
            'customers_by_source' => Customer::selectRaw('source, COUNT(*) as count')
                ->whereNotNull('source')
                ->groupBy('source')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'source')
                ->toArray(),
            'customers_by_company' => Customer::selectRaw('companies.name as company_name, COUNT(customers.id) as count')
                ->join('companies', 'customers.company_id', '=', 'companies.id')
                ->groupBy('companies.id', 'companies.name')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'company_name')
                ->toArray()
        ];
    }

    /**
     * Search customers
     */
    public function searchCustomers(string $search, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return Customer::where('customer_name', 'like', '%' . $search . '%')
            ->orWhere('customer_code', 'like', '%' . $search . '%')
            ->orWhere('email', 'like', '%' . $search . '%')
            ->where('status', 'active')
            ->with(['company', 'province', 'city'])
            ->orderBy('customer_name')
            ->limit($limit)
            ->get();
    }

    /**
     * Get customers by company
     */
    public function getCustomersByCompany(int $companyId): \Illuminate\Database\Eloquent\Collection
    {
        return Customer::where('company_id', $companyId)
            ->where('status', 'active')
            ->with(['company', 'province', 'city'])
            ->orderBy('customer_name')
            ->get();
    }

    /**
     * Get customers by assigned user
     */
    public function getCustomersByAssignedUser(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return Customer::where('assigned_to', $userId)
            ->where('status', 'active')
            ->with(['company', 'province', 'city'])
            ->orderBy('customer_name')
            ->get();
    }

    /**
     * Get customers with expiring credit limits
     */
    public function getCustomersWithExpiringCreditLimits(int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return Customer::whereHas('creditLimits', function ($query) use ($days) {
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
    public function getCustomersWithExpiringPaymentTerms(int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return Customer::whereHas('paymentTerms', function ($query) use ($days) {
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
}
