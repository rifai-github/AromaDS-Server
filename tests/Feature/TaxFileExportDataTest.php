<?php

namespace Tests\Feature;

use App\Http\Controllers\Finance\TaxFileExportController;
use App\Models\TaxFileExport;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class TaxFileExportDataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('npwp')->nullable();
            $table->text('npwp_address')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number');
            $table->foreignId('customer_id');
            $table->date('invoice_date')->nullable();
            $table->string('invoice_status');
            $table->string('npwp_number')->nullable();
            $table->text('tax_address')->nullable();
            $table->text('billing_address')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('subtotal_after_discount', 15, 2)->nullable();
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->text('additional_notes')->nullable();
            $table->string('period_invoice')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('customers')->insert([
            'id' => 1,
            'name' => 'PT Approved Customer',
            'npwp' => '012345678901234',
            'npwp_address' => 'Jl. Pajak No. 1',
            'address' => 'Jl. Umum No. 2',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('invoices')->insert([
            [
                'id' => 1,
                'invoice_number' => 'INV-APPROVED-IN-RANGE',
                'customer_id' => 1,
                'invoice_date' => '2026-07-10',
                'invoice_status' => 'approved',
                'npwp_number' => '987654321098765',
                'tax_address' => 'Jl. Snapshot Pajak No. 3',
                'subtotal' => 1000000,
                'subtotal_after_discount' => 900000,
                'tax_amount' => 99000,
                'additional_notes' => 'Approved invoice',
                'period_invoice' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'invoice_number' => 'INV-DRAFT-IN-RANGE',
                'customer_id' => 1,
                'invoice_date' => '2026-07-11',
                'invoice_status' => 'draft',
                'npwp_number' => null,
                'tax_address' => null,
                'subtotal' => 2000000,
                'subtotal_after_discount' => null,
                'tax_amount' => 220000,
                'additional_notes' => null,
                'period_invoice' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'invoice_number' => 'INV-APPROVED-OUTSIDE-RANGE',
                'customer_id' => 1,
                'invoice_date' => '2026-08-01',
                'invoice_status' => 'approved',
                'npwp_number' => null,
                'tax_address' => null,
                'subtotal' => 3000000,
                'subtotal_after_discount' => null,
                'tax_amount' => 330000,
                'additional_notes' => null,
                'period_invoice' => 'August 2026',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('customers');

        parent::tearDown();
    }

    public function test_date_range_exports_only_approved_invoices_with_real_values(): void
    {
        $export = new TaxFileExport([
            'period_from' => '2026-07-01',
            'period_to' => '2026-07-31',
            'filter_parameters' => [],
        ]);

        $data = $this->generateData($export);

        $this->assertCount(2, $data);
        $this->assertSame([
            '987654321098765',
            'PT Approved Customer',
            'Jl. Snapshot Pajak No. 3',
            '2026-07-10',
            'INV-APPROVED-IN-RANGE',
            '900000.00',
            '99000.00',
            'Approved invoice',
        ], $data[1]);
    }

    public function test_specific_invoice_selection_lists_approved_invoices_only(): void
    {
        $request = \Illuminate\Http\Request::create('/finance/tax-file-exports/create', 'GET', server: [
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ]);
        app()->instance('request', $request);

        $response = app(TaxFileExportController::class)->create();
        $invoices = collect($response->getData(true)['invoices']);

        $this->assertSame([3, 1], $invoices->pluck('id')->all());
        $this->assertNotContains(2, $invoices->pluck('id')->all());
    }

    public function test_specific_invoice_export_uses_selected_approved_ids_only(): void
    {
        $export = new TaxFileExport([
            'period_from' => '2026-07-01',
            'period_to' => '2026-07-31',
            'filter_parameters' => ['invoice_ids' => [2, 3]],
        ]);

        $data = $this->generateData($export);

        $this->assertCount(2, $data);
        $this->assertSame('INV-APPROVED-OUTSIDE-RANGE', $data[1][4]);
        $this->assertSame('August 2026', $data[1][7]);
    }

    private function generateData(TaxFileExport $export): array
    {
        $method = new ReflectionMethod(TaxFileExportController::class, 'generateESPTData');

        return $method->invoke(app(TaxFileExportController::class), $export);
    }
}
