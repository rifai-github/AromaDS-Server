<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Katalog frekuensi service harus ikut pulih setiap kali database di-reset.
 *
 * Perbaikan 16 Sep 2026 dijalankan manual lewat command, lalu hilang lagi saat produksi
 * di-bootstrap ulang 20 Sep 2026 — dan QA melaporkan gejala yang sama persis pada 21 Sep
 * ("Tidak ada Frequensi Service di setiap Rental", "Job Service/Check lanjutan tidak
 * keluar"). Migrasi 2026_09_22_000001 menutup celah itu karena alur reset memang
 * menjalankan `php artisan migrate`.
 */
class SeedRentalServiceFrequenciesMigrationTest extends TestCase
{
    private const MIGRATION = __DIR__.'/../../database/migrations/2026_09_22_000001_seed_rental_service_frequencies.php';

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('rental_service_frequencies', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->string('description')->nullable();
            $table->integer('frequency_months')->default(1);
            $table->integer('frequency_times_per_month')->default(1);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_rentals', function (Blueprint $table) {
            $table->id();
            $table->string('rental_code')->nullable();
            $table->string('rental_name')->nullable();
            $table->foreignId('service_frequency_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        foreach (['master_rentals', 'rental_service_frequencies'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    private function runMigration(): void
    {
        (require self::MIGRATION)->up();
    }

    private function seedRentals(): void
    {
        DB::table('master_rentals')->insert([
            // "N Bln Mx"
            ['id' => 1, 'rental_code' => 'C10050', 'rental_name' => 'ADS C100 50ml 1 bln 1x', 'service_frequency_id' => null],
            ['id' => 2, 'rental_code' => 'C10051', 'rental_name' => 'ADS C100 50ml 2 Bln 1x', 'service_frequency_id' => null],
            // "N SVC / YR"
            ['id' => 3, 'rental_code' => 'A103', 'rental_name' => 'ADS 103 12 SVC / YR PCKG 100 ml', 'service_frequency_id' => null],
            ['id' => 4, 'rental_code' => 'A104', 'rental_name' => 'ADS 104 24 SVC / YR PCKG 100 ml', 'service_frequency_id' => null],
            // Tanpa petunjuk kadens - harus dibiarkan NULL, bukan ditebak.
            ['id' => 5, 'rental_code' => 'HS7100', 'rental_name' => 'Dispenser Hand Sanitizer 7100 SB3--', 'service_frequency_id' => null],
        ]);
    }

    public function test_it_fills_the_catalog_and_maps_rentals_from_their_names(): void
    {
        $this->seedRentals();

        $this->runMigration();

        $this->assertSame(5, DB::table('rental_service_frequencies')->count());

        $monthly = DB::table('rental_service_frequencies')->where('code', '1M1X')->first();
        $everyTwoMonths = DB::table('rental_service_frequencies')->where('code', '2M1X')->first();
        $twicePerMonth = DB::table('rental_service_frequencies')->where('code', '1M2X')->first();

        $this->assertSame((int) $monthly->id, (int) DB::table('master_rentals')->where('id', 1)->value('service_frequency_id'));
        $this->assertSame((int) $everyTwoMonths->id, (int) DB::table('master_rentals')->where('id', 2)->value('service_frequency_id'));
        $this->assertSame((int) $monthly->id, (int) DB::table('master_rentals')->where('id', 3)->value('service_frequency_id'));
        $this->assertSame((int) $twicePerMonth->id, (int) DB::table('master_rentals')->where('id', 4)->value('service_frequency_id'));

        // Nama tanpa kadens tidak ditebak.
        $this->assertNull(DB::table('master_rentals')->where('id', 5)->value('service_frequency_id'));
    }

    public function test_running_it_twice_changes_nothing_more(): void
    {
        $this->seedRentals();

        $this->runMigration();
        $snapshot = DB::table('master_rentals')->orderBy('id')->pluck('service_frequency_id', 'id')->all();

        $this->runMigration();

        $this->assertSame(5, DB::table('rental_service_frequencies')->count());
        $this->assertSame($snapshot, DB::table('master_rentals')->orderBy('id')->pluck('service_frequency_id', 'id')->all());
    }

    public function test_it_never_overwrites_a_frequency_someone_already_chose(): void
    {
        $this->seedRentals();

        DB::table('rental_service_frequencies')->insert([
            'id' => 90,
            'code' => 'FS1B1',
            'name' => 'Freq 1 Bln 1x Svc',
            'frequency_months' => 1,
            'frequency_times_per_month' => 1,
            'is_active' => true,
        ]);
        DB::table('master_rentals')->where('id', 1)->update(['service_frequency_id' => 90]);

        $this->runMigration();

        // Baris katalog buatan tangan dibiarkan, dan rental yang sudah dipetakan manusia
        // tidak digeser ke baris katalog bawaan.
        $this->assertSame(90, (int) DB::table('master_rentals')->where('id', 1)->value('service_frequency_id'));
        $this->assertDatabaseHas('rental_service_frequencies', ['id' => 90, 'code' => 'FS1B1']);
    }

    public function test_a_soft_deleted_catalog_row_is_restored_instead_of_duplicated(): void
    {
        $this->seedRentals();

        DB::table('rental_service_frequencies')->insert([
            'id' => 91,
            'code' => '1M1X',
            'name' => 'Monthly',
            'frequency_months' => 1,
            'frequency_times_per_month' => 1,
            'is_active' => true,
            'deleted_at' => now(),
        ]);

        $this->runMigration();

        $this->assertNull(DB::table('rental_service_frequencies')->where('id', 91)->value('deleted_at'));
        $this->assertSame(1, DB::table('rental_service_frequencies')->where('code', '1M1X')->count());
        $this->assertSame(91, (int) DB::table('master_rentals')->where('id', 1)->value('service_frequency_id'));
    }
}
