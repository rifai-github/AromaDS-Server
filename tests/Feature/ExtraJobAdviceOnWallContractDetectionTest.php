<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExtraJobAdviceOnWallContractDetectionTest extends TestCase
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
            $table->foreignId('marketing_id')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('quotation_id')->nullable();
            $table->string('status')->nullable();
            $table->string('contract_status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('existing_contract_id')->nullable();
            $table->string('quotation_type')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contract_renewals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('new_contract_id')->nullable();
            $table->string('status')->nullable();
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
            $table->timestamps();
        });

        Schema::create('unit_on_walls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('contract_room_id')->nullable();
            $table->foreignId('install_job_schedule_id')->nullable();
            $table->foreignId('building_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('status')->nullable();
            $table->string('company_name')->nullable();
            $table->string('room_name')->nullable();
            $table->string('rental_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('customers')->insert([
            'id' => 10,
            'name' => 'Maju Sejahtera Indonesia',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('buildings')->insert([
            'id' => 30,
            'name' => 'Gedung Utama',
            'nama_gedung' => 'Gedung Utama',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('master_rooms')->insert([
            'id' => 40,
            'building_id' => 30,
            'room_name' => 'Lobby',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('contracts')->insert([
            'id' => 20,
            'contract_number' => 'BDG-CA/26-05/0001',
            'customer_id' => 10,
            'marketing_id' => 1,
            'created_by' => 1,
            'status' => 'active',
            'contract_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('contract_rooms')->insert([
            'id' => 50,
            'contract_id' => 20,
            'room_id' => 40,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('unit_on_walls')->insert([
            'id' => 60,
            'customer_id' => 10,
            'building_id' => 30,
            'room_id' => 40,
            'serial_number' => 'SN-ONWALL-001',
            'status' => 'on_wall',
            'company_name' => 'Maju Sejahtera Indonesia',
            'room_name' => 'Lobby',
            'rental_name' => 'Aroma Unit',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        foreach ([
            'unit_on_walls',
            'contract_rooms',
            'master_rooms',
            'buildings',
            'contract_renewals',
            'quotations',
            'contracts',
            'customers',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_on_wall_unit_returns_detected_contract_reference_for_extra_job_advice(): void
    {
        $response = $this->getJson('/api/contracts/on-wall-units-for-job-advice?marketing_id=1');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.0.id', 60)
            ->assertJsonPath('data.0.contract_id', 20)
            ->assertJsonPath('data.0.contract_number', 'BDG-CA/26-05/0001')
            ->assertJsonPath('data.0.contract_room_id', 50);
    }

    public function test_unit_keeps_its_own_contract_when_a_newer_contract_shares_the_room(): void
    {
        // Same customer + same room, but a newer contract exists. Guessing by
        // customer+room would hand the unit to contract 21 - it belongs to 20.
        $this->insertContract(21, 'BDG-CA/26-06/0007');
        $this->insertContractRoom(51, 21, 40);

        DB::table('unit_on_walls')->where('id', 60)->update([
            'contract_id' => 20,
            'contract_room_id' => 50,
        ]);

        $response = $this->getJson('/api/contracts/on-wall-units-for-job-advice?marketing_id=1');

        $response->assertOk()
            ->assertJsonPath('data.0.id', 60)
            ->assertJsonPath('data.0.contract_id', 20)
            ->assertJsonPath('data.0.contract_number', 'BDG-CA/26-05/0001')
            ->assertJsonPath('data.0.contract_room_id', 50);
    }

    public function test_unit_follows_the_renewal_successor_of_its_stored_contract(): void
    {
        DB::table('quotations')->insert([
            'id' => 70,
            'existing_contract_id' => 20,
            'quotation_type' => 'renewal',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->insertContract(22, 'BDG-CA/26-07/0011', ['quotation_id' => 70]);
        $this->insertContractRoom(52, 22, 40);

        DB::table('unit_on_walls')->where('id', 60)->update([
            'contract_id' => 20,
            'contract_room_id' => 50,
        ]);

        $response = $this->getJson('/api/contracts/on-wall-units-for-job-advice?marketing_id=1');

        $response->assertOk()
            ->assertJsonPath('data.0.id', 60)
            ->assertJsonPath('data.0.contract_id', 22)
            ->assertJsonPath('data.0.contract_number', 'BDG-CA/26-07/0011')
            ->assertJsonPath('data.0.contract_room_id', 52);
    }

    public function test_unit_is_not_offered_when_its_stored_contract_is_terminated(): void
    {
        DB::table('contracts')->where('id', 20)->update([
            'status' => 'terminated',
            'contract_status' => 'terminated',
        ]);

        DB::table('unit_on_walls')->where('id', 60)->update([
            'contract_id' => 20,
            'contract_room_id' => 50,
        ]);

        $response = $this->getJson('/api/contracts/on-wall-units-for-job-advice?marketing_id=1');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(0, 'data');
    }

    public function test_legacy_unit_without_stored_contract_skips_already_renewed_contracts(): void
    {
        // No contract_id on the unit (pre-migration row) so the legacy guess runs.
        // Contract 20 was renewed into 23, so 23 is the one that may take a new Job Advice.
        DB::table('quotations')->insert([
            'id' => 71,
            'existing_contract_id' => 20,
            'quotation_type' => 'renewal',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->insertContract(23, 'BDG-CA/26-08/0002', ['quotation_id' => 71]);
        $this->insertContractRoom(53, 23, 40);

        $response = $this->getJson('/api/contracts/on-wall-units-for-job-advice?marketing_id=1');

        $response->assertOk()
            ->assertJsonPath('data.0.id', 60)
            ->assertJsonPath('data.0.contract_id', 23)
            ->assertJsonPath('data.0.contract_number', 'BDG-CA/26-08/0002');
    }

    private function insertContract(int $id, string $contractNumber, array $overrides = []): void
    {
        DB::table('contracts')->insert(array_merge([
            'id' => $id,
            'contract_number' => $contractNumber,
            'customer_id' => 10,
            'marketing_id' => 1,
            'created_by' => 1,
            'status' => 'active',
            'contract_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function insertContractRoom(int $id, int $contractId, int $roomId): void
    {
        DB::table('contract_rooms')->insert([
            'id' => $id,
            'contract_id' => $contractId,
            'room_id' => $roomId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
