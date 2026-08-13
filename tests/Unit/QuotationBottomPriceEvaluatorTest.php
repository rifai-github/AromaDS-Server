<?php

namespace Tests\Unit;

use App\Models\Quotation;
use App\Services\Marketing\QuotationBottomPriceEvaluator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuotationBottomPriceEvaluatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['quotation_details', 'rental_bottom_prices', 'quotations', 'master_rentals', 'quotation_approval_levels'] as $table) {
            Schema::dropIfExists($table);
        }

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

        $this->seedApprovalLevels();
    }

    public function test_it_allows_quotation_details_at_or_above_bottom_price(): void
    {
        $quotation = $this->seedQuotationWithDetail(1_500_000);
        $this->seedBottomPrice(1_000_000);

        $result = app(QuotationBottomPriceEvaluator::class)->evaluate($quotation);

        $this->assertFalse($result['requires_approval']);
        $this->assertSame([], $result['issues']);
        $this->assertNull($result['required_level']);
    }

    public function test_it_requires_approval_when_detail_is_below_bottom_price(): void
    {
        // 1.000.000 against a 1.500.000 floor is a 33,33% discount -> GM.
        $quotation = $this->seedQuotationWithDetail(1_000_000);
        $this->seedBottomPrice(1_500_000);

        $result = app(QuotationBottomPriceEvaluator::class)->evaluate($quotation);

        $this->assertTrue($result['requires_approval']);
        $this->assertSame('below_bottom_price', $result['issues'][0]['type']);
        $this->assertSame(33.3333, $result['issues'][0]['discount_percentage']);
        $this->assertSame('gm', $result['required_level']['level_code']);
    }

    public function test_it_requires_approval_when_bottom_price_is_missing(): void
    {
        $quotation = $this->seedQuotationWithDetail(1_500_000);

        $result = app(QuotationBottomPriceEvaluator::class)->evaluate($quotation);

        $this->assertTrue($result['requires_approval']);
        $this->assertSame('missing_bottom_price', $result['issues'][0]['type']);
        // Undeterminable discount escalates to the most senior level.
        $this->assertSame('director', $result['required_level']['level_code']);
    }

    public function test_a_zero_bottom_price_is_treated_as_missing_rather_than_dividing_by_zero(): void
    {
        $quotation = $this->seedQuotationWithDetail(1_500_000);
        $this->seedBottomPrice(0);

        $result = app(QuotationBottomPriceEvaluator::class)->evaluate($quotation);

        $this->assertTrue($result['requires_approval']);
        $this->assertSame('missing_bottom_price', $result['issues'][0]['type']);
        $this->assertSame('director', $result['required_level']['level_code']);
    }

    public function test_free_lines_never_trigger_approval(): void
    {
        $quotation = $this->seedQuotationWithDetail(0, qtyFree: 1, quantity: 0);
        $this->seedBottomPrice(1_000_000);

        $result = app(QuotationBottomPriceEvaluator::class)->evaluate($quotation);

        $this->assertFalse($result['requires_approval']);
        $this->assertSame([], $result['issues']);
    }

    public function test_a_paid_quotation_carrying_a_free_line_is_judged_only_on_the_paid_line(): void
    {
        $quotation = $this->seedQuotationWithDetail(1_500_000);
        $this->addDetail($quotation, 0, qtyFree: 1, quantity: 0);
        $this->seedBottomPrice(1_000_000);

        $result = app(QuotationBottomPriceEvaluator::class)->evaluate($quotation);

        $this->assertFalse($result['requires_approval']);
    }

    public function test_discount_exactly_on_a_level_boundary_stays_with_that_level(): void
    {
        // 800.000 against 1.000.000 is exactly 20% - Manager's ceiling, inclusive.
        $quotation = $this->seedQuotationWithDetail(800_000);
        $this->seedBottomPrice(1_000_000);

        $result = app(QuotationBottomPriceEvaluator::class)->evaluate($quotation);

        $this->assertTrue($result['requires_approval']);
        $this->assertSame(20.0, $result['issues'][0]['discount_percentage']);
        $this->assertSame('manager', $result['required_level']['level_code']);
    }

    public function test_discount_just_past_a_boundary_escalates_to_the_next_level(): void
    {
        $quotation = $this->seedQuotationWithDetail(799_900);
        $this->seedBottomPrice(1_000_000);

        $result = app(QuotationBottomPriceEvaluator::class)->evaluate($quotation);

        $this->assertSame(20.01, $result['issues'][0]['discount_percentage']);
        $this->assertSame('gm', $result['required_level']['level_code']);
    }

    public function test_the_required_level_is_decided_by_the_aggregate_discount_not_the_worst_line(): void
    {
        // Line A: 900.000 vs 1.000.000 floor (10% under, would be Manager alone).
        // Line B: 400.000 vs 1.000.000 floor (60% under, would be Director alone).
        // Aggregate: 1.300.000 / 2.000.000 -> 35% under -> GM, not Director.
        $quotation = $this->seedQuotationWithDetail(900_000);
        $this->addDetail($quotation, 400_000);
        $this->seedBottomPrice(1_000_000);

        $result = app(QuotationBottomPriceEvaluator::class)->evaluate($quotation);

        $this->assertCount(2, $result['issues']);
        $this->assertSame(35.0, $result['issues'][0]['discount_percentage']);
        $this->assertSame(35.0, $result['issues'][1]['discount_percentage']);
        $this->assertSame('gm', $result['required_level']['level_code']);
    }

    public function test_a_line_below_its_own_floor_is_auto_approved_when_the_total_clears_the_total_floor(): void
    {
        // Line A: 700.000 vs 1.000.000 floor (individually 30% under).
        // Line B: 1.400.000 vs 1.000.000 floor (individually above floor).
        // Aggregate: 2.100.000 total vs 2.000.000 total floor -> total clears -> auto-approve.
        $quotation = $this->seedQuotationWithDetail(700_000);
        $this->addDetail($quotation, 1_400_000);
        $this->seedBottomPrice(1_000_000);

        $result = app(QuotationBottomPriceEvaluator::class)->evaluate($quotation);

        $this->assertFalse($result['requires_approval']);
        $this->assertSame([], $result['issues']);
        $this->assertNull($result['required_level']);
    }

    public function test_totals_are_weighted_by_each_lines_quantity(): void
    {
        // Line A: unit 900.000 x qty 1 vs floor 1.000.000 x 1 = 900.000 / 1.000.000.
        // Line B: unit 400.000 x qty 3 vs floor 1.000.000 x 3 = 1.200.000 / 3.000.000.
        // Aggregate: 2.100.000 / 4.000.000 -> 47,5% under -> still GM.
        $quotation = $this->seedQuotationWithDetail(900_000);
        $this->addDetail($quotation, 400_000, quantity: 3);
        $this->seedBottomPrice(1_000_000);

        $result = app(QuotationBottomPriceEvaluator::class)->evaluate($quotation);

        $this->assertSame(47.5, $result['issues'][0]['discount_percentage']);
        $this->assertSame('gm', $result['required_level']['level_code']);
    }

    public function test_it_fails_closed_when_no_level_covers_the_discount(): void
    {
        DB::table('quotation_approval_levels')->where('level_code', 'director')->delete();
        DB::table('quotation_approval_levels')->where('level_code', 'gm')->delete();

        $quotation = $this->seedQuotationWithDetail(100_000); // 90% discount
        $this->seedBottomPrice(1_000_000);

        $result = app(QuotationBottomPriceEvaluator::class)->evaluate($quotation);

        $this->assertTrue($result['requires_approval']);
        $this->assertNull($result['required_level']);
    }

    private function seedApprovalLevels(): void
    {
        $levels = [
            ['manager', 'Manager', 20, 10],
            ['gm', 'General Manager', 50, 20],
            ['director', 'Director', 100, 30],
        ];

        foreach ($levels as [$code, $name, $maxDiscount, $sort]) {
            DB::table('quotation_approval_levels')->insert([
                'level_code' => $code,
                'level_name' => $name,
                'max_discount_percentage' => $maxDiscount,
                'permission_name' => 'marketing.quotations.approve-level-'.$code,
                'sort_order' => $sort,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedQuotationWithDetail(int $unitPrice, int $qtyFree = 0, int $quantity = 1): Quotation
    {
        DB::table('master_rentals')->insert([
            'id' => 10,
            'rental_name' => 'Rental07 - 1 x 1 Bulan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('quotations')->insert([
            'id' => 38,
            'quotation_number' => 'SBY-SQ/26-07/0004',
            'branch_id' => 2,
            'rental_unit' => 'bulan',
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $quotation = Quotation::findOrFail(38);

        $this->addDetail($quotation, $unitPrice, $qtyFree, $quantity);

        return $quotation;
    }

    private function addDetail(Quotation $quotation, int $unitPrice, int $qtyFree = 0, int $quantity = 1): void
    {
        DB::table('quotation_details')->insert([
            'quotation_id' => $quotation->id,
            'master_rental_id' => 10,
            'room_name' => 'Ruang Dokumentasi',
            'quantity' => $quantity,
            'qty_free' => $qtyFree,
            'unit_price' => $unitPrice,
            'total_price' => $unitPrice * max($quantity, 1),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $quotation->unsetRelation('quotationDetails');
    }

    private function seedBottomPrice(int $bottomPrice): void
    {
        DB::table('rental_bottom_prices')->insert([
            'master_rental_id' => 10,
            'branch_id' => 2,
            'offer_type' => 'bulan',
            'bottom_price' => $bottomPrice,
            'replacement_price' => 3_000_000,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
