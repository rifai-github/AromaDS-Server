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

    public function test_only_unit_only_rooms_are_treated_as_check_flow(): void
    {
        $controller = new JobAdviceController();
        $method = (new ReflectionClass($controller))->getMethod('roomsRepresentUnitOnlyCheckFlow');
        $method->setAccessible(true);

        $unitOnlyRoom = $this->makeRoomWithRentalType('unit_only');
        $refillOnlyRoom = $this->makeRoomWithRentalType('refill_only');

        $this->assertTrue($method->invoke($controller, collect([$unitOnlyRoom])));
        $this->assertFalse($method->invoke($controller, collect([$unitOnlyRoom, $refillOnlyRoom])));
        $this->assertFalse($method->invoke($controller, collect([$refillOnlyRoom])));
    }

    public function test_remove_flow_is_limited_to_active_on_wall_units_with_serial_numbers(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Marketing/JobAdviceController.php'));
        $filterMethod = $this->extractMethodSource($controller, 'filterRemoveRoomsWithActiveOnWallUnits');

        $this->assertStringContainsString('filterRemoveRoomsWithActiveOnWallUnits', $controller);
        $this->assertStringContainsString('No active Unit On Wall with serial number found for remove Job Advice', $controller);
        $this->assertStringContainsString('whereNotNull(\'serial_number_id\')', $filterMethod);
        $this->assertStringNotContainsString('orWhereNotNull(\'serial_number\')', $filterMethod);
        $this->assertStringContainsString("return 'room_' . \$roomId;", $controller);
    }

    private function extractMethodSource(string $source, string $methodName): string
    {
        $start = strpos($source, 'private function ' . $methodName);
        $this->assertNotFalse($start, "Method {$methodName} not found in controller source.");

        $braceStart = strpos($source, '{', $start);
        $this->assertNotFalse($braceStart, "Method {$methodName} body not found in controller source.");

        $depth = 0;
        $length = strlen($source);

        for ($index = $braceStart; $index < $length; $index++) {
            if ($source[$index] === '{') {
                $depth++;
            } elseif ($source[$index] === '}') {
                $depth--;

                if ($depth === 0) {
                    return substr($source, $start, $index - $start + 1);
                }
            }
        }

        $this->fail("Method {$methodName} body is not balanced.");
    }

    private function makeRoomWithRentalType(string $rentalType): JobAdviceRoom
    {
        $rental = new MasterRental(['rental_type' => $rentalType]);
        $room = new JobAdviceRoom(['room_name' => 'Lobby']);
        $room->setRelation('rentalProduct', $rental);

        return $room;
    }
}
