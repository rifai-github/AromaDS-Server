<?php

namespace App\Services\Company;

use App\Models\Customer;
use App\Models\CustomerTax;
use Illuminate\Validation\ValidationException;

class CustomerIdentifierUniquenessService
{
    public function findCustomerUsingNib(?string $nib, ?int $ignoreCustomerId = null): ?Customer
    {
        $normalizedNib = $this->normalizeIdentifier($nib);

        if ($normalizedNib === '') {
            return null;
        }

        return Customer::query()
            ->select(['id', 'name', 'nib', 'nib_number'])
            ->where(function ($query) {
                $query->whereNotNull('nib')
                    ->orWhereNotNull('nib_number');
            })
            ->get()
            ->first(function (Customer $customer) use ($normalizedNib, $ignoreCustomerId) {
                if ($ignoreCustomerId && (int) $customer->id === $ignoreCustomerId) {
                    return false;
                }

                return $this->normalizeIdentifier($customer->nib) === $normalizedNib
                    || $this->normalizeIdentifier($customer->nib_number) === $normalizedNib;
            });
    }

    public function findCustomerUsingNpwp(?string $npwp, ?int $ignoreCustomerId = null): ?Customer
    {
        $normalizedNpwp = $this->normalizeIdentifier($npwp);

        if ($normalizedNpwp === '') {
            return null;
        }

        $legacyOwner = Customer::query()
            ->select(['id', 'name', 'npwp'])
            ->whereNotNull('npwp')
            ->get()
            ->first(function (Customer $customer) use ($normalizedNpwp, $ignoreCustomerId) {
                if ($ignoreCustomerId && (int) $customer->id === $ignoreCustomerId) {
                    return false;
                }

                return $this->normalizeIdentifier($customer->npwp) === $normalizedNpwp;
            });

        if ($legacyOwner) {
            return $legacyOwner;
        }

        $taxOwner = CustomerTax::query()
            ->with('customer:id,name')
            ->where('tax_name', 'NPWP')
            ->whereNotNull('tax_number')
            ->get()
            ->first(function (CustomerTax $tax) use ($normalizedNpwp, $ignoreCustomerId) {
                if ($ignoreCustomerId && (int) $tax->customer_id === $ignoreCustomerId) {
                    return false;
                }

                return $this->normalizeIdentifier($tax->tax_number) === $normalizedNpwp;
            });

        return $taxOwner?->customer;
    }

    public function validateUniqueNib(?string $nib, ?int $ignoreCustomerId = null, string $attribute = 'nib'): void
    {
        $owner = $this->findCustomerUsingNib($nib, $ignoreCustomerId);

        if (! $owner) {
            return;
        }

        throw ValidationException::withMessages([
            $attribute => "NIB ini sudah digunakan oleh customer: {$owner->name}.",
        ]);
    }

    public function validateUniqueNpwp(?string $npwp, ?int $ignoreCustomerId = null, string $attribute = 'tax_number'): void
    {
        $owner = $this->findCustomerUsingNpwp($npwp, $ignoreCustomerId);

        if (! $owner) {
            return;
        }

        throw ValidationException::withMessages([
            $attribute => "NPWP ini sudah digunakan oleh customer: {$owner->name}.",
        ]);
    }

    private function normalizeIdentifier(?string $value): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', (string) $value));
    }
}
