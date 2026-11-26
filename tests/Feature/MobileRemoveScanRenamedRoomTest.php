<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Mobile\JobController;
use App\Models\JobSchedule;
use App\Models\UnitOnWall;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA 25-26 Nov 2026, JKT-RF/26-11/0002: master room 45009 diganti nama menjadi
 * "Test Ruang 261116-2Sq" setelah SQ dibuat; ruangan JA masih bernama "Test Ruang 261116-2".
 * Scan SN di job Remove membandingkan NAMA saja, jadi setiap SN di ruangan itu ditolak
 * ("terpasang di Room: …-2Sq, bukan Room: …-2") padahal ruangannya sama.
 */
class MobileRemoveScanRenamedRoomTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['job_schedules', 'job_advices', 'job_advice_rooms', 'quotation_rooms', 'contract_rooms', 'master_rooms', 'unit_on_walls'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::create('job_schedules', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('job_advice_id')->nullable();
            $t->string('type')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('job_advices', function (Blueprint $t) {
            $t->id();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('job_advice_rooms', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('job_advice_id')->nullable();
            $t->unsignedBigInteger('room_id')->nullable();
            $t->unsignedBigInteger('contract_room_id')->nullable();
            $t->unsignedBigInteger('quotation_room_id')->nullable();
            $t->string('room_name')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        foreach (['quotation_rooms', 'contract_rooms'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('room_id')->nullable();
                $t->timestamps();
                $t->softDeletes();
            });
        }
        Schema::create('master_rooms', function (Blueprint $t) {
            $t->id();
            $t->string('room_name')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('unit_on_walls', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('room_id')->nullable();
            $t->string('room_name')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        DB::table('master_rooms')->insert([
            ['id' => 45009, 'room_name' => 'Test Ruang 261116-2Sq'],
            ['id' => 45010, 'room_name' => 'Lobby'],
        ]);
        DB::table('quotation_rooms')->insert(['id' => 77343, 'room_id' => 45009]);
        DB::table('job_advices')->insert(['id' => 1]);
        DB::table('job_advice_rooms')->insert(['job_advice_id' => 1, 'quotation_room_id' => 77343, 'room_name' => 'Test Ruang 261116-2']);
        DB::table('job_schedules')->insert(['id' => 429, 'job_advice_id' => 1, 'type' => 'remove_free']);
    }

    private function otherRoom(int $unitRoomId, string $unitRoomName, string $selected): bool
    {
        $unitId = DB::table('unit_on_walls')->insertGetId(['room_id' => $unitRoomId, 'room_name' => $unitRoomName]);
        $method = new \ReflectionMethod(JobController::class, 'unitOnWallIsInOtherRoom');
        $method->setAccessible(true);

        return $method->invoke(app(JobController::class), JobSchedule::find(429), UnitOnWall::find($unitId), $selected);
    }

    public function test_renamed_master_room_is_still_the_same_room(): void
    {
        $this->assertFalse($this->otherRoom(45009, 'Test Ruang 261116-2Sq', 'Test Ruang 261116-2'));
    }

    public function test_a_unit_in_a_genuinely_different_room_is_still_rejected(): void
    {
        $this->assertTrue($this->otherRoom(45010, 'Lobby', 'Test Ruang 261116-2'));
    }

    public function test_name_fallback_when_the_room_is_not_on_the_job_advice(): void
    {
        $this->assertFalse($this->otherRoom(45010, 'Lobby', 'lobby'));
        $this->assertTrue($this->otherRoom(45010, 'Lobby', 'Pantry'));
    }
}
