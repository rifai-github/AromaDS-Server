<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Klien 25 Nov 2026: Frequency komponen yang di Excel 0x tampil 1x di Master Rental.
 */
class RestoreInstallOnlyRentalFrequencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('master_rentals', function (Blueprint $t) {
            $t->id();
            $t->string('rental_code');
            $t->string('rental_name')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('product_categories', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->boolean('is_unit')->default(false);
        });
        Schema::create('rental_components', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('master_rental_id');
            $t->string('component_name')->nullable();
            $t->string('description')->nullable();
            $t->integer('replacement_frequency_months')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('rental_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('master_rental_id');
            $t->unsignedBigInteger('product_category_id')->nullable();
            $t->integer('service_frequency_multiplier')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        DB::table('master_rentals')->insert([['id' => 1, 'rental_code' => 'A103-12-10'], ['id' => 2, 'rental_code' => 'VG880']]);
        DB::table('product_categories')->insert([['id' => 2, 'name' => 'Difusser', 'is_unit' => true], ['id' => 8, 'name' => 'Fragrance', 'is_unit' => false], ['id' => 40, 'name' => 'Standing', 'is_unit' => false]]);
        // id => [rental, component months (Excel xFS), detail multiplier now, category]
        $rows = [
            1 => [1, 0, 1, 2],   // Difusser: Excel 0x, seed wrote 1x -> fix
            2 => [1, 1, 1, 8],   // Fragrance 1x -> keep
            3 => [1, 0, 1, 40],  // Standing (non-unit) Excel 0x -> fix
            4 => [2, 12, 12, 8], // 12x -> keep
            5 => [2, 0, 3, 2],   // edited by hand to 3x -> keep
            6 => [2, 0, 0, 2],   // already 0x -> nothing to do
        ];
        foreach ($rows as $id => [$rental, $months, $mult, $cat]) {
            DB::table('rental_components')->insert(['id' => $id, 'master_rental_id' => $rental, 'component_name' => 'X', 'replacement_frequency_months' => $months]);
            DB::table('rental_details')->insert(['id' => $id, 'master_rental_id' => $rental, 'product_category_id' => $cat, 'service_frequency_multiplier' => $mult]);
        }
        // Detail without a paired component (rental created later) is left alone.
        DB::table('rental_details')->insert(['id' => 7, 'master_rental_id' => 2, 'product_category_id' => 2, 'service_frequency_multiplier' => 1]);
    }

    protected function tearDown(): void
    {
        foreach (['rental_details', 'rental_components', 'product_categories', 'master_rentals'] as $table) {
            Schema::dropIfExists($table);
        }
        parent::tearDown();
    }

    private function multipliers(): array
    {
        return DB::table('rental_details')->orderBy('id')->pluck('service_frequency_multiplier', 'id')->map(fn ($v) => (int) $v)->all();
    }

    public function test_dry_run_reports_without_writing(): void
    {
        $this->artisan('master-rentals:restore-install-only-frequency')
            ->expectsOutputToContain('DRY-RUN: 2 baris BOM di 1 rental')
            ->assertSuccessful();

        $this->assertSame([1 => 1, 2 => 1, 3 => 1, 4 => 12, 5 => 3, 6 => 0, 7 => 1], $this->multipliers());
    }

    public function test_apply_restores_only_excel_zero_rows_still_at_one(): void
    {
        $this->artisan('master-rentals:restore-install-only-frequency --apply')->assertSuccessful();

        $this->assertSame([1 => 0, 2 => 1, 3 => 0, 4 => 12, 5 => 3, 6 => 0, 7 => 1], $this->multipliers());

        // Idempotent.
        $this->artisan('master-rentals:restore-install-only-frequency')
            ->expectsOutputToContain('DRY-RUN: 0 baris BOM')
            ->assertSuccessful();
    }

    public function test_rental_filter(): void
    {
        $this->artisan('master-rentals:restore-install-only-frequency --apply --rental=VG880')->assertSuccessful();

        $this->assertSame(1, $this->multipliers()[1], 'rental lain tidak tersentuh');
    }
}
