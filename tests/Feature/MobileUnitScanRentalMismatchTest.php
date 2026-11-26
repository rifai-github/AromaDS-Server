<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Mobile\JobController;
use App\Models\JobSchedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA 26 Nov 2026, JKT-IF/26-11/0003 Ruang-3: SN HIKV diterima untuk rental TestUO1b3x (yang
 * BOM-nya hanya dispenser), dan SN dispenser diterima di slot unit ke-2 TestUO1b3x.
 */
class MobileUnitScanRentalMismatchTest extends TestCase
{
    private const CAT_DISPENSER = 11;
    private const CAT_THERMAL = 49;

    protected function setUp(): void
    {
        parent::setUp();

        $tables = ['job_schedules', 'job_advice_rooms', 'master_rentals', 'rental_details', 'product_categories',
            'product_types', 'master_products', 'serial_numbers', 'job_schedule_units'];
        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('job_schedules', function (Blueprint $t) {
            $t->id();
            $t->string('job_number')->nullable();
            $t->string('type')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('job_advice_rooms', function (Blueprint $t) {
            $t->id();
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

        DB::table('product_categories')->insert([
            ['id' => self::CAT_DISPENSER, 'name' => 'Dispenser', 'is_unit' => true],
            ['id' => self::CAT_THERMAL, 'name' => 'Thermal', 'is_unit' => true],
            ['id' => 60, 'name' => 'Refill', 'is_unit' => false],
        ]);
        DB::table('master_products')->insert([
            ['id' => 622, 'name' => 'PURE Dispenser 87400A', 'product_category_id' => self::CAT_DISPENSER],
            ['id' => 747, 'name' => 'HIK VISION K1TA70MI-T', 'product_category_id' => self::CAT_THERMAL],
            ['id' => 900, 'name' => 'Refill Lemon', 'product_category_id' => 60],
        ]);
        DB::table('serial_numbers')->insert([
            ['serial_number' => 'TESTDSP87400A-1', 'master_product_id' => 622],
            ['serial_number' => 'TESTDSP87400A-3', 'master_product_id' => 622],
            ['serial_number' => 'TESTHIKVKT-2', 'master_product_id' => 747],
            ['serial_number' => 'REF-1', 'master_product_id' => 900],
        ]);
        DB::table('master_rentals')->insert([
            ['id' => 382, 'rental_name' => 'TestUO1b3x'],
            ['id' => 383, 'rental_name' => 'TestHIKDisp'],
        ]);
        DB::table('rental_details')->insert([
            ['master_rental_id' => 382, 'product_category_id' => self::CAT_DISPENSER, 'bom_rental_qty' => 1],
            ['master_rental_id' => 383, 'product_category_id' => self::CAT_THERMAL, 'bom_rental_qty' => 1],
            ['master_rental_id' => 383, 'product_category_id' => self::CAT_DISPENSER, 'bom_rental_qty' => 1],
        ]);
        DB::table('job_advice_rooms')->insert([
            ['id' => 78125, 'rental_product_id' => 382, 'quantity' => 1],
            ['id' => 78126, 'rental_product_id' => 383, 'quantity' => 1],
        ]);
        DB::table('job_schedules')->insert([
            ['id' => 59849, 'job_number' => 'JKT-IF/26-11/0003', 'type' => 'install_free'],
            ['id' => 60000, 'job_number' => 'JKT-CSR/26-11/0001', 'type' => 'service'],
        ]);
    }

    private function mismatch(int $jobId, int $adviceRoomId, string $sn): ?string
    {
        $method = new \ReflectionMethod(JobController::class, 'unitScanMismatchForRental');
        $method->setAccessible(true);

        return $method->invoke(app(JobController::class), JobSchedule::find($jobId), $adviceRoomId, $sn);
    }

    public function test_a_unit_of_another_rental_is_rejected(): void
    {
        $message = $this->mismatch(59849, 78125, 'TESTHIKVKT-2');

        $this->assertNotNull($message);
        $this->assertStringContainsString('bukan unit untuk TestUO1b3x', $message);
    }

    public function test_the_rentals_own_unit_is_accepted(): void
    {
        $this->assertNull($this->mismatch(59849, 78125, 'TESTDSP87400A-3'));
        $this->assertNull($this->mismatch(59849, 78126, 'TESTHIKVKT-2'));
        $this->assertNull($this->mismatch(59849, 78126, 'TESTDSP87400A-1'), 'Dispenser memang bagian BOM TestHIKDisp.');
    }

    public function test_a_second_unit_beyond_the_rentals_quantity_is_rejected(): void
    {
        DB::table('job_schedule_units')->insert(['job_schedule_id' => 59849, 'job_advice_room_id' => 78125, 'mac' => 'TESTDSP87400A-3']);

        $this->assertStringContainsString('sudah lengkap', (string) $this->mismatch(59849, 78125, 'TESTDSP87400A-1'));
        // Scan ulang SN yang sama (update) tetap boleh.
        $this->assertNull($this->mismatch(59849, 78125, 'TESTDSP87400A-3'));
    }

    public function test_non_install_jobs_and_non_unit_items_are_not_restricted(): void
    {
        $this->assertNull($this->mismatch(60000, 78125, 'TESTHIKVKT-2'));
        $this->assertNull($this->mismatch(59849, 78125, 'REF-1'));
        $this->assertNull($this->mismatch(59849, 78125, 'SN-TIDAK-DIKENAL'));
    }
}
