<?php

namespace Tests\Feature;

use App\Models\Finance\Invoice;
use App\Models\Finance\TaxFileImportDetail;
use App\Models\TaxFileImport;
use App\Services\Finance\CoreTaxImportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CoreTaxImportTest extends TestCase
{
    private string $uploadDir;

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
            // The legacy column Invoice::boot() keeps in sync.
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

        Schema::create('tax_file_imports', function (Blueprint $table) {
            $table->id();
            $table->string('import_number');
            $table->string('file_name')->nullable();
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
            $table->boolean('skip_header')->default(false);
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
            ['id' => 1, 'invoice_number' => 'ADS-INV/25-10/0633', 'invoice_status' => 'approved', 'tax_obligation' => true, 'invoice_date' => '2025-10-20', 'tax_amount' => 60500, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'invoice_number' => 'ADS-INV/25-10/0638', 'invoice_status' => 'approved', 'tax_obligation' => true, 'invoice_date' => '2025-10-20', 'tax_amount' => 71500, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'invoice_number' => 'ADS-INV/25-10/0671', 'invoice_status' => 'cancelled', 'tax_obligation' => true, 'invoice_date' => '2025-10-20', 'tax_amount' => 71500, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->uploadDir = public_path('uploads/tax-file-imports');
        if (! is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        foreach (glob($this->uploadDir.'/phpunit-coretax-*.csv') ?: [] as $file) {
            @unlink($file);
        }

        Schema::dropIfExists('tax_file_import_details');
        Schema::dropIfExists('tax_file_imports');
        Schema::dropIfExists('invoice_activities');
        Schema::dropIfExists('invoices');

        parent::tearDown();
    }

    public function test_approved_rows_issue_the_invoice_and_store_the_faktur_number(): void
    {
        $import = $this->importFile($this->coreTaxCsv());

        $summary = app(CoreTaxImportService::class)->process($import, 'tax-file-imports/'.$import->file_name);

        $this->assertSame(
            ['total' => 4, 'approved' => 1, 'unmatched' => 1, 'skipped' => 2],
            $summary
        );

        $issued = Invoice::find(1);
        $this->assertSame(Invoice::STATUS_TAX_APPROVED, $issued->invoice_status);
        $this->assertSame('04002500353922260', $issued->coretax_faktur_number);
        $this->assertSame('APPROVED', $issued->coretax_status);
        $this->assertSame('2025-11-03', $issued->coretax_faktur_date->toDateString());
        $this->assertTrue($issued->hasValidCoreTaxFaktur());
        $this->assertSame(1, $issued->invoiceActivities()->count());

        $import->refresh();
        $this->assertSame('completed', $import->status);
        $this->assertSame(1, $import->success_count);
        $this->assertNotNull($import->processed_at);
    }

    public function test_rows_coretax_has_not_approved_leave_the_invoice_alone(): void
    {
        $import = $this->importFile($this->coreTaxCsv());

        app(CoreTaxImportService::class)->process($import, 'tax-file-imports/'.$import->file_name);

        $pending = Invoice::find(2);
        $this->assertSame(Invoice::STATUS_APPROVED, $pending->invoice_status);
        $this->assertNull($pending->coretax_faktur_number);
        $this->assertFalse($pending->hasValidCoreTaxFaktur());

        $detail = TaxFileImportDetail::where('invoice_number', 'ADS-INV/25-10/0638')->first();
        $this->assertSame('warning', $detail->status);
    }

    public function test_a_cancelled_invoice_is_never_resurrected(): void
    {
        $import = $this->importFile($this->coreTaxCsv());

        app(CoreTaxImportService::class)->process($import, 'tax-file-imports/'.$import->file_name);

        $cancelled = Invoice::find(3);
        $this->assertSame(Invoice::STATUS_CANCELLED, $cancelled->invoice_status);
        $this->assertNull($cancelled->coretax_faktur_number);
    }

    public function test_unknown_invoice_numbers_are_recorded_as_rejected(): void
    {
        $import = $this->importFile($this->coreTaxCsv());

        app(CoreTaxImportService::class)->process($import, 'tax-file-imports/'.$import->file_name);

        $detail = TaxFileImportDetail::where('invoice_number', 'ADS-INV/25-10/9999')->first();
        $this->assertSame('rejected', $detail->status);
        $this->assertStringContainsString('Tidak ada invoice', $detail->remarks);
    }

    public function test_a_file_that_is_not_a_coretax_result_is_rejected_with_a_clear_message(): void
    {
        $import = $this->importFile("kolom_a,kolom_b\nisi,isi\n");

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('tidak dikenali sebagai hasil CoreTax');

        app(CoreTaxImportService::class)->process($import, 'tax-file-imports/'.$import->file_name);
    }

    public function test_semicolon_delimited_files_are_still_understood(): void
    {
        $csv = str_replace(',', ';', "Reference,TaxInvoiceNumber,TaxInvoiceDate,TaxInvoiceStatus,VAT\n"
            ."ADS-INV/25-10/0633,04002500353922260,2025-11-03T00:00:00,APPROVED,3377000\n");

        $import = $this->importFile($csv);

        $summary = app(CoreTaxImportService::class)->process($import, 'tax-file-imports/'.$import->file_name);

        $this->assertSame(1, $summary['approved']);
        $this->assertSame(Invoice::STATUS_TAX_APPROVED, Invoice::find(1)->invoice_status);
    }

    public function test_print_gate_exempts_invoices_that_carry_no_ppn(): void
    {
        $withPpn = Invoice::find(1);
        $this->assertFalse($withPpn->canPrintDocuments());
        $this->assertStringContainsString('belum punya Faktur Pajak', $withPpn->documentBlockReason());

        $withPpn->update([
            'coretax_faktur_number' => '04002500353922260',
            'coretax_status' => 'APPROVED',
        ]);
        $this->assertTrue($withPpn->fresh()->canPrintDocuments());

        // A revoked faktur locks the invoice again.
        $withPpn->update(['coretax_status' => 'CANCELLED']);
        $this->assertFalse($withPpn->fresh()->canPrintDocuments());
        $this->assertStringContainsString('dibatalkan', $withPpn->fresh()->documentBlockReason());

        // Exempt only when no PPN was actually billed.
        $withoutPpn = Invoice::find(2);
        $withoutPpn->update(['tax_obligation' => false, 'tax_amount' => 0]);
        $this->assertTrue($withoutPpn->fresh()->canPrintDocuments());
        $this->assertNull($withoutPpn->fresh()->documentBlockReason());
    }

    /**
     * Most existing invoices bill PPN while `tax_obligation` is still 0 — on QA,
     * 49 of the 55 that charge tax. Keying the gate on the flag alone let those
     * through, so the tax actually charged has to count as well.
     */
    public function test_gate_blocks_invoices_that_bill_ppn_even_when_the_flag_is_off(): void
    {
        $invoice = Invoice::find(2);
        $invoice->update(['tax_obligation' => false, 'tax_amount' => 33000]);

        $this->assertTrue($invoice->fresh()->requiresFakturPajak());
        $this->assertFalse($invoice->fresh()->canPrintDocuments());
        $this->assertStringContainsString('belum punya Faktur Pajak', $invoice->fresh()->documentBlockReason());
    }

    /**
     * Rule 43 used to trip on the name of an uploaded file. It must follow the
     * faktur pajak that CoreTax actually issued instead.
     */
    public function test_cancelling_the_invoice_is_blocked_only_by_a_live_coretax_faktur(): void
    {
        $invoice = Invoice::find(1);

        // An attached file must not lock the invoice on its own.
        $invoice->update(['faktur_pajak' => 'scan-faktur.pdf', 'faktur_pajak_status' => 'active']);
        $this->assertTrue($invoice->fresh()->canCancel());

        $invoice->update(['coretax_faktur_number' => '0400260035400001', 'coretax_status' => 'APPROVED']);
        $this->assertFalse($invoice->fresh()->canCancel());

        $invoice->fresh()->cancelCoreTaxFaktur();
        $this->assertTrue($invoice->fresh()->canCancel());
    }

    public function test_cancelling_the_faktur_relocks_documents_and_reopens_export(): void
    {
        $invoice = Invoice::find(1);
        $invoice->update([
            'invoice_status' => Invoice::STATUS_TAX_APPROVED,
            'coretax_faktur_number' => '0400260035400001',
            'coretax_status' => 'APPROVED',
        ]);

        $this->assertTrue($invoice->fresh()->canPrintDocuments());

        $invoice->fresh()->cancelCoreTaxFaktur();
        $invoice = $invoice->fresh();

        $this->assertSame(Invoice::CORETAX_STATUS_CANCELLED, $invoice->coretax_status);
        $this->assertFalse($invoice->hasValidCoreTaxFaktur());
        // Documents lock again...
        $this->assertFalse($invoice->canPrintDocuments());
        $this->assertStringContainsString('dibatalkan', $invoice->documentBlockReason());
        // ...and the invoice is eligible for a fresh CoreTax export.
        $this->assertSame(Invoice::STATUS_APPROVED, $invoice->invoice_status);
    }

    /**
     * Shaped like the real CoreTax export: two unnamed leading columns, then the
     * named ones. Row 1 is issued, row 2 is still pending, row 3 belongs to a
     * cancelled invoice, row 4 references an invoice we do not have.
     */
    private function coreTaxCsv(): string
    {
        $header = ',,BuyerTIN,DisplayName,TaxInvoiceCode,TaxInvoiceNumber,TaxInvoiceDate,TaxInvoicePeriod,'
            .'TaxInvoiceYear,TaxInvoiceStatus,ESignStatus,SellingPrice,OtherTaxBase,VAT,STLG,Signer,Reference,'
            ."ReportedBySeller,ReportedByVATCollector\n";

        $row = function (string $faktur, string $status, string $reference, string $vat) {
            return ',0010613925093000,PT CONTOH,04 - DPP Nilai Lain,'.$faktur.',2025-11-03T00:00:00,November,2025,'
                .$status.',Done,30700000,28141667,'.$vat.',0,JEFFRY RONADHI JAP,'.$reference.",false,\n";
        };

        return $header
            .$row('04002500353922260', 'APPROVED', 'ADS-INV/25-10/0633', '3377000')
            .$row('04002500353848664', 'IN PROGRESS', 'ADS-INV/25-10/0638', '451000')
            .$row('04002500353848631', 'APPROVED', 'ADS-INV/25-10/0671', '77000')
            .$row('04002500353848668', 'APPROVED', 'ADS-INV/25-10/9999', '660000')
            .",,,,,,,,,,,,,,,,,,\n"; // trailing blank row, as CoreTax emits
    }

    private function importFile(string $contents): TaxFileImport
    {
        $fileName = 'phpunit-coretax-'.uniqid().'.csv';
        file_put_contents($this->uploadDir.'/'.$fileName, $contents);

        return TaxFileImport::create([
            'import_number' => 'TFI-TEST-'.uniqid(),
            'file_name' => $fileName,
            'import_date' => now()->toDateString(),
            'file_format' => 'csv',
            'delimiter' => TaxFileImport::DELIMITER_COMMA,
            'status' => 'processing',
        ]);
    }
}
