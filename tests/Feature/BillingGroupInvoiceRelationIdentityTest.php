<?php

namespace Tests\Feature;

use App\Models\Finance\Invoice;
use Tests\TestCase;

/**
 * Accessor identitas pajak harus ada di model yang BENAR-BENAR dipakai invoice.
 *
 * Ada dua model billing group yang memetakan tabel yang sama:
 * App\Models\BillingGroup dan App\Models\Finance\BillingGroup. Jalur invoice
 * memakai yang di namespace Finance -- App\Models\Finance\Invoice menyebut
 * `BillingGroup::class` tanpa `use`, jadi resolusinya jatuh ke
 * App\Models\Finance\BillingGroup, dan InvoiceGenerationService meng-import
 * kelas yang sama.
 *
 * Accessor tax_identity_number/tax_identity_address semula hanya dipasang di
 * App\Models\BillingGroup. Akibatnya $invoice->billingGroup->tax_identity_number
 * SELALU null saat berjalan, dan baik InvoiceGenerationService maupun
 * reloadTaxData() diam-diam kembali memakai identitas customer -- yaitu bug
 * yang dilaporkan QA dan yang sudah dikira beres.
 *
 * BillingGroupTaxIdentityTest lolos karena meng-instantiate kelas yang salah.
 * Diverifikasi di produksi 16 Sep 2026: billing group 21525/21526 punya npwp
 * dan nitku terisi, tetapi tax_identity_number mengembalikan null.
 */
class BillingGroupInvoiceRelationIdentityTest extends TestCase
{
    public function test_the_model_behind_the_invoice_relation_exposes_the_identity(): void
    {
        $related = (new Invoice)->billingGroup()->getRelated();

        $group = $related->newInstance();
        $group->setRawAttributes([
            'npwp' => '0014789965412000',
            'nitku' => '000001',
            'npwp_address' => 'Jalan Sentul Raya',
        ]);

        $this->assertSame(
            '0014789965412000000001',
            $group->tax_identity_number,
            get_class($group).' tidak punya accessor tax_identity_number, jadi invoice akan diam-diam memakai identitas customer.'
        );
        $this->assertSame('Jalan Sentul Raya', $group->tax_identity_address);
    }

    public function test_both_billing_group_models_share_one_rule(): void
    {
        $classes = [
            \App\Models\BillingGroup::class,
            \App\Models\Finance\BillingGroup::class,
        ];

        foreach ($classes as $class) {
            $group = new $class;
            $group->setRawAttributes(['npwp' => '0014789965412000', 'nitku' => '000000']);

            $this->assertSame('0014789965412000000000', $group->tax_identity_number, $class);
        }
    }

    public function test_the_rule_lives_in_one_place(): void
    {
        // Kalau aturannya disalin ke masing-masing model, keduanya bisa berbeda
        // lagi tanpa ketahuan. Satu definisi, dipakai bersama.
        foreach (['BillingGroup.php', 'Finance/BillingGroup.php'] as $file) {
            $source = file_get_contents(app_path('Models/'.$file));

            $this->assertStringContainsString('HasBillingTaxIdentity', $source, $file);
            $this->assertStringNotContainsString('function getTaxIdentityNumberAttribute', $source, $file);
        }
    }
}
