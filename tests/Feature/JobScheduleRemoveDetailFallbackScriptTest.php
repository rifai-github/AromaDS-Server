<?php

namespace Tests\Feature;

use Tests\TestCase;

class JobScheduleRemoveDetailFallbackScriptTest extends TestCase
{
    public function test_remove_job_detail_scopes_null_job_advice_rooms_to_current_job(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Operational/JobScheduleController.php'));

        $this->assertStringContainsString('Jobs without Job Advice must stay scoped to the current job', $controller);
        $this->assertStringContainsString("\$query->where('job_schedule_id', \$jobSchedule->id);", $controller);
        $this->assertStringContainsString('buildFallbackRentalTeamRows($jobSchedule)', $controller);
    }

    public function test_remove_job_detail_view_allows_fallback_rows_without_job_advice_room(): void
    {
        $view = file_get_contents(resource_path('views/operational/job-schedules/show.blade.php'));

        $this->assertStringContainsString('$roomData = $jaRoom?->contractRoom?->room', $view);
        $this->assertStringContainsString('$jobScheduleRoom->fallback_rental_name', $view);
        $this->assertStringContainsString('$jaRoom?->notes ?? $jobScheduleRoom->notes', $view);
    }
}
