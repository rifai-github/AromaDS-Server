<?php

namespace Tests\Feature;

use App\Http\Controllers\Marketing\QuotationWizardController;
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
    }

    protected function tearDown(): void
    {
        foreach (['survey_details', 'surveys', 'buildings', 'customers'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_survey_selection_hides_identical_duplicate_surveys(): void
    {
        $this->insertSurvey(10, 'JKT-SR/26-05/0005', now()->subMinute());
        $this->insertSurvey(11, 'JKT-SR/26-05/0006', now());

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

    private function insertSurvey(int $id, string $number, $createdAt): void
    {
        DB::table('surveys')->insert([
            'id' => $id,
            'survey_number' => $number,
            'customer_id' => 1,
            'building_id' => 1,
            'marketing_id' => 7,
            'survey_date' => '2026-05-10',
            'status' => 'approved',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        DB::table('survey_details')->insert([
            'survey_id' => $id,
            'room_name' => 'Lobby',
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
