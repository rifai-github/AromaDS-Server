<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Customer;
use App\Models\Invoice as LegacyInvoice;
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
            $table->string('ppn_code')->nullable();
            $table->string('npwp')->nullable();
            $table->string('npwp_address')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->foreignId('update_by_1')->nullable();
            $table->timestamp('update_at_1')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // PPN applicability is resolved from these three tables via
        // InvoiceTaxResolver, so invoice generation needs them present.
        Schema::create('customer_tax_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('ppn_code')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('tax_address')->nullable();
            $table->date('effective_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('finance_tax_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('description')->nullable();
            $table->text('ppn_status')->nullable();
            $table->string('invoice_status')->nullable();
            $table->string('faktur_pajak_status')->nullable();
            $table->string('customer_status')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tax_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('tax_code')->nullable();
            $table->string('tax_type')->nullable();
            $table->decimal('tax_rate', 8, 2)->default(0);
            $table->boolean('is_default')->default(false);
            $table->date('effective_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('payment_method')->nullable();
            $table->string('billing_methods')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('quotation_id')->nullable();
            $table->string('contract_status')->nullable();
            $table->string('status')->nullable();
            $table->boolean('hold_invoice')->default(false);
            $table->boolean('ba_files_supported')->default(false);
            $table->string('payment_terms')->nullable();
            $table->string('ppn_code')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
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
            $table->foreignId('contract_rental_id')->nullable();
            $table->foreignId('rental_product_id')->nullable();
            $table->string('room_name')->nullable();
            $table->string('rental_name')->nullable();
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
            $table->foreignId('room_id')->nullable();
            $table->integer('period')->nullable();
            $table->date('schedule_date')->nullable();
            $table->date('ba_date')->nullable();
            $table->text('internal_notes')->nullable();
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

        Schema::create('job_schedule_ba_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->string('room_name')->nullable();
            $table->string('file_name')->nullable();
            $table->string('verification_status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contract_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_type')->nullable();
            $table->string('verification_status')->nullable();
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
            $table->date('ba_date')->nullable();
            $table->string('period_invoice')->nullable();
            $table->date('invoice_date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->decimal('total_paid', 12, 2)->default(0);
            $table->decimal('outstanding', 12, 2)->default(0);
            $table->unsignedBigInteger('tax_setting_id')->nullable();
            $table->string('tax_code')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('npwp_number')->nullable();
            $table->string('tax_address')->nullable();
            $table->decimal('subtotal_after_discount', 12, 2)->default(0);
            $table->boolean('tax_obligation')->default(false);
            $table->string('invoice_status')->nullable();
            $table->string('status')->nullable();
            $table->string('kirim')->nullable();
            $table->string('gedung')->nullable();
            $table->string('alamat_1')->nullable();
            $table->string('alamat_2')->nullable();
            $table->text('catatan_internal')->nullable();
            $table->text('catatan_customer')->nullable();
            $table->integer('umur_invoice')->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('terms_conditions')->nullable();
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
            'quotations',
            'tax_settings',
            'finance_tax_codes',
            'customer_tax_settings',
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
            'ba_date' => '2026-05-04',
            'period_invoice' => 'Period 1',
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
            'period_invoice' => 'Period 1',
        ]);
        $this->assertSame('2026-05-04', $result['invoice']->fresh()->ba_date->toDateString());
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

    public function test_regenerated_cancelled_invoice_snapshot_excludes_now_suspended_room(): void
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
            'id' => 40,
            'building_name' => 'Gedung Cabang B 110526',
            'name' => 'Gedung Cabang B 110526',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('master_rooms')->insert([
            ['id' => 401, 'building_id' => 40, 'room_name' => 'Ruang Melati', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 402, 'building_id' => 40, 'room_name' => 'Ruang Anggrek', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('contract_rooms')->insert([
            ['id' => 901, 'contract_id' => $contract->id, 'room_id' => 401, 'billing_group_id' => $billingGroup->id, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 902, 'contract_id' => $contract->id, 'room_id' => 402, 'billing_group_id' => $billingGroup->id, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('master_rentals')->insert([
            ['id' => 1001, 'rental_name' => 'ADS XL Complete Package', 'rental_type' => 'rental_only', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 1002, 'rental_name' => 'ADS 301 Complete Package', 'rental_type' => 'unit_refill', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('contract_rentals')->insert([
            ['contract_id' => $contract->id, 'master_rental_id' => 1001, 'room_id' => 401, 'quantity' => 1, 'unit_price' => 1500000, 'total_price' => 1500000, 'created_at' => now(), 'updated_at' => now()],
            ['contract_id' => $contract->id, 'master_rental_id' => 1002, 'room_id' => 402, 'quantity' => 1, 'unit_price' => 1000000, 'total_price' => 1000000, 'created_at' => now(), 'updated_at' => now()],
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
            ['id' => 1101, 'job_advice_id' => $jobAdviceId, 'contract_room_id' => 901, 'service_job_schedule_id' => $jobSchedule->id, 'is_trial' => false, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 1102, 'job_advice_id' => $jobAdviceId, 'contract_room_id' => 902, 'service_job_schedule_id' => $jobSchedule->id, 'is_trial' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('job_schedule_rooms')->insert([
            ['job_schedule_id' => $jobSchedule->id, 'job_advice_room_id' => 1101, 'room_id' => 401, 'room_name' => 'Ruang Melati', 'status' => 'completed', 'notes' => null, 'created_at' => now(), 'updated_at' => now()],
            ['job_schedule_id' => $jobSchedule->id, 'job_advice_room_id' => 1102, 'room_id' => 402, 'room_name' => 'Ruang Anggrek', 'status' => 'pending', 'notes' => '[SUSPEND] Room suspended by Admin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $cancelledInvoice = Invoice::create([
            'invoice_number' => 'BDG-INV/26-06/0008',
            'contract_id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'customer_id' => $customer->id,
            'billing_group_id' => $billingGroup->id,
            'invoice_date' => '2026-06-29',
            'due_date' => '2026-07-29',
            'invoice_status' => Invoice::STATUS_CANCELLED,
            'status' => Invoice::STATUS_CANCELLED,
        ]);
        $cancelledInvoice->invoiceRentalDetails()->create([
            'master_rental_id' => 1001,
            'job_no' => 'BDG-CSR/26-06/0005',
            'building_name' => 'Gedung Cabang B 110526',
            'room_name' => 'Ruang Melati',
            'rental_name' => 'ADS XL Complete Package',
            'quantity' => 1,
            'unit_price' => 1500000,
            'total_price' => 1500000,
        ]);
        $cancelledInvoice->invoiceRentalDetails()->create([
            'master_rental_id' => 1002,
            'job_no' => 'BDG-CSR/26-06/0005',
            'building_name' => 'Gedung Cabang B 110526',
            'room_name' => 'Ruang Anggrek',
            'rental_name' => 'ADS 301 Complete Package',
            'quantity' => 1,
            'unit_price' => 1000000,
            'total_price' => 1000000,
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
                return 'BDG-INV/26-06/0009';
            }
        });

        $result = $service->autoGenerateInvoiceWhenJobsCompleted(
            $billingGroup->id,
            Carbon::parse('2026-06-29'),
            $cancelledInvoice
        );

        $this->assertTrue($result['success'], $result['message'] ?? 'No message');
        $this->assertDatabaseHas('invoice_rental_details', [
            'invoice_id' => $result['invoice']->id,
            'room_name' => 'Ruang Melati',
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

    public function test_regenerated_cancelled_invoice_snapshot_keeps_room_with_stale_cancelled_schedule_room(): void
    {
        // Reproduces the Moonton/SBY-INV/26-06/0024->0026 bug: a room's job_advice_room
        // accumulates a stale `cancelled` job_schedule_room from a superseded/corrected
        // install job, while the actual billing (CSR) job for that same room completed
        // fine. Regenerating from a cancelled invoice snapshot must not drop the room
        // just because one historical schedule-room record was cancelled.
        DB::table('users')->insert([
            'name' => 'Admin',
            'email' => 'admin@aroma.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $customer = Customer::create(['name' => 'Moonton']);
        $contract = Contract::create([
            'contract_number' => 'SBY-CA/26-06/0010',
            'customer_id' => $customer->id,
            'payment_terms' => 30,
        ]);
        $billingGroup = BillingGroup::create([
            'billing_group_name' => 'Main Billing',
            'customer_id' => $customer->id,
            'contract_id' => $contract->id,
            'billing_frequency' => 'monthly',
            'billing_start_date' => '2026-06-21',
            'billing_end_date' => '2026-09-20',
            'billing_amount' => 6000000,
            'is_active' => true,
        ]);

        DB::table('buildings')->insert([
            'id' => 50,
            'building_name' => 'Gedung Moonton',
            'name' => 'Gedung Moonton',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('master_rooms')->insert([
            ['id' => 501, 'building_id' => 50, 'room_name' => 'Ruang Meeting', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('contract_rooms')->insert([
            ['id' => 1201, 'contract_id' => $contract->id, 'room_id' => 501, 'billing_group_id' => $billingGroup->id, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('master_rentals')->insert([
            ['id' => 1301, 'rental_name' => 'Rental 1x 1bln', 'rental_type' => 'unit_refill', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('contract_rentals')->insert([
            ['contract_id' => $contract->id, 'master_rental_id' => 1301, 'room_id' => 501, 'quantity' => 1, 'unit_price' => 1000000, 'total_price' => 1000000, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $jobAdviceId = DB::table('job_advices')->insertGetId([
            'contract_id' => $contract->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Superseded install job: its room entry was cancelled when the install was corrected.
        $supersededInstallJob = JobSchedule::create([
            'job_number' => 'SBY-IR/26-06/0005',
            'type' => 'install',
            'status' => 'meninggalkan_lokasi',
            'job_advice_id' => $jobAdviceId,
            'room_id' => 501,
            'schedule_date' => '2026-06-21',
            'ba_date' => '2026-06-22',
        ]);

        // The actual billing job: a completed CSR for the same room.
        $csrJob = JobSchedule::create([
            'job_number' => 'SBY-CSR/26-06/0009',
            'type' => 'service_first',
            'status' => 'done_job',
            'job_advice_id' => $jobAdviceId,
            'room_id' => 501,
            'schedule_date' => '2026-06-21',
            'ba_date' => '2026-06-22',
        ]);

        // Corrected install job, also completed.
        $correctedInstallJob = JobSchedule::create([
            'job_number' => 'SBY-IR/26-06/0009',
            'type' => 'install',
            'status' => 'done_job',
            'job_advice_id' => $jobAdviceId,
            'room_id' => 501,
            'schedule_date' => '2026-06-22',
            'ba_date' => '2026-06-22',
        ]);

        $jobAdviceRoomId = DB::table('job_advice_rooms')->insertGetId([
            'job_advice_id' => $jobAdviceId,
            'contract_room_id' => 1201,
            'rental_product_id' => 1301,
            'service_job_schedule_id' => $csrJob->id,
            'status' => 'completed',
            'is_trial' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_schedule_rooms')->insert([
            [
                'job_schedule_id' => $supersededInstallJob->id,
                'job_advice_room_id' => $jobAdviceRoomId,
                'room_id' => 501,
                'room_name' => 'Ruang Meeting',
                'status' => 'cancelled',
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'job_schedule_id' => $csrJob->id,
                'job_advice_room_id' => $jobAdviceRoomId,
                'room_id' => 501,
                'room_name' => 'Ruang Meeting',
                'status' => 'completed',
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'job_schedule_id' => $correctedInstallJob->id,
                'job_advice_room_id' => $jobAdviceRoomId,
                'room_id' => 501,
                'room_name' => 'Ruang Meeting',
                'status' => 'completed',
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $cancelledInvoice = Invoice::create([
            'invoice_number' => 'SBY-INV/26-06/0024',
            'contract_id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'customer_id' => $customer->id,
            'billing_group_id' => $billingGroup->id,
            'invoice_date' => '2026-06-22',
            'due_date' => '2026-07-22',
            'invoice_status' => Invoice::STATUS_CANCELLED,
            'status' => Invoice::STATUS_CANCELLED,
        ]);
        $cancelledInvoice->invoiceRentalDetails()->create([
            'master_rental_id' => 1301,
            'job_no' => 'SBY-CSR/26-06/0009',
            'building_name' => 'Gedung Moonton',
            'room_name' => 'Ruang Meeting',
            'rental_name' => 'Rental 1x 1bln (3 bulan)',
            'quantity' => 1,
            'unit_price' => 1000000,
            'total_price' => 3000000,
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
                return 'SBY-INV/26-06/0026';
            }
        });

        $result = $service->autoGenerateInvoiceWhenJobsCompleted(
            $billingGroup->id,
            Carbon::parse('2026-06-22'),
            $cancelledInvoice
        );

        $this->assertTrue($result['success'], $result['message'] ?? 'No message');
        $this->assertDatabaseHas('invoice_rental_details', [
            'invoice_id' => $result['invoice']->id,
            'room_name' => 'Ruang Meeting',
            'rental_name' => 'Rental 1x 1bln (3 bulan)',
            'total_price' => 3000000,
        ]);
        $this->assertDatabaseHas('invoices', [
            'id' => $result['invoice']->id,
            'subtotal' => 3000000,
            'grand_total' => 3000000,
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

    public function test_rental_period_invoice_uses_csr_not_ir_for_unit_refill_rental(): void
    {
        [$contract, $installJob, $serviceJob] = $this->makeContractWithRentalFlow(
            rentalType: 'unit_refill',
            rentalName: 'ADS XL Complete Package',
            installJobNo: 'JKT-IR/26-05/0004',
            serviceJobNo: 'JKT-CSR/26-05/0004'
        );

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
                return 'JKT-INV/26-05/0008';
            }
        });

        $triggerMethod = new \ReflectionMethod($service, 'getCompletedInvoiceTriggerJobsInPeriod');
        $triggerMethod->setAccessible(true);
        $detailMethod = new \ReflectionMethod($service, 'getRentalDetailsForJob');
        $detailMethod->setAccessible(true);

        $triggerJobs = $triggerMethod->invoke(
            $service,
            $contract->fresh(['contractRentals.masterRental', 'contractRooms.room']),
            Carbon::parse('2026-05-01'),
            Carbon::parse('2026-05-31')
        );

        $this->assertSame(['JKT-CSR/26-05/0004'], $triggerJobs->pluck('job_number')->values()->all());
        $this->assertSame([], $detailMethod->invoke($service, $installJob->fresh('jobAdvice.rooms.rentalProduct')));
        $this->assertSame('ADS XL Complete Package', $detailMethod->invoke($service, $serviceJob->fresh('jobAdvice.rooms.rentalProduct'))[0]['rental_name']);
    }

    public function test_rental_period_invoice_uses_ir_not_csr_for_unit_only_rental(): void
    {
        [$contract, $installJob, $serviceJob] = $this->makeContractWithRentalFlow(
            rentalType: 'unit_only',
            rentalName: 'ADS Unit Only',
            installJobNo: 'JKT-IR/26-05/0005',
            serviceJobNo: 'JKT-CSR/26-05/0005'
        );

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
                return 'JKT-INV/26-05/0009';
            }
        });

        $triggerMethod = new \ReflectionMethod($service, 'getCompletedInvoiceTriggerJobsInPeriod');
        $triggerMethod->setAccessible(true);
        $detailMethod = new \ReflectionMethod($service, 'getRentalDetailsForJob');
        $detailMethod->setAccessible(true);

        $triggerJobs = $triggerMethod->invoke(
            $service,
            $contract->fresh(['contractRentals.masterRental', 'contractRooms.room']),
            Carbon::parse('2026-05-01'),
            Carbon::parse('2026-05-31')
        );

        $this->assertSame(['JKT-IR/26-05/0005'], $triggerJobs->pluck('job_number')->values()->all());
        $this->assertSame('ADS Unit Only', $detailMethod->invoke($service, $installJob->fresh('jobAdvice.rooms.rentalProduct'))[0]['rental_name']);
        $this->assertSame([], $detailMethod->invoke($service, $serviceJob->fresh('jobAdvice.rooms.rentalProduct')));
    }

    public function test_before_service_trial_continuation_is_ready_while_first_csr_is_unstarted(): void
    {
        [$contract, $installJob, $serviceJob] = $this->makeContractWithRentalFlow(
            rentalType: 'unit_refill',
            rentalName: 'ADS Trial Continuation',
            installJobNo: 'SBY-IF/26-07/0012',
            serviceJobNo: 'SBY-CSR/26-07/0048'
        );

        $quotationId = DB::table('quotations')->insertGetId([
            'payment_method' => 'Before Service',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $contract->update([
            'quotation_id' => $quotationId,
            'contract_status' => 'active',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
        ]);
        $installJob->update([
            'type' => 'install_free',
            'status' => 'done_job',
        ]);
        $serviceJob->update([
            'type' => 'service_first',
            'status' => 'scheduled',
            'period' => 1,
            'ba_date' => null,
            'internal_notes' => 'Auto-generated first CSR from contract activation: SBY-JA/26-07/0048 | Room: Ruang IF Before Service',
        ]);

        $contract = $contract->fresh(['quotation', 'contractRentals.masterRental', 'contractRooms.room']);

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
                return 'SBY-INV/26-05/0099';
            }
        });
        $readyMethod = new \ReflectionMethod($service, 'getReadyInvoiceTriggerJobsInPeriod');
        $readyMethod->setAccessible(true);
        $completedMethod = new \ReflectionMethod($service, 'getCompletedInvoiceTriggerJobsInPeriod');
        $completedMethod->setAccessible(true);
        $detailMethod = new \ReflectionMethod($service, 'getRentalDetailsForJob');
        $detailMethod->setAccessible(true);

        $periodStart = Carbon::parse('2026-05-01');
        $periodEnd = Carbon::parse('2026-05-31');

        $this->assertSame([], $completedMethod->invoke($service, $contract, $periodStart, $periodEnd)->all());
        $this->assertSame(
            ['SBY-CSR/26-07/0048'],
            $readyMethod->invoke($service, $contract, $periodStart, $periodEnd)->pluck('job_number')->all()
        );
        $this->assertSame(
            'ADS Trial Continuation',
            $detailMethod->invoke($service, $serviceJob->fresh('jobAdvice.rooms.rentalProduct'))[0]['rental_name']
        );

        $result = $service->autoGenerateInvoiceForRentalPeriod($contract->id, 'Period 1', $periodStart, $periodEnd);

        $this->assertTrue($result['success'], $result['message']);
        $this->assertDatabaseHas('invoices', [
            'contract_id' => $contract->id,
            'period_invoice' => 'Period 1',
            'invoice_status' => 'draft',
        ]);
        $this->assertDatabaseHas('invoice_rental_details', [
            'job_no' => 'SBY-CSR/26-07/0048',
            'rental_name' => 'ADS Trial Continuation',
            'total_price' => 2000000,
        ]);
    }

    public function test_after_service_trial_continuation_still_waits_for_csr_completion(): void
    {
        [$contract, $installJob, $serviceJob] = $this->makeContractWithRentalFlow(
            rentalType: 'unit_refill',
            rentalName: 'ADS Trial Continuation',
            installJobNo: 'SBY-IF/26-07/0013',
            serviceJobNo: 'SBY-CSR/26-07/0049'
        );

        $installJob->update([
            'type' => 'install_free',
            'status' => 'done_job',
        ]);
        $serviceJob->update([
            'type' => 'service_first',
            'status' => 'scheduled',
            'period' => 1,
            'ba_date' => null,
            'internal_notes' => 'Auto-generated first CSR from contract activation: SBY-JA/26-07/0049 | Room: Ruang IF After Service',
        ]);

        $contract->setRelation('quotation', new \App\Models\Quotation([
            'payment_method' => 'After Service',
        ]));

        $service = new InvoiceGenerationService(new DocumentNumberService);
        $readyMethod = new \ReflectionMethod($service, 'getReadyInvoiceTriggerJobsInPeriod');
        $readyMethod->setAccessible(true);

        $this->assertSame(
            [],
            $readyMethod->invoke(
                $service,
                $contract,
                Carbon::parse('2026-05-01'),
                Carbon::parse('2026-05-31')
            )->all()
        );
    }

    public function test_rental_period_invoice_uses_unit_only_check_after_install_period(): void
    {
        [$contract, $installJob, $checkJob] = $this->makeContractWithRentalFlow(
            rentalType: 'unit_only',
            rentalName: 'ADS Unit Only',
            installJobNo: 'JKT-IR/26-05/0005',
            serviceJobNo: 'JKT-IR/26-06/0005'
        );

        $checkJob->update([
            'type' => 'service_routine',
            'period' => 2,
            'schedule_date' => '2026-06-26',
            'ba_date' => '2026-06-26',
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
                return 'JKT-INV/26-06/0009';
            }
        });

        $triggerMethod = new \ReflectionMethod($service, 'getCompletedInvoiceTriggerJobsInPeriod');
        $triggerMethod->setAccessible(true);
        $detailMethod = new \ReflectionMethod($service, 'getRentalDetailsForJob');
        $detailMethod->setAccessible(true);

        $triggerJobs = $triggerMethod->invoke(
            $service,
            $contract->fresh(['contractRentals.masterRental', 'contractRooms.room']),
            Carbon::parse('2026-06-01'),
            Carbon::parse('2026-06-30')
        );

        $this->assertSame(['JKT-IR/26-06/0005'], $triggerJobs->pluck('job_number')->values()->all());
        $this->assertSame('ADS Unit Only', $detailMethod->invoke($service, $checkJob->fresh('jobAdvice.rooms.rentalProduct'))[0]['rental_name']);
    }

    public function test_rental_period_invoice_includes_refill_only_csr_when_same_room_has_unit_only_ir(): void
    {
        [$contract, $installJob, $serviceJob] = $this->makeContractWithUnitOnlyAndRefillOnlyInSameRoom();

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
                return 'JKT-INV/26-06/0001';
            }
        });

        $triggerMethod = new \ReflectionMethod($service, 'getCompletedInvoiceTriggerJobsInPeriod');
        $triggerMethod->setAccessible(true);
        $detailMethod = new \ReflectionMethod($service, 'getRentalDetailsForJob');
        $detailMethod->setAccessible(true);

        $triggerJobs = $triggerMethod->invoke(
            $service,
            $contract->fresh(['contractRentals.masterRental', 'contractRooms.room']),
            Carbon::parse('2026-06-01'),
            Carbon::parse('2026-06-30')
        );

        $this->assertSame(
            ['JKT-IR/26-06/0002', 'JKT-CSR/26-06/0004'],
            $triggerJobs->pluck('job_number')->values()->all()
        );

        $installDetails = $detailMethod->invoke($service, $installJob->fresh('jobAdvice.rooms.rentalProduct'));
        $serviceDetails = $detailMethod->invoke($service, $serviceJob->fresh('jobAdvice.rooms.rentalProduct'));

        $this->assertSame('ADS XL Unit Only', $installDetails[0]['rental_name']);
        $this->assertSame('JKT-IR/26-06/0002', $installJob->job_number);
        $this->assertSame('Refill Only', $serviceDetails[0]['rental_name']);
        $this->assertSame(500000.0, (float) $serviceDetails[0]['unit_price']);
        $this->assertSame('Ruang Delima', $serviceDetails[0]['room_name']);
    }

    public function test_repair_missing_invoice_rental_details_adds_refill_only_csr_row(): void
    {
        [$contract, $installJob] = $this->makeContractWithUnitOnlyAndRefillOnlyInSameRoom();

        $invoice = Invoice::create([
            'invoice_number' => 'JKT-INV/26-06/0001',
            'contract_id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'customer_id' => $contract->customer_id,
            'invoice_status' => 'draft',
            'invoice_date' => '2026-06-04',
            'due_date' => '2026-07-04',
            'tax_obligation' => false,
            'subtotal' => 1000000,
            'tax_amount' => 0,
            'total_amount' => 1000000,
            'grand_total' => 1000000,
            'outstanding' => 1000000,
        ]);
        $invoice->invoiceRentalDetails()->create([
            'master_rental_id' => 1992,
            'job_no' => $installJob->job_number,
            'building_name' => 'Gedung Test260218',
            'room_name' => 'Ruang Delima',
            'rental_name' => 'ADS XL Unit Only',
            'quantity' => 1,
            'unit_price' => 1000000,
            'total_price' => 1000000,
        ]);

        $this->artisan('finance:repair-missing-invoice-rental-details', [
            '--invoice-number' => ['JKT-INV/26-06/0001'],
        ])->assertSuccessful();

        $this->assertDatabaseCount('invoice_rental_details', 1);

        $this->artisan('finance:repair-missing-invoice-rental-details', [
            '--invoice-number' => ['JKT-INV/26-06/0001'],
            '--apply' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('invoice_rental_details', [
            'invoice_id' => $invoice->id,
            'master_rental_id' => 1993,
            'job_no' => 'JKT-CSR/26-06/0004',
            'room_name' => 'Ruang Delima',
            'rental_name' => 'Refill Only',
            'unit_price' => 500000,
            'total_price' => 500000,
        ]);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'subtotal' => 1500000,
            'total_amount' => 1500000,
            'grand_total' => 1500000,
        ]);
    }

    public function test_expected_invoice_rows_use_schedule_room_scope_for_partial_follow_up_jobs(): void
    {
        $customer = Customer::create(['name' => 'Abadi Company']);
        $contract = Contract::create([
            'contract_number' => 'SBY-CA/26-06/0001',
            'customer_id' => $customer->id,
            'payment_terms' => 30,
        ]);

        DB::table('buildings')->insert([
            'id' => 190,
            'building_name' => 'Hotel Melton Surabaya',
            'name' => 'Hotel Melton Surabaya',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('master_rooms')->insert([
            ['id' => 400, 'building_id' => 190, 'room_name' => 'Lobby', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 401, 'building_id' => 190, 'room_name' => 'Ruang VIP', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('contract_rooms')->insert([
            ['id' => 500, 'contract_id' => $contract->id, 'room_id' => 400, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 501, 'contract_id' => $contract->id, 'room_id' => 401, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('master_rentals')->insert([
            ['id' => 700, 'rental_name' => 'ADS W300 500 ml', 'rental_type' => 'unit_refill', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 701, 'rental_name' => 'ADS W300 300 ml baterai', 'rental_type' => 'unit_refill', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('contract_rentals')->insert([
            [
                'id' => 800,
                'contract_id' => $contract->id,
                'master_rental_id' => 700,
                'room_id' => 400,
                'quantity' => 1,
                'unit_price' => 5000000,
                'total_price' => 5000000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 801,
                'contract_id' => $contract->id,
                'master_rental_id' => 701,
                'room_id' => 401,
                'quantity' => 1,
                'unit_price' => 1000000,
                'total_price' => 1000000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $lobbyJobAdviceId = DB::table('job_advices')->insertGetId([
            'contract_id' => $contract->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $vipJobAdviceId = DB::table('job_advices')->insertGetId([
            'contract_id' => $contract->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $originalLobbyJob = JobSchedule::create([
            'job_number' => 'SBY-CSR/26-06/0001',
            'type' => 'service_first',
            'status' => 'meninggalkan_lokasi',
            'job_advice_id' => $lobbyJobAdviceId,
            'schedule_date' => '2026-06-11',
            'ba_date' => '2026-06-11',
        ]);
        $vipJob = JobSchedule::create([
            'job_number' => 'SBY-CSR/26-06/0002',
            'type' => 'service_first',
            'status' => 'done_job',
            'job_advice_id' => $vipJobAdviceId,
            'room_id' => 401,
            'schedule_date' => '2026-06-11',
            'ba_date' => '2026-06-11',
        ]);
        $followUpLobbyJob = JobSchedule::create([
            'job_number' => 'SBY-CSR/26-06/0003',
            'type' => 'service_first',
            'status' => 'done_job',
            'job_advice_id' => $lobbyJobAdviceId,
            'room_id' => null,
            'schedule_date' => '2026-06-11',
            'ba_date' => '2026-06-11',
        ]);

        DB::table('job_advice_rooms')->insert([
            [
                'id' => 900,
                'job_advice_id' => $lobbyJobAdviceId,
                'contract_room_id' => 500,
                'contract_rental_id' => 800,
                'rental_product_id' => 700,
                'service_job_schedule_id' => $originalLobbyJob->id,
                'room_name' => 'Lobby',
                'rental_name' => 'ADS W300 500 ml',
                'status' => 'completed',
                'is_trial' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 901,
                'job_advice_id' => $vipJobAdviceId,
                'contract_room_id' => 501,
                'contract_rental_id' => 801,
                'rental_product_id' => 701,
                'service_job_schedule_id' => $vipJob->id,
                'room_name' => 'Ruang VIP',
                'rental_name' => 'ADS W300 300 ml baterai',
                'status' => 'completed',
                'is_trial' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        DB::table('job_schedule_rooms')->insert([
            [
                'job_schedule_id' => $originalLobbyJob->id,
                'job_advice_room_id' => 900,
                'room_id' => 400,
                'room_name' => 'Lobby',
                'status' => 'cancelled',
                'notes' => 'Pekerjaan tidak selesai, dipindahkan ke Job baru.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'job_schedule_id' => $vipJob->id,
                'job_advice_room_id' => 901,
                'room_id' => 401,
                'room_name' => 'Ruang VIP',
                'status' => 'completed',
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'job_schedule_id' => $followUpLobbyJob->id,
                'job_advice_room_id' => 900,
                'room_id' => 400,
                'room_name' => 'Lobby',
                'status' => 'completed',
                'notes' => 'Pindahan dari Job SBY-CSR/26-06/0001',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $invoice = LegacyInvoice::create([
            'invoice_number' => 'SBY-INV/26-06/0001',
            'contract_id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'customer_id' => $customer->id,
            'invoice_status' => 'draft',
            'invoice_date' => '2026-06-11',
            'due_date' => '2026-07-11',
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
                return 'SBY-INV/26-06/0001';
            }
        });

        $expectedRows = collect($service->expectedRentalDetailsForInvoice($invoice));

        $this->assertCount(2, $expectedRows);
        $this->assertSame(6000000.0, (float) $expectedRows->sum('total_price'));
        $this->assertSame(1, $expectedRows->where('room_name', 'Lobby')->count());
        $this->assertSame(1, $expectedRows->where('room_name', 'Ruang VIP')->count());
        $this->assertFalse(
            $expectedRows->contains(fn ($row) => $row['job_no'] === 'SBY-CSR/26-06/0003' && $row['room_name'] === 'Ruang VIP')
        );

        $invoice->invoiceRentalDetails()->create([
            'master_rental_id' => 700,
            'job_no' => 'SBY-CSR/26-06/0003',
            'room_name' => 'Lobby',
            'rental_name' => 'ADS W300 500 ml',
            'quantity' => 1,
            'unit_price' => 5000000,
            'total_price' => 5000000,
        ]);
        $invoice->invoiceRentalDetails()->create([
            'master_rental_id' => 701,
            'job_no' => 'SBY-CSR/26-06/0003',
            'room_name' => 'Ruang VIP',
            'rental_name' => 'ADS W300 300 ml baterai',
            'quantity' => 1,
            'unit_price' => 1000000,
            'total_price' => 1000000,
        ]);
        $invoice->invoiceRentalDetails()->create([
            'master_rental_id' => 701,
            'job_no' => 'SBY-CSR/26-06/0002',
            'room_name' => 'Ruang VIP',
            'rental_name' => 'ADS W300 300 ml baterai',
            'quantity' => 1,
            'unit_price' => 1000000,
            'total_price' => 1000000,
        ]);

        $this->artisan('finance:repair-missing-invoice-rental-details', [
            '--invoice-number' => ['SBY-INV/26-06/0001'],
            '--rebuild' => true,
        ])->assertSuccessful();
        $this->assertDatabaseCount('invoice_rental_details', 3);

        $this->artisan('finance:repair-missing-invoice-rental-details', [
            '--invoice-number' => ['SBY-INV/26-06/0001'],
            '--rebuild' => true,
            '--apply' => true,
        ])->assertSuccessful();

        $this->assertDatabaseCount('invoice_rental_details', 2);
        $this->assertDatabaseMissing('invoice_rental_details', [
            'invoice_id' => $invoice->id,
            'job_no' => 'SBY-CSR/26-06/0003',
            'room_name' => 'Ruang VIP',
        ]);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'subtotal' => 6000000,
            'total_amount' => 6000000,
            'grand_total' => 6000000,
        ]);
    }

    public function test_real_time_invoice_trigger_backfills_room_that_completes_after_draft_invoice_created(): void
    {
        DB::table('users')->insert([
            'name' => 'Admin',
            'email' => 'admin@aroma.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $customer = Customer::create(['name' => 'Test Multi Room']);
        $contract = Contract::create([
            'contract_number' => 'SBY-CA/26-06/0010',
            'customer_id' => $customer->id,
            'payment_terms' => 30,
            'start_date' => '2026-06-21',
            'end_date' => '2026-12-20',
        ]);
        BillingGroup::create([
            'billing_group_name' => 'Main Billing',
            'customer_id' => $customer->id,
            'contract_id' => $contract->id,
            'billing_frequency' => 'monthly',
            'is_active' => true,
        ]);

        DB::table('buildings')->insert([
            'id' => 70,
            'building_name' => 'Gedung Multi Room',
            'name' => 'Gedung Multi Room',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('master_rooms')->insert([
            ['id' => 701, 'building_id' => 70, 'room_name' => 'Ruang Meeting', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 702, 'building_id' => 70, 'room_name' => 'Ruang Aula', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('contract_rooms')->insert([
            ['id' => 7001, 'contract_id' => $contract->id, 'room_id' => 701, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7002, 'contract_id' => $contract->id, 'room_id' => 702, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('master_rentals')->insert([
            ['id' => 7101, 'rental_name' => 'Rental 1x 1bln', 'rental_type' => 'unit_refill', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7102, 'rental_name' => 'ADS W300 100 ml', 'rental_type' => 'unit_refill', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('contract_rentals')->insert([
            ['id' => 7201, 'contract_id' => $contract->id, 'master_rental_id' => 7101, 'room_id' => 701, 'quantity' => 1, 'unit_price' => 1000000, 'total_price' => 1000000, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7202, 'contract_id' => $contract->id, 'master_rental_id' => 7102, 'room_id' => 702, 'quantity' => 1, 'unit_price' => 1000000, 'total_price' => 1000000, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $jobAdviceId = DB::table('job_advices')->insertGetId([
            'contract_id' => $contract->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Meeting room's job completes first.
        $meetingJob = JobSchedule::create([
            'job_number' => 'SBY-CSR/26-06/0009',
            'type' => 'service_first',
            'status' => 'done_job',
            'job_advice_id' => $jobAdviceId,
            'room_id' => 701,
            'schedule_date' => '2026-06-21',
            'ba_date' => '2026-06-21',
        ]);
        DB::table('job_advice_rooms')->insert([
            'id' => 7301,
            'job_advice_id' => $jobAdviceId,
            'contract_room_id' => 7001,
            'contract_rental_id' => 7201,
            'rental_product_id' => 7101,
            'service_job_schedule_id' => $meetingJob->id,
            'room_name' => 'Ruang Meeting',
            'rental_name' => 'Rental 1x 1bln',
            'status' => 'completed',
            'is_trial' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('job_schedule_rooms')->insert([
            'job_schedule_id' => $meetingJob->id,
            'job_advice_room_id' => 7301,
            'room_id' => 701,
            'room_name' => 'Ruang Meeting',
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
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
                return 'SBY-INV/26-06/0027';
            }
        });

        // First trigger: only Ruang Meeting's job is done. An invoice for Period 1 is
        // drafted but should only contain Meeting's rental so far.
        $service->attemptAutoInvoiceForContract($contract->id);

        $this->assertDatabaseHas('invoices', [
            'contract_id' => $contract->id,
            'period_invoice' => 'Period 1',
            'invoice_status' => 'draft',
        ]);
        $invoice = Invoice::where('contract_id', $contract->id)->where('period_invoice', 'Period 1')->first();
        $this->assertNotNull($invoice);
        $this->assertDatabaseCount('invoice_rental_details', 1);
        $this->assertDatabaseHas('invoice_rental_details', [
            'invoice_id' => $invoice->id,
            'room_name' => 'Ruang Meeting',
        ]);

        // Aula room's job completes afterwards, still inside the same Period 1 window.
        $aulaJob = JobSchedule::create([
            'job_number' => 'SBY-CSR/26-06/0009',
            'type' => 'service_first',
            'status' => 'done_job',
            'job_advice_id' => $jobAdviceId,
            'room_id' => 702,
            'schedule_date' => '2026-06-21',
            'ba_date' => '2026-06-22',
        ]);
        DB::table('job_advice_rooms')->insert([
            'id' => 7302,
            'job_advice_id' => $jobAdviceId,
            'contract_room_id' => 7002,
            'contract_rental_id' => 7202,
            'rental_product_id' => 7102,
            'service_job_schedule_id' => $aulaJob->id,
            'room_name' => 'Ruang Aula',
            'rental_name' => 'ADS W300 100 ml',
            'status' => 'completed',
            'is_trial' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('job_schedule_rooms')->insert([
            'job_schedule_id' => $aulaJob->id,
            'job_advice_room_id' => 7302,
            'room_id' => 702,
            'room_name' => 'Ruang Aula',
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Second trigger: should backfill Aula into the SAME draft invoice, not skip it
        // and not create a second invoice for Period 1.
        $service->attemptAutoInvoiceForContract($contract->id);

        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_rental_details', 2);
        $this->assertDatabaseHas('invoice_rental_details', [
            'invoice_id' => $invoice->id,
            'room_name' => 'Ruang Aula',
        ]);
        // payment_terms=30 (no "bulan" unit) resolves to a 1-month billing period, so
        // each room's 1,000,000/month rental bills once.
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'subtotal' => 2000000,
        ]);
    }

    public function test_multi_month_top_period_bills_sq_price_flat_not_multiplied_by_months(): void
    {
        // Client rule (1 Jul 2026): the price on the SQ is per TOP installment and must
        // be billed as-is, regardless of how many calendar months the TOP period spans.
        // Contract: 3 bulan 1x TOP -> period spans 3 months, SQ price stays 1,000,000/invoice.
        DB::table('users')->insert([
            'name' => 'Admin',
            'email' => 'admin2@aroma.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $customer = Customer::create(['name' => 'Test TOP Multi Month']);
        $contract = Contract::create([
            'contract_number' => 'SBY-CA/26-07/0011',
            'customer_id' => $customer->id,
            'payment_terms' => '3 bulan 1x',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);
        BillingGroup::create([
            'billing_group_name' => 'Main Billing',
            'customer_id' => $customer->id,
            'contract_id' => $contract->id,
            'billing_frequency' => 'monthly',
            'is_active' => true,
        ]);

        DB::table('buildings')->insert([
            'id' => 80,
            'building_name' => 'Gedung TOP',
            'name' => 'Gedung TOP',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('master_rooms')->insert([
            ['id' => 801, 'building_id' => 80, 'room_name' => 'Ruang TOP', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('contract_rooms')->insert([
            ['id' => 8001, 'contract_id' => $contract->id, 'room_id' => 801, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('master_rentals')->insert([
            ['id' => 8101, 'rental_name' => 'Rental 3bln TOP', 'rental_type' => 'unit_refill', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('contract_rentals')->insert([
            ['id' => 8201, 'contract_id' => $contract->id, 'master_rental_id' => 8101, 'room_id' => 801, 'quantity' => 1, 'unit_price' => 1000000, 'total_price' => 1000000, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $jobAdviceId = DB::table('job_advices')->insertGetId([
            'contract_id' => $contract->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $topJob = JobSchedule::create([
            'job_number' => 'SBY-CSR/26-01/0011',
            'type' => 'service_first',
            'status' => 'done_job',
            'job_advice_id' => $jobAdviceId,
            'room_id' => 801,
            'schedule_date' => '2026-01-05',
            'ba_date' => '2026-01-05',
        ]);
        DB::table('job_advice_rooms')->insert([
            'id' => 8301,
            'job_advice_id' => $jobAdviceId,
            'contract_room_id' => 8001,
            'contract_rental_id' => 8201,
            'rental_product_id' => 8101,
            'service_job_schedule_id' => $topJob->id,
            'room_name' => 'Ruang TOP',
            'rental_name' => 'Rental 3bln TOP',
            'status' => 'completed',
            'is_trial' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('job_schedule_rooms')->insert([
            'job_schedule_id' => $topJob->id,
            'job_advice_room_id' => 8301,
            'room_id' => 801,
            'room_name' => 'Ruang TOP',
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
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
                return 'SBY-INV/26-01/0031';
            }
        });

        // Contract's TOP interval must resolve to 3 months so Period 1 (Jan-Mar) spans
        // multiple calendar months.
        $this->assertSame(3, $contract->fresh()->top_interval_months);

        $invoice = LegacyInvoice::create([
            'invoice_number' => 'SBY-INV/26-01/0031',
            'contract_id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'customer_id' => $customer->id,
            'billing_group_id' => null,
            'period_invoice' => 'Period 1',
            'invoice_date' => '2026-01-05',
            'due_date' => '2026-02-04',
            'invoice_status' => Invoice::STATUS_DRAFT,
            'status' => Invoice::STATUS_DRAFT,
        ]);

        $createDetailsMethod = new \ReflectionMethod($service, 'createInvoiceDetailsFromJobs');
        $createDetailsMethod->setAccessible(true);
        $createDetailsMethod->invoke(
            $service,
            $invoice,
            $contract->fresh(['contractRentals.masterRental', 'contractRooms.room']),
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-03-31')
        );

        // Period 1 spans 3 calendar months (Jan-Mar), but the invoice amount must stay
        // the flat SQ price (1,000,000), not 1,000,000 x 3.
        $this->assertDatabaseHas('invoice_rental_details', [
            'invoice_id' => $invoice->id,
            'room_name' => 'Ruang TOP',
            'total_price' => 1000000,
        ]);
    }

    private function makeContractWithRentalFlow(
        string $rentalType,
        string $rentalName,
        string $installJobNo,
        string $serviceJobNo
    ): array {
        $customer = Customer::create(['name' => 'Test 260218 PT']);
        $contract = Contract::create([
            'contract_number' => 'JKT-CA/26-05/0001',
            'customer_id' => $customer->id,
            'payment_terms' => 30,
        ]);

        DB::table('buildings')->insert([
            'id' => 99,
            'building_name' => 'Gedung Test260218',
            'name' => 'Gedung Test260218',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('master_rooms')->insert([
            'id' => 990,
            'building_id' => 99,
            'room_name' => 'Ruang Wijaya',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('contract_rooms')->insert([
            'id' => 991,
            'contract_id' => $contract->id,
            'room_id' => 990,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('master_rentals')->insert([
            'id' => 992,
            'rental_name' => $rentalName,
            'rental_type' => $rentalType,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('contract_rentals')->insert([
            'contract_id' => $contract->id,
            'master_rental_id' => 992,
            'room_id' => 990,
            'quantity' => 1,
            'unit_price' => 2000000,
            'total_price' => 2000000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $jobAdviceId = DB::table('job_advices')->insertGetId([
            'contract_id' => $contract->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $installJob = JobSchedule::create([
            'job_number' => $installJobNo,
            'type' => 'install',
            'status' => 'done_job',
            'job_advice_id' => $jobAdviceId,
            'schedule_date' => '2026-05-26',
            'ba_date' => '2026-05-26',
        ]);
        $serviceJob = JobSchedule::create([
            'job_number' => $serviceJobNo,
            'type' => 'service',
            'status' => 'done_job',
            'job_advice_id' => $jobAdviceId,
            'schedule_date' => '2026-05-31',
            'ba_date' => '2026-05-31',
        ]);
        DB::table('job_advice_rooms')->insert([
            'job_advice_id' => $jobAdviceId,
            'contract_room_id' => 991,
            'rental_product_id' => 992,
            'install_job_schedule_id' => $installJob->id,
            'service_job_schedule_id' => $serviceJob->id,
            'is_trial' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$contract, $installJob, $serviceJob];
    }

    private function makeContractWithUnitOnlyAndRefillOnlyInSameRoom(): array
    {
        $customer = Customer::create(['name' => 'Test 260218 PT']);
        $contract = Contract::create([
            'contract_number' => 'JKT-CA/26-06/0004',
            'customer_id' => $customer->id,
            'payment_terms' => 30,
        ]);

        DB::table('buildings')->insert([
            'id' => 199,
            'building_name' => 'Gedung Test260218',
            'name' => 'Gedung Test260218',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('master_rooms')->insert([
            'id' => 1990,
            'building_id' => 199,
            'room_name' => 'Ruang Delima',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('contract_rooms')->insert([
            'id' => 1991,
            'contract_id' => $contract->id,
            'room_id' => 1990,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('master_rentals')->insert([
            ['id' => 1992, 'rental_name' => 'ADS XL Unit Only', 'rental_type' => 'unit_only', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 1993, 'rental_name' => 'Refill Only', 'rental_type' => 'refill_only', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('contract_rentals')->insert([
            [
                'contract_id' => $contract->id,
                'master_rental_id' => 1992,
                'room_id' => 1990,
                'quantity' => 1,
                'unit_price' => 1000000,
                'total_price' => 1000000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'contract_id' => $contract->id,
                'master_rental_id' => 1993,
                'room_id' => 1990,
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
        $installJob = JobSchedule::create([
            'job_number' => 'JKT-IR/26-06/0002',
            'type' => 'install',
            'status' => 'done_job',
            'job_advice_id' => $jobAdviceId,
            'schedule_date' => '2026-06-04',
            'ba_date' => '2026-06-04',
        ]);
        $serviceJob = JobSchedule::create([
            'job_number' => 'JKT-CSR/26-06/0004',
            'type' => 'service_first',
            'status' => 'done_job',
            'job_advice_id' => $jobAdviceId,
            'schedule_date' => '2026-06-04',
            'ba_date' => '2026-06-04',
        ]);
        DB::table('job_advice_rooms')->insert([
            [
                'job_advice_id' => $jobAdviceId,
                'contract_room_id' => 1991,
                'rental_product_id' => 1992,
                'install_job_schedule_id' => $installJob->id,
                'service_job_schedule_id' => null,
                'is_trial' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'job_advice_id' => $jobAdviceId,
                'contract_room_id' => 1991,
                'rental_product_id' => 1993,
                'install_job_schedule_id' => null,
                'service_job_schedule_id' => $serviceJob->id,
                'is_trial' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        return [$contract, $installJob, $serviceJob];
    }
}
