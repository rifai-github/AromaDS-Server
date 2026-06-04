<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobScheduleController;
use App\Models\JobSchedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class JobScheduleGroupingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->nullable();
            $table->foreignId('job_advice_id')->nullable();
            $table->foreignId('building_id')->nullable();
            $table->string('type')->nullable();
            $table->integer('period')->nullable();
            $table->string('status')->nullable();
            $table->boolean('material_checked')->default(false);
            $table->timestamp('material_checked_at')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedule_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->foreignId('job_advice_room_id')->nullable();
            $table->string('room_name')->nullable();
            $table->string('status')->nullable();
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

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('team_name')->nullable();
            $table->timestamps();
        });

        Schema::create('job_assign_material_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_assign_schedule_id')->nullable();
            $table->foreignId('material_issue_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_issues', function (Blueprint $table) {
            $table->id();
            $table->string('issue_number')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_issue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_issue_id')->nullable();
            $table->foreignId('job_assign_schedule_id')->nullable();
            $table->string('room_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_issuings', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        foreach ([
            'inventory_issuings',
            'material_issue_items',
            'material_issues',
            'job_assign_material_issues',
            'teams',
            'job_assign_schedules',
            'job_schedule_rooms',
            'job_schedules',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_grouped_rooms_are_scoped_by_job_number_after_material_assign(): void
    {
        DB::table('job_schedules')->insert([
            [
                'id' => 1,
                'job_number' => 'BDG-CSR/26-05/0005',
                'job_advice_id' => 99,
                'building_id' => 5,
                'type' => 'service',
                'period' => 1,
                'status' => 'barang_dipersiapkan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'job_number' => 'BDG-CSR/26-05/0006',
                'job_advice_id' => 99,
                'building_id' => 5,
                'type' => 'service',
                'period' => 1,
                'status' => 'assign_material',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('job_schedule_rooms')->insert([
            [
                'id' => 10,
                'job_schedule_id' => 1,
                'room_name' => 'Ruang VIP',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 20,
                'job_schedule_id' => 2,
                'room_name' => 'Ruang VIP',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 21,
                'job_schedule_id' => 2,
                'room_name' => 'Cafe',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $jobs = JobSchedule::with('jobScheduleRooms')
            ->whereIn('id', [1, 2])
            ->orderBy('id')
            ->get();

        $paginator = new LengthAwarePaginator($jobs, $jobs->count(), 15);

        $method = new ReflectionMethod(JobScheduleController::class, 'attachGroupedRoomsToJobs');
        $method->setAccessible(true);
        $method->invoke(new JobScheduleController(), $paginator);

        $this->assertSame([10], $jobs[0]->allGroupedRooms->pluck('id')->all());
        $this->assertSame([20, 21], $jobs[1]->allGroupedRooms->pluck('id')->all());
    }

    public function test_detail_rooms_are_scoped_to_the_current_unassigned_period(): void
    {
        DB::table('job_schedules')->insert([
            [
                'id' => 1,
                'job_number' => null,
                'job_advice_id' => 99,
                'building_id' => 5,
                'type' => 'service',
                'period' => 2,
                'status' => 'scheduled',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'job_number' => null,
                'job_advice_id' => 99,
                'building_id' => 5,
                'type' => 'service',
                'period' => 3,
                'status' => 'scheduled',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('job_schedule_rooms')->insert([
            [
                'id' => 10,
                'job_schedule_id' => 1,
                'room_name' => 'Ruang Delima',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 20,
                'job_schedule_id' => 2,
                'room_name' => 'Ruang Delima',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $method = new ReflectionMethod(JobScheduleController::class, 'relatedJobScheduleRoomsQuery');
        $method->setAccessible(true);
        $query = $method->invoke(new JobScheduleController(), [1]);

        $this->assertSame([10], $query->pluck('id')->all());
    }

    public function test_pending_material_issue_still_displays_as_material_assign_until_inventory_issuing_exists(): void
    {
        DB::table('job_schedules')->insert([
            'id' => 1,
            'job_number' => 'BDG-CSR/26-05/0006',
            'job_advice_id' => 99,
            'building_id' => 5,
            'type' => 'service',
            'period' => 1,
            'status' => 'barang_dipersiapkan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_schedule_rooms')->insert([
            'id' => 10,
            'job_schedule_id' => 1,
            'room_name' => 'Ruang VIP',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_assign_schedules')->insert([
            'id' => 20,
            'job_schedule_id' => 1,
            'status' => 'assigned',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('material_issues')->insert([
            'id' => 30,
            'issue_number' => 'BDG-MI/26-05/0001',
            'status' => 'pending',
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

        DB::table('material_issue_items')->insert([
            'id' => 50,
            'material_issue_id' => 30,
            'job_assign_schedule_id' => 20,
            'room_name' => 'Ruang VIP',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $room = \App\Models\JobScheduleRoom::with([
            'jobSchedule.jobAssignSchedules.jobAssignMaterialIssues.materialIssue.items',
            'jobSchedule.jobScheduleRooms',
        ])->findOrFail(10);

        $this->assertSame('Material Assign', $room->status_text);
        $this->assertSame('status-assign-material', $room->status_badge_class);

        DB::table('inventory_issuings')->insert([
            'id' => 60,
            'reference_no' => 'BDG-MI/26-05/0001',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $room->unsetRelation('jobSchedule');
        $room->load([
            'jobSchedule.jobAssignSchedules.jobAssignMaterialIssues.materialIssue.items',
            'jobSchedule.jobScheduleRooms',
        ]);

        $this->assertSame('Material in Prep', $room->status_text);
        $this->assertSame('status-barang-dipersiapkan', $room->status_badge_class);
    }

    public function test_repair_keeps_pending_material_issue_job_at_material_assign_until_submit_issue(): void
    {
        DB::table('job_schedules')->insert([
            'id' => 1,
            'job_number' => 'BDG-CSR/26-05/0005',
            'job_advice_id' => 99,
            'building_id' => 5,
            'type' => 'service',
            'period' => 1,
            'status' => 'barang_dipersiapkan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_assign_schedules')->insert([
            'id' => 20,
            'job_schedule_id' => 1,
            'status' => 'assigned',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('material_issues')->insert([
            'id' => 30,
            'issue_number' => 'BDG-MI/26-05/0005',
            'status' => 'pending',
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

        DB::table('material_issue_items')->insert([
            'id' => 50,
            'material_issue_id' => 30,
            'job_assign_schedule_id' => 20,
            'room_name' => 'Ruang VIP',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('jobs:repair-grouped-material-statuses', [
            '--job-number' => ['BDG-CSR/26-05/0005'],
            '--apply' => true,
        ])->assertExitCode(0);

        $this->assertSame('assign_material', DB::table('job_schedules')->where('id', 1)->value('status'));
    }
}
