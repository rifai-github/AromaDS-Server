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
use Tests\TestCase;

/**
 * Bug #26/#27: technician finishes the last room of a job, but the job status
 * stays stuck at "in_progress" ("masih dikerjakan" / "masih menunggu") until a
 * second identical action is performed. Root cause: completeRoom()'s 30-second
 * duplicate-debounce early-return hardcoded all_completed=true and skipped the
 * job-status transition block entirely, so a retried/duplicate request (e.g.
 * technician re-taps after a slow response, or the request is replayed) reports
 * success to the app while never actually flipping the job to
 * 'teknisi_selesai_pengerjaan'.
 */
class MobileCompleteRoomDuplicateTest extends TestCase
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

        Schema::create('job_advices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_advice_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_advice_id')->nullable();
            $table->string('room_name')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->foreignId('rental_product_id')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('remove_job_schedule_id')->nullable();
            $table->foreignId('install_job_schedule_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->boolean('material_checked')->default(false);
            $table->timestamp('material_checked_at')->nullable();
            $table->foreignId('job_advice_id')->nullable();
            $table->foreignId('building_id')->nullable();
            $table->date('completed_at')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
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
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable();
            $table->text('completion_notes')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
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

        Schema::create('job_schedule_room_rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_room_id')->nullable();
            $table->foreignId('job_advice_room_id')->nullable();
            $table->boolean('is_primary')->default(false);
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

        Schema::create('inventory_issuings', function (Blueprint $table) {
            $table->id();
            $table->string('issuing_number')->nullable();
            $table->string('reference_no')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->boolean('is_unit')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->boolean('is_unit')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->foreignId('product_category_id')->nullable();
            $table->foreignId('product_type_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_rentals', function (Blueprint $table) {
            $table->id();
            $table->string('rental_name')->nullable();
            $table->string('rental_type')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('rental_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_rental_id')->nullable();
            $table->string('item_type')->default('product');
            $table->foreignId('product_category_id')->nullable();
            $table->foreignId('product_type_id')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('bom_rental_qty', 10, 2)->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->string('status')->nullable();
            $table->string('location_type')->nullable();
            $table->foreignId('location_id')->nullable();
            $table->foreignId('updated_by')->nullable();
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

        Schema::create('job_schedule_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('job_advice_room_id')->nullable();
            $table->string('mac')->nullable();
            $table->json('device_snapshot')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();
        });

        Schema::create('mobile_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action')->nullable();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('job_schedule_room_id')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->timestamps();
        });

        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Teknisi Test',
            'email' => 'teknisi@example.test',
            'password' => 'password',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Auth::login(User::findOrFail(1));
    }

    protected function tearDown(): void
    {
        foreach ([
            'mobile_sync_logs',
            'job_schedule_units',
            'job_photos',
            'inventory_issuing_items',
            'serial_numbers',
            'rental_details',
            'master_rentals',
            'master_products',
            'product_types',
            'product_categories',
            'inventory_issuings',
            'job_assign_material_issues',
            'material_issues',
            'job_schedule_room_rentals',
            'job_assign_schedules',
            'job_schedule_rooms',
            'job_schedules',
            'job_advice_rooms',
            'job_advices',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    /**
     * Seed the material-ready chain so getMaterialReadinessBlockReason() lets
     * completeRoom() proceed for an 'install' job (which cannot bypass via
     * canBypassMaterialAssignFlow()).
     */
    private function seedMaterialReadyForJob(int $jobScheduleId, int $jobAssignScheduleId): void
    {
        DB::table('job_assign_schedules')->insert([
            'id' => $jobAssignScheduleId,
            'job_schedule_id' => $jobScheduleId,
            'status' => 'assigned',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $materialIssueId = $jobAssignScheduleId + 1000;
        DB::table('material_issues')->insert([
            'id' => $materialIssueId,
            'issue_number' => "MI-{$jobAssignScheduleId}",
            'warehouse_id' => 1,
            'status' => 'issued',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_assign_material_issues')->insert([
            'job_assign_schedule_id' => $jobAssignScheduleId,
            'material_issue_id' => $materialIssueId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventory_issuings')->insert([
            'issuing_number' => "WI-{$jobAssignScheduleId}",
            'reference_no' => "MI-{$jobAssignScheduleId}",
            'warehouse_id' => 1,
            'status' => 'sent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_duplicate_complete_room_call_still_transitions_job_to_teknisi_selesai_pengerjaan(): void
    {
        DB::table('job_advices')->insert([
            'id' => 70,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_advice_rooms')->insert([
            ['id' => 80, 'job_advice_id' => 70, 'room_name' => 'Room A', 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 81, 'job_advice_id' => 70, 'room_name' => 'Room B', 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('job_schedules')->insert([
            'id' => 10,
            'job_advice_id' => 70,
            'job_number' => 'BDG-IR/26-06/0001',
            'type' => 'install',
            'status' => 'barang_diambil',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_schedule_rooms')->insert([
            ['id' => 90, 'job_schedule_id' => 10, 'job_advice_room_id' => 80, 'room_name' => 'Room A', 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 91, 'job_schedule_id' => 10, 'job_advice_room_id' => 81, 'room_name' => 'Room B', 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->seedMaterialReadyForJob(10, 20);

        $controller = app(JobController::class);

        // Step 1: complete Room A (first of two rooms) — job should stay in_progress.
        $requestA = Request::create('/api/v1/mobile/rooms/80/complete', 'POST', [
            'job_schedule_id' => 10,
        ], [], [
            'before_photos' => [UploadedFile::fake()->image('before-a.jpg')],
            'after_photos' => [UploadedFile::fake()->image('after-a.jpg')],
        ]);
        $responseA = $controller->completeRoom($requestA, 80);
        $payloadA = json_decode($responseA->getContent(), true);

        $this->assertSame(200, $responseA->getStatusCode());
        $this->assertFalse($payloadA['data']['all_completed']);
        $this->assertSame('in_progress', DB::table('job_schedules')->where('id', 10)->value('status'));

        // Step 2: complete Room B (the LAST room) — first call. This is the one
        // that should flip the job to 'teknisi_selesai_pengerjaan'.
        $requestB1 = Request::create('/api/v1/mobile/rooms/81/complete', 'POST', [
            'job_schedule_id' => 10,
        ], [], [
            'before_photos' => [UploadedFile::fake()->image('before-b.jpg')],
            'after_photos' => [UploadedFile::fake()->image('after-b.jpg')],
        ]);
        $responseB1 = $controller->completeRoom($requestB1, 81);
        $payloadB1 = json_decode($responseB1->getContent(), true);

        $this->assertSame(200, $responseB1->getStatusCode());
        $this->assertTrue($payloadB1['data']['all_completed']);
        $this->assertSame('teknisi_selesai_pengerjaan', DB::table('job_schedules')->where('id', 10)->value('status'));

        // Step 3: technician's app retries/re-sends the SAME completion for Room B
        // within the 30s debounce window (e.g. after a slow/ambiguous response).
        // This must stay idempotent: report all_completed=true AND the job status
        // must remain (or be re-affirmed as) 'teknisi_selesai_pengerjaan' — not
        // silently skip the transition and leave the job looking stuck.
        $requestB2 = Request::create('/api/v1/mobile/rooms/81/complete', 'POST', [
            'job_schedule_id' => 10,
        ], [], [
            'before_photos' => [UploadedFile::fake()->image('before-b2.jpg')],
            'after_photos' => [UploadedFile::fake()->image('after-b2.jpg')],
        ]);
        $responseB2 = $controller->completeRoom($requestB2, 81);
        $payloadB2 = json_decode($responseB2->getContent(), true);

        $this->assertSame(200, $responseB2->getStatusCode());
        $this->assertStringContainsString('duplicate', $payloadB2['message']);
        $this->assertTrue($payloadB2['data']['all_completed']);
        $this->assertSame('teknisi_selesai_pengerjaan', DB::table('job_schedules')->where('id', 10)->value('status'));
    }

    public function test_duplicate_complete_room_call_does_not_falsely_report_all_completed_when_sibling_room_still_pending(): void
    {
        // Regression guard for the exact hardcoded-true bug: if the duplicate
        // debounce fires for Room A while Room B is STILL pending (e.g. the
        // technician completed A, the app retried the same A request, but B
        // hasn't been touched yet), the duplicate response must not lie and say
        // all_completed=true.
        DB::table('job_advices')->insert([
            'id' => 71,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_advice_rooms')->insert([
            ['id' => 82, 'job_advice_id' => 71, 'room_name' => 'Room A', 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 83, 'job_advice_id' => 71, 'room_name' => 'Room B', 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('job_schedules')->insert([
            'id' => 11,
            'job_advice_id' => 71,
            'job_number' => 'BDG-IR/26-06/0002',
            'type' => 'install',
            'status' => 'barang_diambil',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_schedule_rooms')->insert([
            ['id' => 92, 'job_schedule_id' => 11, 'job_advice_room_id' => 82, 'room_name' => 'Room A', 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 93, 'job_schedule_id' => 11, 'job_advice_room_id' => 83, 'room_name' => 'Room B', 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->seedMaterialReadyForJob(11, 21);

        $controller = app(JobController::class);

        $requestA1 = Request::create('/api/v1/mobile/rooms/82/complete', 'POST', [
            'job_schedule_id' => 11,
        ], [], [
            'before_photos' => [UploadedFile::fake()->image('before-a.jpg')],
            'after_photos' => [UploadedFile::fake()->image('after-a.jpg')],
        ]);
        $controller->completeRoom($requestA1, 82);

        // Retry the SAME room A completion within the debounce window while
        // Room B is still untouched.
        $requestA2 = Request::create('/api/v1/mobile/rooms/82/complete', 'POST', [
            'job_schedule_id' => 11,
        ], [], [
            'before_photos' => [UploadedFile::fake()->image('before-a2.jpg')],
            'after_photos' => [UploadedFile::fake()->image('after-a2.jpg')],
        ]);
        $responseA2 = $controller->completeRoom($requestA2, 82);
        $payloadA2 = json_decode($responseA2->getContent(), true);

        $this->assertSame(200, $responseA2->getStatusCode());
        $this->assertStringContainsString('duplicate', $payloadA2['message']);
        $this->assertFalse($payloadA2['data']['all_completed']);
        $this->assertSame('in_progress', DB::table('job_schedules')->where('id', 11)->value('status'));
    }

    public function test_unit_refill_completion_does_not_auto_complete_unit_only_sibling_without_scan(): void
    {
        DB::table('job_advices')->insert([
            'id' => 72,
            'customer_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('master_rentals')->insert([
            ['id' => 1, 'rental_name' => 'ADS W300 100 ml', 'rental_type' => 'unit_refill', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'rental_name' => 'Rental Unit Only', 'rental_type' => 'unit_only', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('product_categories')->insert([
            ['id' => 4, 'name' => 'Diffuser', 'is_unit' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'Dispenser', 'is_unit' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('master_products')->insert([
            ['id' => 30, 'name' => 'Diffuser W300 White', 'product_category_id' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 37, 'name' => 'PURE Dispenser 7200', 'product_category_id' => 6, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('rental_details')->insert([
            ['master_rental_id' => 1, 'product_category_id' => 4, 'master_product_id' => 30, 'quantity' => 1, 'bom_rental_qty' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['master_rental_id' => 2, 'product_category_id' => 6, 'master_product_id' => 37, 'quantity' => 1, 'bom_rental_qty' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('serial_numbers')->insert([
            ['id' => 10, 'serial_number' => 'DW300W2606005', 'master_product_id' => 30, 'status' => 'on_hand', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 247, 'serial_number' => 'PD72002606015', 'master_product_id' => 37, 'status' => 'on_hand', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('job_advice_rooms')->insert([
            ['id' => 90, 'job_advice_id' => 72, 'room_name' => 'Ruang Lobby Umum', 'rental_product_id' => 1, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 91, 'job_advice_id' => 72, 'room_name' => 'Ruang Lobby Umum', 'rental_product_id' => 2, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('job_schedules')->insert([
            'id' => 12,
            'job_advice_id' => 72,
            'job_number' => 'SBY-IF/26-07/0004',
            'type' => 'install_free',
            'status' => 'barang_diambil',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_schedule_rooms')->insert([
            ['id' => 94, 'job_schedule_id' => 12, 'job_advice_room_id' => 90, 'room_name' => 'Ruang Lobby Umum', 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 95, 'job_schedule_id' => 12, 'job_advice_room_id' => 91, 'room_name' => 'Ruang Lobby Umum', 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('job_schedule_room_rentals')->insert([
            ['job_schedule_room_id' => 94, 'job_advice_room_id' => 90, 'is_primary' => true, 'created_at' => now(), 'updated_at' => now()],
            ['job_schedule_room_id' => 94, 'job_advice_room_id' => 91, 'is_primary' => false, 'created_at' => now(), 'updated_at' => now()],
            ['job_schedule_room_id' => 95, 'job_advice_room_id' => 91, 'is_primary' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->seedMaterialReadyForJob(12, 22);

        DB::table('inventory_issuing_items')->insert([
            ['inventory_issuing_id' => 1, 'job_assign_schedule_id' => 22, 'product_id' => 30, 'room_name' => 'Ruang Lobby Umum', 'quantity_requested' => 1, 'quantity_issued' => 1, 'serial_number_id' => 10, 'created_at' => now(), 'updated_at' => now()],
            ['inventory_issuing_id' => 1, 'job_assign_schedule_id' => 22, 'product_id' => 37, 'room_name' => 'Ruang Lobby Umum', 'quantity_requested' => 1, 'quantity_issued' => 1, 'serial_number_id' => 247, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('job_schedule_units')->insert([
            'job_schedule_id' => 12,
            'job_advice_room_id' => 90,
            'mac' => 'DW300W2606005',
            'device_snapshot' => '{}',
            'scanned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = app(JobController::class)->completeRoom(
            Request::create('/api/v1/mobile/rooms/90/complete', 'POST', [
                'job_schedule_id' => 12,
            ], [], [
                'before_photos' => [UploadedFile::fake()->image('before.jpg')],
                'after_photos' => [UploadedFile::fake()->image('after.jpg')],
            ]),
            90
        );

        $payload = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($payload['data']['all_completed']);
        $this->assertSame('completed', DB::table('job_schedule_rooms')->where('id', 94)->value('status'));
        $this->assertSame('pending', DB::table('job_schedule_rooms')->where('id', 95)->value('status'));
        $this->assertSame('pending', DB::table('job_advice_rooms')->where('id', 91)->value('status'));

        DB::table('job_schedule_rooms')->where('id', 95)->update(['status' => 'completed']);

        $job = JobSchedule::findOrFail(12);
        $controller = app(JobController::class);
        $method = new \ReflectionMethod($controller, 'validateJobReadyForMobileCompletion');
        $method->setAccessible(true);
        $readiness = $method->invoke($controller, $job);

        $this->assertFalse($readiness['ok']);
        $this->assertStringContainsString('Rental Unit Only', $readiness['message']);
        $this->assertStringContainsString('Dispenser', $readiness['message']);
    }
}
