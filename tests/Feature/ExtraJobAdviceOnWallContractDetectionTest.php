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
            $table->string('status')->nullable();
            $table->string('contract_status')->nullable();
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
}
