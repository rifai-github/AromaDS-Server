<?php

namespace Tests\Feature;

use App\Http\Controllers\Finance\BankWebhookController;
use App\Models\BankReceipt;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BankWebhookIdempotencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::setDefaultDriver('array');

        Schema::create('company_virtual_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('customer_id');
            $table->foreignId('bank_payment_id')->nullable();
            $table->string('account_number');
            $table->string('account_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number');
            $table->foreignId('customer_id');
            $table->string('invoice_status')->default('approved');
            $table->string('status')->nullable();
            $table->date('invoice_date')->nullable();
            $table->decimal('total_amount', 15, 2);
            $table->decimal('grand_total', 15, 2)->nullable();
            $table->decimal('total_paid', 15, 2)->default(0);
            $table->decimal('outstanding', 15, 2)->default(0);
            $table->date('payment_date')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bank_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique();
            $table->date('receipt_date')->nullable();
            $table->foreignId('customer_id');
            $table->string('invoice_reference')->nullable();
            $table->foreignId('bank_id')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_holder_name')->nullable();
            $table->decimal('amount', 15, 2);
            $table->date('payment_date')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('status')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('company_virtual_accounts')->insert([
            'id' => 1,
            'customer_id' => 50,
            'account_number' => '999998',
            'account_name' => 'Customer VA',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('invoices')->insert([
            'id' => 10,
            'invoice_number' => 'INV-TEST-001',
            'customer_id' => 50,
            'invoice_status' => 'approved',
            'invoice_date' => '2026-07-22',
            'total_amount' => 125000,
            'total_paid' => 0,
            'outstanding' => 125000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('bank_receipts');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('company_virtual_accounts');

        parent::tearDown();
    }

    public function test_duplicate_transaction_id_is_acknowledged_without_creating_another_receipt(): void
    {
        $controller = app(BankWebhookController::class);

        $first = $controller->handleVirtualAccountPayment($this->webhookRequest());
        $second = $controller->handleVirtualAccountPayment($this->webhookRequest());

        $this->assertSame(200, $first->getStatusCode());
        $this->assertSame(200, $second->getStatusCode());
        $this->assertDatabaseCount('bank_receipts', 1);
        $this->assertTrue($second->getData(true)['data']['duplicate']);
        $this->assertSame('paid', DB::table('invoices')->where('id', 10)->value('invoice_status'));
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_unknown_va_does_not_leave_an_open_database_transaction(): void
    {
        $response = app(BankWebhookController::class)->handleVirtualAccountPayment(
            $this->webhookRequest(['virtual_account_number' => 'UNKNOWN-VA'])
        );

        $this->assertSame(404, $response->getStatusCode());
        $this->assertDatabaseCount('bank_receipts', 0);
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_separate_payments_receive_sequential_unique_receipt_numbers(): void
    {
        $controller = app(BankWebhookController::class);
        $first = $controller->handleVirtualAccountPayment($this->webhookRequest());

        DB::table('invoices')->insert([
            'id' => 11,
            'invoice_number' => 'INV-TEST-002',
            'customer_id' => 50,
            'invoice_status' => 'approved',
            'invoice_date' => '2026-07-22',
            'total_amount' => 125000,
            'total_paid' => 0,
            'outstanding' => 125000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $second = $controller->handleVirtualAccountPayment($this->webhookRequest([
            'transaction_id' => 'BANK-TXN-0002',
            'bank_reference' => 'BANK-REF-0002',
        ]));

        $this->assertSame(200, $first->getStatusCode());
        $this->assertSame(200, $second->getStatusCode());
        $this->assertSame([
            'BR-20260722-0001',
            'BR-20260722-0002',
        ], DB::table('bank_receipts')->orderBy('id')->pluck('receipt_number')->all());
    }

    public function test_receipt_failure_rolls_back_invoice_payment(): void
    {
        $originalDispatcher = BankReceipt::getEventDispatcher();
        BankReceipt::setEventDispatcher(clone $originalDispatcher);
        BankReceipt::creating(function () {
            throw new \RuntimeException('Simulated receipt write failure.');
        });

        try {
            $response = app(BankWebhookController::class)->handleVirtualAccountPayment(
                $this->webhookRequest()
            );
        } finally {
            BankReceipt::setEventDispatcher($originalDispatcher);
        }

        $this->assertSame(500, $response->getStatusCode());
        $this->assertDatabaseCount('bank_receipts', 0);
        $this->assertSame('approved', DB::table('invoices')->where('id', 10)->value('invoice_status'));
        $this->assertSame(125000.0, (float) DB::table('invoices')->where('id', 10)->value('outstanding'));
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_amount_mismatch_does_not_change_invoice_or_open_a_transaction(): void
    {
        $response = app(BankWebhookController::class)->handleVirtualAccountPayment(
            $this->webhookRequest(['amount' => 100000])
        );

        $this->assertSame(400, $response->getStatusCode());
        $this->assertDatabaseCount('bank_receipts', 0);
        $this->assertSame('approved', DB::table('invoices')->where('id', 10)->value('invoice_status'));
        $this->assertSame(0, DB::transactionLevel());
    }

    private function webhookRequest(array $overrides = []): Request
    {
        return Request::create('/api/bank-webhook/virtual-account-payment', 'POST', array_merge([
            'virtual_account_number' => '0000000000999998',
            'amount' => 125000,
            'payment_date' => '2026-07-22',
            'transaction_id' => 'BANK-TXN-0001',
            'bank_reference' => 'BANK-REF-0001',
            'customer_name' => 'Customer Test',
        ], $overrides));
    }
}
