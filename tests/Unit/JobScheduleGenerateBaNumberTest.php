<?php

namespace Tests\Unit;

use App\Models\JobSchedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Bug #30: every job that went through finalizeWithBa() (web) or
 * verifyJob() (mobile) got the IDENTICAL ba_number — confirmed on live QA
 * data, where 16 different done_job rows all shared
 * "JKT-BA/26-06/0001". Root cause: those two call sites generated the
 * number via DocumentNumberService::generate('berita_acara', ...), which
 * counts rows in a separate `berita_acara` table that is never actually
 * populated (0 rows) — so the "next sequence" always resolved to 1.
 *
 * JobSchedule::generateBaNumber() is the fix: it counts existing
 * job_schedules.ba_number rows directly (the table BA numbers are actually
 * stored in), mirroring the one call site that was already correct
 * (JobScheduleController::generateBANumber()).
 */
class JobScheduleGenerateBaNumberTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->nullable();
            $table->string('ba_number')->nullable();
            $table->date('ba_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('job_schedules');

        parent::tearDown();
    }

    public function test_first_ba_number_of_the_month_is_sequence_one(): void
    {
        $number = JobSchedule::generateBaNumber(new \DateTime('2026-06-15'));

        $this->assertSame('JKT-BA/26-06/0001', $number);
    }

    public function test_each_successive_job_gets_a_distinct_incrementing_number(): void
    {
        // Mirrors the live bug: multiple jobs each call generateBaNumber() and
        // immediately persist the result before the next one is generated.
        JobSchedule::create(['job_number' => 'A', 'ba_number' => JobSchedule::generateBaNumber(new \DateTime('2026-06-15'))]);
        JobSchedule::create(['job_number' => 'B', 'ba_number' => JobSchedule::generateBaNumber(new \DateTime('2026-06-20'))]);
        JobSchedule::create(['job_number' => 'C', 'ba_number' => JobSchedule::generateBaNumber(new \DateTime('2026-06-22'))]);

        $numbers = JobSchedule::pluck('ba_number')->all();

        $this->assertSame(
            ['JKT-BA/26-06/0001', 'JKT-BA/26-06/0002', 'JKT-BA/26-06/0003'],
            $numbers,
            'Each job must get a distinct, incrementing BA number instead of the same one every time.'
        );
        $this->assertCount(3, array_unique($numbers), 'BA numbers must not collide across different jobs.');
    }

    public function test_sequence_resets_for_a_different_month(): void
    {
        JobSchedule::create(['job_number' => 'A', 'ba_number' => JobSchedule::generateBaNumber(new \DateTime('2026-06-30'))]);

        $julyNumber = JobSchedule::generateBaNumber(new \DateTime('2026-07-01'));

        $this->assertSame('JKT-BA/26-07/0001', $julyNumber);
    }
}
