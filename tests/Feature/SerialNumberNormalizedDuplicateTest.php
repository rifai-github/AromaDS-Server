<?php

namespace Tests\Feature;

use App\Http\Controllers\Warehouse\SerialNumberController;
use App\Http\Controllers\Warehouse\SerialNumberImportController;
use App\Models\SerialNumber;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SerialNumberNormalizedDuplicateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('warehouse_code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->boolean('has_serial_number')->default(false);
            $table->boolean('is_unit')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('sku')->nullable();
            $table->unsignedBigInteger('product_category_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number');
            $table->string('status')->nullable();
            $table->string('condition_status')->nullable();
            $table->string('location_type')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('master_product_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        \DB::table('warehouses')->insert(['id' => 1, 'name' => 'Gudang Jakarta', 'warehouse_code' => 'WH-JKT']);
        \DB::table('product_categories')->insert(['id' => 1, 'code' => 'DISP', 'name' => 'Dispenser', 'has_serial_number' => true, 'is_unit' => true]);
        \DB::table('product_categories')->insert(['id' => 2, 'code' => 'RFL', 'name' => 'Refill', 'has_serial_number' => true, 'is_unit' => false]);
        \DB::table('master_products')->insert(['id' => 1, 'name' => 'Dispenser Aroma', 'sku' => 'DISP-001', 'product_category_id' => 1]);
        \DB::table('master_products')->insert(['id' => 2, 'name' => 'Aroma Lemongrass 100ml', 'sku' => 'RFL-001', 'product_category_id' => 2]);
        SerialNumber::create([
            'serial_number' => 'SN-DUP-001',
            'status' => 'ready',
            'warehouse_id' => 1,
            'master_product_id' => 1,
        ]);
        SerialNumber::create([
            'serial_number' => 'SN-BATCH-001',
            'status' => 'ready',
            'warehouse_id' => 1,
            'master_product_id' => 2,
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('serial_numbers');
        Schema::dropIfExists('master_products');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('warehouses');

        parent::tearDown();
    }

    private function csvUpload(string $contents): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'sn-import').'.csv';
        file_put_contents($path, $contents);

        return new UploadedFile($path, 'serial-numbers.csv', 'text/csv', null, true);
    }

    public function test_manual_create_rejects_duplicate_serial_after_trim_and_uppercase(): void
    {
        $request = Request::create('/warehouse/serial-numbers', 'POST', [
            'warehouse_id' => 1,
            'master_product_id' => 1,
            'serial_number' => ' sn-dup-001 ',
            'status' => 'ready',
            'condition_status' => 'new',
        ]);
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $response = (new SerialNumberController)->store($request);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertStringContainsString('SN-DUP-001', $response->getData(true)['message']);
    }

    public function test_import_preview_marks_normalized_duplicate_as_existing(): void
    {
        $request = Request::create('/warehouse/serial-numbers/import-preview', 'POST');
        $request->files->set('file', $this->csvUpload(
            "serial_number,product_sku,status,condition_status,warehouse\n sn-dup-001 ,DISP-001,ready,new,WH-JKT\nSN-NEW-001,DISP-001,ready,new,WH-JKT\n"
        ));

        $response = (new SerialNumberImportController)->preview($request);
        $payload = $response->getData(true);

        $this->assertSame('success', $payload['status']);
        $this->assertSame(1, $payload['preview']['existing']);
        $this->assertSame(1, $payload['preview']['new']);
        $this->assertTrue($payload['preview']['preview_data'][0]['exists']);
    }

    public function test_manual_create_allows_shared_serial_for_non_unit_product(): void
    {
        $request = Request::create('/warehouse/serial-numbers', 'POST', [
            'warehouse_id' => 1,
            'master_product_id' => 2,
            'serial_number' => ' sn-batch-001 ',
            'status' => 'ready',
            'condition_status' => 'new',
        ]);
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $response = (new SerialNumberController)->store($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('success', $response->getData(true)['status']);
        $this->assertSame(2, SerialNumber::whereNormalizedSerialNumber('SN-BATCH-001')->count());
    }

    public function test_import_preview_treats_non_unit_shared_serial_as_new(): void
    {
        $request = Request::create('/warehouse/serial-numbers/import-preview', 'POST');
        $request->files->set('file', $this->csvUpload(
            "serial_number,product_sku,status,condition_status,warehouse\n sn-batch-001 ,RFL-001,ready,new,WH-JKT\n sn-batch-001 ,RFL-001,ready,new,WH-JKT\n"
        ));

        $response = (new SerialNumberImportController)->preview($request);
        $payload = $response->getData(true);

        $this->assertSame('success', $payload['status']);
        $this->assertSame(0, $payload['preview']['existing']);
        $this->assertSame(2, $payload['preview']['new']);
        $this->assertEmpty($payload['preview']['errors']);
    }

    public function test_import_allows_shared_serial_for_non_unit_product(): void
    {
        $request = Request::create('/warehouse/serial-numbers/import', 'POST');
        $request->files->set('file', $this->csvUpload(
            "serial_number,product_sku,status,condition_status,warehouse\nSN-BATCH-001,RFL-001,ready,new,WH-JKT\nSN-BATCH-001,RFL-001,ready,new,WH-JKT\n"
        ));

        $response = (new SerialNumberImportController)->import($request);
        $payload = $response->getData(true);

        $this->assertSame('success', $payload['status']);
        $this->assertSame(2, $payload['stats']['success']);
        $this->assertSame(0, $payload['stats']['failed']);
        $this->assertSame(3, SerialNumber::whereNormalizedSerialNumber('SN-BATCH-001')->count());
    }

    public function test_check_serial_number_finds_value_case_insensitively(): void
    {
        $request = Request::create('/warehouse/serial-numbers/check', 'POST', [
            'serial_number' => ' sn-dup-001 ',
        ]);

        $response = (new SerialNumberController)->checkSerialNumber($request);
        $payload = $response->getData(true);

        $this->assertTrue($payload['found']);
        $this->assertSame('SN-DUP-001', $payload['serialNumber']['serial_number']);
    }
}
