<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Kontrak hasil migrasi Catalyst membawa JA riwayat (`JA-CATALYST-...`) yang
 * berstatus approved tapi tidak pernah menghasilkan job schedule maupun
 * unit-on-wall.
 *
 * Kalau JA riwayat itu ikut mengunci room, kontrak lama mentok total: Install
 * ditolak karena room "sudah dipakai", Service ditolak karena tidak ada bukti
 * install yang completed. Test ini mengunci kedua jalan keluarnya.
 */
class JobAdviceMigratedContractRoomAvailabilityTest extends TestCase
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
            $table->text('notes_operation')->nullable();
            $table->text('notes_finance')->nullable();
            $table->text('notes_sales')->nullable();
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
            $table->string('room_type')->nullable();
            $table->string('room_floor')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contract_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->timestamps();
        });

        Schema::create('master_rentals', function (Blueprint $table) {
            $table->id();
            $table->string('rental_code')->nullable();
            $table->string('rental_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contract_rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->foreignId('master_rental_id')->nullable();
            $table->string('rental_alias')->nullable();
            $table->integer('quantity')->nullable();
            $table->decimal('qty_free', 8, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('unit_on_walls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('contract_room_id')->nullable();
            $table->foreignId('building_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('status')->nullable();
            $table->string('room_name')->nullable();
            $table->string('rental_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_advices', function (Blueprint $table) {
            $table->id();
            $table->string('job_advice_number')->nullable();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('quotation_id')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_advice_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_advice_id')->nullable();
            $table->foreignId('contract_room_id')->nullable();
            $table->foreignId('quotation_room_id')->nullable();
            $table->foreignId('contract_rental_id')->nullable();
            $table->foreignId('rental_product_id')->nullable();
            $table->integer('quantity')->nullable();
            $table->decimal('qty_free', 8, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_advice_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
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

        // Kontrak hasil migrasi Catalyst.
        DB::table('contracts')->insert([
            'id' => 20,
            'contract_number' => 'SMG-AG/25-04/0013',
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

        DB::table('contract_rentals')->insert([
            'id' => 55,
            'contract_id' => 20,
            'room_id' => 40,
            'master_rental_id' => 7,
            'rental_alias' => 'Aroma Unit + Refill',
            'quantity' => 1,
            'qty_free' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // JA riwayat dari importer: approved, tapi tanpa job schedule / unit-on-wall.
        DB::table('job_advices')->insert([
            'id' => 80,
            'job_advice_number' => 'JA-CATALYST-SMG-AG/25-04/0013',
            'contract_id' => 20,
            'customer_id' => 10,
            'type' => 'install',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_advice_rooms')->insert([
            'id' => 90,
            'job_advice_id' => 80,
            'contract_room_id' => 50,
            'contract_rental_id' => 55,
            'rental_product_id' => 7,
            'quantity' => 1,
            'qty_free' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        foreach ([
            'job_schedules',
            'job_advice_rooms',
            'job_advices',
            'unit_on_walls',
            'contract_rentals',
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

    public function test_install_still_offers_a_room_held_only_by_a_migrated_job_advice(): void
    {
        $response = $this->getJson('/api/contracts/20/for-job-advice?type=install');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'contract_rooms')
            ->assertJsonPath('contract_rooms.0.id', 50);
    }

    public function test_service_still_offers_a_room_held_only_by_a_migrated_job_advice(): void
    {
        $response = $this->getJson('/api/contracts/20/for-job-advice?type=service');

        $response->assertOk()
            ->assertJsonCount(1, 'contract_rooms')
            ->assertJsonPath('contract_rooms.0.id', 50);
    }

    public function test_a_room_held_by_a_migrated_job_advice_is_still_flagged_as_used(): void
    {
        $response = $this->getJson('/api/contracts/20/for-job-advice?type=install');

        $response->assertOk()
            ->assertJsonPath('used_rooms', 1)
            ->assertJsonPath('contract_rooms.0.is_used_in_other_ja', true)
            ->assertJsonPath('contract_rooms.0.used_by_ja.job_advice_number', 'JA-CATALYST-SMG-AG/25-04/0013');
    }

    public function test_a_normal_job_advice_still_blocks_install(): void
    {
        DB::table('job_advices')->where('id', 80)->update([
            'job_advice_number' => 'SMG-JA/26-08/0001',
        ]);

        $response = $this->getJson('/api/contracts/20/for-job-advice?type=install');

        $response->assertOk()
            ->assertJsonCount(0, 'contract_rooms')
            ->assertJsonPath('used_rooms', 1);
    }

    public function test_a_normal_job_advice_alone_still_does_not_qualify_a_room_for_service(): void
    {
        DB::table('job_advices')->where('id', 80)->update([
            'job_advice_number' => 'SMG-JA/26-08/0001',
        ]);

        $response = $this->getJson('/api/contracts/20/for-job-advice?type=service');

        $response->assertOk()
            ->assertJsonCount(0, 'contract_rooms');
    }

    public function test_a_cancelled_migrated_job_advice_does_not_qualify_a_room_for_service(): void
    {
        DB::table('job_advices')->where('id', 80)->update(['status' => 'cancelled']);

        $response = $this->getJson('/api/contracts/20/for-job-advice?type=service');

        $response->assertOk()
            ->assertJsonCount(0, 'contract_rooms');
    }
}
