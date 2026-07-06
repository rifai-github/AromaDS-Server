<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobScheduleController;
use App\Models\InventoryTransfer;
use App\Models\MaterialReturn;
use App\Models\MaterialReturnItem;
use App\Models\Warehouse;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Return Cabang -> Pusat: when a branch material return is approved with
 * disposition = forward_to_center and then completed, it should auto-create a
 * draft InventoryTransfer from the branch warehouse to the central warehouse.
 */
class MaterialReturnForwardToCenterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable();
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_center')->default(false);
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

        Schema::create('material_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number');
            $table->unsignedBigInteger('job_schedule_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->string('status')->default('pending');
            $table->string('disposition')->default('keep_branch');
            $table->date('return_date')->nullable();
            $table->unsignedBigInteger('inventory_transfer_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_return_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->integer('quantity')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function makeReturn(Warehouse $branch, array $items): MaterialReturn
    {
        $return = MaterialReturn::create([
            'return_number' => 'RTR-TEST-0001',
            'job_schedule_id' => null,
            'warehouse_id' => $branch->id,
            'status' => MaterialReturn::STATUS_APPROVED,
            'disposition' => MaterialReturn::DISPOSITION_FORWARD_TO_CENTER,
            'return_date' => now()->toDateString(),
        ]);

        foreach ($items as $it) {
            MaterialReturnItem::create([
                'material_return_id' => $return->id,
                'product_id' => $it['product_id'],
                'quantity' => $it['quantity'],
            ]);
        }

        return $return->load('items');
    }

    private function invokeForward(MaterialReturn $return, ?Warehouse $from)
    {
        $method = new ReflectionMethod(JobScheduleController::class, 'createForwardTransferForReturn');
        $method->setAccessible(true);

        return $method->invoke(new JobScheduleController, $return, $from);
    }

    public function test_forward_creates_draft_transfer_from_branch_to_center(): void
    {
        $branch = Warehouse::create(['name' => 'Gudang Cabang A', 'is_center' => false]);
        $center = Warehouse::create(['name' => 'Gudang Pusat', 'is_center' => true]);

        $return = $this->makeReturn($branch, [
            ['product_id' => 101, 'quantity' => 3],
            ['product_id' => 102, 'quantity' => 5],
        ]);

        $transfer = $this->invokeForward($return, $branch);

        $this->assertInstanceOf(InventoryTransfer::class, $transfer);
        $this->assertEquals($branch->id, $transfer->from_warehouse_id);
        $this->assertEquals($center->id, $transfer->to_warehouse_id);
        $this->assertEquals('draft', $transfer->status);
        $this->assertEquals('material_return', $transfer->source_type);
        $this->assertEquals($return->id, $transfer->source_id);

        $this->assertCount(2, $transfer->transferItems()->get());
        $this->assertEquals(3, $transfer->transferItems()->where('master_product_id', 101)->value('quantity'));
        $this->assertEquals(5, $transfer->transferItems()->where('master_product_id', 102)->value('quantity'));

        $this->assertEquals($transfer->id, $return->fresh()->inventory_transfer_id);
    }

    public function test_forward_skipped_when_no_center_warehouse(): void
    {
        $branch = Warehouse::create(['name' => 'Gudang Cabang A', 'is_center' => false]);

        $return = $this->makeReturn($branch, [['product_id' => 101, 'quantity' => 3]]);

        $transfer = $this->invokeForward($return, $branch);

        $this->assertNull($transfer);
        $this->assertEquals(0, InventoryTransfer::count());
        $this->assertNull($return->fresh()->inventory_transfer_id);
    }

    public function test_forward_skipped_when_from_is_center(): void
    {
        $center = Warehouse::create(['name' => 'Gudang Pusat', 'is_center' => true]);

        $return = $this->makeReturn($center, [['product_id' => 101, 'quantity' => 3]]);

        // from == center: nothing to forward.
        $transfer = $this->invokeForward($return, $center);

        $this->assertNull($transfer);
        $this->assertEquals(0, InventoryTransfer::count());
    }
}
