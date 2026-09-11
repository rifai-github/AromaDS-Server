<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Import Catalyst sempat menyimpan kode TermOfPayment mentah ("00", "3xM",
 * "QUA") di quotations.terms_of_payment dan rental_period bergaya "6 bulan"
 * (list SQ jadi menampilkan "6 bulan Bulan"). Command remap memperbaiki baris
 * yang terlanjur masuk tanpa harus import ulang.
 */
class RemapCatalystTermOfPaymentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number')->nullable();
            $table->string('rental_period')->nullable();
            $table->string('rental_unit')->nullable();
            $table->string('terms_of_payment')->nullable();
            $table->integer('top_months')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('billing_methods')->nullable();
            $table->timestamps();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->nullable();
            $table->string('term_of_payment')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('quotations');
        Schema::dropIfExists('contracts');

        parent::tearDown();
    }

    private function seedQuotation(array $overrides = []): int
    {
        return DB::table('quotations')->insertGetId(array_merge([
            'quotation_number' => 'SQ-001',
            'rental_period' => '12 bulan',
            'rental_unit' => 'bulan',
            'terms_of_payment' => '00',
            'top_months' => null,
            'payment_method' => null,
            'billing_methods' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    public function test_dry_run_reports_changes_without_writing(): void
    {
        $id = $this->seedQuotation();

        $this->artisan('catalyst:remap-term-of-payment')->assertSuccessful();

        $row = DB::table('quotations')->find($id);
        $this->assertSame('12 bulan', $row->rental_period);
        $this->assertSame('00', $row->terms_of_payment);
    }

    public function test_apply_remaps_monthly_code_and_strips_the_unit_from_rental_period(): void
    {
        $id = $this->seedQuotation();

        $this->artisan('catalyst:remap-term-of-payment --apply')->assertSuccessful();

        $row = DB::table('quotations')->find($id);
        $this->assertSame('12', $row->rental_period);
        $this->assertSame('1 bulan 1x', $row->terms_of_payment);
        $this->assertSame(1, (int) $row->top_months);
        $this->assertSame('After Service', $row->payment_method);
        $this->assertSame('After Service', $row->billing_methods);
    }

    public function test_apply_remaps_advance_codes_and_derives_top_months(): void
    {
        $quarterly = $this->seedQuotation(['quotation_number' => 'SQ-QUA', 'terms_of_payment' => 'QUA']);
        $fourTimes = $this->seedQuotation(['quotation_number' => 'SQ-4X', 'terms_of_payment' => '4x']);
        $once = $this->seedQuotation(['quotation_number' => 'SQ-1X', 'terms_of_payment' => '1x']);

        $this->artisan('catalyst:remap-term-of-payment --apply')->assertSuccessful();

        $row = DB::table('quotations')->find($quarterly);
        $this->assertSame('3 bulan 1x', $row->terms_of_payment);
        $this->assertSame(3, (int) $row->top_months);
        $this->assertSame('After Service', $row->payment_method);

        $row = DB::table('quotations')->find($fourTimes);
        $this->assertSame('4x per periode contract', $row->terms_of_payment);
        $this->assertSame(3, (int) $row->top_months); // 12 bulan / 4x
        $this->assertSame('Before Service', $row->payment_method);

        $row = DB::table('quotations')->find($once);
        $this->assertSame('Tahunan', $row->terms_of_payment);
        $this->assertNull($row->top_months);
        $this->assertSame('Before Service', $row->payment_method);
    }

    public function test_it_never_overwrites_an_existing_payment_method(): void
    {
        $id = $this->seedQuotation(['payment_method' => 'Before Service', 'billing_methods' => 'Before Service']);

        $this->artisan('catalyst:remap-term-of-payment --apply')->assertSuccessful();

        $row = DB::table('quotations')->find($id);
        $this->assertSame('1 bulan 1x', $row->terms_of_payment);
        $this->assertSame('Before Service', $row->payment_method);
    }

    public function test_rows_already_using_ads_terms_are_left_alone(): void
    {
        $id = $this->seedQuotation([
            'rental_period' => '6',
            'terms_of_payment' => '3 bulan 1x',
            'top_months' => 3,
            'payment_method' => 'After Service',
        ]);
        $before = DB::table('quotations')->find($id);

        $this->artisan('catalyst:remap-term-of-payment --apply')->assertSuccessful();

        $this->assertEquals($before, DB::table('quotations')->find($id));
    }

    public function test_day_based_rental_periods_are_not_converted(): void
    {
        $id = $this->seedQuotation(['rental_period' => '14', 'rental_unit' => 'hari', 'terms_of_payment' => 'COD']);

        $this->artisan('catalyst:remap-term-of-payment --apply')->assertSuccessful();

        $row = DB::table('quotations')->find($id);
        $this->assertSame('14', $row->rental_period);
        $this->assertSame('hari', $row->rental_unit);
        $this->assertSame('1 bulan 1x', $row->terms_of_payment);
    }

    public function test_contracts_get_the_same_mapping(): void
    {
        DB::table('contracts')->insert([
            ['contract_number' => 'CT-3XM', 'term_of_payment' => '3xM', 'created_at' => now(), 'updated_at' => now()],
            ['contract_number' => 'CT-OK', 'term_of_payment' => '1 bulan 1x', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->artisan('catalyst:remap-term-of-payment --apply')->assertSuccessful();

        $this->assertSame('3 bulan 1x', DB::table('contracts')->where('contract_number', 'CT-3XM')->value('term_of_payment'));
        $this->assertSame('1 bulan 1x', DB::table('contracts')->where('contract_number', 'CT-OK')->value('term_of_payment'));
    }
}
