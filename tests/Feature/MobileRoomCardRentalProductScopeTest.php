<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Mobile\JobController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Tests\TestCase;

/**
 * Kartu ruangan di APK sudah dipecah per rental, tapi daftar materialnya tidak:
 * material_issue_items / inventory_issuing_items hanya membawa room_name, tanpa
 * kunci rental. Akibatnya di ruangan dengan 2 rental, kartu VirusGuard ikut
 * menampilkan diffuser milik rental ADS 250 (QA 15 Sep 2026).
 *
 * Penyaringnya memakai KATEGORI produk pada BOM rental, bukan id produknya —
 * Material Assign boleh menukar produk ke sekeluarga (data produksi: BOM menyebut
 * "Air Purification VG 1600" tetapi yang dikeluarkan "VG 800", dan SA250 Black
 * ditukar jadi White), sehingga pencocokan id produk justru menyembunyikan barang
 * yang benar-benar dikeluarkan.
 */
class MobileRoomCardRentalProductScopeTest extends TestCase
{
    // Kategori nyata dari job produksi ADS-IF/26-09/0001, Ruang Meeting.
    private const CAT_AIR_PURIFIER = 19;
    private const CAT_ULTRAVIOLET = 20;
    private const CAT_PRE_FILTER = 21;
    private const CAT_DIFFUSER = 2;
    private const CAT_REFILL_ENZYME = 24;

    private const RENTAL_VGUARD = 328;
    private const RENTAL_ADS250 = 73;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('product_category_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('rental_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('master_rental_id')->nullable();
            $table->string('item_type')->nullable();
            $table->unsignedBigInteger('product_category_id')->nullable();
            $table->unsignedBigInteger('master_product_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('master_products')->insert([
            ['id' => 1, 'name' => 'Air Purification VG 800', 'product_category_id' => self::CAT_AIR_PURIFIER],
            ['id' => 2, 'name' => 'JAF PRE 800 FILTER', 'product_category_id' => self::CAT_PRE_FILTER],
            ['id' => 3, 'name' => 'Ultraviolet', 'product_category_id' => self::CAT_ULTRAVIOLET],
            ['id' => 4, 'name' => 'SA250 with Bluetooth Apps (White)', 'product_category_id' => self::CAT_DIFFUSER],
            ['id' => 5, 'name' => 'PURE Phyto Green (Enzym) 200 ml', 'product_category_id' => self::CAT_REFILL_ENZYME],
            ['id' => 6, 'name' => 'Barang tanpa kategori', 'product_category_id' => null],
        ]);

