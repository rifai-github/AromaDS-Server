<?php

namespace Tests\Feature;

use Tests\TestCase;

class JobScheduleRemoveDetailFallbackScriptTest extends TestCase
{
    public function test_remove_job_detail_scopes_null_job_advice_rooms_to_current_job(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Operational/JobScheduleController.php'));

        $this->assertStringContainsString('buildFallbackRentalTeamRows(JobSchedule $jobSchedule)', $controller);
        $this->assertStringContainsString("'job_schedule_id' => \$jobSchedule->id", $controller);
        $this->assertStringContainsString('buildFallbackRentalTeamRows($jobSchedule)', $controller);
    }

    public function test_remove_job_detail_view_allows_fallback_rows_without_job_advice_room(): void
    {
        $view = file_get_contents(resource_path('views/operational/job-schedules/show.blade.php'));

        $this->assertStringContainsString('$roomData = $jaRoom?->contractRoom?->room', $view);
        $this->assertStringContainsString('$jobScheduleRoom->fallback_rental_name', $view);
        $this->assertStringContainsString('$jaRoom?->notes ?? $jobScheduleRoom->notes', $view);
    }

    public function test_renewal_remove_uses_source_contract_install_serial_numbers_without_room_fallback(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Operational/JobScheduleController.php'));

        $this->assertStringContainsString('resolveRenewalSourceContractForJobAdvice', $controller);
        $this->assertStringContainsString('getInstalledSerialNumberIdsForRemoveJob($jobSchedule, $jobAdvice, $renewalSourceContract)', $controller);
        $this->assertStringContainsString('} elseif ($renewalSourceContract) {', $controller);
        $this->assertStringContainsString('has no installed SNs available for Remove', $controller);
        $this->assertStringContainsString('if ($serialNumbers->isEmpty() && !$renewalSourceContract)', $controller);
    }

    public function test_renewal_auto_remove_skips_when_source_contract_install_serials_are_missing(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Operational/JobScheduleController.php'));

        $this->assertStringContainsString('getInstalledSerialNumberIdsForRemoveJob($removeJob, $jobAdvice, $renewalSourceContract)', $controller);
        $this->assertStringContainsString('Skipping Unit On Wall auto-remove to avoid removing unrelated units', $controller);
        $this->assertStringContainsString('getSerialNumberIdsFromInventoryIssuingReferences', $controller);
        $this->assertStringContainsString('inventory_issuing_item_serials', $controller);
    }
}
