<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\ContractRental;
use App\Models\JobAdvice;
use App\Models\JobSchedule;
use App\Models\UnitOnWall;
use App\Services\Operational\ContractRenewalRemovalService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Client spec 28 Aug 2026: when a renewal keeps less than the contract before it - a room
 * taken out entirely, or a quantity reduced - the units it dropped must be asked for back
 * automatically once the renewal is active.
 *
 * The tests that matter most here are the ones asserting NOTHING happens: this code raises
 * Remove jobs off a contract comparison, so a wrong answer does not just miss a job, it can
 * pull units off walls the customer is still renting.
 */
class ContractRenewalRemovalTest extends TestCase
{
    private int $roomA = 460;

    private int $roomB = 461;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->date('start_date')->nullable();
            $table->string('contract_status')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contract_rentals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->unsignedBigInteger('master_rental_id')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->integer('quantity')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        // Only needs to be countable: these two tests assert that NOTHING was written here.
        foreach (['job_advices', 'job_schedules'] as $table) {
            Schema::create($table, function (Blueprint $blueprint) {
                $blueprint->id();
                $blueprint->timestamps();
                $blueprint->softDeletes();
            });
        }

        Schema::create('unit_on_walls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->unsignedBigInteger('rental_id')->nullable();
            $table->unsignedBigInteger('serial_number_id')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('install_date')->nullable();
            $table->string('status')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('unit_on_walls');
        Schema::dropIfExists('job_schedules');
        Schema::dropIfExists('job_advices');
        Schema::dropIfExists('contract_rentals');
        Schema::dropIfExists('contracts');

        parent::tearDown();
    }

    private function contract(string $number): Contract
    {
        return Contract::create([
            'contract_number' => $number,
            'customer_id' => 10,
            'start_date' => '2026-09-01',
            'contract_status' => 'active',
        ]);
    }

    private function rental(Contract $contract, int $roomId, int $rentalId, int $qty): void
    {
        ContractRental::create([
            'contract_id' => $contract->id,
            'master_rental_id' => $rentalId,
            'room_id' => $roomId,
            'quantity' => $qty,
        ]);
    }

    private function unit(Contract $contract, int $roomId, int $rentalId, string $serial, string $installDate): UnitOnWall
    {
        return UnitOnWall::create([
            'contract_id' => $contract->id,
            'room_id' => $roomId,
            'rental_id' => $rentalId,
            'serial_number' => $serial,
            'install_date' => $installDate,
            'status' => 'active',
        ]);
    }

    /** @return array{0: array, 1: int} shortfalls and how many units the service would pull */
    private function shortfalls(Contract $new, Contract $old): array
    {
        $method = new \ReflectionMethod(ContractRenewalRemovalService::class, 'resolveShortfalls');
        $method->setAccessible(true);

        return $method->invoke(app(ContractRenewalRemovalService::class), $new, $old);
    }

    private function unitsToRemove(Contract $old, int $roomId, int $rentalId, int $limit)
    {
        $method = new \ReflectionMethod(ContractRenewalRemovalService::class, 'unitsToRemove');
        $method->setAccessible(true);

        return $method->invoke(app(ContractRenewalRemovalService::class), $old, $roomId, $rentalId, $limit);
    }

    public function test_a_room_dropped_entirely_is_a_shortfall_of_its_whole_quantity(): void
    {
        $old = $this->contract('SBY-CA/26-08/0001');
        $new = $this->contract('SBY-CA/26-09/0001');

        $this->rental($old, $this->roomA, 4, 2);
        $this->rental($old, $this->roomB, 4, 1);
        // Renewal keeps only room B.
        $this->rental($new, $this->roomB, 4, 1);

        $this->assertSame(
            [['room_id' => $this->roomA, 'rental_id' => 4, 'drop' => 2]],
            $this->shortfalls($new, $old)
        );
    }

    public function test_a_reduced_quantity_only_drops_the_difference(): void
    {
        $old = $this->contract('SBY-CA/26-08/0002');
        $new = $this->contract('SBY-CA/26-09/0002');

        $this->rental($old, $this->roomA, 4, 2);
        $this->rental($new, $this->roomA, 4, 1);

        $this->assertSame(
            [['room_id' => $this->roomA, 'rental_id' => 4, 'drop' => 1]],
            $this->shortfalls($new, $old)
        );
    }

    public function test_an_unchanged_renewal_removes_nothing(): void
    {
        $old = $this->contract('SBY-CA/26-08/0003');
        $new = $this->contract('SBY-CA/26-09/0003');

        $this->rental($old, $this->roomA, 4, 2);
        $this->rental($old, $this->roomB, 7, 1);
        $this->rental($new, $this->roomA, 4, 2);
        $this->rental($new, $this->roomB, 7, 1);

        $this->assertSame([], $this->shortfalls($new, $old));
    }

    public function test_an_increased_renewal_removes_nothing(): void
    {
        // Additions keep going through Job Advice - this service must never react to them.
        $old = $this->contract('SBY-CA/26-08/0004');
        $new = $this->contract('SBY-CA/26-09/0004');

        $this->rental($old, $this->roomA, 4, 1);
        $this->rental($new, $this->roomA, 4, 3);
        $this->rental($new, $this->roomB, 4, 2);

        $this->assertSame([], $this->shortfalls($new, $old));
    }

