<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobScheduleController;
use App\Models\Contract;
use App\Models\JobSchedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA "Revisi 1 - 15 okt 26" (kontrak renewal MDO-CA/26-10/0001):
 * - Start/End Date kontrak renewal kosong padahal job pertamanya (Service Pertama CSR) sudah selesai.
 * - Satu baris NEW JOB pecah jadi dua nomor JS setelah Material Assign.
 */
class QaRevisi15Okt2026Test extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('contracts', function (Blueprint $t) {
            $t->id();
            $t->string('contract_number')->nullable();
            $t->date('start_date')->nullable();
            $t->date('end_date')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('job_advices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('contract_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('job_schedules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('job_advice_id')->nullable();
            $t->string('type')->nullable();
            $t->date('ba_date')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        foreach (['job_schedules', 'job_advices', 'contracts'] as $table) {
            Schema::dropIfExists($table);
        }
        parent::tearDown();
    }

    private function renewalContractWithFirstJob(string $type): Contract
    {
        DB::table('contracts')->insert([
            'id' => 1, 'contract_number' => 'MDO-CA/26-10/0001',
            'start_date' => '2026-10-14', 'end_date' => '2027-04-14',
        ]);
        DB::table('job_advices')->insert(['id' => 1, 'contract_id' => 1]);
        DB::table('job_schedules')->insert(['id' => 1, 'job_advice_id' => 1, 'type' => $type, 'ba_date' => '2026-10-15']);

        return Contract::findOrFail(1);
    }

    public function test_renewal_contract_period_starts_at_the_first_csr_ba_date(): void
    {
        $contract = $this->renewalContractWithFirstJob('service_first');

        $this->assertSame('2026-10-15', \Carbon\Carbon::parse($contract->actual_start_date)->toDateString());
        $this->assertSame('2027-04-15', $contract->actual_end_date->toDateString());
    }

    public function test_six_month_period_ending_the_day_before_keeps_its_full_length(): void
    {
        // Data QA kontrak 21124: 14 Okt 2026 - 13 Apr 2027, BA CSR pertama 15 Okt.
        // diffInMonths() = 5,97 dan dulu dipotong jadi 5 -> End Date 15 Mar 2027.
        $contract = $this->renewalContractWithFirstJob('service_first');
        DB::table('contracts')->where('id', 1)->update(['end_date' => '2027-04-13']);
        $contract = Contract::findOrFail(1);

        $this->assertSame('2027-04-14', $contract->actual_end_date->toDateString());
    }

    public function test_remove_job_ba_date_still_does_not_start_the_contract(): void
    {
        $contract = $this->renewalContractWithFirstJob('remove');

        $this->assertNull($contract->actual_start_date);
        $this->assertNull($contract->actual_end_date);
    }

    private function batchKey(array $attributes): string
    {
        $job = new JobSchedule;
        $job->forceFill(array_merge(['job_advice_id' => 7, 'building_id' => 3, 'type' => 'service'], $attributes));

        $method = new \ReflectionMethod(JobScheduleController::class, 'materialAssignBatchKey');

        return $method->invoke(app(JobScheduleController::class), $job);
    }

    public function test_rooms_of_one_service_period_share_one_job_number_even_with_different_dates(): void
    {
        // Tanggal service berikutnya dihitung per ruangan, jadi ruangan satu periode (satu baris
        // NEW JOB di Job View) bisa beda schedule_date. Dulu kuncinya tanggal -> 2 nomor JS.
        $wawali = $this->batchKey(['period' => '2', 'schedule_date' => '2026-11-14']);
        $kasubag = $this->batchKey(['period' => '2', 'schedule_date' => '2026-11-16']);

        $this->assertSame($wawali, $kasubag);
        $this->assertNotSame($wawali, $this->batchKey(['period' => '3', 'schedule_date' => '2026-11-14']));
        $this->assertNotSame($wawali, $this->batchKey(['period' => '2', 'schedule_date' => '2026-11-14', 'building_id' => 4]));
    }

    public function test_jobs_without_a_period_are_still_split_by_date(): void
    {
        $this->assertNotSame(
            $this->batchKey(['period' => null, 'schedule_date' => '2026-11-14']),
            $this->batchKey(['period' => null, 'schedule_date' => '2026-11-16'])
        );
        $this->assertSame(
            $this->batchKey(['period' => null, 'schedule_date' => '2026-11-14']),
            $this->batchKey(['period' => '', 'schedule_date' => '2026-11-14'])
        );
    }
}
