<?php

namespace Tests\Feature;

use App\Models\Finance\Invoice;
use App\Models\Finance\TaxFileImportDetail;
use App\Models\TaxFileImport;
use App\Services\Finance\CoreTaxFakturPdfImportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Covers the individually-downloaded "Output Tax Invoice" PDF path — the
 * counterpart to CoreTaxImportTest, which covers the batched CSV/XLSX result
 * file. Fixture PDFs are generated on the fly with dompdf rather than
 * checking in a real CoreTax PDF, which would carry a real customer's NPWP
 * and business data.
 */
class CoreTaxFakturPdfImportTest extends TestCase
{
    private array $scratchFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('contract_number')->nullable();
            $table->date('invoice_date')->nullable();
            $table->string('invoice_status');
            $table->string('status')->nullable();
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->boolean('tax_obligation')->default(false);
            $table->string('faktur_pajak')->nullable();
            $table->string('faktur_pajak_status')->nullable();
            $table->string('coretax_faktur_number', 50)->nullable();
            $table->date('coretax_faktur_date')->nullable();
            $table->string('coretax_status', 30)->nullable();
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoice_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id');
            $table->string('activity_type')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoice_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id');
            $table->string('file_name');
            $table->string('file_path')->nullable();
            $table->string('file_type')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('tax_file_imports', function (Blueprint $table) {
            $table->id();
            $table->string('import_number');
            $table->string('file_name');
            $table->date('import_date')->nullable();
            $table->string('file_format')->nullable();
            $table->integer('total_records')->default(0);
            $table->integer('success_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->decimal('success_rate', 5, 2)->default(0);
            $table->integer('approval_success')->default(0);
            $table->integer('not_approved')->default(0);
            $table->integer('rejected')->default(0);
            $table->string('delimiter')->nullable();
            $table->boolean('auto_process')->default(false);
            $table->string('status')->default('pending');
            $table->text('error_log')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tax_file_import_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_file_import_id');
            $table->string('invoice_number')->nullable();
            $table->string('tax_number')->nullable();
            $table->date('tax_date')->nullable();
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->string('status')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        DB::table('invoices')->insert([
            ['id' => 1, 'invoice_number' => 'TEST-INV/26-08/0001', 'invoice_status' => 'approved', 'tax_obligation' => true, 'invoice_date' => '2026-08-01', 'tax_amount' => 66000, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'invoice_number' => 'TEST-INV/26-08/0002', 'invoice_status' => 'cancelled', 'tax_obligation' => true, 'invoice_date' => '2026-08-01', 'tax_amount' => 66000, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    protected function tearDown(): void
    {
        foreach ($this->scratchFiles as $path) {
            @unlink($path);
        }

        Schema::dropIfExists('tax_file_import_details');
        Schema::dropIfExists('tax_file_imports');
        Schema::dropIfExists('invoice_files');
        Schema::dropIfExists('invoice_activities');
        Schema::dropIfExists('invoices');

        parent::tearDown();
    }

    public function test_matched_pdf_issues_the_invoice_and_archives_the_faktur_file(): void
    {
        $pdf = $this->makeFakturPdf(['reference' => 'TEST-INV/26-08/0001']);

        $import = $this->makeImport();
        $summary = app(CoreTaxFakturPdfImportService::class)->process($import, [
            ['original_name' => 'faktur-1.pdf', 'full_path' => $pdf],
        ]);

        $this->assertSame(['total' => 1, 'approved' => 1, 'unmatched' => 0, 'skipped' => 0], $summary);

        $invoice = Invoice::find(1);
        $this->assertSame('tax_approved', $invoice->invoice_status);
        $this->assertSame('04002600321184875', $invoice->coretax_faktur_number);
        $this->assertSame('2026-08-12', $invoice->coretax_faktur_date->toDateString());
        $this->assertTrue($invoice->hasValidCoreTaxFaktur());

        $file = DB::table('invoice_files')->where('invoice_id', 1)->first();
        $this->assertNotNull($file, 'faktur pajak PDF was not archived onto the invoice');
        $this->assertStringContainsString('Faktur Pajak 04002600321184875', $file->description);
        $this->assertSame('faktur-1.pdf', $file->file_name);
        $this->assertFileExists(public_path($file->file_path));
        $this->scratchFiles[] = public_path($file->file_path);

        $detail = TaxFileImportDetail::where('tax_file_import_id', $import->id)->sole();
        $this->assertSame('approved', $detail->status);
        $this->assertSame('04002600321184875', $detail->tax_number);
    }

    public function test_unmatched_reference_is_recorded_as_rejected_without_touching_any_invoice(): void
    {
        $pdf = $this->makeFakturPdf(['reference' => 'NOT-A-REAL-INVOICE']);

        $import = $this->makeImport();
        $summary = app(CoreTaxFakturPdfImportService::class)->process($import, [
            ['original_name' => 'faktur-x.pdf', 'full_path' => $pdf],
        ]);

        $this->assertSame(['total' => 1, 'approved' => 0, 'unmatched' => 1, 'skipped' => 0], $summary);

        $detail = TaxFileImportDetail::where('tax_file_import_id', $import->id)->sole();
        $this->assertSame('rejected', $detail->status);
        $this->assertStringContainsString('Tidak ada invoice', $detail->remarks);

        $this->assertSame('approved', Invoice::find(1)->invoice_status);
    }

    public function test_a_cancelled_invoice_is_never_resurrected(): void
    {
        $pdf = $this->makeFakturPdf(['reference' => 'TEST-INV/26-08/0002']);

        $import = $this->makeImport();
        app(CoreTaxFakturPdfImportService::class)->process($import, [
            ['original_name' => 'faktur-cancelled.pdf', 'full_path' => $pdf],
        ]);

        $invoice = Invoice::find(2);
        $this->assertSame('cancelled', $invoice->invoice_status);
        $this->assertNull($invoice->coretax_faktur_number);
        $this->assertSame(0, DB::table('invoice_files')->where('invoice_id', 2)->count());
    }

    /**
     * One bad file in a batch must not stop the rest — this is exactly the
     * scenario a real multi-PDF upload will hit whenever a user drops in a
     * stray file that isn't a faktur pajak at all.
     */
    public function test_a_file_missing_the_referensi_line_is_rejected_without_aborting_the_batch(): void
    {
        $good = $this->makeFakturPdf(['reference' => 'TEST-INV/26-08/0001']);
        $bad = $this->makeFakturPdf(['reference' => null]);

        $import = $this->makeImport();
        $summary = app(CoreTaxFakturPdfImportService::class)->process($import, [
            ['original_name' => 'no-referensi.pdf', 'full_path' => $bad],
            ['original_name' => 'faktur-1.pdf', 'full_path' => $good],
        ]);

        $this->assertSame(['total' => 2, 'approved' => 1, 'unmatched' => 1, 'skipped' => 0], $summary);
        $this->assertSame('tax_approved', Invoice::find(1)->invoice_status);

        $rejected = TaxFileImportDetail::where('tax_file_import_id', $import->id)->where('status', 'rejected')->sole();
        $this->assertStringContainsString('no-referensi.pdf', $rejected->remarks);
    }

    public function test_a_corrupt_file_is_rejected_without_aborting_the_batch(): void
    {
        $corrupt = sys_get_temp_dir().'/corrupt_'.uniqid().'.pdf';
        file_put_contents($corrupt, 'this is not a pdf');
        $this->scratchFiles[] = $corrupt;

        $good = $this->makeFakturPdf(['reference' => 'TEST-INV/26-08/0001']);

        $import = $this->makeImport();
        $summary = app(CoreTaxFakturPdfImportService::class)->process($import, [
            ['original_name' => 'corrupt.pdf', 'full_path' => $corrupt],
            ['original_name' => 'faktur-1.pdf', 'full_path' => $good],
        ]);

        $this->assertSame(1, $summary['approved']);
        $this->assertSame(1, $summary['unmatched']);
        $this->assertSame('tax_approved', Invoice::find(1)->invoice_status);
    }

    public function test_reimporting_the_same_faktur_does_not_duplicate_the_archived_file(): void
    {
        $pdf = $this->makeFakturPdf(['reference' => 'TEST-INV/26-08/0001']);

        $import = $this->makeImport();
        $service = app(CoreTaxFakturPdfImportService::class);
        $service->process($import, [['original_name' => 'faktur-1.pdf', 'full_path' => $pdf]]);
        $service->process($import, [['original_name' => 'faktur-1-copy.pdf', 'full_path' => $pdf]]);

        $files = DB::table('invoice_files')->where('invoice_id', 1)->get();
        $this->assertCount(1, $files, 'the same faktur pajak was archived twice');

        foreach ($files as $file) {
            $this->scratchFiles[] = public_path($file->file_path);
        }
    }

    /**
     * @param  array{reference?: ?string, faktur_number?: string}  $overrides
     */
    private function makeFakturPdf(array $overrides = []): string
    {
        $reference = array_key_exists('reference', $overrides) ? $overrides['reference'] : 'TEST-INV/26-08/0001';
        $fakturNumber = $overrides['faktur_number'] ?? '04002600321184875';

        $referenceLine = $reference === null ? '' : "<p>(Referensi: {$reference})</p>";

        $html = <<<HTML
            <html><body style="font-family: sans-serif; font-size: 12px;">
            <p style="text-align:center;">Faktur Pajak</p>
            <p>Kode dan Nomor Seri Faktur Pajak: {$fakturNumber}</p>
            <p>Pengusaha Kena Pajak:</p>
            <p>Nama : PT CONTOH PENJUAL</p>
            <p>NPWP : 0017556507035000</p>
            <p>Pembeli Barang Kena Pajak/Penerima Jasa Kena Pajak:</p>
            <p>Nama : PT CONTOH PEMBELI</p>
            <p>NPWP : 0010613925093000</p>
            <p>Dasar Pengenaan Pajak &nbsp;&nbsp;&nbsp;&nbsp; 550.000,00</p>
            <p>Jumlah PPN (Pajak Pertambahan Nilai) &nbsp;&nbsp;&nbsp;&nbsp; 66.000,00</p>
            <p>Sesuai dengan ketentuan yang berlaku, faktur pajak ini ditandatangani secara elektronik.</p>
            <p>KOTA ADM. JAKARTA BARAT, 12 Agustus 2026</p>
            <p>Ditandatangani secara elektronik</p>
            {$referenceLine}
            </body></html>
            HTML;

        $path = sys_get_temp_dir().'/faktur_fixture_'.uniqid().'.pdf';
        file_put_contents($path, \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->output());
        $this->scratchFiles[] = $path;

        return $path;
    }

    private function makeImport(): TaxFileImport
    {
        $importNumber = 'TFI-PDF-TEST-'.uniqid();

        return TaxFileImport::create([
            'import_number' => $importNumber,
            // NOT NULL in the real DB; the controller sets this to the
            // eventual zip name up front (see store()) — mirrored here since
            // this test calls the service directly, bypassing the controller.
            'file_name' => $importNumber.'.zip',
            'import_date' => now()->toDateString(),
            'file_format' => 'pdf',
            'status' => 'processing',
        ]);
    }
}
