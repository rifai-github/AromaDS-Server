<?php

namespace Tests\Feature;

use Tests\TestCase;

class CustomerTaxFormScriptTest extends TestCase
{
    public function test_customer_tax_create_form_submits_tax_type_as_finance_tax_code(): void
    {
        $view = file_get_contents(resource_path('views/company/customer-taxes/index.blade.php'));

        $this->assertStringContainsString('const customerTaxCodes = @json', $view);
        $this->assertStringContainsString('function buildCustomerTaxCodeOptions(selectedCode = \'\')', $view);
        $this->assertStringContainsString('name="tax_type" id="create_tax_type"', $view);
        $this->assertStringContainsString('name="ppn_code" id="create_ppn_code"', $view);
        $this->assertStringNotContainsString('placeholder="e.g. PPN 11%, PPh 23"', $view);
        $this->assertStringNotContainsString('value="PPN 11%"', $view);
    }
}
