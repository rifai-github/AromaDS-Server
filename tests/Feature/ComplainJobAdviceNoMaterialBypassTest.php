<?php

namespace Tests\Feature;

use App\Models\JobAdvice;
use App\Models\JobSchedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA 25 Aug 2026: "job complain tanpa material, cara tesnya gimana? soalnya saya coba tetep
 * pakai material juga." The Job Advice's "With Materials" answer was stored, filtered and
 * displayed but never read by the workflow, so a Complain Job Advice marked "No" still had
 * to walk through Material Assign — there was no no-material path to test at all.
 *
 * The scoping to Complain is the load-bearing part of these tests. The create form defaults
 * "With Materials" to "No", so honouring the flag for every Job Advice type would let real
 * installs skip the warehouse: 52 install Job Advices on QA alone carry that unintended "No".
 */
class ComplainJobAdviceNoMaterialBypassTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('job_advices', function (Blueprint $table) {
            $table->id();
            $table->string('job_advice_number')->nullable();
            $table->string('type')->nullable();
            $table->boolean('with_materials')->default(false);
            $table->boolean('with_invoicing')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_advice_id')->nullable();
            $table->string('job_number')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->boolean('material_checked')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_assign_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_schedule_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_assign_material_issues', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_assign_schedule_id')->nullable();
            $table->unsignedBigInteger('material_issue_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('job_assign_material_issues');
        Schema::dropIfExists('job_assign_schedules');
        Schema::dropIfExists('job_schedules');
        Schema::dropIfExists('job_advices');

        parent::tearDown();
    }

    private function makeJob(string $jobAdviceType, bool $withMaterials, string $jobType, bool $materialChecked = false): JobSchedule
    {
        $jobAdvice = JobAdvice::create([
            'job_advice_number' => 'SBY-JA/26-08/9999',
            'type' => $jobAdviceType,
            'with_materials' => $withMaterials,
        ]);

        return JobSchedule::create([
            'job_advice_id' => $jobAdvice->id,
            'type' => $jobType,
            'status' => 'new_job',
            'material_checked' => $materialChecked,
        ]);
    }

    public function test_complain_job_advice_marked_no_material_skips_material_assign(): void
    {
        $job = $this->makeJob('Complain', false, 'complain');

        $this->assertTrue($job->skipsMaterialByJobAdviceDeclaration());
        $this->assertTrue($job->canBypassMaterialAssignFlow());
        $this->assertTrue($job->skips_material_assignment);
    }

    public function test_complain_job_advice_marked_with_material_still_goes_through_material_assign(): void
    {
        $job = $this->makeJob('Complain', true, 'complain');

        $this->assertFalse($job->skipsMaterialByJobAdviceDeclaration());
        $this->assertFalse($job->canBypassMaterialAssignFlow());
        $this->assertFalse($job->skips_material_assignment);
    }

    public function test_install_job_advice_marked_no_material_is_not_affected(): void
    {
        // The 52 install Job Advices on QA that carry an unintended "No" because that is the
        // form's default. An install must never walk past the warehouse on the strength of it.
        $job = $this->makeJob('Install', false, 'install');

        $this->assertFalse($job->skipsMaterialByJobAdviceDeclaration());
        $this->assertFalse($job->canBypassMaterialAssignFlow());
        $this->assertFalse($job->skips_material_assignment);
    }

    public function test_extra_job_advice_marked_no_material_is_not_affected(): void
    {
        // Whether Extra should follow the same rule is still an open question with the client,
        // so it deliberately keeps the old behavior until that is answered.
        $job = $this->makeJob('Extra', false, 'extra');

        $this->assertFalse($job->skipsMaterialByJobAdviceDeclaration());
        $this->assertFalse($job->canBypassMaterialAssignFlow());
    }

    public function test_job_whose_material_was_already_prepared_never_claims_to_be_material_less(): void
    {
        // Flipping the Job Advice to "No" after the warehouse already issued material would
        // otherwise strand what was issued. A real MaterialIssue - not just material_checked
        // - is what proves material was actually prepared; see the next test for why the flag
        // alone can't be trusted for this.
        $job = $this->makeJob('Complain', false, 'complain', materialChecked: true);

        $jobAssignSchedule = \App\Models\JobAssignSchedule::create([
            'job_schedule_id' => $job->id,
            'status' => 'assigned',
        ]);
        \App\Models\JobAssignMaterialIssue::create([
            'job_assign_schedule_id' => $jobAssignSchedule->id,
            'material_issue_id' => 1,
        ]);

        $this->assertTrue($job->hasRecordedMaterialIssue());
        $this->assertFalse($job->skipsMaterialByJobAdviceDeclaration());
        $this->assertFalse($job->canBypassMaterialAssignFlow());
    }

    public function test_material_checked_auto_flag_alone_does_not_block_the_bypass(): void
    {
        // QA 30 Aug 2026, job 725 (SBY-NR/26-08/0004): a genuinely material-less Complain
        // (with_materials=false) gets material_checked auto-flipped to true the first time the
        // technician opens the job (Api\Mobile\JobController::markJobMaterialCheckedIfNeeded,
        // a display convenience — not a real warehouse issuance). Guarding on that flag made
        // the very next check of THIS SAME method report "already prepared" and block "Tiba di
        // Lokasi" with "Material issue tidak ditemukan", even though no MaterialIssue was ever
        // created. The guard must key off a real MaterialIssue, not material_checked.
        $job = $this->makeJob('Complain', false, 'complain', materialChecked: true);

        $this->assertFalse($job->hasRecordedMaterialIssue());
        $this->assertTrue($job->skipsMaterialByJobAdviceDeclaration());
        $this->assertTrue($job->canBypassMaterialAssignFlow());
    }

    public function test_job_without_a_job_advice_is_left_alone(): void
    {
        $job = JobSchedule::create([
            'job_advice_id' => null,
            'type' => 'complain',
            'status' => 'new_job',
        ]);

        $this->assertFalse($job->skipsMaterialByJobAdviceDeclaration());
    }
}
