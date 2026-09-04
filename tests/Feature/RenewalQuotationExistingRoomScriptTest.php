<?php

namespace Tests\Feature;

use Tests\TestCase;

class RenewalQuotationExistingRoomScriptTest extends TestCase
{
    public function test_renewal_endpoint_separates_survey_detail_and_master_room_ids(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Marketing/ContractRenewalController.php'));

        // Null-safe: a contract imported without a quotation has no survey to find the
        // room in, and survey_detail_id is allowed to come back empty for it.
        $this->assertStringContainsString("'survey_detail_id' => \$surveyDetail?->id", $controller);
        $this->assertStringContainsString("'master_room_id' => \$room->room_id", $controller);
        $this->assertStringContainsString("'room_id' => \$surveyDetail->id ?? \$room->room_id", $controller);
        $this->assertStringContainsString("'survey_detail_id' => \$resolvedSurveyDetailId", $controller);
        $this->assertStringContainsString("'master_room_id' => \$resolvedRoomId", $controller);
        $this->assertStringContainsString('getActiveUnitOnWallsForRenewal', $controller);
        $this->assertStringContainsString("'unit_on_wall_id' => \$unitOnWall?->id", $controller);
        $this->assertStringContainsString("'source' => \$unitOnWall ? 'unit_on_wall' : 'contract'", $controller);
        $this->assertStringContainsString('buildRenewalRooms', $controller);
        $this->assertStringContainsString("'source' => \$unitOnWall ? 'unit_on_wall' : 'contract_rental'", $controller);
        $this->assertStringContainsString('$resolvedRentalId = $rental->master_rental_id ?: $unitOnWall?->rental_id;', $controller);
        $this->assertStringContainsString("\$resolvedRentalName = (\$rental->masterRental->rental_name ?? '')", $controller);
        $this->assertStringContainsString('$contract->contractRentals', $controller);
        $this->assertStringContainsString("'contract_room_id' => \$contractRoom?->id", $controller);
        $this->assertStringContainsString("'specifications' => \$resolvedSpecifications ?: \$this->masterRoomSpecifications", $controller);
        $this->assertStringContainsString("'specifications' => \$surveyDetail?->specifications ?: \$this->masterRoomSpecifications", $controller);
        $this->assertStringContainsString('private function masterRoomSpecifications', $controller);
    }

    public function test_renewal_endpoint_survives_a_contract_without_a_quotation(): void
    {
        // 2325 live contracts were imported without a quotation. They stayed invisible
        // while the picker scoped branch through the quotation; now that they can be
        // picked, every lookup through $contract->quotation has to tolerate a null or
        // opening one for renewal 500s (QA contract 4905 SBY-AG/23-03/0011).
        $controller = file_get_contents(app_path('Http/Controllers/Marketing/ContractRenewalController.php'));

        // ?? coalesces the whole chain, so those reads are already safe.
        $unguarded = array_filter(
            preg_grep(
                '/\$contract->quotation->(survey_id|quotationDetails|quotationRooms|quotationSurveys)\b/',
                explode("\n", $controller)
            ),
            fn ($line) => ! str_contains($line, '??')
        );

        $this->assertSame([], array_values($unguarded), 'Reach the quotation with ?-> : it can be null.');

        $this->assertStringContainsString('$contract->quotation?->survey_id', $controller);
        $this->assertStringContainsString('$contract->quotation?->quotationDetails', $controller);
        $this->assertStringContainsString('$contract->quotation?->quotationRooms', $controller);
    }

    public function test_eligible_contracts_use_same_renewal_block_rule_as_contract_detail_endpoint(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Marketing/ContractRenewalController.php'));

        $this->assertStringContainsString('$blockReason = $contract->getRenewalBlockReason();', $controller);
        $this->assertStringContainsString('Contract::query()', $controller);
        $this->assertStringNotContainsString("Contract::where('contract_status', 'active')", $controller);
    }

