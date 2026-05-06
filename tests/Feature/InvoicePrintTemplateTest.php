<?php

namespace Tests\Feature;

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

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->nullable();
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
    }

    protected function tearDown(): void
    {
        foreach ([
            'invoice_details',
            'invoice_rental_details',
            'invoices',
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
        $this->assertStringContainsString('Finance Manager', $html);
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
        $this->assertStringContainsString('Finance Manager', $html);
    }

    private function makeInvoice(): Invoice
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

        DB::table('invoices')->insert([
            'id' => 20,
            'invoice_number' => 'INV/TEST/001',
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
        ]);

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
