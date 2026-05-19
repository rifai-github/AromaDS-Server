<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\FreeTrial;
use App\Models\Quotation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuotationContractCreationGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('free_trials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('free_trials');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('quotations');

        parent::tearDown();
    }

    public function test_approved_quotation_without_contract_or_active_free_trial_can_create_contract(): void
    {
        $quotation = Quotation::create(['status' => 'approved']);

        $this->assertTrue($quotation->canCreateContract());
    }

    public function test_approved_quotation_with_existing_contract_cannot_create_another_contract(): void
    {
        $quotation = Quotation::create(['status' => 'approved']);
        Contract::create(['quotation_id' => $quotation->id]);

        $this->assertFalse($quotation->fresh()->canCreateContract());
    }

    public function test_approved_quotation_with_active_free_trial_cannot_create_contract(): void
    {
        $quotation = Quotation::create(['status' => 'approved']);
        FreeTrial::create([
            'quotation_id' => $quotation->id,
            'status' => 'active',
        ]);

        $this->assertFalse($quotation->fresh()->canCreateContract());
    }
}
