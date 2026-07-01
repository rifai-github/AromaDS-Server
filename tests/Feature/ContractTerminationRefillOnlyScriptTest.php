<?php

namespace Tests\Feature;

use App\Http\Controllers\Marketing\ContractTerminationController;
use App\Models\JobSchedule;
use App\Services\DocumentNumberService;
use ReflectionMethod;
use Tests\TestCase;

class ContractTerminationRefillOnlyScriptTest extends TestCase
{
    public function test_contract_termination_skips_remove_jobs_for_refill_only_rooms(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Marketing/ContractTerminationController.php'));

        $this->assertStringContainsString('shouldCreateRemoveJobForContractRoom', $controller);
        $this->assertStringContainsString("\$rentalType === 'refill_only'", $controller);
        $this->assertStringContainsString('activeOnWallUnitExistsForContractRoom', $controller);
        $this->assertStringContainsString('whereNotNull(\'serial_number_id\')', $controller);
        $this->assertStringContainsString('Skipping remove job for refill-only contract room during termination', $controller);
        $this->assertStringContainsString('room(s) without active unit skipped for remove job', $controller);
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

    public function test_future_service_jobs_do_not_block_contract_termination(): void
    {
        $method = new ReflectionMethod(ContractTerminationController::class, 'jobBlocksContractTermination');
        $method->setAccessible(true);
        $controller = new ContractTerminationController($this->createMock(DocumentNumberService::class));

        $futureServiceJob = new JobSchedule([
            'type' => 'service',
            'status' => 'barang_siap_diambil',
        ]);
        $futureCheckJob = new JobSchedule([
            'type' => 'service_first',
            'status' => 'new_job',
        ]);
        $installJob = new JobSchedule([
            'type' => 'install',
            'status' => 'barang_siap_diambil',
        ]);
        $startedServiceJob = new JobSchedule([
            'type' => 'service',
            'status' => 'barang_diambil',
        ]);

        $this->assertFalse($method->invoke($controller, $futureServiceJob));
        $this->assertFalse($method->invoke($controller, $futureCheckJob));
        $this->assertTrue($method->invoke($controller, $installJob));
        $this->assertTrue($method->invoke($controller, $startedServiceJob));
    }
}
