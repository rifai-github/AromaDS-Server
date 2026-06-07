<?php

namespace Tests\Feature;

use App\Models\Warehouse;
use App\Services\Warehouse\BranchWarehouseResolver;
use DomainException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
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
}
