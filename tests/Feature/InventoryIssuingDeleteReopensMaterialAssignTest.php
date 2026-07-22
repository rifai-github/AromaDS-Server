<?php

namespace Tests\Feature;

use App\Http\Controllers\Warehouse\InventoryIssuingController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InventoryIssuingDeleteReopensMaterialAssignTest extends TestCase
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
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('job_advice_id')->nullable();
            $table->foreignId('building_id')->nullable();
            $table->integer('period')->nullable();
            $table->boolean('material_checked')->default(false);
            $table->timestamp('material_checked_at')->nullable();
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
            $table->foreignId('updated_by')->nullable();
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

        Schema::create('material_issue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_issue_id')->nullable();
            $table->foreignId('job_assign_schedule_id')->nullable();
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
        Schema::dropIfExists('warehouse_products');
        Schema::dropIfExists('master_products');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_receivings');
        Schema::dropIfExists('inventory_issuing_items');
        Schema::dropIfExists('inventory_issuings');
        Schema::dropIfExists('material_issue_items');
        Schema::dropIfExists('job_assign_material_issues');
        Schema::dropIfExists('material_issues');
        Schema::dropIfExists('job_assign_schedules');
        Schema::dropIfExists('job_schedules');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_deleting_pending_inventory_issuing_reopens_material_assign_by_reference(): void
    {
        $this->seedIssuedMaterialAssignScenario('BDG-MA/26-05/0001');

        $this->deleteIssuing();

        $this->assertMaterialAssignWasReopened();
    }

    public function test_deleting_pending_inventory_issuing_reopens_material_assign_when_reference_has_drifted(): void
    {
        $this->seedIssuedMaterialAssignScenario('WRONG-REFERENCE');

        $this->deleteIssuing();

        $this->assertMaterialAssignWasReopened();
    }

    private function seedIssuedMaterialAssignScenario(string $issuingReference): void
    {
        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Administrator',
            'email' => 'admin@example.test',
            'password' => 'password',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Auth::login(User::findOrFail(1));

        DB::table('job_schedules')->insert([
            'id' => 10,
            'job_number' => 'BDG-CSR/26-05/0001',
            'type' => 'Service',
            'status' => 'barang_dipersiapkan',
            'material_checked' => true,
            'material_checked_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_assign_schedules')->insert([
            'id' => 20,
            'job_schedule_id' => 10,
            'team_id' => null,
            'status' => 'assigned',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('material_issues')->insert([
            'id' => 30,
            'issue_number' => 'BDG-MA/26-05/0001',
            'warehouse_id' => 5,
            'status' => 'issued',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('master_products')->insert([
            'id' => 100,
            'name' => 'Cleaner 100ml',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_assign_material_issues')->insert([
            'id' => 40,
            'job_assign_schedule_id' => 20,
            'material_issue_id' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventory_issuings')->insert([
            'id' => 50,
            'issuing_number' => 'BDG-IIS/26-05/0001',
            'warehouse_id' => 5,
            'issue_date' => now()->toDateString(),
            'reference_no' => $issuingReference,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventory_issuing_items')->insert([
            'id' => 60,
            'inventory_issuing_id' => 50,
            'job_assign_schedule_id' => 20,
            'product_id' => 100,
            'quantity_requested' => 1,
            'quantity_issued' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function deleteIssuing(): void
    {
        $response = app(InventoryIssuingController::class)->destroy(50);

        $this->assertTrue($response->isRedirect());
    }

    private function assertMaterialAssignWasReopened(): void
    {
        $this->assertDatabaseHas('material_issues', [
            'id' => 30,
            'status' => 'approved',
            'updated_by' => 1,
        ]);

        $this->assertDatabaseHas('job_schedules', [
            'id' => 10,
            'status' => 'assign_material',
            'material_checked' => false,
        ]);

        $this->assertDatabaseHas('job_assign_material_issues', [
            'id' => 40,
            'job_assign_schedule_id' => 20,
            'material_issue_id' => 30,
            'deleted_at' => null,
        ]);

        $this->assertSoftDeleted('inventory_issuings', ['id' => 50]);
        $this->assertDatabaseMissing('inventory_issuing_items', ['id' => 60]);
    }
}
