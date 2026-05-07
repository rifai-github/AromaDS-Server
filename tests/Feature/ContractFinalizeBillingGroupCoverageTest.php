<?php

namespace Tests\Feature;

use App\Http\Controllers\Marketing\ContractController;
use App\Models\Contract;
use App\Models\Finance\BillingGroup;
use App\Models\Finance\BillingGroupBuilding;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContractFinalizeBillingGroupCoverageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->nullable();
            $table->string('contract_status')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('quotation_id')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('nama_gedung')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->nullable();
            $table->string('room_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contract_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->foreignId('billing_group_id')->nullable();
            $table->timestamps();
        });

        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contract_surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('survey_id')->nullable();
            $table->timestamps();
        });

        Schema::create('billing_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('billing_group_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('billing_group_buildings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_group_id')->nullable();
            $table->foreignId('building_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('billing_group_buildings');
        Schema::dropIfExists('billing_groups');
        Schema::dropIfExists('contract_surveys');
        Schema::dropIfExists('surveys');
        Schema::dropIfExists('contract_rooms');
        Schema::dropIfExists('master_rooms');
        Schema::dropIfExists('buildings');
        Schema::dropIfExists('contracts');

        parent::tearDown();
    }

    public function test_finalize_blocks_contract_when_any_building_has_no_billing_group(): void
    {
        $contract = $this->createDraftContractWithRooms(['RS Siloam Bali', 'RS Siloam Surabaya']);
        $billingGroup = $this->createBillingGroup($contract);

        BillingGroupBuilding::create([
            'billing_group_id' => $billingGroup->id,
            'building_id' => 1,
            'is_active' => true,
        ]);

        $response = app(ContractController::class)->finalize(new Request(), $contract->fresh());
        $payload = $response->getData(true);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('error', $payload['status']);
        $this->assertStringContainsString('RS Siloam Surabaya', $payload['message']);
        $this->assertSame('draft', $contract->fresh()->contract_status);
    }

    public function test_finalize_allows_contract_when_all_buildings_have_billing_groups(): void
    {
        $contract = $this->createDraftContractWithRooms(['RS Siloam Bali', 'RS Siloam Surabaya']);
        $billingGroup = $this->createBillingGroup($contract);

        foreach ([1, 2] as $buildingId) {
            BillingGroupBuilding::create([
                'billing_group_id' => $billingGroup->id,
                'building_id' => $buildingId,
                'is_active' => true,
            ]);
        }

        $response = app(ContractController::class)->finalize(new Request(), $contract->fresh());
        $payload = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('success', $payload['status']);
        $this->assertSame('waiting_for_approval', $contract->fresh()->contract_status);
    }

    private function createDraftContractWithRooms(array $buildingNames): Contract
    {
        $contract = Contract::create([
            'contract_number' => 'TST-CA/26-05/0001',
            'contract_status' => 'draft',
        ]);

        foreach ($buildingNames as $index => $buildingName) {
            $buildingId = $index + 1;
            $roomId = $index + 1;

            \DB::table('buildings')->insert([
                'id' => $buildingId,
                'name' => $buildingName,
                'nama_gedung' => $buildingName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \DB::table('master_rooms')->insert([
                'id' => $roomId,
                'building_id' => $buildingId,
                'room_name' => 'Room ' . $buildingId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \DB::table('contract_rooms')->insert([
                'contract_id' => $contract->id,
                'room_id' => $roomId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $contract;
    }

    private function createBillingGroup(Contract $contract): BillingGroup
    {
        return BillingGroup::create([
            'contract_id' => $contract->id,
            'is_active' => true,
            'billing_group_name' => 'Billing Group 1',
        ]);
    }
}
