<?php

namespace Tests\Feature;

use App\Models\Finance\BillingGroup;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA 26 Nov 2026: Billing Group butuh nomor transaksi ADS-BIL-MM/XXXX.
 */
class BillingGroupTransactionNumberTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('billing_groups');
        Schema::create('billing_groups', function (Blueprint $t) {
            $t->id();
            $t->string('billing_group_name');
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->unsignedBigInteger('contract_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
    }

    private function migrate(): void
    {
        $migration = require database_path('migrations/2026_11_27_000201_add_transaction_number_to_billing_groups.php');
        $migration->up();
    }

    public function test_migration_backfills_only_names_in_catalyst_format(): void
    {
        DB::table('billing_groups')->insert([
            ['id' => 1, 'billing_group_name' => 'ADS-BIL-12/0141'],
            ['id' => 2, 'billing_group_name' => 'Billing Group 1'],
            ['id' => 3, 'billing_group_name' => 'ADS-BIL-09/2650 lama'],
        ]);

        $this->migrate();

        $this->assertSame('ADS-BIL-12/0141', DB::table('billing_groups')->where('id', 1)->value('transaction_number'));
        $this->assertNull(DB::table('billing_groups')->where('id', 2)->value('transaction_number'));
        $this->assertNull(DB::table('billing_groups')->where('id', 3)->value('transaction_number'));
    }

    public function test_new_erp_billing_group_gets_next_number_of_its_month(): void
    {
        $this->migrate();
        Carbon::setTestNow('2027-12-02 10:00:00');

        DB::table('billing_groups')->insert([
            ['billing_group_name' => 'ADS-BIL-12/8378', 'transaction_number' => 'ADS-BIL-12/8378', 'deleted_at' => null],
            ['billing_group_name' => 'ADS-BIL-12/0005', 'transaction_number' => 'ADS-BIL-12/0005', 'deleted_at' => now()],
            ['billing_group_name' => 'ADS-BIL-09/2650', 'transaction_number' => 'ADS-BIL-09/2650', 'deleted_at' => null],
        ]);

        $bg = BillingGroup::create(['billing_group_name' => 'Billing Group 1']);
        $this->assertSame('ADS-BIL-12/8379', $bg->transaction_number);

        $this->assertSame('ADS-BIL-01/0001', BillingGroup::nextTransactionNumber(Carbon::parse('2027-01-05')));

        Carbon::setTestNow();
    }

    public function test_catalyst_code_and_copies_keep_their_number(): void
    {
        $this->migrate();

        $imported = BillingGroup::create(['billing_group_name' => 'ADS-BIL-09/0001']);
        $this->assertSame('ADS-BIL-09/0001', $imported->transaction_number);

        $copy = BillingGroup::create(['billing_group_name' => 'Billing Group 1', 'transaction_number' => 'ADS-BIL-12/0141']);
        $this->assertSame('ADS-BIL-12/0141', $copy->transaction_number);
        $this->assertSame('ADS-BIL-12/0141', $copy->display_transaction_number);
    }

    public function test_without_the_column_nothing_breaks(): void
    {
        $bg = BillingGroup::create(['billing_group_name' => 'Billing Group 1']);

        $this->assertNull($bg->transaction_number);
        $this->assertSame('Billing Group 1', $bg->display_transaction_number);
    }

    public function test_erp_numbering_waits_until_1_january_2027(): void
    {
        $this->migrate();
        Carbon::setTestNow('2026-12-31 23:00:00');

        $beforeStart = BillingGroup::create(['billing_group_name' => 'Billing Group 1']);
        $this->assertNull($beforeStart->transaction_number, 'Catalyst masih menerbitkan nomor sampai 1 Jan 2027.');

        $catalyst = BillingGroup::create(['billing_group_name' => 'ADS-BIL-12/8400']);
        $this->assertSame('ADS-BIL-12/8400', $catalyst->transaction_number, 'Kode Catalyst tetap dipakai.');

        Carbon::setTestNow('2027-01-01 08:00:00');
        $afterStart = BillingGroup::create(['billing_group_name' => 'Billing Group 2']);
        $this->assertSame('ADS-BIL-01/0001', $afterStart->transaction_number);

        Carbon::setTestNow();
    }
}
