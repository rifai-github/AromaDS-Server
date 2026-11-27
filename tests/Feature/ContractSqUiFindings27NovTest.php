<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Temuan UI Pak Hengky 27 Nov 2026 di halaman detail Kontrak & SQ (contoh kontrak import
 * ADS-AG/26-07/0096).
 */
class ContractSqUiFindings27NovTest extends TestCase
{
    private function viewSource(string $path): string
    {
        return file_get_contents(resource_path("views/{$path}.blade.php"));
    }

    public function test_price_basis_is_labelled_basis_harga_on_contract_and_sq(): void
    {
        $contract = $this->viewSource('marketing/contracts/show');
        $quotation = $this->viewSource('marketing/quotations/show');

        $this->assertStringContainsString('<div class="contract-field-label">Basis Harga</div>', $contract);
        $this->assertStringNotContainsString('>Basis SQ<', $contract);
        $this->assertStringNotContainsString("'Gagal menyimpan Basis SQ'", $contract);
        $this->assertStringContainsString('<div class="quotation-field-label">Basis Harga</div>', $quotation);
        $this->assertStringNotContainsString('Basis SQ', $quotation);

        $controller = file_get_contents(app_path('Http/Controllers/Marketing/ContractController.php'));
        $this->assertStringNotContainsString("'Basis SQ disimpan: '", $controller);
    }

    public function test_tab_tables_sort_and_filter_in_the_browser_without_reloading(): void
    {
        $contract = $this->viewSource('marketing/contracts/show');
        $quotation = $this->viewSource('marketing/quotations/show');
        $tools = $this->viewSource('components/client-table-tools');

        // Tabel tab ikut sort/filter sisi klien.
        foreach (['roomRentalTable', 'billingGroupsTable', 'buildingsTable', 'filesTable'] as $tableId) {
            $this->assertMatchesRegularExpression('/id="'.$tableId.'"[^>]*data-client-table/', $contract, "{$tableId} belum data-client-table");
        }
        $this->assertMatchesRegularExpression('/id="quotationDetailsTable"[^>]*data-client-table/', $quotation);
        $this->assertStringContainsString("@include('components.client-table-tools')", $contract);
        $this->assertStringContainsString("@include('components.client-table-tools')", $quotation);

        // Filter lama tabel Billing Group (hanya satu kolom, keyup) tidak bertabrakan lagi.
        $this->assertStringNotContainsString("$('#billingGroupsTable .filter-row input').on('keyup'", $contract);

        // Komponen: header ditandai data-no-sort (skrip sort global ?sort= reload dilewati),
        // Enter di filter dicegat pada fase capture sebelum handler global yang me-reload.
        $this->assertStringContainsString("th.setAttribute('data-no-sort', '')", $tools);
        $this->assertStringContainsString("event.stopImmediatePropagation();", $tools);
        $this->assertMatchesRegularExpression("/addEventListener\\('keydown',[\\s\\S]*?\\}, true\\);/", $tools);
        $this->assertStringNotContainsString('window.location', $tools);

        // Tab aktif disimpan di hash, dan hash dibuka saat load (kontrak & SQ).
        foreach ([$contract, $quotation] as $source) {
            $this->assertStringContainsString('window.history.replaceState(null, \'\', window.location.pathname + window.location.search + target);', $source);
        }
        $this->assertStringContainsString('$(\'#quotationTabs button[data-bs-target="\' + window.location.hash + \'"]\').first().trigger(\'click\');', $quotation);
    }

    public function test_sq_basic_info_shows_payment_method_and_term_of_payment_once(): void
    {
        $quotation = $this->viewSource('marketing/quotations/show');

        $this->assertSame(1, substr_count($quotation, '<div class="quotation-field-label">Payment Method</div>'));
        $this->assertSame(1, substr_count($quotation, '<div class="quotation-field-label">Term of Payment</div>'));
    }
}
