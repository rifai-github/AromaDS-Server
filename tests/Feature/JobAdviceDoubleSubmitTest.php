<?php

namespace Tests\Feature;

use App\Http\Controllers\Marketing\JobAdviceController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class JobAdviceDoubleSubmitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('quotation_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number')->nullable();
            $table->foreignId('survey_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->nullable();
            $table->foreignId('province_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->foreignId('city_id')->nullable();
            $table->foreignId('province_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contract_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_advices', function (Blueprint $table) {
            $table->id();
            $table->string('job_advice_number')->nullable();
            $table->string('type')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('company_name')->nullable();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('quotation_id')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('request_by')->nullable();
            $table->foreignId('customer_contact_id')->nullable();
            $table->foreignId('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->date('expected_date')->nullable();
            $table->date('first_service_date')->nullable();
            $table->date('remove_date')->nullable();
            $table->string('status')->nullable();
            $table->boolean('with_invoicing')->default(false);
            $table->boolean('with_materials')->default(false);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_advice_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_advice_id')->nullable();
            $table->foreignId('contract_room_id')->nullable();
            $table->foreignId('quotation_room_id')->nullable();
            $table->foreignId('quotation_rental_id')->nullable();
            $table->foreignId('quotation_detail_id')->nullable();
            $table->foreignId('rental_product_id')->nullable();
            $table->integer('quantity')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Administrator',
            'email' => 'admin@example.test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('customers')->insert([
            'id' => 10,
            'name' => 'Maju Sejahtera Indonesia',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('contracts')->insert([
            'id' => 20,
            'contract_number' => 'BDG-CA/26-05/0001',
            'customer_id' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Auth::login(User::findOrFail(1));
    }

    protected function tearDown(): void
    {
        foreach ([
            'job_advice_rooms',
            'job_advices',
            'master_rooms',
            'contract_rooms',
            'branches',
            'provinces',
            'cities',
            'buildings',
            'surveys',
            'quotations',
            'contracts',
            'customers',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_repeated_identical_create_request_returns_existing_job_advice(): void
    {
        $controller = app(JobAdviceController::class);

        $first = $controller->store($this->createRequest());
        $second = $controller->store($this->createRequest());

        $firstPayload = $first->getData(true);
        $secondPayload = $second->getData(true);

        $this->assertSame('success', $firstPayload['status']);
        $this->assertSame('success', $secondPayload['status']);
        $this->assertArrayHasKey('duplicate_prevented', $secondPayload, json_encode($secondPayload));
        $this->assertTrue($secondPayload['duplicate_prevented']);
        $this->assertSame($firstPayload['data']['id'], $secondPayload['data']['id']);
        $this->assertDatabaseCount('job_advices', 1);
    }

    public function test_extra_job_advice_uses_contract_number_reference_and_flags(): void
    {
        $controller = app(JobAdviceController::class);

        $response = $controller->store($this->createRequest([
            'type' => 'Extra',
            'expected_date' => '2026-06-05',
            'with_invoicing' => true,
            'with_materials' => true,
        ]));

        $payload = $response->getData(true);

        $this->assertSame('success', $payload['status']);
        $this->assertDatabaseHas('job_advices', [
            'contract_id' => 20,
            'type' => 'Extra',
            'reference_number' => 'BDG-CA/26-05/0001',
            'with_invoicing' => true,
            'with_materials' => true,
        ]);
    }

    public function test_install_job_advice_ignores_rental_rooms_sent_during_create(): void
    {
        $controller = app(JobAdviceController::class);

        $response = $controller->store($this->createRequest([
            'expected_date' => '2026-06-06',
            'rooms' => [
                [
                    'contract_room_id' => 999,
                    'rental_product_id' => 999,
                    'quantity' => 1,
                ],
            ],
        ]));

        $payload = $response->getData(true);

        $this->assertSame('success', $payload['status']);
        $this->assertDatabaseHas('job_advices', [
            'contract_id' => 20,
            'type' => 'Install',
        ]);
        $this->assertDatabaseCount('job_advice_rooms', 0);
    }

    public function test_install_job_advice_room_rental_cannot_be_updated_directly(): void
    {
        $controller = app(JobAdviceController::class);

        DB::table('job_advices')->insert([
            'id' => 50,
            'contract_id' => 20,
            'customer_id' => 10,
            'type' => 'Install',
            'status' => 'draft',
            'expected_date' => '2026-06-07',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('job_advice_rooms')->insert([
            'id' => 60,
            'job_advice_id' => 50,
            'rental_product_id' => 1,
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $controller->updateRoomRental(
            Request::create('/marketing/job-advices/rooms/60/update-rental', 'POST', [
                'rental_product_id' => 999,
                'quantity' => 2,
            ]),
            \App\Models\JobAdviceRoom::findOrFail(60)
        );

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('Perubahan rental harus melalui JA Change Rental.', $response->getData(true)['message']);
        $this->assertDatabaseHas('job_advice_rooms', [
            'id' => 60,
            'rental_product_id' => 1,
            'quantity' => 1,
        ]);
    }

    private function createRequest(array $overrides = []): Request
    {
        $request = Request::create('/marketing/job-advices', 'POST', array_merge([
            'contract_id' => 20,
            'type' => 'Install',
            'request_by' => 1,
            'expected_date' => '2026-06-04',
            'status' => 'draft',
        ], $overrides));
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        return $request;
    }
}
