<?php

namespace Tests\Feature;

use App\Http\Controllers\Warehouse\InventoryIssuingController;
use App\Models\InventoryIssuing;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InventoryIssuingRemarksFilterTest extends TestCase
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
            $table->foreignId('branch_id')->nullable();
            $table->string('data_restriction')->nullable();
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
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('role_id')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('permission_id')->nullable();
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->nullable();
            $table->foreignId('permission_id')->nullable();
            $table->timestamps();
        });

        Schema::create('user_access_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('access_type')->nullable();
            $table->json('access_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_head_id')->nullable();
            $table->timestamps();
        });

        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->foreignId('manager')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouse_admins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_requests', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('inventory_issuings', function (Blueprint $table) {
            $table->id();
            $table->string('issuing_number')->nullable();
            $table->foreignId('inventory_request_id')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->date('issue_date')->nullable();
            $table->string('reference_no')->nullable();
            $table->foreignId('requested_by')->nullable();
            $table->foreignId('issued_by')->nullable();
            $table->foreignId('received_by')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->string('status')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_inventory_issuing_remarks_filter_uses_contains_search(): void
    {
        $user = User::create([
            'name' => 'Management User',
            'email' => 'management@example.test',
            'password' => 'password',
            'roles' => 'Management Manager',
        ]);

        Auth::login($user);

        InventoryIssuing::create([
            'issuing_number' => 'ISS-BDG',
            'status' => 'sent',
            'remarks' => 'Auto-created from Job Schedule BDG-CSR/26-06/0005',
            'created_by' => $user->id,
        ]);

        InventoryIssuing::create([
            'issuing_number' => 'ISS-JKT',
            'status' => 'sent',
            'remarks' => 'Auto-created from Job Schedule JKT-IF/26-05/0015',
            'created_by' => $user->id,
        ]);

        $request = Request::create('/warehouse/inventory-issuings', 'GET', [
            'filter' => [
                'remarks' => 'BDG-CSR/26-06/',
            ],
        ]);

        $response = app(InventoryIssuingController::class)->index($request);
        $issuings = $response->getData()['issuings'];

        $this->assertSame(1, $issuings->total());
        $this->assertSame('ISS-BDG', $issuings->first()->issuing_number);
    }

    public function test_warehouse_staff_can_see_inventory_issuings_for_own_branch(): void
    {
        $staff = User::create([
            'name' => 'Gudang Staff KLTM',
            'email' => 'warehouse@example.test',
            'password' => 'password',
            'roles' => 'Warehouse Staff',
            'branch_id' => 1,
        ]);

        $otherUser = User::create([
            'name' => 'Operational User',
            'email' => 'operational@example.test',
            'password' => 'password',
            'roles' => 'Operational Staff',
            'branch_id' => 2,
        ]);

        Auth::login($staff);

        \DB::table('branches')->insert([
            ['id' => 1, 'name' => 'DKI Jakarta', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Bandung', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        \DB::table('warehouses')->insert([
            ['id' => 10, 'name' => 'Warehouse Jakarta', 'branch_id' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 20, 'name' => 'Warehouse Bandung', 'branch_id' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        InventoryIssuing::create([
            'issuing_number' => 'JKT-IIS/26-06/0001',
            'branch_id' => 1,
            'warehouse_id' => 10,
            'status' => 'pending',
            'reference_no' => 'JKT-MI/26-06/0001',
            'requested_by' => $otherUser->id,
            'created_by' => $otherUser->id,
        ]);

        InventoryIssuing::create([
            'issuing_number' => 'BDG-IIS/26-06/0001',
            'branch_id' => 2,
            'warehouse_id' => 20,
            'status' => 'pending',
            'reference_no' => 'BDG-MI/26-06/0001',
            'requested_by' => $otherUser->id,
            'created_by' => $otherUser->id,
        ]);

        $request = Request::create('/warehouse/inventory-issuings', 'GET');

        $response = app(InventoryIssuingController::class)->index($request);
        $issuings = $response->getData()['issuings'];

        $this->assertSame(1, $issuings->total());
        $this->assertSame('JKT-IIS/26-06/0001', $issuings->first()->issuing_number);
    }

    public function test_pending_inventory_issuing_list_does_not_show_issued_by(): void
    {
        $view = file_get_contents(resource_path('views/warehouse/inventory-issuings/index.blade.php'));

        $this->assertStringContainsString("\$issuing->status !== 'pending' ?", $view);
        $this->assertStringContainsString("\$issuing->issuedBy?->name", $view);
    }
}