    public function test_wizard_preserves_existing_renewal_room_metadata_until_submit(): void
    {
        $view = file_get_contents(resource_path('views/marketing/quotations/wizard/create.blade.php'));

        $this->assertStringContainsString('existing-contract-select', $view);
        $this->assertStringContainsString('function initializeExistingContractSearch()', $view);
        $this->assertStringContainsString("placeholder: 'Cari nomor contract atau customer...'", $view);
        $this->assertStringContainsString('initializeExistingContractSearch();', $view);
        $this->assertStringContainsString('room_id: room.survey_detail_id || room.room_id', $view);
        $this->assertStringContainsString('master_room_id: room.master_room_id || room.room_id || null', $view);
        $this->assertStringContainsString('contract_room_id: room.contract_room_id || null', $view);
        $this->assertStringContainsString("survey_id: room.survey_id || data.survey_id || 'custom'", $view);
        $this->assertStringContainsString("surveyId: rental.survey_id || data.survey_id || 'custom'", $view);
        $this->assertStringContainsString("surveyId: rental.survey_id || window.renewalContractData.survey_id || 'custom'", $view);
        $this->assertStringContainsString('contractRoomId: rental.contract_room_id || null', $view);
        $this->assertStringContainsString('isRenewalWithExistingRooms', $view);
        $this->assertStringContainsString('room_selections_data[${index}][master_room_id]', $view);
        $this->assertStringContainsString('room_selections_data[${index}][room_name]', $view);
        $this->assertStringContainsString('data-contract-room-id="${contractRoomId}"', $view);
        $this->assertStringContainsString('data-contract-room-id="${room.contract_room_id || \'\'}"', $view);
        $this->assertStringContainsString('formData.append(`rental_items[${uniqueId}][contract_room_id]`', $view);
        $this->assertStringContainsString('formData.append(`room_selections_data[${index}][specifications]`', $view);
        $this->assertStringContainsString('specifications: room.specifications || null', $view);
        $this->assertStringContainsString("$('#custom-rooms-container .custom-room-checkbox:checked')", $view);
        $this->assertStringContainsString('String(resolvedRoomId).startsWith', $view);
        $this->assertStringContainsString('const renewalRooms = Array.isArray(data.rooms) ? data.rooms : []', $view);
        $this->assertStringContainsString('if (roomSelections.length === 0 && data.rentals && data.rentals.length > 0)', $view);
        $this->assertStringContainsString('const roomKey = rental.master_room_id || rental.room_id || rental.room_name', $view);
        $this->assertStringContainsString('function hasRenewalExistingRooms()', $view);
        $this->assertStringContainsString('renderRenewalExistingRoomsWithoutSurvey', $view);
        $this->assertStringContainsString('Ruangan Existing Contract', $view);
        $this->assertStringContainsString('Aroma/Variant Lama dari Contract Existing', $view);
        $this->assertStringContainsString('window.resolveAromaProductOptionId', $view);
        $this->assertStringContainsString('window.applyAromaSelection', $view);
        $this->assertStringContainsString('window.normalizeRenewalRoomName', $view);
        $this->assertStringContainsString('window.withAromaSelectionFallback', $view);
        $this->assertStringContainsString('window.findRenewalContractRoomSource', $view);
        $this->assertStringContainsString('source.aroma_variant', $view);
        $this->assertStringContainsString("const surveySection = aromaContainer.closest('.survey-room-section')", $view);
        $this->assertStringContainsString("surveySection.find('.room-checkbox:checked').not('.custom-room-checkbox')", $view);
        $this->assertStringContainsString('window.rebuildAromaDropdownsForAllCheckedSurveyRooms', $view);
        $this->assertStringContainsString("typeof window.loadAromaProducts === 'function'", $view);
        $this->assertStringContainsString('const matchingSelection = selectionsToRestore.find', $view);
        $this->assertStringContainsString('resolvedIdMap[matchingSelection.room_id] = customRoom.id', $view);
        $this->assertStringContainsString('const isRenewalExistingSource =', $view);
        $this->assertStringContainsString('is_renewal_existing: isRenewalExistingSource', $view);
        $this->assertStringContainsString('aroma_product_id: sourceRoom.aroma_product_id || null', $view);
        $this->assertStringContainsString('aroma_variant: sourceRoom.aroma_variant || sourceRoom.aroma_display_name || null', $view);
        $this->assertStringContainsString('function applyRentalProductPrice', $view);
        $this->assertStringContainsString('function fetchAndApplyRentalProductPrice', $view);
        $this->assertStringContainsString('if (productSelect.val() && !priceInput.val())', $view);
        $this->assertStringContainsString('product_id: productId', $view);
        $this->assertStringContainsString('async function resolveSummaryRentalPrice', $view);
        $this->assertStringContainsString('fetchRentalProductForSummary(productId, surveyId, selectedText)', $view);
        $this->assertStringContainsString('updateSummaryTotalsFromRentalItems(summarySubTotal)', $view);
        $this->assertStringContainsString('room.contract_room_id && selection.contract_room_id', $view);
        $this->assertStringContainsString('clearRenewalContractScopedWizardData', $view);
        $this->assertStringContainsString('window.buildRenewalRentalUniqueId', $view);
        $this->assertStringContainsString('sanitizeRenewalRoomName', $view);
        $this->assertStringContainsString('function getRoomNameFromSelectionRow(row, cellIndex = 2)', $view);
        $this->assertStringContainsString('row.find(`td:eq(${cellIndex}) > strong`).first().text()', $view);
        $this->assertStringContainsString('row.find(`td:eq(${cellIndex}) > small.text-muted`).first().text()', $view);
        $this->assertStringContainsString('getRoomNameFromSelectionRow(row, 1)', $view);
        $this->assertStringContainsString('sanitizeRenewalRoomName(config.roomName)', $view);
        $this->assertStringContainsString('.add-rental-btn[data-survey-id="${surveyId}"][data-contract-room-id="${contractRoomId}"]', $view);
        $this->assertStringContainsString('seenRenewalRooms', $view);
        $this->assertStringContainsString('normalizeRenewalRoomName(r.room_name)', $view);
        $this->assertStringContainsString('function isFreeOnlyRentalConfig', $view);
        $this->assertStringContainsString('function applyFreeOnlyPriceState', $view);
        $this->assertStringContainsString("priceInput.data('paid-price-before-free'", $view);
        $this->assertStringContainsString('const effectivePriceNum = isFreeOnlyRentalConfig(config) ? 0 : priceNum;', $view);
    }

