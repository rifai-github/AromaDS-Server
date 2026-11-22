<?php

namespace Tests\Unit;

use App\Models\Quotation;
use App\Services\Marketing\QuotationBottomPriceEvaluator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * MOM 18 Nov 2026 p3d + jawaban klien 22 Nov #18: bottom price ikut branch MASTER BUILDING (lokasi
 * pasang), bukan branch customer/marketing. SQ yang gedungnya di beberapa cabang memakai bottom
 * price tertinggi di antara cabang gedung itu. Contoh: marketing + customer Jakarta, pasang di
 * Bandung.
 */
class QuotationBottomPriceBuildingBranchTest extends TestCase
{
    private const JAKARTA = 1;

    private const BANDUNG = 2;

    private const SURABAYA = 3;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['quotation_details', 'rental_bottom_prices', 'quotations', 'master_rentals', 'quotation_approval_levels', 'surveys', 'buildings', 'branches'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('city_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('building_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('rental_unit')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_rentals', function (Blueprint $table) {
            $table->id();
            $table->string('rental_name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quotation_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quotation_id');
            $table->unsignedBigInteger('survey_id')->nullable();
            $table->unsignedBigInteger('master_rental_id');
            $table->string('room_name');
            $table->integer('quantity')->default(1);
            $table->integer('qty_free')->default(0);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_price', 15, 2);
            $table->timestamps();
        });

