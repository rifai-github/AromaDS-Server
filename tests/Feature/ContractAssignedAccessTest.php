<?php

namespace Tests\Feature;

use App\Http\Controllers\Marketing\ContractAssignedController;
use App\Models\ContractAssigned;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContractAssignedAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->text('roles')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('roles', function (Blueprint $table) {
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

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_access_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('access_type')->nullable();
            $table->json('access_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('marketing_id')->nullable();
            $table->string('contract_status')->nullable();
            $table->string('status')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contract_assigned', function (Blueprint $table) {
            $table->id();
            $table->string('switching_number')->nullable();
            $table->foreignId('old_contract_id')->nullable();
            $table->foreignId('old_marketing_id')->nullable();
            $table->foreignId('new_marketing_id')->nullable();
            $table->string('switching_reason')->nullable();
            $table->string('status')->nullable();
            $table->text('approval_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('initiated_by')->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->foreignId('rejected_by')->nullable();
            $table->foreignId('executed_by')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('contract_assigned');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('user_access_levels');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_contract_assigned_list_is_filtered_to_hierarchy_and_peer_users(): void
    {
        DB::table('users')->insert([
            [
                'id' => 87,
                'name' => 'Yadi',
                'email' => 'yadi@example.test',
                'roles' => 'Marketing Manager',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 80,
                'name' => 'Marketing HO',
                'email' => 'mho@example.test',
                'roles' => 'Marketing Staff',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 88,
                'name' => 'Yenny',
                'email' => 'yenny@example.test',
                'roles' => 'Marketing Manager',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 28,
                'name' => 'Administrator',
                'email' => 'admin@example.test',
                'roles' => 'Administrator',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('user_access_levels')->insert([
            [
                'user_id' => 87,
                'access_type' => 'hierarchical',
                'access_config' => json_encode(['subordinates' => [80]]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 87,
                'access_type' => 'peer',
                'access_config' => json_encode(['peer_users' => [88]]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('contracts')->insert([
            [
                'id' => 1,
                'contract_number' => 'MHO-CA/26-05/0001',
                'marketing_id' => 80,
                'contract_status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'contract_number' => 'ADM-CA/26-05/0001',
                'marketing_id' => 28,
                'contract_status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('contract_assigned')->insert([
            [
                'id' => 10,
                'switching_number' => 'CAS-MHO',
                'old_contract_id' => 1,
                'old_marketing_id' => 80,
                'new_marketing_id' => 88,
                'status' => 'draft',
                'created_by' => 80,
                'initiated_by' => 80,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 11,
                'switching_number' => 'CAS-ADMIN',
                'old_contract_id' => 2,
                'old_marketing_id' => 28,
                'new_marketing_id' => 28,
                'status' => 'draft',
                'created_by' => 28,
                'initiated_by' => 28,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Auth::login(User::findOrFail(87));

        $controller = app(ContractAssignedController::class);
        $method = new \ReflectionMethod($controller, 'applyContractAssignedAccessFilter');
        $method->setAccessible(true);

        $filteredQuery = $method->invoke($controller, ContractAssigned::query());

        $this->assertSame([10], $filteredQuery->pluck('id')->all());
    }

    public function test_draft_contract_assigned_can_be_directly_executed_with_explicit_permission(): void
    {
        DB::table('users')->insert([
            [
                'id' => 1,
                'name' => 'Old Marketing',
                'email' => 'old@example.test',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'New Marketing',
                'email' => 'new@example.test',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'Administrator',
                'email' => 'admin@example.test',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('contracts')->insert([
            'id' => 1,
            'contract_number' => 'JKT-CA/26-05/0001',
            'marketing_id' => 1,
            'contract_status' => 'active',
            'notes' => 'Existing note',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('contract_assigned')->insert([
            'id' => 1,
            'switching_number' => 'JKT-CAS/26-05/0001',
            'old_contract_id' => 1,
            'old_marketing_id' => 1,
            'new_marketing_id' => 2,
            'status' => ContractAssigned::STATUS_DRAFT,
            'initiated_by' => 3,
            'created_by' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $assigned = ContractAssigned::findOrFail(1);
        $contract = $assigned->execute(3, true, 'Approved while executing');

        $assigned->refresh();

        $this->assertSame(ContractAssigned::STATUS_COMPLETED, $assigned->status);
        $this->assertSame(3, $assigned->approved_by);
        $this->assertSame(3, $assigned->executed_by);
        $this->assertNotNull($assigned->approved_at);
        $this->assertNotNull($assigned->executed_at);
        $this->assertNotNull($assigned->completed_at);
        $this->assertSame(2, $contract->fresh()->marketing_id);
        $this->assertStringContainsString('Marketing transferred from Old Marketing to New Marketing', $contract->fresh()->notes);
    }

    public function test_draft_contract_assigned_still_requires_explicit_direct_execute_permission(): void
    {
        DB::table('contract_assigned')->insert([
            'id' => 1,
            'switching_number' => 'JKT-CAS/26-05/0001',
            'old_contract_id' => 1,
            'old_marketing_id' => 1,
            'new_marketing_id' => 2,
            'status' => ContractAssigned::STATUS_DRAFT,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Switching must be approved before execution');

        ContractAssigned::findOrFail(1)->execute(3);
    }
}
