<?php

namespace Tests\Unit;

use App\Models\Contract;
use App\Models\Quotation;
use Tests\TestCase;

class ContractTopIntervalMonthsTest extends TestCase
{
    public function test_contract_uses_quotation_top_months_for_per_contract_period_terms(): void
    {
        $contract = new Contract([
            'term_of_payment' => '3x per periode contract',
        ]);

        $contract->setRelation('quotation', new Quotation([
            'top_months' => 6,
        ]));

        $this->assertSame(6, $contract->top_interval_months);
    }

    public function test_contract_treats_multi_advance_terms_as_contract_installment_count(): void
    {
        $threeTimesAdvance = new Contract([
            'term_of_payment' => '3x advance',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $fourTimesAdvance = new Contract([
            'term_of_payment' => '4x Advance',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $this->assertSame(4, $threeTimesAdvance->top_interval_months);
        $this->assertSame(3, $fourTimesAdvance->top_interval_months);
    }
}
