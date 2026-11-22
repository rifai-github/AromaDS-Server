<?php

namespace Tests\Feature;

use App\Http\Controllers\Finance\CommissionPaymentController;
use App\Models\Finance\CommissionPayment;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * QA 8 Okt: satu kalkulasi komisi bisa dibayar berkali-kali. Kalkulasi yang sudah punya
 * payment tetap "approved" dan tetap ditawarkan di New Payment, dan tidak ada tombol di
 * daftar payment untuk menandainya Completed (statusnya tak pernah jadi Paid).
 */
class CommissionPaymentSingleUseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('achievement_periods', function (Blueprint $table) {
            $table->id();
            $table->string('period_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('commission_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('achievement_period_id')->nullable();
            $table->string('calculation_type')->nullable();
            $table->decimal('final_amount', 15, 2)->default(0);
            $table->decimal('commission_amount', 15, 2)->default(0);
            $table->decimal('original_amount', 15, 2)->nullable();
            $table->enum('status', ['calculated', 'approved', 'paid', 'cancelled', 'over_period'])->default('calculated');
            $table->date('payment_date')->nullable();
            $table->timestamp('payout_requested_at')->nullable();
            $table->foreignId('payout_requested_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('commission_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_calculation_id');
            $table->foreignId('user_id');
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('payment_method')->default('bank_transfer');
            $table->string('payment_reference')->nullable();
            $table->date('payment_date')->nullable();
            $table->string('status')->default('pending');
            $table->text('payment_notes')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('bank_name')->nullable();
            $table->foreignId('processed_by')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('users')->insert(['id' => 1, 'name' => 'Wahyu']);
        DB::table('commission_calculations')->insert([
            ['id' => 1, 'user_id' => 1, 'calculation_type' => 'new', 'final_amount' => 45000, 'status' => 'approved'],
            ['id' => 2, 'user_id' => 1, 'calculation_type' => 'new', 'final_amount' => 24000, 'status' => 'approved'],
            ['id' => 3, 'user_id' => 1, 'calculation_type' => 'new', 'final_amount' => 10000, 'status' => 'approved'],
        ]);
        // Kalkulasi 1 sudah punya payment pending; kalkulasi 3 punya payment yang gagal.
        DB::table('commission_payments')->insert([
            ['id' => 10, 'commission_calculation_id' => 1, 'user_id' => 1, 'amount' => 45000, 'payment_date' => '2026-10-08', 'status' => 'pending'],
            ['id' => 11, 'commission_calculation_id' => 3, 'user_id' => 1, 'amount' => 10000, 'payment_date' => '2026-10-08', 'status' => 'failed'],
        ]);

        $this->actingAs(User::find(1));
        $request = Request::create('/finance/commission-payments', 'POST');
        $request->setLaravelSession(app('session.store'));
        app()->instance('request', $request);
    }

    protected function tearDown(): void
    {
        foreach (['commission_payments', 'commission_calculations', 'achievement_periods', 'users'] as $table) {
            Schema::dropIfExists($table);
        }
        parent::tearDown();
    }

    private function storeRequest(int $calculationId, ?float $amount = null): Request
    {
        $request = Request::create('/finance/commission-payments', 'POST', [
            'commission_calculation_id' => $calculationId,
            'user_id' => 1,
            // Nominal pembayaran tak boleh melebihi komisinya (meeting 17 Nov 2026).
            'amount' => $amount ?? (float) DB::table('commission_calculations')->where('id', $calculationId)->value('final_amount'),
            'payment_method' => 'bank_transfer',
            'payment_date' => '2026-10-08',
        ]);
        $request->setLaravelSession(app('session.store'));

        return $request;
    }

    public function test_new_payment_only_offers_calculations_without_an_active_payment(): void
    {
        $view = app(CommissionPaymentController::class)->create();

        // 1 sudah dibayar (pending) -> disembunyikan; 3 hanya punya payment gagal -> boleh lagi.
        $this->assertEqualsCanonicalizing([2, 3], $view->getData()['calculations']->pluck('id')->all());
    }

    public function test_store_rejects_a_second_payment_for_the_same_calculation(): void
    {
        app(CommissionPaymentController::class)->store($this->storeRequest(1));

        $this->assertSame(1, CommissionPayment::where('commission_calculation_id', 1)->count());
        $this->assertStringContainsString('sudah punya pembayaran #10', (string) session('error'));
    }

    public function test_store_still_accepts_a_calculation_whose_previous_payment_failed(): void
    {
        app(CommissionPaymentController::class)->store($this->storeRequest(3));

        $this->assertSame(1, CommissionPayment::where('commission_calculation_id', 3)->where('status', 'pending')->count());
    }

    public function test_completing_a_payment_marks_its_commission_paid(): void
    {
        app(CommissionPaymentController::class)->markAsCompleted(CommissionPayment::findOrFail(10));

        $this->assertSame('completed', DB::table('commission_payments')->find(10)->status);
        $calculation = DB::table('commission_calculations')->find(1);
        $this->assertSame('paid', $calculation->status);
        $this->assertStringStartsWith('2026-10-08', (string) $calculation->payment_date);
    }

    public function test_cancelling_a_pending_payment_frees_the_commission_for_a_new_payment(): void
    {
        $request = Request::create('/finance/commission-payments/10/cancel', 'POST', ['reason' => 'salah input']);
        $request->setLaravelSession(app('session.store'));
        app(CommissionPaymentController::class)->cancel($request, CommissionPayment::findOrFail(10));

        $this->assertSame('cancelled', DB::table('commission_payments')->find(10)->status);

        $view = app(CommissionPaymentController::class)->create();
        $this->assertContains(1, $view->getData()['calculations']->pluck('id')->all());
    }

    public function test_a_completed_payment_cannot_be_cancelled(): void
    {
        DB::table('commission_payments')->where('id', 10)->update(['status' => 'completed']);

        $request = Request::create('/finance/commission-payments/10/cancel', 'POST', ['reason' => 'x']);
        $request->setLaravelSession(app('session.store'));
        app(CommissionPaymentController::class)->cancel($request, CommissionPayment::findOrFail(10));

        $this->assertSame('completed', DB::table('commission_payments')->find(10)->status);
    }

    public function test_payment_cannot_exceed_the_commission_amount(): void
    {
        app(CommissionPaymentController::class)->store($this->storeRequest(2, 24_001));

        $this->assertSame(0, CommissionPayment::where('commission_calculation_id', 2)->count());
        $this->assertStringContainsString('melebihi komisi #2', (string) session('error'));
    }

    public function test_over_period_commission_is_payable_only_after_its_amount_is_restored(): void
    {
        DB::table('commission_calculations')->insert([
            ['id' => 4, 'user_id' => 1, 'calculation_type' => 'new', 'final_amount' => 0, 'original_amount' => 300000, 'status' => 'over_period'],
            ['id' => 5, 'user_id' => 1, 'calculation_type' => 'new', 'final_amount' => 300000, 'original_amount' => 300000, 'status' => 'over_period'],
        ]);

        $offered = app(CommissionPaymentController::class)->create()->getData()['calculations']->pluck('id')->all();
        $this->assertNotContains(4, $offered);
        $this->assertContains(5, $offered);

        app(CommissionPaymentController::class)->store($this->storeRequest(4, 0));
        $this->assertStringContainsString('Over Period dengan nominal Rp 0', (string) session('error'));

        app(CommissionPaymentController::class)->store($this->storeRequest(5));
        $payment = CommissionPayment::where('commission_calculation_id', 5)->firstOrFail();

        app(CommissionPaymentController::class)->markAsCompleted($payment);
        $this->assertSame('paid', DB::table('commission_calculations')->find(5)->status);
    }

    public function test_marketing_request_payout_flags_payable_commissions_for_finance(): void
    {
        $dashboard = app(\App\Http\Controllers\Marketing\CommissionDashboardController::class);
        $dashboard->requestPayout();

        // 1 sudah punya payment pending -> tidak ikut; 2 dan 3 siap cair.
        $this->assertNull(DB::table('commission_calculations')->find(1)->payout_requested_at);
        $this->assertNotNull(DB::table('commission_calculations')->find(2)->payout_requested_at);
        $this->assertNotNull(DB::table('commission_calculations')->find(3)->payout_requested_at);
        $this->assertSame(1, (int) DB::table('commission_calculations')->find(2)->payout_requested_by);

        $requests = app(CommissionPaymentController::class)->index(Request::create('/finance/commission-payments'))
            ->getData()['payoutRequests'];
        $this->assertCount(1, $requests);
        $this->assertSame(2, $requests[0]['count']);
        $this->assertEquals(34000, $requests[0]['amount']);
        $this->assertEqualsCanonicalizing([2, 3], $requests[0]['calculation_ids']);

        // Request ke-2 tanpa komisi baru tidak mengubah apa pun.
        $dashboard->requestPayout();
        $this->assertStringContainsString('Tidak ada komisi siap cair', (string) session('error'));

        // Setelah Finance membuat pembayaran, komisi itu keluar dari daftar request.
        app(CommissionPaymentController::class)->store($this->storeRequest(2));
        $requests = app(CommissionPaymentController::class)->index(Request::create('/finance/commission-payments'))
            ->getData()['payoutRequests'];
        $this->assertSame([3], $requests[0]['calculation_ids']);
    }

    public function test_payment_list_has_a_cancel_button(): void
    {
        // QA 8 Okt: tidak ada tombol batal; ▶ ternyata "Mark as Processing".
        $source = file_get_contents(View::getFinder()->find('finance.commission-payments.index'));

        $this->assertStringContainsString("route('finance.commission-payments.cancel'", $source);
        $this->assertStringContainsString('function askCancelReason', $source);
    }

    public function test_payment_list_has_processing_and_completed_buttons(): void
    {
        $source = file_get_contents(View::getFinder()->find('finance.commission-payments.index'));

        $this->assertStringContainsString("route('finance.commission-payments.mark-processing'", $source);
        $this->assertStringContainsString("route('finance.commission-payments.mark-completed'", $source);
    }

    // MOM 17 Nov 2026 p1: admin mencentang marketing lalu memproses pencairan; Finance dapat email.
    private function bulkRequest(array $userIds): Request
    {
        $request = Request::create('/finance/commission-payments/bulk-process', 'POST', [
            'user_ids' => $userIds,
            'payment_method' => 'bank_transfer',
            'payment_date' => '2026-11-22',
        ]);
        $request->setLaravelSession(app('session.store'));

        return $request;
    }

    public function test_bulk_process_pays_every_open_commission_of_the_selected_marketing_and_emails_finance(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        config(['commission.finance_notification_emails' => ['tiff@example.test']]);

        $response = app(CommissionPaymentController::class)->bulkProcess($this->bulkRequest([1]));

        // 1 sudah punya payment aktif -> dilewati; 2 dan 3 dibuatkan payment Pending.
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(1, CommissionPayment::where('commission_calculation_id', 2)->where('status', 'pending')->count());
        $this->assertSame(1, CommissionPayment::where('commission_calculation_id', 3)->where('status', 'pending')->count());
        $this->assertSame(1, CommissionPayment::where('commission_calculation_id', 1)->count());

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\CommissionPayoutProcessedMail::class, function ($mail) {
            return $mail->hasTo('tiff@example.test')
                && $mail->adminName === 'Wahyu'
                && $mail->marketingCount === 1
                && abs($mail->totalAmount - 34000) < 0.01;
        });
    }

    public function test_bulk_process_without_finance_address_still_pays_but_warns(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        config(['commission.finance_notification_emails' => []]);

        app(CommissionPaymentController::class)->bulkProcess($this->bulkRequest([1]));

        $this->assertSame(1, CommissionPayment::where('commission_calculation_id', 2)->count());
        \Illuminate\Support\Facades\Mail::assertNothingSent();
        $this->assertStringContainsString('COMMISSION_FINANCE_EMAILS', (string) session('success'));
    }
}
