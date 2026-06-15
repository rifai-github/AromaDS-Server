<?php

namespace Tests\Unit;

use App\Models\MasterRental;
use App\Models\Quotation;
use App\Models\QuotationDetail;
use App\Models\RentalServiceFrequency;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class QuotationServiceFrequencyPeriodTest extends TestCase
{
    public function test_quarterly_service_frequency_rejects_non_multiple_rental_period(): void
    {
        $quotation = $this->quotationWithRentalPeriodAndFrequency(4, 3);

        $errors = $quotation->getServiceFrequencyPeriodValidationErrors();

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('periode sewa 4 bulan', $errors[0]);
        $this->assertStringContainsString('kelipatan 3 bulan', $errors[0]);

        $this->expectException(ValidationException::class);

        $quotation->ensureServiceFrequencyPeriodCompatible();
    }

    public function test_quarterly_service_frequency_accepts_multiple_rental_period(): void
    {
        $quotation = $this->quotationWithRentalPeriodAndFrequency(6, 3);

        $this->assertSame([], $quotation->getServiceFrequencyPeriodValidationErrors());
    }

    public function test_monthly_service_frequency_accepts_any_monthly_rental_period(): void
    {
        $quotation = $this->quotationWithRentalPeriodAndFrequency(4, 1);

        $this->assertSame([], $quotation->getServiceFrequencyPeriodValidationErrors());
    }

    private function quotationWithRentalPeriodAndFrequency(int $rentalPeriod, int $frequencyMonths): Quotation
    {
        $serviceFrequency = new RentalServiceFrequency([
            'name' => $frequencyMonths === 1 ? 'Monthly 1x' : 'Quarterly 1x',
            'frequency_months' => $frequencyMonths,
            'frequency_times_per_month' => 1,
        ]);

        $rental = new MasterRental([
            'rental_name' => 'Rental 1x svc per '.$frequencyMonths.' bulan',
        ]);
        $rental->setRelation('serviceFrequency', $serviceFrequency);

        $detail = new QuotationDetail();
        $detail->setRelation('masterRental', $rental);

        $quotation = new Quotation([
            'rental_period' => $rentalPeriod,
            'rental_unit' => 'bulan',
        ]);
        $quotation->setRelation('quotationDetails', new Collection([$detail]));
        $quotation->setRelation('quotationRentals', new Collection());

        return $quotation;
    }
}
