<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobScheduleController;
use App\Models\Contract;
use App\Models\UnitOnWall;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

/**
 * QA 24 Aug 2026, Remove job SBY-RV/26-08/0002: a room rented at qty 2 listed more than
 * 2 serial numbers. Room 460 held 8 active Unit On Wall rows — same customer, same
 * building, same rental_id 4 — belonging to four different contracts
 * (SBY-CA/26-08/0007..0010). customer + building + room + rental matched all 8, so both
 * the Serial Numbers tab and autoRemoveUnitOnWall() (which marks units removed and queues
 * them into an Inventory Receiving) treated every one of them as part of that one Remove.
 *
 * These cover the scope helper itself rather than the full Remove-job completion graph:
 * what actually went wrong is which unit_on_walls rows survive the filter.
 */
class RemoveJobUnitOnWallContractScopeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('unit_on_walls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->unsignedBigInteger('building_id')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->unsignedBigInteger('rental_id')->nullable();
            $table->unsignedBigInteger('serial_number_id')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('unit_on_walls');

        parent::tearDown();
    }

    private function seedUnit(?int $contractId, string $serial): UnitOnWall
    {
        return UnitOnWall::create([
            'customer_id' => 10,
            'contract_id' => $contractId,
            'building_id' => 206,
            'room_id' => 460,
            'rental_id' => 4,
            'serial_number' => $serial,
            'status' => 'active',
        ]);
    }

    private function scope($query, array $contractIds): void
    {
        $method = new ReflectionMethod(JobScheduleController::class, 'scopeUnitOnWallsToContracts');
        $method->setAccessible(true);
        $method->invoke(new JobScheduleController, $query, $contractIds);
    }

    private function contractIds($jobAdvice, ?Contract $renewalSourceContract = null): array
    {
        $method = new ReflectionMethod(JobScheduleController::class, 'removeJobContractIds');
        $method->setAccessible(true);

        return $method->invoke(new JobScheduleController, $jobAdvice, $renewalSourceContract);
    }

    public function test_room_shared_by_several_contracts_only_yields_the_remove_jobs_own_units(): void
    {
        $this->seedUnit(5948, 'DIFF3030021');
        $this->seedUnit(5948, 'DIFF3030022');
        $this->seedUnit(5950, 'DW300W2606011');
        $this->seedUnit(5950, 'DW300W2606012');
        $this->seedUnit(5951, 'DW300B2606019');
        $this->seedUnit(5951, 'DW300B2606018');
        $this->seedUnit(5965, 'DW300W2606014');
        $this->seedUnit(5965, 'DW300W2606008');

        $query = UnitOnWall::where('customer_id', 10)
            ->where('building_id', 206)
            ->where('room_id', 460)
            ->where('rental_id', 4);

        $this->scope($query, [5965]);

        $this->assertSame(
            ['DW300W2606008', 'DW300W2606014'],
            $query->pluck('serial_number')->sort()->values()->all()
        );
    }

    public function test_renewal_source_contracts_units_stay_removable(): void
    {
        $this->seedUnit(5948, 'DIFF3030021');
        $this->seedUnit(5965, 'DW300W2606014');

        $query = UnitOnWall::where('room_id', 460);

        $this->scope($query, [5965, 5948]);

        $this->assertSame(
            ['DIFF3030021', 'DW300W2606014'],
            $query->pluck('serial_number')->sort()->values()->all()
        );
    }

    public function test_legacy_units_without_a_contract_are_left_reachable(): void
    {
        // Rows predating the unit_on_walls.contract_id backfill (5fc9b84), and units whose
        // room was moved to another contract by Contract Switching, carry no matching
        // contract_id. A Remove job is the only way to get them off the wall, so tightening
        // must not hide them.
        $this->seedUnit(null, 'LEGACY001');
        $this->seedUnit(null, 'LEGACY002');

        $query = UnitOnWall::where('room_id', 460);

        $this->scope($query, [5965]);

        $this->assertSame(
            ['LEGACY001', 'LEGACY002'],
            $query->pluck('serial_number')->sort()->values()->all()
        );
    }

    public function test_no_contract_on_the_job_advice_leaves_the_query_untouched(): void
    {
        $this->seedUnit(5948, 'DIFF3030021');
        $this->seedUnit(5965, 'DW300W2606014');

        $query = UnitOnWall::where('room_id', 460);

        $this->scope($query, []);

        $this->assertSame(2, $query->count());
    }

    public function test_contract_ids_collect_the_job_advice_and_renewal_source(): void
    {
        $jobAdvice = (object) ['contract_id' => 5965];

        $renewalSource = new Contract;
        $renewalSource->id = 5948;

        $this->assertSame([5965, 5948], $this->contractIds($jobAdvice, $renewalSource));
        $this->assertSame([5965], $this->contractIds($jobAdvice));
        $this->assertSame([], $this->contractIds(null));
    }
}
