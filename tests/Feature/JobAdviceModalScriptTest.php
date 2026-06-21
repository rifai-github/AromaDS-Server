<?php

namespace Tests\Feature;

use App\Http\Controllers\Marketing\JobAdviceController;
use App\Models\ContractRental;
use App\Models\JobAdvice;
use App\Models\JobAdviceRoom;
use Tests\TestCase;

class JobAdviceModalScriptTest extends TestCase
{
    public function test_job_advice_edit_dates_do_not_use_utc_iso_conversion(): void
    {
        $indexView = file_get_contents(resource_path('views/marketing/job-advices/index.blade.php'));
        $showView = file_get_contents(resource_path('views/marketing/job-advices/show.blade.php'));

        $this->assertStringNotContainsString("toISOString().split('T')[0]", $indexView);
        $this->assertStringNotContainsString("toISOString().split('T')[0]", $showView);
        $this->assertStringContainsString('function dateValueForInput(dateInput)', $indexView);
        $this->assertStringContainsString('dateString.match(/^(\\d{4}-\\d{2}-\\d{2})$/)', $showView);
    }

    public function test_job_advice_edit_endpoint_returns_calendar_dates_without_utc_shift(): void
    {
        $jobAdvice = new JobAdvice([
            'job_advice_number' => 'JKT-JA/26-06/0003',
            'type' => 'install',
            'expected_date' => '2026-06-15',
            'first_service_date' => '2026-06-20',
            'remove_date' => '2026-07-15',
            'status' => 'draft',
        ]);

        $response = (new JobAdviceController)->edit($jobAdvice);

        $this->assertSame('2026-06-15', $response->getData(true)['expected_date']);
        $this->assertSame('2026-06-20', $response->getData(true)['first_service_date']);
        $this->assertSame('2026-07-15', $response->getData(true)['remove_date']);
    }

    public function test_job_advice_room_operational_quantity_includes_free_source_quantity(): void
    {
        $room = new JobAdviceRoom([
            'quantity' => 0,
            'qty_free' => 1,
        ]);

        $room->setRelation('contractRental', new ContractRental([
            'quantity' => 0,
            'qty_free' => 1,
        ]));

        $this->assertSame(0, $room->quantity);
        $this->assertSame(1, $room->qty_free);
        $this->assertSame(1.0, $room->operational_quantity);
    }

    public function test_install_job_advice_create_flow_keeps_rental_rooms_hidden(): void
    {
        $view = file_get_contents(resource_path('views/marketing/job-advices/index.blade.php'));

        $this->assertStringContainsString("return ['install', 'install_free'].includes(normalizeJobAdviceType(type));", $view);
        $this->assertGreaterThanOrEqual(
            3,
            substr_count($view, 'shouldSelectRoomsAfterCreate(getCreateModalJobAdviceType())')
        );
        $this->assertStringContainsString('function addRoomRow() {', $view);
        $this->assertStringContainsString('resetCreateRoomSelection();', $view);
    }

    public function test_contract_room_loading_guards_against_overlapping_requests(): void
    {
        // Bug: selecting a contract then quickly changing Job Advice Type to "Remove"
        // dispatches a second contract-rooms fetch while the first is still in-flight.
        // Whichever fetch resolves last must win, and any rental-product dropdown left
        // empty by a stale/in-flight fetch must be backfilled once data finally arrives.
        $view = file_get_contents(resource_path('views/marketing/job-advices/index.blade.php'));

        $this->assertStringContainsString('let contractRoomsRequestToken = 0;', $view);
        $this->assertGreaterThanOrEqual(
            2,
            substr_count($view, 'const requestToken = ++contractRoomsRequestToken;')
        );
        $this->assertGreaterThanOrEqual(
            2,
            substr_count($view, 'if (requestToken !== contractRoomsRequestToken) {')
        );
        $this->assertStringContainsString('function refreshRentalProductOptionsInExistingRows() {', $view);
        $this->assertGreaterThanOrEqual(
            2,
            substr_count($view, 'refreshRentalProductOptionsInExistingRows();')
        );
    }
}
