<?php

namespace Tests\Feature;

use App\Services\Imports\Catalyst\CatalystMasterDataImporter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionProperty;
use Tests\TestCase;

/**
 * Mengunci step backfill quotation_existing_contracts.
 *
 * Step `quotations` berjalan sebelum `contracts`, jadi existing_contract_id tidak akan pernah
 * terisi pada import dari nol. Step ini mengulang resolusinya setelah peta kontrak ada.
 */
class CatalystQuotationExistingContractStepTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number')->nullable();
            $table->unsignedBigInteger('existing_contract_id')->nullable();
            $table->timestamps();
        });

        Schema::create('source_import_maps', function (Blueprint $table) {
            $table->id();
            $table->string('source_system');
            $table->string('source_table');
            $table->string('source_key');
            $table->string('target_table');
            $table->unsignedBigInteger('target_id');
        });

        DB::table('quotations')->insert([
            ['id' => 5, 'quotation_number' => 'SQ/001', 'existing_contract_id' => null],
            ['id' => 6, 'quotation_number' => 'SQ/002', 'existing_contract_id' => null],
        ]);

        DB::table('source_import_maps')->insert([
            ['source_system' => 'catalyst', 'source_table' => 'MKTQuotationHd', 'source_key' => 'SQ/001', 'target_table' => 'quotations', 'target_id' => 5],
            ['source_system' => 'catalyst', 'source_table' => 'MKTQuotationHd', 'source_key' => 'SQ/002', 'target_table' => 'quotations', 'target_id' => 6],
            ['source_system' => 'catalyst', 'source_table' => 'MKTContractHd', 'source_key' => 'CT-10', 'target_table' => 'contracts', 'target_id' => 77],
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('source_import_maps');
        Schema::dropIfExists('quotations');

        parent::tearDown();
    }

    /**
     * @param  list<object>  $sourceRows
     */
    private function importer(array $sourceRows, bool $apply): object
    {
        $importer = new class($sourceRows) extends CatalystMasterDataImporter
        {
            public function __construct(private array $sourceRows) {}

            protected function sourceQuotationRentalRows(): array
            {
                return $this->sourceRows;
            }

            // Dua ini menulis ke source_import_logs / source_import_batches milik satu batch
            // berjalan; di luar konteks batch tidak ada yang perlu dicatat.
            protected function log(string $step, string $level, string $message, array $context = []): void {}

            protected function heartbeat(string $step, array $stats, int $totalRows, bool $final = false): void {}

            public function runBackfill(): array
            {
                return $this->quotation_existing_contracts();
            }
        };

        foreach (['apply' => $apply, 'heartbeatEvery' => 1000] as $name => $value) {
            $property = new ReflectionProperty(CatalystMasterDataImporter::class, $name);
            $property->setAccessible(true);
            $property->setValue($importer, $value);
        }

        return $importer;
    }

    public function test_it_fills_existing_contract_id_after_the_contract_map_exists(): void
    {
        $result = $this->importer([
            (object) ['TransNmbr' => 'SQ/001', 'OldContractNo' => 'CT-10'],
        ], true)->runBackfill();

        $this->assertSame(1, $result['stats']['updated']);
        $this->assertSame(77, (int) DB::table('quotations')->where('id', 5)->value('existing_contract_id'));
    }

    public function test_dry_run_reports_the_change_without_writing_it(): void
    {
        $result = $this->importer([
            (object) ['TransNmbr' => 'SQ/001', 'OldContractNo' => 'CT-10'],
        ], false)->runBackfill();

        $this->assertSame(1, $result['stats']['updated']);
        $this->assertNull(DB::table('quotations')->where('id', 5)->value('existing_contract_id'));
    }

    public function test_an_unmapped_old_contract_fails_the_row_and_leaves_the_quotation_alone(): void
    {
        $result = $this->importer([
            (object) ['TransNmbr' => 'SQ/002', 'OldContractNo' => 'CT-TIDAK-ADA'],
        ], true)->runBackfill();

        $this->assertSame(1, $result['stats']['failed']);
        $this->assertNull(DB::table('quotations')->where('id', 6)->value('existing_contract_id'));
    }

    public function test_a_quotation_that_is_already_correct_is_skipped(): void
    {
        DB::table('quotations')->where('id', 5)->update(['existing_contract_id' => 77]);

        $result = $this->importer([
            (object) ['TransNmbr' => 'SQ/001', 'OldContractNo' => 'CT-10'],
        ], true)->runBackfill();

        $this->assertSame(1, $result['stats']['skipped']);
        $this->assertSame(0, $result['stats']['updated']);
    }

    public function test_rows_without_an_old_contract_number_never_reach_the_step(): void
    {
        $result = $this->importer([
            (object) ['TransNmbr' => 'SQ/001', 'OldContractNo' => null],
            (object) ['TransNmbr' => 'SQ/002', 'OldContractNo' => '   '],
        ], true)->runBackfill();

        $this->assertSame(0, $result['stats']['processed']);
    }

    public function test_the_step_runs_after_contracts(): void
    {
        $property = new ReflectionProperty(CatalystMasterDataImporter::class, 'steps');
        $property->setAccessible(true);
        $steps = $property->getValue(new CatalystMasterDataImporter());

        $this->assertGreaterThan(
            array_search('contracts', $steps, true),
            array_search('quotation_existing_contracts', $steps, true),
            'Backfill harus setelah contracts, kalau tidak peta kontraknya masih kosong.'
        );
    }
}
