<?php

namespace Tests\Feature;

use App\Models\Finance\Invoice;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * UAT 24 Nov 2026: data pajak customer di invoice = snapshot saat invoice terbit; tidak boleh
 * ikut berubah bila master Customer Tax diganti di tengah penagihan.
 */
class InvoiceIdentitySnapshotTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('nik')->nullable();
            $table->string('npwp')->nullable();
            $table->string('npwp_address')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('customer_tax_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('tax_address')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('billing_groups', function (Blueprint $table) {
            $table->id();
            $table->string('npwp')->nullable();
            $table->string('nitku')->nullable();
            $table->string('npwp_number')->nullable();
            $table->string('nik')->nullable();
            $table->string('pic_address')->nullable();
            $table->string('npwp_address')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->string('bill_to_name')->nullable();
            $table->foreignId('billing_group_id')->nullable();
            $table->string('npwp_number')->nullable();
            $table->string('nik_number')->nullable();
            $table->string('tax_address')->nullable();
            $table->string('invoice_status')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('customers')->insert(['id' => 1, 'name' => 'Cahaya Mandiri Utama', 'nik' => '3171000000000001', 'npwp' => '0000000000000000']);
        DB::table('customer_tax_settings')->insert(['customer_id' => 1, 'tax_number' => '9989807896988979', 'tax_address' => 'Jalan Raya Jakarta II']);
    }

    protected function tearDown(): void
    {
        foreach (['invoices', 'billing_groups', 'customer_tax_settings', 'customers'] as $table) {
            Schema::dropIfExists($table);
        }
        parent::tearDown();
    }

    public function test_identity_is_stamped_at_issue_and_survives_master_changes(): void
    {
        $invoice = Invoice::create(['invoice_number' => 'JKT-INV/26-06/0001', 'customer_id' => 1, 'invoice_status' => 'draft', 'status' => 'draft']);

        $stored = DB::table('invoices')->where('id', $invoice->id)->first();
        $this->assertSame('Cahaya Mandiri Utama', $stored->bill_to_name);
        $this->assertSame('3171000000000001', $stored->nik_number);
        $this->assertSame('9989807896988979', $stored->npwp_number);
        $this->assertSame('Jalan Raya Jakarta II', $stored->tax_address);

        // Master berubah di tengah penagihan.
        DB::table('customers')->where('id', 1)->update(['name' => 'Nama Baru', 'nik' => '9999']);
        DB::table('customer_tax_settings')->update(['tax_number' => '1111111111111111', 'tax_address' => 'Alamat Baru']);

        $fresh = Invoice::find($invoice->id);
        $this->assertSame('Cahaya Mandiri Utama', $fresh->bill_to_name);
        $this->assertSame('3171000000000001', $fresh->nik_number);
        $this->assertSame('9989807896988979', $fresh->npwp_number);
    }

    public function test_values_given_on_create_are_not_overwritten(): void
    {
        $invoice = Invoice::create(['invoice_number' => 'X', 'customer_id' => 1, 'npwp_number' => '1234', 'invoice_status' => 'draft', 'status' => 'draft']);

        $this->assertSame('1234', DB::table('invoices')->where('id', $invoice->id)->value('npwp_number'));
    }

    public function test_reload_tax_refreshes_name_and_nik(): void
    {
        $invoice = Invoice::create(['invoice_number' => 'Y', 'customer_id' => 1, 'invoice_status' => 'draft', 'status' => 'draft']);
        DB::table('customers')->where('id', 1)->update(['name' => 'Nama Baru', 'nik' => '9999']);

        $invoice = Invoice::find($invoice->id);
        $invoice->stampIdentitySnapshot(true, ['bill_to_name', 'nik_number']);
        $invoice->save();

        $stored = DB::table('invoices')->where('id', $invoice->id)->first();
        $this->assertSame('Nama Baru', $stored->bill_to_name);
        $this->assertSame('9999', $stored->nik_number);
        $this->assertSame('9989807896988979', $stored->npwp_number, 'NPWP diatur oleh Reload Tax sendiri, tidak ditimpa di sini');
    }

    public function test_print_and_detail_views_no_longer_read_live_customer_tax(): void
    {
        $print = file_get_contents(resource_path('views/finance/invoices/print_template.blade.php'));
        $show = file_get_contents(resource_path('views/finance/invoices/show.blade.php'));

        $this->assertStringNotContainsString('customerTaxSettings', $print);
        $this->assertStringNotContainsString('$invoice->customer?->nik', $print);
        $this->assertStringNotContainsString('customerTaxSettings->first()', $show);
        $this->assertStringContainsString('$invoice->nik_number', $print);
    }
}
