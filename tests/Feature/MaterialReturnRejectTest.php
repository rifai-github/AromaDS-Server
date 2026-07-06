<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobScheduleController;
use App\Models\JobSchedule;
use App\Models\MaterialReturn;
use App\Models\Permission;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * rejectMaterialReturn(): newly added action wiring up the pre-existing
 * MaterialReturn::STATUS_REJECTED, which previously had no route/controller
 * method - used to resolve duplicate returns (e.g. a manual return created
 * for a room that already had a pending partial-completion auto-return).
 */
class MaterialReturnRejectTest extends TestCase
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

        Schema::create('material_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->nullable();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->string('status')->nullable();
            $table->string('disposition')->default('keep_branch');
            $table->date('return_date')->nullable();
            $table->text('approval_notes')->nullable();
            $table->unsignedBigInteger('inventory_transfer_id')->nullable();
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
    }

    private function loginAsApprover(): void
    {
        DB::table('users')->insert([
            'id' => 1, 'name' => 'Approver', 'email' => 'approver@example.test', 'password' => 'password',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $user = \App\Models\User::findOrFail(1);
        $permission = Permission::create(['name' => 'operational.job-schedules.approve-material-return']);
        $user->setRelation('permissions', new Collection([$permission]));
        $user->setRelation('roles', new Collection());
        Auth::login($user);
    }

    public function test_pending_return_can_be_rejected(): void
    {
        $this->loginAsApprover();

        DB::table('job_schedules')->insert(['id' => 10, 'job_number' => 'BDG-IR/26-05/0008', 'created_at' => now(), 'updated_at' => now()]);
        $job = JobSchedule::findOrFail(10);

        $materialReturn = MaterialReturn::create([
            'return_number' => 'BDG-RTR/26-07/0099',
            'job_schedule_id' => 10,
            'status' => MaterialReturn::STATUS_PENDING,
            'return_date' => now()->toDateString(),
        ]);

        $response = (new JobScheduleController())->rejectMaterialReturn(
            Request::create('/operational/job-schedules/10/material-returns/'.$materialReturn->id.'/reject', 'POST', [
                'rejection_reason' => 'Duplicate of an existing partial-completion return',
            ]),
            $job,
            $materialReturn->id
        );

        $payload = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('success', $payload['status']);
        $this->assertDatabaseHas('material_returns', [
            'id' => $materialReturn->id,
            'status' => MaterialReturn::STATUS_REJECTED,
            'approval_notes' => 'Duplicate of an existing partial-completion return',
        ]);
    }

    public function test_approved_return_cannot_be_rejected(): void
    {
        $this->loginAsApprover();

        DB::table('job_schedules')->insert(['id' => 11, 'job_number' => 'BDG-IR/26-05/0009', 'created_at' => now(), 'updated_at' => now()]);
        $job = JobSchedule::findOrFail(11);

        $materialReturn = MaterialReturn::create([
            'return_number' => 'BDG-RTR/26-07/0098',
            'job_schedule_id' => 11,
            'status' => MaterialReturn::STATUS_APPROVED,
            'return_date' => now()->toDateString(),
        ]);

        $response = (new JobScheduleController())->rejectMaterialReturn(
            Request::create('/operational/job-schedules/11/material-returns/'.$materialReturn->id.'/reject', 'POST'),
            $job,
            $materialReturn->id
        );

        $payload = json_decode($response->getContent(), true);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('error', $payload['status']);
        $this->assertDatabaseHas('material_returns', [
            'id' => $materialReturn->id,
            'status' => MaterialReturn::STATUS_APPROVED,
        ]);
    }
}
