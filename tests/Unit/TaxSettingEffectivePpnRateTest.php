<?php

namespace Tests\Unit;

use App\Models\TaxSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class TaxSettingEffectivePpnRateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // migrations/ is empty in this checkout, so build the one table under test.
        Schema::create('tax_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('tax_code')->nullable();
            $table->string('tax_type')->nullable();
            $table->decimal('tax_rate', 8, 2)->default(0);
            $table->boolean('is_default')->default(false);
            $table->date('effective_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('tax_settings');

        parent::tearDown();
    }

    private function makeVat(array $overrides = []): TaxSetting
    {
        return TaxSetting::create(array_merge([
            'name' => 'VAT Standard Rate',
            'tax_code' => 'VAT001',
            'tax_type' => 'vat',
            'tax_rate' => 11.00,
            'is_default' => true,
            'effective_date' => '2024-01-01',
            'end_date' => null,
            'status' => 'active',
        ], $overrides));
    }

    public function test_returns_configured_default_vat_rate_as_multiplier(): void
    {
        $this->makeVat();

        $this->assertSame(0.11, TaxSetting::getEffectivePpnRate());
    }

    public function test_reflects_a_changed_rate_without_code_changes(): void
    {
        $this->makeVat(['tax_rate' => 12.00]);

        $this->assertSame(0.12, TaxSetting::getEffectivePpnRate());
    }

    public function test_falls_back_to_eleven_percent_when_no_setting_exists(): void
    {
        $this->assertSame(0.11, TaxSetting::getEffectivePpnRate());
    }

    public function test_ignores_inactive_and_non_default_settings(): void
    {
        $this->makeVat(['tax_rate' => 12.00, 'status' => 'inactive']);
        $this->makeVat(['tax_rate' => 20.00, 'is_default' => false]);

        // Neither qualifies, so the fallback applies.
        $this->assertSame(0.11, TaxSetting::getEffectivePpnRate());
    }

    public function test_uses_the_rate_effective_on_the_given_invoice_date(): void
    {
        // Old rate closed off at end of 2025, new rate starts 2026.
        $this->makeVat(['tax_rate' => 11.00, 'end_date' => '2025-12-31']);
        $this->makeVat(['tax_rate' => 12.00, 'effective_date' => '2026-01-01']);

        $this->assertSame(0.11, TaxSetting::getEffectivePpnRate('2025-06-15'));
        $this->assertSame(0.12, TaxSetting::getEffectivePpnRate('2026-06-15'));
    }

    public function test_accepts_a_carbon_instance_as_the_effective_date(): void
    {
        $this->makeVat(['tax_rate' => 11.00, 'end_date' => '2025-12-31']);
        $this->makeVat(['tax_rate' => 12.00, 'effective_date' => '2026-01-01']);

        $this->assertSame(0.12, TaxSetting::getEffectivePpnRate(Carbon::parse('2026-03-01')));
    }

    public function test_null_date_resolves_against_today(): void
    {
        // Effective window that has already closed must not be picked up.
        $this->makeVat(['tax_rate' => 20.00, 'end_date' => '2020-12-31']);

        $this->assertSame(0.11, TaxSetting::getEffectivePpnRate(null));
    }

    public function test_supports_a_zero_rated_vat_setting(): void
    {
        $this->makeVat(['tax_rate' => 0.00]);

        $this->assertSame(0.0, TaxSetting::getEffectivePpnRate());
    }
}
