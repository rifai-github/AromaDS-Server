<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\UnitOnWall;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA 27 Nov 2026 (Customer Testing Indo, Ruang-4): trial JKT-IF/26-11/0007 memasang
 * DIS005S2611230016 yang lalu menjadi kontrak BLP-CA/26-11/0004 (unit ber-contract_id 21134).
 * SQ baru BLP-SQ/26-11/0133 memasang DIS005S2611230015 di ruangan + rental yang sama (contract_id
 * NULL) dan menjadi kontrak BLP-CA/26-11/0006. Kartu CSR ADS-CSR/26-11/0001 di APK menampilkan
 * kedua SN: scopedToContracts() fail-open karena tak ada unit ber-contract_id 21792, dan saringan
 * fail-open hanya membuang unit TANPA kontrak dari trial SQ lain.
 */
class UnitOnWallOtherContractScopeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['unit_on_walls', 'job_schedules', 'contracts', 'quotations', 'contract_switchings', 'contract_renewals'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('quotations', function (Blueprint $t) {
            $t->id();
            $t->string('quotation_number')->nullable();
            $t->unsignedBigInteger('existing_contract_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('contracts', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('quotation_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('contract_switchings', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('old_contract_id')->nullable();
            $t->unsignedBigInteger('new_contract_id')->nullable();
            $t->string('status')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('contract_renewals', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('contract_id')->nullable();
            $t->unsignedBigInteger('new_contract_id')->nullable();
            $t->string('status')->nullable();
            $t->timestamps();
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
            $t->unsignedBigInteger('rental_id')->nullable();
            $t->string('serial_number')->nullable();
            $t->string('status')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        DB::table('quotations')->insert([
            ['id' => 29185, 'quotation_number' => 'BLP-SQ/26-11/0004', 'existing_contract_id' => null],
            ['id' => 30577, 'quotation_number' => 'BLP-SQ/26-11/0133', 'existing_contract_id' => null],
        ]);
        DB::table('contracts')->insert([
            ['id' => 21134, 'quotation_id' => 29185],
            ['id' => 21792, 'quotation_id' => 30577],
        ]);
        DB::table('job_schedules')->insert([
            ['id' => 59861, 'type' => 'install_free', 'quotation_number' => 'BLP-SQ/26-11/0004'], // JKT-IF/26-11/0007
            ['id' => 59990, 'type' => 'install_free', 'quotation_number' => 'BLP-SQ/26-11/0133'], // ADS-IF/26-11/0003
        ]);
        DB::table('unit_on_walls')->insert([
            ['id' => 52, 'contract_id' => 21134, 'install_job_schedule_id' => 59861, 'room_id' => 45027, 'rental_id' => 384, 'serial_number' => 'DIS005S2611230016', 'status' => 'active'],
            ['id' => 63, 'contract_id' => null, 'install_job_schedule_id' => 59990, 'room_id' => 45027, 'rental_id' => 384, 'serial_number' => 'DIS005S2611230015', 'status' => 'active'],
        ]);
    }

    protected function tearDown(): void
    {
        foreach (['unit_on_walls', 'job_schedules', 'contracts', 'quotations', 'contract_switchings', 'contract_renewals'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    private function scopedIds(int $contractId): array
    {
        return UnitOnWall::where('room_id', 45027)
            ->where('rental_id', 384)
            ->scopedToContracts(Contract::renewalLineageIds($contractId))
            ->orderBy('id')
            ->pluck('id')
            ->all();
    }

    public function test_unit_bound_to_another_contract_no_longer_leaks_through_the_fail_open_path(): void
    {
        $this->assertSame([63], $this->scopedIds(21792));
    }

    public function test_the_other_contract_still_sees_its_own_unit(): void
    {
        $this->assertSame([52], $this->scopedIds(21134));
    }

    public function test_unit_of_a_deleted_contract_is_not_owned_by_anyone(): void
    {
        DB::table('contracts')->where('id', 21134)->update(['deleted_at' => now()]);

        $this->assertSame([52, 63], $this->scopedIds(21792));
    }

    public function test_renewal_keeps_the_units_of_its_predecessor(): void
    {
        DB::table('quotations')->where('id', 30577)->update(['existing_contract_id' => 21134]);

        $this->assertSame([52], $this->scopedIds(21792));
        $this->assertSame([21792, 21134], Contract::unitOwnershipLineageIds(21792));
    }

    public function test_completed_contract_renewal_record_counts_as_the_same_lineage(): void
    {
        DB::table('contract_renewals')->insert(['contract_id' => 21134, 'new_contract_id' => 21792, 'status' => 'completed']);

        $this->assertSame([52], $this->scopedIds(21792));
    }

    public function test_contract_switching_target_keeps_the_units_still_carrying_the_source_contract(): void
    {
        // Switching memindah ruangan ke kontrak baru; unit tetap membawa contract_id kontrak lama.
        DB::table('contract_switchings')->insert(['old_contract_id' => 21134, 'new_contract_id' => 21792, 'status' => 'completed']);

        $this->assertSame([52], $this->scopedIds(21792));
        $this->assertContains(21134, Contract::unitOwnershipLineageIds(21792));
    }

    public function test_switching_source_unit_and_a_new_unit_of_the_target_both_belong_to_the_target(): void
    {
        DB::table('contract_switchings')->insert(['old_contract_id' => 21134, 'new_contract_id' => 21792, 'status' => 'executed']);
        DB::table('unit_on_walls')->where('id', 63)->update(['contract_id' => 21792]);

        $this->assertSame([52, 63], $this->scopedIds(21792));
    }

    public function test_switching_of_a_renewed_contract_follows_the_whole_chain(): void
    {
        // 21134 <- renewal 21500 <- switching 21792: unit 52 (dipasang 21134) tetap milik 21792.
        DB::table('quotations')->insert(['id' => 30000, 'quotation_number' => 'BLP-SQ/26-11/0200', 'existing_contract_id' => 21134]);
        DB::table('contracts')->insert(['id' => 21500, 'quotation_id' => 30000]);
        DB::table('contract_switchings')->insert(['old_contract_id' => 21500, 'new_contract_id' => 21792, 'status' => 'completed']);

        $this->assertSame([21792, 21500, 21134], Contract::unitOwnershipLineageIds(21792));
        $this->assertSame([52], $this->scopedIds(21792));
    }

    public function test_unexecuted_or_deleted_switching_does_not_count(): void
    {
        DB::table('contract_switchings')->insert([
            ['old_contract_id' => 21134, 'new_contract_id' => 21792, 'status' => 'approved', 'deleted_at' => null],
            ['old_contract_id' => 21134, 'new_contract_id' => 21792, 'status' => 'completed', 'deleted_at' => now()],
        ]);

        $this->assertSame([63], $this->scopedIds(21792));
    }

    public function test_legacy_units_without_contract_still_fail_open(): void
    {
        DB::table('unit_on_walls')->insert([
            'id' => 70, 'contract_id' => null, 'install_job_schedule_id' => null, 'room_id' => 45027, 'rental_id' => 384, 'status' => 'active',
        ]);

        $this->assertSame([63, 70], $this->scopedIds(21792));
    }

    public function test_per_unit_check_matches_the_scope(): void
    {
        $lineage = Contract::unitOwnershipLineageIds(21792);

        $this->assertTrue(UnitOnWall::findOrFail(52)->isBoundToOtherContract($lineage));
        $this->assertFalse(UnitOnWall::findOrFail(63)->isBoundToOtherContract($lineage));
        $this->assertFalse(UnitOnWall::findOrFail(52)->isBoundToOtherContract(Contract::unitOwnershipLineageIds(21134)));

        DB::table('contracts')->where('id', 21134)->update(['deleted_at' => now()]);
        $this->assertFalse(UnitOnWall::findOrFail(52)->isBoundToOtherContract($lineage));
    }
}
