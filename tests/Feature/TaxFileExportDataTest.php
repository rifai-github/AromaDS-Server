<?php

namespace Tests\Feature;

use App\Http\Controllers\Finance\TaxFileExportController;
use App\Models\TaxFileExport;
use App\Services\Finance\CoreTaxExportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
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
            $table->string('contract_number')->nullable();
            $table->date('invoice_date')->nullable();
            $table->string('invoice_status');
            $table->string('tax_code')->nullable();
            $table->string('tax_number')->nullable();
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

        Schema::create('invoice_rental_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id');
            $table->string('rental_name')->nullable();
            $table->string('building_name')->nullable();
            $table->string('room_name')->nullable();
            $table->integer('quantity')->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('npwp')->nullable();
            $table->string('nitku')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customer_tax_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id');
            $table->string('tax_number')->nullable();
            $table->text('tax_address')->nullable();
            $table->string('nitku')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Left empty on purpose: with no effective setting the service falls back to 11%.
        Schema::create('tax_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('tax_type')->nullable();
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->boolean('is_default')->default(false);
            $table->date('effective_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('active');
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
                'contract_number' => 'ADS-AG/26-07/0001',
                'invoice_date' => '2026-07-10',
                'invoice_status' => 'approved',
                'tax_code' => '04',
                'npwp_number' => '9876543210987650',
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
                'contract_number' => null,
                'invoice_date' => '2026-07-11',
                'invoice_status' => 'draft',
                'tax_code' => null,
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
                'contract_number' => null,
                'invoice_date' => '2026-08-01',
                'invoice_status' => 'approved',
                'tax_code' => null,
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
        Schema::dropIfExists('tax_settings');
        Schema::dropIfExists('customer_tax_settings');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('invoice_rental_details');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('customers');

        parent::tearDown();
    }

    public function test_date_range_exports_only_approved_invoices_in_range(): void
    {
        $export = new TaxFileExport([
            'period_from' => '2026-07-01',
            'period_to' => '2026-07-31',
            'filter_parameters' => [],
        ]);

        $invoices = app(CoreTaxExportService::class)->resolveInvoices($export);

        $this->assertSame(['INV-APPROVED-IN-RANGE'], $invoices->pluck('invoice_number')->all());
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

        $invoices = app(CoreTaxExportService::class)->resolveInvoices($export);

        $this->assertSame(['INV-APPROVED-OUTSIDE-RANGE'], $invoices->pluck('invoice_number')->all());
    }

    /**
     * `customers.npwp` is unused in this app and `invoices.npwp_number` is only a
     * snapshot that is often blank — the NPWP actually lives on the customer's tax
     * setting, which is also where the NITKU comes from.
     */
    public function test_buyer_npwp_falls_back_to_the_customer_tax_setting(): void
    {
        $this->seedSellerAndLines();

        DB::table('customer_tax_settings')->insert([
            'id' => 1,
            'customer_id' => 1,
            'tax_number' => '0002026088072026',
            'tax_address' => 'Jl. Pajak Customer No. 9',
            'nitku' => '000000',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Blank the invoice snapshot, exactly like the invoices that exported empty.
        DB::table('invoices')->where('id', 1)->update(['npwp_number' => null, 'tax_address' => null]);

        $export = $this->newExport('TFE-TEST-NPWP');
        $result = app(CoreTaxExportService::class)->generate($export);
        $fullPath = public_path('uploads/'.$result['file_path']);

        try {
            $book = (new XlsxReader)->load($fullPath);
            $faktur = $book->getSheetByName('Faktur');

            $this->assertSame('0002026088072026', $faktur->getCell('J4')->getValue());
            $this->assertSame('0002026088072026000000', $faktur->getCell('Q4')->getValue());

            $legacy = $book->getSheetByName('E-Faktur-2026-07-01_2026-07-31_');
            $this->assertSame('0002026088072026', $legacy->getCell('H4')->getValue());
            $this->assertSame('Jl. Pajak Customer No. 9', $legacy->getCell('J4')->getValue());

            $book->disconnectWorksheets();
        } finally {
            @unlink($fullPath);
        }
    }

    public function test_export_fails_when_the_buyer_has_no_npwp(): void
    {
        $this->seedSellerAndLines();
        // No snapshot, no tax setting, and no customer NPWP anywhere.
        DB::table('invoices')->where('id', 1)->update(['npwp_number' => null]);
        DB::table('customers')->where('id', 1)->update(['npwp' => null]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('NPWP pembeli tidak ditemukan');

        app(CoreTaxExportService::class)->generate($this->newExport('TFE-TEST-NONPWP'));
    }

    public function test_export_fails_when_an_invoice_has_no_detail_lines(): void
    {
        DB::table('companies')->insert([
            'id' => 1,
            'name' => 'PT Aroma Delivery System',
            'npwp' => '0017556507035000',
            'nitku' => '000000',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $export = new TaxFileExport([
            'export_number' => 'TFE-TEST-0002',
            'period_from' => '2026-07-01',
            'period_to' => '2026-07-31',
            'filter_parameters' => [],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('INV-APPROVED-IN-RANGE');

        app(CoreTaxExportService::class)->generate($export);
    }

    /**
     * Locks the layout CoreTax accepts: sheet names, the seller NPWP on row 1 of
     * "Faktur", headers on row 3, the END terminators, and the DPP Nilai Lain split.
     */
    public function test_generated_workbook_matches_the_coretax_layout(): void
    {
        DB::table('companies')->insert([
            'id' => 1,
            'name' => 'PT Aroma Delivery System',
            'npwp' => '0017556507035000',
            'nitku' => '000000',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('customer_tax_settings')->insert([
            'id' => 1,
            'customer_id' => 1,
            'nitku' => '000007',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('invoice_rental_details')->insert([
            'id' => 1,
            'invoice_id' => 1,
            'rental_name' => 'Aroma Delivery Sys Svc',
            'building_name' => 'GROUND',
            'room_name' => 'STORE (STORE - ADS)',
            'quantity' => 1,
            'unit_price' => 550000,
            'total_price' => 550000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $export = new TaxFileExport([
            'export_number' => 'TFE-TEST-0001',
            'period_from' => '2026-07-01',
            'period_to' => '2026-07-31',
            'filter_parameters' => [],
        ]);

        $result = app(CoreTaxExportService::class)->generate($export);
        $fullPath = public_path('uploads/'.$result['file_path']);

        try {
            $this->assertSame(1, $result['total_records']);

            $book = (new XlsxReader)->load($fullPath);
            $this->assertSame(
                ['Faktur', 'DetailFaktur', 'E-Faktur-2026-07-01_2026-07-31_'],
                $book->getSheetNames()
            );

            $faktur = $book->getSheetByName('Faktur');
            $this->assertSame('0017556507035000', $faktur->getCell('C1')->getValue());
            $this->assertSame('Baris', $faktur->getCell('A3')->getValue());
            $this->assertSame('ID TKU Pembeli', $faktur->getCell('Q3')->getValue());
            $this->assertSame('10/07/2026', $faktur->getCell('B4')->getValue());
            $this->assertSame('Normal', $faktur->getCell('C4')->getValue());
            $this->assertSame('04', $faktur->getCell('D4')->getValue());
            $this->assertSame('INV-APPROVED-IN-RANGE', $faktur->getCell('G4')->getValue());
            $this->assertSame('0017556507035000000000', $faktur->getCell('I4')->getValue());
            $this->assertSame('9876543210987650', $faktur->getCell('J4')->getValue());
            $this->assertSame('9876543210987650000007', $faktur->getCell('Q4')->getValue());
            $this->assertSame('END', $faktur->getCell('A5')->getValue());

            $detail = $book->getSheetByName('DetailFaktur');
            $this->assertSame(1, $detail->getCell('A2')->getValue());
            $this->assertSame('B', $detail->getCell('B2')->getValue());
            $this->assertSame('000000', $detail->getCell('C2')->getValue());
            $this->assertSame(
                'Aroma Delivery Sys Svc - GROUND - STORE (STORE - ADS)',
                $detail->getCell('D2')->getValue()
            );
            $this->assertSame('UM.0024', $detail->getCell('E2')->getValue());
            $this->assertSame(550000, $detail->getCell('I2')->getValue());
            // Code 04 taxes 12% of 11/12 of the DPP, which nets the 11% effective rate.
            $this->assertEqualsWithDelta(504166.6667, $detail->getCell('J2')->getValue(), 0.001);
            $this->assertSame(12, $detail->getCell('K2')->getValue());
            $this->assertSame(60500, $detail->getCell('L2')->getValue());
            $this->assertSame('END', $detail->getCell('A3')->getValue());

            $legacy = $book->getSheetByName('E-Faktur-2026-07-01_2026-07-31_');
            $this->assertSame('FK', $legacy->getCell('A1')->getValue());
            $this->assertSame('LT', $legacy->getCell('A2')->getValue());
            $this->assertSame('OF', $legacy->getCell('A3')->getValue());
            $this->assertSame('FK', $legacy->getCell('A4')->getValue());
            $this->assertSame('INV-APPROVED-IN-RANGE', $legacy->getCell('S4')->getValue());
            $this->assertSame('LT', $legacy->getCell('A5')->getValue());
            $this->assertSame('OF', $legacy->getCell('A6')->getValue());
            $this->assertSame('ADS-AG/26-07/0001', $legacy->getCell('B6')->getValue());
            $this->assertSame('550000.00', $legacy->getCell('H6')->getValue());
            $this->assertSame('60500.00', $legacy->getCell('I6')->getValue());

            $book->disconnectWorksheets();
        } finally {
            @unlink($fullPath);
        }
    }

    private function seedSellerAndLines(): void
    {
        DB::table('companies')->insert([
            'id' => 1,
            'name' => 'PT Aroma Delivery System',
            'npwp' => '0017556507035000',
            'nitku' => '000000',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('invoice_rental_details')->insert([
            'id' => 1,
            'invoice_id' => 1,
            'rental_name' => 'Aroma Delivery Sys Svc',
            'building_name' => 'GROUND',
            'room_name' => 'STORE (STORE - ADS)',
            'quantity' => 1,
            'unit_price' => 550000,
            'total_price' => 550000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function newExport(string $exportNumber): TaxFileExport
    {
        return new TaxFileExport([
            'export_number' => $exportNumber,
            'period_from' => '2026-07-01',
            'period_to' => '2026-07-31',
            'filter_parameters' => [],
        ]);
    }
}
