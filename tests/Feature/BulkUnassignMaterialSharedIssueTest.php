<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobScheduleController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Satu Job Advice berisi beberapa ruangan menghasilkan beberapa job saudara yang
 * berbagi SATU MaterialIssue (blok "MOM CONSOLIDATION" di
 * JobScheduleController::autoCreateMaterialIssue()).
 *
 * Unassign Material atas semuanya harus melepas semuanya. Versi lama hanya melepas
 * job terakhir dalam loop: tautan JobAssignMaterialIssue baru dihapus kalau header MI
 * bersama itu ikut habis, sehingga tiga job pertama tetap memegang job_number dan
 * status MATERIAL ASSIGN-nya (QA "Revisi 1", 21 Sep 2026).
 */
class BulkUnassignMaterialSharedIssueTest extends TestCase
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

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->nullable();
            $table->foreignId('job_advice_id')->nullable();
            $table->foreignId('building_id')->nullable();
            $table->string('type')->nullable();
            $table->integer('period')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedule_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->string('room_name')->nullable();
            $table->string('status')->nullable();
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
            $table->foreignId('warehouse_id')->nullable();
            $table->date('issue_date')->nullable();
            $table->string('reference_no')->nullable();
            $table->string('status')->nullable();
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
            $table->timestamps();
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->string('movement_type')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->integer('quantity')->default(0);
            $table->string('reference_no')->nullable();
            $table->string('reference_type')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_receivings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issuing_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouse_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('master_product_id')->nullable();
            $table->integer('quantity')->default(0);
            $table->integer('minimum_stock')->default(0);
            $table->integer('maximum_stock')->default(1000);
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
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
            'master_products',
            'warehouse_products',
            'inventory_receivings',
            'inventory_movements',
            'inventory_issuing_items',
            'inventory_issuings',
            'job_assign_material_issues',
            'material_issue_items',
            'material_issues',
            'job_assign_schedules',
            'job_schedule_rooms',
            'job_schedules',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_every_sibling_job_sharing_one_material_issue_is_released(): void
    {
        $this->seedSiblingsSharingOneMaterialIssue();

        $request = Request::create('/operational/job-schedules/bulk-unassign-material', 'POST', [
            'ids' => [1, 2, 3, 4],
            'room_ids' => [201, 202, 203, 204],
            'strict_selection' => true,
        ]);

        $response = app(JobScheduleController::class)->bulkUnassignMaterial($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            'Berhasil membatalkan material assign untuk 4 job. ',
            $response->getData(true)['message']
        );

        foreach ([1, 2, 3, 4] as $jobId) {
            $this->assertDatabaseHas('job_schedules', [
                'id' => $jobId,
                'job_number' => null,
                'status' => 'new_job',
            ]);
        }

        foreach ([30, 31, 32, 33] as $linkId) {
            $this->assertSoftDeleted('job_assign_material_issues', ['id' => $linkId]);
        }

        $this->assertSoftDeleted('material_issues', ['id' => 10]);
        $this->assertSoftDeleted('inventory_issuings', ['id' => 50]);
        $this->assertSame(0, DB::table('inventory_issuing_items')->count());
    }

    public function test_unselected_sibling_keeps_its_share_of_the_material_issue(): void
    {
        $this->seedSiblingsSharingOneMaterialIssue();

        $request = Request::create('/operational/job-schedules/bulk-unassign-material', 'POST', [
            'ids' => [1],
            'room_ids' => [201],
            'strict_selection' => true,
        ]);

        $response = app(JobScheduleController::class)->bulkUnassignMaterial($request);

        $this->assertSame(200, $response->getStatusCode());

        $this->assertDatabaseHas('job_schedules', [
            'id' => 1,
            'job_number' => null,
            'status' => 'new_job',
        ]);
        $this->assertSoftDeleted('job_assign_material_issues', ['id' => 30]);
        $this->assertSoftDeleted('material_issue_items', ['id' => 40]);

        // Job saudara yang tidak dipilih tidak boleh ikut terlepas, dan header MI
        // bersamanya harus tetap hidup karena masih dipakai mereka.
        foreach ([2, 3, 4] as $jobId) {
            $this->assertDatabaseHas('job_schedules', [
                'id' => $jobId,
                'job_number' => 'JKT-IR/26-09/0001',
                'status' => 'assign_material',
            ]);
        }

        $this->assertDatabaseHas('material_issues', ['id' => 10, 'deleted_at' => null]);
        $this->assertDatabaseHas('inventory_issuings', ['id' => 50, 'deleted_at' => null]);
        $this->assertDatabaseMissing('inventory_issuing_items', ['id' => 60]);
        $this->assertSame(3, DB::table('inventory_issuing_items')->count());
    }

    /**
     * 4 ruangan -> 4 job schedule ber-job_number sama -> SATU MaterialIssue bersama,
     * dengan tiap barisnya distempel job_assign_schedule_id pemiliknya.
     */
    private function seedSiblingsSharingOneMaterialIssue(): void
    {
        DB::table('master_products')->insert([
            'id' => 100,
            'name' => 'Aroma Diffuser Model C100 Black',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $rooms = [
            1 => 'LOBBY',
            2 => 'Lobby Simo',
            3 => 'Ruang Periksa',
            4 => 'Ruang Periksa Simo',
        ];

        foreach ($rooms as $jobId => $roomName) {
            DB::table('job_schedules')->insert([
                'id' => $jobId,
                'job_number' => 'JKT-IR/26-09/0001',
                'job_advice_id' => 1000,
                'building_id' => 2000,
                'type' => 'install',
                'period' => 1,
                'status' => 'assign_material',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('job_schedule_rooms')->insert([
                'id' => 200 + $jobId,
                'job_schedule_id' => $jobId,
                'room_name' => $roomName,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('job_assign_schedules')->insert([
                'id' => 19 + $jobId,
                'job_schedule_id' => $jobId,
                'team_id' => null,
                'status' => 'assigned',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('job_assign_material_issues')->insert([
                'id' => 29 + $jobId,
                'job_assign_schedule_id' => 19 + $jobId,
                'material_issue_id' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('material_issue_items')->insert([
                'id' => 39 + $jobId,
                'material_issue_id' => 10,
                'job_assign_schedule_id' => 19 + $jobId,
                'product_id' => 100,
                'room_name' => $roomName,
                'quantity' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('inventory_issuing_items')->insert([
                'id' => 59 + $jobId,
                'inventory_issuing_id' => 50,
                'job_assign_schedule_id' => 19 + $jobId,
                'product_id' => 100,
                'room_name' => $roomName,
                'quantity_requested' => 1,
                'quantity_issued' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('material_issues')->insert([
            'id' => 10,
            'issue_number' => 'JKT-MI/26-09/0001',
            'warehouse_id' => 5,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventory_issuings')->insert([
            'id' => 50,
            'issuing_number' => 'JKT-IIS/26-09/0001',
            'warehouse_id' => 5,
            'issue_date' => now()->toDateString(),
            'reference_no' => 'JKT-MI/26-09/0001',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
