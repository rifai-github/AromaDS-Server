<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobScheduleController;
use App\Models\JobAssignSchedule;
use App\Models\JobSchedule;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA 5 Okt (JKT-CSR/26-10/0003 & JKT-IR/26-10/0002): ruangan lobby bernomor JS tapi tampil
 * NEW JOB. Material Assign memberi nomor + status Material Assign sebelum material dibuat;
 * rental lobby belum punya produk di Master Rental, jadi material tak terbentuk dan job
 * tertinggal bernomor tanpa material. Sekarang job seperti itu dikembalikan ke keadaan semula.
 */
class MaterialAssignWithoutMaterialRevertTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->nullable();
            $table->string('status')->nullable();
            $table->string('type')->nullable();
            $table->date('assign_date')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('job_assign_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id');
            $table->foreignId('team_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('job_assign_material_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_assign_schedule_id');
            $table->foreignId('material_issue_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('action')->nullable();
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->text('changed_fields')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('page_name')->nullable();
            $table->string('module_name')->nullable();
            $table->timestamps();
        });

        DB::table('users')->insert(['id' => 1, 'name' => 'Admin']);
        $this->actingAs(User::find(1));
    }

    protected function tearDown(): void
    {
        foreach (['audit_logs', 'job_assign_material_issues', 'job_assign_schedules', 'job_schedules', 'users'] as $table) {
            Schema::dropIfExists($table);
        }
        parent::tearDown();
    }

    private function revert(JobSchedule $job, JobAssignSchedule $jas, array $before, bool $created): bool
    {
        $method = new \ReflectionMethod(JobScheduleController::class, 'revertMaterialAssignWithoutMaterial');
        $method->setAccessible(true);

        return $method->invoke(app(JobScheduleController::class), $job, $jas, $before, $created);
    }

    public function test_job_without_any_material_is_restored_and_its_new_assign_schedule_removed(): void
    {
        DB::table('job_schedules')->insert(['id' => 170, 'job_number' => null, 'status' => 'new_job', 'type' => 'service_first']);
        $job = JobSchedule::find(170);
        $before = $job->only(['job_number', 'status', 'assign_date']);
        $job->update(['job_number' => 'JKT-CSR/26-10/0003', 'status' => 'assign_material', 'assign_date' => '2026-10-05']);
        $jas = JobAssignSchedule::create(['job_schedule_id' => 170, 'status' => 'assigned']);

        $this->assertFalse($this->revert($job, $jas, $before, true));

        $row = DB::table('job_schedules')->find(170);
        $this->assertNull($row->job_number);
        $this->assertSame('new_job', $row->status);
        $this->assertSame(0, JobAssignSchedule::where('job_schedule_id', 170)->count());
    }

    public function test_job_with_material_is_kept(): void
    {
        DB::table('job_schedules')->insert(['id' => 168, 'job_number' => 'JKT-CSR/26-10/0003', 'status' => 'assign_material', 'type' => 'service_first']);
        $job = JobSchedule::find(168);
        $jas = JobAssignSchedule::create(['job_schedule_id' => 168, 'status' => 'assigned']);
        DB::table('job_assign_material_issues')->insert(['job_assign_schedule_id' => $jas->id, 'material_issue_id' => 68]);

        $this->assertTrue($this->revert($job, $jas, ['job_number' => null, 'status' => 'new_job', 'assign_date' => null], true));

        $this->assertSame('assign_material', DB::table('job_schedules')->find(168)->status);
        $this->assertSame(1, JobAssignSchedule::where('job_schedule_id', 168)->count());
    }
}
