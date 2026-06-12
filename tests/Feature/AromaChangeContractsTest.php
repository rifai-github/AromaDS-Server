<?php

namespace Tests\Feature;

use App\Http\Controllers\Marketing\AromaChangeController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AromaChangeContractsTest extends TestCase
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
            $table->string('contract_status')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('customers')->insert([
            'id' => 10,
            'name' => 'Abadi Company',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('contracts')->insert([
            'id' => 20,
            'contract_number' => 'SBY-CA/26-06/0001',
            'customer_id' => 10,
            'contract_status' => 'active',
            'status' => 'active',
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

    public function test_aroma_change_contract_search_works_without_customer_company_name_column(): void
    {
        $response = (new AromaChangeController())->getContracts(Request::create(
            '/marketing/aroma-changes/get-contracts',
            'GET',
            ['search' => 'SBY-CA/26-06/0001']
        ));

        $payload = $response->getData(true);

        $this->assertCount(1, $payload);
        $this->assertSame(20, $payload[0]['id']);
        $this->assertSame('SBY-CA/26-06/0001 - Abadi Company', $payload[0]['text']);
    }
}
