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
        $this->assertContains('job_advices', $steps);
        $this->assertContains('job_advice_rooms', $steps);
        $this->assertLessThan(array_search('job_advices', $steps, true), array_search('contracts', $steps, true));
        $this->assertLessThan(array_search('job_advice_rooms', $steps, true), array_search('job_advices', $steps, true));
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

    private function importer(): object
    {
        return new class extends CatalystMasterDataImporter
        {
            public function resolveRequestedSteps(array $steps): array
            {
                return $this->resolveSteps($steps);
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
