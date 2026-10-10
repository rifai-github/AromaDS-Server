<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Finance\CommissionCalculation;
use App\Models\Finance\MarketingTarget;
use App\Models\Finance\RenewalContractAssignment;
use App\Services\Finance\CommissionCalculationService;
use App\Services\Finance\RenewalAssignmentService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Komisi otomatis per kontrak: tidak boleh dobel saat dihitung ulang (ganti install_date)
 * dan edit net_value hanya menggeser target sebesar selisihnya.
 */
class CommissionCalculationDuplicateTest extends TestCase
{
    private CommissionCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('contracts', function (Blueprint $t) {
            $t->id();
            $t->string('contract_number');
            $t->foreignId('customer_id');
            $t->foreignId('marketing_id')->nullable();
            $t->foreignId('quotation_id')->nullable();
            $t->foreignId('commission_recipient_id')->nullable();
            $t->string('contract_type')->nullable();
            $t->decimal('contract_value', 15, 2)->default(0);
            $t->decimal('net_value', 15, 2)->nullable();
            $t->boolean('is_installed')->default(false);
            $t->date('installed_date')->nullable();
            $t->string('status')->default('active');
            $t->foreignId('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        // Contract::update() menulis audit trail.
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->string('model_type')->nullable();
            $t->unsignedBigInteger('model_id')->nullable();
            $t->string('action')->nullable();
            $t->text('old_values')->nullable();
            $t->text('new_values')->nullable();
            $t->text('changed_fields')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('ip_address')->nullable();
            $t->text('user_agent')->nullable();
            $t->string('page_name')->nullable();
            $t->string('module_name')->nullable();
            $t->timestamps();
        });
        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->string('invoice_number');
            $t->string('contract_number')->nullable();
            $t->decimal('total_amount', 15, 2)->default(0);
            $t->string('invoice_status')->default('draft');
            $t->string('status')->nullable(); // kolom legacy yang disinkronkan hook saving() Invoice
            $t->date('invoice_date')->nullable();
            $t->foreignId('updated_by')->nullable();
            $t->decimal('grand_total', 15, 2)->default(0);
            $t->decimal('total_paid', 15, 2)->default(0);
            $t->decimal('outstanding', 15, 2)->default(0);
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('invoice_activities', function (Blueprint $t) {
            $t->id();
            $t->foreignId('invoice_id');
            $t->string('activity_type')->nullable();
            $t->text('notes')->nullable();
            $t->foreignId('created_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('bank_receipts', function (Blueprint $t) {
            $t->id();
            $t->string('receipt_number')->nullable();
            $t->string('invoice_reference')->nullable();
            $t->decimal('amount', 15, 2)->default(0);
            $t->date('payment_date')->nullable();
            $t->string('status')->default('pending');
            $t->foreignId('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('achievement_periods', function (Blueprint $t) {
            $t->id();
            $t->string('period_name');
            $t->date('start_date');
            $t->date('end_date');
            $t->string('status');
            $t->text('description')->nullable();
            $t->foreignId('created_by')->nullable();
            $t->foreignId('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('marketing_targets', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id');
            $t->foreignId('achievement_period_id');
            $t->string('target_type');
            $t->decimal('target_amount', 15, 2);
            $t->decimal('achieved_amount', 15, 2)->default(0);
            $t->boolean('is_locked')->default(false);
            $t->date('lock_date')->nullable();
            $t->foreignId('locked_by')->nullable();
            $t->text('notes')->nullable();
            $t->foreignId('created_by')->nullable();
            $t->foreignId('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('commission_levels', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->decimal('min_percentage', 8, 2);
            $t->decimal('max_percentage', 8, 2)->nullable();
            $t->decimal('commission_rate', 8, 2);
            $t->string('target_type');
            $t->integer('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->text('description')->nullable();
            $t->foreignId('created_by')->nullable();
            $t->foreignId('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('cr_variables', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->integer('cr_days');
            $t->text('description')->nullable();
            $t->boolean('is_active')->default(true);
            $t->boolean('is_default')->default(false);
            $t->foreignId('created_by')->nullable();
            $t->foreignId('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('commission_calculations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id');
            $t->foreignId('achievement_period_id')->nullable();
            $t->foreignId('marketing_target_id')->nullable();
            $t->foreignId('contract_id')->nullable();
            $t->string('calculation_type')->nullable();
            $t->decimal('base_amount', 15, 2)->default(0);
            $t->decimal('net_value', 15, 2)->nullable();
            $t->decimal('commission_rate', 8, 2)->default(0);
            $t->foreignId('commission_level_id')->nullable();
            $t->decimal('commission_amount', 15, 2)->default(0);
            $t->decimal('bonus_amount', 15, 2)->default(0);
            $t->decimal('penalty_amount', 15, 2)->default(0);
            $t->decimal('final_amount', 15, 2)->default(0);
            // Sama dengan MySQL produksi: nilai di luar enum (mis. 'pending'/'void') ditolak.
            $t->enum('status', ['calculated', 'approved', 'paid', 'cancelled'])->default('calculated');
            $t->date('calculation_date')->nullable();
            $t->date('payment_date')->nullable();
            $t->text('calculation_notes')->nullable();
            $t->boolean('is_installed')->default(false);
            $t->date('cash_receipt_date')->nullable();
            $t->foreignId('cr_variable_id')->nullable();
            $t->date('cr_due_date')->nullable();
            $t->boolean('is_cr_expired')->default(false);
            $t->boolean('is_commission_void')->default(false);
            $t->foreignId('commission_transfer_id')->nullable();
            $t->foreignId('approved_by')->nullable();
            $t->foreignId('created_by')->nullable();
            $t->foreignId('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('achievements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id');
            $t->foreignId('achievement_period_id')->nullable();
            $t->foreignId('contract_id')->nullable();
            $t->string('achievement_type')->nullable();
            $t->decimal('target_amount', 15, 2)->default(0);
            $t->decimal('achieved_amount', 15, 2)->default(0);
            $t->decimal('commission_rate', 8, 2)->default(0);
            $t->foreignId('commission_level_id')->nullable();
            $t->decimal('commission_amount', 15, 2)->default(0);
            $t->string('status')->nullable();
            $t->date('achievement_date')->nullable();
            $t->integer('cut_off_start_date')->nullable();
            $t->integer('cut_off_end_date')->nullable();
            $t->integer('cut_off_tolerance_days')->nullable();
            $t->boolean('is_installed')->default(false);
            $t->date('installed_date')->nullable();
            $t->text('notes')->nullable();
            $t->foreignId('created_by')->nullable();
            $t->foreignId('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('commission_transfers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('contract_id');
            $t->foreignId('from_user_id');
            $t->foreignId('to_user_id');
            $t->foreignId('commission_calculation_id')->nullable();
            $t->decimal('commission_amount', 15, 2)->nullable();
            $t->string('status');
            $t->text('reason')->nullable();
            $t->foreignId('approved_by')->nullable();
            $t->timestamp('approved_at')->nullable();
            $t->text('approval_notes')->nullable();
            $t->foreignId('created_by')->nullable();
            $t->foreignId('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('commission_payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('commission_calculation_id');
            $t->foreignId('user_id');
            $t->decimal('amount', 15, 2)->default(0);
            $t->string('status')->default('pending');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('renewal_contract_assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('achievement_period_id');
            $t->foreignId('user_id');
            $t->string('contract_number_from')->nullable();
            $t->string('contract_number_to')->nullable();
            $t->decimal('target_amount', 15, 2)->default(0);
            $t->boolean('is_locked')->default(false);
            $t->date('lock_date')->nullable();
            $t->foreignId('locked_by')->nullable();
            $t->text('notes')->nullable();
            $t->foreignId('created_by')->nullable();
            $t->foreignId('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('marketing_levels', function (Blueprint $t) {
            $t->id();
            $t->string('level_code')->nullable();
            $t->string('level_name')->nullable();
            $t->integer('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('user_marketing_levels', function (Blueprint $t) {
            $t->id();
            $t->foreignId('marketing_level_id');
            $t->foreignId('user_id');
            $t->foreignId('custom_level_id')->nullable();
            $t->date('effective_from')->nullable();
            $t->date('effective_to')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
        });

        $now = now();
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Budi', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'Sari', 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('achievement_periods')->insert([
            'id' => 1, 'period_name' => 'Sep 2026', 'start_date' => '2026-09-01',
            'end_date' => '2026-09-30', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('marketing_targets')->insert([
            ['id' => 1, 'user_id' => 1, 'achievement_period_id' => 1, 'target_type' => 'new',
             'target_amount' => 100_000_000, 'achieved_amount' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'user_id' => 1, 'achievement_period_id' => 1, 'target_type' => 'renewal',
             'target_amount' => 50_000_000, 'achieved_amount' => 0, 'created_at' => $now, 'updated_at' => $now],
        ]);
        foreach ([
            ['L1', 0, 49.99, 0.5, 1],
            ['L2', 50, 99.99, 1.0, 2],
            ['L3', 100, null, 1.5, 3],
        ] as [$name, $min, $max, $rate, $sort]) {
            DB::table('commission_levels')->insert([
                'name' => $name, 'min_percentage' => $min, 'max_percentage' => $max,
                'commission_rate' => $rate, 'target_type' => 'both', 'sort_order' => $sort,
                'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        DB::table('cr_variables')->insert([
            'id' => 1, 'name' => 'CR 90', 'cr_days' => 90, 'is_active' => true,
            'is_default' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);

        Carbon::setTestNow('2026-09-20 10:00:00');
        $this->service = new CommissionCalculationService;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        foreach (['renewal_contract_assignments', 'user_marketing_levels', 'marketing_levels', 'commission_payments', 'commission_transfers', 'achievements',
            'commission_calculations', 'cr_variables', 'commission_levels', 'marketing_targets',
            'achievement_periods', 'bank_receipts', 'invoice_activities', 'invoices', 'audit_logs', 'contracts', 'users'] as $table) {
            Schema::dropIfExists($table);
        }
        parent::tearDown();
    }

    private function contract(int $id, int $customerId, float $value, ?float $net = null, bool $installed = true,
        ?string $type = null, string $status = 'draft'): Contract
    {
        DB::table('contracts')->insert([
            'id' => $id, 'contract_number' => "CT-{$id}", 'customer_id' => $customerId,
            'marketing_id' => 1, 'contract_value' => $value, 'net_value' => $net,
            'is_installed' => $installed, 'installed_date' => $installed ? '2026-09-10' : null,
            'contract_type' => $type,
            // status 'draft' supaya kontrak lain customer ini tidak dianggap "kontrak aktif sebelumnya"
            'status' => $status, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return Contract::findOrFail($id);
    }

    private function achieved(): float
    {
        return (float) MarketingTarget::find(1)->achieved_amount;
    }

    public function test_three_new_contracts_climb_tiers(): void
    {
        $a = $this->service->calculateCommissionForContract($this->contract(1, 101, 40_000_000));
        $b = $this->service->calculateCommissionForContract($this->contract(2, 102, 30_000_000, 25_000_000));
        $c = $this->service->calculateCommissionForContract($this->contract(3, 103, 50_000_000));

        $this->assertEquals(200_000, $a['amount']);
        $this->assertEquals(250_000, $b['amount']);
        $this->assertEquals(750_000, $c['amount']);
        $this->assertEquals(115_000_000, $this->achieved());
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_new_commission_is_stored_with_a_status_the_production_enum_accepts(): void
    {
        // QA 1 Okt: komisi otomatis tak pernah tersimpan di MySQL karena kode menulis
        // status 'pending' padahal enum-nya calculated|approved|paid|cancelled. Tabel test
        // memakai enum yang sama, jadi nilai di luar itu kini ditolak di sini juga.
        $result = $this->service->calculateCommissionForContract($this->contract(1, 101, 40_000_000));

        $this->assertTrue($result['success'], $result['message']);
        $this->assertSame('calculated', $result['commission']->status);
    }

    public function test_paid_invoice_approves_or_cancels_the_commission_with_valid_statuses(): void
    {
        $contract = $this->contract(1, 101, 40_000_000);
        $this->service->calculateCommissionForContract($contract);

        $invoice = new \App\Models\Invoice();
        $invoice->contract_id = $contract->id;
        $invoice->setRelation('contract', $contract);

        $onTime = $this->service->calculateCommissionOnCashReceipt($invoice, now()->toDateString());
        $this->assertTrue($onTime['success']);
        $this->assertSame('approved', $onTime['commission']->fresh()->status);

        $late = $this->service->calculateCommissionOnCashReceipt($invoice, now()->subDays(400)->toDateString());
        $this->assertFalse($late['success']);
        $this->assertSame('cancelled', $late['commission']->fresh()->status);
        $this->assertTrue((bool) $late['commission']->fresh()->is_commission_void);
    }

    public function test_commission_created_by_payment_is_approved_right_away(): void
    {
        // Kontrak sudah terinstall tapi belum punya komisi (mis. Tanggal Install diisi sebelum
        // perbaikan): pembayaran invoice yang membuat komisinya, langsung approved.
        $contract = $this->contract(1, 101, 40_000_000);
        $invoice = new \App\Models\Invoice();
        $invoice->contract_id = $contract->id;
        $invoice->setRelation('contract', $contract);

        $result = $this->service->calculateCommissionOnCashReceipt($invoice, now()->toDateString());

        $this->assertTrue($result['success'], $result['message']);
        $this->assertSame('approved', $result['commission']->status);
    }

    public function test_commission_service_accepts_the_finance_invoice_model_used_by_bank_payment_and_invoice_screens(): void
    {
        // BankReceiptService/InvoiceController memakai App\Models\Finance\Invoice; tipe parameter
        // lama (App\Models\Invoice) membuat langkah komisi melempar TypeError.
        $contract = $this->contract(1, 101, 40_000_000);
        $this->service->calculateCommissionForContract($contract);

        $invoice = new \App\Models\Finance\Invoice();
        $invoice->contract_number = $contract->contract_number;

        $result = $this->service->calculateCommissionOnCashReceipt($invoice, now()->toDateString());

        $this->assertTrue($result['success'], $result['message']);
        $this->assertSame('approved', $result['commission']->fresh()->status);
    }

    public function test_processing_a_bank_receipt_that_references_an_invoice_pays_it_and_creates_the_commission(): void
    {
        $contract = $this->contract(1, 101, 40_000_000);
        DB::table('invoices')->insert([
            'id' => 1, 'invoice_number' => 'INV-1', 'contract_number' => $contract->contract_number,
            'invoice_status' => 'sent', 'grand_total' => 44_400_000, 'outstanding' => 44_400_000,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('bank_receipts')->insert([
            'id' => 1, 'receipt_number' => 'BR-1', 'invoice_reference' => 'INV-1',
            'amount' => 44_400_000, 'payment_date' => now()->toDateString(), 'status' => 'verified',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs(\App\Models\User::find(1));
        $request = \Illuminate\Http\Request::create('/finance/bank-receipts/1/process', 'POST');
        $request->setLaravelSession(app('session.store'));
        app()->instance('request', $request);

        app(\App\Http\Controllers\Finance\BankReceiptController::class)
            ->process(\App\Models\Finance\BankReceipt::findOrFail(1));

        $this->assertDatabaseHas('invoices', ['id' => 1, 'invoice_status' => 'paid', 'outstanding' => 0]);
        $this->assertDatabaseHas('bank_receipts', ['id' => 1, 'status' => 'processed']);
        $this->assertDatabaseHas('commission_calculations', ['contract_id' => 1, 'status' => 'approved']);
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_processing_a_bank_receipt_with_a_different_amount_keeps_the_invoice_unpaid(): void
    {
        $contract = $this->contract(1, 101, 40_000_000);
        DB::table('invoices')->insert([
            'id' => 1, 'invoice_number' => 'INV-1', 'contract_number' => $contract->contract_number,
            'invoice_status' => 'sent', 'grand_total' => 44_400_000, 'outstanding' => 44_400_000,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('bank_receipts')->insert([
            'id' => 1, 'invoice_reference' => 'INV-1', 'amount' => 1_000_000,
            'payment_date' => now()->toDateString(), 'status' => 'verified',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs(\App\Models\User::find(1));
        $request = \Illuminate\Http\Request::create('/finance/bank-receipts/1/process', 'POST');
        $request->setLaravelSession(app('session.store'));
        app()->instance('request', $request);

        app(\App\Http\Controllers\Finance\BankReceiptController::class)
            ->process(\App\Models\Finance\BankReceipt::findOrFail(1));

        $this->assertDatabaseHas('invoices', ['id' => 1, 'invoice_status' => 'sent']);
        $this->assertDatabaseHas('bank_receipts', ['id' => 1, 'status' => 'processed']);
        $this->assertDatabaseCount('commission_calculations', 0);
    }

    public function test_mark_paid_on_an_invoice_creates_the_approved_commission(): void
    {
        // Jalur Invoice -> Paid (endpoint mark-paid dan form edit memakai triggerAutoCommissionCalculation).
        $contract = $this->contract(1, 101, 40_000_000);
        DB::table('invoices')->insert([
            'id' => 1, 'invoice_number' => 'INV-1', 'contract_number' => $contract->contract_number,
            'invoice_status' => 'sent', 'invoice_date' => now()->subDays(5)->toDateString(),
            'grand_total' => 44_400_000, 'outstanding' => 44_400_000,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs(\App\Models\User::find(1));
        $response = app(\App\Http\Controllers\Finance\InvoiceController::class)
            ->markPaid(\App\Models\Finance\Invoice::findOrFail(1));

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getContent());
        $this->assertDatabaseHas('invoices', ['id' => 1, 'invoice_status' => 'paid', 'outstanding' => 0]);
        $this->assertDatabaseHas('commission_calculations', ['contract_id' => 1, 'status' => 'approved']);
    }

    public function test_automatic_commission_cannot_be_approved_manually_before_the_invoice_is_paid(): void
    {
        // QA 1 Okt: komisi otomatis ternyata bisa di-approve manual tanpa invoice dibayar.
        $contract = $this->contract(1, 101, 40_000_000);
        $auto = $this->service->calculateCommissionForContract($contract)['commission'];
        $this->assertTrue($auto->isAwaitingCashReceipt());

        $this->actingAs(\App\Models\User::find(1));
        $request = \Illuminate\Http\Request::create('/finance/commissions/'.$auto->id.'/approve', 'POST');
        $request->setLaravelSession(app('session.store'));
        app()->instance('request', $request);

        app(\App\Http\Controllers\Finance\CommissionController::class)->approve($auto);

        $this->assertSame('calculated', $auto->fresh()->status);
        $this->assertStringContainsString('tidak bisa di-approve manual', (string) session('error'));

        // Setelah invoice dibayar, status sudah approved lewat pembayaran (bukan tombol).
        $invoice = new \App\Models\Finance\Invoice();
        $invoice->contract_number = $contract->contract_number;
        $this->service->calculateCommissionOnCashReceipt($invoice, now()->toDateString());
        $this->assertSame('approved', $auto->fresh()->status);
        $this->assertFalse($auto->fresh()->isAwaitingCashReceipt());
    }

    public function test_manual_commission_can_still_be_approved_by_hand(): void
    {
        $manual = \App\Models\Finance\CommissionCalculation::create([
            'user_id' => 1, 'achievement_period_id' => 1, 'calculation_type' => 'manual',
            'base_amount' => 3_000_000, 'commission_rate' => 1, 'commission_amount' => 30_000,
            'final_amount' => 30_000, 'status' => 'calculated', 'calculation_date' => now(),
        ]);
        $this->assertFalse($manual->isAwaitingCashReceipt());

        $this->actingAs(\App\Models\User::find(1));
        $request = \Illuminate\Http\Request::create('/finance/commissions/'.$manual->id.'/approve', 'POST');
        $request->setLaravelSession(app('session.store'));
        app()->instance('request', $request);

        app(\App\Http\Controllers\Finance\CommissionController::class)->approve($manual);

        $this->assertSame('approved', $manual->fresh()->status);
    }
    public function test_paying_the_invoice_does_not_approve_a_manual_commission_on_the_same_contract(): void
    {
        // QA 5 Okt (SMG-AG/26-09/0011): QA membuat komisi Manual yang menunjuk kontrak ini.
        // Mark as Paid lalu meng-approve komisi manual itu dan komisi otomatisnya tak dibuat.
        $contract = $this->contract(1, 101, 40_000_000);
        $manual = \App\Models\Finance\CommissionCalculation::create([
            'user_id' => 1, 'achievement_period_id' => 1, 'contract_id' => 1, 'calculation_type' => 'manual',
            'base_amount' => 3_000_000, 'commission_rate' => 1, 'commission_amount' => 30_000,
            'final_amount' => 30_000, 'status' => 'calculated', 'calculation_date' => now(),
        ]);

        $invoice = new \App\Models\Finance\Invoice();
        $invoice->contract_number = $contract->contract_number;
        $result = $this->service->calculateCommissionOnCashReceipt($invoice, now()->toDateString());

        $this->assertTrue($result['success'], $result['message']);
        $this->assertSame('new', $result['commission']->calculation_type);
        $this->assertSame('approved', $result['commission']->fresh()->status);

        $manual->refresh();
        $this->assertSame('calculated', $manual->status);
        $this->assertNull($manual->cash_receipt_date);
    }
    private function approveTransfer(int $calculationId, float $amount): void
    {
        DB::table('commission_transfers')->insert([
            'id' => 1, 'contract_id' => 1, 'from_user_id' => 1, 'to_user_id' => 2,
            'commission_calculation_id' => $calculationId, 'commission_amount' => $amount,
            'status' => 'pending', 'reason' => 'berbagi', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs(\App\Models\User::find(1));
        $request = \Illuminate\Http\Request::create('/finance/commission-transfers/1/approve', 'POST');
        $request->setLaravelSession(app('session.store'));
        app()->instance('request', $request);

        app(\App\Http\Controllers\Finance\CommissionTransferController::class)
            ->approve($request, \App\Models\Finance\CommissionTransfer::findOrFail(1));
    }

    public function test_full_commission_transfer_moves_the_commission_to_the_recipient(): void
    {
        // QA 8 Okt (SMG-AG/26-01/0028): transfer approved tapi komisi tetap atas nama Wahyu.
        $calc = $this->service->calculateCommissionForContract($this->contract(1, 101, 40_000_000))['commission'];

        $this->approveTransfer($calc->id, (float) $calc->final_amount);

        $calc->refresh();
        $this->assertSame(2, (int) $calc->user_id);
        $this->assertSame(1, (int) $calc->commission_transfer_id);
        $this->assertSame('approved', DB::table('commission_transfers')->find(1)->status);
        $this->assertSame(1, \App\Models\Finance\CommissionCalculation::count());
        // Target tetap milik marketing asal.
        $this->assertEquals(40_000_000, $this->achieved());
    }

    public function test_partial_transfer_splits_and_both_parts_are_approved_when_the_invoice_is_paid(): void
    {
        $contract = $this->contract(1, 101, 40_000_000);
        $calc = $this->service->calculateCommissionForContract($contract)['commission']; // 200.000 @0.5%

        $this->approveTransfer($calc->id, 50_000);

        $parts = \App\Models\Finance\CommissionCalculation::orderBy('id')->get();
        $this->assertCount(2, $parts);
        $this->assertEquals([1 => 150_000, 2 => 50_000], $parts->mapWithKeys(fn ($c) => [$c->user_id => (float) $c->final_amount])->all());

        $invoice = new \App\Models\Finance\Invoice();
        $invoice->contract_number = $contract->contract_number;
        $this->service->calculateCommissionOnCashReceipt($invoice, now()->toDateString());

        $this->assertSame(['approved', 'approved'], \App\Models\Finance\CommissionCalculation::orderBy('id')->pluck('status')->all());
    }

    public function test_transfer_is_refused_when_the_commission_already_has_a_payment(): void
    {
        $calc = $this->service->calculateCommissionForContract($this->contract(1, 101, 40_000_000))['commission'];
        DB::table('commission_payments')->insert(['commission_calculation_id' => $calc->id, 'user_id' => 1, 'amount' => 200_000, 'status' => 'pending']);

        $this->approveTransfer($calc->id, (float) $calc->final_amount);

        $this->assertSame(1, (int) $calc->fresh()->user_id);
        $this->assertSame('pending', DB::table('commission_transfers')->find(1)->status);
        $this->assertStringContainsString('sudah punya pembayaran', (string) session('error'));
    }

    public function test_transfer_form_only_offers_commissions_without_an_active_payment(): void
    {
        $calc = $this->service->calculateCommissionForContract($this->contract(1, 101, 40_000_000))['commission'];

        $list = fn () => app(\App\Http\Controllers\Finance\CommissionTransferController::class)
            ->getCalculationsByContract(\Illuminate\Http\Request::create('/x', 'GET', ['user_id' => 1]), 1)
            ->getData(true)['data'];

        $this->assertCount(1, $list());

        DB::table('commission_payments')->insert(['commission_calculation_id' => $calc->id, 'user_id' => 1, 'amount' => 200_000, 'status' => 'processing']);
        $this->assertCount(0, $list());

        DB::table('commission_payments')->update(['status' => 'cancelled']);
        $this->assertCount(1, $list());
    }

    public function test_a_commission_held_by_a_payment_cannot_be_hidden(): void
    {
        // QA 8 Okt: komisi #3 disembunyikan padahal payment #1 masih menunjuknya.
        $calc = $this->service->calculateCommissionForContract($this->contract(1, 101, 40_000_000))['commission'];
        DB::table('commission_payments')->insert(['commission_calculation_id' => $calc->id, 'user_id' => 1, 'amount' => 200_000, 'status' => 'processing']);

        $this->actingAs(\App\Models\User::find(1));
        $request = \Illuminate\Http\Request::create('/finance/commissions/'.$calc->id, 'DELETE');
        $request->setLaravelSession(app('session.store'));
        app()->instance('request', $request);

        app(\App\Http\Controllers\Finance\CommissionController::class)->destroy($calc);

        $this->assertNotNull(\App\Models\Finance\CommissionCalculation::find($calc->id));
        $this->assertStringContainsString('masih punya pembayaran', (string) session('error'));
    }

    public function test_paying_a_later_invoice_does_not_reopen_a_paid_commission(): void
    {
        $contract = $this->contract(1, 101, 40_000_000);
        $calc = $this->service->calculateCommissionForContract($contract)['commission'];
        $calc->update(['status' => 'paid']);

        $invoice = new \App\Models\Finance\Invoice();
        $invoice->contract_number = $contract->contract_number;
        $result = $this->service->calculateCommissionOnCashReceipt($invoice, now()->toDateString());

        $this->assertTrue($result['success']);
        $this->assertSame('paid', $calc->fresh()->status);
    }

    public function test_automatic_achievement_status_follows_target_progress(): void
    {
        // Target 100 jt: 40 jt -> pending, +60 jt = 100 jt -> achieved, +50 jt -> exceeded.
        $this->service->calculateCommissionForContract($this->contract(1, 101, 40_000_000));
        $this->service->calculateCommissionForContract($this->contract(2, 102, 60_000_000));
        $this->service->calculateCommissionForContract($this->contract(3, 103, 50_000_000));

        $this->assertSame(['pending', 'achieved', 'exceeded'], DB::table('achievements')->orderBy('id')->pluck('status')->all());
    }

    public function test_calculating_same_contract_twice_keeps_one_record_and_one_target_increment(): void
    {
        $contract = $this->contract(1, 101, 40_000_000);
        $this->service->calculateCommissionForContract($contract);
        $second = $this->service->calculateCommissionForContract($contract->fresh());

        $this->assertFalse($second['success']);
        $this->assertTrue($second['already_calculated']);
        $this->assertSame(1, CommissionCalculation::where('contract_id', 1)->count());
        $this->assertSame(1, DB::table('achievements')->where('contract_id', 1)->count());
        $this->assertEquals(40_000_000, $this->achieved());
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_manual_adjustment_does_not_block_automatic_commission(): void
    {
        DB::table('commission_calculations')->insert([
            'user_id' => 1, 'contract_id' => 1, 'calculation_type' => 'adjustment',
            'status' => 'calculated', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $result = $this->service->calculateCommissionForContract($this->contract(1, 101, 40_000_000));

        $this->assertTrue($result['success']);
        $this->assertEquals(40_000_000, $this->achieved());
    }

    public function test_net_value_edit_shifts_target_by_delta_and_retiers_at_original_position(): void
    {
        $this->service->calculateCommissionForContract($this->contract(1, 101, 40_000_000));
        $contractB = $this->contract(2, 102, 30_000_000, 25_000_000);
        $this->service->calculateCommissionForContract($contractB);
        $this->service->calculateCommissionForContract($this->contract(3, 103, 50_000_000));

        DB::table('contracts')->where('id', 2)->update(['net_value' => 28_000_000]);
        $result = $this->service->recalculateCommissionForContract($contractB->fresh());

        // B dihitung pada posisi 40 jt + 28 jt = 68% (L2 1%), bukan 143%.
        $this->assertTrue($result['success']);
        $this->assertEquals(280_000, $result['amount']);
        $this->assertEquals(118_000_000, $this->achieved());
        $this->assertSame(3, CommissionCalculation::count());

        $calc = CommissionCalculation::where('contract_id', 2)->sole();
        $this->assertEquals(28_000_000, (float) $calc->net_value);
        $this->assertEquals(1.0, (float) $calc->commission_rate);
        $this->assertEquals(280_000, (float) $calc->final_amount);
        $this->assertEquals(68_000_000, (float) DB::table('achievements')->where('contract_id', 2)->value('achieved_amount'));
        $this->assertSame(0, DB::transactionLevel());
    }

    private function updateContractNet(int $contractId, float $netValue): array
    {
        // Endpoint field "Contract Net" di Detail Kontrak (marketing/contracts/{id}/update-net-value).
        $user = new class extends \App\Models\User
        {
            protected $table = 'users';

            public function hasPermission($permission)
            {
                return true;
            }
        };
        $this->actingAs($user->newQuery()->findOrFail(1));

        $request = \Illuminate\Http\Request::create("/marketing/contracts/{$contractId}/update-net-value", 'POST', ['net_value' => $netValue]);
        $response = app(\App\Http\Controllers\Marketing\ContractController::class)->updateNetValue($request, $contractId);
        $this->assertSame(200, $response->getStatusCode(), (string) $response->getContent());

        return $response->getData(true);
    }

    public function test_contract_net_field_recalculates_existing_calculated_commission(): void
    {
        // QA 11 Okt: Contract Net diubah setelah Tanggal Install, komisi & target tetap.
        // Field di layar memanggil updateNetValue(), yang dulu tidak menghitung ulang.
        $this->service->calculateCommissionForContract($this->contract(1, 101, 40_000_000));
        $this->assertEquals(40_000_000, $this->achieved());

        $data = $this->updateContractNet(1, 30_000_000);

        $calc = CommissionCalculation::where('contract_id', 1)->sole();
        $this->assertEquals(30_000_000, (float) $calc->net_value);
        $this->assertEquals(150_000, (float) $calc->final_amount);
        $this->assertEquals(30_000_000, $this->achieved());
        $this->assertStringContainsString('dihitung ulang', (string) $data['commission_message']);
    }

    public function test_contract_net_field_before_install_does_not_create_commission(): void
    {
        $this->contract(1, 101, 6_000_000, null, false);

        $data = $this->updateContractNet(1, 5_000_000);

        $this->assertNull($data['commission_message']);
        $this->assertSame(0, CommissionCalculation::count());
        $this->assertEquals(0, $this->achieved());

        // Saat Tanggal Install memicu perhitungan, Net yang dipakai (5 jt), bukan Contract Value.
        DB::table('contracts')->where('id', 1)->update(['is_installed' => true, 'installed_date' => '2026-09-10']);
        $result =$this->service->calculateCommissionForContract(Contract::findOrFail(1));
        $this->assertEquals(5_000_000, (float) $result['commission']->net_value);
        $this->assertEquals(6_000_000, (float) $result['commission']->base_amount);
    }

    public function test_contract_net_field_reports_refusal_on_approved_commission(): void
    {
        $this->service->calculateCommissionForContract($this->contract(1, 101, 40_000_000));
        DB::table('commission_calculations')->update(['status' => 'approved']);

        $data = $this->updateContractNet(1, 20_000_000);

        $this->assertEquals(200_000, (float) CommissionCalculation::first()->final_amount);
        $this->assertStringContainsString('tidak dihitung ulang', (string) $data['commission_message']);
    }

    public function test_net_value_edit_on_approved_commission_is_not_applied(): void
    {
        $contract = $this->contract(1, 101, 40_000_000, 40_000_000);
        $this->service->calculateCommissionForContract($contract);
        DB::table('commission_calculations')->update(['status' => 'approved']);

        DB::table('contracts')->where('id', 1)->update(['net_value' => 20_000_000]);
        $result = $this->service->recalculateCommissionForContract($contract->fresh());

        $this->assertFalse($result['success']);
        $this->assertSame(1, CommissionCalculation::count());
        $this->assertEquals(200_000, (float) CommissionCalculation::first()->final_amount);
        $this->assertEquals(40_000_000, $this->achieved());
    }

    public function test_early_returns_do_not_leave_transaction_open(): void
    {
        $notInstalled = $this->service->calculateCommissionForContract($this->contract(1, 101, 40_000_000, null, installed: false));
        $this->assertFalse($notInstalled['success']);
        $this->assertSame(0, DB::transactionLevel());

        DB::table('marketing_targets')->update(['is_locked' => true]);
        $noTarget = $this->service->calculateCommissionForContract($this->contract(2, 102, 40_000_000));
        $this->assertFalse($noTarget['success']);
        $this->assertSame(0, DB::transactionLevel());

        DB::table('marketing_targets')->update(['is_locked' => false]);
        DB::table('commission_levels')->delete();
        $noLevel = $this->service->calculateCommissionForContract($this->contract(3, 103, 40_000_000));
        $this->assertFalse($noLevel['success']);
        $this->assertSame(0, DB::transactionLevel());
        $this->assertEquals(0, $this->achieved()); // penambahan target ikut dibatalkan
    }

    public function test_new_contract_type_is_not_treated_as_renewal_for_an_existing_customer(): void
    {
        // QA 8 Okt (MDO-AG/26-03/0006): SQ/kontrak bertipe new, tetapi customer-nya punya
        // kontrak aktif lain -> dulu dianggap renewal, dicari target Renewal, komisi tak terbentuk.
        $this->contract(1, 101, 10_000_000, null, false, 'new', 'active');
        $result = $this->service->calculateCommissionForContract($this->contract(2, 101, 40_000_000, null, true, 'new', 'active'));

        $this->assertTrue($result['success'], $result['message']);
        $this->assertSame('new', $result['commission']->calculation_type);
        $this->assertEquals(40_000_000, $this->achieved());
    }

    public function test_contract_without_explicit_type_keeps_the_previous_contract_fallback(): void
    {
        $this->contract(1, 101, 10_000_000, null, false, null, 'active');
        $result = $this->service->calculateCommissionForContract($this->contract(2, 101, 10_000_000, null, true, null, 'active'));

        $this->assertTrue($result['success'], $result['message']);
        $this->assertSame('renewal', $result['commission']->calculation_type);
    }

    public function test_renewal_commission_uses_renewal_contract_assignment_as_target(): void
    {
        // QA 8 Okt (MDO-AG/26-03/0004): target renewal disiapkan lewat Renewal Contract
        // Assignment, tidak lewat Marketing Target. Dulu assignment tidak dibaca sama sekali.
        MarketingTarget::whereKey(2)->forceDelete();
        $assignment = RenewalContractAssignment::create([
            'achievement_period_id' => 1, 'user_id' => 1, 'target_amount' => 20_000_000,
        ]);

        $result = $this->service->calculateCommissionForContract($this->contract(5, 105, 12_000_000, null, true, 'renewal'));

        $this->assertTrue($result['success'], $result['message']);
        $this->assertSame('renewal', $result['commission']->calculation_type);
        $target = MarketingTarget::where('user_id', 1)->where('target_type', 'renewal')->firstOrFail();
        $this->assertEquals(20_000_000, (float) $target->target_amount);
        $this->assertEquals(12_000_000, (float) $target->achieved_amount);
        $this->assertSame($target->id, (int) $result['commission']->marketing_target_id);
        // 60% -> L2 1%
        $this->assertEquals(120_000, $result['amount']);

        // Target assignment diubah -> target hasil assignment ikut, pencapaian tetap.
        $assignment->update(['target_amount' => 30_000_000]);
        (new RenewalAssignmentService)->syncGeneratedMarketingTarget($assignment);
        $this->assertEquals(30_000_000, (float) $target->fresh()->target_amount);
        $this->assertEquals(12_000_000, (float) $target->fresh()->achieved_amount);
    }

    public function test_existing_renewal_marketing_target_wins_over_assignment(): void
    {
        RenewalContractAssignment::create([
            'achievement_period_id' => 1, 'user_id' => 1, 'target_amount' => 20_000_000,
        ]);

        $result = $this->service->calculateCommissionForContract($this->contract(5, 105, 12_000_000, null, true, 'renewal'));

        $this->assertTrue($result['success'], $result['message']);
        $this->assertSame(2, (int) $result['commission']->marketing_target_id);
        $this->assertSame(1, MarketingTarget::where('target_type', 'renewal')->count());
    }

    public function test_assignment_range_that_excludes_the_contract_is_not_used(): void
    {
        MarketingTarget::whereKey(2)->forceDelete();
        RenewalContractAssignment::create([
            'achievement_period_id' => 1, 'user_id' => 1, 'target_amount' => 20_000_000,
            'contract_number_from' => 'CT-100', 'contract_number_to' => 'CT-199',
        ]);

        $result = $this->service->calculateCommissionForContract($this->contract(5, 105, 12_000_000, null, true, 'renewal'));

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Renewal Contract Assignment', $result['message']);
        $this->assertSame(0, MarketingTarget::where('target_type', 'renewal')->count());
        $this->assertSame(0, DB::transactionLevel());
    }
}