    public function test_a_different_rental_in_the_same_room_is_treated_separately(): void
    {
        // Same room, two rentals: dropping one must not touch the other.
        $old = $this->contract('SBY-CA/26-08/0005');
        $new = $this->contract('SBY-CA/26-09/0005');

        $this->rental($old, $this->roomA, 4, 1);
        $this->rental($old, $this->roomA, 7, 1);
        $this->rental($new, $this->roomA, 7, 1);

        $this->assertSame(
            [['room_id' => $this->roomA, 'rental_id' => 4, 'drop' => 1]],
            $this->shortfalls($new, $old)
        );
    }

    public function test_the_longest_installed_units_come_off_first(): void
    {
        $old = $this->contract('SBY-CA/26-08/0006');

        $this->unit($old, $this->roomA, 4, 'NEWEST', '2026-08-01');
        $this->unit($old, $this->roomA, 4, 'OLDEST', '2026-01-15');
        $this->unit($old, $this->roomA, 4, 'MIDDLE', '2026-04-10');

        $this->assertSame(
            ['OLDEST', 'MIDDLE'],
            $this->unitsToRemove($old, $this->roomA, 4, 2)->pluck('serial_number')->all()
        );
    }

    public function test_units_of_another_contract_in_the_same_room_are_never_taken(): void
    {
        // The defect fixed in 8ce6d11, guarded here too: one room can hold several contracts'
        // units on the same rental, and a renewal must only ever pull back its own.
        $old = $this->contract('SBY-CA/26-08/0007');
        $other = $this->contract('SBY-CA/26-08/0008');

        $this->unit($old, $this->roomA, 4, 'MINE', '2026-01-01');
        $this->unit($other, $this->roomA, 4, 'SOMEONE-ELSE', '2025-01-01');

        $this->assertSame(
            ['MINE'],
            $this->unitsToRemove($old, $this->roomA, 4, 5)->pluck('serial_number')->all()
        );
    }

    public function test_it_never_pulls_more_units_than_the_renewal_dropped(): void
    {
        $old = $this->contract('SBY-CA/26-08/0009');

        $this->unit($old, $this->roomA, 4, 'A', '2026-01-01');
        $this->unit($old, $this->roomA, 4, 'B', '2026-02-01');
        $this->unit($old, $this->roomA, 4, 'C', '2026-03-01');

        $this->assertCount(1, $this->unitsToRemove($old, $this->roomA, 4, 1));
    }

    public function test_already_removed_units_are_left_alone(): void
    {
        $old = $this->contract('SBY-CA/26-08/0010');

        $this->unit($old, $this->roomA, 4, 'STILL-UP', '2026-01-01');
        UnitOnWall::create([
            'contract_id' => $old->id,
            'room_id' => $this->roomA,
            'rental_id' => 4,
            'serial_number' => 'ALREADY-GONE',
            'install_date' => '2025-01-01',
            'status' => 'removed',
        ]);

        $this->assertSame(
            ['STILL-UP'],
            $this->unitsToRemove($old, $this->roomA, 4, 5)->pluck('serial_number')->all()
        );
    }

    public function test_the_same_contract_passed_twice_is_a_no_op(): void
    {
        $contract = $this->contract('SBY-CA/26-08/0011');

        $this->assertSame(0, app(ContractRenewalRemovalService::class)->handleActivatedRenewal($contract, $contract));
    }

    public function test_a_missing_contract_is_a_no_op(): void
    {
        $contract = $this->contract('SBY-CA/26-08/0012');
        $service = app(ContractRenewalRemovalService::class);

        $this->assertSame(0, $service->handleActivatedRenewal($contract, null));
        $this->assertSame(0, $service->handleActivatedRenewal(null, $contract));
        $this->assertSame(0, $service->handleActivatedRenewal(null, null));
    }

    public function test_a_renewal_that_dropped_nothing_creates_no_job_advice_or_job(): void
    {
        $old = $this->contract('SBY-CA/26-08/0013');
        $new = $this->contract('SBY-CA/26-09/0013');

        $this->rental($old, $this->roomA, 4, 1);
        $this->rental($new, $this->roomA, 4, 1);
        $this->unit($old, $this->roomA, 4, 'UNTOUCHED', '2026-01-01');

        $before = JobSchedule::count() + JobAdvice::count();

        $this->assertSame(0, app(ContractRenewalRemovalService::class)->handleActivatedRenewal($new, $old));
        $this->assertSame($before, JobSchedule::count() + JobAdvice::count());
    }

    public function test_a_drop_with_nothing_on_the_wall_raises_no_job(): void
    {
        // The contract said 2 but the units were never installed (or already came back).
        // There is nothing to fetch, so there must be no job telling a technician to fetch it.
        $old = $this->contract('SBY-CA/26-08/0014');
        $new = $this->contract('SBY-CA/26-09/0014');

        $this->rental($old, $this->roomA, 4, 2);

        $before = JobSchedule::count() + JobAdvice::count();

        $this->assertSame(0, app(ContractRenewalRemovalService::class)->handleActivatedRenewal($new, $old));
        $this->assertSame($before, JobSchedule::count() + JobAdvice::count());
    }
}
