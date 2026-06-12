<?php

namespace Tests\Feature;

use App\Models\Finance\Invoice;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InvoiceActivityTimelineTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->nullable();
            $table->string('invoice_status')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoice_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id');
            $table->string('activity_type');
            $table->text('notes')->nullable();
            $table->foreignId('created_by');
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoice_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id');
            $table->date('follow_up_date');
            $table->string('follow_up_type');
            $table->text('notes');
            $table->string('status');
            $table->foreignId('created_by');
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bank_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->nullable();
            $table->date('receipt_date')->nullable();
            $table->string('invoice_reference')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->date('payment_date')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('status')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Finance User', 'email' => 'finance@example.test', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Admin User', 'email' => 'admin@example.test', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    protected function tearDown(): void
    {
        foreach ([
            'bank_receipts',
            'invoice_follow_ups',
            'invoice_activities',
            'invoices',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_invoice_activity_timeline_combines_invoice_logs_follow_ups_receipts_and_created_fallback(): void
    {
        $invoiceId = DB::table('invoices')->insertGetId([
            'invoice_number' => 'SBY-INV/26-06/0001',
            'invoice_status' => 'approved',
            'created_by' => 1,
            'updated_by' => 2,
            'created_at' => Carbon::parse('2026-06-10 09:00:00'),
            'updated_at' => Carbon::parse('2026-06-12 11:30:00'),
        ]);

        DB::table('invoice_activities')->insert([
            'invoice_id' => $invoiceId,
            'activity_type' => 'updated',
            'notes' => 'Invoice approved',
            'created_by' => 2,
            'created_at' => Carbon::parse('2026-06-11 16:08:43'),
            'updated_at' => Carbon::parse('2026-06-11 16:08:43'),
        ]);

        DB::table('invoice_follow_ups')->insert([
            'invoice_id' => $invoiceId,
            'follow_up_date' => '2026-06-10',
            'follow_up_type' => 'phone',
            'notes' => 'Follow up pembayaran',
            'status' => 'pending',
            'created_by' => 1,
            'updated_by' => 2,
            'created_at' => Carbon::parse('2026-06-11 16:14:09'),
            'updated_at' => Carbon::parse('2026-06-12 11:03:36'),
        ]);

        DB::table('bank_receipts')->insert([
            'receipt_number' => 'BR-001',
            'invoice_reference' => 'SBY-INV/26-06/0001',
            'amount' => 6000000,
            'payment_date' => '2026-06-13',
            'payment_method' => 'transfer',
            'status' => 'verified',
            'notes' => 'Payment received',
            'created_by' => 2,
            'created_at' => Carbon::parse('2026-06-13 09:00:00'),
            'updated_at' => Carbon::parse('2026-06-13 09:00:00'),
        ]);

        $timeline = Invoice::findOrFail($invoiceId)->activity_timeline;

        $this->assertSame('Payment Receipt', $timeline->first()['title']);
        $this->assertContains('Invoice Created', $timeline->pluck('title')->all());
        $this->assertContains('Updated', $timeline->pluck('title')->all());
        $this->assertContains('Follow Up - Phone Call', $timeline->pluck('title')->all());
        $this->assertContains('Follow Up Updated', $timeline->pluck('title')->all());
        $this->assertContains('Payment Receipt', $timeline->pluck('title')->all());

        $created = $timeline->firstWhere('title', 'Invoice Created');
        $this->assertSame('Finance User', $created['performed_by']);
    }

    public function test_invoice_activity_timeline_does_not_add_created_fallback_when_created_activity_exists(): void
    {
        $invoiceId = DB::table('invoices')->insertGetId([
            'invoice_number' => 'SBY-INV/26-06/0002',
            'invoice_status' => 'draft',
            'created_by' => 1,
            'created_at' => Carbon::parse('2026-06-10 09:00:00'),
            'updated_at' => Carbon::parse('2026-06-10 09:00:00'),
        ]);

        DB::table('invoice_activities')->insert([
            'invoice_id' => $invoiceId,
            'activity_type' => 'created',
            'notes' => 'Invoice created',
            'created_by' => 1,
            'created_at' => Carbon::parse('2026-06-10 09:01:00'),
            'updated_at' => Carbon::parse('2026-06-10 09:01:00'),
        ]);

        $timeline = Invoice::findOrFail($invoiceId)->activity_timeline;

        $this->assertSame(1, $timeline->where('title', 'Created')->count());
        $this->assertSame(0, $timeline->where('title', 'Invoice Created')->count());
    }
}
