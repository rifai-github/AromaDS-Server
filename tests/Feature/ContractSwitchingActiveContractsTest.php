<?php

namespace Tests\Feature;

use App\Http\Controllers\Marketing\ContractSwitchingController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContractSwitchingActiveContractsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->nullable();
            $table->string('contract_status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('customers')->insert([
            'id' => 1,
            'name' => 'Maju Sejahtera Indonesia',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('customers');

        parent::tearDown();
    }

    public function test_active_contracts_include_contract_status_active_when_status_is_draft(): void
    {
        DB::table('contracts')->insert([
            [
                'contract_number' => 'BDG-CA/26-05/0001',
                'customer_id' => 1,
                'start_date' => '2026-05-04',
                'end_date' => '2027-05-03',
                'status' => 'draft',
                'contract_status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'contract_number' => 'JKT-CA/26-05/9999',
                'customer_id' => 1,
                'start_date' => '2026-05-04',
                'end_date' => '2027-05-03',
                'status' => 'terminated',
                'contract_status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = (new ContractSwitchingController())->getActiveContracts(new Request());
        $payload = $response->getData(true);
        $contractNumbers = collect($payload['data'])->pluck('contract_number');

        $this->assertSame('success', $payload['status']);
        $this->assertTrue($contractNumbers->contains('BDG-CA/26-05/0001'));
        $this->assertFalse($contractNumbers->contains('JKT-CA/26-05/9999'));
    }
}
