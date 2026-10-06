<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * QA 5 Okt (JKT-INV/26-10/0001): invoice sudah PAID tapi "Paid Amount" Rp 0, dan
 * "Tax Exported?" NO padahal invoicenya sudah ada di TFE-20261005-0001.
 */
class InvoicePaidAmountAndTaxExportedTest extends TestCase
{
    protected function tearDown(): void
    {
        Schema::dropIfExists('tax_file_exports');
        Schema::dropIfExists('invoices');
        parent::tearDown();
    }

    public function test_invoice_detail_paid_amount_reads_total_paid(): void
    {
        // Kolom paid_amount tidak ada di tabel invoices; datanya ada di total_paid.
        $source = file_get_contents(View::getFinder()->find('finance.invoices.show'));

        $this->assertStringNotContainsString('$invoice->paid_amount', $source);
        $this->assertStringContainsString('$invoice->total_paid', $source);
    }

    public function test_coretax_export_marks_exported_invoices(): void
    {
        $source = file_get_contents(app_path('Services/Finance/CoreTaxExportService.php'));

        $this->assertStringContainsString("update(['is_tax_exported' => true])", $source);
    }

    public function test_backfill_marks_invoices_from_completed_exports_and_coretax_fakturs(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_tax_exported')->default(false);
            $table->string('coretax_faktur_number')->nullable();
        });
        Schema::create('tax_file_exports', function (Blueprint $table) {
            $table->id();
            $table->string('status');
            $table->json('filter_parameters')->nullable();
            $table->softDeletes();
        });

        DB::table('invoices')->insert([
            ['id' => 10, 'coretax_faktur_number' => null],
            ['id' => 11, 'coretax_faktur_number' => '04002600000001'],
            ['id' => 12, 'coretax_faktur_number' => null],
            ['id' => 13, 'coretax_faktur_number' => null],
        ]);
        DB::table('tax_file_exports')->insert([
            ['status' => 'completed', 'filter_parameters' => json_encode(['invoice_ids' => ['10']]), 'deleted_at' => null],
            ['status' => 'failed', 'filter_parameters' => json_encode(['invoice_ids' => ['12']]), 'deleted_at' => null],
            ['status' => 'completed', 'filter_parameters' => json_encode(['invoice_ids' => ['13']]), 'deleted_at' => now()],
        ]);

        $migration = require database_path('migrations/2026_10_06_000001_backfill_invoices_is_tax_exported.php');
        $migration->up();

        $this->assertEquals(
            [10 => 1, 11 => 1, 12 => 0, 13 => 0],
            DB::table('invoices')->orderBy('id')->pluck('is_tax_exported', 'id')->map(fn ($v) => (int) $v)->all()
        );
    }
}
