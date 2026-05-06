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
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
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
}
