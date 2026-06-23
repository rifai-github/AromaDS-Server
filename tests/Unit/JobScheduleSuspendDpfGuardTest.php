<?php

namespace Tests\Unit;

use App\Models\JobSchedule;
use Tests\TestCase;

class JobScheduleSuspendDpfGuardTest extends TestCase
{
    /**
     * MOM: IR (install) and S1 (service_first) mark the start of an active
     * contract and must never be suspendable/DPF-able. Only periodic service
     * jobs (service_routine, legacy service) qualify.
     */
    public function test_install_job_cannot_be_suspended_or_dpf(): void
    {
        $job = new JobSchedule(['type' => 'install']);

        $this->assertFalse($job->canBeSuspendedOrDpf());
        $this->expectException(\RuntimeException::class);
        $job->suspend();
    }

    public function test_service_first_job_cannot_be_suspended_or_dpf(): void
    {
        $job = new JobSchedule(['type' => 'service_first']);

        $this->assertFalse($job->canBeSuspendedOrDpf());
        $this->expectException(\RuntimeException::class);
        $job->markAsDpf();
    }

    public function test_install_free_and_remove_jobs_cannot_be_suspended(): void
    {
        $this->assertFalse((new JobSchedule(['type' => 'install_free']))->canBeSuspendedOrDpf());
        $this->assertFalse((new JobSchedule(['type' => 'remove']))->canBeSuspendedOrDpf());
        $this->assertFalse((new JobSchedule(['type' => 'remove_free']))->canBeSuspendedOrDpf());
    }

    public function test_service_routine_and_legacy_service_jobs_can_be_suspended(): void
    {
        $this->assertTrue((new JobSchedule(['type' => 'service_routine']))->canBeSuspendedOrDpf());
        $this->assertTrue((new JobSchedule(['type' => 'service']))->canBeSuspendedOrDpf());
    }
}