        DB::table('rental_details')->insert([
            // BOM VirusGuard menyebut VG 1600, bukan VG 800 yang benar-benar dikeluarkan.
            ['master_rental_id' => self::RENTAL_VGUARD, 'item_type' => 'product', 'product_category_id' => self::CAT_AIR_PURIFIER, 'master_product_id' => null],
            ['master_rental_id' => self::RENTAL_VGUARD, 'item_type' => 'product_type', 'product_category_id' => self::CAT_PRE_FILTER, 'master_product_id' => null],
            ['master_rental_id' => self::RENTAL_VGUARD, 'item_type' => 'product', 'product_category_id' => self::CAT_ULTRAVIOLET, 'master_product_id' => null],
            ['master_rental_id' => self::RENTAL_ADS250, 'item_type' => 'product', 'product_category_id' => self::CAT_DIFFUSER, 'master_product_id' => null],
            ['master_rental_id' => self::RENTAL_ADS250, 'item_type' => 'product', 'product_category_id' => self::CAT_REFILL_ENZYME, 'master_product_id' => null],
        ]);
    }

    private function narrow(array $products, $roomGroup, $displayRoom): array
    {
        $controller = app(JobController::class);
        $method = (new ReflectionClass($controller))->getMethod('narrowProductsToCardRental');
        $method->setAccessible(true);

        return $method->invoke($controller, $products, $roomGroup, $displayRoom);
    }

    private function adviceRoom(int $rentalId): object
    {
        return (object) ['rental_product_id' => $rentalId];
    }

    private function roomProducts(): array
    {
        return [
            ['product_id' => 1, 'product_name' => 'Air Purification VG 800'],
            ['product_id' => 2, 'product_name' => 'JAF PRE 800 FILTER'],
            ['product_id' => 3, 'product_name' => 'Ultraviolet'],
            ['product_id' => 4, 'product_name' => 'SA250 with Bluetooth Apps (White)'],
            ['product_id' => 5, 'product_name' => 'PURE Phyto Green (Enzym) 200 ml'],
        ];
    }

    public function test_virusguard_card_drops_the_ads250_diffuser_and_refill(): void
    {
        $vguard = $this->adviceRoom(self::RENTAL_VGUARD);
        $group = collect([$vguard, $this->adviceRoom(self::RENTAL_ADS250)]);

        $names = collect($this->narrow($this->roomProducts(), $group, $vguard))->pluck('product_name')->all();

        $this->assertSame([
            'Air Purification VG 800',
            'JAF PRE 800 FILTER',
            'Ultraviolet',
        ], $names);
    }

    public function test_ads250_card_keeps_only_its_own_products(): void
    {
        $ads = $this->adviceRoom(self::RENTAL_ADS250);
        $group = collect([$this->adviceRoom(self::RENTAL_VGUARD), $ads]);

        $names = collect($this->narrow($this->roomProducts(), $group, $ads))->pluck('product_name')->all();

        $this->assertSame([
            'SA250 with Bluetooth Apps (White)',
            'PURE Phyto Green (Enzym) 200 ml',
        ], $names);
    }

    public function test_single_rental_room_is_left_untouched(): void
    {
        $vguard = $this->adviceRoom(self::RENTAL_VGUARD);
        $products = $this->roomProducts();

        $this->assertSame($products, $this->narrow($products, collect([$vguard]), $vguard));
    }

    public function test_product_with_unknown_category_is_never_hidden(): void
    {
        $vguard = $this->adviceRoom(self::RENTAL_VGUARD);
        $group = collect([$vguard, $this->adviceRoom(self::RENTAL_ADS250)]);

        $products = [
            ['product_id' => 1, 'product_name' => 'Air Purification VG 800'],
            ['product_id' => 6, 'product_name' => 'Barang tanpa kategori'],
        ];

        $names = collect($this->narrow($products, $group, $vguard))->pluck('product_name')->all();

        $this->assertContains('Barang tanpa kategori', $names);
    }

    public function test_never_narrows_down_to_an_empty_list(): void
    {
        // Kartu VirusGuard, tetapi yang dikeluarkan hanya material milik rental
        // tetangga. Menyembunyikan semuanya jauh lebih berbahaya daripada
        // menampilkan lebih, jadi daftar aslinya dipertahankan utuh.
        $vguard = $this->adviceRoom(self::RENTAL_VGUARD);
        $group = collect([$vguard, $this->adviceRoom(self::RENTAL_ADS250)]);

        $products = [
            ['product_id' => 4, 'product_name' => 'SA250 with Bluetooth Apps (White)'],
            ['product_id' => 5, 'product_name' => 'PURE Phyto Green (Enzym) 200 ml'],
        ];

        $this->assertSame($products, $this->narrow($products, $group, $vguard));
    }

    public function test_shared_category_between_both_rentals_is_not_used_to_drop(): void
    {
        // Kalau kedua rental memakai kategori yang sama, kategori itu tidak bisa
        // membedakan siapa pemiliknya dan tidak boleh dipakai membuang apa pun.
        DB::table('rental_details')->insert([
            'master_rental_id' => self::RENTAL_VGUARD,
            'item_type' => 'product',
            'product_category_id' => self::CAT_DIFFUSER,
            'master_product_id' => null,
        ]);

        $vguard = $this->adviceRoom(self::RENTAL_VGUARD);
        $group = collect([$vguard, $this->adviceRoom(self::RENTAL_ADS250)]);

        $names = collect($this->narrow($this->roomProducts(), $group, $vguard))->pluck('product_name')->all();

        $this->assertContains('SA250 with Bluetooth Apps (White)', $names);
        $this->assertNotContains('PURE Phyto Green (Enzym) 200 ml', $names);
    }

    public function test_unit_on_wall_entries_are_never_filtered_out(): void
    {
        // Daftar Unit On Wall sengaja memuat seluruh unit yang terpasang di ruangan,
        // lintas kontrak, supaya Ganti Unit bisa memvalidasi serial mana pun di situ.
        // Menyaringnya akan membuat unit yang sah ditolak saat scan.
        $vguard = $this->adviceRoom(self::RENTAL_VGUARD);
        $group = collect([$vguard, $this->adviceRoom(self::RENTAL_ADS250)]);

        $products = [
            ['product_id' => 1, 'product_name' => 'Air Purification VG 800'],
            // Diffuser milik rental tetangga, tetapi terpasang di dinding ruangan ini.
            ['product_id' => 4, 'product_name' => 'SA250 terpasang', 'source' => 'unit_on_wall', 'serial_number' => 'SN-001'],
            ['product_id' => 5, 'product_name' => 'PURE Phyto Green (Enzym) 200 ml'],
        ];

        $names = collect($this->narrow($products, $group, $vguard))->pluck('product_name')->all();

        $this->assertSame([
            'Air Purification VG 800',
            'SA250 terpasang',
        ], $names);
    }

    public function test_room_without_a_resolved_rental_is_left_untouched(): void
    {
        $unknown = (object) ['rental_product_id' => null];
        $group = collect([$unknown, $this->adviceRoom(self::RENTAL_ADS250)]);
        $products = $this->roomProducts();

        $this->assertSame($products, $this->narrow($products, $group, $unknown));
    }
}
