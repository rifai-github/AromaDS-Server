<?php

namespace Tests\Feature;

use App\Services\DocumentNumberService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DocumentNumberServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->foreignId('city_id')->nullable();
            $table->foreignId('province_id')->nullable();
            $table->boolean('has_warehouse')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->foreignId('city_id')->nullable();
            $table->foreignId('province_id')->nullable();
            $table->foreignId('district_id')->nullable();
            $table->foreignId('subdistrict_id')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('operational_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable();
            $table->foreignId('province_id')->nullable();
            $table->foreignId('city_id')->nullable();
            $table->foreignId('district_id')->nullable();
            $table->foreignId('subdistrict_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Schema::dropIfExists('operational_areas');
        Schema::dropIfExists('buildings');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('job_schedules');

        parent::tearDown();
    }

    public function test_job_number_prefix_can_use_schedule_date_instead_of_today(): void
    {
        Carbon::setTestNow('2026-05-07');

        $number = app(DocumentNumberService::class)->generate(
            documentType: 'installation_report',
            branchCode: 'BDG',
            documentDate: '2031-09-14',
        );

        $this->assertSame('BDG-IR/31-09/0001', $number);
    }

    public function test_job_number_sequence_is_scoped_to_schedule_month_prefix(): void
    {
        DB::table('job_schedules')->insert([
            'job_number' => 'BDG-CSR/31-09/0001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $number = app(DocumentNumberService::class)->generate(
            documentType: 'customer_service_report',
            branchCode: 'BDG',
            documentDate: '2031-09-14',
        );

        $this->assertSame('BDG-CSR/31-09/0002', $number);
    }

    public function test_job_schedule_prefix_uses_service_branch_operational_area(): void
    {
        DB::table('branches')->insert([
            [
                'id' => 1,
                'code' => 'BDG',
                'name' => 'Bandung',
                'city_id' => 10,
                'province_id' => 32,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'code' => 'JKT',
                'name' => 'Jakarta',
                'city_id' => 20,
                'province_id' => 31,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('buildings')->insert([
            'id' => 1,
            'name' => 'Bogor Building',
            'city_id' => 10,
            'province_id' => 32,
            'branch_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('operational_areas')->insert([
            'branch_id' => 2,
            'city_id' => 10,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $number = app(DocumentNumberService::class)->generate(
            documentType: 'installation_free',
            buildingId: 1,
            documentDate: '2026-05-29',
        );

        $this->assertSame('JKT-IF/26-05/0001', $number);
    }
}
