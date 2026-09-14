<?php

namespace Tests\Feature;

use App\Services\Imports\Catalyst\CatalystMasterDataImporter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionProperty;
use Tests\TestCase;

/**
 * Mengunci pengaman step `surveys`: identitas survei adalah source key (SQ||gedung),
 * BUKAN nomornya.
 *
 * Dengan fallback-match aktif, satu nomor yang bertabrakan tidak membuat baris baru -
 * syncRecord menemukan baris lama lewat `survey_number` lalu MENIMPA-nya. Di produksi itu
 * membuat 273 pasangan (SQ, gedung) menyatu ke satu baris survei, sehingga 266 SQ
 * menampilkan survei milik gedung lain.
 *
 * Penomoran sudah diperbaiki terpisah (DocumentNumberSequenceOverflowTest), tapi pengaman
 * ini harus tetap ada: kalau penomoran rusak lagi, akibatnya cuma nomor kembar - bukan dua
 * survei yang diam-diam menyatu.
 */
class CatalystSurveyNoFallbackMatchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->string('survey_number')->nullable();
            $table->unsignedBigInteger('building_id')->nullable();
        });

        Schema::create('source_import_maps', function (Blueprint $table) {
            $table->id();
            $table->string('source_system');
            $table->string('source_table');
            $table->string('source_key');
            $table->string('target_table');
            $table->unsignedBigInteger('target_id');
            $table->string('source_hash')->nullable();
            $table->unsignedBigInteger('last_batch_id')->nullable();
            $table->timestamp('last_imported_at')->nullable();
            $table->timestamps();
        });

        // Survei milik gedung Jakarta yang sudah ada, tanpa baris peta - persis kondisi
        // survey 32816 sebelum ditimpa.
        DB::table('surveys')->insert([
            'id' => 900,
            'survey_number' => 'JKT-SR/26-09/10100',
            'building_id' => 17363,
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('source_import_maps');
        Schema::dropIfExists('surveys');

        parent::tearDown();
    }

    private function importer(): object
    {
        $importer = new class extends CatalystMasterDataImporter
        {
            public function sync(string $sourceKey, array $match, array $payload, bool $allowFallbackMatch): array
            {
                return $this->syncRecord(
                    'surveys',
                    'MKTQuotationRental_survey',
                    $sourceKey,
                    'surveys',
                    $match,
                    $payload,
                    [],
                    $allowFallbackMatch
                );
            }
        };

        foreach (['apply' => true, 'batchId' => 1] as $name => $value) {
            $property = new ReflectionProperty(CatalystMasterDataImporter::class, $name);
            $property->setAccessible(true);
            $property->setValue($importer, $value);
        }

        return $importer;
    }

    public function test_without_fallback_a_colliding_number_creates_its_own_survey(): void
    {
        $result = $this->importer()->sync(
            'BAL-SQ/26-09/0413||ADS-BALS0037',
            ['survey_number' => 'JKT-SR/26-09/10100'],
            ['building_id' => 17678],
            false
        );

        $this->assertSame('inserted', $result['action']);
        $this->assertNotSame(900, $result['target_id']);

        // Survei Jakarta yang lama tidak tersentuh.
        $this->assertSame(17363, (int) DB::table('surveys')->where('id', 900)->value('building_id'));
        $this->assertSame(2, DB::table('surveys')->count());
    }

    public function test_with_fallback_the_old_survey_is_hijacked_instead(): void
    {
        // Ini perilaku LAMA, dipertahankan sebagai dokumentasi kenapa surveys memakai false.
        $result = $this->importer()->sync(
            'BAL-SQ/26-09/0413||ADS-BALS0037',
            ['survey_number' => 'JKT-SR/26-09/10100'],
            ['building_id' => 17678],
            true
        );

        $this->assertSame('updated', $result['action']);
        $this->assertSame(900, (int) $result['target_id']);

        // Gedung Jakarta tertimpa gedung Bali - survei milik SQ lain ikut berubah.
        $this->assertSame(17678, (int) DB::table('surveys')->where('id', 900)->value('building_id'));
        $this->assertSame(1, DB::table('surveys')->count());
    }

    public function test_a_mapped_survey_is_still_updated_in_place(): void
    {
        DB::table('source_import_maps')->insert([
            'source_system' => 'catalyst',
            'source_table' => 'MKTQuotationRental_survey',
            'source_key' => 'JKT-SQ/26-09/0001||ADS-ADSW0022',
            'target_table' => 'surveys',
            'target_id' => 900,
        ]);

        $result = $this->importer()->sync(
            'JKT-SQ/26-09/0001||ADS-ADSW0022',
            ['survey_number' => 'JKT-SR/26-09/10100'],
            ['building_id' => 17363],
            false
        );

        // Tidak ada perubahan payload, jadi skipped - yang penting tetap baris 900,
        // bukan baris baru. Mematikan fallback tidak boleh bikin duplikat tiap re-run.
        $this->assertSame(900, (int) $result['target_id']);
        $this->assertSame(1, DB::table('surveys')->count());
    }
}
