<?php

namespace Tests\Feature;

use App\Http\Controllers\Marketing\ContractWizardController;
use App\Models\Customer;
use App\Models\CustomerTax;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Tests\TestCase;

class ContractWizardCustomerTaxFallbackTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customer_tax_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable();
            $table->string('tax_name')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('nitku')->nullable();
            $table->string('status')->nullable();
            $table->boolean('is_active')->default(false);
            $table->date('effective_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('customer_tax_settings');
        Schema::dropIfExists('customers');

        parent::tearDown();
    }

    public function test_contract_wizard_can_use_active_tax_from_duplicate_customer_name(): void
    {
        $quotationCustomer = Customer::create(['name' => 'Hotel JkT45']);
        $taxCustomer = Customer::create(['name' => 'Hotel JkT45']);

        CustomerTax::create([
            'customer_id' => $taxCustomer->id,
            'tax_name' => 'NPWP',
            'tax_number' => '2313123123141423',
            'nitku' => '000000',
            'status' => 'active',
            'is_active' => true,
            'effective_date' => '2026-05-08',
        ]);

        $this->attachTaxFallback($quotationCustomer);

        $this->assertCount(1, $quotationCustomer->customerTaxSettings);
        $this->assertSame('NPWP', $quotationCustomer->customerTaxSettings->first()->tax_name);
        $this->assertSame('2313123123141423', $quotationCustomer->customerTaxSettings->first()->tax_number);
    }

    public function test_contract_wizard_keeps_customer_own_tax_before_using_fallback(): void
    {
        $quotationCustomer = Customer::create(['name' => 'Hotel JkT45']);
        $taxCustomer = Customer::create(['name' => 'Hotel JkT45']);

        CustomerTax::create([
            'customer_id' => $quotationCustomer->id,
            'tax_name' => 'NPWP',
            'tax_number' => 'OWN-TAX',
            'status' => 'active',
            'is_active' => true,
            'effective_date' => '2026-05-08',
        ]);

        CustomerTax::create([
            'customer_id' => $taxCustomer->id,
            'tax_name' => 'NPWP',
            'tax_number' => 'FALLBACK-TAX',
            'status' => 'active',
            'is_active' => true,
            'effective_date' => '2026-05-09',
        ]);

        $this->attachTaxFallback($quotationCustomer);

        $this->assertCount(1, $quotationCustomer->customerTaxSettings);
        $this->assertSame('OWN-TAX', $quotationCustomer->customerTaxSettings->first()->tax_number);
    }

    private function attachTaxFallback(Customer $customer): void
    {
        $controller = app(ContractWizardController::class);
        $method = (new ReflectionClass($controller))->getMethod('attachCustomerTaxSettingsFallback');
        $method->setAccessible(true);
        $method->invoke($controller, $customer);
    }
}
