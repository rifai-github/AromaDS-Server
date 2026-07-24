<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\CustomerTax;
use App\Models\FinanceTaxCode;
use App\Models\TaxSetting;
use App\Services\Finance\InvoiceTaxResolver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Locks the single source of truth for "does PPN apply": the customer's
 * finance tax code, never the legacy customer.tax_obligation boolean.
 */
class InvoiceTaxResolverTest extends TestCase
{
    private InvoiceTaxResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        // migrations/ is empty in this checkout, so build the tables under test.
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
            $table->string('code');
            $table->string('description')->nullable();
            $table->text('ppn_status')->nullable();
            $table->string('invoice_status')->nullable();
            $table->string('faktur_pajak_status')->nullable();
            $table->string('customer_status')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('ppn_code')->nullable();
            $table->boolean('tax_obligation')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customer_tax_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('ppn_code')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('tax_address')->nullable();
            $table->date('effective_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        TaxSetting::create([
            'name' => 'VAT Standard Rate',
            'tax_code' => 'VAT001',
            'tax_type' => 'vat',
            'tax_rate' => 11.00,
            'is_default' => true,
            'effective_date' => '2024-01-01',
            'status' => 'active',
        ]);

        // 01 — seller collects and remits, printed normally: PPN applies.
        $this->makeTaxCode('01', 'Bayar & setor oleh penjual', 'Tercetak', 'Tercetak');
        // 02 — buyer (pemungut) collects: seller must not charge PPN.
        $this->makeTaxCode('02', 'Bayar & setor oleh customer', 'Tercetak', 'Tercetak');
        // 07 — not collected / exempt, printed as zero.
        $this->makeTaxCode('07', 'customer dan penjual tidak dapat mengkreditkan', 'Tercetak (nol)', 'Tercetak (nol)');

        $this->resolver = new InvoiceTaxResolver;
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('customer_tax_settings');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('finance_tax_codes');
        Schema::dropIfExists('tax_settings');

        parent::tearDown();
    }

    private function makeTaxCode(string $code, string $customerStatus, string $invoiceStatus, string $fakturStatus): FinanceTaxCode
    {
        return FinanceTaxCode::create([
            'code' => $code,
            'description' => "Kode {$code}",
            'ppn_status' => 'PPN dapat di kreditkan',
            'invoice_status' => $invoiceStatus,
            'faktur_pajak_status' => $fakturStatus,
            'customer_status' => $customerStatus,
            'is_active' => true,
        ]);
    }

    private function makeCustomer(array $overrides = []): Customer
    {
        $customer = new Customer;
        $customer->forceFill(array_merge([
            'name' => 'PT Contoh',
            'ppn_code' => '01',
            'tax_obligation' => false,
        ], $overrides));
        $customer->save();

        return $customer;
    }

    public function test_ppn_applies_for_seller_collected_code_even_when_tax_obligation_is_false(): void
    {
        // This is the exact QA case: tax_code 01 but tax_obligation = 0.
        $customer = $this->makeCustomer(['ppn_code' => '01', 'tax_obligation' => false]);

        $context = $this->resolver->resolve($customer, null, '2026-06-19');

        $this->assertTrue($context['applies_ppn']);
        $this->assertSame(0.11, $context['rate']);
        $this->assertSame(33000.0, $this->resolver->taxAmount(300000, $context));
    }

    public function test_ppn_does_not_apply_when_buyer_collects_even_when_tax_obligation_is_true(): void
    {
        $customer = $this->makeCustomer(['ppn_code' => '02', 'tax_obligation' => true]);

        $context = $this->resolver->resolve($customer, null, '2026-06-19');

        $this->assertFalse($context['applies_ppn']);
        $this->assertSame(0.0, $context['rate']);
        $this->assertSame(0.0, $this->resolver->taxAmount(300000, $context));
    }

    public function test_zero_print_code_charges_no_ppn(): void
    {
        $customer = $this->makeCustomer(['ppn_code' => '07', 'tax_obligation' => true]);

        $context = $this->resolver->resolve($customer, null, '2026-06-19');

        $this->assertFalse($context['applies_ppn']);
        $this->assertSame(0.0, $this->resolver->taxAmount(1000000, $context));
    }

    public function test_active_customer_tax_row_overrides_the_customer_ppn_code(): void
    {
        $customer = $this->makeCustomer(['ppn_code' => '01']);
        CustomerTax::create([
            'customer_id' => $customer->id,
            'ppn_code' => '02',
            'tax_number' => '01.234.567.8-901.000',
            'tax_address' => 'Jl. Contoh No. 1',
            'effective_date' => '2026-01-01',
            'expiry_date' => null,
            'is_active' => true,
        ]);

        $context = $this->resolver->resolve($customer, null, '2026-06-19');

        $this->assertSame('02', $context['tax_code']);
        $this->assertFalse($context['applies_ppn']);
    }

    public function test_expired_customer_tax_row_is_ignored(): void
    {
        $customer = $this->makeCustomer(['ppn_code' => '01']);
        CustomerTax::create([
            'customer_id' => $customer->id,
            'ppn_code' => '02',
            'effective_date' => '2025-01-01',
            'expiry_date' => '2025-12-31',
            'is_active' => true,
        ]);

        $context = $this->resolver->resolve($customer, null, '2026-06-19');

        $this->assertSame('01', $context['tax_code']);
        $this->assertTrue($context['applies_ppn']);
    }

    public function test_explicitly_requested_tax_code_wins(): void
    {
        $customer = $this->makeCustomer(['ppn_code' => '01']);

        $context = $this->resolver->resolve($customer, '07', '2026-06-19');

        $this->assertSame('07', $context['tax_code']);
        $this->assertFalse($context['applies_ppn']);
    }

    public function test_unknown_tax_code_falls_back_to_01(): void
    {
        $customer = $this->makeCustomer(['ppn_code' => '99']);

        $context = $this->resolver->resolve($customer, null, '2026-06-19');

        $this->assertSame('01', $context['tax_code']);
        $this->assertTrue($context['applies_ppn']);
    }

    public function test_rate_follows_the_effective_tax_setting(): void
    {
        TaxSetting::query()->update(['end_date' => '2026-06-30']);
        TaxSetting::create([
            'name' => 'VAT 12%',
            'tax_code' => 'VAT002',
            'tax_type' => 'vat',
            'tax_rate' => 12.00,
            'is_default' => true,
            'effective_date' => '2026-07-01',
            'status' => 'active',
        ]);

        $customer = $this->makeCustomer(['ppn_code' => '01']);

        $this->assertSame(0.11, $this->resolver->resolve($customer, null, '2026-06-19')['rate']);
        $this->assertSame(0.12, $this->resolver->resolve($customer, null, '2026-07-05')['rate']);
    }

    public function test_null_customer_falls_back_to_default_code(): void
    {
        $context = $this->resolver->resolve(null, null, '2026-06-19');

        $this->assertSame('01', $context['tax_code']);
        $this->assertTrue($context['applies_ppn']);
    }
}
