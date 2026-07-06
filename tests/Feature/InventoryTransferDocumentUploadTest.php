<?php

namespace Tests\Feature;

use App\Http\Controllers\Warehouse\InventoryController;
use App\Models\InventoryTransfer;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Surat pengajuan (branch->center) and surat jalan (center->branch) uploads on
 * InventoryTransfer - QA reported neither existed for the return-to-center flow.
 * Also guards against the bug found while building this: submitForm() in the blade
 * view was JSON.stringify()-ing FormData, which silently drops File objects - fixed
 * separately, but this test locks down the backend half (storeTransfer/updateTransfer
 * actually persisting the uploaded file path).
 */
class InventoryTransferDocumentUploadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_center')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->decimal('minimum_stock', 10, 2)->nullable();
            $table->decimal('maximum_stock', 10, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouse_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('minimum_stock', 10, 2)->default(0);
            $table->decimal('maximum_stock', 10, 2)->default(0);
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number');
            $table->foreignId('from_warehouse_id');
            $table->foreignId('to_warehouse_id');
            $table->date('transfer_date')->nullable();
            $table->string('status')->default('draft');
            $table->boolean('is_direct_branch_transfer')->default(false);
            $table->string('delivery_order_file')->nullable();
            $table->unsignedBigInteger('central_approved_by')->nullable();
            $table->timestamp('central_approved_at')->nullable();
            $table->text('central_approval_notes')->nullable();
            $table->string('submission_letter_file')->nullable();
            $table->unsignedBigInteger('submission_letter_uploaded_by')->nullable();
            $table->timestamp('submission_letter_uploaded_at')->nullable();
            $table->string('delivery_note_file')->nullable();
            $table->unsignedBigInteger('delivery_note_uploaded_by')->nullable();
            $table->timestamp('delivery_note_uploaded_at')->nullable();
            $table->string('source_type', 64)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_transfer_id');
            $table->foreignId('master_product_id');
            $table->integer('quantity')->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->string('movement_type')->nullable();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
        });

        DB::table('users')->insert(['id' => 1, 'name' => 'Admin', 'created_at' => now(), 'updated_at' => now()]);
        Auth::login(User::findOrFail(1));
    }

    private function requestWithFile(string $uri, array $data, array $files): Request
    {
        $request = Request::create($uri, 'POST', $data);
        foreach ($files as $key => $file) {
            $request->files->set($key, $file);
        }

        return $request;
    }

    public function test_store_transfer_persists_submission_letter_and_delivery_note(): void
    {
        $branch = Warehouse::create(['name' => 'Gudang Cabang', 'is_center' => false]);
        $center = Warehouse::create(['name' => 'Gudang Pusat', 'is_center' => true]);
        DB::table('master_products')->insert(['id' => 1, 'name' => 'Diffuser W300 White', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('warehouse_products')->insert([
            'warehouse_id' => $branch->id, 'master_product_id' => 1, 'quantity' => 5,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $request = $this->requestWithFile('/warehouse/inventory-transfers/api/store', [
            'transfer_date' => now()->toDateString(),
            'from_warehouse_id' => $branch->id,
            'to_warehouse_id' => $center->id,
            'status' => 'draft',
            'items' => [['product_id' => 1, 'quantity' => 2]],
        ], [
            'submission_letter_file' => UploadedFile::fake()->create('surat-pengajuan.pdf', 100),
        ]);

        $response = (new InventoryController())->storeTransfer($request);
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode(), $payload['message'] ?? 'no message');
        $this->assertSame('success', $payload['status']);

        $transfer = InventoryTransfer::first();
        $this->assertNotNull($transfer->submission_letter_file);
        Storage::disk('public')->assertExists($transfer->submission_letter_file);
        $this->assertSame(1, $transfer->submission_letter_uploaded_by);
        $this->assertNull($transfer->delivery_note_file);
    }

    public function test_update_transfer_persists_delivery_note_via_method_override(): void
    {
        $branch = Warehouse::create(['name' => 'Gudang Cabang', 'is_center' => false]);
        $center = Warehouse::create(['name' => 'Gudang Pusat', 'is_center' => true]);
        DB::table('master_products')->insert(['id' => 1, 'name' => 'Diffuser W300 White', 'created_at' => now(), 'updated_at' => now()]);

        $transfer = InventoryTransfer::create([
            'transfer_date' => now()->toDateString(),
            'from_warehouse_id' => $branch->id,
            'to_warehouse_id' => $center->id,
            'status' => 'draft',
        ]);

        $request = $this->requestWithFile('/warehouse/inventory-transfers/api/'.$transfer->id.'/update', [
            'transfer_date' => now()->toDateString(),
            'from_warehouse_id' => $branch->id,
            'to_warehouse_id' => $center->id,
            'status' => 'draft',
        ], [
            'delivery_note_file' => UploadedFile::fake()->create('surat-jalan.pdf', 100),
        ]);

        $response = (new InventoryController())->updateTransfer($request, $transfer->id);
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode(), $payload['message'] ?? 'no message');

        $transfer->refresh();
        $this->assertNotNull($transfer->delivery_note_file);
        Storage::disk('public')->assertExists($transfer->delivery_note_file);
        $this->assertSame(1, $transfer->delivery_note_uploaded_by);
    }
}
