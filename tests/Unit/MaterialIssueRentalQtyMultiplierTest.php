<?php

namespace Tests\Unit;

use App\Http\Controllers\Operational\JobAssignMaterialIssueController;
use App\Models\ContractRental;
use App\Models\JobAdviceRoom;
use App\Models\JobSchedule;
use App\Models\JobScheduleRoom;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Regression lock for the rental quantity multiplier floor.
 *
 * Commit 9ee06a5 changed the multiplier from max(1, ...) to max(0, ...) on the theory
 * that "contract qty 0 means issue no material". That was wrong: ~1000 legacy
 * contract_rentals rows carry quantity=0 AND qty_free=0 (Catalyst import artifacts that
 * bypassed the app's own validation, which rejects both-zero), which made 1,033 of 18,199
 * job_advice_rooms silently stop generating material entirely.
 *
 * The rule: presence of a rental line in the Job Advice IS the operational decision to
 * service it, so material is issued even when the contract row says qty 0.
 *
 * A 0 multiplier also silently disabled the Submit-to-Issue BOM validation, because
 * validateMaterialIssueBomTargets() skips any group whose target computes to <= 0.
 */
class MaterialIssueRentalQtyMultiplierTest extends TestCase
{
    public function test_zero_quantity_contract_rental_still_yields_a_multiplier_of_one(): void
    {
        // The exact 1,033-room case: contract_rentals row with quantity=0 AND qty_free=0.
        $jobSchedule = $this->jobScheduleWithRoom('Main Hall', 159, $this->contractRental(0, 0));

        $map = $this->buildMap($jobSchedule);

        $this->assertSame(1, $this->resolve($map, 'Main Hall', 159));
    }

    public function test_multi_unit_rental_still_scales_the_multiplier(): void
    {
        // Protects the original "1 Rental banyak Qty" feature through the revert.
        $jobSchedule = $this->jobScheduleWithRoom('Main Hall', 302, $this->contractRental(2, 0));

        $map = $this->buildMap($jobSchedule);

        $this->assertSame(2, $this->resolve($map, 'Main Hall', 302));
    }

    public function test_paid_and_free_quantities_are_summed(): void
    {
        $jobSchedule = $this->jobScheduleWithRoom('Lobby', 57, $this->contractRental(1, 2));

        $map = $this->buildMap($jobSchedule);

        $this->assertSame(3, $this->resolve($map, 'Lobby', 57));
    }

    public function test_room_with_no_rental_source_falls_back_to_one(): void
    {
        $jobSchedule = $this->jobScheduleWithRoom('Lobby', 57, null);

        $map = $this->buildMap($jobSchedule);

        $this->assertSame(1, $this->resolve($map, 'Lobby', 57));
    }

    public function test_unknown_room_resolves_to_one(): void
    {
        $this->assertSame(1, $this->resolve([], 'Not In Map', 999));
    }

    private function buildMap(JobSchedule $jobSchedule): array
    {
        $controller = new JobAssignMaterialIssueController;
        $method = (new ReflectionClass($controller))->getMethod('buildRentalQuantityMultiplierMap');
        $method->setAccessible(true);

        return $method->invoke($controller, $jobSchedule);
    }

    private function resolve(array $map, string $roomName, int $masterRentalId): int
    {
        $controller = new JobAssignMaterialIssueController;
        $method = (new ReflectionClass($controller))->getMethod('resolveRentalQtyMultiplier');
        $method->setAccessible(true);

        return $method->invoke($controller, $map, $roomName, $masterRentalId);
    }

    private function contractRental(int $quantity, int $qtyFree): ContractRental
    {
        return new ContractRental([
            'quantity' => $quantity,
            'qty_free' => $qtyFree,
        ]);
    }

    private function jobScheduleWithRoom(
        string $roomName,
        int $masterRentalId,
        ?ContractRental $contractRental
    ): JobSchedule {
        $adviceRoom = new JobAdviceRoom([
            'room_name' => $roomName,
            'quantity' => 0,
            'qty_free' => 0,
        ]);
        $adviceRoom->rental_product_id = $masterRentalId;

        // getOperationalQuantityAttribute() prefers the linked rental source over the
        // JobAdviceRoom's own columns, so every source relation must be set explicitly.
        $adviceRoom->setRelation('contractRental', $contractRental);
        $adviceRoom->setRelation('quotationRental', null);
        $adviceRoom->setRelation('quotationDetail', null);

        $scheduleRoom = new JobScheduleRoom(['room_name' => $roomName]);
        $scheduleRoom->setRelation('jobAdviceRooms', new Collection([$adviceRoom]));
        $scheduleRoom->setRelation('jobAdviceRoom', $adviceRoom);

        $jobSchedule = new JobSchedule;
        $jobSchedule->setRelation('jobScheduleRooms', new Collection([$scheduleRoom]));

        return $jobSchedule;
    }
}
