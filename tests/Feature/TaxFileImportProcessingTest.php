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
            $table->string('import_number');
            $table->string('file_name');
            $table->date('import_date');
            $table->foreignId('bank_id')->nullable();
            $table->string('file_format');
            $table->unsignedInteger('total_records')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->decimal('success_rate', 5, 2)->default(0);
            $table->boolean('auto_process')->default(false);
            $table->boolean('skip_header')->default(true);
            $table->string('delimiter')->default(',');
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
            $table->date('tax_date');
            $table->decimal('tax_amount', 15, 2);
            $table->enum('status', ['pending', 'approved', 'rejected', 'warning'])->default('pending');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number');
            $table->string('faktur_pajak')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('npwp_number')->nullable();
            $table->date('invoice_date')->nullable();
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tax_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('no_faktur')->nullable();
            $table->boolean('approved')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('invoices')->insert([
            [
                'id' => 1,
                'invoice_number' => 'INV-001',
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
                'faktur_pajak' => null,
                'tax_number' => 'TAX-002',
                'invoice_date' => '2026-07-18',
                'tax_amount' => 220000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('tax_invoices')->insert([
            'id' => 1,
            'no_faktur' => 'FP-001',
            'approved' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $directory = public_path('uploads/tax-file-imports');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $this->testFilePath = $directory.'/tax-file-import-processing-test.csv';
        $handle = fopen($this->testFilePath, 'w');
        fputcsv($handle, ['invoice_number', 'coretax_status']);
        fputcsv($handle, ['INV-001', 'Approved']);
        fputcsv($handle, ['INV-002', 'Pending']);
        fputcsv($handle, ['INV-NOT-FOUND', 'Rejected']);
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

        Schema::dropIfExists('tax_invoices');
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
            'skip_header' => true,
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
        $this->assertSame('TAX-001', $details[0]->tax_number);
        $this->assertSame('N/A', $details[2]->invoice_number);
        $this->assertStringContainsString('INV-NOT-FOUND', $details[2]->remarks);

        $import = TaxFileImport::findOrFail($importId);
        $this->assertSame('completed', $import->status);
        $this->assertSame(3, $import->total_records);
        $this->assertSame(2, $import->success_count);
        $this->assertSame(1, $import->failed_count);
        $this->assertTrue((bool) DB::table('tax_invoices')->where('id', 1)->value('approved'));
    }

    public function test_process_import_converts_tab_token_to_tab_character(): void
    {
        $tabFilePath = public_path('uploads/tax-file-imports/tax-file-import-processing-tab-test.csv');
        file_put_contents(
            $tabFilePath,
            "invoice_number\tcoretax_status\nINV-002\tPending\n",
        );

        try {
            $importId = DB::table('tax_file_imports')->insertGetId([
                'import_number' => 'TFI-TEST-TAB-001',
                'file_name' => basename($tabFilePath),
                'import_date' => '2026-07-19',
                'file_format' => 'csv',
                'skip_header' => true,
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
            $this->assertSame('warning', $detail->status);
            $this->assertSame('completed', $import->status);
            $this->assertSame(1, $import->success_count);
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
            'skip_header' => true,
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

    public function test_store_accepts_each_supported_delimiter(): void
    {
        $delimiters = [
            'comma' => TaxFileImport::DELIMITER_COMMA,
            'semicolon' => TaxFileImport::DELIMITER_SEMICOLON,
            'tab' => TaxFileImport::DELIMITER_TAB,
        ];

        foreach ($delimiters as $label => $delimiter) {
            $file = UploadedFile::fake()->create(
                "delimiter-{$label}.csv",
                1,
                'text/csv',
            );

            $response = $this
                ->withoutMiddleware()
                ->withHeaders([
                    'Accept' => 'application/json',
                    'X-Requested-With' => 'XMLHttpRequest',
                ])
                ->post(route('finance.tax-file-imports.store'), [
                    'file' => $file,
                    'auto_process' => '0',
                    'skip_header' => '1',
                    'delimiter' => $delimiter,
                    'notes' => "delimiter-test-{$label}",
                ]);

            $response->assertOk();
            $response->assertJsonPath('status', 'success');

            $import = TaxFileImport::where('notes', "delimiter-test-{$label}")->firstOrFail();

            $this->assertSame($delimiter, $import->delimiter);
            $this->uploadedImportFiles[] = public_path('uploads/tax-file-imports/'.$import->file_name);
        }
    }
}
