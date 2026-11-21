<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * UAT 19 Nov 2026 (Database Mapping & Bug Hunting): bug UI kontrak, billing group, customer tax.
 */
class Uat19NovUiFixesTest extends TestCase
{
    private function viewSource(string $view): string
    {
        return file_get_contents(View::getFinder()->find($view));
    }

    public function test_customer_tax_edit_sets_nitku_and_counter_after_the_form_renders(): void
    {
        $source = $this->viewSource('company.customer-taxes.index');

        // Dulu: wrapper setTimeout 500ms yang jalan sebelum form edit selesai dimuat.
        $this->assertStringNotContainsString('const originalOpenEditModal = openEditModal;', $source);
        $this->assertMatchesRegularExpression(
            "/syncCustomerTaxCodeFields\('edit'\);.*?updateTaxNumberMaxLength\('edit'\);.*?\}\)\s*\.catch/s",
            $source
        );
    }

    public function test_customer_tax_number_keeps_letters_for_kitas_and_passport(): void
    {
        $source = $this->viewSource('company.customer-taxes.index');

        $this->assertStringContainsString("['NPWP', 'NIK', 'NITKU'].includes(taxName)", $source);
    }

    public function test_customer_tax_edit_does_not_ship_every_customer_and_locks_the_customer(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Company/CustomerTaxController.php'));

        $this->assertStringContainsString("Customer::whereKey(\$customerTax->customer_id)->get(['id', 'name'])", $controller);
        $this->assertStringContainsString("\$request->merge(['customer_id' => \$customerTax->customer_id]);", $controller);
        $this->assertStringNotContainsString('<select name="customer_id" class="form-input" required onchange="fetchCustomerTaxInfo(this.value, \'edit\')">',
            $this->viewSource('company.customer-taxes.index'));
    }

    public function test_billing_group_edit_has_no_undeclared_ppn_code_variables(): void
    {
        $source = $this->viewSource('finance.billing-groups.editbg');

        $this->assertDoesNotMatchRegularExpression('/if \(ppnCode(Input|Display)\)/', $source);
        $this->assertStringContainsString('$matchedPicContact', $source);
    }

    public function test_contract_billing_group_list_has_selection_actions_and_tax_billing_label(): void
    {
        $source = $this->viewSource('marketing.contracts.show');

        $this->assertStringContainsString('id="bgEditSelectedBtn"', $source);
        $this->assertStringContainsString('function deleteSelectedBillingGroups()', $source);
        $this->assertStringContainsString('<th>Alamat Penagihan Pajak</th>', $source);
        $this->assertStringNotContainsString('<th>Alamat Kantor Pajak</th>', $source);
    }

    public function test_auto_generated_invoice_uses_the_contract_po_number(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Finance/InvoiceController.php'));

        $this->assertStringNotContainsString("'po_number' => \$contract->quotation->quotation_number", $controller);
        $this->assertStringContainsString("'po_number' => \$contract->po_number,", $controller);
    }

    public function test_contract_wizard_saves_the_pic_finance_phone(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Marketing/ContractWizardController.php'));

        $this->assertStringContainsString("'pic_phone' => \$picPhone,", $controller);
    }
}
