<?php

namespace Tests\Unit;

use App\Models\JobSchedule;
use App\Models\RentalDetail;
use Tests\TestCase;

/**
 * Locks the CLIENT-CONFIRMED (10 Jul 2026) XFreqService rule:
 * service_frequency_multiplier is an INTERVAL measured in SERVICE COUNT (not time).
 * A material is due at service #n when (n - 1) % multiplier == 0. Service #1 = install
 * (all materials fresh). Multiplier 0 = permanent unit (install only).
 */
class XFreqServiceIntervalTest extends TestCase
{
    private function detail(?int $multiplier): RentalDetail
    {
        $detail = new RentalDetail();
        // Assign directly so a null multiplier stays null (not mass-assigned to 0).
        $detail->service_frequency_multiplier = $multiplier;

        return $detail;
    }

    public function test_null_multiplier_is_always_due_backward_compatible(): void
    {
        $detail = $this->detail(null);

        foreach ([1, 2, 3, 5, 12, 37] as $n) {
            $this->assertTrue($detail->isDueAtServiceSequence($n), "seq {$n} should be due when multiplier is null");
        }
    }

    public function test_multiplier_zero_is_permanent_unit_install_only(): void
    {
        $detail = $this->detail(0);

        $this->assertTrue($detail->isDueAtServiceSequence(1), 'unit is fresh at install (service 1)');
        $this->assertFalse($detail->isDueAtServiceSequence(2), 'unit never recurs on service 2');
        $this->assertFalse($detail->isDueAtServiceSequence(13), 'unit never recurs');
    }

    public function test_multiplier_one_is_due_every_service(): void
    {
        $detail = $this->detail(1);

        foreach ([1, 2, 3, 4, 10, 25] as $n) {
            $this->assertTrue($detail->isDueAtServiceSequence($n), "multiplier 1 should be due every service, seq {$n}");
        }
    }

    public function test_multiplier_six_is_due_at_1_7_13(): void
    {
        $detail = $this->detail(6);

        $due = [1, 7, 13, 19];
        $notDue = [2, 3, 4, 5, 6, 8, 12, 14];

        foreach ($due as $n) {
            $this->assertTrue($detail->isDueAtServiceSequence($n), "multiplier 6 should be due at seq {$n}");
        }
        foreach ($notDue as $n) {
            $this->assertFalse($detail->isDueAtServiceSequence($n), "multiplier 6 should NOT be due at seq {$n}");
        }
    }

    public function test_multiplier_twelve_is_due_at_1_13_25(): void
    {
        $detail = $this->detail(12);

        $this->assertTrue($detail->isDueAtServiceSequence(1));
        $this->assertTrue($detail->isDueAtServiceSequence(13));
        $this->assertTrue($detail->isDueAtServiceSequence(25));
        $this->assertFalse($detail->isDueAtServiceSequence(7));
        $this->assertFalse($detail->isDueAtServiceSequence(12));
        $this->assertFalse($detail->isDueAtServiceSequence(24));
    }

    public function test_unknown_sequence_fails_open_for_configured_detail(): void
    {
        $detail = $this->detail(6);

        $this->assertTrue($detail->isDueAtServiceSequence(null), 'unknown ordinal must fail open (include)');
    }

    public function test_filtering_inactive_when_killswitch_off(): void
    {
        config(['aroma.xfreq_service_material_filter' => false]);

        $job = new JobSchedule();
        $job->type = 'service_routine';

        $details = collect([$this->detail(6), $this->detail(0)]);

        $this->assertFalse($job->serviceIntervalFilteringActive($details));
    }

    public function test_filtering_inactive_for_non_service_job(): void
    {
        config(['aroma.xfreq_service_material_filter' => true]);

        $job = new JobSchedule();
        $job->type = 'install';

        $details = collect([$this->detail(6)]);

        $this->assertFalse($job->serviceIntervalFilteringActive($details), 'install jobs are never interval-filtered');
    }

    public function test_filtering_inactive_when_rental_not_configured(): void
    {
        config(['aroma.xfreq_service_material_filter' => true]);

        $job = new JobSchedule();
        $job->type = 'service_routine';

        // All null / 0 -> not configured -> keep full-BOM behaviour.
        $details = collect([$this->detail(null), $this->detail(0)]);

        $this->assertFalse($job->serviceIntervalFilteringActive($details));
    }

    public function test_filtering_active_for_configured_service_job(): void
    {
        config(['aroma.xfreq_service_material_filter' => true]);

        $job = new JobSchedule();
        $job->type = 'service_routine';

        $details = collect([$this->detail(0), $this->detail(6)]);

        $this->assertTrue($job->serviceIntervalFilteringActive($details));
    }

    public function test_sequence_null_when_not_applicable(): void
    {
        // No contract_id -> short-circuits before any DB query.
        $job = new JobSchedule();
        $job->type = 'service_routine';

        $this->assertNull($job->getServiceSequenceNumber());
    }
}
