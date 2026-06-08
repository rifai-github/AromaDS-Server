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
}
