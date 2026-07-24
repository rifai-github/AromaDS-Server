<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Covers the dry-run detection logic only. autoCreateUnitOnWall()/
 * autoRemoveUnitOnWall() (what --apply re-runs) already have their own
 * fixtures elsewhere in this suite via real install/remove job completion —
 * duplicating their full JobAdvice/MaterialIssue/InventoryIssuing dependency
 * graph here would test the same code twice. What matters for this command
 * is that it (a) finds the right rows, (b) never calls into that automation
 * for a job that hasn't reached done_job, and (c) never touches data by
 * default.
 */
class AuditUnitOnWallSyncCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->boolean('has_serial_number')->default(false);
            $table->boolean('is_unit')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->boolean('has_serial_number')->default(false);
            $table->boolean('is_unit')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('sku')->nullable();
            $table->unsignedBigInteger('product_category_id')->nullable();
            $table->unsignedBigInteger('product_type_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number');
            $table->string('status')->nullable();
            $table->string('condition_status')->nullable();
            $table->string('location_type')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('master_product_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('unit_on_walls', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->nullable();
            $table->unsignedBigInteger('building_id')->nullable();
            $table->string('room_name')->nullable();
            $table->string('rental_name')->nullable();
            $table->string('product_name')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('install_date')->nullable();
            $table->string('status')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->unsignedBigInteger('rental_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('serial_number_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->unsignedBigInteger('job_advice_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedule_units', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_schedule_id')->nullable();
            $table->string('mac')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('job_schedule_units');
        Schema::dropIfExists('job_schedules');
        Schema::dropIfExists('unit_on_walls');
        Schema::dropIfExists('serial_numbers');
        Schema::dropIfExists('master_products');
        Schema::dropIfExists('product_types');
        Schema::dropIfExists('product_categories');

        parent::tearDown();
    }

    private function makeUnitProduct(): int
    {
        $categoryId = DB::table('product_categories')->insertGetId([
            'code' => 'DIFF',
            'name' => 'Diffuser',
            'has_serial_number' => true,
            'is_unit' => true,
        ]);

        return DB::table('master_products')->insertGetId([
            'name' => 'Diffuser 303',
            'product_category_id' => $categoryId,
        ]);
    }

    private function makeBatchProduct(): int
    {
        $categoryId = DB::table('product_categories')->insertGetId([
            'code' => 'RGB',
            'name' => 'Refill Green Bottle',
            'has_serial_number' => true,
            'is_unit' => false,
        ]);

        return DB::table('master_products')->insertGetId([
            'name' => 'Fragrance Green Tea',
            'product_category_id' => $categoryId,
        ]);
    }

    public function test_reports_installed_unit_serial_with_no_wall_record_and_names_the_stuck_job(): void
    {
        $productId = $this->makeUnitProduct();
        $snId = DB::table('serial_numbers')->insertGetId([
            'serial_number' => 'DIFF3030008',
            'status' => 'in_use',
            'location_type' => 'customer',
            'master_product_id' => $productId,
        ]);

        $jobId = DB::table('job_schedules')->insertGetId([
            'job_number' => 'SBY-IR/26-06/0007',
            'type' => 'install',
            'status' => 'meninggalkan_lokasi',
        ]);
        DB::table('job_schedule_units')->insert([
            'job_schedule_id' => $jobId,
            'mac' => 'DIFF3030008',
            'scanned_at' => now(),
        ]);

        $exitCode = Artisan::call('warehouse:audit-unit-on-wall-sync');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('DIFF3030008', $output);
        $this->assertStringContainsString('SBY-IR/26-06/0007', $output);
        $this->assertStringContainsString('not verified yet', $output);
        $this->assertStringContainsString('meninggalkan_lokasi', $output);

        // Dry-run: nothing written.
        $this->assertSame('in_use', DB::table('serial_numbers')->where('id', $snId)->value('status'));
        $this->assertSame(0, DB::table('unit_on_walls')->where('serial_number_id', $snId)->count());
    }

    public function test_does_not_flag_batch_serial_that_does_not_require_uniqueness(): void
    {
        $productId = $this->makeBatchProduct();
        DB::table('serial_numbers')->insert([
            'serial_number' => 'RGB1000001',
            'status' => 'in_use',
            'location_type' => 'customer',
            'master_product_id' => $productId,
        ]);

        $exitCode = Artisan::call('warehouse:audit-unit-on-wall-sync');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringNotContainsString('RGB1000001', $output);
        $this->assertStringContainsString('No installed unit-type serials missing', $output);
    }

    public function test_reports_active_wall_record_whose_serial_already_moved_on(): void
    {
        $productId = $this->makeUnitProduct();
        $snId = DB::table('serial_numbers')->insertGetId([
            'serial_number' => 'DIFF3030015',
            'status' => 'on_hand_remove',
            'location_type' => 'technician',
            'master_product_id' => $productId,
        ]);
        DB::table('unit_on_walls')->insert([
            'company_name' => 'Dunia Parfum',
            'room_name' => 'Toilet Karyawan',
            'status' => 'active',
            'serial_number_id' => $snId,
            'serial_number' => 'DIFF3030015',
        ]);

        $jobId = DB::table('job_schedules')->insertGetId([
            'job_number' => 'SBY-RV/26-07/0004',
            'type' => 'remove',
            'status' => 'done_job',
        ]);
        DB::table('job_schedule_units')->insert([
            'job_schedule_id' => $jobId,
            'mac' => 'DIFF3030015',
            'scanned_at' => now(),
        ]);

        $exitCode = Artisan::call('warehouse:audit-unit-on-wall-sync');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('DIFF3030015', $output);
        $this->assertStringContainsString('SBY-RV/26-07/0004', $output);
        $this->assertStringContainsString('ready, re-run with --apply', $output);

        // Dry-run: still active, nothing written.
        $this->assertSame('active', DB::table('unit_on_walls')->where('serial_number_id', $snId)->value('status'));
    }

    public function test_apply_never_touches_a_job_that_has_not_reached_done_job(): void
    {
        $productId = $this->makeUnitProduct();
        $snId = DB::table('serial_numbers')->insertGetId([
            'serial_number' => 'DW300B2606007',
            'status' => 'in_use',
            'location_type' => 'customer',
            'master_product_id' => $productId,
        ]);

        $jobId = DB::table('job_schedules')->insertGetId([
            'job_number' => 'SBY-IR/26-06/0010',
            'type' => 'install',
            'status' => 'teknisi_selesai_pengerjaan',
        ]);
        DB::table('job_schedule_units')->insert([
            'job_schedule_id' => $jobId,
            'mac' => 'DW300B2606007',
            'scanned_at' => now(),
        ]);

        $exitCode = Artisan::call('warehouse:audit-unit-on-wall-sync', ['--apply' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('will not touch', $output);

        // --apply must not fabricate a Unit On Wall row for an unverified job.
        $this->assertSame(0, DB::table('unit_on_walls')->where('serial_number_id', $snId)->count());
        $this->assertSame('in_use', DB::table('serial_numbers')->where('id', $snId)->value('status'));
    }
}
