<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Data prod 27 Nov 2026 (Customer Testing Indo): ADS-CSR/26-11/0001 dan BLP-CSR/26-11/0005
 * (kontrak BLP-CA/26-11/0006) mencatat service ke unit kontrak BLP-CA/26-11/0004 dan unit trial
 * SQ BLP-SQ/26-11/0003.
 */
class RepairForeignUnitServiceHistoryCommandTest extends TestCase
{
    private const TABLES = ['unit_on_wall_histories', 'unit_on_walls', 'job_schedules', 'job_advices', 'contracts', 'quotations'];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('quotations', function (Blueprint $t) {
            $t->id();
            $t->string('quotation_number')->nullable();
            $t->foreignId('existing_contract_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('contracts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('quotation_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('job_advices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('contract_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('job_schedules', function (Blueprint $t) {
            $t->id();
            $t->string('job_number')->nullable();
            $t->string('type')->nullable();
            $t->string('quotation_number')->nullable();
            $t->foreignId('job_advice_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('unit_on_walls', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')->nullable();
            $t->foreignId('contract_id')->nullable();
            $t->foreignId('install_job_schedule_id')->nullable();
            $t->string('serial_number')->nullable();
            $t->date('last_service_date')->nullable();
            $t->string('status')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('unit_on_wall_histories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('unit_on_wall_id')->nullable();
            $t->string('action')->nullable();
            $t->date('action_date')->nullable();
            $t->foreignId('job_schedule_id')->nullable();
            $t->string('job_schedule_number')->nullable();
            $t->timestamps();
        });

        DB::table('quotations')->insert([
            ['id' => 29185, 'quotation_number' => 'BLP-SQ/26-11/0004'],
            ['id' => 30577, 'quotation_number' => 'BLP-SQ/26-11/0133'],
        ]);
        DB::table('contracts')->insert([
            ['id' => 21134, 'quotation_id' => 29185],
            ['id' => 21792, 'quotation_id' => 30577],
        ]);
        DB::table('job_advices')->insert([
            ['id' => 26510, 'contract_id' => 21134],
            ['id' => 27679, 'contract_id' => 21792],
        ]);
        DB::table('job_schedules')->insert([
            ['id' => 59849, 'job_number' => 'JKT-IF/26-11/0003', 'type' => 'install_free', 'quotation_number' => 'BLP-SQ/26-11/0003', 'job_advice_id' => null],
            ['id' => 59864, 'job_number' => 'JKT-CSR/26-11/0005', 'type' => 'service_first', 'quotation_number' => 'BLP-SQ/26-11/0004', 'job_advice_id' => 26510],
            ['id' => 59990, 'job_number' => 'ADS-IF/26-11/0003', 'type' => 'install_free', 'quotation_number' => 'BLP-SQ/26-11/0133', 'job_advice_id' => null],
            ['id' => 59992, 'job_number' => 'ADS-CSR/26-11/0001', 'type' => 'service_first', 'quotation_number' => 'BLP-SQ/26-11/0133', 'job_advice_id' => 27679],
        ]);
        DB::table('unit_on_walls')->insert([
            ['id' => 44, 'customer_id' => 15230, 'contract_id' => null, 'install_job_schedule_id' => 59849, 'serial_number' => 'DIS005S2611230007', 'last_service_date' => '2026-11-27', 'status' => 'active'],
            ['id' => 52, 'customer_id' => 15230, 'contract_id' => 21134, 'install_job_schedule_id' => null, 'serial_number' => 'DIS005S2611230016', 'last_service_date' => '2026-11-27', 'status' => 'active'],
            ['id' => 63, 'customer_id' => 15230, 'contract_id' => null, 'install_job_schedule_id' => 59990, 'serial_number' => 'DIS005S2611230015', 'last_service_date' => '2026-11-27', 'status' => 'active'],
            ['id' => 70, 'customer_id' => 15230, 'contract_id' => null, 'install_job_schedule_id' => null, 'serial_number' => 'LEGACY-1', 'last_service_date' => '2026-11-27', 'status' => 'active'],
        ]);
        DB::table('unit_on_wall_histories')->insert([
            ['id' => 28, 'unit_on_wall_id' => 52, 'action' => 'service', 'action_date' => '2026-11-26', 'job_schedule_id' => 59864, 'job_schedule_number' => 'JKT-CSR/26-11/0005'],
            ['id' => 29, 'unit_on_wall_id' => 52, 'action' => 'service', 'action_date' => '2026-11-27', 'job_schedule_id' => 59992, 'job_schedule_number' => 'ADS-CSR/26-11/0001'],
            ['id' => 30, 'unit_on_wall_id' => 63, 'action' => 'service', 'action_date' => '2026-11-27', 'job_schedule_id' => 59992, 'job_schedule_number' => 'ADS-CSR/26-11/0001'],
            ['id' => 31, 'unit_on_wall_id' => 44, 'action' => 'service', 'action_date' => '2026-11-27', 'job_schedule_id' => 59992, 'job_schedule_number' => 'ADS-CSR/26-11/0001'],
            ['id' => 34, 'unit_on_wall_id' => 70, 'action' => 'service', 'action_date' => '2026-11-27', 'job_schedule_id' => 59992, 'job_schedule_number' => 'ADS-CSR/26-11/0001'],
            ['id' => 23, 'unit_on_wall_id' => 63, 'action' => 'install', 'action_date' => '2026-11-27', 'job_schedule_id' => 59990, 'job_schedule_number' => 'ADS-IF/26-11/0003'],
        ]);
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
        $this->artisan('unit-on-wall:repair-foreign-service-history')
            ->expectsOutputToContain('2 baris riwayat salah, 2 unit perlu last_service_date dihitung ulang.')
            ->assertSuccessful();

        $this->assertSame(6, DB::table('unit_on_wall_histories')->count());
        $this->assertSame('2026-11-27', substr((string) DB::table('unit_on_walls')->where('id', 52)->value('last_service_date'), 0, 10));
    }

    public function test_apply_removes_only_history_of_units_owned_elsewhere(): void
    {
        $this->artisan('unit-on-wall:repair-foreign-service-history', ['--apply' => true])->assertSuccessful();

        $this->assertSame([23, 28, 30, 34], DB::table('unit_on_wall_histories')->orderBy('id')->pluck('id')->all());
        // Unit kontrak lain kembali ke service kontraknya sendiri; unit trial SQ lain tak pernah di-service.
        $this->assertSame('2026-11-26', substr((string) DB::table('unit_on_walls')->where('id', 52)->value('last_service_date'), 0, 10));
        $this->assertNull(DB::table('unit_on_walls')->where('id', 44)->value('last_service_date'));
        // Unit kontrak job dan unit lama tanpa kontrak tidak disentuh.
        $this->assertSame('2026-11-27', substr((string) DB::table('unit_on_walls')->where('id', 63)->value('last_service_date'), 0, 10));
        $this->assertSame('2026-11-27', substr((string) DB::table('unit_on_walls')->where('id', 70)->value('last_service_date'), 0, 10));

        $this->artisan('unit-on-wall:repair-foreign-service-history', ['--apply' => true])
            ->expectsOutputToContain('Tidak ada riwayat service yang salah.')
            ->assertSuccessful();
    }

    public function test_job_filter_limits_the_scope(): void
    {
        $this->artisan('unit-on-wall:repair-foreign-service-history', ['--apply' => true, '--job' => 59864])
            ->expectsOutputToContain('Tidak ada riwayat service yang salah.')
            ->assertSuccessful();

        $this->assertSame(6, DB::table('unit_on_wall_histories')->count());
    }
}
