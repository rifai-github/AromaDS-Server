<?php

namespace Tests\Feature;

use App\Models\AromaChange;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AromaChangeApplyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-06-20'));

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->nullable();
            $table->foreignId('quotation_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quotation_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->foreignId('aroma_product_id')->nullable();
            $table->string('aroma_variant')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('variant_name')->nullable();
            $table->string('brand_line')->nullable();
            $table->string('sku')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_advices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_advice_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->string('contract_number')->nullable();
            $table->string('status')->nullable();
            $table->integer('period')->nullable();
            $table->date('schedule_date')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('aroma_changes', function (Blueprint $table) {
            $table->id();
            $table->string('change_number')->nullable();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('building_id')->nullable();
            $table->foreignId('unit_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->foreignId('contract_room_id')->nullable();
            $table->string('previous_aroma_code')->nullable();
            $table->string('previous_aroma_name')->nullable();
            $table->foreignId('effective_schedule_id')->nullable();
            $table->string('new_aroma')->nullable();
            $table->string('new_aroma_code')->nullable();
            $table->string('new_aroma_name')->nullable();
            $table->foreignId('previous_product_type_id')->nullable();
            $table->foreignId('previous_product_category_id')->nullable();
            $table->foreignId('previous_product_id')->nullable();
            $table->foreignId('new_product_type_id')->nullable();
            $table->foreignId('new_product_category_id')->nullable();
            $table->foreignId('new_product_id')->nullable();
            $table->string('change_reason')->nullable();
            $table->string('change_description')->nullable();
            $table->string('change_notes')->nullable();
            $table->string('status')->nullable();
            $table->string('approval_notes')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->foreignId('requested_by')->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->foreignId('applied_by')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('aroma_changes');
        Schema::dropIfExists('job_schedules');
        Schema::dropIfExists('job_advices');
        Schema::dropIfExists('master_products');
        Schema::dropIfExists('quotation_rooms');
        Schema::dropIfExists('quotations');
        Schema::dropIfExists('contracts');

        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_apply_change_updates_quotation_room_even_when_effective_date_is_in_the_future(): void
    {
        DB::table('quotations')->insert(['id' => 6, 'created_at' => now(), 'updated_at' => now()]);

        DB::table('contracts')->insert([
            'id' => 3,
            'contract_number' => 'JKT-CA/26-06/0002',
            'quotation_id' => 6,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('master_products')->insert([
            [
                'id' => 20,
                'name' => 'Fragrance Ginger Blossom 100 ml',
                'variant_name' => 'Luxo',
                'brand_line' => 'Luxo',
                'sku' => 'REFGINGERBLOSSOM100',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 25,
                'name' => 'Fragrance Loco Floral 100 ml',
                'variant_name' => 'Luxo',
                'brand_line' => 'Luxo',
                'sku' => 'REFLOCOF100',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('quotation_rooms')->insert([
            'id' => 12,
            'quotation_id' => 6,
            'room_id' => 409,
            'aroma_product_id' => 20,
            'aroma_variant' => 'Luxo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_advices')->insert(['id' => 20, 'contract_id' => 3, 'created_at' => now(), 'updated_at' => now()]);

        // Future schedule (Period 2), same shape as the real JKT-AC/26-06/0002 bug report
        DB::table('job_schedules')->insert([
            'id' => 34,
            'job_advice_id' => 20,
            'room_id' => 409,
            'contract_number' => 'JKT-CA/26-06/0002',
            'status' => 'scheduled',
            'period' => 2,
            'schedule_date' => '2026-07-15',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $aromaChange = AromaChange::create([
            'change_number' => 'JKT-AC/26-06/0002',
            'contract_id' => 3,
            'building_id' => 1,
            'unit_id' => 0,
            'room_id' => 409,
            'contract_room_id' => 4,
            'previous_aroma_code' => 'REFGINGERBLOSSOM100',
            'previous_aroma_name' => 'Fragrance Ginger Blossom',
            'previous_product_id' => 20,
            'new_aroma' => 'Fragrance Loco Floral',
            'new_aroma_code' => 'REFLOCOF100',
            'new_aroma_name' => 'Fragrance Loco Floral',
            'new_product_id' => 25,
            'effective_schedule_id' => 34,
            'change_reason' => 'wangi tidak suka.',
            'status' => AromaChange::STATUS_APPROVED,
            'requested_by' => 1,
            'created_by' => 1,
        ]);

        $aromaChange->applyChange(1);

        $this->assertSame(AromaChange::STATUS_COMPLETED, $aromaChange->fresh()->status);

        $quotationRoom = DB::table('quotation_rooms')->where('id', 12)->first();

        // Before the fix this stayed at 20 (the old aroma) because applyChange() returned
        // early for any future effective date, before ever reaching the QuotationRoom update.
        $this->assertEquals(25, $quotationRoom->aroma_product_id);
        $this->assertSame('Fragrance Loco Floral', $quotationRoom->aroma_variant);

        // The future job schedule should also get an internal note about the change.
        $schedule = DB::table('job_schedules')->where('id', 34)->first();
        $this->assertStringContainsString('Fragrance Loco Floral', $schedule->internal_notes ?? '');
    }
}
