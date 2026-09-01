<?php

namespace Tests\Feature;

use App\Http\Controllers\Marketing\LostUnitReportController;
use App\Models\LostUnitReport;
use ReflectionMethod;
use Tests\TestCase;

/**
 * QA, 31 Aug 2026: a lost unit report charged to the customer had its price raised from
 * 2.000.000 to 2.500.000, but the generated invoice still billed 2.000.000 (PPN 220.000,
 * grand total 2.220.000) while its own detail row showed 2.500.000.
 *
 * Two things went wrong and both are covered here:
 *
 * 1. "Nominal Charge" (charge_amount) is seeded from the lost-unit total, but editing an
 *    item price only updated the total - the charge was left on the old figure, and the
 *    invoice header bills the charge.
 * 2. The invoice header and its detail rows were built from different numbers, so they
 *    could disagree at all. The rows are now made to add up to the charged amount.
 */
class LostUnitInvoiceAmountTest extends TestCase
{
    private function invokeControllerMethod(string $method, array $args)
    {
        $reflection = new ReflectionMethod(LostUnitReportController::class, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke(new LostUnitReportController, ...$args);
    }

    private function chargeTracksTotal(?float $chargeAmount, float $previousTotal): bool
    {
        $report = new LostUnitReport(['charge_amount' => $chargeAmount]);

        return $this->invokeControllerMethod('chargeAmountTracksTotal', [$report, $previousTotal]);
    }

    public function test_a_charge_still_equal_to_the_old_total_follows_the_new_price(): void
    {
        $this->assertTrue($this->chargeTracksTotal(2000000.0, 2000000.0));
    }

    public function test_an_unset_charge_follows_the_total(): void
    {
        $this->assertTrue($this->chargeTracksTotal(null, 2000000.0));
        $this->assertTrue($this->chargeTracksTotal(0.0, 2000000.0));
    }

    public function test_a_deliberately_different_charge_is_left_alone(): void
    {
        $this->assertFalse($this->chargeTracksTotal(1500000.0, 2000000.0));
    }

    private function distribute(array $prices, float $charge): array
    {
        $lines = array_map(fn ($price) => ['total_price' => (float) $price], $prices);

        return array_column(
            $this->invokeControllerMethod('distributeChargeAcrossLines', [$lines, $charge]),
            'total_price'
        );
    }

    public function test_lines_are_untouched_when_they_already_match_the_charge(): void
    {
        $this->assertSame([2500000.0], $this->distribute([2500000], 2500000));
        $this->assertSame([1000000.0, 500000.0], $this->distribute([1000000, 500000], 1500000));
    }

    public function test_a_single_line_takes_the_whole_charge(): void
    {
        $this->assertSame([2500000.0], $this->distribute([2000000], 2500000));
    }

    public function test_a_partial_charge_is_split_in_proportion_to_the_line_prices(): void
    {
        $this->assertSame([600000.0, 300000.0, 100000.0], $this->distribute([1200000, 600000, 200000], 1000000));
    }

    public function test_rounding_remainder_lands_on_the_last_line_so_the_rows_still_sum_to_the_charge(): void
    {
        $lines = $this->distribute([1, 1, 1], 100);

        $this->assertSame(100.0, round(array_sum($lines), 2));
        $this->assertSame([33.33, 33.33, 33.34], $lines);
    }

    public function test_a_zero_priced_report_is_not_divided_by_zero(): void
    {
        $this->assertSame([0.0], $this->distribute([0], 2500000));
    }
}
