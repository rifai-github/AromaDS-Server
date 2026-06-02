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
}
