<?php

namespace Tests\Feature;

use App\Models\UnitOnWall;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA 26 Nov 2026 (BLP-CA/26-11/0004 Ruang-6): kartu service APK menampilkan DIS...0007 (unit trial
 * BLP-SQ/26-11/0003) di samping DIS...0009 (trial BLP-SQ/26-11/0004 yang menjadi kontrak).
 */
class UnitOnWallOtherTrialScopeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['unit_on_walls', 'job_schedules', 'contracts', 'quotations'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::create('quotations', function (Blueprint $t) {
            $t->id();
            $t->string('quotation_number')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('contracts', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('quotation_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('job_schedules', function (Blueprint $t) {
            $t->id();
            $t->string('type')->nullable();
            $t->string('quotation_number')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('unit_on_walls', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('contract_id')->nullable();
            $t->unsignedBigInteger('install_job_schedule_id')->nullable();
            $t->unsignedBigInteger('room_id')->nullable();
            $t->string('status')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        DB::table('quotations')->insert(['id' => 29185, 'quotation_number' => 'BLP-SQ/26-11/0004']);
        DB::table('contracts')->insert(['id' => 21134, 'quotation_id' => 29185]);
        DB::table('job_schedules')->insert([
            ['id' => 1, 'type' => 'install_free', 'quotation_number' => 'BLP-SQ/26-11/0003'],
            ['id' => 2, 'type' => 'install_free', 'quotation_number' => 'BLP-SQ/26-11/0004'],
            ['id' => 3, 'type' => 'install', 'quotation_number' => 'LAMA-SQ'],
        ]);
        DB::table('unit_on_walls')->insert([
            ['id' => 44, 'contract_id' => null, 'install_job_schedule_id' => 1, 'room_id' => 6, 'status' => 'active'], // trial SQ lain
            ['id' => 51, 'contract_id' => null, 'install_job_schedule_id' => 2, 'room_id' => 6, 'status' => 'active'], // trial SQ kontrak
            ['id' => 60, 'contract_id' => null, 'install_job_schedule_id' => null, 'room_id' => 6, 'status' => 'active'], // data lama
            ['id' => 61, 'contract_id' => null, 'install_job_schedule_id' => 3, 'room_id' => 6, 'status' => 'active'], // bukan trial
        ]);
    }

    public function test_fail_open_scope_drops_only_trial_units_of_another_quotation(): void
    {
        $ids = UnitOnWall::where('room_id', 6)->scopedToContracts([21134])->orderBy('id')->pluck('id')->all();

        $this->assertSame([51, 60, 61], $ids);
    }

    public function test_units_carrying_the_contract_still_win(): void
    {
        DB::table('unit_on_walls')->where('id', 51)->update(['contract_id' => 21134]);

        $ids = UnitOnWall::where('room_id', 6)->scopedToContracts([21134])->pluck('id')->all();

        $this->assertSame([51], $ids);
    }

    public function test_per_unit_check_matches_the_scope(): void
    {
        $this->assertTrue(UnitOnWall::findOrFail(44)->belongsToOtherQuotationTrial(['BLP-SQ/26-11/0004']));
        $this->assertFalse(UnitOnWall::findOrFail(51)->belongsToOtherQuotationTrial(['BLP-SQ/26-11/0004']));
        $this->assertFalse(UnitOnWall::findOrFail(60)->belongsToOtherQuotationTrial(['BLP-SQ/26-11/0004']));
        $this->assertFalse(UnitOnWall::findOrFail(44)->belongsToOtherQuotationTrial([]));
    }
}
