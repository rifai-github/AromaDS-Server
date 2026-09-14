<?php

namespace Tests\Feature;

use App\Services\DocumentNumberService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Mengunci penomoran dokumen di atas 9999.
 *
 * Pencari urutan dulu membaca 4 KARAKTER TERAKHIR nomor, jadi ".../10100" terbaca 100.
 * Begitu satu prefix tembus 9999 urutannya balik ke bawah, setiap kandidat bertabrakan,
 * dan setelah 100 percobaan nomor yang SUDAH DIPAKAI tetap dikembalikan. Di import Catalyst
 * itu membuat 273 survei menyatu jadi satu baris, karena importer mencocokkan baris lewat
 * nomor dokumen.
 */
class DocumentNumberSequenceOverflowTest extends TestCase
{
    private string $prefix;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->string('survey_number')->nullable();
        });

        $now = Carbon::now();
        $this->prefix = 'JKT-SR/' . $now->format('y') . '-' . $now->format('m') . '/';
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('surveys');

        parent::tearDown();
    }

    private function seedNumbers(array $sequences): void
    {
        DB::table('surveys')->insert(array_map(
            fn ($seq) => ['survey_number' => $this->prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT)],
            $sequences
        ));
    }

    public function test_it_continues_past_9999_instead_of_wrapping_back_to_the_last_four_digits(): void
    {
        // Keranjang padat yang sudah tembus 9999 - bentuk persis yang meruntuhkan
        // JKT-SR/26-09 di produksi.
        $this->seedNumbers(array_merge([9999], range(10000, 10100)));

        $number = (new DocumentNumberService())->generate('survey', 'JKT');

        $this->assertSame($this->prefix . '10101', $number);
    }

    public function test_it_never_hands_back_a_number_that_already_exists(): void
    {
        $this->seedNumbers(array_merge([9999], range(10000, 10100)));

        $number = (new DocumentNumberService())->generate('survey', 'JKT');

        $this->assertFalse(
            DB::table('surveys')->where('survey_number', $number)->exists(),
            'Nomor yang dikembalikan sudah dipakai baris lain - inilah yang bikin survei tertimpa.'
        );
    }

    public function test_ordinary_numbering_below_9999_is_unchanged(): void
    {
        $this->seedNumbers([1, 2, 3]);

        $this->assertSame($this->prefix . '0004', (new DocumentNumberService())->generate('survey', 'JKT'));
    }

    public function test_an_empty_prefix_starts_at_one(): void
    {
        $this->assertSame($this->prefix . '0001', (new DocumentNumberService())->generate('survey', 'JKT'));
    }

    public function test_a_gap_below_the_maximum_is_not_reused(): void
    {
        // Nomor dokumen tidak boleh dipakai ulang hanya karena ada lubang di tengah.
        $this->seedNumbers([1, 2, 5]);

        $this->assertSame($this->prefix . '0006', (new DocumentNumberService())->generate('survey', 'JKT'));
    }

    public function test_it_ignores_numbers_belonging_to_another_prefix(): void
    {
        $this->seedNumbers([1, 2]);
        DB::table('surveys')->insert(['survey_number' => 'BAL-SR/00-01/9999']);

        $this->assertSame($this->prefix . '0003', (new DocumentNumberService())->generate('survey', 'JKT'));
    }
}
