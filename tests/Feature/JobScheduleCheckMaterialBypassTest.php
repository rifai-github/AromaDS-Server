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

    public function test_job_schedule_page_keeps_material_assign_visible_but_warns_for_displayed_check_jobs(): void
    {
        $view = file_get_contents(resource_path('views/operational/job-schedules/index.blade.php'));

        $this->assertStringContainsString('data-display-type="{{ $job->display_type', $view);
        $this->assertStringContainsString('function isCheckJobType(type, displayType = \'\')', $view);
        $this->assertStringContainsString('function openMaterialAction(jobScheduleId, type = \'\', displayType = \'\')', $view);
        $this->assertStringContainsString('Job Check/Remove tidak menggunakan alur material', $view);
        $this->assertStringContainsString('materialAssignOption.disabled = false', $view);
    }

    private function validateMakeAssignTeam(JobSchedule $job): mixed
    {
        $method = new ReflectionMethod(JobScheduleController::class, 'validateMakeAssignTeam');
        $method->setAccessible(true);

        return $method->invoke(new JobScheduleController(), $job);
    }
}
