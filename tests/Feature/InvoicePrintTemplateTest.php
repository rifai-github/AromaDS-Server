<?php

namespace Tests\Feature;

use App\Http\Controllers\Finance\InvoiceController;
use App\Models\Finance\Invoice;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class InvoicePrintTemplateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key');
            $table->text('setting_value')->nullable();
            $table->string('setting_type')->default('string');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->text('billing_address')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('position_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->foreignId('invoice_authorized_by_user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->nullable();
            $table->foreignId('contract_id')->nullable();
            $table->string('contract_number')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->text('billing_address')->nullable();
            $table->string('pic_name')->nullable();
            $table->string('invoice_status')->nullable();
            $table->date('invoice_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('tax_code')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoice_rental_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->nullable();
            $table->string('rental_name')->nullable();
            $table->string('room_name')->nullable();
            $table->string('job_no')->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total_price', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('invoice_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->nullable();
            $table->string('description')->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total_price', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->nullable();
            $table->string('contract_number')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        foreach ([
            'invoice_details',
            'job_schedules',
            'invoice_rental_details',
            'invoices',
            'contracts',
            'branches',
            'users',
            'customers',
            'system_settings',
            'companies',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_invoice_print_template_defaults_authorized_signature_to_finance_manager(): void
    {
        $invoice = $this->makeInvoice();

        $html = View::make('finance.invoices.print_template', compact('invoice'))->render();

        $this->assertStringContainsString('Authorized By', $html);
        $this->assertStringContainsString('Manager Finance', $html);
        $this->assertStringContainsString('PT Pink Services Indonesia', $html);
    }

    public function test_invoice_print_template_uses_configured_authorized_signature(): void
    {
        DB::table('system_settings')->insert([
            [
                'setting_key' => 'invoice_authorized_by_name',
                'setting_value' => 'Ani Finance',
                'setting_type' => 'string',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'setting_key' => 'invoice_authorized_by_position',
                'setting_value' => 'Finance Director',
                'setting_type' => 'string',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $invoice = $this->makeInvoice();

        $html = View::make('finance.invoices.print_template', compact('invoice'))->render();

        $this->assertStringContainsString('Ani Finance', $html);
        $this->assertStringContainsString('Finance Director', $html);
    }

    public function test_invoice_print_template_uses_branch_signatory_user_from_master_branch(): void
    {
        DB::table('users')->insert([
            'id' => 30,
            'name' => 'Budi Branch',
            'email' => 'budi@example.test',
            'position_name' => 'Branch Manager',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('branches')->insert([
            'id' => 40,
            'code' => 'BDG',
            'name' => 'Bandung Branch',
            'invoice_authorized_by_user_id' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('contracts')->insert([
            'id' => 50,
            'contract_number' => 'BDG-CA/TEST/001',
            'customer_id' => 10,
            'branch_id' => 40,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $invoice = $this->makeInvoice([
            'contract_id' => 50,
            'contract_number' => null,
        ])->load(['contractById.branch.invoiceAuthorizedByUser']);

        $html = View::make('finance.invoices.print_template', compact('invoice'))->render();

        $this->assertStringContainsString('Budi Branch', $html);
        $this->assertStringContainsString('Branch Manager', $html);
        $this->assertStringNotContainsString('Manager Finance</p>', $html);
    }

    public function test_invoice_print_template_falls_back_when_system_settings_schema_is_legacy(): void
    {
        Schema::dropIfExists('system_settings');
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->nullable();
            $table->text('value')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        $invoice = $this->makeInvoice();

        $html = View::make('finance.invoices.print_template', compact('invoice'))->render();

        $this->assertStringContainsString('Authorized By', $html);
        $this->assertStringContainsString('Manager Finance', $html);
    }

    public function test_invoice_header_print_keeps_only_first_system_csr_attachment(): void
    {
        $controller = new InvoiceController;
        $method = new \ReflectionMethod($controller, 'getDefaultPrintAttachmentIds');
        $method->setAccessible(true);

        $ids = $method->invoke($controller, collect([
            (object) ['id' => 'sys-csr-7'],
            (object) ['id' => 'sys-csr-6'],
            (object) ['id' => 'inv-9'],
            (object) ['id' => 'ba-3'],
        ]));

        $this->assertSame(['sys-csr-7', 'inv-9', 'ba-3'], $ids);
    }

    public function test_delivery_receipt_renumbers_documents_and_counts_invoice_jobs_only(): void
    {
        $invoice = $this->makeInvoice([
            'invoice_number' => 'SBY-INV/26-06/0001',
            'contract_number' => 'SBY-CA/26-06/0001',
        ]);

        DB::table('contracts')->insert([
            'id' => 70,
            'contract_number' => 'SBY-CA/26-06/0001',
            'customer_id' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('invoice_rental_details')->insert([
            'invoice_id' => 20,
            'rental_name' => 'C200 200 ml',
            'room_name' => 'Meeting Room',
            'job_no' => 'SBY-CSR/26-06/0003',
            'quantity' => 1,
            'unit_price' => 120000,
            'total_price' => 120000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $jobs = [];
        for ($i = 1; $i <= 26; $i++) {
            $jobs[] = [
                'job_number' => sprintf('SBY-CSR/26-06/%04d', $i),
                'contract_number' => 'SBY-CA/26-06/0001',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('job_schedules')->insert($jobs);

        $invoice = $invoice->fresh(['customer', 'contract', 'invoiceRentalDetails']);

        $html = View::make('finance.invoices.delivery_receipt_pdf', compact('invoice'))->render();

        $this->assertStringNotContainsString('Faktur Pajak Asli', $html);
        $this->assertStringContainsString('<td class="center">1</td>', $html);
        $this->assertStringContainsString('<td class="center">2</td>', $html);
        $this->assertStringContainsString('<td class="center">3</td>', $html);
        $this->assertStringNotContainsString('<td class="center">4</td>', $html);
        $this->assertStringContainsString('Lampiran Kontrak / PO No: SBY-CA/26-06/0001', $html);
        $this->assertStringContainsString('Berita Acara Pekerjaan (2 dokumen)', $html);
        $this->assertStringNotContainsString('26 dokumen', $html);
    }

    private function makeInvoice(array $overrides = []): Invoice
    {
        DB::table('companies')->insert([
            'id' => 1,
            'name' => 'PT Pink Services Indonesia',
            'address' => 'Jl. Sudirman No. 1',
            'phone' => '021-12345678',
            'email' => 'info@pinkservices.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('customers')->insert([
            'id' => 10,
            'name' => 'Maju Sejahtera Indonesia',
            'billing_address' => 'Bandung',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('invoices')->insert(array_merge([
            'id' => 20,
            'invoice_number' => 'INV/TEST/001',
            'contract_id' => null,
            'contract_number' => null,
            'customer_id' => 10,
            'invoice_status' => 'tax_approved',
            'invoice_date' => '2026-05-04',
            'due_date' => '2026-05-04',
            'tax_code' => null,
            'subtotal' => 120000,
            'discount_amount' => 0,
            'tax_amount' => 13200,
            'grand_total' => 133200,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));

        DB::table('invoice_rental_details')->insert([
            'invoice_id' => 20,
            'rental_name' => 'C100 100 ml',
            'room_name' => 'Office Room',
            'job_no' => 'BDG-CSR/26-05/0001',
            'quantity' => 1,
            'unit_price' => 120000,
            'total_price' => 120000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Invoice::with(['customer', 'invoiceRentalDetails', 'invoiceDetails'])->findOrFail(20);
    }
}
