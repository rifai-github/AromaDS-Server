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

        foreach (['quotation_details', 'rental_bottom_prices', 'quotations', 'master_rentals'] as $table) {
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
    }

    public function test_it_allows_quotation_details_at_or_above_bottom_price(): void
    {
        $quotation = $this->seedQuotationWithDetail(1_500_000);
        $this->seedBottomPrice(1_000_000);

        $result = app(QuotationBottomPriceEvaluator::class)->evaluate($quotation);

        $this->assertFalse($result['requires_approval']);
        $this->assertSame([], $result['issues']);
    }

    public function test_it_requires_approval_when_detail_is_below_bottom_price(): void
    {
        $quotation = $this->seedQuotationWithDetail(1_000_000);
        $this->seedBottomPrice(1_500_000);

        $result = app(QuotationBottomPriceEvaluator::class)->evaluate($quotation);

        $this->assertTrue($result['requires_approval']);
        $this->assertSame('below_bottom_price', $result['issues'][0]['type']);
    }

    public function test_it_requires_approval_when_bottom_price_is_missing(): void
    {
        $quotation = $this->seedQuotationWithDetail(1_500_000);

        $result = app(QuotationBottomPriceEvaluator::class)->evaluate($quotation);

        $this->assertTrue($result['requires_approval']);
        $this->assertSame('missing_bottom_price', $result['issues'][0]['type']);
    }

    private function seedQuotationWithDetail(int $unitPrice): Quotation
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

        DB::table('quotation_details')->insert([
            'quotation_id' => 38,
            'master_rental_id' => 10,
            'room_name' => 'Ruang Dokumentasi',
            'quantity' => 1,
            'qty_free' => 0,
            'unit_price' => $unitPrice,
            'total_price' => $unitPrice,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Quotation::findOrFail(38);
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
