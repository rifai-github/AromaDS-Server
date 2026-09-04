<?php

namespace Tests\Feature;

use App\Http\Controllers\Marketing\ContractRenewalController;
use App\Models\Contract;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Branch scoping and searching of the SQ Renewal contract picker.
 *
 * The picker used to load every contract in the database and filter branch through
 * the contract's quotation, which dropped every imported contract (they have no
 * quotation) and timed the endpoint out. These lock in the replacement.
 */
class EligibleContractsBranchScopeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-04 10:00:00');

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->timestamps();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->nullable();
            $table->string('status')->nullable();
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
            $table->string('quotation_number')->nullable();
            $table->string('quotation_type')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('branch_id')->nullable();
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
        Schema::dropIfExists('job_schedules');
        Schema::dropIfExists('job_advices');
        Schema::dropIfExists('contract_renewals');
        Schema::dropIfExists('quotations');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('users');
        Schema::dropIfExists('customers');

        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_contract_is_scoped_by_its_quotation_branch(): void
    {
        $surabaya = $this->createContractWithQuotation('SBY-CA/26-08/0001', branchId: 2);
        $jakarta = $this->createContractWithQuotation('JKT-CA/26-08/0001', branchId: 7);

        $numbers = $this->contractNumbersFor(['branch_id' => 2]);

        $this->assertContains($surabaya->contract_number, $numbers);
        $this->assertNotContains($jakarta->contract_number, $numbers);
    }

    public function test_imported_contract_without_quotation_is_scoped_by_its_marketing_branch(): void
    {
        // The regression this whole change exists for: 2.325 live contracts carry no
        // quotation at all, so scoping through the quotation hid every one of them.
        $imported = $this->createContract('SBY-AG/24-11/0008', marketingBranchId: 2);

        $numbers = $this->contractNumbersFor(['branch_id' => 2]);

        $this->assertContains($imported->contract_number, $numbers);
    }

    public function test_imported_contract_of_another_branch_stays_out_of_the_unsearched_list(): void
    {
        $this->createContract('MDN-AG/24-11/0001', marketingBranchId: 14);

        $numbers = $this->contractNumbersFor(['branch_id' => 2]);

        $this->assertNotContains('MDN-AG/24-11/0001', $numbers);
    }

    public function test_search_finds_a_contract_outside_the_selected_branch(): void
    {
        // Branch attribution on imported contracts is unreliable, so an explicit search
        // must never be silently limited to the branch picked in the form.
        $this->createContract('MDN-AG/24-11/0001', marketingBranchId: 14);

        $numbers = $this->contractNumbersFor(['branch_id' => 2, 'q' => 'MDN-AG/24-11']);

        $this->assertContains('MDN-AG/24-11/0001', $numbers);
    }

    public function test_search_ranks_the_selected_branch_first(): void
    {
        $this->createContract('CA/26-08/0001', marketingBranchId: 14, endDate: '2027-01-01');
        $this->createContract('CA/26-08/0002', marketingBranchId: 2, endDate: '2027-06-01');

        $numbers = $this->contractNumbersFor(['branch_id' => 2, 'q' => 'CA/26-08']);

        // Own branch first even though the other contract expires sooner.
        $this->assertSame(['CA/26-08/0002', 'CA/26-08/0001'], $numbers);
    }

    public function test_search_matches_customer_name(): void
    {
        DB::table('customers')->insert([
            'id' => 1,
            'name' => 'PT. Wijaya Karya Realty',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->createContract('SBY-AG/24-11/0009', marketingBranchId: 2, customerId: 1);

        $numbers = $this->contractNumbersFor(['branch_id' => 2, 'q' => 'wijaya']);

        $this->assertContains('SBY-AG/24-11/0009', $numbers);
    }

    public function test_results_are_limited_to_one_page(): void
    {
        for ($i = 1; $i <= 30; $i++) {
            $this->createContract(sprintf('SBY-AG/24-11/%04d', $i), marketingBranchId: 2);
        }

        $this->assertCount(25, $this->contractNumbersFor(['branch_id' => 2]));
        $this->assertCount(5, $this->contractNumbersFor(['branch_id' => 2, 'limit' => 5]));
    }

    public function test_selected_contract_is_returned_even_when_it_is_no_longer_eligible(): void
    {
        $contract = $this->createContract('SBY-AG/24-11/0010', marketingBranchId: 2);

        DB::table('quotations')->insert([
            'quotation_number' => 'SBY-SQ/26-09/0001',
            'quotation_type' => 'renewal',
            'status' => 'approved',
            'existing_contract_id' => $contract->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertNotContains('SBY-AG/24-11/0010', $this->contractNumbersFor(['branch_id' => 2]));

        $payload = $this->eligibleContracts(['branch_id' => 2, 'include_id' => $contract->id]);
        $current = collect($payload['data'])->firstWhere('contract_number', 'SBY-AG/24-11/0010');

        $this->assertNotNull($current);
        $this->assertTrue($current['is_current']);
    }

    private function eligibleContracts(array $params): array
    {
        $response = app(ContractRenewalController::class)->getEligibleContracts(
            Request::create('/marketing/contract-renewals/eligible-contracts', 'GET', $params)
        );

        $payload = $response->getData(true);
        $this->assertSame('success', $payload['status'], $payload['message'] ?? '');

        return $payload;
    }

    private function contractNumbersFor(array $params): array
    {
        return collect($this->eligibleContracts($params)['data'])->pluck('contract_number')->all();
    }

    private function createContract(
        string $contractNumber,
        ?int $marketingBranchId = null,
        ?int $customerId = null,
        string $endDate = '2027-05-15'
    ): Contract {
        $marketingId = null;

        if ($marketingBranchId !== null) {
            $marketingId = DB::table('users')->insertGetId([
                'name' => 'Marketing '.$contractNumber,
                'branch_id' => $marketingBranchId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return Contract::create([
            'contract_number' => $contractNumber,
            'status' => 'active',
            'contract_status' => 'active',
            'customer_id' => $customerId,
            'marketing_id' => $marketingId,
            'start_date' => '2026-05-01',
            'end_date' => $endDate,
        ]);
    }

    private function createContractWithQuotation(string $contractNumber, int $branchId): Contract
    {
        $quotationId = DB::table('quotations')->insertGetId([
            'quotation_number' => str_replace('CA', 'SQ', $contractNumber),
            'quotation_type' => 'new',
            'status' => 'approved',
            'branch_id' => $branchId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $contract = $this->createContract($contractNumber);
        $contract->update(['quotation_id' => $quotationId]);

        return $contract->fresh();
    }
}
