<?php

namespace Tests\Feature;

use App\Models\JobAdvice;
use App\Models\JobSchedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA 7 Sep 2026, contract 5973: the install job and its first service were both scheduled
 * on the same day, and the service row happened to be inserted first. The service timeline
 * ordered by (schedule_date, id) then made the SERVICE #1 and the install #2, shifting every
 * material's due-service by one for the whole contract (see
 * RentalDetail::isDueAtServiceSequence, which anchors on install = service #1).
 *
 * Install anchors the timeline by definition, so it must win a same-date tie regardless of
 * insertion order.
 */
class ServiceSequenceAnchorOrderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('job_advices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->string('type')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_advice_id')->nullable();
            $table->string('type')->nullable();
            $table->date('schedule_date')->nullable();
            $table->date('expected_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('job_schedules');
        Schema::dropIfExists('job_advices');

        parent::tearDown();
    }

    private function schedule(int $jobAdviceId, string $type, string $date): JobSchedule
    {
        $schedule = new JobSchedule;
        $schedule->job_advice_id = $jobAdviceId;
        $schedule->type = $type;
        $schedule->schedule_date = $date;
        $schedule->save();

        return $schedule;
    }

    private function jobAdvice(int $contractId): JobAdvice
    {
        $advice = new JobAdvice;
        $advice->contract_id = $contractId;
        $advice->save();

        return $advice;
    }

    public function test_install_is_service_one_even_when_a_same_day_service_was_inserted_first(): void
    {
        $advice = $this->jobAdvice(5973);

        // Insertion order is deliberately service-then-install, as QA hit it.
        $serviceFirst = $this->schedule($advice->id, 'service_first', '2026-09-07');
        $install = $this->schedule($advice->id, 'install', '2026-09-07');
        $serviceSecond = $this->schedule($advice->id, 'service_first', '2026-09-07');

        $this->assertSame(1, $install->getServiceSequenceNumber(), 'install anchors the timeline');
        $this->assertSame(2, $serviceFirst->getServiceSequenceNumber());
        $this->assertSame(3, $serviceSecond->getServiceSequenceNumber());
    }

    public function test_normal_chronological_chain_is_unchanged(): void
    {
        $advice = $this->jobAdvice(5961);

        $install = $this->schedule($advice->id, 'install', '2026-08-15');
        $first = $this->schedule($advice->id, 'service_first', '2026-08-15');
        $second = $this->schedule($advice->id, 'service', '2026-08-25');
        $third = $this->schedule($advice->id, 'service', '2026-09-04');

        $this->assertSame(1, $install->getServiceSequenceNumber());
        $this->assertSame(2, $first->getServiceSequenceNumber());
        $this->assertSame(3, $second->getServiceSequenceNumber());
        $this->assertSame(4, $third->getServiceSequenceNumber());
    }

    public function test_other_contracts_do_not_shift_the_sequence(): void
    {
        $ours = $this->jobAdvice(100);
        $theirs = $this->jobAdvice(200);

        $this->schedule($theirs->id, 'install', '2026-01-01');
        $this->schedule($theirs->id, 'service', '2026-02-01');

        $install = $this->schedule($ours->id, 'install', '2026-03-01');
        $service = $this->schedule($ours->id, 'service', '2026-04-01');

        $this->assertSame(1, $install->getServiceSequenceNumber());
        $this->assertSame(2, $service->getServiceSequenceNumber());
    }

    public function test_remove_jobs_do_not_occupy_a_slot(): void
    {
        $advice = $this->jobAdvice(300);

        $install = $this->schedule($advice->id, 'install', '2026-05-01');
        $this->schedule($advice->id, 'remove', '2026-05-10');
        $service = $this->schedule($advice->id, 'service', '2026-06-01');

        $this->assertSame(1, $install->getServiceSequenceNumber());
        $this->assertSame(2, $service->getServiceSequenceNumber());
    }

    public function test_schedule_without_a_contract_has_no_sequence(): void
    {
        $advice = $this->jobAdvice(0);
        $advice->contract_id = null;
        $advice->save();

        $installFree = $this->schedule($advice->id, 'install_free', '2026-09-07');

        $this->assertNull($installFree->getServiceSequenceNumber());
    }
}
