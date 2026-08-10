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

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('province_id')->nullable();
            $table->string('name')->nullable();
            $table->string('type')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Schema::dropIfExists('provinces');
        Schema::dropIfExists('cities');
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

    /**
     * `cities` carries two overlapping legacy imports, so the same city (e.g.
     * "KABUPATEN SEMARANG") exists twice under different IDs. A building and
     * its branch can each end up on a different one of those IDs, so the exact
     * city_id match in getBranchFromLocation() silently fails and the document
     * falls back to the hardcoded JKT default - found via QA report 10 Agu
     * 2026 where a Job Advice for a Semarang contract got a JKT- number.
     */
    public function test_branch_resolves_by_city_name_when_city_ids_are_duplicated(): void
    {
        DB::table('cities')->insert([
            ['id' => 203, 'name' => 'KABUPATEN SEMARANG', 'province_id' => 22, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 582, 'name' => 'KABUPATEN SEMARANG', 'province_id' => 56, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('branches')->insert([
            'id' => 1,
            'code' => 'SMG',
            'name' => 'SEMARANG',
            'city_id' => 582, // the branch's own duplicate-city row
            'province_id' => 56,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('buildings')->insert([
            'id' => 1,
            'name' => 'Semarang Building',
            'city_id' => 203, // a *different* duplicate row for the same city
            'province_id' => 22,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $number = app(DocumentNumberService::class)->generate(
            documentType: 'installation_free',
            buildingId: 1,
            documentDate: '2026-08-10',
        );

        $this->assertSame('SMG-IF/26-08/0001', $number);
    }

    public function test_falls_back_to_jkt_default_when_no_city_matches_at_all(): void
    {
        DB::table('cities')->insert([
            'id' => 203,
            'name' => 'KOTA YANG TIDAK PUNYA CABANG',
            'province_id' => 22,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('branches')->insert([
            'id' => 1,
            'code' => 'SMG',
            'name' => 'SEMARANG',
            'city_id' => 582,
            'province_id' => 56,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('buildings')->insert([
            'id' => 1,
            'name' => 'Unmatched Building',
            'city_id' => 203,
            'province_id' => 22,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $number = app(DocumentNumberService::class)->generate(
            documentType: 'installation_free',
            buildingId: 1,
            documentDate: '2026-08-10',
        );

        $this->assertSame('JKT-IF/26-08/0001', $number);
    }

    /**
     * In production the "203 vs 582" duplicate wasn't just two live rows - the
     * stale one (203) had already been soft-deleted, presumably in a prior
     * cleanup pass, while `buildings.city_id` was never repointed at the
     * surviving row (582). City::find() (and any withoutTrashed lookup)
     * silently returns nothing for a soft-deleted id, so the name lookup must
     * use withTrashed() or this fix does nothing for exactly the case it was
     * written for.
     */
    public function test_branch_resolves_by_city_name_even_when_the_buildings_city_row_is_soft_deleted(): void
    {
        DB::table('cities')->insert([
            ['id' => 203, 'name' => 'KABUPATEN SEMARANG', 'province_id' => 22, 'created_at' => now(), 'updated_at' => now(), 'deleted_at' => now()],
            ['id' => 582, 'name' => 'KABUPATEN SEMARANG', 'province_id' => 56, 'created_at' => now(), 'updated_at' => now(), 'deleted_at' => null],
        ]);

        DB::table('branches')->insert([
            'id' => 1,
            'code' => 'SMG',
            'name' => 'SEMARANG',
            'city_id' => 582,
            'province_id' => 56,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('buildings')->insert([
            'id' => 1,
            'name' => 'Semarang Building',
            'city_id' => 203, // stale reference to the now soft-deleted row
            'province_id' => 22,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $number = app(DocumentNumberService::class)->generate(
            documentType: 'installation_free',
            buildingId: 1,
            documentDate: '2026-08-10',
        );

        $this->assertSame('SMG-IF/26-08/0001', $number);
    }
}
