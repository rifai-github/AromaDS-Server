<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Customer;
use App\Models\Finance\BillingGroup;
use App\Models\Finance\Invoice;
use App\Models\JobSchedule;
use App\Services\DocumentNumberService;
use App\Services\Finance\BillingGroupService;
use App\Services\Finance\InvoiceGenerationService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BillingGroupInvoiceRegenerationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->foreignId('update_by_1')->nullable();
            $table->timestamp('update_at_1')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->text('address')->nullable();
            $table->string('email')->nullable();
            $table->boolean('tax_obligation')->default(false);
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->foreignId('update_by_1')->nullable();
            $table->timestamp('update_at_1')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->string('payment_terms')->nullable();
            $table->string('ppn_code')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->foreignId('update_by_1')->nullable();
            $table->timestamp('update_at_1')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('billing_groups', function (Blueprint $table) {
            $table->id();
            $table->string('billing_group_name')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('contract_id')->nullable();
            $table->string('billing_frequency')->nullable();
            $table->date('billing_start_date')->nullable();
            $table->date('billing_end_date')->nullable();
            $table->decimal('billing_amount', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('pic_name')->nullable();
            $table->string('pic_email')->nullable();
            $table->text('pic_address')->nullable();
            $table->string('invoice_type')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->string('building_name')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->nullable();
            $table->string('room_name')->nullable();
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

        Schema::create('master_rentals', function (Blueprint $table) {
            $table->id();
            $table->string('rental_name')->nullable();
            $table->string('rental_type')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contract_rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable();
            $table->foreignId('master_rental_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total_price', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('job_advices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_advice_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_advice_id')->nullable();
            $table->foreignId('contract_room_id')->nullable();
            $table->foreignId('install_job_schedule_id')->nullable();
            $table->foreignId('service_job_schedule_id')->nullable();
            $table->foreignId('remove_job_schedule_id')->nullable();
            $table->string('status')->nullable();
            $table->boolean('is_trial')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('job_advice_id')->nullable();
            $table->date('schedule_date')->nullable();
            $table->date('ba_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedule_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('job_advice_room_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->string('room_name')->nullable();
            $table->string('status')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->nullable();
            $table->foreignId('contract_id')->nullable();
            $table->string('contract_number')->nullable();
            $table->string('po_number')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('billing_group_id')->nullable();
            $table->text('billing_address')->nullable();
            $table->string('pic_finance')->nullable();
            $table->string('email')->nullable();
            $table->date('invoice_date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->decimal('total_paid', 12, 2)->default(0);
            $table->decimal('outstanding', 12, 2)->default(0);
            $table->string('tax_code')->nullable();
            $table->boolean('tax_obligation')->default(false);
            $table->string('invoice_status')->nullable();
            $table->string('status')->nullable();
            $table->string('kirim')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoice_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->nullable();
            $table->string('description')->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total_price', 12, 2)->default(0);
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('invoice_rental_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->nullable();
            $table->foreignId('master_rental_id')->nullable();
            $table->string('job_no')->nullable();
            $table->string('building_name')->nullable();
            $table->string('room_name')->nullable();
            $table->string('rental_name')->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total_price', 12, 2)->default(0);
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        foreach ([
            'invoice_rental_details',
            'invoice_details',
            'invoices',
            'job_schedule_rooms',
            'job_schedules',
            'job_advice_rooms',
            'job_advices',
            'contract_rentals',
            'master_rentals',
            'contract_rooms',
            'master_rooms',
            'buildings',
            'billing_groups',
            'contracts',
            'customers',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_cancelled_billing_group_invoice_can_regenerate_from_previous_snapshot(): void
    {
        DB::table('users')->insert([
            'name' => 'Admin',
            'email' => 'admin@aroma.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $customer = Customer::create(['name' => 'Maju Sejahtera Indonesia']);
        $contract = Contract::create([
            'contract_number' => 'BDG-CA/26-05/0001',
            'customer_id' => $customer->id,
            'payment_terms' => 30,
        ]);
        $billingGroup = BillingGroup::create([
            'billing_group_name' => 'Main Billing',
            'customer_id' => $customer->id,
            'contract_id' => $contract->id,
            'billing_frequency' => 'monthly',
            'billing_start_date' => '2026-05-01',
            'billing_end_date' => '2026-05-31',
            'billing_amount' => 120000,
            'is_active' => true,
        ]);

        $jobAdviceId = \DB::table('job_advices')->insertGetId([
            'contract_id' => $contract->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        JobSchedule::create([
            'job_number' => 'BDG-CSR/26-05/0001',
            'type' => 'service',
            'status' => 'done_job',
            'job_advice_id' => $jobAdviceId,
            'schedule_date' => '2026-05-04',
            'ba_date' => '2026-05-04',
        ]);

        $cancelledInvoice = Invoice::create([
            'invoice_number' => 'BDG-INV/26-05/0001',
            'contract_id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'customer_id' => $customer->id,
            'billing_group_id' => $billingGroup->id,
            'invoice_date' => '2026-05-04',
            'due_date' => '2026-06-03',
            'invoice_status' => Invoice::STATUS_CANCELLED,
            'status' => Invoice::STATUS_CANCELLED,
        ]);
        $cancelledInvoice->invoiceRentalDetails()->create([
            'job_no' => 'BDG-CSR/26-05/0001',
            'building_name' => 'Spektrum Biologi I',
            'room_name' => 'Office Room',
            'rental_name' => 'C100 100 ml',
            'quantity' => 1,
            'unit_price' => 120000,
            'total_price' => 120000,
        ]);

        $service = new BillingGroupService(new class extends DocumentNumberService {
            public function generate(
                string $documentType,
                ?string $branchCode = null,
                ?int $buildingId = null,
                ?int $contractId = null,
                ?int $quotationId = null,
                ?int $surveyId = null,
                ?int $warehouseId = null,
                ?int $branchId = null,
                \DateTimeInterface|string|null $documentDate = null
            ): string {
                return 'BDG-INV/26-05/0002';
            }
        });

        $result = $service->autoGenerateInvoiceWhenJobsCompleted(
            $billingGroup->id,
            Carbon::parse('2026-05-04'),
            $cancelledInvoice
        );

        $this->assertTrue($result['success'], $result['message'] ?? 'No message');
        $this->assertSame('BDG-INV/26-05/0002', $result['invoice']->invoice_number);
        $this->assertDatabaseHas('invoices', [
            'invoice_number' => 'BDG-INV/26-05/0002',
            'invoice_status' => Invoice::STATUS_DRAFT,
            'billing_group_id' => $billingGroup->id,
        ]);
        $this->assertDatabaseHas('invoice_rental_details', [
            'invoice_id' => $result['invoice']->id,
            'job_no' => 'BDG-CSR/26-05/0001',
            'rental_name' => 'C100 100 ml',
            'total_price' => 120000,
        ]);
    }

    public function test_auto_invoice_includes_unit_only_room_even_without_own_service_job(): void
    {
        DB::table('users')->insert([
            'name' => 'Admin',
            'email' => 'admin@aroma.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $customer = Customer::create(['name' => 'Test110526']);
        $contract = Contract::create([
            'contract_number' => 'BDG-CA/26-05/0005',
            'customer_id' => $customer->id,
            'payment_terms' => 30,
        ]);
        $billingGroup = BillingGroup::create([
            'billing_group_name' => 'Main Billing',
            'customer_id' => $customer->id,
            'contract_id' => $contract->id,
            'billing_frequency' => 'monthly',
            'billing_start_date' => '2026-05-01',
            'billing_end_date' => '2026-05-31',
            'billing_amount' => 1700000,
            'is_active' => true,
        ]);

        DB::table('buildings')->insert([
            'id' => 10,
            'building_name' => 'Gedung Cabang B 110526',
            'name' => 'Gedung Cabang B 110526',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('master_rooms')->insert([
            ['id' => 101, 'building_id' => 10, 'room_name' => 'Ruang Melati', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 102, 'building_id' => 10, 'room_name' => 'Ruang Anggrek', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('contract_rooms')->insert([
            ['id' => 201, 'contract_id' => $contract->id, 'room_id' => 101, 'billing_group_id' => $billingGroup->id, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 202, 'contract_id' => $contract->id, 'room_id' => 102, 'billing_group_id' => $billingGroup->id, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('master_rentals')->insert([
            ['id' => 301, 'rental_name' => 'Rental-5', 'rental_type' => 'rental_only', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 302, 'rental_name' => 'Unit Only', 'rental_type' => 'unit_only', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('contract_rentals')->insert([
            [
                'contract_id' => $contract->id,
                'master_rental_id' => 301,
                'room_id' => 101,
                'quantity' => 1,
                'unit_price' => 1200000,
                'total_price' => 1200000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'contract_id' => $contract->id,
                'master_rental_id' => 302,
                'room_id' => 102,
                'quantity' => 1,
                'unit_price' => 500000,
                'total_price' => 500000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $jobAdviceId = DB::table('job_advices')->insertGetId([
            'contract_id' => $contract->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $jobSchedule = JobSchedule::create([
            'job_number' => 'BDG-CSR/26-05/0010',
            'type' => 'service',
            'status' => 'done_job',
            'job_advice_id' => $jobAdviceId,
            'schedule_date' => '2026-05-28',
            'ba_date' => '2026-05-28',
        ]);
        DB::table('job_advice_rooms')->insert([
            'job_advice_id' => $jobAdviceId,
            'contract_room_id' => 201,
            'service_job_schedule_id' => $jobSchedule->id,
            'is_trial' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = new BillingGroupService(new class extends DocumentNumberService {
            public function generate(
                string $documentType,
                ?string $branchCode = null,
                ?int $buildingId = null,
                ?int $contractId = null,
                ?int $quotationId = null,
                ?int $surveyId = null,
                ?int $warehouseId = null,
                ?int $branchId = null,
                \DateTimeInterface|string|null $documentDate = null
            ): string {
                return 'BDG-INV/26-05/0007';
            }
        });

        $result = $service->autoGenerateInvoiceWhenJobsCompleted(
            $billingGroup->id,
            Carbon::parse('2026-05-28')
        );

        $this->assertTrue($result['success'], $result['message'] ?? 'No message');
        $this->assertDatabaseHas('invoice_rental_details', [
            'invoice_id' => $result['invoice']->id,
            'room_name' => 'Ruang Melati',
            'rental_name' => 'Rental-5',
            'total_price' => 1200000,
        ]);
        $this->assertDatabaseHas('invoice_rental_details', [
            'invoice_id' => $result['invoice']->id,
            'room_name' => 'Ruang Anggrek',
            'rental_name' => 'Unit Only',
            'total_price' => 500000,
        ]);
        $this->assertDatabaseHas('invoices', [
            'id' => $result['invoice']->id,
            'subtotal' => 1700000,
            'grand_total' => 1700000,
        ]);
    }

    public function test_auto_invoice_excludes_suspended_room_but_keeps_other_billable_rooms(): void
    {
        DB::table('users')->insert([
            'name' => 'Admin',
            'email' => 'admin@aroma.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $customer = Customer::create(['name' => 'Test110526']);
        $contract = Contract::create([
            'contract_number' => 'BDG-CA/26-05/0005',
            'customer_id' => $customer->id,
            'payment_terms' => 30,
        ]);
        $billingGroup = BillingGroup::create([
            'billing_group_name' => 'Main Billing',
            'customer_id' => $customer->id,
            'contract_id' => $contract->id,
            'billing_frequency' => 'monthly',
            'billing_start_date' => '2026-06-01',
            'billing_end_date' => '2026-06-30',
            'billing_amount' => 2500000,
            'is_active' => true,
        ]);

        DB::table('buildings')->insert([
            'id' => 20,
            'building_name' => 'Gedung Cabang B 110526',
            'name' => 'Gedung Cabang B 110526',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('master_rooms')->insert([
            ['id' => 201, 'building_id' => 20, 'room_name' => 'Ruang Melati', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 202, 'building_id' => 20, 'room_name' => 'Ruang Anggrek', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('contract_rooms')->insert([
            ['id' => 301, 'contract_id' => $contract->id, 'room_id' => 201, 'billing_group_id' => $billingGroup->id, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 302, 'contract_id' => $contract->id, 'room_id' => 202, 'billing_group_id' => $billingGroup->id, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('master_rentals')->insert([
            ['id' => 401, 'rental_name' => 'ADS XL Complete Package', 'rental_type' => 'rental_only', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 402, 'rental_name' => 'ADS 301 Complete Package', 'rental_type' => 'unit_refill', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('contract_rentals')->insert([
            [
                'contract_id' => $contract->id,
                'master_rental_id' => 401,
                'room_id' => 201,
                'quantity' => 1,
                'unit_price' => 1500000,
                'total_price' => 1500000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'contract_id' => $contract->id,
                'master_rental_id' => 402,
                'room_id' => 202,
                'quantity' => 1,
                'unit_price' => 1000000,
                'total_price' => 1000000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $jobAdviceId = DB::table('job_advices')->insertGetId([
            'contract_id' => $contract->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $jobSchedule = JobSchedule::create([
            'job_number' => 'BDG-CSR/26-06/0005',
            'type' => 'service',
            'status' => 'done_job',
            'job_advice_id' => $jobAdviceId,
            'schedule_date' => '2026-06-29',
            'ba_date' => '2026-06-29',
        ]);
        DB::table('job_advice_rooms')->insert([
            [
                'id' => 501,
                'job_advice_id' => $jobAdviceId,
                'contract_room_id' => 301,
                'service_job_schedule_id' => $jobSchedule->id,
                'is_trial' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 502,
                'job_advice_id' => $jobAdviceId,
                'contract_room_id' => 302,
                'service_job_schedule_id' => $jobSchedule->id,
                'is_trial' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        DB::table('job_schedule_rooms')->insert([
            [
                'job_schedule_id' => $jobSchedule->id,
                'job_advice_room_id' => 501,
                'room_id' => 201,
                'room_name' => 'Ruang Melati',
                'status' => 'completed',
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'job_schedule_id' => $jobSchedule->id,
                'job_advice_room_id' => 502,
                'room_id' => 202,
                'room_name' => 'Ruang Anggrek',
                'status' => 'pending',
                'notes' => '[SUSPEND] Room suspended by Admin',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $service = new BillingGroupService(new class extends DocumentNumberService {
            public function generate(
                string $documentType,
                ?string $branchCode = null,
                ?int $buildingId = null,
                ?int $contractId = null,
                ?int $quotationId = null,
                ?int $surveyId = null,
                ?int $warehouseId = null,
                ?int $branchId = null,
                \DateTimeInterface|string|null $documentDate = null
            ): string {
                return 'BDG-INV/26-06/0008';
            }
        });

        $result = $service->autoGenerateInvoiceWhenJobsCompleted(
            $billingGroup->id,
            Carbon::parse('2026-06-29')
        );

        $this->assertTrue($result['success'], $result['message'] ?? 'No message');
        $this->assertDatabaseHas('invoice_rental_details', [
            'invoice_id' => $result['invoice']->id,
            'room_name' => 'Ruang Melati',
            'rental_name' => 'ADS XL Complete Package',
            'total_price' => 1500000,
        ]);
        $this->assertDatabaseMissing('invoice_rental_details', [
            'invoice_id' => $result['invoice']->id,
            'room_name' => 'Ruang Anggrek',
        ]);
        $this->assertDatabaseHas('invoices', [
            'id' => $result['invoice']->id,
            'subtotal' => 1500000,
            'grand_total' => 1500000,
        ]);
    }

    public function test_rental_period_invoice_excludes_room_suspended_inside_completed_job(): void
    {
        $customer = Customer::create(['name' => 'Test110526']);
        $contract = Contract::create([
            'contract_number' => 'BDG-CA/26-05/0005',
            'customer_id' => $customer->id,
            'payment_terms' => 30,
        ]);

        DB::table('buildings')->insert([
            'id' => 30,
            'building_name' => 'Gedung Cabang B 110526',
            'name' => 'Gedung Cabang B 110526',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('master_rooms')->insert([
            ['id' => 301, 'building_id' => 30, 'room_name' => 'Ruang Melati', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 302, 'building_id' => 30, 'room_name' => 'Ruang Anggrek', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('contract_rooms')->insert([
            ['id' => 601, 'contract_id' => $contract->id, 'room_id' => 301, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 602, 'contract_id' => $contract->id, 'room_id' => 302, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('master_rentals')->insert([
            ['id' => 701, 'rental_name' => 'ADS XL Complete Package', 'rental_type' => 'rental_only', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 702, 'rental_name' => 'ADS 301 Complete Package', 'rental_type' => 'unit_refill', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('contract_rentals')->insert([
            ['contract_id' => $contract->id, 'master_rental_id' => 701, 'room_id' => 301, 'quantity' => 1, 'unit_price' => 1500000, 'total_price' => 1500000, 'created_at' => now(), 'updated_at' => now()],
            ['contract_id' => $contract->id, 'master_rental_id' => 702, 'room_id' => 302, 'quantity' => 1, 'unit_price' => 1000000, 'total_price' => 1000000, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $jobAdviceId = DB::table('job_advices')->insertGetId([
            'contract_id' => $contract->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $jobSchedule = JobSchedule::create([
            'job_number' => 'BDG-CSR/26-06/0005',
            'type' => 'service',
            'status' => 'done_job',
            'job_advice_id' => $jobAdviceId,
            'schedule_date' => '2026-06-29',
            'ba_date' => '2026-06-29',
        ]);
        DB::table('job_advice_rooms')->insert([
            ['id' => 801, 'job_advice_id' => $jobAdviceId, 'contract_room_id' => 601, 'service_job_schedule_id' => $jobSchedule->id, 'is_trial' => false, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 802, 'job_advice_id' => $jobAdviceId, 'contract_room_id' => 602, 'service_job_schedule_id' => $jobSchedule->id, 'is_trial' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('job_schedule_rooms')->insert([
            ['job_schedule_id' => $jobSchedule->id, 'job_advice_room_id' => 801, 'room_id' => 301, 'room_name' => 'Ruang Melati', 'status' => 'completed', 'notes' => null, 'created_at' => now(), 'updated_at' => now()],
            ['job_schedule_id' => $jobSchedule->id, 'job_advice_room_id' => 802, 'room_id' => 302, 'room_name' => 'Ruang Anggrek', 'status' => 'pending', 'notes' => '[SUSPEND] Room suspended by Admin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $service = new InvoiceGenerationService(new class extends DocumentNumberService {
            public function generate(
                string $documentType,
                ?string $branchCode = null,
                ?int $buildingId = null,
                ?int $contractId = null,
                ?int $quotationId = null,
                ?int $surveyId = null,
                ?int $warehouseId = null,
                ?int $branchId = null,
                \DateTimeInterface|string|null $documentDate = null
            ): string {
                return 'BDG-INV/26-06/0008';
            }
        });
        $method = new \ReflectionMethod($service, 'getRentalDetailsForJob');
        $method->setAccessible(true);

        $details = collect($method->invoke($service, $jobSchedule->fresh('jobAdvice.rooms')));

        $this->assertTrue($details->contains(fn ($detail) => $detail['room_name'] === 'Ruang Melati'));
        $this->assertFalse($details->contains(fn ($detail) => $detail['room_name'] === 'Ruang Anggrek'));
    }
}
