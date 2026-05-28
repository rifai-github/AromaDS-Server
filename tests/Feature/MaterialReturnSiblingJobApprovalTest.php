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

class MaterialReturnSiblingJobApprovalTest extends TestCase
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
            $table->string('type')->nullable();
            $table->foreignId('job_advice_id')->nullable();
            $table->foreignId('building_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->nullable();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->string('status')->nullable();
            $table->date('return_date')->nullable();
            $table->string('return_reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->foreignId('returned_by')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_return_id')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('convert', 10, 2)->nullable();
            $table->decimal('bom_quantity', 10, 2)->nullable();
            $table->string('return_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_material_return_can_be_approved_from_sibling_job_schedule_detail(): void
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

        DB::table('job_schedules')->insert([
            'id' => 10,
            'job_number' => 'BDG-IR/26-05/0008',
            'job_advice_id' => 77,
            'building_id' => 174,
            'type' => 'installation',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('job_schedules')->insert([
            'id' => 11,
            'job_number' => 'BDG-IR/26-05/0009',
            'job_advice_id' => 77,
            'building_id' => 174,
            'type' => 'installation',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $currentJob = JobSchedule::findOrFail(10);
        $actualReturnJob = JobSchedule::findOrFail(11);

        DB::table('warehouses')->insert(['id' => 1, 'name' => 'Warehouse Bandung', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('teams')->insert(['id' => 1, 'name' => 'Team Bandung', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('master_products')->insert(['id' => 1, 'name' => 'Diffuser Premium XL Unit', 'created_at' => now(), 'updated_at' => now()]);

        $materialReturn = MaterialReturn::create([
            'return_number' => 'BDG-RTR/26-05/0008',
            'job_schedule_id' => $actualReturnJob->id,
            'warehouse_id' => 1,
            'team_id' => 1,
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
            Request::create('/operational/job-schedules/'.$currentJob->id.'/material-returns/'.$materialReturn->id.'/approve', 'POST', [
                'approval_notes' => 'Approved from sibling job detail',
            ]),
            $currentJob,
            $materialReturn->id
        );

        $payload = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('success', $payload['status']);
        $this->assertDatabaseHas('material_returns', [
            'id' => $materialReturn->id,
            'status' => MaterialReturn::STATUS_APPROVED,
            'approved_by' => $user->id,
            'approval_notes' => 'Approved from sibling job detail',
        ]);
    }
}
