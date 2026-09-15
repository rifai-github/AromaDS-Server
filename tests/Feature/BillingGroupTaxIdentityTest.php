<?php

namespace Tests\Feature;

use App\Models\BillingGroup;
use Tests\TestCase;

/**
 * Identitas pajak invoice harus berasal dari billing group invoice itu, bukan
 * dari customer-nya.
 *
 * Satu customer bisa punya beberapa billing group dengan NPWP sama tetapi NITKU
 * berbeda (satu NITKU per cabang/gedung). Di produksi, customer Sejahtera Sukses
 * Makmur punya Billing Group 1 (NITKU 000000, alamat Jalan Menteng 99 Raya) dan
 * Billing Group 2 (NITKU 000001, alamat Jalan Sentul Raya) — tetapi invoice
 * JKT-INV/26-09/0001 milik Billing Group 1 tercetak dengan
 * npwp_number 0014789965412000000001 dan tax_address Jalan Sentul Raya, yaitu
 * identitas Billing Group 2 (QA 15 Sep 2026 part 2).
 */
class BillingGroupTaxIdentityTest extends TestCase
{
    private function group(array $attributes): BillingGroup
    {
        $group = new BillingGroup;
        $group->setRawAttributes($attributes);

        return $group;
    }

    public function test_tax_number_is_npwp_joined_with_nitku(): void
    {
        $group = $this->group(['npwp' => '0014789965412000', 'nitku' => '000000']);

        $this->assertSame('0014789965412000000000', $group->tax_identity_number);
    }

    public function test_each_billing_group_keeps_its_own_nitku(): void
    {
        $first = $this->group(['npwp' => '0014789965412000', 'nitku' => '000000']);
        $second = $this->group(['npwp' => '0014789965412000', 'nitku' => '000001']);

        $this->assertNotSame($first->tax_identity_number, $second->tax_identity_number);
        $this->assertSame('0014789965412000000001', $second->tax_identity_number);
    }

    public function test_legacy_npwp_number_column_still_wins_when_filled(): void
    {
        $group = $this->group([
            'npwp_number' => '9999999999999999999999',
            'npwp' => '0014789965412000',
            'nitku' => '000000',
        ]);

        $this->assertSame('9999999999999999999999', $group->tax_identity_number);
    }

    public function test_returns_null_when_the_group_has_no_tax_identity(): void
    {
        $this->assertNull($this->group(['npwp' => null, 'nitku' => null])->tax_identity_number);
        $this->assertNull($this->group(['npwp' => '', 'nitku' => ''])->tax_identity_number);
    }

    public function test_npwp_without_nitku_is_still_usable(): void
    {
        $group = $this->group(['npwp' => '0014789965412000', 'nitku' => null]);

        $this->assertSame('0014789965412000', $group->tax_identity_number);
    }

    public function test_tax_address_comes_from_the_group_npwp_address(): void
    {
        $first = $this->group(['npwp_address' => 'Jalan Menteng 99 Raya']);
        $second = $this->group(['npwp_address' => 'Jalan Sentul Raya']);

        $this->assertSame('Jalan Menteng 99 Raya', $first->tax_identity_address);
        $this->assertSame('Jalan Sentul Raya', $second->tax_identity_address);
        $this->assertNull($this->group(['npwp_address' => '   '])->tax_identity_address);
    }

    public function test_invoice_generation_and_reload_use_the_group_identity(): void
    {
        $generation = file_get_contents(app_path('Services/Finance/InvoiceGenerationService.php'));
        $controller = file_get_contents(app_path('Http/Controllers/Finance/InvoiceController.php'));

        // Kolom warisan npwp_number tidak lagi dibaca langsung dari billing group.
        $this->assertStringNotContainsString('$billingGroup->npwp_number ?? null', $generation);
        $this->assertStringContainsString('$billingGroup->tax_identity_number', $generation);
        $this->assertStringContainsString('$billingGroup->tax_identity_address', $generation);

        // Reload Tax tidak boleh menimpa identitas grup dengan identitas customer.
        $this->assertStringContainsString('$billingGroup?->tax_identity_number', $controller);
        $this->assertStringContainsString("\$groupTaxAddress ?: \$taxPayload['tax_address']", $controller);
    }
}
