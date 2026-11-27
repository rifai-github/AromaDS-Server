<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobScheduleController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA 26 Nov 2026 (JKT-IF/26-11/0003, TestHIKDisp = Thermal + Dispenser, qty 1): teknisi men-scan
 * A-8 dan T-4, tetapi hanya T-4 yang tercatat di Unit On Wall karena jumlahnya dibatasi qty rental.
 * A-8 lalu tak muncul di tab Serial Numbers Remove Free.
 */
class UnitOnWallMultiUnitRentalSlotTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['rental_details', 'product_categories', 'product_types', 'master_products'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::create('product_categories', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->boolean('is_unit')->default(false);
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('product_types', function (Blueprint $t) {
            $t->id();
            $t->boolean('is_unit')->default(false);
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('master_products', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('product_category_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('rental_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('master_rental_id');
            $t->unsignedBigInteger('product_category_id')->nullable();
            $t->unsignedBigInteger('product_type_id')->nullable();
            $t->unsignedBigInteger('master_product_id')->nullable();
            $t->decimal('bom_rental_qty', 12, 2)->nullable();
            $t->decimal('quantity', 12, 2)->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        DB::table('product_categories')->insert([
            ['id' => 49, 'name' => 'Thermal', 'is_unit' => true],
            ['id' => 11, 'name' => 'Dispenser', 'is_unit' => true],
            ['id' => 70, 'name' => 'Refill', 'is_unit' => false],
        ]);
        DB::table('rental_details')->insert([
            ['master_rental_id' => 383, 'product_category_id' => 49, 'bom_rental_qty' => 1],
            ['master_rental_id' => 383, 'product_category_id' => 11, 'bom_rental_qty' => 1],
            ['master_rental_id' => 380, 'product_category_id' => 11, 'bom_rental_qty' => 1],
            ['master_rental_id' => 380, 'product_category_id' => 70, 'bom_rental_qty' => 250],
        ]);
    }

    private function slots(?int $rentalId, int $qty): int
    {
        $method = new \ReflectionMethod(JobScheduleController::class, 'unitOnWallSlotCountForJobAdviceRoom');
        $method->setAccessible(true);

        return $method->invoke(app(JobScheduleController::class), (object) ['rental_product_id' => $rentalId], $qty);
    }

    public function test_two_unit_rental_gets_two_slots_per_room_quantity(): void
    {
        $this->assertSame(2, $this->slots(383, 1));
        $this->assertSame(4, $this->slots(383, 2));
    }

    public function test_single_unit_rental_is_unchanged_and_refill_lines_do_not_count(): void
    {
        $this->assertSame(1, $this->slots(380, 1));
        $this->assertSame(2, $this->slots(380, 2));
    }

    public function test_unknown_rental_keeps_the_old_quantity(): void
    {
        $this->assertSame(3, $this->slots(999, 3));
        $this->assertSame(1, $this->slots(null, 1));
    }
}
