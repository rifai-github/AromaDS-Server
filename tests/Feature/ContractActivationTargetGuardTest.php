<?php

namespace Tests\Feature;

use App\Http\Controllers\Marketing\ContractController;
use App\Models\Contract;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContractActivationTargetGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->nullable();
            $table->string('contract_status')->nullable();
            $table->boolean('is_contract')->default(false);
            $table->foreignId('created_by')->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->timestamp('date_approved')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('contracts');

        parent::tearDown();
    }

    public function test_approve_rejects_contract_when_contract_target_is_no(): void
    {
        $contract = Contract::create([
            'contract_number' => 'TST-CA/26-06/0001',
            'contract_status' => 'waiting_for_approval',
            'is_contract' => false,
        ]);

        Auth::shouldReceive('user')->once()->andReturn(new class {
            public function canApprove(string $module): bool
            {
                return $module === 'contracts';
            }
        });

        $response = app(ContractController::class)->approveContract(new Request(), $contract->fresh());
        $payload = $response->getData(true);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('error', $payload['status']);
        $this->assertStringContainsString('Contract masih NO', $payload['message']);
        $this->assertSame('waiting_for_approval', $contract->fresh()->contract_status);
    }

    public function test_update_status_rejects_active_when_contract_target_is_no(): void
    {
        $contract = Contract::create([
            'contract_number' => 'TST-CA/26-06/0002',
            'contract_status' => 'signed',
            'is_contract' => false,
        ]);

        $request = Request::create('/marketing/contracts/'.$contract->id.'/update-status', 'POST', [
            'status' => 'active',
        ]);

        $response = app(ContractController::class)->updateStatus($request, $contract->fresh());
        $payload = $response->getData(true);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('error', $payload['status']);
        $this->assertStringContainsString('Contract masih NO', $payload['message']);
        $this->assertSame('signed', $contract->fresh()->contract_status);
    }

    public function test_update_status_allows_active_when_contract_target_is_yes(): void
    {
        $contract = Contract::create([
            'contract_number' => 'TST-CA/26-06/0003',
            'contract_status' => 'signed',
            'is_contract' => true,
        ]);

        $request = Request::create('/marketing/contracts/'.$contract->id.'/update-status', 'POST', [
            'status' => 'active',
        ]);

        $response = app(ContractController::class)->updateStatus($request, $contract->fresh());
        $payload = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('success', $payload['status']);
        $this->assertSame('active', $contract->fresh()->contract_status);
    }
}
