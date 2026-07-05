<?php

namespace Tests\Unit;

use App\Models\Contract;
use App\Models\JobSchedule;
use App\Models\Quotation;
use App\Services\DocumentNumberService;
use App\Services\Finance\InvoiceGenerationService;
use Tests\TestCase;

class InvoiceRentalTypeTriggerTest extends TestCase
{
    private function callJobTypeCanInvoiceRentalType(
        string $jobType,
        string $rentalType,
        Contract $contract,
        ?JobSchedule $jobSchedule = null
    ): bool {
        $service = new InvoiceGenerationService(new DocumentNumberService());

        $method = new \ReflectionMethod(InvoiceGenerationService::class, 'jobTypeCanInvoiceRentalType');
        $method->setAccessible(true);

        return $method->invoke($service, $jobType, $rentalType, $contract, $jobSchedule);
    }

    private function contractWithPaymentMethod(string $paymentMethod): Contract
    {
        $contract = new Contract();
        $contract->setRelation('quotation', new Quotation([
            'payment_method' => $paymentMethod,
        ]));

        return $contract;
    }

    public function test_unit_refill_before_service_is_triggered_by_install_job_for_period_one(): void
    {
        $contract = $this->contractWithPaymentMethod('Before Service');

        $this->assertTrue(
            $this->callJobTypeCanInvoiceRentalType('install', 'unit_refill', $contract)
        );
    }

    public function test_unit_refill_before_service_is_not_triggered_by_first_csr_job(): void
    {
        $contract = $this->contractWithPaymentMethod('Before Service');
        $firstPeriodCsr = new JobSchedule(['period' => 1]);

        $this->assertFalse(
            $this->callJobTypeCanInvoiceRentalType('csr', 'unit_refill', $contract, $firstPeriodCsr)
        );
    }

    public function test_unit_refill_before_service_is_triggered_by_service_job_from_period_two_onward(): void
    {
        $contract = $this->contractWithPaymentMethod('Before Service');
        $secondPeriodService = new JobSchedule(['period' => 2]);

        $this->assertTrue(
            $this->callJobTypeCanInvoiceRentalType('service_routine', 'unit_refill', $contract, $secondPeriodService)
        );
    }

    public function test_unit_refill_after_service_is_still_triggered_by_service_job_only(): void
    {
        $contract = $this->contractWithPaymentMethod('After Service');

        $this->assertTrue(
            $this->callJobTypeCanInvoiceRentalType('csr', 'unit_refill', $contract)
        );
        $this->assertFalse(
            $this->callJobTypeCanInvoiceRentalType('install', 'unit_refill', $contract)
        );
    }

    public function test_refill_only_is_triggered_by_service_job_regardless_of_timing(): void
    {
        $beforeService = $this->contractWithPaymentMethod('Before Service');
        $afterService = $this->contractWithPaymentMethod('After Service');

        $this->assertTrue($this->callJobTypeCanInvoiceRentalType('csr', 'refill_only', $beforeService));
        $this->assertTrue($this->callJobTypeCanInvoiceRentalType('csr', 'refill_only', $afterService));
        $this->assertFalse($this->callJobTypeCanInvoiceRentalType('install', 'refill_only', $beforeService));
    }

    public function test_unit_only_behaviour_is_unaffected(): void
    {
        $contract = $this->contractWithPaymentMethod('Before Service');
        $firstPeriod = new JobSchedule(['period' => 1]);
        $secondPeriod = new JobSchedule(['period' => 2]);

        $this->assertTrue($this->callJobTypeCanInvoiceRentalType('install', 'unit_only', $contract));
        $this->assertFalse($this->callJobTypeCanInvoiceRentalType('service', 'unit_only', $contract, $firstPeriod));
        $this->assertTrue($this->callJobTypeCanInvoiceRentalType('service', 'unit_only', $contract, $secondPeriod));
    }
}
