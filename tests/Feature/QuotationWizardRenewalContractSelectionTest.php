<?php

namespace Tests\Feature;

use App\Http\Controllers\Marketing\QuotationWizardController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class QuotationWizardRenewalContractSelectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->nullable();
            $table->foreignId('quotation_id')->nullable();
            $table->foreignId('marketing_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('quotations')->insert([
            'id' => 10,
            'branch_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('contracts')->insert([
            'id' => 20,
            'contract_number' => 'BDG-CA/26-05/0001',
            'quotation_id' => 10,
            'marketing_id' => 7,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('quotations');

        parent::tearDown();
    }

    public function test_renewal_contract_must_match_selected_marketing(): void
    {
        $this->expectException(ValidationException::class);

        $this->invokeSelectionValidation(new Request([
            'quotation_type' => 'renewal',
            'existing_contract_id' => 20,
            'marketing_id' => 8,
            'branch_id' => 1,
        ]));
    }

    public function test_renewal_contract_must_match_selected_branch(): void
    {
        $this->expectException(ValidationException::class);

        $this->invokeSelectionValidation(new Request([
            'quotation_type' => 'renewal',
            'existing_contract_id' => 20,
            'marketing_id' => 7,
            'branch_id' => 2,
        ]));
    }

    public function test_matching_renewal_contract_passes_selection_validation(): void
    {
        $this->invokeSelectionValidation(new Request([
            'quotation_type' => 'renewal',
            'existing_contract_id' => 20,
            'marketing_id' => 7,
            'branch_id' => 1,
        ]));

        $this->assertTrue(true);
    }

    private function invokeSelectionValidation(Request $request): void
    {
        $controller = new QuotationWizardController();
        $method = new \ReflectionMethod($controller, 'ensureRenewalSourceMatchesSelection');
        $method->setAccessible(true);
        $method->invoke($controller, $request);
    }
}
