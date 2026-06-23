<?php

namespace Tests\Feature;

use App\Http\Controllers\Marketing\ContractController;
use App\Models\Contract;
use App\Models\Quotation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContractDestroyStatusGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number')->nullable();
            $table->string('status')->nullable();
            $table->string('quotation_type')->nullable();
            $table->foreignId('existing_contract_id')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->nullable();
            $table->string('contract_status')->nullable();
            $table->boolean('is_contract')->default(false);
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('quotation_id')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->timestamp('date_approved')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Auth::shouldReceive('id')->andReturn(1);
        Auth::shouldReceive('user')->andReturn(null);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('quotations');

        parent::tearDown();
    }

    public function test_destroy_blocks_non_draft_contract(): void
    {
        $quotation = Quotation::create([
            'quotation_number' => 'SBY-SQ/26-06/0002',
            'status' => 'contract',
        ]);

        $contract = Contract::create([
            'contract_number' => 'SBY-CA/26-06/0002',
            'contract_status' => 'active',
            'quotation_id' => $quotation->id,
        ]);

        $response = app(ContractController::class)->destroy($contract);
        $payload = $response->getData(true);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('error', $payload['status']);
        $this->assertNotNull($contract->fresh());
        $this->assertSame('contract', $quotation->fresh()->status);
    }

    public function test_destroy_allows_draft_contract_and_resets_quotation_status(): void
    {
        $quotation = Quotation::create([
            'quotation_number' => 'SBY-SQ/26-06/0002',
            'status' => 'contract',
        ]);

        $contract = Contract::create([
            'contract_number' => 'SBY-CA/26-06/0002',
            'contract_status' => 'draft',
            'quotation_id' => $quotation->id,
        ]);

        $response = app(ContractController::class)->destroy($contract);
        $payload = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('success', $payload['status']);
        $this->assertNull(Contract::find($contract->id));
        $this->assertSame('approved', $quotation->fresh()->status);
    }
}
