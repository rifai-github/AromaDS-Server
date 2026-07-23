<?php

namespace Tests\Feature;

use App\Http\Controllers\Finance\TaxSettingController;
use App\Models\TaxSetting;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TaxSettingDefaultVatWindowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('tax_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('tax_code')->nullable();
            $table->string('tax_type')->nullable();
            $table->decimal('tax_rate', 8, 2)->default(0);
            $table->boolean('is_default')->default(false);
            $table->text('description')->nullable();
            $table->date('effective_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('active');
            $table->boolean('is_compound')->default(false);
            $table->string('calculation_method')->default('percentage');
            $table->string('rounding_method')->default('nearest');
            $table->unsignedInteger('decimal_places')->default(2);
            $table->decimal('minimum_amount', 15, 2)->nullable();
            $table->decimal('maximum_amount', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('tax_settings');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    private function makeVat(array $overrides = []): TaxSetting
    {
        return TaxSetting::create(array_merge([
            'name' => 'PPN Lama',
            'tax_code' => 'PPN11',
            'tax_type' => 'vat',
            'tax_rate' => 11,
            'is_default' => true,
            'effective_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'status' => 'active',
            'calculation_method' => 'percentage',
            'rounding_method' => 'nearest',
            'decimal_places' => 2,
        ], $overrides));
    }

    private function ajaxStore(array $payload)
    {
        $request = Request::create('/finance/tax-settings', 'POST', array_merge([
            'name' => 'PPN Baru',
            'tax_code' => 'PPN12',
            'tax_type' => 'vat',
            'tax_rate' => 12,
            'is_default' => '1',
            'effective_date' => '2026-07-01',
            'end_date' => null,
            'status' => 'active',
            'is_compound' => '0',
            'calculation_method' => 'percentage',
            'rounding_method' => 'nearest',
            'decimal_places' => 2,
        ], $payload));
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        return (new TaxSettingController)->store($request);
    }

    public function test_rejects_overlapping_active_default_vat_periods(): void
    {
        $this->makeVat(['end_date' => null]);

        $response = $this->ajaxStore([
            'effective_date' => '2026-07-01',
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertStringContainsString('Default PPN sudah ada', $response->getData(true)['message']);
    }

    public function test_allows_non_overlapping_default_vat_periods_without_disabling_old_default(): void
    {
        $old = $this->makeVat();

        $response = $this->ajaxStore([]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($old->fresh()->is_default);
        $this->assertTrue(TaxSetting::where('tax_code', 'PPN12')->first()->is_default);
    }
}
