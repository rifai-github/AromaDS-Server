<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckFrozenAccount;
use App\Http\Middleware\CheckLoginRestriction;
use App\Http\Middleware\CheckMultiLogin;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerTaxNitkuValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckFrozenAccount::class,
            CheckLoginRestriction::class,
            CheckMultiLogin::class,
        ]);

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->text('roles')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('role_id')->nullable();
            $table->timestamps();
        });

        Schema::create('user_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('permission_id')->nullable();
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->nullable();
            $table->foreignId('permission_id')->nullable();
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('status')->nullable();
            $table->string('npwp')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customer_tax_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable();
            $table->string('label')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('nitku')->nullable();
            $table->string('tax_name')->nullable();
            $table->text('tax_address')->nullable();
            $table->string('tax_type')->nullable();
            $table->string('ppn_code')->nullable();
            $table->decimal('tax_rate', 5, 2)->nullable();
            $table->date('effective_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->nullable();
            $table->boolean('is_active')->default(false);
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('finance_tax_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('description')->nullable();
            $table->string('ppn_status')->nullable();
            $table->string('invoice_status')->nullable();
            $table->string('faktur_pajak_status')->nullable();
            $table->string('customer_status')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('finance_tax_codes')->insert([
            'code' => '04',
            'description' => 'Test PPN Code',
            'sort_order' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('tax_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('tax_code')->nullable();
            $table->string('tax_type')->nullable();
            $table->decimal('tax_rate', 5, 2)->nullable();
            $table->boolean('is_default')->default(false);
            $table->date('effective_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('tax_settings')->insert([
            'name' => 'PPN 11%',
            'tax_code' => '04',
            'tax_type' => 'vat',
            'tax_rate' => 11.00,
            'is_default' => true,
            'effective_date' => '2026-01-01',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        foreach ([
            'tax_settings',
            'finance_tax_codes',
            'customer_tax_settings',
            'customers',
            'role_permissions',
            'user_permission',
            'user_roles',
            'permissions',
            'roles',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_store_rejects_nitku_tax_name_with_short_tax_number(): void
    {
        $user = $this->createUserWithPermission('marketing.customer-taxes.view');
        $customerId = DB::table('customers')->insertGetId([
            'name' => 'Abadi Company',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->postJson(route('company.customer-taxes.store'), [
                'customer_id' => $customerId,
                'tax_name' => 'NITKU',
                'tax_number' => '000123', // 6-digit NITKU number entered where the NPWP number is expected
                'nitku' => '000123',
                'tax_type' => '04',
                'effective_date' => '2026-06-21',
                'status' => 'active',
                'tax_address' => 'Jalan Test',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('tax_number');

        $this->assertSame(0, DB::table('customer_tax_settings')->count());
    }

    public function test_store_accepts_nitku_tax_name_with_valid_parent_npwp_number(): void
    {
        $user = $this->createUserWithPermission('marketing.customer-taxes.view');
        $customerId = DB::table('customers')->insertGetId([
            'name' => 'Abadi Company',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->postJson(route('company.customer-taxes.store'), [
                'customer_id' => $customerId,
                'tax_name' => 'NITKU',
                'tax_number' => '1100011100001114', // 16-digit parent NPWP number
                'nitku' => '015471',
                'tax_type' => '04',
                'effective_date' => '2026-06-21',
                'status' => 'active',
                'tax_address' => 'Jalan Test',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');

        $this->assertSame(1, DB::table('customer_tax_settings')->count());
        $this->assertDatabaseHas('customer_tax_settings', [
            'tax_name' => 'NITKU',
            'tax_number' => '1100011100001114',
            'nitku' => '015471',
        ]);
    }

    private function createUserWithPermission(string $permissionName): User
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Test User',
            'email' => 'test-customer-tax@example.com',
            'password' => 'password',
            'roles' => 'Test Role',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roleId = DB::table('roles')->insertGetId([
            'name' => 'Test Role',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('user_roles')->insert([
            'user_id' => $userId,
            'role_id' => $roleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $permissionId = DB::table('permissions')->insertGetId([
            'name' => $permissionName,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('role_permissions')->insert([
            'role_id' => $roleId,
            'permission_id' => $permissionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::findOrFail($userId);
    }
}
