<?php

namespace Tests\Feature;

use App\Http\Controllers\Marketing\ContractController;
use App\Models\Contract;
use App\Models\ContractRoom;
use App\Models\Finance\BillingGroup;
use App\Models\Finance\BillingGroupBuilding;
use App\Services\ContractMergeService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContractMergeWizardValueTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-06-29 10:11:37');

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->foreignId('quotation_id')->nullable();
            $table->string('contract_type')->nullable();
            $table->date('contract_date')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('contract_value', 12, 2);
            $table->decimal('net_value', 12, 2)->nullable();
            $table->foreignId('marketing_id')->nullable();
            $table->string('contract_status')->nullable();
            $table->boolean('is_contract')->default(false);
            $table->string('payment_terms')->nullable();
            $table->string('term_of_payment')->nullable();
            $table->string('contract_period_type')->nullable();
            $table->string('invoice_period_type')->nullable();
            $table->string('ppn_code')->nullable();
            $table->date('install_date')->nullable();
            $table->date('first_service_date')->nullable();
            $table->foreignId('customer_signing_1_id')->nullable();
            $table->foreignId('internal_signing_id')->nullable();
            $table->text('internal_remark')->nullable();
            $table->text('external_remark')->nullable();
            $table->text('notes')->nullable();
            $table->text('merged_from_ids')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contract_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->foreignId('billing_group_id')->nullable();
            $table->foreignId('source_contract_id')->nullable();
            $table->foreignId('source_contract_room_id')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('contract_rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('master_rental_id')->nullable();
            $table->string('rental_alias')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->integer('quantity')->default(0);
            $table->integer('qty_free')->default(0);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total_price', 12, 2)->default(0);
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
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
            $table->string('status')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contract_terminations', function (Blueprint $table) {
            $table->id();
            $table->string('termination_number')->nullable();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('new_contract_id')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->string('reason')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->foreignId('requested_by')->nullable();
            $table->boolean('auto_generated')->default(false);
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contract_merges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('new_contract_id')->nullable();
            $table->foreignId('source_contract_id')->nullable();
            $table->integer('rooms_copied')->default(0);
            $table->integer('rentals_copied')->default(0);
            $table->integer('jobs_cancelled')->default(0);
            $table->foreignId('merged_by')->nullable();
            $table->timestamp('merged_at')->nullable();
            $table->timestamps();
        });

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number')->nullable();
            $table->string('quotation_type')->nullable();
            $table->foreignId('existing_contract_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quotation_surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->nullable();
            $table->foreignId('survey_id')->nullable();
            $table->timestamps();
        });

        Schema::create('contract_surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('survey_id')->nullable();
            $table->timestamps();
        });

        Schema::create('billing_groups', function (Blueprint $table) {
            $table->id();
            $table->string('billing_group_name')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('contract_id')->nullable();
            $table->string('billing_frequency')->nullable();
            $table->date('billing_start_date')->nullable();
            $table->date('billing_end_date')->nullable();
            $table->decimal('billing_amount', 12, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('pic_name')->nullable();
            $table->string('pic_phone')->nullable();
            $table->string('pic_email')->nullable();
            $table->string('npwp')->nullable();
            $table->string('nitku')->nullable();
            $table->string('invoice_type')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('virtual_account_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('billing_group_buildings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_group_id')->nullable();
            $table->foreignId('building_id')->nullable();
            $table->decimal('billing_amount', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('customers')->insert(['id' => 1, 'name' => 'Acme', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('branches')->insert(['id' => 1, 'code' => 'SBY', 'name' => 'Surabaya', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('users')->insert(['id' => 1, 'name' => 'Marketing', 'email' => 'marketing@example.test', 'created_at' => now(), 'updated_at' => now()]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('billing_group_buildings');
        Schema::dropIfExists('billing_groups');
        Schema::dropIfExists('contract_surveys');
        Schema::dropIfExists('quotation_surveys');
        Schema::dropIfExists('quotations');
        Schema::dropIfExists('contract_merges');
        Schema::dropIfExists('contract_terminations');
        Schema::dropIfExists('job_schedules');
        Schema::dropIfExists('job_advices');
        Schema::dropIfExists('contract_rentals');
        Schema::dropIfExists('contract_rooms');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('users');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('customers');

        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_merge_wizard_creates_contract_with_required_contract_value(): void
    {
        app()->instance(ContractMergeService::class, new class extends ContractMergeService {
            public function execute(Contract $newContract, array $sourceContractIds): array
            {
                return [
                    'success' => true,
                    'message' => 'OK',
                    'stats' => [
                        'source_contracts_merged' => count($sourceContractIds),
                        'rooms_copied' => 0,
                        'rentals_copied' => 0,
                        'jobs_cancelled' => 0,
                    ],
                ];
            }
        });

        $sourceA = Contract::create([
            'contract_number' => 'SBY-CA/26-06/0001',
            'customer_id' => 1,
            'contract_status' => 'active',
            'contract_value' => 1000000,
            'net_value' => 900000,
        ]);

        $sourceB = Contract::create([
            'contract_number' => 'SBY-CA/26-06/0002',
            'customer_id' => 1,
            'contract_status' => 'active',
            'contract_value' => 2500000,
            'net_value' => 2400000,
        ]);

        $request = Request::create('/marketing/contracts/merge-wizard/save', 'POST', [
            'customer_id' => 1,
            'source_contract_ids' => [$sourceA->id, $sourceB->id],
            'branch_id' => 1,
            'contract_date' => '2026-06-29',
            'start_date' => '2026-06-29',
            'end_date' => '2027-06-29',
            'marketing_id' => 1,
        ]);

        $response = app(ContractController::class)->saveMergeWizard($request);
        $payload = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);

        $newContract = Contract::findOrFail($payload['contract_id']);
        $this->assertSame('merge', $newContract->contract_type);
        $this->assertSame(3500000.0, (float) $newContract->contract_value);
        $this->assertSame(3300000.0, (float) $newContract->net_value);
    }

    public function test_merge_candidates_exclude_renewal_source_contract_for_selected_quotation(): void
    {
        $renewalSource = Contract::create([
            'contract_number' => 'SBY-CA/26-07/0010',
            'customer_id' => 1,
            'contract_status' => 'active',
            'contract_value' => 1000000,
        ]);

        $otherContract = Contract::create([
            'contract_number' => 'SBY-CA/26-07/0011',
            'customer_id' => 1,
            'contract_status' => 'active',
            'contract_value' => 1000000,
        ]);

        DB::table('quotations')->insert([
            'id' => 56,
            'quotation_number' => 'SBY-SQ/26-07/0016',
            'quotation_type' => 'renewal',
            'existing_contract_id' => $renewalSource->id,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/marketing/contracts/merge-candidates', 'GET', [
            'customer_id' => 1,
            'quotation_id' => 56,
        ]);

        $response = app(ContractController::class)->getMergeCandidates($request);
        $payload = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertSame([$otherContract->id], collect($payload['data'])->pluck('id')->all());
        $this->assertNotContains($renewalSource->id, collect($payload['data'])->pluck('id')->all());
    }

    public function test_merge_termination_uses_valid_contract_status_and_term_renew_audit_status(): void
    {
        $source = Contract::create([
            'contract_number' => 'SBY-CA/26-06/0001',
            'customer_id' => 1,
            'contract_status' => 'active',
            'contract_value' => 1000000,
        ]);

        $newContract = Contract::create([
            'contract_number' => 'SBY-CA/26-06/0002',
            'customer_id' => 1,
            'contract_type' => 'merge',
            'contract_status' => 'active',
            'contract_value' => 1000000,
        ]);

        app(ContractMergeService::class)->terminateSourceContracts([$source], $newContract);

        $this->assertSame('terminated', $source->fresh()->contract_status);
        $this->assertDatabaseHas('contract_terminations', [
            'contract_id' => $source->id,
            'new_contract_id' => $newContract->id,
            'status' => 'term-renew',
            'reason' => 'Contract Merge',
        ]);
    }

    public function test_merge_copies_metadata_billing_groups_and_room_billing_mapping(): void
    {
        $source = Contract::create([
            'contract_number' => 'SBY-CA/26-06/0001',
            'customer_id' => 1,
            'contract_status' => 'active',
            'contract_value' => 1000000,
            'payment_terms' => 'cash',
            'term_of_payment' => '1 bulan 1x',
            'contract_period_type' => 'contract',
            'invoice_period_type' => 'contract_date',
            'ppn_code' => '01',
            'install_date' => '2026-06-25',
            'first_service_date' => '2026-06-25',
            'customer_signing_1_id' => 10,
            'internal_signing_id' => 112,
            'internal_remark' => 'test internal',
            'external_remark' => 'test external',
        ]);

        $newContract = Contract::create([
            'contract_number' => 'SBY-CA/26-06/0002',
            'customer_id' => 1,
            'contract_type' => 'merge',
            'contract_status' => 'active',
            'contract_value' => 1000000,
        ]);

        $billingGroup = BillingGroup::create([
            'billing_group_name' => 'Billing Group 1',
            'customer_id' => 1,
            'contract_id' => $source->id,
            'billing_frequency' => 'monthly',
            'billing_amount' => 1000000,
            'pic_name' => 'Lily',
            'pic_email' => 'ceo@example.test',
            'npwp' => '123',
            'nitku' => '000000',
            'invoice_type' => 'both',
            'virtual_account_number' => 'VA-001',
            'bank_name' => 'Bank Central Asia',
        ]);

        BillingGroupBuilding::create([
            'billing_group_id' => $billingGroup->id,
            'building_id' => 501,
            'billing_amount' => 1000000,
            'notes' => 'Main building',
        ]);

        ContractRoom::create([
            'contract_id' => $source->id,
            'room_id' => 701,
            'billing_group_id' => $billingGroup->id,
        ]);

        $service = app(ContractMergeService::class);
        $service->syncMergeContractMetadata($newContract, [$source]);
        $billingGroupMap = $service->copyBillingGroups($newContract, [$source->load('billingGroups.billingGroupBuildings')]);
        $service->copyRoomsAndRentals($newContract, [$source->load('contractRooms', 'contractRentals')], $billingGroupMap);

        $freshContract = $newContract->fresh();
        $this->assertSame('1 bulan 1x', $freshContract->term_of_payment);
        $this->assertSame('01', $freshContract->ppn_code);
        $this->assertSame('test internal', $freshContract->internal_remark);

        $newBillingGroup = BillingGroup::where('contract_id', $newContract->id)->first();
        $this->assertNotNull($newBillingGroup);
        $this->assertSame('Billing Group 1', $newBillingGroup->billing_group_name);
        $this->assertSame('Lily', $newBillingGroup->pic_name);
        $this->assertSame(1000000.0, (float) $newBillingGroup->billing_amount);

        $this->assertDatabaseHas('billing_group_buildings', [
            'billing_group_id' => $newBillingGroup->id,
            'building_id' => 501,
            'notes' => 'Main building',
        ]);

        $newRoom = ContractRoom::where('contract_id', $newContract->id)->first();
        $this->assertNotNull($newRoom);
        $this->assertSame($newBillingGroup->id, $newRoom->billing_group_id);
    }

    public function test_merge_contract_exposes_clickable_quotations_and_virtual_account_fallback(): void
    {
        DB::table('quotations')->insert([
            ['id' => 11, 'quotation_number' => 'SBY-SQ/26-06/0020', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 12, 'quotation_number' => 'SBY-SQ/26-06/0021', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $sourceA = Contract::create([
            'contract_number' => 'SBY-CA/26-06/0017',
            'customer_id' => 1,
            'quotation_id' => 11,
            'contract_status' => 'terminated',
            'contract_value' => 1000000,
        ]);

        $sourceB = Contract::create([
            'contract_number' => 'SBY-CA/26-06/0018',
            'customer_id' => 1,
            'quotation_id' => 12,
            'contract_status' => 'terminated',
            'contract_value' => 2300000,
        ]);

        $mergeContract = Contract::create([
            'contract_number' => 'SBY-CA/26-06/0022',
            'customer_id' => 1,
            'contract_type' => 'merge',
            'contract_status' => 'active',
            'contract_value' => 3300000,
        ]);

        DB::table('contract_merges')->insert([
            [
                'new_contract_id' => $mergeContract->id,
                'source_contract_id' => $sourceA->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'new_contract_id' => $mergeContract->id,
                'source_contract_id' => $sourceB->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        BillingGroup::create([
            'billing_group_name' => 'Billing Group 1',
            'customer_id' => 1,
            'contract_id' => $mergeContract->id,
            'virtual_account_number' => '88997000001',
        ]);

        $freshContract = $mergeContract->fresh();

        $this->assertSame(
            ['SBY-SQ/26-06/0020', 'SBY-SQ/26-06/0021'],
            $freshContract->display_quotations->pluck('quotation_number')->all()
        );
        $this->assertSame('SBY-SQ/26-06/0020, SBY-SQ/26-06/0021', $freshContract->display_quotation_numbers);
        $this->assertSame('88997000001', $freshContract->display_virtual_accounts);
    }
}
