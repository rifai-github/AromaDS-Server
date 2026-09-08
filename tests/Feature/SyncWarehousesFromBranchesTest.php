<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Warehouse;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SyncWarehousesFromBranchesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->string('address_1')->nullable();
            $table->string('phone_1')->nullable();
            $table->boolean('is_head_office')->default(false);
            $table->boolean('has_warehouse')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('delete_by')->nullable();
            $table->timestamp('delete_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('warehouse_code')->nullable();
            $table->string('name')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->foreignId('warehouse_type_id')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('manager')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_center')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouse_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function test_dry_run_reports_without_writing(): void
    {
        $this->makeBranch(['code' => 'JKT', 'name' => 'Jakarta']);

        $this->artisan('warehouses:sync-from-branches')
            ->assertExitCode(0);

        $this->assertSame(0, Warehouse::count());
    }

    public function test_apply_creates_one_warehouse_per_branch_from_branch_data(): void
    {
        $head = $this->makeBranch([
            'code' => 'JKT',
            'name' => 'Jakarta Pusat',
            'address_1' => 'Jl. Sudirman 1',
            'phone_1' => '021-111',
            'is_head_office' => true,
        ]);
        $branch = $this->makeBranch([
            'code' => 'SBY',
            'name' => 'Surabaya',
            'address_1' => 'Jl. Basuki 2',
            'phone_1' => '031-222',
        ]);

        $this->artisan('warehouses:sync-from-branches --apply')->assertExitCode(0);

        $this->assertSame(2, Warehouse::count());

        $headWarehouse = Warehouse::where('branch_id', $head->id)->first();
        $this->assertSame('Gudang Jakarta Pusat', $headWarehouse->name);
        $this->assertSame('Jl. Sudirman 1', $headWarehouse->address);
        $this->assertSame('021-111', $headWarehouse->phone);
        $this->assertTrue($headWarehouse->is_center);
        $this->assertTrue($headWarehouse->is_active);
        $this->assertStringStartsWith('WH'.date('Ym'), $headWarehouse->warehouse_code);

        $branchWarehouse = Warehouse::where('branch_id', $branch->id)->first();
        $this->assertSame('Gudang Surabaya', $branchWarehouse->name);
        $this->assertFalse($branchWarehouse->is_center);

        $this->assertNotSame($headWarehouse->warehouse_code, $branchWarehouse->warehouse_code);

        // Branch master is kept consistent with the warehouse that now exists.
        $this->assertTrue($head->fresh()->has_warehouse);
        $this->assertTrue($branch->fresh()->has_warehouse);
    }

    public function test_rerun_is_idempotent_and_keeps_manually_added_warehouses(): void
    {
        $branch = $this->makeBranch(['code' => 'BDG', 'name' => 'Bandung']);

        $this->artisan('warehouses:sync-from-branches --apply')->assertExitCode(0);
        $this->assertSame(1, Warehouse::count());

        // A second warehouse added by hand afterwards.
        Warehouse::create([
            'warehouse_code' => 'WH-MANUAL',
            'name' => 'Gudang Spare Part Bandung',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_center' => false,
        ]);

        $this->artisan('warehouses:sync-from-branches --apply')->assertExitCode(0);

        $this->assertSame(2, Warehouse::where('branch_id', $branch->id)->count());
        $this->assertNotNull(Warehouse::where('warehouse_code', 'WH-MANUAL')->first());
    }

    public function test_inactive_branches_are_skipped_unless_flag_given(): void
    {
        $this->makeBranch(['code' => 'OLD', 'name' => 'Cabang Tutup', 'is_active' => false]);

        $this->artisan('warehouses:sync-from-branches --apply')->assertExitCode(0);
        $this->assertSame(0, Warehouse::count());

        $this->artisan('warehouses:sync-from-branches --apply --include-inactive')->assertExitCode(0);
        $this->assertSame(1, Warehouse::count());
    }

    public function test_only_flagged_limits_to_branches_with_has_warehouse(): void
    {
        $this->makeBranch(['code' => 'A', 'name' => 'Punya Gudang', 'has_warehouse' => true]);
        $this->makeBranch(['code' => 'B', 'name' => 'Belum Dicentang']);

        $this->artisan('warehouses:sync-from-branches --apply --only-flagged')->assertExitCode(0);

        $this->assertSame(1, Warehouse::count());
        $this->assertSame('Gudang Punya Gudang', Warehouse::first()->name);
    }

    private function makeBranch(array $attributes): Branch
    {
        return Branch::create(array_merge([
            'is_head_office' => false,
            'has_warehouse' => false,
            'is_active' => true,
        ], $attributes));
    }
}
