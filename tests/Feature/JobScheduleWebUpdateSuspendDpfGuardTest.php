<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobScheduleController;
use App\Models\JobSchedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class JobScheduleWebUpdateSuspendDpfGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('job_schedule_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('job_schedule_rooms');

        parent::tearDown();
    }

    /**
     * The generic web "Edit Job Schedule" form (PUT job-schedules/{id}) accepts
     * `status` directly and saves via JobSchedule::update(), bypassing the
     * suspend()/markAsDpf() model methods entirely. MOM: IR and S1 jobs mark
     * the start of an active contract and must never reach suspend/dpf through
     * this path either.
     */
    public function test_install_job_cannot_transition_to_suspend_via_web_update(): void
    {
        $job = new JobSchedule(['type' => 'install', 'status' => 'in_progress']);

        $result = $this->validateWebCompletionTransition($job, 'suspend');

        $this->assertIsArray($result);
        $this->assertSame('error', $result['status']);
        $this->assertStringContainsString('Install (IR)', $result['message']);
    }

    public function test_service_first_job_cannot_transition_to_dpf_via_web_update(): void
    {
        $job = new JobSchedule(['type' => 'service_first', 'status' => 'in_progress']);

        $result = $this->validateWebCompletionTransition($job, 'dpf');

        $this->assertIsArray($result);
        $this->assertSame('error', $result['status']);
    }

    public function test_service_routine_job_can_transition_to_suspend_via_web_update(): void
    {
        $job = new JobSchedule(['type' => 'service_routine', 'status' => 'in_progress']);

        $this->assertTrue($this->validateWebCompletionTransition($job, 'suspend'));
    }

    private function validateWebCompletionTransition(JobSchedule $job, ?string $targetStatus): mixed
    {
        $method = new ReflectionMethod(JobScheduleController::class, 'validateWebCompletionTransition');
        $method->setAccessible(true);

        return $method->invoke(new JobScheduleController, $job, $targetStatus);
    }
}
