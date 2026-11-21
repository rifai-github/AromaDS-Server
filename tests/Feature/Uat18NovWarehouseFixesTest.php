<?php

namespace Tests\Feature;

use App\Http\Controllers\Warehouse\MasterRentalController;
use App\Http\Controllers\Warehouse\StockOpnameController;
use App\Models\StockOpname;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * UAT 18 Nov 2026 - modul Warehouse: sort Master Rental, teks IP kamera, import Stock Opname,
 * dan SN Stock Adjustment yang tak boleh di-uppercase.
 */
class Uat18NovWarehouseFixesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('rental_service_frequencies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_rentals', function (Blueprint $table) {
            $table->id();
            $table->string('rental_code')->nullable();
            $table->string('rental_name')->nullable();
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('daily_price', 15, 2)->default(0);
            $table->decimal('monthly_price', 15, 2)->default(0);
            $table->integer('install_duration')->nullable();
            $table->integer('service_duration')->nullable();
            $table->foreignId('service_frequency_id')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('sku')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->string('opname_number')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('stock_opname_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->integer('system_stock')->default(0);
            $table->integer('physical_stock')->nullable();
            $table->integer('variance')->default(0);
            $table->text('notes')->nullable();
            $table->json('scanned_serial_numbers')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        foreach (['stock_opname_details', 'stock_opnames', 'master_products', 'master_rentals', 'rental_service_frequencies', 'users'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    private function seedRentals(): void
    {
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Zaki', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Ani', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('rental_service_frequencies')->insert([
            ['id' => 1, 'name' => 'Monthly', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Bi-Weekly', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('master_rentals')->insert([
            ['id' => 1, 'rental_code' => 'R-002', 'rental_name' => 'Bravo', 'monthly_price' => 300, 'service_frequency_id' => 1, 'created_by' => 2, 'created_at' => '2026-11-01 00:00:00', 'updated_at' => now()],
            ['id' => 2, 'rental_code' => 'R-003', 'rental_name' => 'Alpha', 'monthly_price' => 100, 'service_frequency_id' => 2, 'created_by' => 1, 'created_at' => '2026-11-03 00:00:00', 'updated_at' => now()],
            ['id' => 3, 'rental_code' => 'R-001', 'rental_name' => 'Charlie', 'monthly_price' => 200, 'service_frequency_id' => 1, 'created_by' => 1, 'created_at' => '2026-11-02 00:00:00', 'updated_at' => now()],
        ]);
    }

    private function rentalIdsSortedBy(array $query): array
    {
        $request = Request::create('/warehouse/master-rentals', 'GET', $query);
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $this->app->instance('request', $request);

        $payload = app(MasterRentalController::class)->index($request)->getData(true);

        return array_column($payload['data'], 'id');
    }

    public function test_master_rental_index_honours_global_header_sort_params(): void
    {
        $this->seedRentals();

        $this->assertSame([2, 1, 3], $this->rentalIdsSortedBy(['sort' => 'rental_name', 'direction' => 'asc']));
        $this->assertSame([3, 1, 2], $this->rentalIdsSortedBy(['sort' => 'rental_name', 'direction' => 'desc']));
        $this->assertSame([1, 3, 2], $this->rentalIdsSortedBy(['sort' => 'monthly_price', 'direction' => 'desc']));
        // Kolom relasi dari header (data-column) diurutkan lewat subquery.
        $this->assertSame([1, 2, 3], $this->rentalIdsSortedBy(['sort' => 'creator.name', 'direction' => 'asc']));
        $this->assertSame(2, $this->rentalIdsSortedBy(['sort' => 'serviceFrequency__name', 'direction' => 'asc'])[0]);
        // Kompatibilitas lama sort_by/sort_order tetap jalan.
        $this->assertSame([3, 1, 2], $this->rentalIdsSortedBy(['sort_by' => 'rental_code', 'sort_order' => 'asc']));
        // Kolom tak dikenal / arah aneh -> default created_at desc, tanpa error SQL.
        $this->assertSame([2, 3, 1], $this->rentalIdsSortedBy(['sort' => 'id;drop', 'direction' => 'sideways']));
        $this->assertSame([3, 1, 2], $this->rentalIdsSortedBy(['sort' => 'rental_name', 'direction' => 'desc; drop']));
    }

    public function test_camera_instructions_no_longer_hardcode_old_server_ip(): void
    {
        foreach (['inventory-receivings', 'inventory-issuings'] as $module) {
            $source = file_get_contents(resource_path("views/warehouse/{$module}/show.blade.php"));

            $this->assertStringNotContainsString('72.60.77.23', $source, $module);
            $this->assertStringContainsString("yaitu: <code>{{ url('/') }}</code>", $source, $module);
        }
    }

    public function test_stock_opname_import_skips_blank_and_zero_physical_stock(): void
    {
        DB::table('master_products')->insert([
            ['id' => 1, 'name' => 'Isi', 'sku' => 'SKU-ISI', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Kosong', 'sku' => 'SKU-KOSONG', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Nol', 'sku' => 'SKU-NOL', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'Tak Ada', 'sku' => 'SKU-LAIN', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('stock_opnames')->insert(['id' => 1, 'opname_number' => 'SO-1', 'status' => 'in-progress', 'created_at' => now(), 'updated_at' => now()]);
        foreach ([1, 2, 3, 4] as $productId) {
            DB::table('stock_opname_details')->insert([
                'stock_opname_id' => 1, 'master_product_id' => $productId, 'system_stock' => 5,
                'physical_stock' => 9, 'variance' => 4, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['Stock Opname'],
            ['No', 'Product Code', 'Product Name', 'Stock Fisik'],
            [1, 'SKU-ISI', 'Isi', 7],
            [2, 'SKU-KOSONG', 'Kosong', null],
            [3, 'SKU-NOL', 'Nol', 0],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'opname').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        $request = Request::create('/warehouse/stock-opnames/1/import-stock', 'POST', [], [], [
            'import_file' => new UploadedFile($path, 'opname.xlsx', null, null, true),
        ]);

        app(StockOpnameController::class)->importStock($request, StockOpname::findOrFail(1));
        @unlink($path);

        $physical = DB::table('stock_opname_details')->pluck('physical_stock', 'master_product_id')->all();
        $this->assertSame(7, (int) $physical[1]);
        // Kosong dan 0 dilewati: nilai lama tetap, tidak tertimpa 0.
        $this->assertSame(9, (int) $physical[2]);
        $this->assertSame(9, (int) $physical[3]);
        $this->assertSame(9, (int) $physical[4]);

        $this->assertSame(
            'Berhasil import 1 produk. 2 produk dilewati karena Stock Fisik kosong/0. 1 produk tidak ditemukan dalam file.',
            session('success')
        );
    }

    public function test_stock_adjustment_increase_keeps_serial_case_and_neutral_wording(): void
    {
        $source = file_get_contents(resource_path('views/warehouse/stock-adjustments/show.blade.php'));

        $this->assertStringNotContainsString('scanned.toUpperCase()', $source);
        $this->assertStringNotContainsString('sn.trim().toUpperCase()', $source);
        $this->assertStringNotContainsString('masukkan SN unit tambahan', $source);
        $this->assertStringContainsString('product.requires_unique_serial_number', $source);
    }
}
