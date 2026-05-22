<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobAssignMaterialIssueController;
use App\Models\JobAssignMaterialIssue;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class SubmitIssueGroupedSelectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('master_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->nullable();
            $table->foreignId('job_advice_id')->nullable();
            $table->foreignId('building_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->string('room_name')->nullable();
            $table->string('type')->nullable();
            $table->integer('period')->nullable();
            $table->string('status')->nullable();
            $table->boolean('material_checked')->default(false);
            $table->timestamp('material_checked_at')->nullable();
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

        Schema::create('material_issues', function (Blueprint $table) {
            $table->id();
            $table->string('issue_number')->nullable();
            $table->string('status')->nullable();
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
    }

    protected function tearDown(): void
    {
        foreach ([
            'job_assign_material_issues',
            'material_issues',
            'job_assign_schedules',
            'job_schedule_rooms',
            'job_schedules',
            'master_rooms',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_material_checked_check_room_without_material_does_not_block_submit_issue(): void
    {
        $now = now();

        DB::table('master_rooms')->insert([
            ['id' => 10, 'room_name' => 'Lobby', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 11, 'room_name' => 'VIP Room', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('job_schedules')->insert([
            [
                'id' => 1,
                'job_number' => 'JKT-CSR/26-05/0003',
                'job_advice_id' => 99,
                'building_id' => 7,
                'room_id' => 10,
                'room_name' => 'Lobby',
                'type' => 'service_first',
                'period' => 1,
                'status' => 'assign_material',
                'material_checked' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'job_number' => 'JKT-CSR/26-05/0003',
                'job_advice_id' => 99,
                'building_id' => 7,
                'room_id' => 11,
                'room_name' => 'VIP Room',
                'type' => 'service_first',
                'period' => 1,
                'status' => 'assign_material',
                'material_checked' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('job_assign_schedules')->insert([
            'id' => 20,
            'job_schedule_id' => 2,
            'status' => 'assigned',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('material_issues')->insert([
            'id' => 30,
            'issue_number' => 'JKT-MI/26-05/0001',
            'status' => 'pending',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('job_assign_material_issues')->insert([
            'id' => 40,
            'job_assign_schedule_id' => 20,
            'material_issue_id' => 30,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $errors = $this->validateSelectedIssues([40]);

        $this->assertSame([], $errors);
    }

    public function test_unchecked_sibling_without_material_still_blocks_submit_issue(): void
    {
        $now = now();

        DB::table('master_rooms')->insert([
            ['id' => 10, 'room_name' => 'Lobby', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 11, 'room_name' => 'VIP Room', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('job_schedules')->insert([
            [
                'id' => 1,
                'job_number' => 'JKT-CSR/26-05/0004',
                'job_advice_id' => 99,
                'building_id' => 7,
                'room_id' => 10,
                'room_name' => 'Lobby',
                'type' => 'service_first',
                'period' => 1,
                'status' => 'assign_material',
                'material_checked' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'job_number' => 'JKT-CSR/26-05/0004',
                'job_advice_id' => 99,
                'building_id' => 7,
                'room_id' => 11,
                'room_name' => 'VIP Room',
                'type' => 'service_first',
                'period' => 1,
                'status' => 'assign_material',
                'material_checked' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('job_assign_schedules')->insert([
            'id' => 20,
            'job_schedule_id' => 2,
            'status' => 'assigned',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('material_issues')->insert([
            'id' => 30,
            'issue_number' => 'JKT-MI/26-05/0002',
            'status' => 'pending',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('job_assign_material_issues')->insert([
            'id' => 40,
            'job_assign_schedule_id' => 20,
            'material_issue_id' => 30,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $errors = $this->validateSelectedIssues([40]);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('Lobby', $errors[0]);
    }

    private function validateSelectedIssues(array $ids): array
    {
        $selected = JobAssignMaterialIssue::with([
            'jobAssignSchedule.jobSchedule.room',
            'jobAssignSchedule.jobSchedule.jobScheduleRooms',
        ])->whereIn('id', $ids)->get();

        $method = new ReflectionMethod(JobAssignMaterialIssueController::class, 'validateGroupedSubmitIssueSelection');
        $method->setAccessible(true);

        return $method->invoke(app(JobAssignMaterialIssueController::class), $selected);
    }
}
