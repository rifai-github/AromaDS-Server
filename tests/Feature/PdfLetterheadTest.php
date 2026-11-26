<?php

namespace Tests\Feature;

use App\Support\PdfLetterhead;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * UAT 24 Nov 2026: kop & footer seragam di semua printout PDF. Tengah bawah = Master Company
 * footer 1 (tebal) / 2 / 3, bukan hardcode; kanan atas = QR nomor dokumen.
 */
class PdfLetterheadTest extends TestCase
{
    private const TEMPLATES = [
        'marketing/surveys/pdf',
        'marketing/quotations/pdf',
        'marketing/contracts/pdf',
        'operational/job-schedules/pdf-csr',
        'operational/job-schedules/pdf-csr-invoice',
        'finance/invoices/print_template',
        'finance/invoices/delivery_receipt_pdf',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('footer_line_1')->nullable();
            $table->string('footer_line_2')->nullable();
            $table->string('footer_line_3')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('companies');
        parent::tearDown();
    }

    public function test_footer_lines_come_from_master_company(): void
    {
        DB::table('companies')->insert([
            'name' => 'PT Pink Services Indonesia',
            'footer_line_1' => 'An ISO 14001:2015 Certified Company',
            'footer_line_2' => '',
            'footer_line_3' => 'Jakarta . Surabaya',
        ]);

        $this->assertSame(['An ISO 14001:2015 Certified Company', 'Jakarta . Surabaya'], PdfLetterhead::footerLines());
        $this->assertSame('PT PINK SERVICES INDONESIA', PdfLetterhead::companyName());

        $html = view('pdf.partials.identity-footer')->render();
        $this->assertStringContainsString('font-weight: bold; color: #222;">An ISO 14001:2015 Certified Company', $html);
        $this->assertStringContainsString('Jakarta . Surabaya', $html);
        // Klien 25 Nov 2026: kiri bawah = teks + QR ke linktree, bukan logo ADS.
        $this->assertStringContainsString('Find Us More Info', $html);
        $this->assertStringContainsString('Scan Below', $html);
        $this->assertStringContainsString('QR https://linktr.ee/aromadeliverysystem', $html);
        $this->assertStringNotContainsString('alt="Logo"', $html);
    }

    public function test_qr_encodes_the_document_number(): void
    {
        $this->assertStringStartsWith('data:image/png;base64,', (string) PdfLetterhead::qrSrc('JKT-IR/26-06/0002'));
        $this->assertNotSame(PdfLetterhead::qrSrc('JKT-IR/26-06/0002'), PdfLetterhead::qrSrc('JKT-IR/26-06/0003'));
        $this->assertNull(PdfLetterhead::qrSrc(''));
    }

    public function test_every_printout_uses_the_shared_footer_and_no_hardcoded_branch_list(): void
    {
        foreach (self::TEMPLATES as $template) {
            $source = file_get_contents(resource_path("views/{$template}.blade.php"));
            $this->assertStringContainsString("@include('pdf.partials.identity-footer')", $source, $template);
            $this->assertStringNotContainsString('Balikpapan . Banjarmasin', $source, $template);
        }
    }
}
