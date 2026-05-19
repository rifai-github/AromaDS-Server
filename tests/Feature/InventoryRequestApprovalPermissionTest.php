<?php

namespace Tests\Feature;

use App\Models\InventoryRequest;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InventoryRequestApprovalPermissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckFrozenAccount::class,
            \App\Http\Middleware\CheckLoginRestriction::class,
            \App\Http\Middleware\CheckMultiLogin::class,
        ]);

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nik')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('roles')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->foreignId('update_by_1')->nullable();
            $table->timestamp('update_at_1')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->longText('permissions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('system_reserved')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->boolean('system_reserved')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('role_id');
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id');
            $table->foreignId('permission_id');
            $table->timestamps();
        });

        Schema::create('user_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('permission_id');
        });

        Schema::create('inventory_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->foreignId('requested_by')->nullable();
            $table->date('request_date')->nullable();
            $table->date('required_date')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_request_id');
            $table->foreignId('master_product_id')->nullable();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('approved_qty', 10, 2)->nullable();
            $table->decimal('issued_qty', 10, 2)->nullable();
            $table->decimal('received_qty', 10, 2)->nullable();
            $table->decimal('returned_qty', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->foreignId('update_by_1')->nullable();
            $table->timestamp('update_at_1')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        foreach ([
            'inventory_request_items',
            'master_products',
            'inventory_requests',
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

    public function test_user_with_update_but_without_approve_permission_cannot_approve_pending_request(): void
    {
        $user = $this->createUserWithPermissions(['warehouse.inventory-requests.update']);
        $inventoryRequest = InventoryRequest::create([
            'request_number' => 'BDG-IRQ/26-05/0003',
            'status' => 'pending',
            'requested_by' => $user->id,
            'request_date' => now()->toDateString(),
            'required_date' => now()->toDateString(),
        ]);
        \DB::table('master_products')->insert(['id' => 1, 'name' => 'Test Product']);
        $inventoryRequest->items()->create([
            'master_product_id' => 1,
            'quantity' => 1,
        ]);

        $this->actingAs($user)
            ->from(route('warehouse.inventory-requests.show', $inventoryRequest))
            ->post(route('warehouse.inventory-requests.approve', $inventoryRequest))
            ->assertRedirect(route('warehouse.inventory-requests.show', $inventoryRequest))
            ->assertSessionHas('error', 'Anda tidak memiliki akses untuk approve Inventory Request.');

        $this->assertDatabaseHas('inventory_requests', [
            'id' => $inventoryRequest->id,
            'status' => 'pending',
            'approved_by' => null,
        ]);
    }

    private function createUserWithPermissions(array $permissionNames): User
    {
        $user = User::create([
            'nik' => 'NIK'.uniqid(),
            'name' => 'Warehouse User',
            'email' => uniqid().'@example.test',
            'username' => 'user'.uniqid(),
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $roleId = \DB::table('roles')->insertGetId([
            'name' => 'Operation Gudang',
            'permissions' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('user_roles')->insert([
            'user_id' => $user->id,
            'role_id' => $roleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($permissionNames as $permissionName) {
            $permissionId = \DB::table('permissions')->insertGetId([
                'name' => $permissionName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            \DB::table('role_permissions')->insert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $user->fresh();
    }
}
