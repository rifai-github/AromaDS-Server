<?php

namespace Tests\Feature;

use App\Http\Controllers\Warehouse\StockOpnameController;
use App\Models\StockOpname;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StockOpnameBlindCountVisibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('roles')->nullable();
            $table->string('data_restriction')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->json('permissions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('role_id')->nullable();
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->nullable();
            $table->foreignId('permission_id')->nullable();
            $table->timestamps();
        });

        Schema::create('user_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('permission_id')->nullable();
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('sku')->nullable();
            $table->string('packaging_size')->nullable();
            $table->string('unit')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->string('opname_no')->nullable();
            $table->string('opname_number')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('person_responsible')->nullable();
            $table->date('opname_date')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('stock_opname_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->integer('system_stock')->nullable();
            $table->integer('physical_stock')->nullable();
            $table->integer('variance')->nullable();
            $table->json('scanned_serial_numbers')->nullable();
            $table->timestamps();
        });

        DB::table('users')->insert([
            'id' => 10,
            'name' => 'Warehouse Admin',
            'email' => 'warehouse-admin@example.test',
            'roles' => 'Warehouse Admin',
            'data_restriction' => 'branch',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('roles')->insert([
            'id' => 5,
            'name' => 'Warehouse Admin',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('permissions')->insert([
            'id' => 7,
            'name' => 'warehouse.stock-opnames.approve',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('user_roles')->insert([
            'user_id' => 10,
            'role_id' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('role_permissions')->insert([
            'role_id' => 5,
            'permission_id' => 7,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('branches')->insert([
            'id' => 1,
            'name' => 'Bandung Branch',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('warehouses')->insert([
            'id' => 1,
            'name' => 'Warehouse Bandung',
            'branch_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('master_products')->insert([
            'id' => 1,
            'name' => 'Aroma Diffuser Premium',
            'sku' => 'AD001',
            'packaging_size' => '500ml',
            'unit' => 'milliliter',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        foreach ([
            'stock_opname_details',
            'stock_opnames',
            'master_products',
            'warehouses',
            'branches',
            'user_permission',
            'role_permissions',
            'user_roles',
            'permissions',
            'roles',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_approver_cannot_see_system_stock_while_opname_is_in_progress(): void
    {
        Auth::login(User::findOrFail(10));

        $html = $this->renderStockOpname('in-progress');

        $this->assertStringNotContainsString('System Stock', $html);
        $this->assertStringNotContainsString('Variance', $html);
        $this->assertDoesNotMatchRegularExpression('/>\s*42\s*<\/td>/', $html);
    }

    public function test_approver_can_see_system_stock_after_opname_is_ready_for_approval(): void
    {
        Auth::login(User::findOrFail(10));

        $html = $this->renderStockOpname('waiting for approval');

        $this->assertStringContainsString('System Stock', $html);
        $this->assertStringContainsString('Variance', $html);
        $this->assertMatchesRegularExpression('/>\s*42\s*<\/td>/', $html);
    }

    private function renderStockOpname(string $status): string
    {
        DB::table('stock_opnames')->delete();
        DB::table('stock_opname_details')->delete();

        DB::table('stock_opnames')->insert([
            'id' => 20,
            'opname_no' => 'BDG-SO/26-05/0001',
            'opname_number' => 'BDG-SO/26-05/0001',
            'branch_id' => 1,
            'warehouse_id' => 1,
            'person_responsible' => 10,
            'opname_date' => '2026-05-11',
            'status' => $status,
            'created_by' => 10,
            'updated_by' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('stock_opname_details')->insert([
            'stock_opname_id' => 20,
            'master_product_id' => 1,
            'system_stock' => 42,
            'physical_stock' => null,
            'variance' => -42,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return app(StockOpnameController::class)
            ->show(StockOpname::findOrFail(20))
            ->render();
    }
}
