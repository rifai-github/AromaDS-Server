<?php

namespace Tests\Feature;

use Tests\TestCase;

class QuotationPdfItemLabelTest extends TestCase
{
    private function pdfSource(): string
    {
        return file_get_contents(resource_path('views/marketing/quotations/pdf.blade.php'));
    }

    public function test_item_column_falls_back_to_category_not_rental_name(): void
    {
        $view = $this->pdfSource();

        $this->assertStringContainsString('$detail->masterRental->category', $view);
        $this->assertStringContainsString('$detail->masterRental->rental_name', $view);
    }

    public function test_item_column_ignores_alias_that_is_only_the_rental_code(): void
    {
        $view = $this->pdfSource();

        // Import Catalyst mengisi rental_alias dengan master_rentals.rental_code, sehingga
        // kolom ITEM mencetak kode seperti "A10812100". Alias semacam itu harus dilewati
        // supaya label jatuh ke kategori produk.
        $this->assertStringContainsString('$rentalCode', $view);
        $this->assertStringContainsString('$aliasIsRentalCode', $view);
        $this->assertStringContainsString('strcasecmp($rawAlias, $rentalCode) === 0', $view);
        $this->assertStringContainsString("(\$aliasIsRentalCode ? '' : \$rawAlias)", $view);
    }

    public function test_alias_comparison_treats_matching_code_as_no_alias(): void
    {
        // Menirukan ekspresi di blade untuk mengunci perilakunya, bukan hanya teksnya.
        $resolve = function (?string $alias, ?string $rentalCode, ?string $category, ?string $rentalName): string {
            $rawAlias = trim((string) $alias);
            $code = trim((string) $rentalCode);
            $aliasIsRentalCode = $rawAlias !== '' && $code !== '' && strcasecmp($rawAlias, $code) === 0;

            return ($aliasIsRentalCode ? '' : $rawAlias)
                ?: (trim((string) $category) ?: ($rentalName ?? '-'));
        };

        // Alias hasil import = kode rental -> pakai kategori.
        $this->assertSame(
            'Aroma Delivery Sys Svc',
            $resolve('A10812100', 'A10812100', 'Aroma Delivery Sys Svc', 'ADS 100 12 Bln')
        );

        // Alias asli dari pengguna tetap menang.
        $this->assertSame(
            'Paket Khusus Lobby',
            $resolve('Paket Khusus Lobby', 'A10812100', 'Aroma Delivery Sys Svc', 'ADS 100 12 Bln')
        );

        // Tanpa alias dan tanpa kategori, baru jatuh ke nama rental.
        $this->assertSame(
            'ADS 100 12 Bln',
            $resolve(null, 'A10812100', '', 'ADS 100 12 Bln')
        );
    }
}
