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
}
