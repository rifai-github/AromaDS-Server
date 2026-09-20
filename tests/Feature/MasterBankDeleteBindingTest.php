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

/**
 * QA bug: "Hapus" in the Master Bank detail modal reported success but the bank
 * stayed in the list, while "Hapus Terpilih" (bulk delete) worked.
 *
 * Cause: the resource route parameter is {master_bank} (show/edit/update all
 * type-hint Bank $master_bank), but destroy() type-hinted Bank $bank. With no
 * matching route parameter, implicit model binding skipped it and the container
 * injected an empty Bank instance, so the in-use guard counted 0 payments and
 * delete() returned early on a non-existing model - a 200 "success" that
 * deleted nothing.
 */
class MasterBankDeleteBindingTest extends TestCase
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

        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('bank_code');
            $table->string('bank_name');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bank_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_id')->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('action')->nullable();
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->text('changed_fields')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('page_name')->nullable();
            $table->string('module_name')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        foreach ([
            'audit_logs',
            'bank_payments',
            'banks',
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

    public function test_destroy_actually_soft_deletes_the_selected_bank(): void
    {
        $user = $this->createUserWithPermission('company.master-banks.view');
        $bankId = $this->createBank('BCA', 'PT. Bank Central Asia');
        $otherBankId = $this->createBank('BNI', 'PT. Bank Negara Indonesia');

        $response = $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->deleteJson(route('company.master-banks.destroy', $bankId));

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');

        $this->assertNotNull(DB::table('banks')->where('id', $bankId)->value('deleted_at'));
        $this->assertNull(DB::table('banks')->where('id', $otherBankId)->value('deleted_at'));
    }

    public function test_destroy_refuses_a_bank_still_used_by_a_bank_payment(): void
    {
        $user = $this->createUserWithPermission('company.master-banks.view');
        $bankId = $this->createBank('BCA', 'PT. Bank Central Asia');

        DB::table('bank_payments')->insert([
            'bank_id' => $bankId,
            'account_name' => 'PT Aroma',
            'account_number' => '1234567890',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->deleteJson(route('company.master-banks.destroy', $bankId));

        $response->assertStatus(422);
        $response->assertJsonPath('status', 'error');

        $this->assertNull(DB::table('banks')->where('id', $bankId)->value('deleted_at'));
    }

    private function createBank(string $code, string $name): int
    {
        return DB::table('banks')->insertGetId([
            'bank_code' => $code,
            'bank_name' => $name,
            'name' => $name,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createUserWithPermission(string $permissionName): User
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Test User',
            'email' => 'test-master-bank@example.com',
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
