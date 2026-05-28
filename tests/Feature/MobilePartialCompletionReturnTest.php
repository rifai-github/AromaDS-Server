<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Mobile\JobController;
use App\Models\JobSchedule;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class MobilePartialCompletionReturnTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->foreignId('branch_office')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_center')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('job_advice_id')->nullable();
            $table->foreignId('building_id')->nullable();
            $table->string('building_name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('contract_number')->nullable();
            $table->string('quotation_number')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->integer('period')->nullable();
            $table->integer('service_frequency')->nullable();
            $table->string('service_period_type')->nullable();
            $table->integer('service_interval_days')->nullable();
            $table->date('next_service_date')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('job_reference_number')->nullable();
            $table->date('schedule_date')->nullable();
            $table->date('expected_date')->nullable();
            $table->date('ba_date')->nullable();
            $table->string('ba_number')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('internal_notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_advices', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_advice_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_advice_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedule_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('job_advice_room_id')->nullable();
            $table->string('room_name')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->string('status')->nullable();
            $table->string('material_return_status')->nullable();
            $table->foreignId('material_return_id')->nullable();
            $table->timestamp('material_return_at')->nullable();
            $table->foreignId('material_return_by')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedule_room_rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_room_id')->nullable();
            $table->foreignId('job_advice_room_id')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_assign_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_issues', function (Blueprint $table) {
            $table->id();
            $table->string('issue_number')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_assign_material_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_assign_schedule_id')->nullable();
            $table->foreignId('material_issue_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_issue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_issue_id')->nullable();
            $table->foreignId('job_assign_schedule_id')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->string('room_name')->nullable();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->nullable();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->string('status')->nullable();
            $table->date('return_date')->nullable();
            $table->string('return_reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('returned_by')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_return_id')->nullable();
            $table->foreignId('material_issue_item_id')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->string('room_name')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_receivings', function (Blueprint $table) {
            $table->id();
            $table->string('receiving_number')->nullable();
            $table->string('reference_no')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->foreignId('received_from')->nullable();
            $table->foreignId('received_by_old')->nullable();
            $table->date('receive_date')->nullable();
            $table->date('schedule_date')->nullable();
            $table->string('status')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_receiving_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_receiving_id')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('quantity_received', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_issuings', function (Blueprint $table) {
            $table->id();
            $table->string('issuing_number')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_issuing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_issuing_id')->nullable();
            $table->foreignId('job_assign_schedule_id')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->string('room_name')->nullable();
            $table->decimal('quantity_requested', 10, 2)->default(0);
            $table->decimal('quantity_issued', 10, 2)->default(0);
            $table->foreignId('serial_number_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number')->nullable();
            $table->string('status')->nullable();
            $table->string('location_type')->nullable();
            $table->foreignId('location_id')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->foreignId('inventory_receiving_id')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('unit_on_walls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('serial_number_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouse_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->string('movement_type')->nullable();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->date('movement_date')->nullable();
            $table->string('reference_no')->nullable();
            $table->string('reference_type')->nullable();
            $table->string('movement_no')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('job_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('technician_id')->nullable();
            $table->string('job_type')->nullable();
            $table->text('notes')->nullable();
            $table->string('photo_pic')->nullable();
            $table->string('signature_file')->nullable();
            $table->text('signature_data')->nullable();
            $table->string('pic_name')->nullable();
            $table->json('photos')->nullable();
            $table->string('photo_before')->nullable();
            $table->string('photo_after')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('signature_at')->nullable();
            $table->timestamps();
        });

        Schema::create('job_schedule_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->string('mac')->nullable();
            $table->json('device_snapshot')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();
        });

        Schema::create('job_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('job_schedule_room_id')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('photo_type')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('uploaded_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Administrator',
            'email' => 'admin@example.test',
            'password' => 'password',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Auth::login(User::findOrFail(1));
    }

    protected function tearDown(): void
    {
        foreach ([
            'inventory_movements',
            'warehouse_products',
            'unit_on_walls',
            'serial_numbers',
            'inventory_issuing_items',
            'inventory_issuings',
            'inventory_receiving_items',
            'inventory_receivings',
            'material_return_items',
            'material_returns',
            'material_issue_items',
            'job_assign_material_issues',
            'material_issues',
            'job_assign_schedules',
            'job_photos',
            'job_schedule_units',
            'job_reports',
            'job_schedule_room_rentals',
            'job_schedule_rooms',
            'job_schedules',
            'job_advice_rooms',
            'job_advices',
            'warehouses',
            'teams',
            'buildings',
            'branches',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_partial_completion_creates_pending_return_without_receiving_stock_or_releasing_serial_number(): void
    {
        $this->seedPartialCompletionScenario();

        $controller = app(JobController::class);
        $job = JobSchedule::findOrFail(10);
        $room = (object) [
            'room_name' => 'VIP ROOM',
            'room_id' => 900,
        ];

        $contextMethod = new ReflectionMethod($controller, 'preparePartialCompletionReturnContext');
        $contextMethod->setAccessible(true);
        $returnContext = $contextMethod->invoke($controller, $job, collect([$room]), now());

        $processMethod = new ReflectionMethod($controller, 'processPartialCompletionMaterialReturnItems');
        $processMethod->setAccessible(true);
        $processMethod->invoke($controller, $job, $room, collect([20]), $returnContext, now());

        $this->assertDatabaseHas('material_returns', [
            'job_schedule_id' => 10,
            'warehouse_id' => 5,
            'status' => 'pending',
            'returned_by' => null,
            'returned_at' => null,
        ]);

        $this->assertDatabaseHas('inventory_receivings', [
            'reference_no' => 'BDG-IR/26-05/0002',
            'branch_id' => 2,
            'status' => 'pending',
            'receive_date' => null,
        ]);
        $this->assertStringStartsWith('BDG-IRC/', DB::table('inventory_receivings')->value('receiving_number'));

        $this->assertDatabaseHas('inventory_receiving_items', [
            'master_product_id' => 100,
            'quantity' => 1,
            'quantity_received' => 0,
        ]);

        $this->assertDatabaseHas('serial_numbers', [
            'id' => 200,
            'status' => 'pending',
            'location_type' => 'technician',
            'location_id' => 1,
            'inventory_receiving_id' => 1,
        ]);

        $this->assertDatabaseHas('inventory_issuing_items', [
            'id' => 60,
            'serial_number_id' => 200,
        ]);

        $this->assertDatabaseCount('warehouse_products', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_partial_completion_keeps_source_job_waiting_and_moves_unfinished_room_to_follow_up(): void
    {
        $this->seedPartialCompletionScenario();

        DB::table('job_advices')->insert([
            'id' => 70,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_advice_rooms')->insert([
            ['id' => 80, 'job_advice_id' => 70, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 81, 'job_advice_id' => 70, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('job_schedules')->where('id', 10)->update([
            'job_advice_id' => 70,
            'status' => 'in_progress',
            'type' => 'service',
            'period' => 3,
            'service_frequency' => 1,
            'service_period_type' => '1 Bulan 1x',
            'service_interval_days' => 30,
            'next_service_date' => '2026-09-03',
            'reference_number' => 'BDG-JA/26-05/0002',
            'job_reference_number' => 'BDG-JA/26-05/0002',
        ]);

        DB::table('job_schedule_rooms')->insert([
            [
                'id' => 90,
                'job_schedule_id' => 10,
                'job_advice_room_id' => 80,
                'room_name' => 'OFFICE ROOM',
                'room_id' => 800,
                'status' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 91,
                'job_schedule_id' => 10,
                'job_advice_room_id' => 81,
                'room_name' => 'VIP ROOM',
                'room_id' => 900,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $controller = app(JobController::class);
        $job = JobSchedule::findOrFail(10);

        $method = new ReflectionMethod($controller, 'handleCannotCompleteAllRooms');
        $method->setAccessible(true);
        $method->invoke($controller, $job, now());

        $this->assertDatabaseHas('job_schedules', [
            'id' => 10,
            'status' => 'meninggalkan_lokasi',
        ]);
        $this->assertNull(DB::table('job_schedules')->where('id', 10)->value('completed_at'));

        $this->assertDatabaseHas('job_schedule_rooms', [
            'id' => 91,
            'status' => 'cancelled',
            'material_return_status' => 'pending',
        ]);

        $followUpJob = DB::table('job_schedules')
            ->where('id', '!=', 10)
            ->where('job_advice_id', 70)
            ->first();

        $this->assertNotNull($followUpJob);
        $this->assertSame('new_job', $followUpJob->status);
        $this->assertSame(3, (int) $followUpJob->period);
        $this->assertSame(3, (int) JobSchedule::findOrFail($followUpJob->id)->invoice_period);
        $this->assertSame(1, (int) $followUpJob->service_frequency);
        $this->assertSame('1 Bulan 1x', $followUpJob->service_period_type);
        $this->assertSame(30, (int) $followUpJob->service_interval_days);
        $this->assertSame('BDG-JA/26-05/0002', $followUpJob->reference_number);

        $this->assertDatabaseHas('job_schedule_rooms', [
            'job_schedule_id' => $followUpJob->id,
            'job_advice_room_id' => 81,
            'room_name' => 'VIP ROOM',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('material_returns', [
            'job_schedule_id' => 10,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('inventory_receivings', [
            'reference_no' => 'BDG-IR/26-05/0002',
            'status' => 'pending',
        ]);
    }

    public function test_partial_completion_does_not_mark_completed_sibling_job_as_left_location(): void
    {
        $this->seedPartialCompletionScenario();

        DB::table('job_advices')->insert([
            'id' => 70,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_advice_rooms')->insert([
            ['id' => 80, 'job_advice_id' => 70, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 81, 'job_advice_id' => 70, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('job_schedules')->where('id', 10)->update([
            'job_advice_id' => 70,
            'status' => 'in_progress',
            'type' => 'Install (IR)',
            'building_name' => 'Gedung Cabang B 110526',
            'reference_number' => 'BDG-JA/26-05/0012',
            'job_reference_number' => 'BDG-JA/26-05/0012',
        ]);

        DB::table('job_schedules')->insert([
            'id' => 11,
            'job_number' => 'BDG-IR/26-05/0002',
            'type' => 'Install (IR)',
            'status' => 'teknisi_selesai_pengerjaan',
            'job_advice_id' => 70,
            'building_id' => 10,
            'building_name' => 'Gedung Cabang B 110526',
            'branch_id' => 1,
            'reference_number' => 'BDG-JA/26-05/0012',
            'job_reference_number' => 'BDG-JA/26-05/0012',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_schedule_rooms')->insert([
            [
                'id' => 90,
                'job_schedule_id' => 10,
                'job_advice_room_id' => 80,
                'room_name' => 'Ruang Mawar',
                'room_id' => 800,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 91,
                'job_schedule_id' => 11,
                'job_advice_room_id' => 81,
                'room_name' => 'Ruang Melati',
                'room_id' => 900,
                'status' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $controller = app(JobController::class);

        $method = new ReflectionMethod($controller, 'handleCannotCompleteAllRooms');
        $method->setAccessible(true);
        $method->invoke($controller, JobSchedule::findOrFail(10), now());

        $this->assertDatabaseHas('job_schedules', [
            'id' => 10,
            'status' => 'meninggalkan_lokasi',
        ]);
        $this->assertDatabaseHas('job_schedule_rooms', [
            'id' => 90,
            'status' => 'cancelled',
        ]);

        $this->assertDatabaseHas('job_schedules', [
            'id' => 11,
            'status' => 'teknisi_selesai_pengerjaan',
        ]);
        $this->assertDatabaseHas('job_schedule_rooms', [
            'id' => 91,
            'status' => 'completed',
        ]);
        $this->assertDatabaseMissing('material_returns', [
            'job_schedule_id' => 11,
        ]);
    }

    public function test_partial_completion_verification_saves_pic_photo_signature_and_pic_name(): void
    {
        $this->seedPartialCompletionScenario();

        DB::table('job_advices')->insert([
            'id' => 70,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_advice_rooms')->insert([
            ['id' => 80, 'job_advice_id' => 70, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 81, 'job_advice_id' => 70, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('job_schedules')->where('id', 10)->update([
            'job_advice_id' => 70,
            'status' => 'in_progress',
            'type' => 'service',
            'ba_date' => now()->toDateString(),
            'ba_number' => 'BA-TEST-001',
        ]);

        DB::table('job_schedule_rooms')->insert([
            [
                'id' => 90,
                'job_schedule_id' => 10,
                'job_advice_room_id' => 80,
                'room_name' => 'OFFICE ROOM',
                'room_id' => 800,
                'status' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 91,
                'job_schedule_id' => 10,
                'job_advice_room_id' => 81,
                'room_name' => 'VIP ROOM',
                'room_id' => 900,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $request = Request::create('/api/v1/mobile/jobs/10/verify', 'POST', [
            'pic_name' => 'Bapak Client',
            'signature' => 'data:image/png;base64,'.base64_encode('test-signature'),
            'notes' => 'Tidak semua ruangan selesai.',
            'cannot_complete_all_rooms' => '1',
        ], [], [
            'pic_photo' => UploadedFile::fake()->image('pic.jpg'),
        ]);

        $response = app(JobController::class)->verifyJob($request, 10);
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('success', $payload['status']);
        $this->assertSame('meninggalkan_lokasi', $payload['data']['job_status']);

        $report = DB::table('job_reports')->where('job_schedule_id', 10)->first();
        $this->assertNotNull($report);
        $this->assertSame('Bapak Client', $report->pic_name);
        $this->assertNotEmpty($report->photo_pic);
        $this->assertNotEmpty($report->signature_file);

        $this->assertDatabaseHas('job_photos', [
            'job_schedule_id' => 10,
            'photo_type' => 'PIC Photo',
            'photo_path' => $report->photo_pic,
        ]);
        $this->assertDatabaseHas('job_photos', [
            'job_schedule_id' => 10,
            'photo_type' => 'Digital Signature',
            'photo_path' => $report->signature_file,
        ]);
    }

    public function test_partial_completion_final_verification_waits_until_follow_up_is_suspend_or_dpf(): void
    {
        $this->seedPartialCompletionScenario();

        DB::table('job_advices')->insert([
            'id' => 70,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_advice_rooms')->insert([
            ['id' => 80, 'job_advice_id' => 70, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 81, 'job_advice_id' => 70, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('job_schedules')->where('id', 10)->update([
            'job_advice_id' => 70,
            'status' => 'in_progress',
        ]);

        DB::table('job_schedule_rooms')->insert([
            [
                'id' => 90,
                'job_schedule_id' => 10,
                'job_advice_room_id' => 80,
                'room_name' => 'OFFICE ROOM',
                'room_id' => 800,
                'status' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 91,
                'job_schedule_id' => 10,
                'job_advice_room_id' => 81,
                'room_name' => 'VIP ROOM',
                'room_id' => 900,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $controller = app(JobController::class);

        $partialMethod = new ReflectionMethod($controller, 'handleCannotCompleteAllRooms');
        $partialMethod->setAccessible(true);
        $partialMethod->invoke($controller, JobSchedule::findOrFail(10), now());

        $validateMethod = new ReflectionMethod($controller, 'validateJobReadyForMobileCompletion');
        $validateMethod->setAccessible(true);

        $waitingResult = $validateMethod->invoke($controller, JobSchedule::findOrFail(10));
        $this->assertFalse($waitingResult['ok']);
        $this->assertStringContainsString('masih New Job', $waitingResult['message']);

        $followUpJob = DB::table('job_schedules')
            ->where('id', '!=', 10)
            ->where('job_advice_id', 70)
            ->first();

        DB::table('job_schedules')->where('id', $followUpJob->id)->update([
            'status' => 'suspend',
            'updated_at' => now(),
        ]);

        DB::table('job_photos')->insert([
            [
                'job_schedule_id' => 10,
                'job_schedule_room_id' => 90,
                'photo_path' => 'before.jpg',
                'photo_type' => 'Before Work',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'job_schedule_id' => 10,
                'job_schedule_room_id' => 90,
                'photo_path' => 'after.jpg',
                'photo_type' => 'After Work',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $readyResult = $validateMethod->invoke($controller, JobSchedule::findOrFail(10));
        $this->assertTrue($readyResult['ok']);
    }

    private function seedPartialCompletionScenario(): void
    {
        DB::table('branches')->insert([
            [
                'id' => 1,
                'code' => 'JKT',
                'name' => 'Jakarta Branch',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'code' => 'BDG',
                'name' => 'Bandung Branch',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('buildings')->insert([
            'id' => 10,
            'branch_id' => 1,
            'name' => 'Spektrum Biologi I',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('warehouses')->insert([
            'id' => 5,
            'name' => 'Warehouse Bandung',
            'branch_id' => 2,
            'is_active' => true,
            'is_center' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_schedules')->insert([
            'id' => 10,
            'job_number' => 'BDG-IR/26-05/0002',
            'type' => 'Install (IR)',
            'status' => 'meninggalkan_lokasi',
            'building_id' => 10,
            'branch_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_assign_schedules')->insert([
            'id' => 20,
            'job_schedule_id' => 10,
            'status' => 'assigned',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('material_issues')->insert([
            'id' => 30,
            'issue_number' => 'BDG-MA/26-05/0002',
            'warehouse_id' => 5,
            'status' => 'issued',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_assign_material_issues')->insert([
            'id' => 40,
            'job_assign_schedule_id' => 20,
            'material_issue_id' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('material_issue_items')->insert([
            'id' => 50,
            'material_issue_id' => 30,
            'job_assign_schedule_id' => 20,
            'product_id' => 100,
            'room_name' => 'VIP ROOM',
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventory_issuings')->insert([
            'id' => 55,
            'issuing_number' => 'BDG-WI/26-05/0002',
            'warehouse_id' => 5,
            'status' => 'sent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventory_issuing_items')->insert([
            'id' => 60,
            'inventory_issuing_id' => 55,
            'job_assign_schedule_id' => 20,
            'product_id' => 100,
            'room_name' => 'VIP ROOM',
            'quantity_requested' => 1,
            'quantity_issued' => 1,
            'serial_number_id' => 200,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('serial_numbers')->insert([
            'id' => 200,
            'serial_number' => 'C100B0526002',
            'status' => 'on_hand',
            'location_type' => 'technician',
            'location_id' => 1,
            'master_product_id' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
