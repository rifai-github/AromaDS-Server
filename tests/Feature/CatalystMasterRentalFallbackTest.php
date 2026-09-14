<?php

namespace Tests\Feature;

use App\Services\Imports\Catalyst\CatalystMasterDataImporter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Mengunci fallback resolveMasterRentalId().
 *
 * Step `master_rentals` ada di DISABLED_STEPS, jadi peta MsProduct->master_rentals tidak pernah
 * terisi. Tanpa fallback ke master_rentals.rental_code, setiap baris quotation_rentals /
 * quotation_details / contract_rentals gagal "Master rental missing" dan SQ/kontrak hasil import
 * berakhir tanpa rental sama sekali.
 */
class CatalystMasterRentalFallbackTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('master_rentals', function (Blueprint $table) {
            $table->id();
            $table->string('rental_code')->nullable();
            $table->string('rental_name')->nullable();
        });

        Schema::create('source_import_maps', function (Blueprint $table) {
            $table->id();
            $table->string('source_system');
            $table->string('source_table');
            $table->string('source_key');
            $table->string('target_table');
            $table->unsignedBigInteger('target_id');
        });

        DB::table('master_rentals')->insert([
            ['id' => 11, 'rental_code' => '7100SB3-', 'rental_name' => 'Rental A'],
            ['id' => 12, 'rental_code' => 'A103-12-10', 'rental_name' => 'Rental B'],
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('source_import_maps');
        Schema::dropIfExists('master_rentals');

        parent::tearDown();
    }

    private function importer(): object
    {
        return new class extends CatalystMasterDataImporter
        {
            public function resolve($productCode): ?int
            {
                return $this->resolveMasterRentalId($productCode);
            }
        };
    }

    public function test_it_falls_back_to_rental_code_when_the_import_map_is_empty(): void
    {
        $this->assertSame(0, DB::table('source_import_maps')->count(), 'Prasyarat: peta harus kosong.');

        $importer = $this->importer();

        $this->assertSame(11, $importer->resolve('7100SB3-'));
        $this->assertSame(12, $importer->resolve('A103-12-10'));
    }

    public function test_it_trims_the_source_product_code_like_make_key_does(): void
    {
        $this->assertSame(11, $this->importer()->resolve('  7100SB3-  '));
    }

    public function test_the_import_map_still_wins_when_it_has_a_row(): void
    {
        DB::table('source_import_maps')->insert([
            'source_system' => 'catalyst',
            'source_table' => 'MsProduct',
            'source_key' => '7100SB3-',
            'target_table' => 'master_rentals',
            'target_id' => 99,
        ]);

        $this->assertSame(99, $this->importer()->resolve('7100SB3-'));
    }

    public function test_it_returns_null_for_an_unknown_or_blank_code(): void
    {
        $importer = $this->importer();

        $this->assertNull($importer->resolve('TIDAK-ADA'));
        $this->assertNull($importer->resolve('   '));
        $this->assertNull($importer->resolve(null));
    }

    public function test_it_caches_the_lookup_including_the_misses(): void
    {
        $importer = $this->importer();

        $queries = 0;
        DB::listen(function ($query) use (&$queries) {
            if (str_contains($query->sql, 'master_rentals')) {
                $queries++;
            }
        });

        $importer->resolve('7100SB3-');
        $importer->resolve('7100SB3-');
        $importer->resolve('TIDAK-ADA');
        $importer->resolve('TIDAK-ADA');

        $this->assertSame(2, $queries, 'Tiap kode hanya boleh sekali query, hit maupun miss.');
    }
}
