<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA 23 Nov 2026: catalyst:backfill-building-city melewati ~1000 gedung karena import Catalyst
 * memetakan "Bandung" ke KABUPATEN BANDUNG padahal kecamatannya (Coblong) milik KOTA BANDUNG.
 * Bila kecamatan hanya ada di kota kembar (nama dasar sama), city & district dikoreksi bersama.
 */
class BackfillBuildingCityTwinCityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['buildings', 'districts', 'cities', 'provinces'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('provinces', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('cities', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('province_id')->nullable();
            $t->string('name');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('districts', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('city_id');
            $t->string('name');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('buildings', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('nama_gedung')->nullable();
            $t->text('notes')->nullable();
            $t->text('description')->nullable();
            $t->unsignedBigInteger('city_id')->nullable();
            $t->unsignedBigInteger('province_id')->nullable();
            $t->unsignedBigInteger('district_id')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
        });

        DB::table('provinces')->insert(['id' => 12, 'name' => 'JAWA BARAT']);
        DB::table('cities')->insert([
            ['id' => 166, 'province_id' => 12, 'name' => 'KABUPATEN BANDUNG'],
            ['id' => 597, 'province_id' => 12, 'name' => 'KOTA BANDUNG'],
        ]);
        DB::table('districts')->insert([
            ['id' => 1, 'city_id' => 597, 'name' => 'COBLONG'],
            ['id' => 2, 'city_id' => 166, 'name' => 'BALEENDAH'],
        ]);
        DB::table('buildings')->insert([
            // Kecamatan milik KOTA BANDUNG, kota hasil import KABUPATEN -> dikoreksi.
            ['id' => 1, 'name' => 'Spa Aston Cihampelas', 'city_id' => 166, 'description' => "AreaServiceName: Coblong"],
            // Kecamatan memang di kota hasil import -> kota tetap.
            ['id' => 2, 'name' => 'Gedung Baleendah', 'city_id' => 166, 'description' => "AreaServiceName: Baleendah"],
            // Tak ada di kota mana pun -> SKIP.
            ['id' => 3, 'name' => 'Gedung Entah', 'city_id' => 166, 'description' => "AreaServiceName: Antah Berantah"],
        ]);
    }

    protected function tearDown(): void
    {
        foreach (['buildings', 'districts', 'cities', 'provinces'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_dry_run_plans_twin_city_correction_without_writing(): void
    {
        $this->artisan('catalyst:backfill-building-city')
            ->expectsOutputToContain('kota dikoreksi ke KOTA BANDUNG')
            ->assertSuccessful();

        $this->assertSame(166, (int) DB::table('buildings')->where('id', 1)->value('city_id'));
        $this->assertNull(DB::table('buildings')->where('id', 1)->value('district_id'));
    }

    public function test_apply_moves_building_to_the_city_that_owns_the_district(): void
    {
        $this->artisan('catalyst:backfill-building-city', ['--apply' => true])->assertSuccessful();

        $this->assertSame(
            ['city_id' => 597, 'province_id' => 12, 'district_id' => 1],
            array_map('intval', (array) DB::table('buildings')->where('id', 1)->first(['city_id', 'province_id', 'district_id']))
        );
        $this->assertSame([166, 2], [
            (int) DB::table('buildings')->where('id', 2)->value('city_id'),
            (int) DB::table('buildings')->where('id', 2)->value('district_id'),
        ]);
        $this->assertNull(DB::table('buildings')->where('id', 3)->value('district_id'));
    }
}
