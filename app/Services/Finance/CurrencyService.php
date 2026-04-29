<?php

namespace App\Services\Finance;

use App\Models\Finance\Currency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class CurrencyService
{
    /**
     * Create currency
     */
    public function createCurrency(array $data): array
    {
        try {
            DB::beginTransaction();

            // Validate currency data
            $this->validateCurrencyData($data);

            // Check if currency code already exists
            if (Currency::where('code', $data['code'])->exists()) {
                throw new \Exception('Currency code already exists');
            }

            $currency = Currency::create([
                'code' => strtoupper($data['code']),
                'name' => $data['name'],
                'symbol' => $data['symbol'] ?? '',
                'exchange_rate' => $data['exchange_rate'] ?? 1.0000,
                'is_active' => $data['is_active'] ?? true,
            ]);

            DB::commit();

            return [
                'success' => true,
                'currency' => $currency,
                'message' => 'Currency created successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Currency creation failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to create currency: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update currency
     */
    public function updateCurrency(int $currencyId, array $data): array
    {
        try {
            DB::beginTransaction();

            $currency = Currency::findOrFail($currencyId);
            
            // Validate currency data
            $this->validateCurrencyData($data);

            // Check if currency code already exists (excluding current record)
            if (Currency::where('code', $data['code'])
                ->where('id', '!=', $currencyId)
                ->exists()) {
                throw new \Exception('Currency code already exists');
            }

            $currency->update([
                'code' => strtoupper($data['code']),
                'name' => $data['name'],
                'symbol' => $data['symbol'] ?? $currency->symbol,
                'exchange_rate' => $data['exchange_rate'] ?? $currency->exchange_rate,
                'is_active' => $data['is_active'] ?? $currency->is_active,
            ]);

            DB::commit();

            return [
                'success' => true,
                'currency' => $currency,
                'message' => 'Currency updated successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Currency update failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to update currency: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update exchange rates from external API
     */
    public function updateExchangeRates(): array
    {
        try {
            DB::beginTransaction();

            $baseCurrency = 'USD'; // Default base currency
            $currencies = Currency::where('is_active', true)->get();
            
            $updatedCount = 0;
            $errors = [];

            foreach ($currencies as $currency) {
                if ($currency->code === $baseCurrency) {
                    $currency->update(['exchange_rate' => 1.0000]);
                    $updatedCount++;
                    continue;
                }

                try {
                    $rate = $this->fetchExchangeRate($baseCurrency, $currency->code);
                    if ($rate > 0) {
                        $currency->update(['exchange_rate' => $rate]);
                        $updatedCount++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Failed to update rate for {$currency->code}: " . $e->getMessage();
                }
            }

            DB::commit();

            return [
                'success' => true,
                'updated_count' => $updatedCount,
                'errors' => $errors,
                'message' => "Successfully updated {$updatedCount} exchange rates"
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Exchange rate update failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to update exchange rates: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Fetch exchange rate from external API
     */
    private function fetchExchangeRate(string $from, string $to): float
    {
        // Using exchangerate-api.com (free tier)
        $response = Http::get("https://api.exchangerate-api.com/v4/latest/{$from}");
        
        if ($response->successful()) {
            $data = $response->json();
            return $data['rates'][$to] ?? 0;
        }

        // Fallback to fixer.io if available
        $apiKey = config('services.fixer.api_key');
        if ($apiKey) {
            $response = Http::get("http://data.fixer.io/api/latest", [
                'access_key' => $apiKey,
                'base' => $from,
                'symbols' => $to
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                return $data['rates'][$to] ?? 0;
            }
        }

        throw new \Exception('Unable to fetch exchange rate');
    }

    /**
     * Convert amount between currencies
     */
    public function convertCurrency(float $amount, string $fromCurrency, string $toCurrency): array
    {
        try {
            if ($fromCurrency === $toCurrency) {
                return [
                    'success' => true,
                    'original_amount' => $amount,
                    'converted_amount' => $amount,
                    'from_currency' => $fromCurrency,
                    'to_currency' => $toCurrency,
                    'exchange_rate' => 1.0000
                ];
            }

            $fromCurrencyModel = Currency::where('code', $fromCurrency)->first();
            $toCurrencyModel = Currency::where('code', $toCurrency)->first();

            if (!$fromCurrencyModel || !$toCurrencyModel) {
                throw new \Exception('One or both currencies not found');
            }

            if (!$fromCurrencyModel->is_active || !$toCurrencyModel->is_active) {
                throw new \Exception('One or both currencies are inactive');
            }

            // Convert to base currency (USD) first, then to target currency
            $baseAmount = $amount / $fromCurrencyModel->exchange_rate;
            $convertedAmount = $baseAmount * $toCurrencyModel->exchange_rate;
            $exchangeRate = $toCurrencyModel->exchange_rate / $fromCurrencyModel->exchange_rate;

            return [
                'success' => true,
                'original_amount' => $amount,
                'converted_amount' => round($convertedAmount, 2),
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency,
                'exchange_rate' => round($exchangeRate, 6)
            ];

        } catch (\Exception $e) {
            Log::error('Currency conversion failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to convert currency: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get currency by code
     */
    public function getCurrencyByCode(string $code): ?Currency
    {
        return Currency::where('code', strtoupper($code))->first();
    }

    /**
     * Get active currencies
     */
    public function getActiveCurrencies(): array
    {
        $currencies = Currency::where('is_active', true)
            ->orderBy('code')
            ->get();

        return [
            'success' => true,
            'currencies' => $currencies
        ];
    }

    /**
     * Activate currency
     */
    public function activateCurrency(int $currencyId): array
    {
        try {
            $currency = Currency::findOrFail($currencyId);
            
            $currency->update(['is_active' => true]);

            return [
                'success' => true,
                'message' => 'Currency activated successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Currency activation failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to activate currency: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Deactivate currency
     */
    public function deactivateCurrency(int $currencyId): array
    {
        try {
            $currency = Currency::findOrFail($currencyId);
            
            $currency->update(['is_active' => false]);

            return [
                'success' => true,
                'message' => 'Currency deactivated successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Currency deactivation failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to deactivate currency: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get currency statistics
     */
    public function getCurrencyStatistics(): array
    {
        $total = Currency::count();
        $active = Currency::where('is_active', true)->count();
        $inactive = Currency::where('is_active', false)->count();

        return [
            'total_currencies' => $total,
            'active_currencies' => $active,
            'inactive_currencies' => $inactive,
            'activation_rate' => $total > 0 ? ($active / $total) * 100 : 0,
        ];
    }

    /**
     * Validate currency data
     */
    private function validateCurrencyData(array $data): void
    {
        if (empty($data['code'])) {
            throw new \Exception('Currency code is required');
        }

        if (strlen($data['code']) !== 3) {
            throw new \Exception('Currency code must be exactly 3 characters');
        }

        if (!preg_match('/^[A-Z]{3}$/', strtoupper($data['code']))) {
            throw new \Exception('Currency code must be 3 uppercase letters');
        }

        if (empty($data['name'])) {
            throw new \Exception('Currency name is required');
        }

        if (isset($data['exchange_rate']) && $data['exchange_rate'] < 0) {
            throw new \Exception('Exchange rate cannot be negative');
        }
    }

    /**
     * Get currencies for dropdown/select
     */
    public function getCurrenciesForSelect(): array
    {
        return Currency::where('is_active', true)
            ->orderBy('code')
            ->pluck('name', 'code')
            ->toArray();
    }

    /**
     * Import currencies from array
     */
    public function importCurrencies(array $currenciesData): array
    {
        try {
            DB::beginTransaction();

            $imported = 0;
            $errors = [];

            foreach ($currenciesData as $index => $data) {
                try {
                    $this->validateCurrencyData($data);
                    
                    if (!Currency::where('code', strtoupper($data['code']))->exists()) {
                        Currency::create([
                            'code' => strtoupper($data['code']),
                            'name' => $data['name'],
                            'symbol' => $data['symbol'] ?? '',
                            'exchange_rate' => $data['exchange_rate'] ?? 1.0000,
                            'is_active' => $data['is_active'] ?? true,
                        ]);
                        $imported++;
                    } else {
                        $errors[] = "Row " . ($index + 1) . ": Currency code '{$data['code']}' already exists";
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
                'message' => "Successfully imported {$imported} currencies"
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Currency import failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to import currencies: ' . $e->getMessage()
            ];
        }
    }
}
