<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\ContractRenewal;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContractRenewalEligibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-05-04 10:00:00');

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->nullable();
            $table->string('contract_status')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->foreignId('quotation_id')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('existing_contract_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contract_renewals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('new_contract_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_advices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_advice_id')->nullable();
            $table->string('job_number')->nullable();
            $table->string('contract_number')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->date('ba_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('job_schedules');
        Schema::dropIfExists('job_advices');
        Schema::dropIfExists('contract_renewals');
        Schema::dropIfExists('quotations');
        Schema::dropIfExists('contracts');

        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_contract_with_unfinished_job_cannot_be_renewed(): void
    {
        $contract = $this->createContract('JKT-CA/26-03/0005');
        $this->createJob($contract, 'JKT-IR/26-03/0001', 'install', 'scheduled');

        $this->assertFalse($contract->fresh()->canBeRenewedSafely());
        $this->assertStringContainsString('Job Schedule aktif/belum selesai', $contract->fresh()->getRenewalBlockReason());
        $this->assertFalse(ContractRenewal::isEligibleForRenewal($contract->id)['eligible']);
    }

    public function test_contract_without_ba_date_cannot_be_renewed_even_when_jobs_are_final(): void
    {
        $contract = $this->createContract('JKT-CA/26-03/0005');
        $this->createJob($contract, 'JKT-CSR/26-03/0001', 'service', 'done_job');

        $this->assertFalse($contract->fresh()->canBeRenewedSafely());
        $this->assertStringContainsString('belum dimulai', $contract->fresh()->getRenewalBlockReason());
        $this->assertFalse(ContractRenewal::isEligibleForRenewal($contract->id)['eligible']);
    }

    public function test_contract_with_final_jobs_and_ba_date_can_be_renewed_inside_window(): void
    {
        $contract = $this->createContract('JKT-CA/26-03/0005');
        $this->createJob($contract, 'JKT-CSR/26-03/0001', 'service', 'done_job', '2025-05-15');

        $eligibility = ContractRenewal::isEligibleForRenewal($contract->id);

        $this->assertTrue($contract->fresh()->canBeRenewedSafely());
        $this->assertTrue($eligibility['eligible']);
    }

    private function createContract(string $contractNumber): Contract
    {
        return Contract::create([
            'contract_number' => $contractNumber,
            'contract_status' => 'active',
            'start_date' => '2025-05-01',
            'end_date' => '2026-05-15',
        ]);
    }

    private function createJob(Contract $contract, string $jobNumber, string $type, string $status, ?string $baDate = null): void
    {
        $jobAdviceId = DB::table('job_advices')->insertGetId([
            'contract_id' => $contract->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_schedules')->insert([
            'job_advice_id' => $jobAdviceId,
            'job_number' => $jobNumber,
            'contract_number' => $contract->contract_number,
            'type' => $type,
            'status' => $status,
            'ba_date' => $baDate,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
