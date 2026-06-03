<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobScheduleController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BulkUnassignMaterialStrictSelectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->nullable();
            $table->foreignId('job_advice_id')->nullable();
            $table->foreignId('building_id')->nullable();
            $table->string('type')->nullable();
            $table->integer('period')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_assign_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_issues', function (Blueprint $table) {
            $table->id();
            $table->string('issue_number')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_issue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_issue_id')->nullable();
            $table->foreignId('job_assign_schedule_id')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->string('room_name')->nullable();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_assign_material_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_assign_schedule_id')->nullable();
            $table->foreignId('material_issue_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_issuings', function (Blueprint $table) {
            $table->id();
            $table->string('issuing_number')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->date('issue_date')->nullable();
            $table->string('reference_no')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_issuing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_issuing_id')->nullable();
            $table->foreignId('job_assign_schedule_id')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->string('room_name')->nullable();
            $table->decimal('quantity_requested', 10, 2)->default(0);
            $table->decimal('quantity_issued', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('inventory_receivings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issuing_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->string('movement_type')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->integer('quantity')->default(0);
            $table->string('reference_no')->nullable();
            $table->string('reference_type')->nullable();
            $table->timestamps();
        });

        Schema::create('warehouse_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->integer('quantity')->default(0);
            $table->integer('minimum_stock')->default(0);
            $table->integer('maximum_stock')->default(1000);
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Administrator',
            'email' => 'admin@example.test',
            'password' => 'password',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Auth::login(User::findOrFail(1));
    }

    protected function tearDown(): void
    {
        foreach ([
            'master_products',
            'warehouse_products',
            'inventory_movements',
            'inventory_receivings',
            'inventory_issuing_items',
            'inventory_issuings',
            'job_assign_material_issues',
            'material_issue_items',
            'material_issues',
            'job_assign_schedules',
            'job_schedules',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_bulk_unassign_material_only_handles_checked_job_and_deletes_prepare_issuing(): void
    {
        $this->seedSiblingMaterialPrepareJobs();

        $request = Request::create('/operational/job-schedules/bulk-unassign-material', 'POST', [
            'ids' => [1],
        ]);

        $response = app(JobScheduleController::class)->bulkUnassignMaterial($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('success', $response->getData(true)['status']);

        $this->assertDatabaseHas('job_schedules', [
            'id' => 1,
            'job_number' => null,
            'status' => 'new_job',
        ]);
        $this->assertSoftDeleted('material_issues', ['id' => 10]);
        $this->assertSoftDeleted('job_assign_material_issues', ['id' => 30]);
        $this->assertSoftDeleted('material_issue_items', ['id' => 40]);
        $this->assertSoftDeleted('inventory_issuings', ['id' => 50]);
        $this->assertDatabaseMissing('inventory_issuing_items', ['id' => 60]);
        $this->assertDatabaseMissing('inventory_movements', ['id' => 70]);
        $this->assertDatabaseHas('warehouse_products', [
            'warehouse_id' => 5,
            'master_product_id' => 100,
            'quantity' => 10,
        ]);

        $this->assertDatabaseHas('job_schedules', [
            'id' => 2,
            'job_number' => 'JKT-CSR/26-06/0002',
            'status' => 'barang_dipersiapkan',
        ]);
        $this->assertDatabaseHas('material_issues', [
            'id' => 11,
            'issue_number' => 'JKT-MI/26-06/0002',
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('inventory_issuings', [
            'id' => 51,
            'reference_no' => 'JKT-MI/26-06/0002',
            'deleted_at' => null,
        ]);
    }

    private function seedSiblingMaterialPrepareJobs(): void
    {
        DB::table('master_products')->insert([
            'id' => 100,
            'name' => 'Cleaner',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_schedules')->insert([
            [
                'id' => 1,
                'job_number' => 'JKT-CSR/26-06/0001',
                'job_advice_id' => 1000,
                'building_id' => 2000,
                'type' => 'service',
                'period' => 1,
                'status' => 'barang_dipersiapkan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'job_number' => 'JKT-CSR/26-06/0002',
                'job_advice_id' => 1000,
                'building_id' => 2000,
                'type' => 'service',
                'period' => 1,
                'status' => 'barang_dipersiapkan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('job_assign_schedules')->insert([
            [
                'id' => 20,
                'job_schedule_id' => 1,
                'team_id' => 3,
                'status' => 'assigned',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 21,
                'job_schedule_id' => 2,
                'team_id' => 3,
                'status' => 'assigned',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('material_issues')->insert([
            [
                'id' => 10,
                'issue_number' => 'JKT-MI/26-06/0001',
                'warehouse_id' => 5,
                'status' => 'issued',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 11,
                'issue_number' => 'JKT-MI/26-06/0002',
                'warehouse_id' => 5,
                'status' => 'issued',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('job_assign_material_issues')->insert([
            [
                'id' => 30,
                'job_assign_schedule_id' => 20,
                'material_issue_id' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 31,
                'job_assign_schedule_id' => 21,
                'material_issue_id' => 11,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('material_issue_items')->insert([
            [
                'id' => 40,
                'material_issue_id' => 10,
                'job_assign_schedule_id' => 20,
                'product_id' => 100,
                'room_name' => 'Lobby 1',
                'quantity' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 41,
                'material_issue_id' => 11,
                'job_assign_schedule_id' => 21,
                'product_id' => 100,
                'room_name' => 'Lobby 2',
                'quantity' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('inventory_issuings')->insert([
            [
                'id' => 50,
                'issuing_number' => 'JKT-IIS/26-06/0001',
                'warehouse_id' => 5,
                'issue_date' => now()->toDateString(),
                'reference_no' => 'JKT-MI/26-06/0001',
                'status' => 'processed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 51,
                'issuing_number' => 'JKT-IIS/26-06/0002',
                'warehouse_id' => 5,
                'issue_date' => now()->toDateString(),
                'reference_no' => 'JKT-MI/26-06/0002',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('inventory_issuing_items')->insert([
            [
                'id' => 60,
                'inventory_issuing_id' => 50,
                'job_assign_schedule_id' => 20,
                'product_id' => 100,
                'room_name' => 'Lobby 1',
                'quantity_requested' => 1,
                'quantity_issued' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 61,
                'inventory_issuing_id' => 51,
                'job_assign_schedule_id' => 21,
                'product_id' => 100,
                'room_name' => 'Lobby 2',
                'quantity_requested' => 1,
                'quantity_issued' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('warehouse_products')->insert([
            'id' => 80,
            'warehouse_id' => 5,
            'master_product_id' => 100,
            'quantity' => 9,
            'minimum_stock' => 0,
            'maximum_stock' => 1000,
            'created_by' => 1,
            'updated_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventory_movements')->insert([
            'id' => 70,
            'movement_type' => 'out',
            'warehouse_id' => 5,
            'master_product_id' => 100,
            'quantity' => -1,
            'reference_no' => 'JKT-IIS/26-06/0001',
            'reference_type' => 'inventory_issuing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
