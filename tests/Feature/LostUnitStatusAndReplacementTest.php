<?php

namespace Tests\Feature;

use App\Http\Controllers\Marketing\LostUnitReportController;
use App\Models\MasterRental;
use App\Models\SerialNumber;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Client decisions of 28 Aug 2026 on the Lost Unit flow.
 *
 * 1. A lost unit gets its own status. It used to be stored as 'retired' with the condition
 *    forced to "Rusak", which made a unit that walked off site indistinguishable from one
 *    that came back broken - the exact question a loss report exists to answer.
 *
 * 2. The replacement raises only the install job. It used to also raise a `service_first`
 *    stamped period 1; on a contract already at period 5 that collided with the real first
 *    period and would have serviced (and billed) the customer twice. QA: "service
 *    penggantinya ikut service berjalan aja".
 */
class LostUnitStatusAndReplacementTest extends TestCase
{
    private function jobTypesFor(string $rentalType): array
    {
        $rental = new MasterRental(['rental_type' => $rentalType]);
        $rental->id = 1;

        $method = new ReflectionMethod(LostUnitReportController::class, 'determineJobTypes');
        $method->setAccessible(true);

        return $method->invoke(new LostUnitReportController, $rental);
    }

    public function test_replacement_raises_only_the_install_job(): void
    {
        $this->assertSame(['install'], $this->jobTypesFor('unit_refill'));
        $this->assertSame(['install'], $this->jobTypesFor('unit_only'));
    }

    public function test_replacement_never_raises_a_service_job(): void
    {
        foreach (['unit_refill', 'unit_only', 'refill_only', 'something_else'] as $rentalType) {
            $this->assertNotContains(
                'service_first',
                $this->jobTypesFor($rentalType),
                "rental type {$rentalType} must not raise a replacement service job"
            );
        }
    }

    public function test_refill_only_has_no_unit_to_replace(): void
    {
        $this->assertSame([], $this->jobTypesFor('refill_only'));
    }

    public function test_unknown_rental_type_still_assumes_a_unit(): void
    {
        $this->assertSame(['install'], $this->jobTypesFor('something_else'));
    }

    public function test_lost_status_reads_as_hilang_and_is_not_reported_as_broken(): void
    {
        $serialNumber = new SerialNumber([
            'serial_number' => 'ADSW10026080004',
            'status' => SerialNumber::STATUS_LOST,
            'condition_status' => SerialNumber::CONDITION_SECOND_READY,
        ]);

        $this->assertSame('Hilang', $serialNumber->status_text);

        // The whole point: a missing unit keeps its last known condition instead of being
        // relabelled "Rusak" the way 'retired' still is.
        $this->assertSame(SerialNumber::CONDITION_SECOND_READY, $serialNumber->effective_condition_status);
    }

    public function test_a_lost_unit_can_never_be_installed(): void
    {
        $lost = new SerialNumber([
            'status' => SerialNumber::STATUS_LOST,
            'condition_status' => SerialNumber::CONDITION_SECOND_READY,
        ]);

        $this->assertFalse($lost->can_install);
        $this->assertSame('Unit berstatus Hilang sehingga tidak bisa dipasang.', $lost->install_block_reason);
    }

    public function test_a_retired_unit_can_never_be_installed(): void
    {
        $retired = new SerialNumber([
            'status' => 'retired',
            'condition_status' => SerialNumber::CONDITION_SECOND_READY,
        ]);

        $this->assertFalse($retired->can_install);
    }

    public function test_a_ready_second_hand_unit_is_still_installable(): void
    {
        $ready = new SerialNumber([
            'status' => 'ready',
            'condition_status' => SerialNumber::CONDITION_SECOND_READY,
        ]);

        $this->assertTrue($ready->can_install);
        $this->assertSame('In Warehouse', $ready->status_text);
    }

    public function test_edit_options_use_the_same_wording_the_pages_display(): void
    {
        // The mismatch QA hit: the edit form said "Ready"/"In Use" while every page showed
        // "In Warehouse"/"In Customer", so the status looked like it could not be edited.
        foreach (SerialNumber::STATUS_EDIT_LABELS as $status => $label) {
            $this->assertSame(
                SerialNumber::STATUS_LABELS[$status],
                $label,
                "edit label for '{$status}' must match what the pages display"
            );
        }

        $this->assertArrayHasKey(SerialNumber::STATUS_LOST, SerialNumber::STATUS_EDIT_LABELS);
    }
}
