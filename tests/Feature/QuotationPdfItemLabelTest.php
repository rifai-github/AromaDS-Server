<?php

namespace Tests\Feature;

use Tests\TestCase;

class QuotationPdfItemLabelTest extends TestCase
{
    private function pdfSource(): string
    {
        return file_get_contents(resource_path('views/marketing/quotations/pdf.blade.php'));
    }

    // MOM 18 Nov 2026 p3c: category master rental diganti Rental Alias; alias kosong -> nama rental.
    public function test_item_column_falls_back_to_rental_name_not_category(): void
    {
        $view = $this->pdfSource();

        $this->assertStringNotContainsString('$detail->masterRental->category', $view);
        $this->assertStringContainsString('$detail->masterRental->rental_name', $view);
    }

    public function test_item_column_ignores_alias_that_is_only_the_rental_code(): void
    {
        $view = $this->pdfSource();

        // Import Catalyst mengisi rental_alias dengan master_rentals.rental_code, sehingga
        // kolom ITEM mencetak kode seperti "A10812100". Alias semacam itu harus dilewati
        // supaya label jatuh ke nama rental.
        $this->assertStringContainsString('$rentalCode', $view);
        $this->assertStringContainsString('$aliasIsRentalCode', $view);
        $this->assertStringContainsString('strcasecmp($rawAlias, $rentalCode) === 0', $view);
        $this->assertStringContainsString("(\$aliasIsRentalCode ? '' : \$rawAlias)", $view);
    }

    public function test_alias_comparison_treats_matching_code_as_no_alias(): void
    {
        // Menirukan ekspresi di blade untuk mengunci perilakunya, bukan hanya teksnya.
        $resolve = function (?string $alias, ?string $rentalCode, ?string $rentalName): string {
            $rawAlias = trim((string) $alias);
            $code = trim((string) $rentalCode);
            $aliasIsRentalCode = $rawAlias !== '' && $code !== '' && strcasecmp($rawAlias, $code) === 0;

            return ($aliasIsRentalCode ? '' : $rawAlias)
                ?: ($rentalName ?? '-');
        };

        // Alias hasil import = kode rental -> pakai nama rental.
        $this->assertSame(
            'ADS 100 12 Bln',
            $resolve('A10812100', 'A10812100', 'ADS 100 12 Bln')
        );

        // Rental Alias (default dari master rental atau pilihan user) tetap menang.
        $this->assertSame(
            'Aroma Delivery Sys Svc',
            $resolve('Aroma Delivery Sys Svc', 'A10812100', 'ADS 100 12 Bln')
        );

        // Alias dikosongkan user -> nama rental asli.
        $this->assertSame(
            'ADS 100 12 Bln',
            $resolve(null, 'A10812100', 'ADS 100 12 Bln')
        );
    }
}
