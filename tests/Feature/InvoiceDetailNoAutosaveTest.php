<?php

namespace Tests\Feature;

use App\Models\Finance\Invoice;
use Tests\TestCase;

/**
 * UAT 24 Nov 2026 (invoice detail): tidak boleh ada autosave - Invoice Range, tanggal, diskon
 * dan catatan hanya tersimpan lewat tombol SAVE; Invoice Date & Invoice Range tampil terpisah;
 * printout: "Invoice Date" di kanan atas dan "Period: N dari M (range ...)" di bawah.
 */
class InvoiceDetailNoAutosaveTest extends TestCase
{
    public function test_detail_page_has_no_autosave_handlers(): void
    {
        $show = file_get_contents(resource_path('views/finance/invoices/show.blade.php'));

        $this->assertStringNotContainsString("on('blur'", $show);
        $this->assertStringNotContainsString('saveDatePreference()', $show);
        $this->assertStringNotContainsString('Auto-save immediately', $show);
        // Discount changes are only queued; the discount endpoint is called from the SAVE flow.
        $this->assertStringContainsString('window.invoiceSavePendingDiscounts', $show);
        $this->assertStringContainsString('window.invoiceSavePendingDate', $show);
        $this->assertStringContainsString("beforeunload", $show);
    }

    public function test_invoice_date_and_range_are_separate_fields(): void
    {
        $show = file_get_contents(resource_path('views/finance/invoices/show.blade.php'));

        $this->assertStringContainsString('<div class="invoice-field-label">Invoice Date</div>', $show);
        $this->assertStringContainsString('<div class="invoice-field-label">Invoice Range</div>', $show);
    }

    public function test_print_moves_period_to_the_bottom_with_indonesian_wording(): void
    {
        $print = file_get_contents(resource_path('views/finance/invoices/print_template.blade.php'));

        $this->assertStringContainsString('<td class="meta-label">Invoice Date</td>', $print);
        $this->assertStringNotContainsString('<td class="meta-label">Periode</td>', $print);
        $this->assertStringContainsString('<strong>Period:</strong> {{ $periodeText }}', $print);
    }

    public function test_period_text_falls_back_to_the_raw_period_without_a_contract(): void
    {
        $this->assertSame('2026-11', (new Invoice(['period_invoice' => '2026-11']))->periodRangeText());
        $this->assertSame('-', (new Invoice)->periodRangeText());
    }
}
