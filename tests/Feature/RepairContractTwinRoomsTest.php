<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA 23 Nov 2026 (kontrak BLP-CA/26-11/0001-0002): room kembar hasil auto-create wizard kontrak
 * diarahkan ulang ke Master Room asli, termasuk rental, JA, JS, dan suspend.
 */
class RepairContractTwinRoomsTest extends TestCase
{
    private const TABLES = [
        'contracts', 'quotation_rooms', 'master_rooms', 'contract_rooms', 'contract_rentals', 'job_advices',
        'job_advice_rooms', 'job_schedules', 'job_schedule_rooms', 'unit_on_walls', 'contract_room_suspensions',
        'survey_details', 'quotation_details',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('contracts', function (Blueprint $t) {
            $t->id();
            $t->string('contract_number');
            $t->unsignedBigInteger('quotation_id')->nullable();
            $t->unsignedBigInteger('customer_id')->nullable();
        });
        Schema::create('quotation_rooms', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('quotation_id');
            $t->unsignedBigInteger('room_id')->nullable();
            $t->string('room_name')->nullable();
            $t->softDeletes();
        });
        Schema::create('master_rooms', function (Blueprint $t) {
            $t->id();
            $t->string('room_name');
            $t->string('room_code')->nullable();
            $t->unsignedBigInteger('building_id')->nullable();
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('contract_rooms', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('contract_id');
            $t->unsignedBigInteger('room_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('contract_rentals', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('contract_id');
            $t->unsignedBigInteger('room_id')->nullable();
            $t->unsignedBigInteger('master_rental_id');
            $t->integer('quantity')->default(1);
            $t->integer('qty_free')->default(0);
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('job_advices', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('contract_id')->nullable();
        });
        Schema::create('job_advice_rooms', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('job_advice_id');
            $t->unsignedBigInteger('contract_room_id')->nullable();
            $t->unsignedBigInteger('contract_rental_id')->nullable();
            $t->unsignedBigInteger('rental_product_id')->nullable();
            $t->string('room_name')->nullable();
            $t->timestamps();
        });
        Schema::create('job_schedules', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('job_advice_id')->nullable();
        });
        Schema::create('job_schedule_rooms', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('job_schedule_id');
            $t->unsignedBigInteger('room_id')->nullable();
        });
        Schema::create('unit_on_walls', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('contract_id')->nullable();
            $t->unsignedBigInteger('room_id')->nullable();
        });
        Schema::create('contract_room_suspensions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('contract_id');
            $t->unsignedBigInteger('room_id')->nullable();
        });
        Schema::create('survey_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('room_id')->nullable();
        });
        Schema::create('quotation_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('quotation_id');
            $t->unsignedBigInteger('room_id')->nullable();
        });

        // Kontrak 1: room asli #10 di-rename RT-W01A; wizard membuat kembar #90 "RT-W01" (kode RT--501).
        DB::table('contracts')->insert([
            ['id' => 1, 'contract_number' => 'BLP-CA/26-11/0001', 'quotation_id' => 5, 'customer_id' => 7],
            ['id' => 2, 'contract_number' => 'BLP-CA/26-11/0002', 'quotation_id' => 6, 'customer_id' => 7],
        ]);
        DB::table('quotation_rooms')->insert([
            ['id' => 501, 'quotation_id' => 5, 'room_id' => 10, 'room_name' => 'RT-W01'],
            ['id' => 601, 'quotation_id' => 6, 'room_id' => 20, 'room_name' => 'RT-X01'],
            ['id' => 602, 'quotation_id' => 6, 'room_id' => 21, 'room_name' => 'RT-Y02'],
            ['id' => 603, 'quotation_id' => 6, 'room_id' => 22, 'room_name' => 'RT-Y01'],
        ]);
        DB::table('master_rooms')->insert([
            ['id' => 10, 'room_name' => 'RT-W01A', 'room_code' => 'R10', 'building_id' => 3, 'customer_id' => 7],
            ['id' => 90, 'room_name' => 'RT-W01', 'room_code' => 'RT--501', 'building_id' => 3, 'customer_id' => null],
            ['id' => 20, 'room_name' => 'RT-X01', 'room_code' => 'R20', 'building_id' => 3, 'customer_id' => 7],
            ['id' => 21, 'room_name' => 'RT-Y02', 'room_code' => 'R21', 'building_id' => 3, 'customer_id' => 7],
            // Kembar RT-Y02 (gedung + nama sama) yang dipakai rental kontrak 2.
            ['id' => 91, 'room_name' => 'RT-Y02', 'room_code' => 'RTY-9', 'building_id' => 3, 'customer_id' => null],
            // Kembar RT-Y01 tanpa gedung, customer sama (kasus box #45018).
            ['id' => 92, 'room_name' => 'RT-Y01', 'room_code' => 'RM023006', 'building_id' => null, 'customer_id' => 7],
            ['id' => 22, 'room_name' => 'RT-Y01', 'room_code' => 'R22', 'building_id' => 3, 'customer_id' => 7],
        ]);
        DB::table('contract_rooms')->insert([
            ['id' => 1, 'contract_id' => 1, 'room_id' => 90],
            ['id' => 2, 'contract_id' => 2, 'room_id' => 20],
            ['id' => 3, 'contract_id' => 2, 'room_id' => 21],
            ['id' => 4, 'contract_id' => 2, 'room_id' => 22],
        ]);
        DB::table('contract_rentals')->insert([
            ['id' => 1, 'contract_id' => 1, 'room_id' => 10, 'master_rental_id' => 55, 'quantity' => 1, 'qty_free' => 0],
            ['id' => 2, 'contract_id' => 2, 'room_id' => 20, 'master_rental_id' => 55, 'quantity' => 1, 'qty_free' => 1],
            ['id' => 3, 'contract_id' => 2, 'room_id' => 91, 'master_rental_id' => 55, 'quantity' => 0, 'qty_free' => 1],
            ['id' => 4, 'contract_id' => 2, 'room_id' => 92, 'master_rental_id' => 55, 'quantity' => 0, 'qty_free' => 1],
        ]);
        DB::table('job_advices')->insert(['id' => 1, 'contract_id' => 1]);
        // Fallback lama: rental #1 (MasterRental::first()) yang bukan rental kontrak.
        DB::table('job_advice_rooms')->insert(['id' => 1, 'job_advice_id' => 1, 'contract_room_id' => 1, 'rental_product_id' => 1, 'room_name' => 'RT-W01']);
        DB::table('job_schedules')->insert(['id' => 1, 'job_advice_id' => 1]);
        DB::table('job_schedule_rooms')->insert(['id' => 1, 'job_schedule_id' => 1, 'room_id' => 90]);
        DB::table('contract_room_suspensions')->insert(['id' => 1, 'contract_id' => 1, 'room_id' => 90]);
        DB::table('survey_details')->insert(['id' => 74672, 'room_id' => 92]);
        DB::table('quotation_details')->insert(['id' => 1, 'quotation_id' => 6, 'room_id' => 74672]);
    }

    protected function tearDown(): void
    {
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_dry_run_reports_without_writing(): void
    {
        $this->artisan('contracts:repair-twin-rooms', ['--contract' => [1, 2]])
            ->expectsOutputToContain('Mode DRY-RUN')
            ->expectsOutputToContain('contract_room #1: room #90')
            ->assertSuccessful();

        $this->assertSame(90, (int) DB::table('contract_rooms')->where('id', 1)->value('room_id'));
        $this->assertSame(91, (int) DB::table('contract_rentals')->where('id', 3)->value('room_id'));
        $this->assertSame(1, (int) DB::table('job_advice_rooms')->where('id', 1)->value('rental_product_id'));
    }

    public function test_apply_points_everything_to_the_real_rooms(): void
    {
        $this->artisan('contracts:repair-twin-rooms', ['--contract' => [1, 2], '--apply' => true, '--delete-orphans' => true])
            ->assertSuccessful();

        // Kontrak 1: contract room, JS room, suspend -> room asli #10; JA pakai rental kontrak.
        $this->assertSame(10, (int) DB::table('contract_rooms')->where('id', 1)->value('room_id'));
        $this->assertSame(10, (int) DB::table('job_schedule_rooms')->where('id', 1)->value('room_id'));
        $this->assertSame(10, (int) DB::table('contract_room_suspensions')->where('id', 1)->value('room_id'));
        $this->assertSame([55, 1], [
            (int) DB::table('job_advice_rooms')->where('id', 1)->value('rental_product_id'),
            (int) DB::table('job_advice_rooms')->where('id', 1)->value('contract_rental_id'),
        ]);

        // Kontrak 2: rental free di room kembar #91 kembali ke RT-Y02 asli (#21).
        $this->assertSame(21, (int) DB::table('contract_rentals')->where('id', 3)->value('room_id'));
        $this->assertSame(20, (int) DB::table('contract_rentals')->where('id', 2)->value('room_id'));

        // Kembar tanpa gedung (#92): rental & detail survey ke RT-Y01 asli (#22), room kembar dihapus.
        $this->assertSame(22, (int) DB::table('contract_rentals')->where('id', 4)->value('room_id'));
        $this->assertSame(22, (int) DB::table('survey_details')->where('id', 74672)->value('room_id'));
        $this->assertNotNull(DB::table('master_rooms')->where('id', 92)->value('deleted_at'));

        // Room kembar tanpa customer yang tak dipakai lagi di-soft-delete.
        $this->assertNotNull(DB::table('master_rooms')->where('id', 90)->value('deleted_at'));
        $this->assertNotNull(DB::table('master_rooms')->where('id', 91)->value('deleted_at'));
        $this->assertNull(DB::table('master_rooms')->where('id', 10)->value('deleted_at'));
    }

    public function test_requires_a_contract_option(): void
    {
        $this->artisan('contracts:repair-twin-rooms')->assertFailed();
    }
}
