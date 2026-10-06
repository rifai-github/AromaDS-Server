<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Finance\CommissionCalculation;
use App\Models\Finance\MarketingTarget;
use App\Services\Finance\CommissionCalculationService;
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
            $t->decimal('contract_value', 15, 2)->default(0);
            $t->decimal('net_value', 15, 2)->nullable();
            $t->boolean('is_installed')->default(false);
            $t->date('installed_date')->nullable();
            $t->string('status')->default('active');
            $t->timestamps();
            $t->softDeletes();
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
        foreach (['user_marketing_levels', 'marketing_levels', 'commission_transfers', 'achievements',
            'commission_calculations', 'cr_variables', 'commission_levels', 'marketing_targets',
            'achievement_periods', 'bank_receipts', 'invoice_activities', 'invoices', 'contracts', 'users'] as $table) {
            Schema::dropIfExists($table);
        }
        parent::tearDown();
    }

    private function contract(int $id, int $customerId, float $value, ?float $net = null, bool $installed = true): Contract
    {
        DB::table('contracts')->insert([
            'id' => $id, 'contract_number' => "CT-{$id}", 'customer_id' => $customerId,
            'marketing_id' => 1, 'contract_value' => $value, 'net_value' => $net,
            'is_installed' => $installed, 'installed_date' => $installed ? '2026-09-10' : null,
            // status 'draft' supaya kontrak lain customer ini tidak dianggap "kontrak aktif sebelumnya"
            'status' => 'draft', 'created_at' => now(), 'updated_at' => now(),
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
}
