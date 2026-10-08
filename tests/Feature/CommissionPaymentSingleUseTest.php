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
            $table->enum('status', ['calculated', 'approved', 'paid', 'cancelled'])->default('calculated');
            $table->date('payment_date')->nullable();
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

    private function storeRequest(int $calculationId): Request
    {
        $request = Request::create('/finance/commission-payments', 'POST', [
            'commission_calculation_id' => $calculationId,
            'user_id' => 1,
            'amount' => 45000,
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

    public function test_payment_list_has_processing_and_completed_buttons(): void
    {
        $source = file_get_contents(View::getFinder()->find('finance.commission-payments.index'));

        $this->assertStringContainsString("route('finance.commission-payments.mark-processing'", $source);
        $this->assertStringContainsString("route('finance.commission-payments.mark-completed'", $source);
    }
}
