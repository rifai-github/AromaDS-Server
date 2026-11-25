<?php

namespace Tests\Feature;

use App\Services\Imports\Catalyst\CatalystBillingGroupSplitRepair;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * UAT 24 Nov 2026: SMD-AG/26-04/0003 punya 3 gedung dengan billing group masing-masing di
 * Catalyst (ADS-BIL-09/1146, /2458, /2459), di ERP cuma 1146 untuk ketiga gedung.
 */
class CatalystBillingGroupSplitRepairTest extends TestCase
{
    private const CONTRACT = 'SMD-AG/26-04/0003';

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('source_import_maps', function (Blueprint $t) {
            $t->id();
            $t->string('source_system');
            $t->string('source_table');
            $t->string('source_key');
            $t->string('target_table');
            $t->unsignedBigInteger('target_id');
            $t->timestamp('last_imported_at')->nullable();
            $t->timestamps();
        });
        Schema::create('billing_groups', function (Blueprint $t) {
            $t->id();
            $t->string('billing_group_name')->nullable();
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->unsignedBigInteger('contract_id')->nullable();
            $t->string('npwp')->nullable();
            $t->string('pic_name')->nullable();
            $t->string('pic_phone')->nullable();
            $t->string('pic_email')->nullable();
            $t->string('pic_address')->nullable();
            $t->decimal('billing_amount', 15, 2)->nullable();
            $t->boolean('is_active')->default(true);
            $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('contract_buildings', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('billing_id');
            $t->unsignedBigInteger('building_id');
        });
        Schema::create('billing_group_buildings', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('billing_group_id');
            $t->unsignedBigInteger('building_id');
            $t->boolean('is_active')->default(true);
            $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
        });
        Schema::create('contract_rooms', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('contract_id');
            $t->unsignedBigInteger('billing_group_id')->nullable();
            $t->timestamps();
        });
        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('contract_id')->nullable();
            $t->string('invoice_status')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        $map = fn ($table, $key, $target, $id) => DB::table('source_import_maps')->insert([
            'source_system' => 'catalyst', 'source_table' => $table, 'source_key' => $key, 'target_table' => $target, 'target_id' => $id,
        ]);
        $map('MKTContractHd', self::CONTRACT, 'contracts', 20337);
        $map('MKTContractDt_billing', self::CONTRACT.'||ADS-BIL-09/1146', 'billing_groups', 1);
        foreach (['ADS-SMDG0001' => 19567, 'ADS-SMDG0002' => 19568, 'ADS-SMDR0001' => 19594] as $code => $buildingId) {
            $map('MsBuilding', $code, 'buildings', $buildingId);
        }
        $map('MKTContractDt_room', self::CONTRACT.'||ADS-SMDG0001||1||R1||Lobby', 'contract_rooms', 501);
        $map('MKTContractDt_room', self::CONTRACT.'||ADS-SMDG0002||1||R2||Hall', 'contract_rooms', 502);
        $map('MKTContractDt_room', self::CONTRACT.'||ADS-SMDR0001||1||R3||Ruang Tamu', 'contract_rooms', 503);

        DB::table('billing_groups')->insert(['id' => 1, 'billing_group_name' => 'ADS-BIL-09/1146', 'customer_id' => 9, 'contract_id' => 20337, 'npwp' => '0011', 'pic_name' => 'Lama']);
        foreach ([19567, 19568, 19594] as $buildingId) {
            DB::table('contract_buildings')->insert(['billing_id' => 1, 'building_id' => $buildingId]);
            DB::table('billing_group_buildings')->insert(['billing_group_id' => 1, 'building_id' => $buildingId]);
        }
        foreach ([501, 502, 503] as $roomId) {
            DB::table('contract_rooms')->insert(['id' => $roomId, 'contract_id' => 20337, 'billing_group_id' => 1]);
        }
        DB::table('invoices')->insert(['contract_id' => 20337, 'invoice_status' => 'approved']);
    }

    protected function tearDown(): void
    {
        foreach (['invoices', 'contract_rooms', 'billing_group_buildings', 'contract_buildings', 'billing_groups', 'source_import_maps'] as $table) {
            Schema::dropIfExists($table);
        }
        parent::tearDown();
    }

    private function sourceRows()
    {
        return collect([
            (object) ['TransNmbr' => self::CONTRACT, 'BillingGroup' => 'ADS-BIL-09/1146', 'Building' => 'ADS-SMDG0001', 'FloorCode' => '1', 'RoomCode' => 'R1', 'Room' => 'Lobby', 'AmountForex' => 100],
            (object) ['TransNmbr' => self::CONTRACT, 'BillingGroup' => 'ADS-BIL-09/2458', 'Building' => 'ADS-SMDG0002', 'FloorCode' => '1', 'RoomCode' => 'R2', 'Room' => 'Hall', 'AmountForex' => 200],
            (object) ['TransNmbr' => self::CONTRACT, 'BillingGroup' => 'ADS-BIL-09/2459', 'Building' => 'ADS-SMDR0001', 'FloorCode' => '1', 'RoomCode' => 'R3', 'Room' => 'Ruang Tamu', 'AmountForex' => 300],
            // Contract that is already correct must not show up.
            (object) ['TransNmbr' => 'OTHER/1', 'BillingGroup' => 'X', 'Building' => 'B', 'FloorCode' => '', 'RoomCode' => '', 'Room' => '', 'AmountForex' => 1],
        ]);
    }

    private function legacy()
    {
        return collect(['ADS-BIL-09/2459' => (object) ['Attn' => 'Bu Sekda', 'Telp' => '08115581102', 'EmailBilling' => '-', 'Address' => '3. JALAN GAJAH MADA NO.2']]);
    }

    public function test_dry_run_plans_the_two_missing_groups_and_writes_nothing(): void
    {
        $plans = app(CatalystBillingGroupSplitRepair::class)->plan($this->sourceRows(), $this->legacy());

        $this->assertCount(1, $plans);
        $this->assertSame(['ADS-BIL-09/2458', 'ADS-BIL-09/2459'], array_column($plans[0]['missing'], 'code'));
        $this->assertSame(['ADS-SMDG0002' => 19568], $plans[0]['missing'][0]['buildings']);
        $this->assertSame([502], $plans[0]['missing'][0]['contract_room_ids']);
        $this->assertSame(1, $plans[0]['issued_invoices']);
        $this->assertSame(1, DB::table('billing_groups')->count());
    }

    public function test_apply_splits_buildings_and_rooms_and_is_idempotent(): void
    {
        $repair = app(CatalystBillingGroupSplitRepair::class);
        $plans = $repair->plan($this->sourceRows(), $this->legacy());
        $done = $repair->apply($plans[0], 7);

        $this->assertSame(['groups' => 2, 'building_links' => 2, 'rooms' => 2], $done);

        $g2459 = DB::table('billing_groups')->where('billing_group_name', 'ADS-BIL-09/2459')->first();
        $this->assertSame(20337, (int) $g2459->contract_id);
        $this->assertSame(9, (int) $g2459->customer_id, 'data billing group disalin dari yang sudah ada');
        $this->assertSame('0011', $g2459->npwp);
        $this->assertSame('Bu Sekda', $g2459->pic_name, 'PIC dari MsBillingGroup');
        $this->assertNull($g2459->pic_email, 'Email "-" dibuang');
        $this->assertEquals(300, $g2459->billing_amount);

        $this->assertSame((int) $g2459->id, (int) DB::table('contract_buildings')->where('building_id', 19594)->value('billing_id'));
        $this->assertSame((int) $g2459->id, (int) DB::table('billing_group_buildings')->where('building_id', 19594)->value('billing_group_id'));
        $this->assertSame(1, (int) DB::table('contract_buildings')->where('building_id', 19567)->value('billing_id'), 'gedung 1146 tetap');
        $this->assertSame(3, DB::table('contract_buildings')->count(), 'tidak ada link dobel');
        $this->assertSame((int) $g2459->id, (int) DB::table('contract_rooms')->where('id', 503)->value('billing_group_id'));
        $this->assertSame(1, (int) DB::table('contract_rooms')->where('id', 501)->value('billing_group_id'));
        $this->assertTrue(DB::table('source_import_maps')->where('source_table', 'MKTContractDt_billing')->where('source_key', self::CONTRACT.'||ADS-BIL-09/2459')->exists());

        // Second run: nothing left to do.
        $this->assertSame([], $repair->plan($this->sourceRows(), $this->legacy()));
    }

    public function test_contract_filter_limits_the_plan(): void
    {
        $this->assertSame([], app(CatalystBillingGroupSplitRepair::class)->plan($this->sourceRows(), $this->legacy(), 'ADS-AG/26-04/0098'));
        $this->assertCount(1, app(CatalystBillingGroupSplitRepair::class)->plan($this->sourceRows(), $this->legacy(), self::CONTRACT));
    }
}
