<?php

namespace Tests\Feature;

use Tests\TestCase;

class JobScheduleUnassignTeamScriptTest extends TestCase
{
    public function test_unassign_team_modal_does_not_send_legacy_job_ids_as_room_ids(): void
    {
        $view = file_get_contents(resource_path('views/operational/job-schedules/index.blade.php'));

        $this->assertStringContainsString('const selectedRoomCheckboxes', $view);
        $this->assertStringContainsString("filter(value => /^\\d+$/.test(value))", $view);
        $this->assertStringContainsString("selectedRoomIds.length > 0 ? { room_ids: selectedRoomIds } : {}", $view);
    }

    public function test_unassign_team_endpoint_sanitizes_non_numeric_room_ids(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Operational/JobScheduleController.php'));

        $this->assertStringContainsString("if (\$request->has('room_ids'))", $controller);
        $this->assertStringContainsString("is_numeric(\$roomId) && (int) \$roomId > 0", $controller);
        $this->assertStringContainsString("'room_ids.*' => 'integer'", $controller);
    }
}
