<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerTax;
use App\Services\Company\CustomerIdentifierUniquenessService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerIdentifierUniquenessServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('nib')->nullable();
            $table->string('nib_number')->nullable();
            $table->string('npwp')->nullable();
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

    public function test_it_finds_existing_nib_even_when_formatting_differs(): void
    {
        $owner = Customer::create([
            'name' => 'Abadi Holding',
            'nib' => '02 010 0100 0877',
        ]);

        $match = $this->service()->findCustomerUsingNib('0201001000877');

        $this->assertSame($owner->id, $match?->id);
    }

    public function test_it_checks_legacy_nib_number_and_ignores_current_customer(): void
    {
        $owner = Customer::create([
            'name' => 'Legacy Customer',
            'nib_number' => 'ABC-123',
        ]);

        $this->assertNull($this->service()->findCustomerUsingNib('ABC123', $owner->id));

        $match = $this->service()->findCustomerUsingNib('ABC123');

        $this->assertSame($owner->id, $match?->id);
    }

    public function test_it_finds_existing_npwp_from_legacy_customer_column(): void
    {
        $owner = Customer::create([
            'name' => 'Legacy NPWP Owner',
            'npwp' => '01.234.567.8-901.000',
        ]);

        $match = $this->service()->findCustomerUsingNpwp('012345678901000');

        $this->assertSame($owner->id, $match?->id);
    }

    public function test_it_finds_existing_npwp_from_customer_tax_settings_even_with_different_nitku(): void
    {
        $owner = Customer::create(['name' => 'Tax Owner']);
        CustomerTax::create([
            'customer_id' => $owner->id,
            'tax_name' => 'NPWP',
            'tax_number' => '0006026015062026',
            'nitku' => '000000',
        ]);

        $otherCustomer = Customer::create(['name' => 'Other Customer']);

        $match = $this->service()->findCustomerUsingNpwp('0006026015062026', $otherCustomer->id);

        $this->assertSame($owner->id, $match?->id);
    }

    private function service(): CustomerIdentifierUniquenessService
    {
        return app(CustomerIdentifierUniquenessService::class);
    }
}
