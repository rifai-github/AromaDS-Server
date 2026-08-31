<?php

namespace Tests\Feature;

use App\Models\JobAdviceRoom;
use App\Models\JobSchedule;
use App\Models\JobScheduleRoom;
use App\Models\JobScheduleRoomRental;
use App\Models\MasterRental;
use App\Models\RentalPrice;
use App\Services\Operational\ExtraJobInvoiceService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Covers what an Extra invoice is actually made of: which rooms get billed, and at what price.
 *
 * The invoice header itself (numbering, tax, billing group) is deliberately out of scope here -
 * it is assembled from the same helpers the Lost Unit invoice already uses. What is specific to
 * Extra, and what QA asked for, is "invoice Job Extra yg hanya room yg dikasih extra saja yg
 * muncul" plus the client's pricing rule.
 */
class ExtraJobInvoiceLinesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->string('building_name')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_advice_id')->nullable();
            $table->unsignedBigInteger('building_id')->nullable();
            $table->string('building_name')->nullable();
            $table->string('job_number')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedule_rooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_schedule_id')->nullable();
            $table->unsignedBigInteger('job_advice_room_id')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->string('room_name')->nullable();
            $table->string('status')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedule_room_rentals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_schedule_room_id')->nullable();
            $table->unsignedBigInteger('job_advice_room_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_advice_rooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_advice_id')->nullable();
            $table->unsignedBigInteger('contract_room_id')->nullable();
            $table->unsignedBigInteger('rental_product_id')->nullable();
            $table->string('room_name')->nullable();
            $table->string('rental_name')->nullable();
            $table->integer('quantity')->nullable();
            $table->integer('qty_free')->nullable();
            $table->string('status')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_rentals', function (Blueprint $table) {
            $table->id();
            $table->string('rental_name')->nullable();
            $table->string('rental_type')->nullable();
            $table->decimal('monthly_price', 15, 2)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('rental_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('master_rental_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->decimal('monthly_price', 15, 2)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        foreach ([
            'rental_prices', 'master_rentals', 'job_advice_rooms', 'job_schedule_room_rentals',
            'job_schedule_rooms', 'job_schedules', 'buildings',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    private function rental(string $name, float $monthly): MasterRental
    {
        return MasterRental::create([
            'rental_name' => $name,
            'rental_type' => 'unit_refill',
            'monthly_price' => $monthly,
        ]);
    }

    private function job(int $buildingId = 1): JobSchedule
    {
        return JobSchedule::create([
            'job_advice_id' => 99,
            'building_id' => $buildingId,
            'job_number' => 'SBY-EXT/26-08/0008',
            'type' => 'extra',
            'status' => 'done_job',
        ]);
    }

    private function jaRoom(MasterRental $rental, string $roomName, int $qty = 1, int $qtyFree = 0): JobAdviceRoom
    {
        return JobAdviceRoom::create([
            'job_advice_id' => 99,
            'rental_product_id' => $rental->id,
            'room_name' => $roomName,
            'rental_name' => $rental->rental_name,
            'quantity' => $qty,
            'qty_free' => $qtyFree,
            'status' => 'scheduled',
        ]);
    }

    private function attachRoom(JobSchedule $job, JobAdviceRoom $jaRoom, bool $viaPivot = true): void
    {
        $scheduleRoom = JobScheduleRoom::create([
            'job_schedule_id' => $job->id,
            'job_advice_room_id' => $viaPivot ? null : $jaRoom->id,
            'room_id' => 13266,
            'room_name' => $jaRoom->room_name,
            'status' => 'completed',
        ]);

        if ($viaPivot) {
            JobScheduleRoomRental::create([
                'job_schedule_room_id' => $scheduleRoom->id,
                'job_advice_room_id' => $jaRoom->id,
            ]);
        }
    }

    private function lines(JobSchedule $job, $jobAdvice = null): array
    {
        $method = new ReflectionMethod(ExtraJobInvoiceService::class, 'buildLines');
        $method->setAccessible(true);

        return $method->invoke(app(ExtraJobInvoiceService::class), $job, $jobAdvice ?? (object) ['id' => 99]);
    }

    public function test_it_bills_the_room_the_extra_was_given_to(): void
    {
        \App\Models\Building::create(['building_name' => 'Gedung ABC', 'branch_id' => 2]);

        $rental = $this->rental('Rental Refill Only', 300000);
        $job = $this->job();
        $this->attachRoom($job, $this->jaRoom($rental, 'Ruang Extra'));

        $lines = $this->lines($job);

        $this->assertCount(1, $lines);
        $this->assertSame('Ruang Extra', $lines[0]['room_name']);
        $this->assertSame('Rental Refill Only', $lines[0]['rental_name']);
        $this->assertSame('SBY-EXT/26-08/0008', $lines[0]['job_no']);
        $this->assertSame(300000.0, $lines[0]['unit_price']);
        $this->assertSame(300000.0, $lines[0]['total_price']);
    }

    public function test_it_does_not_bill_rooms_this_job_never_touched(): void
    {
        // The Job Advice can span rooms handled by sibling schedules. QA asked for "hanya room
        // yg dikasih extra saja yg muncul", so an unattached room must not appear.
        \App\Models\Building::create(['building_name' => 'Gedung ABC', 'branch_id' => 2]);

        $rental = $this->rental('Rental A', 100000);
        $job = $this->job();
        $this->attachRoom($job, $this->jaRoom($rental, 'Ruang Extra'));
        $this->jaRoom($rental, 'Ruang Lain'); // same JA, not attached to this schedule

        $lines = $this->lines($job);

        $this->assertCount(1, $lines);
        $this->assertSame('Ruang Extra', $lines[0]['room_name']);
    }

    public function test_quantity_multiplies_the_price(): void
    {
        \App\Models\Building::create(['building_name' => 'Gedung ABC', 'branch_id' => 2]);

        $rental = $this->rental('Rental A', 250000);
        $job = $this->job();
        $this->attachRoom($job, $this->jaRoom($rental, 'Ruang Extra', 3, 1));

        $lines = $this->lines($job);

        $this->assertSame(3, $lines[0]['quantity']);
        $this->assertSame(1, $lines[0]['qty_free']);
        $this->assertSame(750000.0, $lines[0]['total_price']);
    }

    public function test_a_branch_price_wins_over_the_rental_master(): void
    {
        \App\Models\Building::create(['building_name' => 'Gedung ABC', 'branch_id' => 2]);

        $rental = $this->rental('Rental A', 100000);
        RentalPrice::create(['master_rental_id' => $rental->id, 'branch_id' => 2, 'monthly_price' => 175000]);
        // A different branch's price must be ignored.
        RentalPrice::create(['master_rental_id' => $rental->id, 'branch_id' => 9, 'monthly_price' => 999000]);

        $job = $this->job();
        $this->attachRoom($job, $this->jaRoom($rental, 'Ruang Extra'));

        $this->assertSame(175000.0, $this->lines($job)[0]['unit_price']);
    }

    public function test_it_falls_back_to_the_rental_master_when_the_branch_has_no_price(): void
    {
        \App\Models\Building::create(['building_name' => 'Gedung ABC', 'branch_id' => 2]);

        $rental = $this->rental('Rental A', 120000);
        RentalPrice::create(['master_rental_id' => $rental->id, 'branch_id' => 2, 'monthly_price' => 0]);

        $job = $this->job();
        $this->attachRoom($job, $this->jaRoom($rental, 'Ruang Extra'));

        $this->assertSame(120000.0, $this->lines($job)[0]['unit_price']);
    }

    public function test_an_unpriced_rental_still_produces_a_line(): void
    {
        // 326 of 345 rentals have no price yet. The invoice must still appear - "no invoice
        // showed up" is the complaint this whole flow exists to answer - just at zero.
        \App\Models\Building::create(['building_name' => 'Gedung ABC', 'branch_id' => 2]);

        $rental = $this->rental('Rental Tanpa Harga', 0);
        $job = $this->job();
        $this->attachRoom($job, $this->jaRoom($rental, 'Ruang Extra'));

        $lines = $this->lines($job);

        $this->assertCount(1, $lines);
        $this->assertSame(0.0, $lines[0]['total_price']);
    }

    public function test_rooms_linked_by_the_direct_column_still_bill(): void
    {
        // Older schedules carry job_advice_room_id on the row instead of the pivot.
        \App\Models\Building::create(['building_name' => 'Gedung ABC', 'branch_id' => 2]);

        $rental = $this->rental('Rental A', 90000);
        $job = $this->job();
        $this->attachRoom($job, $this->jaRoom($rental, 'Ruang Extra'), viaPivot: false);

        $this->assertCount(1, $this->lines($job));
    }
}
