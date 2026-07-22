<?php

namespace Tests\Feature;

use App\Models\ContractSwitching;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContractSwitchingExecutionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('address')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('company_virtual_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable();
            $table->string('account_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('payment_terms')->nullable();
            $table->string('term_of_payment')->nullable();
            $table->string('status')->nullable();
            $table->string('contract_status')->nullable();
            $table->string('virtual_account')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->foreignId('posted_by')->nullable();
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
            $table->foreignId('room_id')->nullable();
            $table->decimal('quantity', 10, 2)->nullable();
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->decimal('total_price', 15, 2)->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('billing_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->string('billing_group_name')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contract_surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('survey_id')->nullable();
            $table->timestamp('added_at')->nullable();
            $table->foreignId('added_by')->nullable();
            $table->integer('sort_order')->nullable();
            $table->timestamps();
        });

        Schema::create('job_advices', function (Blueprint $table) {
            $table->id();
            $table->string('job_advice_number')->nullable()->unique();
            $table->string('type')->nullable();
            $table->string('company_name')->nullable();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->string('status')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_advice_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_advice_id')->nullable();
            $table->foreignId('contract_room_id')->nullable();
            $table->foreignId('contract_rental_id')->nullable();
            $table->string('room_name')->nullable();
            $table->string('rental_name')->nullable();
            $table->integer('quantity')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('install_job_schedule_id')->nullable();
            $table->foreignId('service_job_schedule_id')->nullable();
            $table->foreignId('remove_job_schedule_id')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('job_advice_id')->nullable();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->string('company_name')->nullable();
            $table->string('contract_number')->nullable();
            $table->date('schedule_date')->nullable();
            $table->date('ba_date')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedule_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('job_advice_room_id')->nullable();
            $table->string('room_name')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedule_room_rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_room_id')->nullable();
            $table->foreignId('job_advice_room_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('unit_on_walls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->string('company_name')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->nullable();
            $table->foreignId('contract_id')->nullable();
            $table->string('contract_number')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->string('invoice_status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contract_switchings', function (Blueprint $table) {
            $table->id();
            $table->string('switching_number')->nullable();
            $table->foreignId('old_contract_id')->nullable();
            $table->foreignId('old_customer_id')->nullable();
            $table->foreignId('new_customer_id')->nullable();
            $table->foreignId('new_contract_id')->nullable();
            $table->string('switching_reason')->nullable();
            $table->text('switching_description')->nullable();
            $table->text('switching_notes')->nullable();
            $table->boolean('continue_period')->default(true);
            $table->boolean('continue_top')->default(true);
            $table->boolean('reset_dates')->default(false);
            $table->integer('continue_from_period')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->foreignId('executed_by')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('customers')->insert([
            ['id' => 1, 'name' => 'Customer Lama', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Customer Baru', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        foreach ([
            'contract_switchings',
            'invoices',
            'unit_on_walls',
            'job_schedule_room_rentals',
            'job_schedule_rooms',
            'job_schedules',
            'job_advice_rooms',
            'job_advices',
            'contract_surveys',
            'billing_groups',
            'contract_rentals',
            'contract_rooms',
            'contracts',
            'customers',
            'company_virtual_accounts',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_continue_remaining_moves_only_future_jobs_and_preserves_invoices(): void
    {
        Carbon::setTestNow('2026-07-01 10:00:00');
        $this->seedSwitchingScenario();

        $switching = ContractSwitching::create([
            'switching_number' => 'CSW-TEST-001',
            'old_contract_id' => 1,
            'old_customer_id' => 1,
            'new_customer_id' => 2,
            'switching_reason' => 'Business Transfer',
            'continue_period' => true,
            'continue_top' => true,
            'reset_dates' => false,
            'status' => ContractSwitching::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => 1,
            'created_by' => 1,
        ]);

        $newContract = $switching->execute(1);
        $newJobAdviceId = DB::table('job_schedules')->where('id', 2)->value('job_advice_id');

        $this->assertSame('2026-07-01', $newContract->start_date->toDateString());
        $this->assertSame('2026-12-31', $newContract->end_date->toDateString());
        $this->assertSame(1, DB::table('job_schedules')->where('id', 1)->value('job_advice_id'));
        $this->assertNotSame(1, $newJobAdviceId);
        $this->assertDatabaseHas('job_advices', [
            'id' => $newJobAdviceId,
            'contract_id' => $newContract->id,
            'customer_id' => 2,
        ]);
        $this->assertNotSame('JA-OLD-001', DB::table('job_advices')->where('id', $newJobAdviceId)->value('job_advice_number'));
        $this->assertDatabaseHas('job_schedules', [
            'id' => 2,
            'contract_id' => $newContract->id,
            'customer_id' => 2,
            'contract_number' => $newContract->contract_number,
            'company_name' => 'Customer Baru',
        ]);
        $this->assertDatabaseHas('unit_on_walls', [
            'id' => 1,
            'customer_id' => 2,
            'company_name' => 'Customer Baru',
        ]);
        $this->assertDatabaseHas('invoices', [
            'id' => 1,
            'contract_id' => 1,
            'contract_number' => 'OLD-001',
            'customer_id' => 1,
        ]);
        $this->assertDatabaseHas('contracts', [
            'id' => 1,
            'status' => 'terminated',
            'contract_status' => 'terminated',
        ]);
    }

    public function test_reset_all_starts_full_new_duration_from_effective_date(): void
    {
        Carbon::setTestNow('2026-07-01 10:00:00');
        $this->seedSwitchingScenario();

        $switching = ContractSwitching::create([
            'switching_number' => 'CSW-TEST-002',
            'old_contract_id' => 1,
            'old_customer_id' => 1,
            'new_customer_id' => 2,
            'switching_reason' => 'Business Transfer',
            'continue_period' => false,
            'continue_top' => false,
            'reset_dates' => true,
            'status' => ContractSwitching::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => 1,
            'created_by' => 1,
        ]);

        $newContract = $switching->execute(1);

        $this->assertSame('2026-07-01', $newContract->start_date->toDateString());
        $this->assertSame('2027-06-30', $newContract->end_date->toDateString());
        $this->assertSame('3 Bulan', $newContract->payment_terms);
        $this->assertSame('1 Bulan', $newContract->term_of_payment);
    }

    private function seedSwitchingScenario(): void
    {
        DB::table('contracts')->insert([
            'id' => 1,
            'contract_number' => 'OLD-001',
            'customer_id' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'payment_terms' => '3 Bulan',
            'term_of_payment' => '3 Bulan',
            'status' => 'active',
            'contract_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('contract_rooms')->insert([
            'id' => 1,
            'contract_id' => 1,
            'room_id' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('contract_rentals')->insert([
            'id' => 1,
            'contract_id' => 1,
            'master_rental_id' => 10,
            'room_id' => 100,
            'quantity' => 1,
            'unit_price' => 100000,
            'total_price' => 100000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_advices')->insert([
            'id' => 1,
            'job_advice_number' => 'JA-OLD-001',
            'type' => 'service',
            'company_name' => 'Customer Lama',
            'contract_id' => 1,
            'customer_id' => 1,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_advice_rooms')->insert([
            'id' => 1,
            'job_advice_id' => 1,
            'contract_room_id' => 1,
            'contract_rental_id' => 1,
            'room_name' => 'Room A',
            'rental_name' => 'Rental A',
            'quantity' => 1,
            'status' => 'scheduled',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_schedules')->insert([
            [
                'id' => 1,
                'job_number' => 'JOB-OLD-DONE',
                'type' => 'service',
                'status' => 'done_job',
                'job_advice_id' => 1,
                'contract_id' => 1,
                'customer_id' => 1,
                'company_name' => 'Customer Lama',
                'contract_number' => 'OLD-001',
                'schedule_date' => '2026-06-01',
                'ba_date' => '2026-06-01',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'job_number' => 'JOB-OLD-FUTURE',
                'type' => 'service',
                'status' => 'scheduled',
                'job_advice_id' => 1,
                'contract_id' => 1,
                'customer_id' => 1,
                'company_name' => 'Customer Lama',
                'contract_number' => 'OLD-001',
                'schedule_date' => '2026-07-15',
                'ba_date' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('job_schedule_rooms')->insert([
            'id' => 1,
            'job_schedule_id' => 2,
            'job_advice_room_id' => 1,
            'room_name' => 'Room A',
            'room_id' => 100,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_schedule_room_rentals')->insert([
            'id' => 1,
            'job_schedule_room_id' => 1,
            'job_advice_room_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('unit_on_walls')->insert([
            'id' => 1,
            'customer_id' => 1,
            'room_id' => 100,
            'company_name' => 'Customer Lama',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('invoices')->insert([
            'id' => 1,
            'invoice_number' => 'INV-OLD-001',
            'contract_id' => 1,
            'contract_number' => 'OLD-001',
            'customer_id' => 1,
            'invoice_status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
