<?php

namespace Tests\Feature;

use Tests\TestCase;

class CustomerTaxFormScriptTest extends TestCase
{
    // Jawaban klien 22 Nov 2026 (#20): kode PPN tidak diisi di Customer Tax, tapi per kontrak.
    public function test_customer_tax_form_no_longer_asks_for_a_ppn_code(): void
    {
        $view = file_get_contents(resource_path('views/company/customer-taxes/index.blade.php'));

        $this->assertStringNotContainsString('name="tax_type" id="create_tax_type"', $view);
        $this->assertStringNotContainsString('name="ppn_code" id="create_ppn_code"', $view);
        $this->assertStringNotContainsString('name="tax_type" id="edit_tax_type"', $view);
        $this->assertStringContainsString('Kode PPN ditentukan per kontrak', $view);

        $controller = file_get_contents(app_path('Http/Controllers/Company/CustomerTaxController.php'));
        $this->assertStringContainsString("\$request->merge(['tax_type' => null, 'ppn_code' => null]);", $controller);
    }

    public function test_customer_tax_form_clamps_tax_number_to_active_maxlength(): void
    {
        $view = file_get_contents(resource_path('views/company/customer-taxes/index.blade.php'));

        $this->assertStringContainsString('oninput="normalizeTaxNumberInput(\'create\')"', $view);
        $this->assertStringContainsString('oninput="normalizeTaxNumberInput(\'edit\')"', $view);
        $this->assertStringContainsString('function normalizeTaxNumberInput(mode)', $view);
        // Tetap dipotong ke maxlength aktif; hanya NPWP/NIK/NITKU yang digit saja (UAT 19 Nov:
        // nomor KITAS/paspor berisi huruf dan dulu ikut terhapus).
        $this->assertStringContainsString('input.value = cleaned.substring(0, maxLength);', $view);
        $this->assertStringContainsString("['NPWP', 'NIK', 'NITKU'].includes(taxName)", $view);
        $this->assertStringContainsString('normalizeTaxNumberInput(mode);', $view);
        $this->assertStringContainsString("const maxLength = parseInt(input.getAttribute('maxlength') || 30, 10);", $view);
        $this->assertStringContainsString("case 'NITKU':", $view);
        $this->assertStringContainsString('maxLength = 22;', $view);
        $this->assertStringNotContainsString("input.getAttribute('maxlength') || 25", $view);
    }

    public function test_contract_wizard_tax_modal_has_no_nitku_or_tax_rate(): void
    {
        // MOM 19 Nov + QA 23 Nov: NITKU bukan Tax Name tersendiri (diisi di bawah NPWP) dan
        // Customer Tax tak lagi menyimpan Kode PPN / Tax Rate.
        $view = file_get_contents(resource_path('views/marketing/contracts/wizard/create.blade.php'));

        $this->assertStringContainsString('NPWP/NIK: 16 digit, Other: 25 digit', $view);
        $this->assertStringContainsString('oninput="normalizeTaxNumberInput(\'modal\')"', $view);
        $this->assertStringContainsString('function normalizeTaxNumberInput(mode)', $view);
        $this->assertStringNotContainsString('<option value="NITKU">', $view);
        $this->assertStringNotContainsString('id="modal_tax_rate_display"', $view);
        $this->assertStringNotContainsString('Kode PPN belum dipilih', $view);
    }
}
