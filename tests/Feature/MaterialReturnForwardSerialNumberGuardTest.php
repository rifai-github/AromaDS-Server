<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobScheduleController;
use App\Models\JobSchedule;
use App\Models\MaterialReturn;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Safety guard: forwarding a material return to the central warehouse moves stock
 * via InventoryTransfer (WarehouseProduct.quantity only) but does not yet move or
 * update the underlying SerialNumber record (warehouse_id, status, location_type)
 * for unit/serial-tracked products. Until that is implemented, approveMaterialReturn
 * must reject disposition=forward_to_center whenever the return contains a product
 * that requires a serial number - otherwise a unit's SN silently goes out of sync
 * with its real warehouse location (reported by QA as unsafe for SN units).
 */
class MaterialReturnForwardSerialNumberGuardTest extends TestCase
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

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_center')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->boolean('is_unit')->nullable();
            $table->boolean('has_serial_number')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->boolean('is_unit')->nullable();
            $table->boolean('has_serial_number')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->foreignId('product_category_id')->nullable();
            $table->foreignId('product_type_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_product_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->nullable();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->string('status')->nullable();
            $table->string('disposition')->default('keep_branch');
            $table->date('return_date')->nullable();
            $table->unsignedBigInteger('inventory_transfer_id')->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_return_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function loginAsApprover(): User
    {
        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Approver',
            'email' => 'approver@example.test',
            'password' => 'password',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user = User::findOrFail(1);

        $permission = Permission::create(['name' => 'operational.job-schedules.approve-material-return']);
        $user->setRelation('permissions', new Collection([$permission]));
        $user->setRelation('roles', new Collection());
        Auth::login($user);

        return $user;
    }

    private function makeJobAndWarehouses(): array
    {
        DB::table('job_schedules')->insert(['id' => 10, 'job_number' => 'BDG-IR/26-05/0008', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('warehouses')->insert(['id' => 1, 'name' => 'Gudang Cabang', 'is_center' => false, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('warehouses')->insert(['id' => 2, 'name' => 'Gudang Pusat', 'is_center' => true, 'created_at' => now(), 'updated_at' => now()]);

        return [JobSchedule::findOrFail(10)];
    }

    public function test_forward_to_center_rejected_when_return_has_serial_number_product(): void
    {
        $this->loginAsApprover();
        [$job] = $this->makeJobAndWarehouses();

        DB::table('product_categories')->insert(['id' => 1, 'name' => 'Diffuser Unit', 'has_serial_number' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('master_products')->insert(['id' => 1, 'name' => 'Diffuser W300 White', 'product_category_id' => 1, 'created_at' => now(), 'updated_at' => now()]);

        $materialReturn = MaterialReturn::create([
            'return_number' => 'BDG-RTR/26-07/0001',
            'job_schedule_id' => $job->id,
            'warehouse_id' => 1,
            'status' => MaterialReturn::STATUS_PENDING,
            'return_date' => now()->toDateString(),
        ]);
        DB::table('material_return_items')->insert([
            'material_return_id' => $materialReturn->id,
            'product_id' => 1,
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = (new JobScheduleController())->approveMaterialReturn(
            Request::create('/operational/job-schedules/'.$job->id.'/material-returns/'.$materialReturn->id.'/approve', 'POST', [
                'disposition' => 'forward_to_center',
            ]),
            $job,
            $materialReturn->id
        );

        $payload = json_decode($response->getContent(), true);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('error', $payload['status']);
        $this->assertStringContainsString('Serial Number', $payload['message']);

        // Rejected: must remain pending, not silently approved.
        $this->assertDatabaseHas('material_returns', [
            'id' => $materialReturn->id,
            'status' => MaterialReturn::STATUS_PENDING,
        ]);
    }

    public function test_forward_to_center_allowed_when_return_has_no_serial_number_product(): void
    {
        $this->loginAsApprover();
        [$job] = $this->makeJobAndWarehouses();

        DB::table('product_categories')->insert(['id' => 2, 'name' => 'Refill Bulk', 'has_serial_number' => false, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('master_products')->insert(['id' => 2, 'name' => 'PURE Hand Sanitizer (Gel) 1000 mL', 'product_category_id' => 2, 'created_at' => now(), 'updated_at' => now()]);

        $materialReturn = MaterialReturn::create([
            'return_number' => 'BDG-RTR/26-07/0002',
            'job_schedule_id' => $job->id,
            'warehouse_id' => 1,
            'status' => MaterialReturn::STATUS_PENDING,
            'return_date' => now()->toDateString(),
        ]);
        DB::table('material_return_items')->insert([
            'material_return_id' => $materialReturn->id,
            'product_id' => 2,
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = (new JobScheduleController())->approveMaterialReturn(
            Request::create('/operational/job-schedules/'.$job->id.'/material-returns/'.$materialReturn->id.'/approve', 'POST', [
                'disposition' => 'forward_to_center',
            ]),
            $job,
            $materialReturn->id
        );

        $payload = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('success', $payload['status']);
        $this->assertDatabaseHas('material_returns', [
            'id' => $materialReturn->id,
            'status' => MaterialReturn::STATUS_APPROVED,
            'disposition' => 'forward_to_center',
        ]);
    }
}
