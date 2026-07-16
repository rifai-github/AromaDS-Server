<?php

namespace Tests\Unit;

use App\Services\Imports\Catalyst\CatalystMasterDataImporter;
use Tests\TestCase;

class CatalystJobAdviceImportMappingTest extends TestCase
{
    public function test_job_advice_room_step_resolves_required_dependencies_in_order(): void
    {
        $importer = $this->importer();

        $steps = $importer->resolveRequestedSteps(['job_advice_rooms']);

        $this->assertContains('contracts', $steps);
        $this->assertContains('contract_rooms', $steps);
        $this->assertContains('contract_rentals', $steps);
        $this->assertContains('quotations', $steps);
        $this->assertContains('quotation_rooms', $steps);
        $this->assertContains('quotation_rentals', $steps);
        $this->assertContains('job_advices', $steps);
        $this->assertContains('job_advice_rooms', $steps);
        $this->assertLessThan(array_search('job_advices', $steps, true), array_search('contracts', $steps, true));
        $this->assertLessThan(array_search('job_advices', $steps, true), array_search('quotations', $steps, true));
        $this->assertLessThan(array_search('job_advice_rooms', $steps, true), array_search('job_advices', $steps, true));
        $this->assertLessThan(array_search('job_advice_rooms', $steps, true), array_search('quotation_rentals', $steps, true));
    }

    public function test_job_advice_steps_can_run_exactly_without_dependency_expansion(): void
    {
        $importer = $this->importer();

        $steps = $importer->resolveRequestedSteps(['job_advice_rooms', 'job_advices'], false);

        $this->assertSame(['job_advices', 'job_advice_rooms'], $steps);
        $this->assertNotContains('contracts', $steps);
        $this->assertNotContains('quotations', $steps);
        $this->assertNotContains('quotation_rooms', $steps);
        $this->assertNotContains('quotation_rentals', $steps);
    }

    public function test_it_builds_stable_catalyst_job_advice_header_payload(): void
    {
        $importer = $this->importer();

        $payload = $importer->jobAdvicePayload((object) [
            'id' => 51,
            'contract_number' => 'JKT-CTR/25/001',
            'customer_id' => 7,
            'quotation_id' => 11,
            'marketing_id' => 19,
            'start_date' => '2025-07-15',
            'first_service_date' => '2025-08-15',
        ], 'JKT-CTR/25/001');

        $this->assertSame('JA-CATALYST-JKT-CTR/25/001', $importer->jobAdviceNumber('JKT-CTR/25/001'));
        $this->assertSame('install', $payload['type']);
        $this->assertSame('approved', $payload['status']);
        $this->assertSame(51, $payload['contract_id']);
        $this->assertSame(11, $payload['quotation_id']);
        $this->assertSame(7, $payload['customer_id']);
        $this->assertSame('Customer Alpha', $payload['company_name']);
        $this->assertSame(19, $payload['request_by']);
        $this->assertSame('2025-07-15', $payload['expected_date']);
        $this->assertSame('2025-08-15', $payload['first_service_date']);
        $this->assertTrue($payload['with_materials']);
        $this->assertFalse($payload['with_invoicing']);
    }

    public function test_it_builds_job_advice_room_payload_from_contract_rental(): void
    {
        $importer = $this->importer();

        $payload = $importer->jobAdviceRoomPayload((object) [
            'id' => 91,
            'contract_id' => 51,
            'room_id' => 301,
            'master_rental_id' => 88,
            'rental_alias' => '',
            'quantity' => 1.2,
            'qty_free' => 0.4,
        ], (object) [
            'id' => 71,
            'room_id' => 301,
        ]);

        $this->assertSame(71, $payload['contract_room_id']);
        $this->assertSame(88, $payload['rental_product_id']);
        $this->assertSame('Lobby', $payload['room_name']);
        $this->assertSame('Aroma Premium', $payload['rental_name']);
        $this->assertSame(2, $payload['quantity']);
        $this->assertSame(1, $payload['qty_free']);
        $this->assertSame('pending', $payload['status']);
        $this->assertTrue($payload['rental_has_installation']);
        $this->assertTrue($payload['rental_has_service']);
        $this->assertFalse($payload['unit_already_installed']);
    }

    public function test_it_builds_safe_legacy_document_candidates_without_changing_the_original(): void
    {
        $importer = $this->importer();

        $this->assertSame(
            ['DPS-SQ/24-03/0005..', 'DPS-SQ/24-03/0005'],
            $importer->jobAdviceDocumentCandidates('  dps-sq/24-03/0005..  ')
        );
        $this->assertSame(
            ['MKS-AG/23-08/0015 -1', 'MKS-AG/23-08/0015'],
            $importer->jobAdviceDocumentCandidates('MKS-AG/23-08/0015 -1')
        );
        $this->assertSame(
            ['JKT-AG/23-08/0001-11', 'JKT-AG/23-08/0001'],
            $importer->jobAdviceDocumentCandidates('JKT-AG/23-08/0001-11')
        );
    }

