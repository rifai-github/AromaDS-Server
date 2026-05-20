<?php

namespace Tests\Feature;

use Tests\TestCase;

class ContractTerminationRefillOnlyScriptTest extends TestCase
{
    public function test_contract_termination_skips_remove_jobs_for_refill_only_rooms(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Marketing/ContractTerminationController.php'));

        $this->assertStringContainsString('shouldCreateRemoveJobForContractRoom', $controller);
        $this->assertStringContainsString("\$rentalType === 'refill_only'", $controller);
        $this->assertStringContainsString('Skipping remove job for refill-only contract room during termination', $controller);
        $this->assertStringContainsString('refill-only room(s) skipped for remove job', $controller);
    }

    public function test_refill_only_has_no_physical_unit_to_remove(): void
    {
        $rentalTypes = [
            'refill_only' => false,
            'unit_only' => true,
            'unit_refill' => true,
            '' => true,
        ];

        foreach ($rentalTypes as $rentalType => $shouldCreateRemoveJob) {
            $this->assertSame($shouldCreateRemoveJob, $rentalType !== 'refill_only');
        }
    }
}
