<?php

namespace Tests\Feature;

use App\Http\Controllers\Finance\InvoiceController;
use App\Models\Customer;
use App\Models\FinanceTaxCode;
use App\Models\TaxSetting;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class InvoiceTaxSettingCalculationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('tax_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('tax_code')->nullable();
            $table->string('tax_type')->nullable();
            $table->decimal('tax_rate', 8, 2)->default(0);
            $table->boolean('is_default')->default(false);
            $table->date('effective_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('finance_tax_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('description')->nullable();
            $table->text('ppn_status')->nullable();
            $table->string('invoice_status')->nullable();
            $table->string('faktur_pajak_status')->nullable();
            $table->string('customer_status')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('customer_tax_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('tax_number')->nullable();
            $table->text('tax_address')->nullable();
            $table->string('ppn_code')->nullable();
            $table->date('effective_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        FinanceTaxCode::create([
            'code' => '01',
            'description' => 'PPN normal',
            'invoice_status' => 'Tercetak',
            'faktur_pajak_status' => 'Tercetak',
            'customer_status' => 'Bayar & setor oleh penjual',
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('customer_tax_settings');
        Schema::dropIfExists('finance_tax_codes');
        Schema::dropIfExists('tax_settings');

        parent::tearDown();
    }

    private function makeVat(array $overrides = []): TaxSetting
    {
        return TaxSetting::create(array_merge([
            'name' => 'PPN 11%',
            'tax_code' => 'PPN11',
            'tax_type' => 'vat',
            'tax_rate' => 11,
            'is_default' => true,
            'effective_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'status' => 'active',
        ], $overrides));
    }

    private function buildPayload(string $invoiceDate): array
    {
        $customer = new Customer([
            'name' => 'Customer Test',
            'ppn_code' => '01',
            'npwp' => '01.234.567.8-999.000',
            'npwp_address' => 'Alamat Pajak',
            'address' => 'Alamat Customer',
        ]);
        $customer->id = 1;
        $customer->exists = true;

        $method = new ReflectionMethod(InvoiceController::class, 'buildInvoiceTaxPayload');
        $method->setAccessible(true);

        return $method->invoke(new InvoiceController, $customer, 1_000_000, 0, '01', $invoiceDate);
    }

    public function test_invoice_tax_uses_vat_rate_effective_on_invoice_date(): void
    {
        $this->makeVat();
        $this->makeVat([
            'name' => 'PPN 12%',
            'tax_code' => 'PPN12',
            'tax_rate' => 12,
            'effective_date' => '2026-01-01',
            'end_date' => null,
        ]);

        $oldPayload = $this->buildPayload('2025-07-15');
        $newPayload = $this->buildPayload('2026-07-15');

        $this->assertSame(11.0, $oldPayload['tax_rate']);
        $this->assertSame(110000.0, $oldPayload['tax_amount']);
        $this->assertSame(12.0, $newPayload['tax_rate']);
        $this->assertSame(120000.0, $newPayload['tax_amount']);
    }

    public function test_invoice_tax_falls_back_to_eleven_percent_when_no_default_vat_setting_exists(): void
    {
        $payload = $this->buildPayload('2026-07-15');

        $this->assertNull($payload['tax_setting_id']);
        $this->assertSame(11.0, $payload['tax_rate']);
        $this->assertSame(110000.0, $payload['tax_amount']);
    }
}
