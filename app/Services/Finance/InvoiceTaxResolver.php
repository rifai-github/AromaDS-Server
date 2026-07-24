<?php

namespace App\Services\Finance;

use App\Models\Customer;
use App\Models\CustomerTax;
use App\Models\FinanceTaxCode;
use App\Models\TaxSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Single source of truth for "does PPN apply to this invoice, and at what rate".
 *
 * Before this existed the answer was derived two different ways: the manual
 * invoice screens asked FinanceTaxCode::appliesPpnToInvoice() while the
 * auto-generation services only looked at the customer's tax_obligation flag.
 * The same invoice could therefore carry a different tax amount depending on
 * which code path touched it last. Everything now goes through resolve().
 */
class InvoiceTaxResolver
{
    public const DEFAULT_TAX_CODE = '01';

    /**
     * Resolve the tax context for a customer at a given date.
     *
     * @return array{
     *     customer_tax: ?CustomerTax,
     *     finance_tax_code: ?FinanceTaxCode,
     *     default_vat_setting: ?TaxSetting,
     *     tax_code: string,
     *     applies_ppn: bool,
     *     rate: float,
     *     date: Carbon
     * }
     */
    public function resolve(?Customer $customer, ?string $requestedTaxCode = null, $date = null): array
    {
        $taxDate = $date ? Carbon::parse($date) : now();

        $customerTax = $customer
            ? CustomerTax::query()
                ->where('customer_id', $customer->id)
                ->where('is_active', true)
                ->where('effective_date', '<=', $taxDate)
                ->where(function ($query) use ($taxDate) {
                    $query->whereNull('expiry_date')
                        ->orWhere('expiry_date', '>=', $taxDate);
                })
                ->orderByDesc('effective_date')
                ->orderByDesc('id')
                ->first()
            : null;

        $resolvedTaxCode = $requestedTaxCode
            ?: $customerTax?->ppn_code
            ?: $customer?->ppn_code
            ?: self::DEFAULT_TAX_CODE;

        $financeTaxCode = $this->findActiveTaxCode($resolvedTaxCode)
            ?? $this->findActiveTaxCode(self::DEFAULT_TAX_CODE);

        if (! $financeTaxCode) {
            // Neither the requested code nor the '01' fallback exists/is active.
            // We cannot invent a tax rule, so no PPN is charged — but this is a
            // master-data problem and must not pass silently.
            Log::warning('No active finance tax code resolved; invoice will carry no PPN.', [
                'requested_code' => $requestedTaxCode,
                'resolved_code' => $resolvedTaxCode,
                'customer_id' => $customer?->id,
                'date' => $taxDate->toDateString(),
            ]);
        }

        $defaultVatSetting = TaxSetting::getDefaultPpnSetting($taxDate);
        $appliesPpn = (bool) $financeTaxCode?->appliesPpnToInvoice();

        return [
            'customer_tax' => $customerTax,
            'finance_tax_code' => $financeTaxCode,
            'default_vat_setting' => $defaultVatSetting,
            'tax_code' => $financeTaxCode?->code ?: $resolvedTaxCode,
            'applies_ppn' => $appliesPpn,
            'rate' => $appliesPpn ? TaxSetting::getEffectivePpnRate($taxDate) : 0.0,
            'date' => $taxDate,
        ];
    }

    /**
     * PPN amount for a base (already discounted) amount, using a resolved context.
     */
    public function taxAmount(float $baseAmount, array $context): float
    {
        return round(max($baseAmount, 0) * (float) $context['rate'], 2);
    }

    private function findActiveTaxCode(?string $code): ?FinanceTaxCode
    {
        if (! $code) {
            return null;
        }

        return FinanceTaxCode::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->first();
    }
}
