<?php

namespace Tests\Feature;

use App\Http\Controllers\Marketing\QuotationWizardController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuotationWizardPicContactsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customer_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable();
            $table->string('name');
            $table->string('position')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customer_customer_contact', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id');
            $table->foreignId('customer_contact_id');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        DB::table('customers')->insert([
            'id' => 1,
            'name' => 'Renewal Customer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('customers')->insert([
            'id' => 2,
            'name' => 'Survey Customer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('contracts')->insert([
            'id' => 20,
            'customer_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('customer_contacts')->insert([
            'id' => 30,
            'customer_id' => 1,
            'name' => 'PIC Renewal',
            'position' => 'Manager',
            'phone' => '08123456789',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('surveys')->insert([
            'id' => 40,
            'customer_id' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('customer_contacts')->insert([
            'id' => 31,
            'customer_id' => 2,
            'name' => 'PIC Survey',
            'position' => 'Supervisor',
            'phone' => '08987654321',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        foreach (['customer_customer_contact', 'customer_contacts', 'surveys', 'contracts', 'customers'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_renewal_without_survey_loads_pic_from_source_contract_customer(): void
    {
        $this->app->instance('request', Request::create(
            '/marketing/quotations/wizard/get-pic-contacts',
            'GET',
            ['existing_contract_id' => 20]
        ));

        $response = app(QuotationWizardController::class)->getPicContacts();
        $payload = $response->getData(true);

        $this->assertCount(1, $payload['customer_contacts']);
        $this->assertSame('PIC Renewal', $payload['customer_contacts'][0]['name']);
    }

    public function test_selected_survey_contacts_keep_priority_over_contract_fallback(): void
    {
        $this->app->instance('request', Request::create(
            '/marketing/quotations/wizard/get-pic-contacts',
            'GET',
            [
                'survey_ids' => [40],
                'existing_contract_id' => 20,
            ]
        ));

        $response = app(QuotationWizardController::class)->getPicContacts();
        $payload = $response->getData(true);

        $this->assertCount(1, $payload['customer_contacts']);
        $this->assertSame('PIC Survey', $payload['customer_contacts'][0]['name']);
    }

    public function test_pic_loader_uses_contract_fallback_only_when_surveys_are_empty(): void
    {
        $view = file_get_contents(resource_path('views/marketing/quotations/wizard/create.blade.php'));

        $this->assertStringContainsString(
            'if (selectedSurveys.length === 0 && !renewalContractId)',
            $view
        );
        $this->assertStringContainsString('existing_contract_id: renewalContractId', $view);
    }
}
