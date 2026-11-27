<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Mobile\JobController;
use App\Models\JobSchedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA 27 Nov 2026, JKT-IF/26-11/0003 Ruang-3 (TestUO1b3x = dispenser saja, TestHIKDisp = thermal +
 * dispenser). Data prod: dispenser TESTDSP87400A-1 diterbitkan untuk TestUO1b3x (MI item 360 ->
 * WI item 228) tetapi tersimpan di kartu TestHIKDisp, dan TESTDSP87400A-3 (MI item 362 -> WI item
 * 230, TestHIKDisp) tersimpan di kartu TestUO1b3x. Cek kategori 26 Nov meloloskan keduanya karena
 * kedua rental sama-sama butuh dispenser. SN terdaftar ke rental/job-nya, bukan ke ruangannya.
 */
class MobileSerialSiblingRentalMismatchTest extends TestCase
{
    private const CAT_DISPENSER = 11;
    private const CAT_THERMAL = 49;
    private const CAT_REFILL = 60;

    private const JOB_IF = 59849;
    private const JOB_CSR = 60000;

    private const ROOM_UO = 78125;   // Ruang-3, TestUO1b3x
    private const ROOM_HIK = 78126;  // Ruang-3, TestHIKDisp
    private const ROOM_R1 = 78124;   // Ruang-1, TestUO1b1x

