<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Mobile\JobController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileJobListRoomAssignmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();

        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Ahmad Wijaya',
            'email' => 'tech@example.test',
            'password' => 'password',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('teams')->insert([
            'id' => 10,
            'team_name' => 'Tim Service Area Bandung Kab',
            'team_head_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('team_members')->insert([
            'id' => 100,
            'team_id' => 10,
            'user_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('customers')->insert([
            'id' => 20,
            'name' => 'Test110526',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_advices')->insert([
            'id' => 30,
            'customer_id' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        foreach ([
            'job_favorites',
            'unit_on_walls',
            'inventory_issuing_items',
            'inventory_issuings',
            'serial_numbers',
            'material_issue_items',
            'material_issues',
            'job_schedule_room_rentals',
            'job_schedule_room_assignments',
            'job_assign_material_issues',
            'job_schedule_rooms',
            'job_assign_schedules',
            'job_schedules',
            'job_advice_rooms',
            'job_advices',
            'master_products',
            'product_types',
            'product_categories',
            'rental_details',
            'rental_component_products',
            'rental_components',
            'master_rentals',
            'contract_rooms',
            'master_rooms',
            'buildings',
            'team_members',
            'teams',
            'customers',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_mobile_job_list_includes_service_assigned_only_at_room_level(): void
    {
        DB::table('job_schedules')->insert([
            'id' => 40,
            'job_number' => 'BDG-CSR/26-06/0003',
            'job_advice_id' => 30,
            'type' => 'service',
            'status' => 'barang_siap_diambil',
            'room_name' => 'Ruang Melati',
            'schedule_date' => '2026-06-01',
            'material_checked' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_schedule_rooms')->insert([
            'id' => 50,
            'job_schedule_id' => 40,
            'room_name' => 'Ruang Melati',
            'room_id' => 500,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_schedule_room_assignments')->insert([
            'id' => 60,
            'job_schedule_id' => 40,
            'job_schedule_room_id' => 50,
            'team_id' => 10,
            'status' => 'assigned',
            'assigned_date' => '2026-06-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/api/v1/mobile/jobs/today', 'GET');
        $request->setUserResolver(fn () => User::find(1));
        Cache::flush();

        $response = app(JobController::class)->getTodayJobs($request);
        $payload = $response->getData(true);

        $this->assertSame('success', $payload['status']);
        $this->assertContains('BDG-CSR/26-06/0003', collect($payload['data'])->pluck('job_number')->all());
    }

    public function test_mobile_job_list_includes_remove_job_with_assign_team_status(): void
    {
        DB::table('job_schedules')->insert([
            'id' => 41,
            'job_number' => 'JKT-RV/26-05/0001',
            'job_advice_id' => 30,
            'type' => 'remove',
            'status' => 'assign_team',
            'room_name' => 'Ruang Wijaya',
            'schedule_date' => '2026-05-26',
            'material_checked' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_assign_schedules')->insert([
            'id' => 61,
            'job_schedule_id' => 41,
            'team_id' => 10,
            'status' => 'assigned',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/api/v1/mobile/jobs/today', 'GET');
        $request->setUserResolver(fn () => User::find(1));
        Cache::flush();

        $response = app(JobController::class)->getTodayJobs($request);
        $payload = $response->getData(true);
        $job = collect($payload['data'])->firstWhere('job_number', 'JKT-RV/26-05/0001');

        $this->assertSame('success', $payload['status']);
        $this->assertNotNull($job);
        $this->assertSame('remove', $job['type']);
        $this->assertSame('assign_team', $job['status']);
        $this->assertTrue($job['material_checked']);
    }

    public function test_mobile_job_list_keeps_plain_leave_location_job_visible(): void
    {
        DB::table('job_schedules')->insert([
            'id' => 43,
            'job_number' => 'BDG-CSR/26-06/0010',
            'job_advice_id' => 30,
            'type' => 'service',
            'status' => 'meninggalkan_lokasi',
            'room_name' => 'Ruang Melati',
            'schedule_date' => '2026-06-10',
            'material_checked' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_schedule_rooms')->insert([
            'id' => 51,
            'job_schedule_id' => 43,
            'room_name' => 'Ruang Melati',
            'room_id' => 500,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_assign_schedules')->insert([
            'id' => 63,
            'job_schedule_id' => 43,
            'team_id' => 10,
            'status' => 'assigned',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/api/v1/mobile/jobs/today', 'GET');
        $request->setUserResolver(fn () => User::find(1));
        Cache::flush();

        $response = app(JobController::class)->getTodayJobs($request);
        $payload = $response->getData(true);

        $this->assertSame('success', $payload['status']);
        $this->assertContains('BDG-CSR/26-06/0010', collect($payload['data'])->pluck('job_number')->all());
    }

    public function test_mobile_remove_job_without_job_advice_rooms_gets_fallback_room_data(): void
    {
        DB::table('job_schedules')->insert([
            'id' => 42,
            'job_number' => 'JKT-RV/26-06/0001',
            'job_advice_id' => 30,
            'type' => 'remove',
            'status' => 'assign_team',
            'room_name' => 'Ruang Wijaya',
            'schedule_date' => '2026-06-02',
            'material_checked' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_assign_schedules')->insert([
            'id' => 62,
            'job_schedule_id' => 42,
            'team_id' => 10,
            'status' => 'assigned',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/api/v1/mobile/jobs/42/rooms', 'GET');
        $request->setUserResolver(fn () => User::find(1));
        $this->actingAs(User::find(1));
        Cache::flush();

        $roomsResponse = app(JobController::class)->getJobRooms(42);
        $roomsPayload = $roomsResponse->getData(true);

        $this->assertSame('success', $roomsPayload['status']);
        $this->assertCount(1, $roomsPayload['data']);
        $this->assertSame('Ruang Wijaya', $roomsPayload['data'][0]['name']);
        $this->assertSame(42, $roomsPayload['data'][0]['job_schedule_id']);

        $detailResponse = app(JobController::class)->getJobDetail($request, 42);
        $detailPayload = $detailResponse->getData(true);

        $this->assertSame('success', $detailPayload['status']);
        $this->assertSame('Ruang Wijaya', $detailPayload['data']['room_name']);
        $this->assertSame(1, $detailPayload['data']['total_rooms']);
        $this->assertDatabaseHas('job_advice_rooms', [
            'job_advice_id' => 30,
            'room_name' => 'Ruang Wijaya',
            'remove_job_schedule_id' => 42,
        ]);
    }

    public function test_mobile_job_groups_multiple_rentals_in_one_physical_room(): void
    {
        DB::table('master_rooms')->insert([
            'id' => 500,
            'room_name' => 'Ruang Delima',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('contract_rooms')->insert([
            'id' => 70,
            'room_id' => 500,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_advice_rooms')->insert([
            [
                'id' => 90,
                'job_advice_id' => 30,
                'contract_room_id' => 70,
                'room_name' => 'Ruang Delima',
                'rental_name' => 'ADS XL Unit Only',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 91,
                'job_advice_id' => 30,
                'contract_room_id' => 70,
                'room_name' => 'Ruang Delima',
                'rental_name' => 'Rental-5',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('job_schedules')->insert([
            'id' => 43,
            'job_number' => 'JKT-IR/26-06/0002',
            'job_advice_id' => 30,
            'type' => 'install',
            'status' => 'assign_team',
            'room_id' => 500,
            'room_name' => 'Ruang Delima',
            'schedule_date' => '2026-06-04',
            'material_checked' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_assign_schedules')->insert([
            'id' => 63,
            'job_schedule_id' => 43,
            'team_id' => 10,
            'status' => 'assigned',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/api/v1/mobile/jobs/today', 'GET');
        $request->setUserResolver(fn () => User::find(1));
        $this->actingAs(User::find(1));
        Cache::flush();

        $listResponse = app(JobController::class)->getTodayJobs($request);
        $listPayload = $listResponse->getData(true);
        $job = collect($listPayload['data'])->firstWhere('job_number', 'JKT-IR/26-06/0002');

        $this->assertSame('success', $listPayload['status']);
        $this->assertNotNull($job);
        $this->assertSame(1, $job['total_rooms']);

        $detailResponse = app(JobController::class)->getJobDetail($request, 43);
        $detailPayload = $detailResponse->getData(true);

        $this->assertSame('success', $detailPayload['status']);
        $this->assertSame(1, $detailPayload['data']['total_rooms']);

        $roomsResponse = app(JobController::class)->getJobRooms(43);
        $roomsPayload = $roomsResponse->getData(true);

        $this->assertSame('success', $roomsPayload['status']);
        $this->assertCount(1, $roomsPayload['data']);
        $this->assertSame('Ruang Delima', $roomsPayload['data'][0]['name']);
        $this->assertSame('ADS XL Unit Only, Rental-5', $roomsPayload['data'][0]['rental_name']);
        $this->assertSame(90, $roomsPayload['data'][0]['id']);
        $this->assertDatabaseHas('job_schedule_rooms', [
            'job_schedule_id' => 43,
            'job_advice_room_id' => 90,
        ]);
        $this->assertDatabaseHas('job_schedule_rooms', [
            'job_schedule_id' => 43,
            'job_advice_room_id' => 91,
            'status' => 'pending',
        ]);

        DB::table('job_schedule_rooms')
            ->where('job_schedule_id', 43)
            ->where('job_advice_room_id', 90)
            ->update(['status' => 'completed', 'updated_at' => now()]);
        DB::table('job_advice_rooms')
            ->where('id', 90)
            ->update(['status' => 'completed', 'updated_at' => now()]);

        $roomsResponse = app(JobController::class)->getJobRooms(43);
        $roomsPayload = $roomsResponse->getData(true);

        $this->assertSame('success', $roomsPayload['status']);
        $this->assertCount(1, $roomsPayload['data']);
        $this->assertSame('pending', $roomsPayload['data'][0]['status']);
        $this->assertSame(91, $roomsPayload['data'][0]['id']);
    }

    public function test_mobile_job_rooms_keep_materials_after_team_reassignment(): void
    {
        DB::table('product_types')->insert([
            'id' => 700,
            'name' => 'Unit',
            'is_unit' => true,
            'has_serial_number' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('master_products')->insert([
            [
                'id' => 900,
                'product_type_id' => 700,
                'name' => 'Diffuser W300 Black',
                'sku' => 'DW300B',
                'unit' => 'pcs',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 901,
                'product_type_id' => 700,
                'name' => 'PURE Dispenser 7200',
                'sku' => 'PD7200',
                'unit' => 'pcs',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('serial_numbers')->insert([
            [
                'id' => 1000,
                'master_product_id' => 900,
                'serial_number' => 'DW300B2606031',
                'status' => 'on_hand',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 1001,
                'master_product_id' => 901,
                'serial_number' => 'PD72002606001',
                'status' => 'on_hand',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('master_rooms')->insert([
            'id' => 501,
            'room_name' => 'Lobby',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('contract_rooms')->insert([
            'id' => 71,
            'room_id' => 501,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('master_rentals')->insert([
            [
                'id' => 701,
                'rental_name' => 'ADS W300 300 ml baterai',
                'rental_type' => 'unit_refill',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 702,
                'rental_name' => 'Rental Unit Only',
                'rental_type' => 'unit_only',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('job_advice_rooms')->insert([
            [
                'id' => 92,
                'job_advice_id' => 30,
                'contract_room_id' => 71,
                'rental_product_id' => 701,
                'room_name' => 'Lobby',
                'rental_name' => 'ADS W300 300 ml baterai',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 93,
                'job_advice_id' => 30,
                'contract_room_id' => 71,
                'rental_product_id' => 702,
                'room_name' => 'Lobby',
                'rental_name' => 'Rental Unit Only',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('job_schedules')->insert([
            'id' => 44,
            'job_number' => 'JKT-IR/26-06/0001',
            'job_advice_id' => 30,
            'type' => 'install',
            'status' => 'in_progress',
            'room_id' => 501,
            'room_name' => 'Lobby',
            'schedule_date' => '2026-06-15',
            'material_checked' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_assign_schedules')->insert([
            [
                'id' => 64,
                'job_schedule_id' => 44,
                'team_id' => null,
                'status' => 'cancelled',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 65,
                'job_schedule_id' => 44,
                'team_id' => 10,
                'status' => 'assigned',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('material_issues')->insert([
            'id' => 88,
            'issue_number' => 'JKT-MI/26-06/0001',
            'status' => 'issued',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_assign_material_issues')->insert([
            'id' => 89,
            'job_assign_schedule_id' => 64,
            'material_issue_id' => 88,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('material_issue_items')->insert([
            [
                'id' => 90,
                'material_issue_id' => 88,
                'job_assign_schedule_id' => 64,
                'room_name' => 'Lobby',
                'product_id' => 900,
                'quantity' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 91,
                'material_issue_id' => 88,
                'job_assign_schedule_id' => 64,
                'room_name' => 'Lobby',
                'product_id' => 901,
                'quantity' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('inventory_issuings')->insert([
            'id' => 92,
            'issuing_number' => 'JKT-WI/26-06/0002',
            'reference_no' => 'JKT-MI/26-06/0001',
            'status' => 'sent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventory_issuing_items')->insert([
            [
                'id' => 93,
                'inventory_issuing_id' => 92,
                'job_assign_schedule_id' => 64,
                'room_name' => 'Lobby',
                'product_id' => 900,
                'serial_number_id' => 1000,
                'quantity_requested' => 1,
                'quantity_issued' => 1,
                'quantity_received' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 94,
                'inventory_issuing_id' => 92,
                'job_assign_schedule_id' => 64,
                'room_name' => 'Lobby',
                'product_id' => 901,
                'serial_number_id' => 1001,
                'quantity_requested' => 1,
                'quantity_issued' => 1,
                'quantity_received' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $request = Request::create('/api/v1/mobile/jobs/44/rooms', 'GET');
        $request->setUserResolver(fn () => User::find(1));
        $this->actingAs(User::find(1));

        $roomsResponse = app(JobController::class)->getJobRooms(44);
        $roomsPayload = $roomsResponse->getData(true);
        $products = collect($roomsPayload['data'][0]['products']);

        $this->assertSame('success', $roomsPayload['status']);

        // "Lobby" holds two rentals that each bring their own unit (Unit + Refill
        // plus Unit Only). QA 30 Aug 2026 on SBY-IR/26-08/0013: merged into one
        // card the technician had to work the room twice with no way to tell, or
        // pick, which rental they were on. Each rental is its own selectable task.
        $this->assertCount(2, $roomsPayload['data']);
        $this->assertSame([92, 93], collect($roomsPayload['data'])->pluck('id')->all());
        $this->assertSame(
            ['ADS W300 300 ml baterai', 'Rental Unit Only'],
            collect($roomsPayload['data'])->pluck('rental_name')->all()
        );
        $this->assertSame(
            ['Lobby - ADS W300 300 ml baterai', 'Lobby - Rental Unit Only'],
            collect($roomsPayload['data'])->pluck('display_name')->all()
        );
        $this->assertSame(
            ['Diffuser W300 Black', 'PURE Dispenser 7200'],
            $products->pluck('product_name')->all()
        );
        $this->assertSame(
            ['DW300B2606031', 'PD72002606001'],
            $products->pluck('serial_number')->all()
        );

        DB::table('job_schedule_rooms')
            ->where('job_schedule_id', 44)
            ->where('job_advice_room_id', 92)
            ->update(['status' => 'completed', 'updated_at' => now()]);
        DB::table('job_advice_rooms')
            ->where('id', 92)
            ->update(['status' => 'completed', 'updated_at' => now()]);

        $roomsResponse = app(JobController::class)->getJobRooms(44);
        $roomsPayload = $roomsResponse->getData(true);

        // Finishing one rental closes only that card; the other stays open and
        // keeps its own identity instead of silently taking over the same card.
        $this->assertSame('success', $roomsPayload['status']);
        $this->assertCount(2, $roomsPayload['data']);
        $this->assertSame('completed', $roomsPayload['data'][0]['status']);
        $this->assertSame(92, $roomsPayload['data'][0]['id']);
        $this->assertSame('pending', $roomsPayload['data'][1]['status']);
        $this->assertSame(93, $roomsPayload['data'][1]['id']);

        $detailRequest = Request::create('/api/v1/mobile/jobs/44', 'GET');
        $detailRequest->setUserResolver(fn () => User::find(1));
        $detailPayload = app(JobController::class)->getJobDetail($detailRequest, 44)->getData(true);

        // The job card's counter has to agree with the cards actually listed.
        $this->assertSame(2, $detailPayload['data']['total_rooms']);
        $this->assertSame(1, $detailPayload['data']['completed_rooms']);
    }

    public function test_mobile_service_job_splits_room_per_rental_when_owner_pointer_moved_on(): void
    {
        DB::table('master_rooms')->insert([
            'id' => 502,
            'room_name' => 'Ruang Anggrek',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('contract_rooms')->insert([
            'id' => 72,
            'room_id' => 502,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('master_rentals')->insert([
            [
                'id' => 711,
                'rental_name' => 'Rental07 - 1 x 1 Bulan',
                'rental_type' => 'unit_refill',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 712,
                'rental_name' => 'Rental 1x 1bln',
                'rental_type' => 'unit_refill',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // job_advice_rooms.service_job_schedule_id is rewritten every time a new
        // service period is generated, so while the technician works period 1 it
        // already points at the LAST period (47 here). The rentals are still this
        // job's tasks — job_schedule_rooms rows on job 46 say so.
        DB::table('job_advice_rooms')->insert([
            [
                'id' => 94,
                'job_advice_id' => 30,
                'contract_room_id' => 72,
                'rental_product_id' => 711,
                'room_name' => 'Ruang Anggrek',
                'rental_name' => 'Rental07 - 1 x 1 Bulan',
                'status' => 'pending',
                'service_job_schedule_id' => 47,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 95,
                'job_advice_id' => 30,
                'contract_room_id' => 72,
                'rental_product_id' => 712,
                'room_name' => 'Ruang Anggrek',
                'rental_name' => 'Rental 1x 1bln',
                'status' => 'pending',
                'service_job_schedule_id' => 47,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('job_schedules')->insert([
            [
                'id' => 46,
                'job_number' => 'SBY-CSR/26-08/0023',
                'job_advice_id' => 30,
                'type' => 'service_first',
                'status' => 'in_progress',
                'room_id' => 502,
                'room_name' => 'Ruang Anggrek',
                'period' => 1,
                'schedule_date' => '2026-08-20',
                'material_checked' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 47,
                'job_number' => null,
                'job_advice_id' => 30,
                'type' => 'service',
                'status' => 'scheduled',
                'room_id' => 502,
                'room_name' => 'Ruang Anggrek',
                'period' => 4,
                'schedule_date' => '2026-11-20',
                'material_checked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('job_schedule_rooms')->insert([
            [
                'id' => 54,
                'job_schedule_id' => 46,
                'job_advice_room_id' => 94,
                'room_name' => 'Ruang Anggrek',
                'room_id' => 502,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 55,
                'job_schedule_id' => 46,
                'job_advice_room_id' => 95,
                'room_name' => 'Ruang Anggrek',
                'room_id' => 502,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('job_assign_schedules')->insert([
            'id' => 66,
            'job_schedule_id' => 46,
            'team_id' => 10,
            'status' => 'assigned',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs(User::find(1));

        $roomsPayload = app(JobController::class)->getJobRooms(46)->getData(true);

        $this->assertSame('success', $roomsPayload['status']);
        $this->assertCount(2, $roomsPayload['data']);
        $this->assertSame([94, 95], collect($roomsPayload['data'])->pluck('id')->all());
        $this->assertSame(
            ['Rental07 - 1 x 1 Bulan', 'Rental 1x 1bln'],
            collect($roomsPayload['data'])->pluck('rental_name')->all()
        );
        $this->assertSame(
            [46, 46],
            collect($roomsPayload['data'])->pluck('job_schedule_id')->all()
        );
    }

    /**
     * QA 6 Sep 2026 (SBY-EXT/26-09/0001 -> SBY-RV/26-09/0003): after Done Job on a Change
     * Rental, ChangeRentalCompletionService raises the RV job for the REPLACED rental and
     * stamps all three pointers of that frozen job_advice_rooms row onto it. The newly
     * installed rental keeps NULL pointers, and the "no owner yet -> belongs everywhere"
     * fallback handed it to the RV job as well: the Remove job listed two rentals (web and
     * APK) and getJobRooms() even created a second job_schedule_rooms row for it.
     */
    public function test_mobile_remove_job_from_change_rental_only_lists_the_replaced_rental(): void
    {
        DB::table('master_rooms')->insert([
            'id' => 560,
            'room_name' => 'Ruang Ganti Rental 1 Room',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('contract_rooms')->insert([
            'id' => 88,
            'room_id' => 560,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('master_rentals')->insert([
            [
                'id' => 811,
                'rental_name' => 'Rental07 - 1 x 1 Bulan',
                'rental_type' => 'unit_refill',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 812,
                'rental_name' => 'Rental 1x 1bln',
                'rental_type' => 'unit_refill',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('job_advice_rooms')->insert([
            [
                // The rental the Change Rental job installed - no pointer of its own.
                'id' => 120,
                'job_advice_id' => 30,
                'contract_room_id' => 88,
                'rental_product_id' => 812,
                'room_name' => 'Ruang Ganti Rental 1 Room',
                'rental_name' => 'Rental 1x 1bln',
                'status' => 'completed',
                'install_job_schedule_id' => null,
                'service_job_schedule_id' => null,
                'remove_job_schedule_id' => null,
                'notes' => 'pasang ganti rental',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                // The replaced rental, frozen onto the RV job by ChangeRentalCompletionService.
                'id' => 121,
                'job_advice_id' => 30,
                'contract_room_id' => 88,
                'rental_product_id' => 811,
                'room_name' => 'Ruang Ganti Rental 1 Room',
                'rental_name' => 'Rental07 - 1 x 1 Bulan',
                'status' => 'scheduled',
                'install_job_schedule_id' => 61,
                'service_job_schedule_id' => 61,
                'remove_job_schedule_id' => 61,
                'notes' => 'Rental lama, digantikan oleh Change Rental SBY-EXT/26-09/0001.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('job_schedules')->insert([
            [
                'id' => 60,
                'job_number' => 'SBY-EXT/26-09/0001',
                'job_advice_id' => 30,
                'type' => 'change',
                'status' => 'done_job',
                'room_id' => 560,
                'room_name' => 'Ruang Ganti Rental 1 Room',
                'schedule_date' => '2026-09-06',
                'material_checked' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 61,
                'job_number' => 'SBY-RV/26-09/0003',
                'job_advice_id' => 30,
                'type' => 'remove',
                'status' => 'assign_team',
                'room_id' => 560,
                'room_name' => 'Ruang Ganti Rental 1 Room',
                'schedule_date' => '2026-09-06',
                'material_checked' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('job_schedule_rooms')->insert([
            [
                'id' => 70,
                'job_schedule_id' => 60,
                'job_advice_room_id' => 120,
                'room_name' => 'Ruang Ganti Rental 1 Room',
                'room_id' => 560,
                'status' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 71,
                'job_schedule_id' => 61,
                'job_advice_room_id' => 121,
                'room_name' => 'Ruang Ganti Rental 1 Room',
                'room_id' => 560,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('job_assign_schedules')->insert([
            'id' => 80,
            'job_schedule_id' => 61,
            'team_id' => 10,
            'status' => 'assigned',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs(User::find(1));

        $roomsPayload = app(JobController::class)->getJobRooms(61)->getData(true);

        $this->assertSame('success', $roomsPayload['status']);
        $this->assertSame(
            ['Rental07 - 1 x 1 Bulan'],
            collect($roomsPayload['data'])->pluck('rental_name')->all()
        );
        $this->assertSame([121], collect($roomsPayload['data'])->pluck('id')->all());

        // The new rental must not be grafted onto the Remove job as a room row either -
        // that row is what makes the web Rental & Team tab show it too.
        $this->assertSame(1, DB::table('job_schedule_rooms')->where('job_schedule_id', 61)->count());
        $this->assertSame(0, DB::table('job_schedule_rooms')->where('job_schedule_id', 61)->where('job_advice_room_id', 120)->count());
    }

    public function test_mobile_job_rooms_reconcile_install_room_completed_from_active_unit_on_wall(): void
    {
        DB::table('buildings')->insert([
            'id' => 9,
            'name' => 'Gedung QA',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_types')->insert([
            'id' => 700,
            'name' => 'Unit',
            'is_unit' => true,
            'has_serial_number' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('master_products')->insert([
            'id' => 900,
            'product_type_id' => 700,
            'name' => 'Diffuser W300 Black',
            'sku' => 'DW300B',
            'unit' => 'pcs',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('serial_numbers')->insert([
            'id' => 1000,
            'master_product_id' => 900,
            'serial_number' => 'DW300B2606031',
            'status' => 'in_use',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('master_rooms')->insert([
            'id' => 501,
            'room_name' => 'Lobby',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('contract_rooms')->insert([
            'id' => 71,
            'room_id' => 501,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_advice_rooms')->insert([
            'id' => 92,
            'job_advice_id' => 30,
            'contract_room_id' => 71,
            'room_name' => 'Lobby',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_schedules')->insert([
            'id' => 44,
            'job_number' => 'JKT-IR/26-06/0001',
            'job_advice_id' => 30,
            'type' => 'install',
            'status' => 'in_progress',
            'building_id' => 9,
            'room_id' => 501,
            'room_name' => 'Lobby',
            'schedule_date' => '2026-06-15',
            'material_checked' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_assign_schedules')->insert([
            'id' => 65,
            'job_schedule_id' => 44,
            'team_id' => 10,
            'status' => 'assigned',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventory_issuings')->insert([
            'id' => 92,
            'issuing_number' => 'JKT-WI/26-06/0002',
            'reference_no' => 'JKT-MI/26-06/0001',
            'status' => 'sent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventory_issuing_items')->insert([
            'id' => 93,
            'inventory_issuing_id' => 92,
            'job_assign_schedule_id' => 65,
            'room_name' => 'Lobby',
            'product_id' => 900,
            'serial_number_id' => 1000,
            'quantity_requested' => 1,
            'quantity_issued' => 1,
            'quantity_received' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_schedule_rooms')->insert([
            'id' => 50,
            'job_schedule_id' => 44,
            'job_advice_room_id' => 92,
            'room_name' => 'Lobby',
            'room_id' => 501,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('unit_on_walls')->insert([
            'id' => 1200,
            'customer_id' => 20,
            'building_id' => 9,
            'room_id' => 501,
            'product_id' => 900,
            'serial_number_id' => 1000,
            'serial_number' => 'DW300B2606031',
            'status' => 'active',
            'room_name' => 'Lobby',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/api/v1/mobile/jobs/44/rooms', 'GET');
        $request->setUserResolver(fn () => User::find(1));
        $this->actingAs(User::find(1));

        $roomsResponse = app(JobController::class)->getJobRooms(44);
        $roomsPayload = $roomsResponse->getData(true);

        $this->assertSame('success', $roomsPayload['status']);
        $this->assertSame('completed', $roomsPayload['data'][0]['status']);
        $this->assertDatabaseHas('job_schedule_rooms', [
            'id' => 50,
            'status' => 'completed',
        ]);
    }

    public function test_mobile_service_room_list_is_not_blocked_by_stale_closed_ir(): void
    {
        DB::table('master_rooms')->insert([
            'id' => 601,
            'room_name' => 'Ruang Aula',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('contract_rooms')->insert([
            'id' => 171,
            'room_id' => 601,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_advice_rooms')->insert([
            'id' => 192,
            'job_advice_id' => 30,
            'contract_room_id' => 171,
            'room_name' => 'Ruang Aula',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_schedules')->insert([
            [
                'id' => 144,
                'job_number' => 'SBY-IR/26-06/0100',
                'job_advice_id' => 30,
                'type' => 'install',
                'status' => 'meninggalkan_lokasi',
                'room_id' => 601,
                'room_name' => 'Ruang Aula',
                'schedule_date' => '2026-06-15',
                'material_checked' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 145,
                'job_number' => 'SBY-CSR/26-06/0100',
                'job_advice_id' => 30,
                'type' => 'service_first',
                'status' => 'barang_diambil',
                'room_id' => 601,
                'room_name' => 'Ruang Aula',
                'schedule_date' => '2026-06-16',
                'material_checked' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('job_schedule_rooms')->insert([
            [
                'id' => 150,
                'job_schedule_id' => 144,
                'job_advice_room_id' => 192,
                'room_name' => 'Ruang Aula',
                'room_id' => 601,
                'status' => 'completed',
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 151,
                'job_schedule_id' => 144,
                'job_advice_room_id' => null,
                'room_name' => 'Ruang Meeting',
                'room_id' => 602,
                'status' => 'cancelled',
                'notes' => 'Pekerjaan tidak selesai, dipindahkan ke Job baru.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 152,
                'job_schedule_id' => 145,
                'job_advice_room_id' => 192,
                'room_name' => 'Ruang Aula',
                'room_id' => 601,
                'status' => 'completed',
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('job_assign_schedules')->insert([
            'id' => 165,
            'job_schedule_id' => 145,
            'team_id' => 10,
            'status' => 'assigned',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/api/v1/mobile/jobs/145/rooms', 'GET');
        $request->setUserResolver(fn () => User::find(1));
        $this->actingAs(User::find(1));

        $roomsResponse = app(JobController::class)->getJobRooms(145);
        $roomsPayload = $roomsResponse->getData(true);

        $this->assertSame('success', $roomsPayload['status']);
        $this->assertSame('Ruang Aula', $roomsPayload['data'][0]['name']);
        $this->assertFalse($roomsPayload['data'][0]['is_blocked_by_ir']);
    }

    /**
     * QA 1 Sep 2026, JA SBY-JA/26-08/0039 period 3 (jobs 718 "Ruang Complain" / 721 "Ruang
     * Extra"): Ruang Extra's Fragrance Lemongrass Mix showed up in Ruang Complain's product
     * list too. Auto-generated period-2+ service schedules never get job_schedules.room_id
     * populated (only the first period's schedule does), so the "which schedule actually owns
     * this room group" resolution in getJobRooms() always missed and fell back to whichever
     * schedule the request happened to be anchored on - collapsing every room of a multi-room
     * Job Advice onto one schedule's job_assign_schedule_id when matching material issue items.
     */
    public function test_mobile_job_rooms_do_not_leak_materials_between_sibling_rooms_with_null_schedule_room_id(): void
    {
        DB::table('product_types')->insert([
            'id' => 710,
            'name' => 'Refill',
            'is_unit' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('master_products')->insert([
            ['id' => 910, 'product_type_id' => 710, 'name' => 'Fragrance Amberwood Sport Mix 1 100 ml', 'sku' => 'AMB100', 'unit' => 'unit', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 911, 'product_type_id' => 710, 'name' => 'All Purpose Cleaner 100 ml', 'sku' => 'APC100', 'unit' => 'unit', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 912, 'product_type_id' => 710, 'name' => 'Fragrance Lemongrass Mix 100 ml', 'sku' => 'LMG100', 'unit' => 'unit', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('master_rooms')->insert([
            ['id' => 510, 'room_name' => 'Ruang Complain', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 511, 'room_name' => 'Ruang Extra', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('contract_rooms')->insert([
            ['id' => 80, 'room_id' => 510, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 81, 'room_id' => 511, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('job_advice_rooms')->insert([
            ['id' => 94, 'job_advice_id' => 30, 'contract_room_id' => 80, 'room_name' => 'Ruang Complain', 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 95, 'job_advice_id' => 30, 'contract_room_id' => 81, 'room_name' => 'Ruang Extra', 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Both auto-generated for period 3, sharing one job_number - and both with room_id
        // left NULL, exactly like ServiceSchedulingService::generateAllRemainingServices()
        // creates them.
        DB::table('job_schedules')->insert([
            ['id' => 45, 'job_number' => 'SBY-CSR/26-10/0011', 'job_advice_id' => 30, 'type' => 'service', 'status' => 'in_progress', 'room_id' => null, 'material_checked' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 46, 'job_number' => 'SBY-CSR/26-10/0011', 'job_advice_id' => 30, 'type' => 'service', 'status' => 'in_progress', 'room_id' => null, 'material_checked' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('job_schedule_rooms')->insert([
            ['id' => 160, 'job_schedule_id' => 45, 'job_advice_room_id' => 94, 'room_name' => 'Ruang Complain', 'room_id' => 510, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 161, 'job_schedule_id' => 46, 'job_advice_room_id' => 95, 'room_name' => 'Ruang Extra', 'room_id' => 511, 'status' => 'completed', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('job_assign_schedules')->insert([
            ['id' => 70, 'job_schedule_id' => 45, 'team_id' => 10, 'status' => 'assigned', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 71, 'job_schedule_id' => 46, 'team_id' => 10, 'status' => 'assigned', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // One Material Issue covers both rooms - exactly the QA data shape (job_assign_
        // material_issues links it to BOTH job 45's and job 46's assignment).
        DB::table('material_issues')->insert([
            'id' => 95, 'issue_number' => 'SBY-MI/26-08/0080', 'status' => 'issued', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('job_assign_material_issues')->insert([
            ['id' => 96, 'job_assign_schedule_id' => 70, 'material_issue_id' => 95, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 97, 'job_assign_schedule_id' => 71, 'material_issue_id' => 95, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('inventory_issuings')->insert([
            'id' => 97, 'issuing_number' => 'SBY-WI/26-08/0069', 'reference_no' => 'SBY-MI/26-08/0080', 'status' => 'sent', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('inventory_issuing_items')->insert([
            ['id' => 98, 'inventory_issuing_id' => 97, 'job_assign_schedule_id' => 70, 'room_name' => 'Ruang Complain', 'product_id' => 910, 'quantity_requested' => 1, 'quantity_issued' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 99, 'inventory_issuing_id' => 97, 'job_assign_schedule_id' => 70, 'room_name' => 'Ruang Complain', 'product_id' => 911, 'quantity_requested' => 1, 'quantity_issued' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 100, 'inventory_issuing_id' => 97, 'job_assign_schedule_id' => 71, 'room_name' => 'Ruang Extra', 'product_id' => 912, 'quantity_requested' => 1, 'quantity_issued' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 101, 'inventory_issuing_id' => 97, 'job_assign_schedule_id' => 71, 'room_name' => 'Ruang Extra', 'product_id' => 911, 'quantity_requested' => 1, 'quantity_issued' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->actingAs(User::find(1));

        // The app is anchored on job 46 (Ruang Extra) but the response must still list Ruang
        // Complain's own materials only - not job 46's assignment's materials.
        $roomsResponse = app(JobController::class)->getJobRooms(46);
        $roomsPayload = $roomsResponse->getData(true);

        $this->assertSame('success', $roomsPayload['status']);

        $complainRoom = collect($roomsPayload['data'])->firstWhere('name', 'Ruang Complain');
        $this->assertNotNull($complainRoom, 'Expected Ruang Complain to still be listed.');

        $complainProducts = collect($complainRoom['products'])->pluck('product_name')->all();
        $this->assertSame(
            ['Fragrance Amberwood Sport Mix 1 100 ml', 'All Purpose Cleaner 100 ml'],
            $complainProducts,
            'Ruang Complain must only show its own materials, not Ruang Extra\'s Lemongrass Mix.'
        );

        $extraRoom = collect($roomsPayload['data'])->firstWhere('name', 'Ruang Extra');
        $this->assertNotNull($extraRoom);
        $extraProducts = collect($extraRoom['products'])->pluck('product_name')->all();
        $this->assertSame(
            ['Fragrance Lemongrass Mix 100 ml', 'All Purpose Cleaner 100 ml'],
            $extraProducts,
            'Ruang Extra must only show its own materials, not Ruang Complain\'s Amberwood Sport Mix.'
        );
    }

    public function test_mobile_job_detail_counts_a_sibling_rooms_completion_when_schedule_room_id_is_null(): void
    {
        // QA 1 Sep 2026, SBY-CSR/26-10/0011: after both rooms of the period were completed,
        // getJobRooms() reported both "completed" while the job detail still said 1 of 2 - the
        // room counter resolved a room's owning schedule through job_schedules.room_id, which
        // auto-generated period-2+ schedules never have, so a sibling's room was checked
        // against the anchor job (where it has no row) and counted unfinished.
        DB::table('master_rooms')->insert([
            ['id' => 520, 'room_name' => 'Ruang Complain', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 521, 'room_name' => 'Ruang Extra', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('contract_rooms')->insert([
            ['id' => 84, 'room_id' => 520, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 85, 'room_id' => 521, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('job_advice_rooms')->insert([
            ['id' => 96, 'job_advice_id' => 30, 'contract_room_id' => 84, 'room_name' => 'Ruang Complain', 'status' => 'completed', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 97, 'job_advice_id' => 30, 'contract_room_id' => 85, 'room_name' => 'Ruang Extra', 'status' => 'completed', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('job_schedules')->insert([
            ['id' => 47, 'job_number' => 'SBY-CSR/26-10/0011', 'job_advice_id' => 30, 'type' => 'service', 'status' => 'teknisi_selesai_pengerjaan', 'room_id' => null, 'material_checked' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 48, 'job_number' => 'SBY-CSR/26-10/0011', 'job_advice_id' => 30, 'type' => 'service', 'status' => 'teknisi_selesai_pengerjaan', 'room_id' => null, 'material_checked' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('job_schedule_rooms')->insert([
            ['id' => 162, 'job_schedule_id' => 47, 'job_advice_room_id' => 96, 'room_name' => 'Ruang Complain', 'room_id' => 520, 'status' => 'completed', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 163, 'job_schedule_id' => 48, 'job_advice_room_id' => 97, 'room_name' => 'Ruang Extra', 'room_id' => 521, 'status' => 'completed', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('job_assign_schedules')->insert([
            ['id' => 72, 'job_schedule_id' => 47, 'team_id' => 10, 'status' => 'assigned', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 73, 'job_schedule_id' => 48, 'team_id' => 10, 'status' => 'assigned', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->actingAs(User::find(1));

        // Whichever of the two the app is anchored on must agree that the work is 2 of 2 done.
        foreach ([47, 48] as $anchorJobId) {
            $request = Request::create("/api/v1/mobile/jobs/{$anchorJobId}", 'GET');
            $request->setUserResolver(fn () => User::find(1));

            $payload = app(JobController::class)->getJobDetail($request, $anchorJobId)->getData(true);

            $this->assertSame('success', $payload['status']);
            $this->assertSame(2, $payload['data']['total_rooms'], "Job {$anchorJobId} should see both rooms.");
            $this->assertSame(
                2,
                $payload['data']['completed_rooms'],
                "Job {$anchorJobId} must count the sibling's completed room too."
            );
        }
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('team_name')->nullable();
            $table->foreignId('team_head_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('job_advices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('customer_contact_id')->nullable();
            $table->foreignId('contract_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_name')->nullable();
            $table->string('room_floor')->nullable();
            $table->string('room_type')->nullable();
            $table->string('room_temperature')->nullable();
            $table->string('room_intensity')->nullable();
            $table->string('room_installation_type')->nullable();
            $table->decimal('room_length', 10, 2)->nullable();
            $table->decimal('room_width', 10, 2)->nullable();
            $table->decimal('room_height', 10, 2)->nullable();
            $table->decimal('area', 10, 2)->nullable();
            $table->decimal('volume', 10, 2)->nullable();
            $table->string('room_remark')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contract_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->timestamps();
        });

        Schema::create('master_rentals', function (Blueprint $table) {
            $table->id();
            $table->string('rental_name')->nullable();
            $table->string('rental_type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->boolean('is_unit')->default(false);
            $table->boolean('has_serial_number')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->boolean('is_unit')->default(false);
            $table->boolean('has_serial_number')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_type_id')->nullable();
            $table->foreignId('product_category_id')->nullable();
            $table->string('name')->nullable();
            $table->string('sku')->nullable();
            $table->string('unit')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('rental_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_rental_id')->nullable();
            $table->string('component_name')->nullable();
            $table->integer('quantity')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('rental_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_rental_id')->nullable();
            $table->foreignId('product_category_id')->nullable();
            $table->foreignId('product_type_id')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->integer('quantity')->nullable();
            $table->integer('bom_rental_qty')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('rental_component_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_component_id')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->boolean('is_preferred')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_advice_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_advice_id')->nullable();
            $table->foreignId('contract_room_id')->nullable();
            $table->foreignId('quotation_room_id')->nullable();
            $table->foreignId('rental_product_id')->nullable();
            $table->string('room_name')->nullable();
            $table->string('rental_name')->nullable();
            $table->integer('quantity')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('install_job_schedule_id')->nullable();
            $table->foreignId('service_job_schedule_id')->nullable();
            $table->foreignId('remove_job_schedule_id')->nullable();
            $table->foreignId('existing_unit_on_wall_id')->nullable();
            $table->boolean('unit_already_installed')->default(false);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
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
            $table->foreignId('room_id')->nullable();
            $table->string('room_name')->nullable();
            $table->integer('period')->nullable();
            $table->date('schedule_date')->nullable();
            $table->boolean('material_checked')->default(false);
            $table->timestamp('material_checked_at')->nullable();
            $table->text('notes')->nullable();
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

        Schema::create('job_assign_material_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_assign_schedule_id')->nullable();
            $table->foreignId('material_issue_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_issues', function (Blueprint $table) {
            $table->id();
            $table->string('issue_number')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_issue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_issue_id')->nullable();
            $table->foreignId('job_assign_schedule_id')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->string('room_name')->nullable();
            $table->integer('quantity')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_product_id')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('status')->nullable();
            $table->string('condition_status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_issuings', function (Blueprint $table) {
            $table->id();
            $table->string('issuing_number')->nullable();
            $table->string('reference_no')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_issuing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_issuing_id')->nullable();
            $table->foreignId('job_assign_schedule_id')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->foreignId('serial_number_id')->nullable();
            $table->string('room_name')->nullable();
            $table->integer('quantity_requested')->default(0);
            $table->integer('quantity_issued')->default(0);
            $table->integer('quantity_received')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('job_schedule_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('job_advice_room_id')->nullable();
            $table->string('room_name')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->string('status')->nullable();
            $table->string('material_return_status')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable();
            $table->text('completion_notes')->nullable();
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

        Schema::create('job_schedule_room_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('job_schedule_room_id')->nullable();
            $table->foreignId('job_advice_room_id')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->foreignId('job_assign_schedule_id')->nullable();
            $table->boolean('is_custom')->default(false);
            $table->date('assigned_date')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('job_schedule_id')->nullable();
            $table->timestamps();
        });

        Schema::create('unit_on_walls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('building_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->foreignId('rental_id')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->foreignId('serial_number_id')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('status')->nullable();
            $table->string('room_name')->nullable();
            $table->string('rental_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
