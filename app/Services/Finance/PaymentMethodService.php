<?php

namespace App\Services\Finance;

use App\Models\Finance\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentMethodService
{
    /**
     * Create payment method
     */
    public function createPaymentMethod(array $data): array
    {
        try {
            DB::beginTransaction();

            // Validate payment method data
            $this->validatePaymentMethodData($data);

            // Check if code already exists
            if (PaymentMethod::where('code', $data['code'])->exists()) {
                throw new \Exception('Payment method code already exists');
            }

            $paymentMethod = PaymentMethod::create([
                'name' => $data['name'],
                'code' => strtoupper($data['code']),
                'is_active' => $data['is_active'] ?? true,
            ]);

            DB::commit();

            return [
                'success' => true,
                'payment_method' => $paymentMethod,
                'message' => 'Payment method created successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment method creation failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to create payment method: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update payment method
     */
    public function updatePaymentMethod(int $paymentMethodId, array $data): array
    {
        try {
            DB::beginTransaction();

            $paymentMethod = PaymentMethod::findOrFail($paymentMethodId);
            
            // Validate payment method data
            $this->validatePaymentMethodData($data);

            // Check if code already exists (excluding current record)
            if (PaymentMethod::where('code', $data['code'])
                ->where('id', '!=', $paymentMethodId)
                ->exists()) {
                throw new \Exception('Payment method code already exists');
            }

            $paymentMethod->update([
                'name' => $data['name'],
                'code' => strtoupper($data['code']),
                'is_active' => $data['is_active'] ?? $paymentMethod->is_active,
            ]);

            DB::commit();

            return [
                'success' => true,
                'payment_method' => $paymentMethod,
                'message' => 'Payment method updated successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment method update failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to update payment method: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Activate payment method
     */
    public function activatePaymentMethod(int $paymentMethodId): array
    {
        try {
            $paymentMethod = PaymentMethod::findOrFail($paymentMethodId);
            
            $paymentMethod->update(['is_active' => true]);

            return [
                'success' => true,
                'message' => 'Payment method activated successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Payment method activation failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to activate payment method: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Deactivate payment method
     */
    public function deactivatePaymentMethod(int $paymentMethodId): array
    {
        try {
            $paymentMethod = PaymentMethod::findOrFail($paymentMethodId);
            
            $paymentMethod->update(['is_active' => false]);

            return [
                'success' => true,
                'message' => 'Payment method deactivated successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Payment method deactivation failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to deactivate payment method: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Bulk activate payment methods
     */
    public function bulkActivatePaymentMethods(array $paymentMethodIds): array
    {
        try {
            DB::beginTransaction();

            $updated = PaymentMethod::whereIn('id', $paymentMethodIds)
                ->update(['is_active' => true]);

            DB::commit();

            return [
                'success' => true,
                'updated_count' => $updated,
                'message' => "Successfully activated {$updated} payment methods"
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk payment method activation failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to activate payment methods: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Bulk deactivate payment methods
     */
    public function bulkDeactivatePaymentMethods(array $paymentMethodIds): array
    {
        try {
            DB::beginTransaction();

            $updated = PaymentMethod::whereIn('id', $paymentMethodIds)
                ->update(['is_active' => false]);

            DB::commit();

            return [
                'success' => true,
                'updated_count' => $updated,
                'message' => "Successfully deactivated {$updated} payment methods"
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk payment method deactivation failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to deactivate payment methods: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get active payment methods
     */
    public function getActivePaymentMethods(): array
    {
        $paymentMethods = PaymentMethod::where('is_active', true)
            ->orderBy('name')
            ->get();

        return [
            'success' => true,
            'payment_methods' => $paymentMethods
        ];
    }

    /**
     * Get payment method statistics
     */
    public function getPaymentMethodStatistics(): array
    {
        $total = PaymentMethod::count();
        $active = PaymentMethod::where('is_active', true)->count();
        $inactive = PaymentMethod::where('is_active', false)->count();

        return [
            'total_payment_methods' => $total,
            'active_payment_methods' => $active,
            'inactive_payment_methods' => $inactive,
            'activation_rate' => $total > 0 ? ($active / $total) * 100 : 0,
        ];
    }

    /**
     * Validate payment method data
     */
    private function validatePaymentMethodData(array $data): void
    {
        if (empty($data['name'])) {
            throw new \Exception('Payment method name is required');
        }

        if (empty($data['code'])) {
            throw new \Exception('Payment method code is required');
        }

        if (strlen($data['code']) < 2 || strlen($data['code']) > 10) {
            throw new \Exception('Payment method code must be between 2 and 10 characters');
        }

        if (!preg_match('/^[A-Z0-9_]+$/', strtoupper($data['code']))) {
            throw new \Exception('Payment method code can only contain uppercase letters, numbers, and underscores');
        }
    }

    /**
     * Get payment method by code
     */
    public function getPaymentMethodByCode(string $code): ?PaymentMethod
    {
        return PaymentMethod::where('code', strtoupper($code))->first();
    }

    /**
     * Check if payment method is active
     */
    public function isPaymentMethodActive(string $code): bool
    {
        $paymentMethod = $this->getPaymentMethodByCode($code);
        return $paymentMethod ? $paymentMethod->is_active : false;
    }

    /**
     * Get payment methods for dropdown/select
     */
    public function getPaymentMethodsForSelect(): array
    {
        return PaymentMethod::where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Import payment methods from array
     */
    public function importPaymentMethods(array $paymentMethodsData): array
    {
        try {
            DB::beginTransaction();

            $imported = 0;
            $errors = [];

            foreach ($paymentMethodsData as $index => $data) {
                try {
                    $this->validatePaymentMethodData($data);
                    
                    if (!PaymentMethod::where('code', strtoupper($data['code']))->exists()) {
                        PaymentMethod::create([
                            'name' => $data['name'],
                            'code' => strtoupper($data['code']),
                            'is_active' => $data['is_active'] ?? true,
                        ]);
                        $imported++;
                    } else {
                        $errors[] = "Row " . ($index + 1) . ": Payment method code '{$data['code']}' already exists";
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
                'message' => "Successfully imported {$imported} payment methods"
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment method import failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to import payment methods: ' . $e->getMessage()
            ];
        }
    }
}
