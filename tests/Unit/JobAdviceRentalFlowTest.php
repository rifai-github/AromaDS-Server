<?php

namespace Tests\Unit;

use App\Http\Controllers\Marketing\JobAdviceController;
use App\Models\JobAdviceRoom;
use App\Models\MasterRental;
use Illuminate\Support\Collection;
use ReflectionClass;
use Tests\TestCase;

class JobAdviceRentalFlowTest extends TestCase
{
    public function test_multi_rental_room_is_split_by_rental_flow(): void
    {
        $controller = new JobAdviceController();
        $method = (new ReflectionClass($controller))->getMethod('splitRoomsByRentalJobFlow');
        $method->setAccessible(true);

        $unitOnlyRoom = $this->makeRoomWithRentalType('unit_only');
        $refillOnlyRoom = $this->makeRoomWithRentalType('refill_only');

        $result = $method->invoke($controller, collect([$unitOnlyRoom, $refillOnlyRoom]));

        $this->assertInstanceOf(Collection::class, $result['install']);
        $this->assertInstanceOf(Collection::class, $result['service']);
        $this->assertInstanceOf(Collection::class, $result['check']);

        $this->assertCount(1, $result['install']);
        $this->assertTrue($result['install']->contains($unitOnlyRoom));
        $this->assertFalse($result['install']->contains($refillOnlyRoom));

        $this->assertCount(1, $result['service']);
        $this->assertTrue($result['service']->contains($refillOnlyRoom));
        $this->assertFalse($result['service']->contains($unitOnlyRoom));

        $this->assertCount(1, $result['check']);
        $this->assertTrue($result['check']->contains($unitOnlyRoom));
        $this->assertFalse($result['check']->contains($refillOnlyRoom));
    }

    public function test_unit_only_flow_creates_check_after_or_instead_of_install(): void
    {
        $controller = new JobAdviceController();
        $method = (new ReflectionClass($controller))->getMethod('determineRentalJobFlow');
        $method->setAccessible(true);

        $result = $method->invoke($controller, $this->makeRoomWithRentalType('unit_only'));

        $this->assertTrue($result['needs_install']);
        $this->assertFalse($result['needs_service']);
        $this->assertTrue($result['needs_check']);
    }

    private function makeRoomWithRentalType(string $rentalType): JobAdviceRoom
    {
        $rental = new MasterRental(['rental_type' => $rentalType]);
        $room = new JobAdviceRoom(['room_name' => 'Lobby']);
        $room->setRelation('rentalProduct', $rental);

        return $room;
    }
}
