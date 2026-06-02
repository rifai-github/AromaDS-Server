<?php

namespace Tests\Feature;

use Tests\TestCase;

class RenewalQuotationExistingRoomScriptTest extends TestCase
{
    public function test_renewal_endpoint_separates_survey_detail_and_master_room_ids(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Marketing/ContractRenewalController.php'));

        $this->assertStringContainsString("'survey_detail_id' => \$surveyDetail->id", $controller);
        $this->assertStringContainsString("'master_room_id' => \$room->room_id", $controller);
        $this->assertStringContainsString("'room_id' => \$surveyDetail->id ?? \$room->room_id", $controller);
        $this->assertStringContainsString("'survey_detail_id' => \$resolvedSurveyDetailId", $controller);
        $this->assertStringContainsString("'master_room_id' => \$resolvedRoomId", $controller);
        $this->assertStringContainsString('getActiveUnitOnWallsForRenewal', $controller);
        $this->assertStringContainsString("'unit_on_wall_id' => \$unitOnWall?->id", $controller);
        $this->assertStringContainsString("'source' => \$unitOnWall ? 'unit_on_wall' : 'contract'", $controller);
        $this->assertStringContainsString('buildRenewalRooms', $controller);
        $this->assertStringContainsString("'source' => \$unitOnWall ? 'unit_on_wall' : 'contract_rental'", $controller);
        $this->assertStringContainsString('$contract->contractRentals', $controller);
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

        $this->assertStringContainsString('room_id: room.survey_detail_id || room.room_id', $view);
        $this->assertStringContainsString('master_room_id: room.master_room_id || room.room_id || null', $view);
        $this->assertStringContainsString('contract_room_id: room.contract_room_id || null', $view);
        $this->assertStringContainsString("survey_id: room.survey_id || data.survey_id || 'custom'", $view);
        $this->assertStringContainsString("surveyId: rental.survey_id || data.survey_id || 'custom'", $view);
        $this->assertStringContainsString("surveyId: rental.survey_id || window.renewalContractData.survey_id || 'custom'", $view);
        $this->assertStringContainsString('isRenewalWithExistingRooms', $view);
        $this->assertStringContainsString('room_selections_data[${index}][master_room_id]', $view);
        $this->assertStringContainsString('room_selections_data[${index}][room_name]', $view);
        $this->assertStringContainsString("$('#custom-rooms-container .custom-room-checkbox:checked')", $view);
        $this->assertStringContainsString('String(resolvedRoomId).startsWith', $view);
        $this->assertStringContainsString('const renewalRooms = Array.isArray(data.rooms) ? data.rooms : []', $view);
        $this->assertStringContainsString('if (roomSelections.length === 0 && data.rentals && data.rentals.length > 0)', $view);
        $this->assertStringContainsString('const roomKey = rental.master_room_id || rental.room_id || rental.room_name', $view);
        $this->assertStringContainsString('function hasRenewalExistingRooms()', $view);
        $this->assertStringContainsString('renderRenewalExistingRoomsWithoutSurvey', $view);
        $this->assertStringContainsString('Ruangan Existing Contract', $view);
        $this->assertStringContainsString('Aroma/Variant Lama dari Contract Existing', $view);
        $this->assertStringContainsString('room.aroma_product_id && window.hasAromaProductOption(room.aroma_product_id)', $view);
    }

    public function test_backend_keeps_quotation_room_when_survey_detail_lookup_fails(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Marketing/QuotationWizardController.php'));

        $this->assertStringContainsString('resolveQuotationRoomSelection', $controller);
        $this->assertStringContainsString('surveyTagsValidationRule', $controller);
        $this->assertStringContainsString('allowsRenewalWithoutSurvey', $controller);
        $this->assertStringContainsString('resolveRenewalCustomerContext', $controller);
        $this->assertStringContainsString("\$roomData['survey_detail_id'] ?? \$roomData['room_id']", $controller);
        $this->assertStringContainsString('if ($masterRoomId) {', $controller);
        $this->assertStringContainsString('$masterRoom = \App\Models\MasterRoom::find($masterRoomId);', $controller);
        $this->assertStringContainsString("'room_code' => strtoupper(substr(\$roomName, 0, 3)) . '-' . uniqid()", $controller);
    }
}
