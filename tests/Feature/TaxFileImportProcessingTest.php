<?php

namespace Tests\Feature;

use App\Http\Controllers\Finance\TaxFileImportController;
use App\Models\TaxFileImport;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class TaxFileImportProcessingTest extends TestCase
{
    private string $testFilePath;

    private array $uploadedImportFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('tax_file_imports', function (Blueprint $table) {
            $table->id();
            $table->string('import_number')->unique();
            $table->string('file_name');
            $table->date('import_date');
            $table->foreignId('bank_id')->nullable();
            $table->string('file_format');
            $table->unsignedInteger('total_records')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->decimal('success_rate', 5, 2)->default(0);
            $table->unsignedInteger('approval_success')->default(0);
            $table->unsignedInteger('not_approved')->default(0);
            $table->unsignedInteger('rejected')->default(0);
            $table->boolean('auto_process')->default(false);
            $table->string('delimiter')->nullable();
            $table->text('notes')->nullable();
            $table->text('error_log')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tax_file_import_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_file_import_id');
            $table->string('invoice_number');
            $table->string('tax_number');
            $table->string('buyer_npwp', 30)->nullable();
            $table->string('buyer_name')->nullable();
            $table->date('tax_date');
            $table->decimal('tax_amount', 15, 2);
            $table->decimal('dpp', 15, 2)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'warning'])->default('pending');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number');
            $table->string('contract_number')->nullable();
            $table->string('invoice_status')->default('approved');
            $table->string('status')->nullable();
            $table->boolean('tax_obligation')->default(true);
            $table->string('faktur_pajak')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('npwp_number')->nullable();
            $table->string('coretax_faktur_number', 50)->nullable();
            $table->date('coretax_faktur_date')->nullable();
            $table->string('coretax_status', 30)->nullable();
            $table->date('invoice_date')->nullable();
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
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

        DB::table('invoices')->insert([
            [
                'id' => 1,
                'invoice_number' => 'INV-001',
                'invoice_status' => 'approved',
                'tax_obligation' => true,
                'faktur_pajak' => 'FP-001',
                'tax_number' => 'TAX-001',
                'invoice_date' => '2026-07-19',
                'tax_amount' => 110000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'invoice_number' => 'INV-002',
                'invoice_status' => 'approved',
                'tax_obligation' => true,
                'faktur_pajak' => null,
                'tax_number' => 'TAX-002',
                'invoice_date' => '2026-07-18',
                'tax_amount' => 220000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $directory = public_path('uploads/tax-file-imports');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $this->testFilePath = $directory.'/tax-file-import-processing-test.csv';
        $handle = fopen($this->testFilePath, 'w');
        fputcsv($handle, ['Reference', 'TaxInvoiceNumber', 'TaxInvoiceDate', 'TaxInvoiceStatus', 'VAT']);
        fputcsv($handle, ['INV-001', '0400250035384444', '2026-07-19T00:00:00', 'APPROVED', '110000']);
        fputcsv($handle, ['INV-002', '0400250035385555', '2026-07-18T00:00:00', 'IN PROGRESS', '220000']);
        fputcsv($handle, ['INV-NOT-FOUND', '0400250035386666', '2026-07-18T00:00:00', 'APPROVED', '330000']);
        fclose($handle);
    }

    protected function tearDown(): void
    {
        if (isset($this->testFilePath) && file_exists($this->testFilePath)) {
            unlink($this->testFilePath);
        }

        foreach ($this->uploadedImportFiles as $uploadedImportFile) {
            if (file_exists($uploadedImportFile)) {
                unlink($uploadedImportFile);
            }
        }

        Schema::dropIfExists('invoice_files');
        Schema::dropIfExists('invoice_activities');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('tax_file_import_details');
        Schema::dropIfExists('tax_file_imports');

        parent::tearDown();
    }

    public function test_process_import_uses_current_detail_schema_and_statuses(): void
    {
        $importId = DB::table('tax_file_imports')->insertGetId([
            'import_number' => 'TFI-TEST-001',
            'file_name' => basename($this->testFilePath),
            'import_date' => '2026-07-19',
            'file_format' => 'csv',
            'delimiter' => ',',
            'status' => 'processing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $method = new ReflectionMethod(TaxFileImportController::class, 'processImportFile');
        $method->invoke(
            app(TaxFileImportController::class),
            TaxFileImport::findOrFail($importId),
            'tax-file-imports/'.basename($this->testFilePath),
        );

        $details = DB::table('tax_file_import_details')->orderBy('id')->get();

        $this->assertSame(['approved', 'warning', 'rejected'], $details->pluck('status')->all());
        $this->assertSame('INV-001', $details[0]->invoice_number);
        $this->assertSame('0400250035384444', $details[0]->tax_number);
        $this->assertSame('INV-NOT-FOUND', $details[2]->invoice_number);
        $this->assertStringContainsString('Tidak ada invoice', $details[2]->remarks);

        // Only the APPROVED row moves its invoice on; the rest are left alone.
        $issued = DB::table('invoices')->where('id', 1)->first();
        $this->assertSame('tax_approved', $issued->invoice_status);
        $this->assertSame('0400250035384444', $issued->coretax_faktur_number);

        $untouched = DB::table('invoices')->where('id', 2)->first();
        $this->assertSame('approved', $untouched->invoice_status);
        $this->assertNull($untouched->coretax_faktur_number);

        $import = TaxFileImport::findOrFail($importId);
        $this->assertSame('completed', $import->status);
        $this->assertSame(3, $import->total_records);
        $this->assertSame(1, $import->success_count);
        $this->assertSame(2, $import->failed_count);
    }

    public function test_process_import_converts_tab_token_to_tab_character(): void
    {
        $tabFilePath = public_path('uploads/tax-file-imports/tax-file-import-processing-tab-test.csv');
        file_put_contents(
            $tabFilePath,
            "Reference\tTaxInvoiceNumber\tTaxInvoiceDate\tTaxInvoiceStatus\tVAT\n"
            ."INV-002\t0400250035387777\t2026-07-18T00:00:00\tAPPROVED\t220000\n",
        );

        try {
            $importId = DB::table('tax_file_imports')->insertGetId([
                'import_number' => 'TFI-TEST-TAB-001',
                'file_name' => basename($tabFilePath),
                'import_date' => '2026-07-19',
                'file_format' => 'csv',
                'delimiter' => TaxFileImport::DELIMITER_TAB,
                'status' => 'processing',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $method = new ReflectionMethod(TaxFileImportController::class, 'processImportFile');
            $method->invoke(
                app(TaxFileImportController::class),
                TaxFileImport::findOrFail($importId),
                'tax-file-imports/'.basename($tabFilePath),
            );

            $detail = DB::table('tax_file_import_details')->sole();
            $import = TaxFileImport::findOrFail($importId);

            $this->assertSame('INV-002', $detail->invoice_number);
            $this->assertSame('approved', $detail->status);
            $this->assertSame('completed', $import->status);
            $this->assertSame(1, $import->success_count);
            $this->assertSame('tax_approved', DB::table('invoices')->where('id', 2)->value('invoice_status'));
        } finally {
            if (file_exists($tabFilePath)) {
                unlink($tabFilePath);
            }
        }
    }

    public function test_show_page_renders_current_import_detail_fields(): void
    {
        $user = new User([
            'name' => 'QA Finance User',
            'email' => 'qa.finance@example.test',
            'department_name' => 'Finance',
            'position_name' => 'Manager',
        ]);
        $user->id = 999;
        $user->exists = true;
        $user->setRelation('roles', collect());
        $user->setRelation('permissions', collect());
        $this->actingAs($user);

        $importId = DB::table('tax_file_imports')->insertGetId([
            'import_number' => 'TFI-TEST-SHOW-001',
            'file_name' => 'coretax-result.xlsx',
            'import_date' => '2026-07-19',
            'file_format' => 'xlsx',
            'total_records' => 1,
            'success_count' => 1,
            'failed_count' => 0,
            'success_rate' => 100,
            'delimiter' => ';',
            'status' => 'completed',
            'processed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('tax_file_import_details')->insert([
            'tax_file_import_id' => $importId,
            'invoice_number' => 'INV-001',
            'tax_number' => 'TAX-001',
            'tax_date' => '2026-07-19',
            'tax_amount' => 110000,
            'status' => 'approved',
            'remarks' => 'Matched with Invoice: INV-001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withoutMiddleware()->get(
            route('finance.tax-file-imports.show', $importId),
        );

        $response->assertOk();
        $response->assertViewIs('finance.tax-file-imports.show');
        $response->assertSee('TFI-TEST-SHOW-001');
        $response->assertSee('coretax-result.xlsx');
        $response->assertSee('INV-001');
        $response->assertSee('TAX-001');
        $response->assertSee('Matched with Invoice: INV-001');
    }

    /**
     * The create form now only accepts PDF Faktur Pajak — the CSV/XLSX result
     * file is no longer offered here (CoreTaxImportService and
     * processImportFile() still exist for the Process button on existing
     * CSV/XLSX import records, unaffected by this).
     */
    public function test_store_rejects_non_pdf_files(): void
    {
        $csv = $this
            ->withoutMiddleware()
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('finance.tax-file-imports.store'), [
                'files' => [UploadedFile::fake()->createWithContent('coretax-result.csv', "a,b\n1,2\n")],
            ]);

        // A plain `mimes:pdf` rejection goes through Laravel's own validation
        // response (an `errors` object), not the controller's custom
        // storeFailure() shape — the create modal's JS already reads
        // `result.errors` first, so this is the right response to send.
        $csv->assertStatus(422);
        $csv->assertJsonValidationErrors('files.0');

        $mixed = $this
            ->withoutMiddleware()
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('finance.tax-file-imports.store'), [
                'files' => [
                    $this->makeFakturPdfUpload('faktur-1.pdf', 'INV-001', '04002600321184875'),
                    UploadedFile::fake()->createWithContent('not-a-faktur.csv', "a,b\n1,2\n"),
                ],
            ]);

        $mixed->assertStatus(422);
        $mixed->assertJsonValidationErrors('files.1');

        $this->assertDatabaseCount('tax_file_imports', 0);
    }

    /**
     * This is the transaction landmine we found: store() used to wrap file
     * creation AND processing in one DB transaction, so a processing failure
     * rolled the import record itself back out of existence, taking the only
     * trace of what was uploaded with it. Processing is no longer optional —
     * there is no Auto Process choice — so this path runs on every single
     * submission now, and the record plus the uploaded file must survive a
     * processing failure so there is something left to diagnose or retry.
     *
     * Each file gets its own row and its own try/catch in the store() loop
     * now, so one file's processing exception marks only that row 'failed'
     * — it no longer fails the whole HTTP request, since $created > 0 still
     * gets a success response summarizing the batch.
     *
     * CoreTaxFakturPdfImportService is deliberately resilient per-file (one
     * bad PDF logs a rejected row rather than throwing — see
     * CoreTaxFakturPdfImportTest), so a REAL failure is hard to trigger
     * black-box. The service is swapped for a mock that throws instead, to
     * exercise the controller's own try/catch directly.
     */
    public function test_a_processing_failure_leaves_the_import_record_and_file_in_place(): void
    {
        $this->mock(\App\Services\Finance\CoreTaxFakturPdfImportService::class, function ($mock) {
            $mock->shouldReceive('process')->once()->andThrow(new \RuntimeException('simulated processing failure'));
        });

        $response = $this
            ->withoutMiddleware()
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('finance.tax-file-imports.store'), [
                'files' => [$this->makeFakturPdfUpload('faktur-1.pdf', 'INV-001', '04002600321184875')],
                'notes' => 'processing-failure-test',
            ]);

        // The row itself was still created, so the request as a whole succeeds.
        $response->assertOk();
        $response->assertJsonPath('status', 'success');

        $import = TaxFileImport::where('notes', 'processing-failure-test')->first();
        $this->assertNotNull($import, 'the import record was rolled back / deleted on a processing failure');
        $this->assertSame('failed', $import->status);
        $this->assertNotNull($import->error_log);

        $filePath = public_path('uploads/tax-file-imports/'.$import->file_name);
        $this->assertFileExists($filePath, 'the uploaded PDF was deleted despite the row surviving');
        $this->uploadedImportFiles[] = $filePath;
    }

    public function test_import_number_sequence_includes_soft_deleted_records(): void
    {
        $date = now()->format('Ymd');

        DB::table('tax_file_imports')->insert([
            [
                'import_number' => "TFI-{$date}-0001",
                'file_name' => 'deleted-1.csv',
                'import_date' => now()->toDateString(),
                'file_format' => 'csv',
                'delimiter' => TaxFileImport::DELIMITER_COMMA,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => now(),
            ],
            [
                'import_number' => "TFI-{$date}-0002",
                'file_name' => 'deleted-2.csv',
                'import_date' => now()->toDateString(),
                'file_format' => 'csv',
                'delimiter' => TaxFileImport::DELIMITER_COMMA,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => now(),
            ],
        ]);

        $this->assertSame("TFI-{$date}-0003", TaxFileImport::generateImportNumber());
    }

    /**
     * Each PDF is stored directly under uploads/tax-file-imports/ (no
     * per-import subfolder, no zip), so a failed row insert only ever needs
     * to clean up that one loose file.
     */
    public function test_failed_store_removes_the_uploaded_pdf_file(): void
    {
        $baseDir = public_path('uploads/tax-file-imports');
        $filesBefore = glob($baseDir.'/*') ?: [];

        DB::statement(<<<'SQL'
            CREATE TRIGGER fail_tax_file_import_insert
            BEFORE INSERT ON tax_file_imports
            BEGIN
                SELECT RAISE(ABORT, 'forced insert failure');
            END
        SQL);

        try {
            $response = $this
                ->withoutMiddleware()
                ->withHeaders([
                    'Accept' => 'application/json',
                    'X-Requested-With' => 'XMLHttpRequest',
                ])
                ->post(route('finance.tax-file-imports.store'), [
                    'files' => [$this->makeFakturPdfUpload('orphan-cleanup-test.pdf', 'INV-001', '04002600321184875')],
                ]);

            // Every file failed to become a row, so the endpoint has nothing to report success on.
            $response->assertServerError();
            $response->assertJsonPath('status', 'error');

            $filesAfter = glob($baseDir.'/*') ?: [];
            $this->assertSame($filesBefore, $filesAfter, 'an uploaded PDF was left behind on a failed insert');
            $this->assertDatabaseCount('tax_file_imports', 0);
        } finally {
            DB::statement('DROP TRIGGER IF EXISTS fail_tax_file_import_insert');

            foreach (array_diff(glob($baseDir.'/*') ?: [], $filesBefore) as $orphanedFile) {
                @unlink($orphanedFile);
            }
        }
    }

    /**
     * End-to-end through the real HTTP endpoint: multiple PDFs in one
     * request now become separate tax_file_imports rows — one per file, no
     * zip — each matched/promoted/archived independently, so one file
     * failing to match never blocks or hides another file's own row.
     */
    public function test_store_accepts_multiple_pdfs_as_separate_rows_and_issues_the_matched_invoice(): void
    {
        $pdfOne = $this->makeFakturPdfUpload('faktur-1.pdf', 'INV-001', '04002600321184875');
        $pdfTwo = $this->makeFakturPdfUpload('faktur-2.pdf', 'INV-DOES-NOT-EXIST', '04002600321184999');

        $response = $this
            ->withoutMiddleware()
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('finance.tax-file-imports.store'), [
                'files' => [$pdfOne, $pdfTwo],
                'notes' => 'pdf-batch-http-test',
            ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'success');

        $imports = TaxFileImport::where('notes', 'pdf-batch-http-test')->orderBy('id')->get();
        $this->assertCount(2, $imports, 'two uploaded PDFs should create two separate import rows');

        foreach ($imports as $import) {
            $this->assertSame('pdf', $import->file_format);
            $this->assertSame('completed', $import->status);
            $this->assertSame(1, $import->total_records);

            // No zip anymore — each row keeps its own plain PDF filename.
            $this->assertStringEndsNotWith('.zip', $import->file_name);
            $filePath = public_path('uploads/tax-file-imports/'.$import->file_name);
            $this->assertFileExists($filePath);
            $this->uploadedImportFiles[] = $filePath;
        }

        [$matchedImport, $unmatchedImport] = $imports[0]->success_count === 1
            ? [$imports[0], $imports[1]]
            : [$imports[1], $imports[0]];

        $this->assertSame(1, $matchedImport->success_count);
        $this->assertSame(0, $unmatchedImport->success_count);
        $this->assertSame(1, $unmatchedImport->failed_count);

        $invoice = DB::table('invoices')->where('id', 1)->first();
        $this->assertSame('tax_approved', $invoice->invoice_status);
        $this->assertSame('04002600321184875', $invoice->coretax_faktur_number);

        $archived = DB::table('invoice_files')->where('invoice_id', 1)->first();
        $this->assertNotNull($archived, 'the matched faktur pajak PDF was not archived onto the invoice');
        $this->uploadedImportFiles[] = public_path($archived->file_path);
    }

    private function makeFakturPdfUpload(string $filename, string $reference, string $fakturNumber): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $filename,
            file_get_contents($this->makeFakturPdfFixture($reference, $fakturNumber)),
        );
    }

    private function makeFakturPdfFixture(string $reference, string $fakturNumber): string
    {
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
            <p>Dasar Pengenaan Pajak &nbsp;&nbsp;&nbsp;&nbsp; 1.000.000,00</p>
            <p>Jumlah PPN (Pajak Pertambahan Nilai) &nbsp;&nbsp;&nbsp;&nbsp; 110.000,00</p>
            <p>Sesuai dengan ketentuan yang berlaku, faktur pajak ini ditandatangani secara elektronik.</p>
            <p>KOTA ADM. JAKARTA BARAT, 19 Juli 2026</p>
            <p>Ditandatangani secara elektronik</p>
            <p>(Referensi: {$reference})</p>
            </body></html>
            HTML;

        $path = sys_get_temp_dir().'/store_pdf_fixture_'.uniqid().'.pdf';
        file_put_contents($path, \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->output());
        $this->uploadedImportFiles[] = $path;

        return $path;
    }
}