    protected function setUp(): void
    {
        parent::setUp();

        $tables = ['job_schedules', 'job_advices', 'job_advice_rooms', 'master_rentals', 'rental_details',
            'product_categories', 'product_types', 'master_products', 'serial_numbers', 'job_schedule_units',
            'job_assign_schedules', 'job_assign_material_issues', 'material_issues', 'material_issue_items',
            'inventory_issuings', 'inventory_issuing_items', 'inventory_issuing_item_serials', 'unit_on_walls'];
        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('job_schedules', function (Blueprint $t) {
            $t->id();
            $t->string('job_number')->nullable();
            $t->string('type')->nullable();
            $t->unsignedBigInteger('job_advice_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('job_advices', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('job_advice_rooms', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('job_advice_id')->nullable();
            $t->unsignedBigInteger('contract_room_id')->nullable();
            $t->unsignedBigInteger('quotation_room_id')->nullable();
            $t->unsignedBigInteger('contract_rental_id')->nullable();
            $t->unsignedBigInteger('quotation_detail_id')->nullable();
            $t->string('room_name')->nullable();
            $t->unsignedBigInteger('rental_product_id')->nullable();
            $t->string('rental_name')->nullable();
            $t->integer('quantity')->nullable();
            $t->integer('operational_quantity')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('master_rentals', function (Blueprint $t) {
            $t->id();
            $t->string('rental_name')->nullable();
            $t->string('rental_type')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('rental_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('master_rental_id')->nullable();
            $t->unsignedBigInteger('product_category_id')->nullable();
            $t->unsignedBigInteger('product_type_id')->nullable();
            $t->unsignedBigInteger('master_product_id')->nullable();
            $t->integer('bom_rental_qty')->nullable();
            $t->integer('quantity')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        foreach (['product_categories', 'product_types'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->string('name')->nullable();
                $t->boolean('is_unit')->default(false);
                $t->timestamps();
                $t->softDeletes();
            });
        }
        Schema::create('master_products', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->unsignedBigInteger('product_category_id')->nullable();
            $t->unsignedBigInteger('product_type_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('serial_numbers', function (Blueprint $t) {
            $t->id();
            $t->string('serial_number')->nullable();
            $t->unsignedBigInteger('master_product_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('job_schedule_units', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('job_schedule_id')->nullable();
            $t->unsignedBigInteger('job_advice_room_id')->nullable();
            $t->string('mac')->nullable();
            $t->timestamps();
        });
        Schema::create('job_assign_schedules', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('job_schedule_id')->nullable();
            $t->timestamps();
        });
        Schema::create('job_assign_material_issues', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('job_assign_schedule_id')->nullable();
            $t->unsignedBigInteger('material_issue_id')->nullable();
            $t->timestamps();
        });
        Schema::create('material_issues', function (Blueprint $t) {
            $t->id();
            $t->string('issue_number')->nullable();
            $t->timestamps();
        });
        Schema::create('material_issue_items', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('material_issue_id')->nullable();
            $t->unsignedBigInteger('job_assign_schedule_id')->nullable();
            $t->unsignedBigInteger('product_id')->nullable();
            $t->string('room_name')->nullable();
            $t->integer('quantity')->nullable();
            $t->string('notes')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('inventory_issuings', function (Blueprint $t) {
            $t->id();
            $t->string('reference_no')->nullable();
            $t->string('status')->nullable();
            $t->timestamps();
        });
        Schema::create('inventory_issuing_items', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('inventory_issuing_id')->nullable();
            $t->unsignedBigInteger('job_assign_schedule_id')->nullable();
            $t->string('room_name')->nullable();
            $t->unsignedBigInteger('product_id')->nullable();
            $t->unsignedBigInteger('serial_number_id')->nullable();
            $t->timestamps();
        });
        Schema::create('inventory_issuing_item_serials', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('inventory_issuing_item_id')->nullable();
            $t->unsignedBigInteger('serial_number_id')->nullable();
            $t->timestamps();
        });
        Schema::create('unit_on_walls', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->unsignedBigInteger('room_id')->nullable();
            $t->unsignedBigInteger('rental_id')->nullable();
            $t->unsignedBigInteger('serial_number_id')->nullable();
            $t->string('serial_number')->nullable();
            $t->string('room_name')->nullable();
            $t->string('status')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        DB::table('product_categories')->insert([
            ['id' => self::CAT_DISPENSER, 'name' => 'Dispenser', 'is_unit' => true],
            ['id' => self::CAT_THERMAL, 'name' => 'Thermal', 'is_unit' => true],
            ['id' => self::CAT_REFILL, 'name' => 'Refill', 'is_unit' => false],
        ]);
        DB::table('master_products')->insert([
            ['id' => 622, 'name' => 'PURE Dispenser 87400A', 'product_category_id' => self::CAT_DISPENSER],
            ['id' => 747, 'name' => 'HIK VISION K1TA70MI-T', 'product_category_id' => self::CAT_THERMAL],
            ['id' => 900, 'name' => 'Refill Lemon', 'product_category_id' => self::CAT_REFILL],
        ]);
        DB::table('serial_numbers')->insert([
            ['id' => 1, 'serial_number' => 'TESTDSP87400A-1', 'master_product_id' => 622],
            ['id' => 3, 'serial_number' => 'TESTDSP87400A-3', 'master_product_id' => 622],
            ['id' => 5, 'serial_number' => 'TESTDSP87400A-5', 'master_product_id' => 622],
            ['id' => 2, 'serial_number' => 'TESTHIKVKT-2', 'master_product_id' => 747],
            ['id' => 9, 'serial_number' => 'REF-1', 'master_product_id' => 900],
        ]);
        DB::table('master_rentals')->insert([
            ['id' => 379, 'rental_name' => 'TestUO1b1x', 'rental_type' => 'unit_only'],
            ['id' => 382, 'rental_name' => 'TestUO1b3x', 'rental_type' => 'unit_only'],
            ['id' => 383, 'rental_name' => 'TestHIKDisp', 'rental_type' => 'unit_only'],
            ['id' => 390, 'rental_name' => 'TestRefill', 'rental_type' => 'refill_only'],
        ]);
        DB::table('rental_details')->insert([
            ['id' => 1060, 'master_rental_id' => 379, 'product_category_id' => self::CAT_DISPENSER, 'bom_rental_qty' => 1],
            ['id' => 1061, 'master_rental_id' => 382, 'product_category_id' => self::CAT_DISPENSER, 'bom_rental_qty' => 1],
            ['id' => 1062, 'master_rental_id' => 383, 'product_category_id' => self::CAT_THERMAL, 'bom_rental_qty' => 1],
            ['id' => 1063, 'master_rental_id' => 383, 'product_category_id' => self::CAT_DISPENSER, 'bom_rental_qty' => 1],
            ['id' => 1070, 'master_rental_id' => 390, 'product_category_id' => self::CAT_REFILL, 'bom_rental_qty' => 1],
        ]);

        DB::table('job_advices')->insert(['id' => 27000, 'customer_id' => 15230]);
        DB::table('job_advice_rooms')->insert([
            ['id' => self::ROOM_R1, 'job_advice_id' => 27000, 'room_name' => 'Ruang-1', 'rental_product_id' => 379, 'quantity' => 1],
            ['id' => self::ROOM_UO, 'job_advice_id' => 27000, 'room_name' => 'Ruang-3', 'rental_product_id' => 382, 'quantity' => 1],
            ['id' => self::ROOM_HIK, 'job_advice_id' => 27000, 'room_name' => 'Ruang-3', 'rental_product_id' => 383, 'quantity' => 1],
        ]);
        DB::table('job_schedules')->insert([
            ['id' => self::JOB_IF, 'job_number' => 'JKT-IF/26-11/0003', 'type' => 'install_free', 'job_advice_id' => 27000],
            ['id' => self::JOB_CSR, 'job_number' => 'JKT-CSR/26-11/0009', 'type' => 'service_routine', 'job_advice_id' => 27000],
        ]);

        // Material Assign -> Material Issue -> Inventory Issuing, urutan baris persis seperti prod.
        DB::table('job_assign_schedules')->insert(['id' => 263, 'job_schedule_id' => self::JOB_IF]);
        DB::table('material_issues')->insert(['id' => 140, 'issue_number' => 'JKT-MI/26-11/0003']);
        DB::table('job_assign_material_issues')->insert(['job_assign_schedule_id' => 263, 'material_issue_id' => 140]);
        DB::table('material_issue_items')->insert([
            ['id' => 359, 'material_issue_id' => 140, 'job_assign_schedule_id' => 263, 'product_id' => 622, 'room_name' => 'Ruang-1', 'quantity' => 1, 'notes' => 'Room: Ruang-1, Rental: TestUO1b1x, RentalDetailID: 1060'],
            ['id' => 360, 'material_issue_id' => 140, 'job_assign_schedule_id' => 263, 'product_id' => 622, 'room_name' => 'Ruang-3', 'quantity' => 1, 'notes' => 'Room: Ruang-3, Rental: TestUO1b3x, RentalDetailID: 1061'],
            ['id' => 361, 'material_issue_id' => 140, 'job_assign_schedule_id' => 263, 'product_id' => 747, 'room_name' => 'Ruang-3', 'quantity' => 1, 'notes' => 'Room: Ruang-3, Rental: TestHIKDisp, RentalDetailID: 1062'],
            ['id' => 362, 'material_issue_id' => 140, 'job_assign_schedule_id' => 263, 'product_id' => 622, 'room_name' => 'Ruang-3', 'quantity' => 1, 'notes' => 'Room: Ruang-3, Rental: TestHIKDisp, RentalDetailID: 1063'],
        ]);
        DB::table('inventory_issuings')->insert(['id' => 90, 'reference_no' => 'JKT-MI/26-11/0003', 'status' => 'sent']);
        DB::table('inventory_issuing_items')->insert([
            ['id' => 227, 'inventory_issuing_id' => 90, 'job_assign_schedule_id' => 263, 'room_name' => 'Ruang-1', 'product_id' => 622, 'serial_number_id' => 5],
            ['id' => 228, 'inventory_issuing_id' => 90, 'job_assign_schedule_id' => 263, 'room_name' => 'Ruang-3', 'product_id' => 622, 'serial_number_id' => 1],
            ['id' => 229, 'inventory_issuing_id' => 90, 'job_assign_schedule_id' => 263, 'room_name' => 'Ruang-3', 'product_id' => 747, 'serial_number_id' => 2],
            // Batch/multi-SN: SN hanya tercatat di tabel tautan, kolom serial_number_id kosong.
            ['id' => 230, 'inventory_issuing_id' => 90, 'job_assign_schedule_id' => 263, 'room_name' => 'Ruang-3', 'product_id' => 622, 'serial_number_id' => null],
        ]);
        DB::table('inventory_issuing_item_serials')->insert([
            ['inventory_issuing_item_id' => 227, 'serial_number_id' => 5],
            ['inventory_issuing_item_id' => 228, 'serial_number_id' => 1],
            ['inventory_issuing_item_id' => 229, 'serial_number_id' => 2],
            ['inventory_issuing_item_id' => 230, 'serial_number_id' => 3],
        ]);
    }

    private function mismatch(int $jobId, int $adviceRoomId, string $sn): ?string
    {
        $method = new \ReflectionMethod(JobController::class, 'serialBelongsToSiblingRentalMessage');
        $method->setAccessible(true);

        return $method->invoke(app(JobController::class), JobSchedule::find($jobId), $adviceRoomId, $sn);
    }

    public function test_dispenser_issued_for_the_other_rental_in_the_same_room_is_rejected(): void
    {
        $message = $this->mismatch(self::JOB_IF, self::ROOM_HIK, 'TESTDSP87400A-1');
        $this->assertNotNull($message, 'SN TestUO1b3x tidak boleh masuk kartu TestHIKDisp walau sama-sama dispenser.');
        $this->assertStringContainsString('diterbitkan untuk rental TestUO1b3x, bukan TestHIKDisp', $message);
        $this->assertStringContainsString('Ruang-3 - TestUO1b3x', $message);

        $message = $this->mismatch(self::JOB_IF, self::ROOM_UO, 'TESTDSP87400A-3');
        $this->assertNotNull($message, 'SN TestHIKDisp (tautan batch) tidak boleh masuk kartu TestUO1b3x.');
        $this->assertStringContainsString('diterbitkan untuk rental TestHIKDisp, bukan TestUO1b3x', $message);
    }

    public function test_serial_issued_for_the_cards_own_rental_is_accepted(): void
    {
        $this->assertNull($this->mismatch(self::JOB_IF, self::ROOM_UO, 'TESTDSP87400A-1'));
        $this->assertNull($this->mismatch(self::JOB_IF, self::ROOM_HIK, 'TESTDSP87400A-3'));
        $this->assertNull($this->mismatch(self::JOB_IF, self::ROOM_HIK, 'TESTHIKVKT-2'));
        // SN dari ruangan lain tidak diputuskan di sini - itu urusan penjagaan room.
        $this->assertNull($this->mismatch(self::JOB_IF, self::ROOM_HIK, 'TESTDSP87400A-5'));
        $this->assertNull($this->mismatch(self::JOB_IF, self::ROOM_HIK, 'SN-TIDAK-DIKENAL'));
    }

    public function test_unknown_issuing_order_does_not_reject(): void
    {
        // Baris WI dispenser Ruang-3 tinggal satu (mis. diubah manual) -> urutan MI/WI tak lagi
        // bisa dipasangkan, jadi rental SN tidak dipastikan dan tidak ada SN sah yang tertolak.
        DB::table('inventory_issuing_items')->where('id', 230)->delete();

        $this->assertNull($this->mismatch(self::JOB_IF, self::ROOM_HIK, 'TESTDSP87400A-1'));
    }

    public function test_serial_of_a_refill_only_rental_packaged_on_the_card_is_not_rejected(): void
    {
        DB::table('job_advice_rooms')->insert(['id' => 78127, 'job_advice_id' => 27000, 'room_name' => 'Ruang-3', 'rental_product_id' => 390, 'quantity' => 1]);
        DB::table('material_issue_items')->insert(['id' => 363, 'material_issue_id' => 140, 'job_assign_schedule_id' => 263, 'product_id' => 900, 'room_name' => 'Ruang-3', 'quantity' => 1, 'notes' => 'Room: Ruang-3, Rental: TestRefill, RentalDetailID: 1070']);
        DB::table('inventory_issuing_items')->insert(['id' => 231, 'inventory_issuing_id' => 90, 'job_assign_schedule_id' => 263, 'room_name' => 'Ruang-3', 'product_id' => 900, 'serial_number_id' => 9]);

        $this->assertNull($this->mismatch(self::JOB_IF, self::ROOM_UO, 'REF-1'));
    }

    public function test_service_job_rejects_the_unit_on_wall_of_the_other_rental(): void
    {
        DB::table('unit_on_walls')->insert([
            ['customer_id' => 15230, 'rental_id' => 382, 'serial_number_id' => 1, 'serial_number' => 'TESTDSP87400A-1', 'room_name' => 'Ruang-3', 'status' => 'active'],
            ['customer_id' => 15230, 'rental_id' => 379, 'serial_number_id' => 5, 'serial_number' => 'TESTDSP87400A-5', 'room_name' => 'Ruang-1', 'status' => 'active'],
        ]);

        $message = $this->mismatch(self::JOB_CSR, self::ROOM_HIK, 'TESTDSP87400A-1');
        $this->assertNotNull($message);
        $this->assertStringContainsString('terpasang untuk rental TestUO1b3x, bukan TestHIKDisp', $message);

        $this->assertNull($this->mismatch(self::JOB_CSR, self::ROOM_UO, 'TESTDSP87400A-1'));
        // Rental pemilik unit ada di ruangan lain -> bukan kartu saudara, penjagaan room yang menangani.
        $this->assertNull($this->mismatch(self::JOB_CSR, self::ROOM_HIK, 'TESTDSP87400A-5'));
    }
}