        Schema::create('rental_bottom_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('master_rental_id');
            $table->unsignedBigInteger('branch_id');
            $table->string('offer_type')->default('bulan');
            $table->decimal('bottom_price', 15, 2);
            $table->decimal('replacement_price', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quotation_approval_levels', function (Blueprint $table) {
            $table->id();
            $table->string('level_code', 50)->unique();
            $table->string('level_name', 100);
            $table->decimal('max_discount_percentage', 5, 2)->default(0);
            $table->string('permission_name', 150)->unique();
            $table->integer('sort_order')->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('quotation_approval_levels')->insert([
            'level_code' => 'director', 'level_name' => 'Director', 'max_discount_percentage' => 100,
            'permission_name' => 'marketing.quotations.approve-level-director', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('branches')->insert([
            ['id' => self::JAKARTA, 'name' => 'Jakarta', 'city_id' => 100, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => self::BANDUNG, 'name' => 'Bandung', 'city_id' => 200, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => self::SURABAYA, 'name' => 'Surabaya', 'city_id' => 300, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('master_rentals')->insert(['id' => 10, 'rental_name' => 'ADS 106 - 150ml', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_building_branch_price_is_used_when_it_is_higher(): void
    {
        $quotation = $this->quotationInJakartaInstalledIn(buildingBranchId: self::BANDUNG, unitPrice: 1_100_000);
        $this->bottomPrice(self::JAKARTA, 1_000_000);
        $this->bottomPrice(self::BANDUNG, 1_200_000);

        $result = app(QuotationBottomPriceEvaluator::class)->evaluate($quotation);

        // 1.100.000 >= Jakarta 1.000.000 tetapi < Bandung 1.200.000 -> wajib approval.
        $this->assertTrue($result['requires_approval']);
        $this->assertSame(1_200_000.0, $result['issues'][0]['bottom_price']);
        $this->assertSame(self::BANDUNG, (int) $result['issues'][0]['bottom_price_branch_id']);
    }

    public function test_the_customer_or_marketing_branch_is_ignored_even_when_higher(): void
    {
        $quotation = $this->quotationInJakartaInstalledIn(buildingBranchId: self::BANDUNG, unitPrice: 1_100_000);
        $this->bottomPrice(self::JAKARTA, 1_500_000);
        $this->bottomPrice(self::BANDUNG, 1_000_000);

        $result = app(QuotationBottomPriceEvaluator::class)->evaluate($quotation);

        // Hanya Bandung (cabang gedung) yang dihitung: 1.100.000 >= 1.000.000 -> lolos.
        $this->assertFalse($result['requires_approval']);
    }

    public function test_multi_branch_quotation_uses_the_highest_building_branch_bottom_price_for_every_line(): void
    {
        $quotation = $this->quotationInJakartaInstalledIn(buildingBranchId: self::BANDUNG, unitPrice: 1_200_000);
        DB::table('buildings')->insert(['id' => 6, 'name' => 'Gedung Surabaya', 'branch_id' => self::SURABAYA, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('surveys')->insert(['id' => 8, 'building_id' => 6, 'created_at' => now(), 'updated_at' => now()]);
        $this->detail(1, 8, 1_200_000);
        $this->bottomPrice(self::BANDUNG, 1_000_000);
        $this->bottomPrice(self::SURABAYA, 1_500_000);

        $result = app(QuotationBottomPriceEvaluator::class)->evaluate($quotation->fresh());

        // Kedua baris (Bandung & Surabaya) dinilai dengan 1.500.000.
        $this->assertTrue($result['requires_approval']);
        $this->assertSame([1_500_000.0, 1_500_000.0], array_column($result['issues'], 'bottom_price'));
    }

    public function test_equal_prices_are_attributed_to_the_building_branch(): void
    {
        $quotation = $this->quotationInJakartaInstalledIn(buildingBranchId: self::BANDUNG, unitPrice: 900_000);
        $this->bottomPrice(self::JAKARTA, 1_000_000);
        $this->bottomPrice(self::BANDUNG, 1_000_000);

        $result = app(QuotationBottomPriceEvaluator::class)->evaluate($quotation);

        $this->assertSame(self::BANDUNG, (int) $result['issues'][0]['bottom_price_branch_id']);
    }

    public function test_building_branch_without_bottom_price_is_missing_not_borrowed_from_the_quotation_branch(): void
    {
        $quotation = $this->quotationInJakartaInstalledIn(buildingBranchId: self::BANDUNG, unitPrice: 1_100_000);
        $this->bottomPrice(self::JAKARTA, 1_000_000);

        $result = app(QuotationBottomPriceEvaluator::class)->evaluate($quotation);

        $this->assertTrue($result['requires_approval']);
        $this->assertSame('missing_bottom_price', $result['issues'][0]['type']);
    }

    public function test_building_without_branch_id_uses_the_branch_serving_its_city(): void
    {
        $quotation = $this->quotationInJakartaInstalledIn(buildingBranchId: null, unitPrice: 1_100_000, buildingCityId: 200);
        $this->bottomPrice(self::JAKARTA, 1_000_000);
        $this->bottomPrice(self::BANDUNG, 1_200_000);

        $result = app(QuotationBottomPriceEvaluator::class)->evaluate($quotation);

        $this->assertTrue($result['requires_approval']);
        $this->assertSame(self::BANDUNG, (int) $result['issues'][0]['bottom_price_branch_id']);
    }

    public function test_line_without_survey_keeps_using_the_quotation_branch(): void
    {
        DB::table('quotations')->insert([
            'id' => 1, 'quotation_number' => 'JKT-SQ/26-11/0001', 'branch_id' => self::JAKARTA,
            'rental_unit' => 'bulan', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->detail(1, null, 900_000);
        $this->bottomPrice(self::JAKARTA, 1_000_000);
        $this->bottomPrice(self::BANDUNG, 5_000_000);

        $result = app(QuotationBottomPriceEvaluator::class)->evaluate(Quotation::findOrFail(1));

        $this->assertSame(1_000_000.0, $result['issues'][0]['bottom_price']);
    }

    private function quotationInJakartaInstalledIn(?int $buildingBranchId, int $unitPrice, ?int $buildingCityId = null): Quotation
    {
        DB::table('buildings')->insert([
            'id' => 5, 'name' => 'Gedung Bandung', 'branch_id' => $buildingBranchId, 'city_id' => $buildingCityId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('surveys')->insert(['id' => 7, 'building_id' => 5, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('quotations')->insert([
            'id' => 1, 'quotation_number' => 'JKT-SQ/26-11/0001', 'branch_id' => self::JAKARTA,
            'rental_unit' => 'bulan', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->detail(1, 7, $unitPrice);

        return Quotation::findOrFail(1);
    }

    private function detail(int $quotationId, ?int $surveyId, int $unitPrice): void
    {
        DB::table('quotation_details')->insert([
            'quotation_id' => $quotationId, 'survey_id' => $surveyId, 'master_rental_id' => 10,
            'room_name' => 'Lobby', 'quantity' => 1, 'qty_free' => 0,
            'unit_price' => $unitPrice, 'total_price' => $unitPrice,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function bottomPrice(int $branchId, int $price): void
    {
        DB::table('rental_bottom_prices')->insert([
            'master_rental_id' => 10, 'branch_id' => $branchId, 'offer_type' => 'bulan',
            'bottom_price' => $price, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
