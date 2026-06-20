<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobScheduleController;
use App\Models\JobAdvice;
use App\Models\JobAdviceRoom;
use App\Models\JobSchedule;
use App\Models\JobScheduleRoom;
use App\Models\JobScheduleRoomRental;
use App\Models\MasterRental;
use Illuminate\Support\Collection;
use ReflectionMethod;
use Tests\TestCase;

class JobScheduleCheckMaterialBypassTest extends TestCase
{
    public function test_check_job_can_assign_team_without_material_assign(): void
    {
        $job = new JobSchedule([
            'type' => 'check',
            'status' => 'new_job',
        ]);

        $this->assertTrue($this->validateMakeAssignTeam($job));
    }

    public function test_unit_only_service_check_can_assign_team_without_material_assign(): void
    {
        $job = new JobSchedule([
            'type' => 'service_first',
            'status' => 'scheduled',
        ]);

        $rental = new MasterRental(['rental_type' => 'unit_only']);
        $jobAdviceRoom = new JobAdviceRoom(['room_name' => 'QA Check Room']);
        $jobAdviceRoom->id = 99;
        $jobAdviceRoom->setRelation('rentalProduct', $rental);

        $jobAdvice = new JobAdvice();
        $jobAdvice->id = 10;
        $jobAdvice->setRelation('rooms', new Collection([$jobAdviceRoom]));

        $rentalLink = new JobScheduleRoomRental();
        $rentalLink->job_advice_room_id = 99;
        $room = new JobScheduleRoom(['room_name' => 'QA Check Room']);
        $room->setRelation('rentals', new Collection([$rentalLink]));

        $job->setRelation('jobAdvice', $jobAdvice);
        $job->setRelation('jobScheduleRooms', new Collection([$room]));

        $this->assertTrue($this->validateMakeAssignTeam($job));
    }

    public function test_check_job_can_bypass_material_assign_flow_via_model(): void
    {
        $job = new JobSchedule([
            'type' => 'check',
            'status' => 'new_job',
        ]);

        $this->assertTrue($job->canBypassMaterialAssignFlow());
    }

    public function test_unit_only_service_check_can_bypass_material_assign_flow_via_model(): void
    {
        $job = new JobSchedule([
            'type' => 'service_first',
            'status' => 'scheduled',
        ]);

        $rental = new MasterRental(['rental_type' => 'unit_only']);
        $jobAdviceRoom = new JobAdviceRoom(['room_name' => 'QA Check Room']);
        $jobAdviceRoom->id = 99;
        $jobAdviceRoom->setRelation('rentalProduct', $rental);

        $rentalLink = new JobScheduleRoomRental();
        $rentalLink->job_advice_room_id = 99;
        $rentalLink->setRelation('jobAdviceRoom', $jobAdviceRoom);
        $room = new JobScheduleRoom(['room_name' => 'QA Check Room']);
        $room->setRelation('rentals', new Collection([$rentalLink]));

        $job->setRelation('jobScheduleRooms', new Collection([$room]));

        // Unlike skips_material_assignment (UI flag), this must be true even though
        // material_checked was never set - the gate applies BEFORE material assign.
        $this->assertNull($job->material_checked ?? null);
        $this->assertTrue($job->canBypassMaterialAssignFlow());
    }

    public function test_brand_new_refill_service_job_cannot_bypass_material_assign_flow_via_model(): void
    {
        $job = new JobSchedule([
            'type' => 'service_first',
            'status' => 'scheduled',
            'period' => 1,
        ]);

        $rental = new MasterRental(['rental_type' => 'refill_only']);
        $jobAdviceRoom = new JobAdviceRoom(['room_name' => 'Ruang Tunggu']);
        $jobAdviceRoom->id = 13;
        $jobAdviceRoom->setRelation('rentalProduct', $rental);

        $rentalLink = new JobScheduleRoomRental();
        $rentalLink->job_advice_room_id = 13;
        $rentalLink->setRelation('jobAdviceRoom', $jobAdviceRoom);
        $room = new JobScheduleRoom(['room_name' => 'Ruang Tunggu']);
        $room->setRelation('rentals', new Collection([$rentalLink]));

        $job->setRelation('jobScheduleRooms', new Collection([$room]));

        $this->assertFalse($job->canBypassMaterialAssignFlow());
    }

    public function test_job_schedule_page_keeps_material_assign_visible_but_warns_for_displayed_check_jobs(): void
    {
        $view = file_get_contents(resource_path('views/operational/job-schedules/index.blade.php'));

        $this->assertStringContainsString('data-display-type="{{ $job->display_type', $view);
        $this->assertStringContainsString('function isCheckJobType(type, displayType = \'\')', $view);
        $this->assertStringContainsString('function openMaterialAction(jobScheduleId, type = \'\', displayType = \'\')', $view);
        $this->assertStringContainsString('tidak menggunakan alur material', $view);
        $this->assertStringContainsString('materialAssignOption.disabled = false', $view);
        $this->assertStringContainsString('const groupedRoomMap = new Map();', $view);
        $this->assertStringContainsString('related_room_ids', $view);
        $this->assertStringContainsString('data-room-ids="${(item.related_room_ids || [item.id]).join(\',\')}"', $view);
        $this->assertStringContainsString('selected_display_count: selectedDisplayCount', $view);

        $controller = file_get_contents(app_path('Http/Controllers/Operational/JobScheduleController.php'));
        $this->assertStringContainsString("'selected_display_count' => 'nullable|integer|min:1'", $controller);
        $this->assertStringContainsString("'processed_room_count' => \$successCount", $controller);
        $this->assertStringContainsString("'display_room_count' => \$displaySuccessCount", $controller);
    }

    private function validateMakeAssignTeam(JobSchedule $job): mixed
    {
        $method = new ReflectionMethod(JobScheduleController::class, 'validateMakeAssignTeam');
        $method->setAccessible(true);

        return $method->invoke(new JobScheduleController(), $job);
    }
}
