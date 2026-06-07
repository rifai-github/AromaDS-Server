<?php

namespace Tests\Feature;

use App\Models\Warehouse;
use App\Http\Controllers\Warehouse\WarehouseController;
use App\Services\Warehouse\BranchWarehouseResolver;
use DomainException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class BranchWarehouseResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable();
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_center')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouse_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->nullable();
            $table->string('status')->nullable();
            $table->string('location_type')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_issuings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_receivings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issuing_id')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_resolves_single_active_branch_warehouse(): void
    {
        $warehouse = Warehouse::create([
            'branch_id' => 10,
            'name' => 'Jakarta Warehouse',
            'is_active' => true,
        ]);

        $resolved = app(BranchWarehouseResolver::class)->resolveActiveForBranch(10);

        $this->assertSame($warehouse->id, $resolved->id);
    }

    public function test_rejects_branch_without_active_warehouse(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(BranchWarehouseResolver::NO_ACTIVE_MESSAGE);

        app(BranchWarehouseResolver::class)->resolveActiveForBranch(99);
    }

    public function test_rejects_branch_with_multiple_active_warehouses(): void
    {
        Warehouse::create(['branch_id' => 10, 'name' => 'Warehouse A', 'is_active' => true]);
        Warehouse::create(['branch_id' => 10, 'name' => 'Warehouse B', 'is_active' => true]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(BranchWarehouseResolver::MULTIPLE_ACTIVE_MESSAGE);

        app(BranchWarehouseResolver::class)->resolveActiveForBranch(10);
    }

    public function test_deactivation_guard_checks_receiving_through_issuing_warehouse(): void
    {
        $warehouse = Warehouse::create(['branch_id' => 10, 'name' => 'Warehouse A', 'is_active' => true]);

        $issuingId = \App\Models\InventoryIssuing::create([
            'warehouse_id' => $warehouse->id,
            'status' => 'sent',
        ])->id;

        \App\Models\InventoryReceiving::create([
            'issuing_id' => $issuingId,
            'branch_id' => 10,
            'status' => 'pending',
        ]);

        $method = new ReflectionMethod(WarehouseController::class, 'canDeactivateWarehouse');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke(app(WarehouseController::class), $warehouse));
    }
}
