<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\ContractRenewal;
use App\Http\Controllers\Marketing\ContractRenewalController;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContractRenewalEligibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-05-04 10:00:00');

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->nullable();
            $table->string('contract_status')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('marketing_id')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->foreignId('quotation_id')->nullable();
            $table->decimal('contract_value', 12, 2)->nullable();
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
            $table->date('schedule_date')->nullable();
            $table->date('ba_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_name')->nullable();
            $table->timestamps();
        });

        Schema::create('billing_groups', function (Blueprint $table) {
            $table->id();
            $table->string('billing_group_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contract_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->foreignId('billing_group_id')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('contract_rooms');
        Schema::dropIfExists('billing_groups');
        Schema::dropIfExists('master_rooms');
        Schema::dropIfExists('job_schedules');
        Schema::dropIfExists('job_advices');
        Schema::dropIfExists('contract_renewals');
        Schema::dropIfExists('quotations');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('customers');

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

    public function test_contract_with_final_jobs_and_ba_date_can_be_renewed_before_renewal_window(): void
    {
        $contract = $this->createContract('JKT-CA/26-03/0006', [
            'start_date' => '2026-05-01',
            'end_date' => '2027-05-01',
        ]);
        $this->createJob($contract, 'JKT-IR/26-05/0001', 'install', 'done_job', '2026-05-02');
        $this->createJob($contract, 'JKT-CSR/26-05/0001', 'service', 'done_job', '2026-05-03');

        $eligibility = ContractRenewal::isEligibleForRenewal($contract->id);

        $this->assertTrue($contract->fresh()->canBeRenewedSafely());
        $this->assertTrue($eligibility['eligible']);
        $this->assertGreaterThan($eligibility['renewal_window_days'], $eligibility['days_until_expiry']);
    }

    public function test_contract_with_done_initial_jobs_can_be_renewed_when_future_service_jobs_exist(): void
    {
        $contract = $this->createContract('BDG-CA/26-05/0001', [
            'start_date' => '2026-05-01',
            'end_date' => '2027-05-01',
        ]);
        $this->createJob($contract, 'BDG-IR/26-05/0001', 'install', 'done_job', '2026-05-03');
        $this->createJob($contract, 'BDG-CSR/26-05/0001', 'service_first', 'done_job', '2026-05-03');
        $this->createJob($contract, 'BDG-CSR/26-08/0001', 'service', 'assign_material', null, '2026-08-03');
        $this->createJob($contract, 'BDG-CSR/26-09/0001', 'service', 'new_job', null, '2026-09-03');

        $eligibility = ContractRenewal::isEligibleForRenewal($contract->id);

        $this->assertTrue($contract->fresh()->canBeRenewedSafely());
        $this->assertTrue($eligibility['eligible']);
    }

    public function test_contract_with_ba_date_can_be_renewed_when_current_routine_service_is_active(): void
    {
        DB::table('customers')->insert([
            'id' => 1,
            'name' => 'Customer Done BA',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $contract = $this->createContract('BDG-CA/26-05/0007', [
            'customer_id' => 1,
            'start_date' => '2026-05-01',
            'end_date' => '2027-05-01',
        ]);
        $this->createJob($contract, 'BDG-IR/26-05/0007', 'install', 'done_job', '2026-05-03');
        $this->createJob($contract, 'BDG-CSR/26-05/0007', 'service_first', 'done_job', '2026-05-04');
        $this->createJob($contract, 'BDG-CSR/26-06/0007', 'service', 'assign_team', null, '2026-05-04');

        $response = app(ContractRenewalController::class)->getEligibleContracts(
            Request::create('/marketing/contract-renewals/eligible-contracts', 'GET')
        );
        $payload = $response->getData(true);

        $this->assertTrue($contract->fresh()->canBeRenewedSafely());
        $this->assertSame('success', $payload['status']);
        $this->assertContains('BDG-CA/26-05/0007', collect($payload['data'])->pluck('contract_number')->all());
    }

    public function test_dropdown_includes_eligible_contract_with_past_original_end_date_and_different_marketing(): void
    {
        DB::table('customers')->insert([
            'id' => 1,
            'name' => 'Maju sejahtera Indonesia',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $contract = $this->createContract('BDG-CA/26-05/0001', [
            'customer_id' => 1,
            'marketing_id' => 99,
            'start_date' => '2026-04-01',
            'end_date' => '2026-05-01',
        ]);
        $this->createJob($contract, 'BDG-CSR/26-05/0001', 'service', 'done_job', '2026-04-10');

        $response = app(ContractRenewalController::class)->getEligibleContracts(
            Request::create('/marketing/contract-renewals/eligible-contracts', 'GET', [
                'marketing_id' => 7,
            ])
        );

        $payload = $response->getData(true);

        $this->assertSame('success', $payload['status']);
        $this->assertContains('BDG-CA/26-05/0001', collect($payload['data'])->pluck('contract_number')->all());
    }

    private function createContract(string $contractNumber, array $overrides = []): Contract
    {
        return Contract::create(array_merge([
            'contract_number' => $contractNumber,
            'contract_status' => 'active',
            'start_date' => '2025-05-01',
            'end_date' => '2026-05-15',
        ], $overrides));
    }

    private function createJob(Contract $contract, string $jobNumber, string $type, string $status, ?string $baDate = null, ?string $scheduleDate = null): void
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
            'schedule_date' => $scheduleDate ?? $baDate,
            'ba_date' => $baDate,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