    public function test_contract_mapping_has_priority_over_quotation_fallback(): void
    {
        $importer = $this->importer();
        $importer->mappedTargets = [
            'MKTContractHd|contracts|ADS-SQ/25-02/0432' => 51,
            'MKTQuotationHd|quotations|ADS-SQ/25-02/0432' => 61,
        ];

        $this->assertSame([
            'type' => 'contract',
            'target_table' => 'contracts',
            'target_id' => 51,
            'document_number' => 'ADS-SQ/25-02/0432',
        ], $importer->jobAdviceSource('ADS-SQ/25-02/0432'));
    }

    public function test_it_falls_back_to_a_normalized_quotation_mapping_and_keeps_orphans_unresolved(): void
    {
        $importer = $this->importer();
        $importer->mappedTargets = [
            'MKTQuotationHd|quotations|DPS-SQ/24-03/0005' => 61,
        ];

        $this->assertSame([
            'type' => 'quotation',
            'target_table' => 'quotations',
            'target_id' => 61,
            'document_number' => 'DPS-SQ/24-03/0005',
        ], $importer->jobAdviceSource('DPS-SQ/24-03/0005..'));
        $this->assertNull($importer->jobAdviceSource('TEST240401D'));
    }

    public function test_it_builds_job_advice_header_and_room_payloads_from_quotation(): void
    {
        $importer = $this->importer();

        $header = $importer->quotationJobAdvicePayload((object) [
            'id' => 61,
            'quotation_number' => 'ADS-SQ/25-02/0432',
            'customer_id' => 7,
            'company_name' => 'Customer Alpha',
            'marketing_id' => 19,
            'quotation_date' => '2025-02-14',
        ], 'ADS-SQ/25-02/0432.', 'ADS-SQ/25-02/0432');

        $this->assertNull($header['contract_id']);
        $this->assertSame(61, $header['quotation_id']);
        $this->assertSame(7, $header['customer_id']);
        $this->assertSame(19, $header['request_by']);
        $this->assertSame('2025-02-14', $header['expected_date']);

        $room = $importer->quotationJobAdviceRoomPayload((object) [
            'id' => 91,
            'quotation_id' => 61,
            'quotation_room_id' => 81,
            'master_rental_id' => 88,
            'aroma_name' => 'Aroma Quote',
            'quantity' => 1.2,
            'qty_free' => 0.4,
        ], (object) [
            'id' => 81,
            'room_id' => 301,
            'room_name' => 'Meeting Room',
        ]);

        $this->assertNull($room['contract_room_id']);
        $this->assertNull($room['contract_rental_id']);
        $this->assertSame(81, $room['quotation_room_id']);
        $this->assertSame(91, $room['quotation_rental_id']);
        $this->assertSame(88, $room['rental_product_id']);
        $this->assertSame('Meeting Room', $room['room_name']);
        $this->assertSame('Aroma Quote', $room['rental_name']);
        $this->assertSame(2, $room['quantity']);
        $this->assertSame(1, $room['qty_free']);
    }

    private function importer(): object
    {
        return new class extends CatalystMasterDataImporter
        {
            public array $mappedTargets = [];

            public function resolveRequestedSteps(array $steps, bool $includeDependencies = true): array
            {
                return $this->resolveSteps($steps, $includeDependencies);
            }

            public function jobAdviceNumber(string $contractNo): string
            {
                return $this->catalystJobAdviceNumber($contractNo);
            }

            public function jobAdvicePayload(object $contract, string $contractNo): array
            {
                return $this->buildCatalystJobAdvicePayload($contract, $contractNo);
            }

            public function jobAdviceRoomPayload(object $contractRental, ?object $contractRoom): array
            {
                return $this->buildCatalystJobAdviceRoomPayload($contractRental, $contractRoom);
            }

            public function jobAdviceDocumentCandidates(string $documentNumber): array
            {
                return $this->catalystJobAdviceDocumentCandidates($documentNumber);
            }

            public function jobAdviceSource(string $documentNumber): ?array
            {
                return $this->resolveCatalystJobAdviceSource($documentNumber);
            }

            public function quotationJobAdvicePayload(object $quotation, string $sourceDocumentNumber, ?string $resolvedDocumentNumber = null): array
            {
                return $this->buildCatalystQuotationJobAdvicePayload($quotation, $sourceDocumentNumber, $resolvedDocumentNumber);
            }

            public function quotationJobAdviceRoomPayload(object $quotationRental, ?object $quotationRoom): array
            {
                return $this->buildCatalystQuotationJobAdviceRoomPayload($quotationRental, $quotationRoom);
            }

            protected function findMappedTargetId(string $sourceTable, ?string $sourceKey, string $targetTable): ?int
            {
                return $this->mappedTargets[implode('|', [$sourceTable, $targetTable, $sourceKey])] ?? null;
            }

            protected function cachedTargetRecord(string $table, ?int $id): ?object
            {
                return match ($table) {
                    'customers' => (object) ['id' => $id, 'name' => 'Customer Alpha'],
                    'master_rentals' => (object) ['id' => $id, 'rental_name' => 'Aroma Premium'],
                    'master_rooms' => (object) ['id' => $id, 'room_name' => 'Lobby'],
                    default => null,
                };
            }

            protected function actorId(): ?int
            {
                return 99;
            }
        };
    }
}
