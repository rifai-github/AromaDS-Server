<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Master TOP ADS berhenti di "4x per periode contract", sedangkan Catalyst punya
 * 5xA / 6x / 8x. Tanpa migrasi ini quotation hasil import menyimpan nilai yang
 * tidak ada di master, dan dropdown Term of Payment di SQ jadi kosong.
 */
class MultiAdvanceTermOfPaymentMigrationTest extends TestCase
{
    private const MIGRATION = __DIR__.'/../../database/migrations/2026_09_11_000001_add_multi_advance_term_of_payment_options.php';

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('master_options', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('option_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('master_option_id');
            $table->string('option_name');
            $table->string('label')->nullable();
            $table->string('code')->nullable();
            $table->text('option_description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('terms_of_payment')->nullable();
            $table->timestamps();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('term_of_payment')->nullable();
            $table->timestamps();
        });

        DB::table('master_options')->insert([
            'id' => 52,
            'name' => 'Term of Payment',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        foreach (['option_details', 'master_options', 'quotations', 'contracts'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    private function migration(): object
    {
        return require self::MIGRATION;
    }

    private function seedExistingOption(int $count): void
    {
        DB::table('option_details')->insert([
            'master_option_id' => 52,
            'option_name' => "{$count}x per periode contract",
            'label' => "{$count}x in advance",
            'code' => 'installments',
            'option_description' => json_encode([
                'billing_mode' => 'per_contract_period',
                'payment_count' => (string) $count,
            ]),
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_it_adds_the_five_six_and_eight_payment_terms(): void
    {
        $this->migration()->up();

        foreach ([5, 6, 8] as $count) {
            $row = DB::table('option_details')
                ->where('option_name', "{$count}x per periode contract")
                ->first();

            $this->assertNotNull($row, "TOP {$count}x tidak dibuat");
            $this->assertSame("{$count}x in advance", $row->label);
            $this->assertSame('installments', $row->code);

            $meta = json_decode($row->option_description, true);
            $this->assertSame('per_contract_period', $meta['billing_mode']);
            $this->assertSame((string) $count, $meta['payment_count']);
        }
    }

    public function test_it_is_idempotent_and_keeps_manual_label_edits(): void
    {
        $this->seedExistingOption(5);
        DB::table('option_details')->where('option_name', '5x per periode contract')->update(['label' => 'Label manual']);

        $this->migration()->up();
        $this->migration()->up();

        $rows = DB::table('option_details')->where('option_name', '5x per periode contract')->get();
        $this->assertCount(1, $rows);
        $this->assertSame('Label manual', $rows->first()->label);
    }

    public function test_down_removes_the_options_when_unused(): void
    {
        $this->migration()->up();
        $this->migration()->down();

        $this->assertSame(0, DB::table('option_details')->whereIn('option_name', [
            '5x per periode contract',
            '6x per periode contract',
            '8x per periode contract',
        ])->count());
    }

    public function test_down_keeps_options_that_are_already_referenced(): void
    {
        $this->migration()->up();

        DB::table('quotations')->insert([
            'terms_of_payment' => '6x per periode contract',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->migration()->down();

        $this->assertSame(3, DB::table('option_details')->whereIn('option_name', [
            '5x per periode contract',
            '6x per periode contract',
            '8x per periode contract',
        ])->count());
    }

    public function test_it_does_nothing_without_the_master_option(): void
    {
        DB::table('master_options')->truncate();

        $this->migration()->up();

        $this->assertSame(0, DB::table('option_details')->count());
    }
}
