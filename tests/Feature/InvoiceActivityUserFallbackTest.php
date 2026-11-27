<?php

namespace Tests\Feature;

use App\Models\Invoice;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Prod 28 Nov 2026 01:10: cron finance:auto-generate-invoices gagal membuat invoice karena
 * invoice_activities.created_by jatuh ke user id 1 yang tidak ada (user terkecil di prod id 28).
 */
class InvoiceActivityUserFallbackTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['invoice_activities', 'invoices', 'users'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('email')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->string('invoice_number')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('invoice_activities', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('invoice_id');
            $t->string('activity_type');
            $t->text('notes')->nullable();
            $t->unsignedBigInteger('created_by');
            $t->timestamps();
        });

        DB::table('users')->insert([['id' => 28, 'name' => 'Admin'], ['id' => 60, 'name' => 'Finance']]);
    }

    public function test_activity_without_login_or_creator_uses_an_existing_user(): void
    {
        $invoiceId = DB::table('invoices')->insertGetId(['invoice_number' => 'INV/1', 'created_by' => null]);

        Invoice::findOrFail($invoiceId)->logActivity('created', 'Invoice auto-generated');

        $this->assertSame(28, (int) DB::table('invoice_activities')->value('created_by'));
    }

    public function test_existing_candidates_win_and_missing_ones_are_skipped(): void
    {
        $this->assertSame(60, Invoice::resolveActivityUserId([null, 1, 60]));
        $this->assertSame(28, Invoice::resolveActivityUserId([1, 999]));
    }
}
