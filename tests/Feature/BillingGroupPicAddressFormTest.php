<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Billing Address di invoice diisi dari billing_groups.pic_address, tapi form
 * Billing Group tidak pernah punya input untuk kolom itu.
 *
 * Akibatnya pic_address selalu NULL untuk billing group yang dibuat lewat UI,
 * sehingga InvoiceGenerationService jatuh ke $contract->customer->address dan
 * SEMUA billing group milik satu customer mencetak Billing Address yang sama.
 * QA 15 Sep 2026 part 2: invoice Billing Group 2 menampilkan "Jalan Menteng 1
 * Raya" (alamat customer) padahal grupnya beralamat Sentul.
 *
 * Form addbg juga merender <textarea name="npwp_address"> DUA kali -- sisa edit
 * yang tidak tuntas. Yang kedua yatim di luar grid dan tidak pernah diisi JS
 * (getElementById hanya mengenai yang pertama), sedangkan PHP mengambil
 * kemunculan TERAKHIR saat submit, jadi npwp_address tersimpan kosong.
 */
class BillingGroupPicAddressFormTest extends TestCase
{
    private function formSource(string $name): string
    {
        return file_get_contents(resource_path("views/finance/billing-groups/{$name}.blade.php"));
    }

    private function controller(): string
    {
        return file_get_contents(app_path('Http/Controllers/Finance/BillingGroupController.php'));
    }

    public function test_both_forms_expose_a_pic_address_input(): void
    {
        foreach (['addbg', 'editbg'] as $form) {
            $this->assertStringContainsString(
                'name="pic_address"',
                $this->formSource($form),
                "Form {$form} tidak punya input pic_address, jadi Billing Address invoice tidak bisa diisi."
            );
        }
    }

    public function test_edit_form_prefills_the_saved_pic_address(): void
    {
        $this->assertStringContainsString('$billingGroup->pic_address', $this->formSource('editbg'));
    }

    public function test_add_form_does_not_render_npwp_address_twice(): void
    {
        $this->assertSame(
            1,
            substr_count($this->formSource('addbg'), 'name="npwp_address"'),
            'npwp_address dirender lebih dari sekali; kemunculan terakhir menimpa nilai yang diisi JS.'
        );
    }

    public function test_update_validates_and_persists_pic_address(): void
    {
        $source = $this->controller();
        $update = substr($source, strpos($source, 'public function update(Request $request, $id)'));
        $update = substr($update, 0, strpos($update, 'public function ', 10));

        $this->assertStringContainsString("'pic_address' => 'nullable|string'", $update);
        $this->assertStringContainsString("'pic_address' => \$request->pic_address", $update);
    }

    public function test_reload_tax_also_refreshes_the_billing_address(): void
    {
        // Tanpa ini tombol Reload Tax memperbaiki identitas pajaknya saja,
        // sementara Billing Address tetap memakai nilai yang ter-stempel saat
        // invoice dibuat -- yaitu alamat customer, bukan alamat billing group.
        $source = file_get_contents(app_path('Http/Controllers/Finance/InvoiceController.php'));
        $reload = substr($source, strpos($source, 'public function reloadTaxData(Invoice $invoice)'));
        $reload = substr($reload, 0, strpos($reload, 'public function ', 10));

        $this->assertStringContainsString("'billing_address' => \$billingGroup?->pic_address", $reload);
    }
}
