<?php

namespace Tests\Feature;

use App\Http\Controllers\Marketing\QuotationWizardController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuotationSurveySelectionDedupTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('data_restriction')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('nama_gedung')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->string('survey_number')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('building_id')->nullable();
            $table->foreignId('marketing_id')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->date('survey_date')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('survey_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->nullable();
            $table->string('room_name')->nullable();
            $table->string('room_type')->nullable();
            $table->integer('quantity_needed')->nullable();
            $table->json('specifications')->nullable();
            $table->timestamps();
        });

        Schema::create('user_access_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('access_type');
            $table->json('access_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manager')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouse_admins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id');
            $table->foreignId('user_id');
            $table->timestamps();
        });

        DB::table('customers')->insert([
            'id' => 1,
            'name' => 'Hotel JKT45',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('buildings')->insert([
            'id' => 1,
            'name' => 'Hotel jakarta II',
            'nama_gedung' => 'Hotel jakarta II',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'id' => 7,
            'name' => 'Marketing User',
            'email' => 'marketing@example.test',
            'password' => 'password',
            'data_restriction' => 'none',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'id' => 8,
            'name' => 'Other Marketing User',
            'email' => 'other-marketing@example.test',
            'password' => 'password',
            'data_restriction' => 'none',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        foreach (['warehouse_admins', 'warehouses', 'user_access_levels', 'survey_details', 'surveys', 'buildings', 'customers', 'users'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_survey_selection_hides_duplicate_survey_numbers(): void
    {
        $this->insertSurvey(10, 'JKT-SR/26-05/0006', now()->subMinute());
        $this->insertSurvey(11, 'JKT-SR/26-05/0006', now());
        $this->actingAs(User::findOrFail(7));

        $controller = app(QuotationWizardController::class);
        $response = $controller->getSurveysByCustomer(Request::create(
            '/marketing/quotations/wizard/get-surveys-by-customer',
            'GET',
            ['marketing_id' => 7]
        ));

        $payload = $response->getData(true);

        $this->assertCount(1, $payload);
        $this->assertSame(11, $payload[0]['id']);
        $this->assertSame('JKT-SR/26-05/0006', $payload[0]['survey_number']);
    }

    public function test_survey_selection_keeps_distinct_survey_numbers_for_same_customer_and_building(): void
    {
        DB::table('customers')->insert([
            'id' => 2,
            'name' => 'Hotel JKT45',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->insertSurvey(10, 'JKT-SR/26-05/0005', now()->subMinutes(2), customerId: 1, roomName: 'Lobby');
        $this->insertSurvey(11, 'JKT-SR/26-05/0006', now()->subMinute(), customerId: 2, roomName: 'Lantai 2');
        $this->insertSurvey(12, 'JKT-SR/26-05/0008', now(), customerId: 1, roomName: 'Lantai 1 Lobby');
        $this->actingAs(User::findOrFail(7));

        $controller = app(QuotationWizardController::class);
        $response = $controller->getSurveysByCustomer(Request::create(
            '/marketing/quotations/wizard/get-surveys-by-customer',
            'GET',
            ['marketing_id' => 7]
        ));

        $payload = $response->getData(true);

        $this->assertCount(3, $payload);
        $this->assertSame([
            'JKT-SR/26-05/0008',
            'JKT-SR/26-05/0006',
            'JKT-SR/26-05/0005',
        ], array_column($payload, 'survey_number'));
    }

    public function test_default_marketing_selection_includes_accessible_subordinate_surveys(): void
    {
        DB::table('users')->insert([
            'id' => 9,
            'name' => 'Marketing Manager',
            'email' => 'marketing-manager@example.test',
            'password' => 'password',
            'data_restriction' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('user_access_levels')->insert([
            'user_id' => 9,
            'access_type' => 'hierarchical',
            'access_config' => json_encode(['subordinates' => [7]]),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->insertSurvey(10, 'JKT-SR/26-06/0001', now()->subMinute(), marketingId: 7);
        $this->insertSurvey(11, 'JKT-SR/26-06/0002', now(), marketingId: 9);
        $this->actingAs(User::findOrFail(9));

        $controller = app(QuotationWizardController::class);
        $response = $controller->getSurveysByCustomer(Request::create(
            '/marketing/quotations/wizard/get-surveys-by-customer',
            'GET',
            ['marketing_id' => 9]
        ));

        $payload = $response->getData(true);

        $this->assertCount(2, $payload);
        $this->assertSame([
            'JKT-SR/26-06/0002',
            'JKT-SR/26-06/0001',
        ], array_column($payload, 'survey_number'));
    }

    public function test_renewal_customer_survey_selection_includes_same_customer_surveys_from_other_marketing(): void
    {
        $this->insertSurvey(10, 'SBY-SR/26-06/0001', now()->subMinutes(2), marketingId: 8);
        $this->insertSurvey(11, 'SBY-SR/26-06/0002', now(), marketingId: 7);
        $this->actingAs(User::findOrFail(7));

        $controller = app(QuotationWizardController::class);
        $response = $controller->getSurveysByCustomer(Request::create(
            '/marketing/quotations/wizard/get-surveys-by-customer',
            'GET',
            ['marketing_id' => 7, 'customer_id' => 1]
        ));

        $payload = $response->getData(true);

        $this->assertCount(2, $payload);
        $this->assertSame([
            'SBY-SR/26-06/0002',
            'SBY-SR/26-06/0001',
        ], array_column($payload, 'survey_number'));
    }

    // UAT 18 Nov: Step 2 memakai select2 AJAX (searchSurveys) alih-alih preload semua survey.
    public function test_search_surveys_paginates_and_keeps_dedup(): void
    {
        $this->insertSurvey(10, 'JKT-SR/26-05/0001', now()->subMinutes(4));
        $this->insertSurvey(11, 'JKT-SR/26-05/0002', now()->subMinutes(3));
        $this->insertSurvey(12, 'JKT-SR/26-05/0003', now()->subMinutes(2));
        $this->insertSurvey(13, 'JKT-SR/26-05/0003', now()->subMinute()); // duplikat nomor, lebih baru
        $this->actingAs(User::findOrFail(7));

        $first = $this->searchSurveys(['marketing_id' => 7, 'limit' => 2, 'page' => 1]);
        $this->assertSame([13, 11], array_column($first['results'], 'id'));
        $this->assertTrue($first['pagination']['more']);
        $this->assertSame('JKT-SR/26-05/0003 - Hotel JKT45 - Hotel jakarta II', $first['results'][0]['text']);
        $this->assertSame(1, $first['results'][0]['customer_id']);

        $second = $this->searchSurveys(['marketing_id' => 7, 'limit' => 2, 'page' => 2]);
        $this->assertSame([10], array_column($second['results'], 'id'));
        $this->assertFalse($second['pagination']['more']);
    }

    public function test_search_surveys_filters_by_term_on_number_customer_and_building(): void
    {
        DB::table('customers')->insert(['id' => 3, 'name' => 'Klinik Sehat', 'created_at' => now(), 'updated_at' => now()]);
        $this->insertSurvey(10, 'JKT-SR/26-05/0001', now()->subMinutes(2));
        $this->insertSurvey(11, 'SBY-SR/26-05/0002', now()->subMinute(), customerId: 3);
        $this->actingAs(User::findOrFail(7));

        $this->assertSame([11], array_column($this->searchSurveys(['marketing_id' => 7, 'term' => 'SBY-SR'])['results'], 'id'));
        $this->assertSame([11], array_column($this->searchSurveys(['marketing_id' => 7, 'term' => 'klinik'])['results'], 'id'));
        $this->assertSame([11, 10], array_column($this->searchSurveys(['marketing_id' => 7, 'term' => 'jakarta II'])['results'], 'id'));
    }

    public function test_search_surveys_locks_to_selected_customer_id_not_customer_name(): void
    {
        // Customer 2 sengaja bernama sama dengan customer 1: penguncian harus by id.
        DB::table('customers')->insert(['id' => 2, 'name' => 'Hotel JKT45', 'created_at' => now(), 'updated_at' => now()]);
        $this->insertSurvey(10, 'JKT-SR/26-05/0001', now()->subMinutes(3), customerId: 1);
        $this->insertSurvey(11, 'JKT-SR/26-05/0002', now()->subMinutes(2), customerId: 2);
        $this->insertSurvey(12, 'JKT-SR/26-05/0003', now()->subMinute(), customerId: 1);
        $this->actingAs(User::findOrFail(7));

        $payload = $this->searchSurveys(['marketing_id' => 7, 'selected_customer_id' => 1]);

        $this->assertSame([12, 10], array_column($payload['results'], 'id'));
    }

    public function test_search_surveys_applies_same_marketing_filter_as_legacy_endpoint(): void
    {
        $this->insertSurvey(10, 'JKT-SR/26-05/0001', now()->subMinutes(2), marketingId: 7);
        $this->insertSurvey(11, 'JKT-SR/26-05/0002', now()->subMinute(), marketingId: 8);
        $this->actingAs(User::findOrFail(7));

        // Marketing lain dipilih -> hanya survey marketing itu; marketing = diri sendiri ->
        // semua survey yang boleh diakses (sama dengan getSurveysByCustomer).
        $this->assertSame([11], array_column($this->searchSurveys(['marketing_id' => 8])['results'], 'id'));
        $this->assertSame([11, 10], array_column($this->searchSurveys(['marketing_id' => 7])['results'], 'id'));
        $this->assertSame(
            array_column($this->getSurveysByCustomerPayload(['marketing_id' => 7]), 'id'),
            array_column($this->searchSurveys(['marketing_id' => 7])['results'], 'id')
        );
    }

    private function getSurveysByCustomerPayload(array $query): array
    {
        return app(QuotationWizardController::class)->getSurveysByCustomer(Request::create(
            '/marketing/quotations/wizard/get-surveys-by-customer',
            'GET',
            $query
        ))->getData(true);
    }

    public function test_search_surveys_loads_selected_ids_for_restore(): void
    {
        $this->insertSurvey(10, 'JKT-SR/26-05/0001', now()->subMinutes(2));
        $this->insertSurvey(11, 'JKT-SR/26-05/0002', now()->subMinute());
        $this->actingAs(User::findOrFail(7));

        $payload = $this->searchSurveys(['ids' => [10, 11]]);

        $this->assertSame([10, 11], array_column($payload['results'], 'id'));
        $this->assertSame('JKT-SR/26-05/0001 - Hotel JKT45 - Hotel jakarta II', $payload['results'][0]['text']);
        $this->assertFalse($payload['pagination']['more']);
    }

    public function test_search_surveys_ids_within_context_drops_surveys_outside_new_marketing(): void
    {
        $this->insertSurvey(10, 'JKT-SR/26-05/0001', now()->subMinutes(2), marketingId: 7);
        $this->insertSurvey(11, 'JKT-SR/26-05/0002', now()->subMinute(), marketingId: 8);
        $this->actingAs(User::findOrFail(7));

        // Restore biasa (edit/kembali dari step lain): semua id yang boleh diakses kembali.
        $this->assertSame([10, 11], array_column($this->searchSurveys(['ids' => [10, 11]])['results'], 'id'));

        // Marketing di Step 1 diganti ke user 8: survey milik marketing lain dilepas.
        $payload = $this->searchSurveys(['ids' => [10, 11], 'within_context' => 1, 'marketing_id' => 8]);
        $this->assertSame([11], array_column($payload['results'], 'id'));
    }

    private function searchSurveys(array $query): array
    {
        $response = app(QuotationWizardController::class)->searchSurveys(Request::create(
            '/marketing/quotations/wizard/search-surveys',
            'GET',
            $query
        ));

        return $response->getData(true);
    }

    private function insertSurvey(
        int $id,
        string $number,
        $createdAt,
        int $customerId = 1,
        string $roomName = 'Lobby',
        int $marketingId = 7
    ): void
    {
        DB::table('surveys')->insert([
            'id' => $id,
            'survey_number' => $number,
            'customer_id' => $customerId,
            'building_id' => 1,
            'marketing_id' => $marketingId,
            'created_by' => $marketingId,
            'survey_date' => '2026-05-10',
            'status' => 'approved',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        DB::table('survey_details')->insert([
            'survey_id' => $id,
            'room_name' => $roomName,
            'room_type' => 'Office',
            'quantity_needed' => 1,
            'specifications' => json_encode([
                'floor' => '1',
                'intensity' => 'Medium',
                'installation_type' => 'Wall',
                'qty' => 1,
                'length' => '1',
                'width' => '1',
                'height' => '1',
                'remark' => '',
            ]),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
