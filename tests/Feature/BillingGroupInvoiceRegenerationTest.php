<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Customer;
use App\Models\Finance\BillingGroup;
use App\Models\Finance\Invoice;
use App\Models\JobSchedule;
use App\Services\DocumentNumberService;
use App\Services\Finance\BillingGroupService;
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

        Schema::create('job_advices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable();
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
            'job_schedules',
            'job_advices',
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
            'status' => 'undone',
            'job_advice_id' => $jobAdviceId,
            'schedule_date' => '2026-05-04',
            'ba_date' => null,
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
}
