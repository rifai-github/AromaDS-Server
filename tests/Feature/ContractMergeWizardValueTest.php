<?php

namespace Tests\Feature;

use App\Http\Controllers\Marketing\ContractController;
use App\Models\Contract;
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
            $table->string('contract_type')->nullable();
            $table->date('contract_date')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('contract_value', 12, 2);
            $table->decimal('net_value', 12, 2)->nullable();
            $table->foreignId('marketing_id')->nullable();
            $table->string('contract_status')->nullable();
            $table->boolean('is_contract')->default(false);
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

        DB::table('customers')->insert(['id' => 1, 'name' => 'Acme', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('branches')->insert(['id' => 1, 'code' => 'SBY', 'name' => 'Surabaya', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('users')->insert(['id' => 1, 'name' => 'Marketing', 'email' => 'marketing@example.test', 'created_at' => now(), 'updated_at' => now()]);
    }

    protected function tearDown(): void
    {
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
}
