<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Import Catalyst membiarkan contracts.quotation_id kosong ketika kolom sumber
 * SqNo tidak terisi, dan contract_surveys ikut kosong karena diturunkan dari
 * kolom itu. Command backfill hanya boleh menyambung yang tidak ambigu.
 */
class RelinkContractQuotationSurveyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('quotation_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quotation_surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->nullable();
            $table->foreignId('survey_id')->nullable();
            $table->integer('sort_order')->nullable();
            $table->timestamp('added_at')->nullable();
            $table->timestamps();
        });

        // added_by NOT NULL tanpa default di production - lupa mengisinya
        // bikin command ini crash di tengah jalan (ketemu saat --apply pertama
        // kali ke QA, 10 Agu 2026). Skema di sini sengaja disamakan biar
        // regresinya ketahuan dari test, bukan dari --apply ke data asli.
        Schema::create('contract_surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('survey_id')->nullable();
            $table->integer('sort_order')->nullable();
            $table->timestamp('added_at')->nullable(false);
            $table->unsignedBigInteger('added_by')->nullable(false);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        DB::table('users')->insert([
            'id' => 28,
            'name' => 'Administrator',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        foreach (['users', 'contract_surveys', 'quotation_surveys', 'quotations', 'contracts'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    private function seedUnambiguousPair(): void
    {
        DB::table('contracts')->insert([
            'id' => 1,
            'contract_number' => 'SMG-AG/25-04/0013',
            'customer_id' => 475,
            'quotation_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('quotations')->insert([
            'id' => 90,
            'quotation_number' => 'SMG-SQ/25-04/0102',
            'customer_id' => 475,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('quotation_surveys')->insert([
            'id' => 5,
            'quotation_id' => 90,
            'survey_id' => 7151,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_dry_run_reports_the_link_without_writing_anything(): void
    {
        $this->seedUnambiguousPair();

        $this->artisan('contracts:relink-quotation-survey')->assertExitCode(0);

        $this->assertNull(DB::table('contracts')->where('id', 1)->value('quotation_id'));
        $this->assertSame(0, DB::table('contract_surveys')->count());
    }

    public function test_apply_links_contract_to_quotation_and_rebuilds_surveys(): void
    {
        $this->seedUnambiguousPair();

        $this->artisan('contracts:relink-quotation-survey --apply')->assertExitCode(0);

        $this->assertSame(90, (int) DB::table('contracts')->where('id', 1)->value('quotation_id'));
        $this->assertDatabaseHas('contract_surveys', [
            'contract_id' => 1,
            'survey_id' => 7151,
            'added_by' => 28,
        ]);
    }

    public function test_ambiguous_period_matches_are_left_alone(): void
    {
        $this->seedUnambiguousPair();

        // SQ kedua di customer, cabang, dan periode yang sama.
        DB::table('quotations')->insert([
            'id' => 91,
            'quotation_number' => 'SMG-SQ/25-04/0777',
            'customer_id' => 475,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('contracts:relink-quotation-survey --apply')->assertExitCode(0);

        $this->assertNull(DB::table('contracts')->where('id', 1)->value('quotation_id'));
        $this->assertSame(0, DB::table('contract_surveys')->count());
    }

    public function test_a_different_customer_is_never_matched(): void
    {
        $this->seedUnambiguousPair();
        DB::table('quotations')->where('id', 90)->update(['customer_id' => 999]);

        $this->artisan('contracts:relink-quotation-survey --apply')->assertExitCode(0);

        $this->assertNull(DB::table('contracts')->where('id', 1)->value('quotation_id'));
    }

    public function test_a_different_period_is_never_matched(): void
    {
        $this->seedUnambiguousPair();
        DB::table('quotations')->where('id', 90)->update(['quotation_number' => 'SMG-SQ/25-09/0102']);

        $this->artisan('contracts:relink-quotation-survey --apply')->assertExitCode(0);

        $this->assertNull(DB::table('contracts')->where('id', 1)->value('quotation_id'));
    }

    public function test_a_different_branch_is_never_matched(): void
    {
        $this->seedUnambiguousPair();
        DB::table('quotations')->where('id', 90)->update(['quotation_number' => 'JKT-SQ/25-04/0102']);

        $this->artisan('contracts:relink-quotation-survey --apply')->assertExitCode(0);

        $this->assertNull(DB::table('contracts')->where('id', 1)->value('quotation_id'));
    }

    public function test_an_existing_link_is_never_overwritten(): void
    {
        $this->seedUnambiguousPair();
        DB::table('contracts')->where('id', 1)->update(['quotation_id' => 12345]);

        $this->artisan('contracts:relink-quotation-survey --apply')->assertExitCode(0);

        $this->assertSame(12345, (int) DB::table('contracts')->where('id', 1)->value('quotation_id'));
    }

    public function test_a_contract_numbered_as_a_quotation_is_matched_directly(): void
    {
        DB::table('contracts')->insert([
            'id' => 2,
            'contract_number' => 'SRG-SQ/23-05/0018X',
            'customer_id' => 300,
            'quotation_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('quotations')->insert([
            'id' => 70,
            'quotation_number' => 'SRG-SQ/23-05/0018',
            'customer_id' => 300,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('contracts:relink-quotation-survey --apply')->assertExitCode(0);

        $this->assertSame(70, (int) DB::table('contracts')->where('id', 2)->value('quotation_id'));
    }

    public function test_rerunning_does_not_duplicate_contract_survey_rows(): void
    {
        $this->seedUnambiguousPair();

        $this->artisan('contracts:relink-quotation-survey --apply')->assertExitCode(0);
        $this->artisan('contracts:relink-quotation-survey --apply')->assertExitCode(0);

        $this->assertSame(1, DB::table('contract_surveys')->where('contract_id', 1)->count());
    }

    public function test_surveys_only_rebuilds_links_without_touching_quotation_id(): void
    {
        $this->seedUnambiguousPair();
        DB::table('contracts')->where('id', 1)->update(['quotation_id' => 90]);

        $this->artisan('contracts:relink-quotation-survey --surveys-only --apply')->assertExitCode(0);

        $this->assertDatabaseHas('contract_surveys', [
            'contract_id' => 1,
            'survey_id' => 7151,
        ]);
    }
}
