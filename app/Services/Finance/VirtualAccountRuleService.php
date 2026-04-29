<?php

namespace App\Services\Finance;

use App\Models\Finance\BillingGroup;
use App\Models\Finance\VirtualAccount;
use App\Models\CompanyVirtualAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VirtualAccountRuleService
{
    /**
     * VA Rule: 5 digit company code + 6 digit free digits = 11 digit total
     */
    const COMPANY_CODE_LENGTH = 5;
    const FREE_DIGITS_LENGTH = 6;
    const TOTAL_VA_LENGTH = 11;
    const DEFAULT_COMPANY_CODE = '88997';

    /**
     * Generate VA number following the 6-digit rule
     */
    public function generateVirtualAccountNumber(string $companyCode = null): array
    {
        try {
            $companyCode = $companyCode ?? self::DEFAULT_COMPANY_CODE;
            
            // Validate company code format
            if (!$this->validateCompanyCode($companyCode)) {
                return [
                    'success' => false,
                    'message' => 'Company code must be exactly 5 digits',
                    'error_code' => 'INVALID_COMPANY_CODE'
                ];
            }

            // Get next available 6-digit sequence
            $nextSequence = $this->getNextAvailableSequence($companyCode);
            
            if (!$nextSequence) {
                return [
                    'success' => false,
                    'message' => 'No available VA numbers for this company code',
                    'error_code' => 'NO_AVAILABLE_SEQUENCE'
                ];
            }

            $fullVaNumber = $companyCode . $nextSequence;
            
            // Validate the complete VA number
            if (!$this->validateVirtualAccountNumber($fullVaNumber)) {
                return [
                    'success' => false,
                    'message' => 'Generated VA number is invalid',
                    'error_code' => 'INVALID_VA_NUMBER'
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'full_va_number' => $fullVaNumber,
                    'company_code' => $companyCode,
                    'free_digits' => $nextSequence,
                    'total_digits' => self::TOTAL_VA_LENGTH,
                    'is_available' => true
                ],
                'message' => 'Virtual Account number generated successfully'
            ];

        } catch (\Exception $e) {
            Log::error('VA generation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to generate VA number: ' . $e->getMessage(),
                'error_code' => 'GENERATION_ERROR'
            ];
        }
    }

    /**
     * Validate VA number format
     */
    public function validateVirtualAccountNumber(string $vaNumber): array
    {
        try {
            // Check length
            if (strlen($vaNumber) !== self::TOTAL_VA_LENGTH) {
                return [
                    'success' => false,
                    'message' => "VA number must be exactly {self::TOTAL_VA_LENGTH} digits",
                    'error_code' => 'INVALID_LENGTH'
                ];
            }

            // Check if all digits
            if (!ctype_digit($vaNumber)) {
                return [
                    'success' => false,
                    'message' => 'VA number must contain only digits',
                    'error_code' => 'NON_NUMERIC'
                ];
            }

            // Extract company code and free digits
            $companyCode = substr($vaNumber, 0, self::COMPANY_CODE_LENGTH);
            $freeDigits = substr($vaNumber, self::COMPANY_CODE_LENGTH, self::FREE_DIGITS_LENGTH);

            // Validate company code
            if (!$this->validateCompanyCode($companyCode)) {
                return [
                    'success' => false,
                    'message' => 'Invalid company code format',
                    'error_code' => 'INVALID_COMPANY_CODE'
                ];
            }

            // Check if VA number already exists
            $existingVa = $this->checkExistingVirtualAccount($vaNumber);
            if ($existingVa) {
                return [
                    'success' => false,
                    'message' => 'Virtual Account number already exists',
                    'error_code' => 'ALREADY_EXISTS',
                    'existing_data' => $existingVa
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'full_va_number' => $vaNumber,
                    'company_code' => $companyCode,
                    'free_digits' => $freeDigits,
                    'total_digits' => self::TOTAL_VA_LENGTH,
                    'is_available' => true
                ],
                'message' => 'Virtual Account number is valid and available'
            ];

        } catch (\Exception $e) {
            Log::error('VA validation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to validate VA number: ' . $e->getMessage(),
                'error_code' => 'VALIDATION_ERROR'
            ];
        }
    }

    /**
     * Get available VA numbers for a company
     */
    public function getAvailableVirtualAccounts(string $companyCode = null, int $limit = 10): array
    {
        try {
            $companyCode = $companyCode ?? self::DEFAULT_COMPANY_CODE;
            
            if (!$this->validateCompanyCode($companyCode)) {
                return [
                    'success' => false,
                    'message' => 'Invalid company code format',
                    'error_code' => 'INVALID_COMPANY_CODE'
                ];
            }

            $availableNumbers = [];
            $currentSequence = $this->getNextAvailableSequence($companyCode);
            
            for ($i = 0; $i < $limit; $i++) {
                $sequence = str_pad((int)$currentSequence + $i, self::FREE_DIGITS_LENGTH, '0', STR_PAD_LEFT);
                $vaNumber = $companyCode . $sequence;
                
                // Check if this VA number is available
                if (!$this->checkExistingVirtualAccount($vaNumber)) {
                    $availableNumbers[] = [
                        'full_va_number' => $vaNumber,
                        'company_code' => $companyCode,
                        'free_digits' => $sequence,
                        'total_digits' => self::TOTAL_VA_LENGTH
                    ];
                }
            }

            return [
                'success' => true,
                'data' => $availableNumbers,
                'count' => count($availableNumbers),
                'message' => 'Available Virtual Account numbers retrieved successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Get available VA numbers failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get available VA numbers: ' . $e->getMessage(),
                'error_code' => 'RETRIEVAL_ERROR'
            ];
        }
    }

    /**
     * Validate company code format
     */
    private function validateCompanyCode(string $companyCode): bool
    {
        return strlen($companyCode) === self::COMPANY_CODE_LENGTH && ctype_digit($companyCode);
    }

    /**
     * Get next available 6-digit sequence for company code
     */
    private function getNextAvailableSequence(string $companyCode): ?string
    {
        // Check in BillingGroup table
        $lastBillingGroup = BillingGroup::where('virtual_account_number', 'like', $companyCode . '%')
            ->orderBy('virtual_account_number', 'desc')
            ->first();

        // Check in VirtualAccount table
        $lastVirtualAccount = VirtualAccount::where('va_number', 'like', $companyCode . '%')
            ->orderBy('va_number', 'desc')
            ->first();

        // Check in CompanyVirtualAccount table
        $lastCompanyVa = CompanyVirtualAccount::where('account_number', 'like', $companyCode . '%')
            ->orderBy('account_number', 'desc')
            ->first();

        // Get the highest sequence from all tables
        $maxSequence = 0;
        
        if ($lastBillingGroup) {
            $sequence = (int)substr($lastBillingGroup->virtual_account_number, self::COMPANY_CODE_LENGTH);
            $maxSequence = max($maxSequence, $sequence);
        }
        
        if ($lastVirtualAccount) {
            $sequence = (int)substr($lastVirtualAccount->va_number, self::COMPANY_CODE_LENGTH);
            $maxSequence = max($maxSequence, $sequence);
        }
        
        if ($lastCompanyVa) {
            $sequence = (int)substr($lastCompanyVa->account_number, self::COMPANY_CODE_LENGTH);
            $maxSequence = max($maxSequence, $sequence);
        }

        // Check if we've reached the maximum (999999)
        if ($maxSequence >= 999999) {
            return null;
        }

        return str_pad($maxSequence + 1, self::FREE_DIGITS_LENGTH, '0', STR_PAD_LEFT);
    }

    /**
     * Check if virtual account already exists
     */
    private function checkExistingVirtualAccount(string $vaNumber): ?array
    {
        // Check in BillingGroup
        $billingGroup = BillingGroup::where('virtual_account_number', $vaNumber)->first();
        if ($billingGroup) {
            return [
                'type' => 'billing_group',
                'id' => $billingGroup->id,
                'name' => $billingGroup->billing_group_name,
                'table' => 'billing_groups'
            ];
        }

        // Check in VirtualAccount
        $virtualAccount = VirtualAccount::where('va_number', $vaNumber)->first();
        if ($virtualAccount) {
            return [
                'type' => 'virtual_account',
                'id' => $virtualAccount->id,
                'name' => $virtualAccount->va_name,
                'table' => 'virtual_accounts'
            ];
        }

        // Check in CompanyVirtualAccount
        $companyVa = CompanyVirtualAccount::where('account_number', $vaNumber)->first();
        if ($companyVa) {
            return [
                'type' => 'company_virtual_account',
                'id' => $companyVa->id,
                'name' => $companyVa->account_name,
                'table' => 'company_virtual_accounts'
            ];
        }

        return null;
    }

    /**
     * Get VA number statistics
     */
    public function getVirtualAccountStatistics(string $companyCode = null): array
    {
        try {
            $companyCode = $companyCode ?? self::DEFAULT_COMPANY_CODE;
            
            if (!$this->validateCompanyCode($companyCode)) {
                return [
                    'success' => false,
                    'message' => 'Invalid company code format',
                    'error_code' => 'INVALID_COMPANY_CODE'
                ];
            }

            $billingGroupCount = BillingGroup::where('virtual_account_number', 'like', $companyCode . '%')->count();
            $virtualAccountCount = VirtualAccount::where('va_number', 'like', $companyCode . '%')->count();
            $companyVaCount = CompanyVirtualAccount::where('account_number', 'like', $companyCode . '%')->count();
            
            $totalUsed = $billingGroupCount + $virtualAccountCount + $companyVaCount;
            $maxAvailable = 999999; // 6-digit maximum
            $available = $maxAvailable - $totalUsed;

            return [
                'success' => true,
                'data' => [
                    'company_code' => $companyCode,
                    'total_used' => $totalUsed,
                    'available' => max(0, $available),
                    'max_available' => $maxAvailable,
                    'usage_percentage' => round(($totalUsed / $maxAvailable) * 100, 2),
                    'breakdown' => [
                        'billing_groups' => $billingGroupCount,
                        'virtual_accounts' => $virtualAccountCount,
                        'company_virtual_accounts' => $companyVaCount
                    ]
                ],
                'message' => 'Virtual Account statistics retrieved successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Get VA statistics failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get VA statistics: ' . $e->getMessage(),
                'error_code' => 'STATISTICS_ERROR'
            ];
        }
    }

    /**
     * Reserve VA number (mark as reserved but not yet used)
     */
    public function reserveVirtualAccountNumber(string $vaNumber, string $reservedBy = null): array
    {
        try {
            $validation = $this->validateVirtualAccountNumber($vaNumber);
            
            if (!$validation['success']) {
                return $validation;
            }

            // Create a temporary reservation record
            // This could be stored in a reservations table or cache
            $reservation = [
                'va_number' => $vaNumber,
                'reserved_by' => $reservedBy ?? auth()->id(),
                'reserved_at' => now(),
                'expires_at' => now()->addMinutes(30) // 30 minutes reservation
            ];

            // Store in cache or temporary table
            cache()->put("va_reservation_{$vaNumber}", $reservation, 30 * 60);

            return [
                'success' => true,
                'data' => $reservation,
                'message' => 'Virtual Account number reserved successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Reserve VA number failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to reserve VA number: ' . $e->getMessage(),
                'error_code' => 'RESERVATION_ERROR'
            ];
        }
    }
}
