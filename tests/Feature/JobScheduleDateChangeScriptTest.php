<?php

namespace Tests\Feature;

use Tests\TestCase;

class JobScheduleDateChangeScriptTest extends TestCase
{
    public function test_new_job_rows_can_change_schedule_date_even_if_team_data_exists(): void
    {
        $view = file_get_contents(resource_path('views/operational/job-schedules/index.blade.php'));

        $this->assertStringContainsString('function canChangeScheduleDateForStatus(status)', $view);
        $this->assertStringContainsString("['scheduled', 'new_job']", $view);
        $this->assertStringContainsString("hasAssignedTeam && !canChangeScheduleDateForStatus(cb.getAttribute('data-status'))", $view);
        $this->assertStringContainsString("&& !canChangeScheduleDateForStatus(data.data.status)", $view);
    }

    public function test_schedule_date_status_normalization_accepts_new_job_display_variants(): void
    {
        $allowedStatuses = ['scheduled', 'new_job'];
        $normalize = fn (string $status): string => strtolower(preg_replace('/[\s-]+/', '_', trim($status)));

        $this->assertContains($normalize('new_job'), $allowedStatuses);
        $this->assertContains($normalize('NEW JOB'), $allowedStatuses);
        $this->assertContains($normalize('New Job'), $allowedStatuses);
    }
}
