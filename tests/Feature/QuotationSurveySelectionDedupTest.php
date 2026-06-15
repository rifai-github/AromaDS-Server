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
