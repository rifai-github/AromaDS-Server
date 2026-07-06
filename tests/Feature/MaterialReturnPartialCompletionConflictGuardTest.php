<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobScheduleController;
use App\Models\JobSchedule;
use App\Models\JobScheduleRoom;
use App\Models\MaterialReturn;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Safety guard: JobWebCompletionService::processPartialCompletionMaterialReturnItems()
 * auto-creates a MaterialReturn + InventoryReceiving for a room when a technician
 * leaves a job mid-way (partial completion), but never sets
 * jobScheduleRoom->material_return_id. That means the manual "Create Return" button
 * in the Job Schedule detail page doesn't detect the conflict and can create a
 * SECOND, independent MaterialReturn for the same room - QA found this causes stock
 * to be credited twice once both the manual return AND the auto-return's pending
 * Inventory Receiving get completed/finalized. createMaterialReturn() must now
 * detect and reject this specific conflict.
 */
class MaterialReturnPartialCompletionConflictGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('job_advice_id')->nullable();
            $table->foreignId('building_id')->nullable();
            $table->string('type')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedule_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id');
            $table->string('room_name')->nullable();
            $table->string('status')->nullable();
            $table->string('material_return_status')->nullable();
            $table->unsignedBigInteger('material_return_id')->nullable();
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
            $table->text('notes')->nullable();
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
            $table->string('room_name')->nullable();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // Minimal schema so createMaterialReturn() can reach its "no items" check
        // (past the partial-completion guard) instead of hitting an unrelated
        // missing-table error.
        Schema::create('job_assign_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_issues', function (Blueprint $table) {
            $table->id();
            $table->string('status')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_assign_material_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_issue_id')->nullable();
            $table->foreignId('job_assign_schedule_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_issue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_issue_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('room_name')->nullable();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_manual_return_blocked_when_partial_completion_return_pending_for_room(): void
    {
        Auth::shouldReceive('id')->andReturn(1);

        DB::table('job_schedules')->insert(['id' => 10, 'job_number' => 'BDG-IR/26-05/0008', 'status' => 'in_progress', 'created_at' => now(), 'updated_at' => now()]);
        $job = JobSchedule::findOrFail(10);

        $room = JobScheduleRoom::create([
            'job_schedule_id' => 10,
            'room_name' => 'Ruang Meeting VIP',
            'status' => 'in_progress',
        ]);

        $autoReturn = MaterialReturn::create([
            'return_number' => 'BDG-RTR/26-05/AUTO',
            'job_schedule_id' => 10,
            'status' => MaterialReturn::STATUS_PENDING,
            'return_date' => now()->toDateString(),
            'notes' => 'Auto-return dari Job BDG-IR/26-05/0008 (Pekerjaan tidak selesai). Room: Ruang Meeting VIP',
        ]);
        DB::table('material_return_items')->insert([
            'material_return_id' => $autoReturn->id,
            'product_id' => 1,
            'room_name' => 'Ruang Meeting VIP',
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = (new JobScheduleController())->createMaterialReturn(
            Request::create('/operational/job-schedules/10/rooms/'.$room->id.'/material-return', 'POST', [
                'return_reason' => 'Test manual return',
            ]),
            $job,
            $room->id
        );

        $payload = json_decode($response->getContent(), true);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('error', $payload['status']);
        $this->assertStringContainsString('partial completion', $payload['message']);
        $this->assertStringContainsString('BDG-RTR/26-05/AUTO', $payload['message']);

        // No second MaterialReturn should have been created for this room.
        $this->assertSame(1, MaterialReturn::where('job_schedule_id', 10)->count());
    }

    public function test_manual_return_not_blocked_when_no_partial_completion_conflict(): void
    {
        Auth::shouldReceive('id')->andReturn(1);

        DB::table('job_schedules')->insert(['id' => 11, 'job_number' => 'BDG-IR/26-05/0009', 'status' => 'in_progress', 'created_at' => now(), 'updated_at' => now()]);
        $job = JobSchedule::findOrFail(11);

        $room = JobScheduleRoom::create([
            'job_schedule_id' => 11,
            'room_name' => 'Toilet VIP',
            'status' => 'in_progress',
        ]);

        $response = (new JobScheduleController())->createMaterialReturn(
            Request::create('/operational/job-schedules/11/rooms/'.$room->id.'/material-return', 'POST', [
                'return_reason' => 'Test manual return',
            ]),
            $job,
            $room->id
        );

        $payload = json_decode($response->getContent(), true);

        // Guard doesn't fire; execution proceeds past it to the next, unrelated
        // validation step ("no material issue item"), confirming the
        // partial-completion conflict guard itself did not block this case.
        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('error', $payload['status']);
        $this->assertStringNotContainsString('partial completion', $payload['message']);
        $this->assertStringContainsString('Tidak ada material issue item', $payload['message']);
    }
}
