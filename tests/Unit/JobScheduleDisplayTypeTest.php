<?php

namespace Tests\Unit;

use App\Models\JobAdviceRoom;
use App\Models\JobSchedule;
use App\Models\JobScheduleRoom;
use App\Models\JobScheduleRoomRental;
use App\Models\MasterRental;
use Illuminate\Support\Collection;
use Tests\TestCase;

class JobScheduleDisplayTypeTest extends TestCase
{
    public function test_done_unit_refill_service_still_displays_as_service(): void
    {
        $job = $this->makeMaterialCheckedService('service_first', ['unit_refill']);

        $this->assertSame('Service Pertama (CSR)', $job->display_type);
    }

    public function test_done_unit_only_service_displays_as_check(): void
    {
        $job = $this->makeMaterialCheckedService('service_first', ['unit_only']);

        $this->assertSame('Check (CHK)', $job->display_type);
    }

    public function test_refill_only_service_still_displays_as_service(): void
    {
        $job = $this->makeMaterialCheckedService('service_first', ['refill_only']);

        $this->assertSame('Service Pertama (CSR)', $job->display_type);
    }

    public function test_mixed_unit_and_refill_service_still_displays_as_service(): void
    {
        $job = $this->makeMaterialCheckedService('service_routine', ['unit_only', 'refill_only']);

        $this->assertSame('Service Routine', $job->display_type);
    }

    private function makeMaterialCheckedService(string $type, array $rentalTypes, bool $materialChecked = true): JobSchedule
    {
        $job = new JobSchedule([
            'type' => $type,
            'material_checked' => $materialChecked,
        ]);

        $room = new JobScheduleRoom(['room_name' => 'Office Room']);
        $rentalLinks = collect($rentalTypes)->map(function (string $rentalType) {
            $rental = new MasterRental(['rental_type' => $rentalType]);
            $jobAdviceRoom = new JobAdviceRoom(['room_name' => 'Office Room']);
            $jobAdviceRoom->setRelation('rentalProduct', $rental);

            $link = new JobScheduleRoomRental();
            $link->setRelation('jobAdviceRoom', $jobAdviceRoom);

            return $link;
        });

        $room->setRelation('rentals', new Collection($rentalLinks));
        $job->setRelation('jobScheduleRooms', new Collection([$room]));

        return $job;
    }
}
