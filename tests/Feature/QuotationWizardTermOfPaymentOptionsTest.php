<?php

namespace Tests\Feature;

use App\Http\Controllers\Marketing\QuotationWizardController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuotationWizardTermOfPaymentOptionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('master_options', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('system_reserved')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('option_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('master_option_id');
            $table->unsignedBigInteger('parent_option_id')->nullable();
            $table->string('option_name');
            $table->text('option_description')->nullable();
            $table->string('label')->nullable();
            $table->string('code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('option_details');
        Schema::dropIfExists('master_options');

        parent::tearDown();
    }

    public function test_wizard_term_options_are_loaded_from_master_option(): void
    {
        Cache::forget('quotation-wizard:term-of-payment-options');

        $masterOptionId = \DB::table('master_options')->insertGetId([
            'name' => 'Term of Payment',
            'description' => 'Dynamic TOP options',
            'system_reserved' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('option_details')->insert([
            [
                'master_option_id' => $masterOptionId,
                'option_name' => '5 bulan 1x',
                'label' => '5 bulan 1x',
                'code' => '5',
                'option_description' => json_encode([
                    'description' => 'Pembayaran setiap 5 bulan',
                    'billing_mode' => 'fixed_interval',
                    'months' => 5,
                    'payment_count' => null,
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'master_option_id' => $masterOptionId,
                'option_name' => 'Tahunan',
                'label' => '1x Advance',
                'code' => 'advance',
                'option_description' => json_encode([
                    'description' => 'Pembayaran 1x advance',
                    'billing_mode' => 'advance',
                    'months' => null,
                    'payment_count' => null,
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'master_option_id' => $masterOptionId,
                'option_name' => '3x per periode contract',
                'label' => '3x dalam Periode Contract',
                'code' => 'installments',
                'option_description' => json_encode([
                    'description' => 'Pembayaran 3x dibagi rata dalam kontrak',
                    'billing_mode' => 'per_contract_period',
                    'months' => null,
                    'payment_count' => 3,
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $controller = app(QuotationWizardController::class);
        $method = new \ReflectionMethod($controller, 'getTermOfPaymentOptions');
        $method->setAccessible(true);

        $options = $method->invoke($controller);

        $this->assertSame([
            [
                'value' => '5 bulan 1x',
                'label' => '5 bulan 1x',
                'months' => 5,
                'is_advance' => false,
                'billing_mode' => 'fixed_interval',
                'payment_count' => null,
            ],
            [
                'value' => 'Tahunan',
                'label' => '1x Advance',
                'months' => null,
                'is_advance' => true,
                'billing_mode' => 'advance',
                'payment_count' => null,
            ],
            [
                'value' => '3x per periode contract',
                'label' => '3x dalam Periode Contract',
                'months' => null,
                'is_advance' => false,
                'billing_mode' => 'per_contract_period',
                'payment_count' => 3,
            ],
        ], $options->all());
    }

    public function test_wizard_top_months_for_per_contract_period_is_calculated_from_rental_period(): void
    {
        Cache::forget('quotation-wizard:term-of-payment-options');

        $masterOptionId = \DB::table('master_options')->insertGetId([
            'name' => 'Term of Payment',
            'description' => 'Dynamic TOP options',
            'system_reserved' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('option_details')->insert([
            'master_option_id' => $masterOptionId,
            'option_name' => '3x per periode contract',
            'label' => '3x dalam Periode Contract',
            'code' => 'installments',
            'option_description' => json_encode([
                'billing_mode' => 'per_contract_period',
                'payment_count' => 3,
            ]),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $controller = app(QuotationWizardController::class);
        $method = new \ReflectionMethod($controller, 'resolveTopMonthsFromRequest');
        $method->setAccessible(true);

        $request = new \Illuminate\Http\Request([
            'rental_period' => 18,
            'rental_unit' => 'bulan',
            'term_of_payment' => '3x per periode contract',
        ]);

        $this->assertSame(6, $method->invoke($controller, $request));
    }
}
