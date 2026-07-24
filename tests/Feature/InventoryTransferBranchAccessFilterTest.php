<?php

namespace Tests\Feature;

use App\Http\Controllers\Warehouse\InventoryController;
use App\Models\InventoryTransfer;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InventoryTransferBranchAccessFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('code')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('branch_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id');
            $table->foreignId('user_id');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        Schema::create('user_access_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('access_type');
            $table->json('access_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->foreignId('manager')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_center')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouse_admins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id');
            $table->foreignId('user_id');
            $table->timestamps();
        });

        Schema::create('inventory_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number');
            $table->foreignId('from_warehouse_id');
            $table->foreignId('to_warehouse_id');
            $table->date('transfer_date')->nullable();
            $table->string('status')->default('draft');
            $table->string('approval_status')->default('not_required');
            $table->boolean('is_direct_branch_transfer')->default(false);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('branches')->insert([
            ['id' => 1, 'name' => 'DKI Jakarta', 'code' => 'JKT', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'BALI', 'code' => 'BAL', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'Surabaya', 'code' => 'SBY', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'Medan', 'code' => 'MDN', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('users')->insert([
            ['id' => 1, 'name' => 'M Logistik', 'branch_id' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Creator Lain', 'branch_id' => 4, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('branch_user')->insert([
            ['branch_id' => 1, 'user_id' => 1, 'is_primary' => false, 'created_at' => now(), 'updated_at' => now()],
            ['branch_id' => 3, 'user_id' => 1, 'is_primary' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('user_access_levels')->insert([
            'user_id' => 1,
            'access_type' => 'branch',
            'access_config' => json_encode(['allowed_branches' => [3, 1]]),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('warehouses')->insert([
            ['id' => 1, 'name' => 'Gudang DKI Jakarta', 'branch_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Gudang Bali', 'branch_id' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'Gudang Surabaya', 'branch_id' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'Gudang Medan', 'branch_id' => 5, 'created_at' => now(), 'updated_at' => now()],
        ]);

        InventoryTransfer::create([
            'transfer_number' => 'TR-BALI-IN',
            'from_warehouse_id' => 4,
            'to_warehouse_id' => 3,
            'transfer_date' => now()->toDateString(),
            'created_by' => 2,
        ]);

        InventoryTransfer::create([
            'transfer_number' => 'TR-OTHER',
            'from_warehouse_id' => 4,
            'to_warehouse_id' => 5,
            'transfer_date' => now()->toDateString(),
            'created_by' => 2,
        ]);
    }

    public function test_branch_access_includes_inventory_transfers_to_destination_branch(): void
    {
        Auth::login(User::findOrFail(1));

        $view = app(InventoryController::class)->index(Request::create('/warehouse/inventory-transfers', 'GET'));
        $transferNumbers = collect($view->getData()['paginatedTransfers']->items())
            ->pluck('transfer_number')
            ->all();

        $this->assertContains('TR-BALI-IN', $transferNumbers);
        $this->assertNotContains('TR-OTHER', $transferNumbers);
    }
}
