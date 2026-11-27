<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Pak Hengky 27 Nov 2026: default Invoice Type untuk kontrak / billing group baru yang dibuat di
 * aplikasi = Hard Copy, bukan Soft Copy. Importer Catalyst tidak diubah (tetap menulis
 * invoice_type eksplisit dari data sumber).
 */
class BillingGroupDefaultInvoiceTypeTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_contract_wizard_defaults_billing_group_invoice_type_to_hard_copy(): void
    {
        $controller = $this->source('app/Http/Controllers/Marketing/ContractWizardController.php');
        $this->assertStringContainsString("\$invoiceType = (\$billingAddress['invoice_receipt'] ?? null) ?: 'hard_copy';", $controller);
        $this->assertStringNotContainsString("\$billingAddress['invoice_receipt'] ?? 'soft_copy'", $controller);

        $wizard = $this->source('resources/views/marketing/contracts/wizard/create.blade.php');
        $this->assertStringContainsString('<option value="hard_copy" selected>Hard copy</option>', $wizard);
    }

    public function test_billing_group_controller_and_service_default_to_hard_copy(): void
    {
        $controller = $this->source('app/Http/Controllers/Finance/BillingGroupController.php');
        $this->assertStringNotContainsString("'soft_copy';", $controller);
        $this->assertStringNotContainsString("?? 'soft_copy'", $controller);
        $this->assertStringContainsString("'invoice_type' => \$request->invoice_type ?: 'hard_copy', // default Hard Copy", $controller);
        // Salinan "link existing billing group" membawa Invoice Type sumbernya.
        $this->assertStringContainsString("'invoice_type' => \$billingGroup->invoice_type ?: 'hard_copy',", $controller);

        $service = $this->source('app/Services/Finance/BillingGroupService.php');
        $this->assertStringContainsString("'invoice_type' => (\$data['invoice_type'] ?? null) ?: 'hard_copy',", $service);
    }

    public function test_billing_group_forms_preselect_hard_copy(): void
    {
        $add = $this->source('resources/views/finance/billing-groups/addbg.blade.php');
        $this->assertStringContainsString('name="invoice_type"', $add);
        $this->assertStringContainsString("@selected(old('invoice_type', 'hard_copy') === \$invoiceTypeValue)", $add);

        $edit = $this->source('resources/views/finance/billing-groups/editbg.blade.php');
        $this->assertStringContainsString("old('invoice_type', \$billingGroup->invoice_type ?: 'hard_copy') == 'hard_copy'", $edit);
    }
}
