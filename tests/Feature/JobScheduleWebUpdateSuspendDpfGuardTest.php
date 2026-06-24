<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobScheduleController;
use App\Models\JobSchedule;
use ReflectionMethod;
use Tests\TestCase;

class JobScheduleWebUpdateSuspendDpfGuardTest extends TestCase
{
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

        return $method->invoke(new JobScheduleController(), $job, $targetStatus);
    }
}
