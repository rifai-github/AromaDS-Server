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

    public function test_contract_dropdown_ignores_stale_responses_and_preserves_selection(): void
    {
        // Bug: overlapping Contract dropdown requests could resolve out of order and
        // rebuild the <select> after the user had already chosen a Contract.
        $view = file_get_contents(resource_path('views/marketing/job-advices/index.blade.php'));

        $this->assertStringContainsString('let contractDropdownRequestToken = 0;', $view);
        $this->assertStringContainsString('let contractDropdownMarketingId = null;', $view);
        $this->assertStringContainsString('const requestToken = ++contractDropdownRequestToken;', $view);
        $this->assertGreaterThanOrEqual(
            2,
            substr_count($view, 'if (requestToken !== contractDropdownRequestToken) {')
        );
        $this->assertStringContainsString(
            "const previouslySelectedContractId = isSameMarketing ? String(contractSelect.value || '') : '';",
            $view
        );
        $this->assertStringContainsString('contractSelect.value = previouslySelectedContractId;', $view);
    }

    public function test_pic_loading_falls_back_to_in_memory_customer_id_map(): void
    {
        // Bug: PIC dropdown could stay empty after selecting a Contract/Quotation if the
        // selected <option>'s data-customer-id attribute wasn't readable yet when the
        // change handler ran. loadCustomerContacts() must still be reachable via an
        // in-memory id->customer_id map populated when the dropdown options are built.
        $view = file_get_contents(resource_path('views/marketing/job-advices/index.blade.php'));

        $this->assertStringContainsString('let contractCustomerIdMap = {};', $view);
        $this->assertStringContainsString('let quotationCustomerIdMap = {};', $view);
        $this->assertStringContainsString("contractCustomerIdMap[String(contract.id)] = contract.customer_id || '';", $view);
        $this->assertStringContainsString("quotationCustomerIdMap[String(quotation.id)] = quotation.customer_id || '';", $view);
        $this->assertGreaterThanOrEqual(
            2,
            substr_count($view, "|| contractCustomerIdMap[String(contractId)] || ''")
        );
        $this->assertStringContainsString("|| quotationCustomerIdMap[String(quotationId)] || ''", $view);
    }

    /**
     * "Add/Choose Rooms" on an existing draft JA used to hide any room flagged
     * is_used_in_other_ja outright - a blanket rule from back when that flag
     * meant "genuinely unavailable". Since ContractController::getForJobAdvice()
     * started letting rooms held only by a migrated Catalyst JA through (they
     * never produced a real job schedule), that same flag is now also set on
     * rooms the backend deliberately still wants selectable. Skipping them in
     * JS re-hid exactly the rooms the backend fix was supposed to expose -
     * found via QA report 10 Agu 2026 where a fresh JA for a migrated contract
     * showed "Tidak ada ruangan yang tersedia" despite the API returning 3.
     */
    public function test_add_room_modal_does_not_re_hide_rooms_the_backend_already_decided_to_show(): void
    {
        $view = file_get_contents(resource_path('views/marketing/job-advices/show.blade.php'));

        $this->assertStringNotContainsString('Skip completely instead of showing', $view);
        $this->assertStringNotContainsString('return; // Skip this room', $view);
        $this->assertStringContainsString('const isUsedInOtherJa = contractRoom.is_used_in_other_ja || false;', $view);
        $this->assertStringContainsString("Dipakai di \${usedByJa", $view);
    }
}
