<?php

namespace Tests\Unit;

use App\Models\JobAdviceRoom;
use App\Models\JobSchedule;
use App\Models\JobScheduleRoom;
use App\Models\MasterRental;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Prod 25 Nov 2026: daftar Job Schedule 500 (OOM) untuk kontrak 221 ruangan karena BOM rental
 * dimuat ulang per ruangan. Jenis alur rental kini di-cache per master_rentals.id per request.
 */
class JobScheduleRentalFlowCacheTest extends TestCase
{
    public function test_rental_flow_is_resolved_once_per_saved_rental_id(): void
    {
        $first = $this->jobWithRental($this->savedRental(77, 'unit_only'));
        // Instance lain dengan id sama (seperti ruangan lain di daftar): BOM tidak dibaca ulang.
        $second = $this->jobWithRental($this->savedRental(77, 'refill_only'));

        $this->assertTrue($first->skips_material_assignment);
        $this->assertTrue($second->skips_material_assignment);
    }

    public function test_unsaved_or_different_rentals_are_not_shared(): void
    {
        $this->assertTrue($this->jobWithRental($this->savedRental(77, 'unit_only'))->skips_material_assignment);
        $this->assertFalse($this->jobWithRental($this->savedRental(78, 'refill_only'))->skips_material_assignment);
        $this->assertFalse($this->jobWithRental(new MasterRental(['rental_type' => 'refill_only']))->skips_material_assignment);
    }

    private function savedRental(int $id, string $type): MasterRental
    {
        $rental = new MasterRental(['rental_type' => $type]);
        $rental->id = $id;
        $rental->exists = true;

        return $rental;
    }

    private function jobWithRental(MasterRental $rental): JobSchedule
    {
        $job = new JobSchedule(['type' => 'service', 'material_checked' => true]);
        $jobAdviceRoom = new JobAdviceRoom(['room_name' => 'Lobby']);
        $jobAdviceRoom->setRelation('rentalProduct', $rental);

        $room = new JobScheduleRoom(['room_name' => 'Lobby']);
        $room->setRelation('rentals', new Collection);
        $room->setRelation('jobAdviceRoom', $jobAdviceRoom);
        $job->setRelation('jobScheduleRooms', new Collection([$room]));

        return $job;
    }
}