    public function test_quotation_detail_page_cleans_renewal_room_display_and_falls_back_to_existing_contract(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Marketing/QuotationController.php'));
        $view = file_get_contents(resource_path('views/marketing/quotations/show.blade.php'));

        $this->assertStringContainsString("'quotationRooms.room'", $controller);
        $this->assertStringContainsString("'existingContract.contractRooms.room'", $controller);
        $this->assertStringContainsString("'existingContract.contractRentals.masterRental'", $controller);
        $this->assertStringContainsString('$sanitizeRenewalRoomName', $view);
        $this->assertStringContainsString('$displayRoomName = $sanitizeRenewalRoomName', $view);
        $this->assertStringContainsString('$resolveContractRoom', $view);
        $this->assertStringContainsString('$masterRoomSpecs($contractRoom->room)', $view);
        $this->assertStringContainsString('$contractRoomRental = $contractRental?->masterRental ?: $contractRoom?->rentalProduct', $view);
        $this->assertStringContainsString('{{ $displayRentalName }}', $view);
    }

    public function test_quotation_approval_allows_pending_renewal_jobs_but_contract_generation_keeps_guard(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Marketing/QuotationController.php'));

        $this->assertStringContainsString('$this->ensureQuotationRenewalCanProceed($quotation, true);', $controller);
        $this->assertStringContainsString('private function ensureQuotationRenewalCanProceed(Quotation $quotation, bool $allowPendingOperationalWork = false): void', $controller);
        $this->assertStringContainsString('if ($allowPendingOperationalWork) {', $controller);
        $this->assertStringContainsString('$blockReason = $contract->getRenewalBlockReason(null, $exceptQuotationId);', $controller);
        $this->assertMatchesRegularExpression(
            '/private function generateContractFromQuotation\(Quotation \$quotation\).*?\$this->ensureQuotationRenewalCanProceed\(\$quotation\);/s',
            $controller
        );
    }

    public function test_backend_keeps_quotation_room_when_survey_detail_lookup_fails(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Marketing/QuotationWizardController.php'));

        $this->assertStringContainsString('resolveQuotationRoomSelection', $controller);
        $this->assertStringContainsString('sanitizeRenewalRoomName', $controller);
        $this->assertStringContainsString('resolveContractRoomFromItem', $controller);
        $this->assertStringContainsString('normalizeSpecifications', $controller);
        $this->assertStringContainsString('surveyTagsValidationRule', $controller);
        $this->assertStringContainsString('allowsRenewalWithoutSurvey', $controller);
        $this->assertStringContainsString('resolveRenewalCustomerContext', $controller);
        $this->assertStringContainsString("\$roomData['survey_detail_id'] ?? \$roomData['room_id']", $controller);
        $this->assertStringContainsString("\$productId = \$request->get('product_id')", $controller);
        $this->assertStringContainsString("\$productsQuery->where('id', \$productId)", $controller);
        $this->assertStringContainsString('if ($masterRoomId) {', $controller);
        $this->assertStringContainsString('$masterRoom = \App\Models\MasterRoom::find($masterRoomId);', $controller);
        $this->assertStringContainsString('$masterRoom->room_name ?: $roomName', $controller);
        $this->assertStringContainsString("'room_code' => strtoupper(substr(\$roomName, 0, 3)) . '-' . uniqid()", $controller);
    }
}
